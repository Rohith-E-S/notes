<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if the request is POST and has a question
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['question'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "notesdb";

// Connect to MySQL
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$user_id = $_SESSION['user_id'];
$question = $_POST['question'];

// Fetch relevant data from the database based on the question
$context = "";

// Search in notes
$notes_sql = "SELECT title, content, created_at FROM notes WHERE user_id = ?";
$notes_stmt = $conn->prepare($notes_sql);
$notes_stmt->bind_param("i", $user_id);
$notes_stmt->execute();
$notes_result = $notes_stmt->get_result();

while ($note = $notes_result->fetch_assoc()) {
    $context .= "Note: {$note['title']}\nContent: {$note['content']}\nCreated: {$note['created_at']}\n\n";
}

// Search in tasks
$tasks_sql = "SELECT title, description, status, due_date FROM tasks WHERE user_id = ?";
$tasks_stmt = $conn->prepare($tasks_sql);
$tasks_stmt->bind_param("i", $user_id);
$tasks_stmt->execute();
$tasks_result = $tasks_stmt->get_result();

while ($task = $tasks_result->fetch_assoc()) {
    $context .= "Task: {$task['title']}\nDescription: {$task['description']}\nStatus: {$task['status']}\nDue Date: {$task['due_date']}\n\n";
}

// Search in events
$events_sql = "SELECT title, description, event_date, event_time, event_end_time FROM events WHERE user_id = ?";
$events_stmt = $conn->prepare($events_sql);
$events_stmt->bind_param("i", $user_id);
$events_stmt->execute();
$events_result = $events_stmt->get_result();

while ($event = $events_result->fetch_assoc()) {
    $context .= "Event: {$event['title']}\nDescription: {$event['description']}\nDate: {$event['event_date']}\nTime: {$event['event_time']} - {$event['event_end_time']}\n\n";
}

// Close database connection
$conn->close();

// Your Gemini API key - using the same key from summarize.php
$apiKey = "AIzaSyDT642gkqTNO45h6ZP-hRufEmzGID3Sc7A";
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;

// Prepare the request data with context and question
$data = [
    "contents" => [
        [
            "parts" => [
                [
                    "text" => "You are a helpful assistant for a productivity app. Answer the following question based on the user's data. If you don't find relevant information in the data, say so politely.\n\nUser's Data:\n$context\n\nQuestion: $question"
                ]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.2,
        "topK" => 40,
        "topP" => 0.95,
        "maxOutputTokens" => 1024
    ]
];

// Initialize cURL session
$ch = curl_init($apiUrl);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing only

// Execute the request
$response = curl_exec($ch);

// Get HTTP status code
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for errors
if (curl_errno($ch)) {
    error_log("Chatbot API Error: " . curl_error($ch));
    http_response_code(500);
    echo json_encode([
        'error' => 'API request failed: ' . curl_error($ch)
    ]);
    exit();
}

// Close cURL session
curl_close($ch);

// If HTTP status code is not 200, return error
if ($httpCode != 200) {
    error_log("Chatbot API Error: HTTP Code " . $httpCode . ", Response: " . $response);
    http_response_code($httpCode);
    echo json_encode([
        'error' => 'API returned error code: ' . $httpCode,
        'response' => $response
    ]);
    exit();
}

// Decode the response
$result = json_decode($response, true);

// Extract the answer from the response
if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $answer = $result['candidates'][0]['content']['parts'][0]['text'];
    echo json_encode(['answer' => $answer]);
} else {
    error_log("Chatbot API Error: Unexpected response format: " . $response);
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to generate answer',
        'response' => $result
    ]);
}
?>