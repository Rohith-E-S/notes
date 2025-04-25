<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$servername = "localhost";
$username = "root";
$password = "";
$database = "notesdb";

// Connect to MySQL
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Retrieve the filter value from the GET request
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Modify the SQL query for tasks based on the filter
if ($filter === 'tasks' || $filter === 'all') {
    $task_sql = "SELECT * FROM tasks WHERE user_id = ? AND (title LIKE ? OR description LIKE ?)";
    if ($filter === 'tasks') {
        $task_sql .= " ORDER BY FIELD(status, 'pending', 'in-progress', 'completed'), due_date ASC";
    }
    $task_stmt = $conn->prepare($task_sql);
    $search_term = '%' . $search . '%';
    $task_stmt->bind_param("iss", $user_id, $search_term, $search_term);
    $task_stmt->execute();
    $task_result = $task_stmt->get_result();
} else {
    $task_result = null; // No tasks to display if the filter is "notes"
}

// Modify the SQL query for notes based on the filter
if ($filter === 'notes' || $filter === 'all') {
    $note_sql = "SELECT * FROM notes WHERE user_id = ? AND (title LIKE ? OR content LIKE ?) ORDER BY created_at DESC";
    $search_term = '%' . $search . '%';
    $note_stmt = $conn->prepare($note_sql);
    $note_stmt->bind_param("iss", $user_id, $search_term, $search_term);
    $note_stmt->execute();
    $note_result = $note_stmt->get_result();
} else {
    $note_result = null; // No notes to display if the filter is "tasks"
}

// Modify the SQL query for events based on the filter
if ($filter === 'events' || $filter === 'all') {
    $event_sql = "SELECT * FROM events WHERE user_id = ? AND (title LIKE ? OR description LIKE ?) ORDER BY event_date ASC";
    $search_term = '%' . $search . '%';
    $event_stmt = $conn->prepare($event_sql);
    $event_stmt->bind_param("iss", $user_id, $search_term, $search_term);
    $event_stmt->execute();
    $event_result = $event_stmt->get_result();
} else {
    $event_result = null; // No events to display if the filter is not "events" or "all"
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/chatbot.css">
    <script>
        // Function to set theme based on localStorage/preference
        function setInitialTheme() {
            const isDark = localStorage.getItem('color-theme') === 'dark' || 
                         (!('color-theme' in localStorage) && 
                          window.matchMedia('(prefers-color-scheme: dark)').matches);
            
            document.documentElement.classList.toggle('dark', isDark);
            
            // Set initial icon visibility
            const darkIcon = document.getElementById('theme-toggle-dark-icon');
            const lightIcon = document.getElementById('theme-toggle-light-icon');
            if (darkIcon && lightIcon) {
                darkIcon.classList.toggle('hidden', !isDark);
                lightIcon.classList.toggle('hidden', isDark);
            }
        }
        
        // Set theme on page load
        setInitialTheme();
        
        // Theme toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    const isDark = document.documentElement.classList.contains('dark');
                    
                    // Toggle theme
                    document.documentElement.classList.toggle('dark', !isDark);
                    localStorage.setItem('color-theme', isDark ? 'light' : 'dark');
                    
                    // Toggle icons
                    const darkIcon = document.getElementById('theme-toggle-dark-icon');
                    const lightIcon = document.getElementById('theme-toggle-light-icon');
                    if (darkIcon && lightIcon) {
                        darkIcon.classList.toggle('hidden', isDark);
                        lightIcon.classList.toggle('hidden', !isDark);
                    }
                });
            }
        });
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    <!-- Add animation classes to main elements -->
    <div class="animate-fade-in">
    <!-- Navigation Bar -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg mb-6 transition-colors duration-200 nav-item">
        <!-- Add animation classes to navigation elements -->
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex flex-wrap justify-between items-center py-3 space-y-2 sm:space-y-0">
                <!-- Left Section: Title -->
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white transition-colors duration-200 flex items-center">
                    <i class="fas fa-tasks mr-2"></i> <span class="hidden sm:inline">My Productivity Dashboard</span>
                </h1>

                <!-- Middle Section: Welcome Message -->
                <div class="order-3 md:order-2 mt-2 md:mt-0 flex justify-center">
                    <span class="text-gray-700 dark:text-gray-300 text-lg transition-colors duration-200 hidden md:inline-block">
                        Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                    </span>
                </div>

                <!-- Right Section: Theme Toggle, Search and Logout -->
                <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-3 md:space-x-4 mt-2 sm:mt-0 order-2 md:order-3 w-full sm:w-auto">
                    <!-- Theme Toggle Button -->
                    <div class="flex items-center space-x-4 w-full md:w-auto justify-center md:justify-start">
                        <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-full p-2 transition-colors duration-200" title="Toggle dark mode">
                            <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                            </svg>
                            <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.707.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Search Form -->
                    <form action="display.php" method="GET" class="flex items-center space-x-2 w-full md:w-auto search-container">
                        <!-- Separate Filter Dropdown -->
                        <select name="filter" onchange="this.form.submit()" class="py-1 sm:py-2 px-2 sm:px-3 text-xs sm:text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-300 dark:focus:ring-blue-700 w-full sm:w-auto" title="Filter your results">
                            <option value="all" <?php echo ($filter === 'all') ? 'selected' : ''; ?>>All</option>
                            <option value="tasks" <?php echo ($filter === 'tasks') ? 'selected' : ''; ?>>Tasks</option>
                            <option value="notes" <?php echo ($filter === 'notes') ? 'selected' : ''; ?>>Notes</option>
                            <option value="events" <?php echo ($filter === 'events') ? 'selected' : ''; ?>>Events</option>
                        </select>
                        
                        <!-- Search Input with Button -->
                        <div class="flex items-center w-full rounded-full overflow-hidden border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-sm">
                            <input type="text" name="search" placeholder="Search..." class="w-full px-2 text-xs sm:text-sm bg-transparent border-none focus:ring-0 text-gray-800 dark:text-white" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white p-2 focus:outline-none transition-colors duration-200" title="Search">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>

                    <!-- Logout Button -->
                    <a href="logout.php" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white px-3 py-1.5 rounded-full text-sm transition-all duration-200 flex items-center btn" title="Log out of your account">
                        <i class="fas fa-sign-out-alt mr-1.5"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <!-- Today's Schedule Gantt Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 sm:p-6 mb-6 card hover-scale">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center">
                    <i class="far fa-calendar-check mr-2"></i> Schedule for the day
                </h2>
                <div class="flex items-center">
                    <!-- Date selector -->
                    <form id="dateSelectForm" class="flex items-center">
                        <input type="date" id="scheduleDate" name="scheduleDate" 
                               class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 bg-white dark:bg-gray-700 text-gray-800 dark:text-white"
                               value="<?php echo isset($_GET['scheduleDate']) ? htmlspecialchars($_GET['scheduleDate']) : date('Y-m-d'); ?>">
                    </form>
                </div>
            </div>
            
            <?php
            // Get selected date (default to today if not specified)
            $selected_date = isset($_GET['scheduleDate']) ? $_GET['scheduleDate'] : date('Y-m-d');
            
            // Fetch events for the selected date
            $today_events_sql = "SELECT id, title, event_time, event_end_time FROM events 
                                WHERE user_id = ? AND event_date = ? 
                                ORDER BY event_time ASC";
            $today_events_stmt = $conn->prepare($today_events_sql);
            $today_events_stmt->bind_param("is", $user_id, $selected_date);
            $today_events_stmt->execute();
            $today_events_result = $today_events_stmt->get_result();
            
            // Fetch tasks for the selected date
            $today_tasks_sql = "SELECT id, title, due_date, status FROM tasks 
                               WHERE user_id = ? AND DATE(due_date) = ? 
                               ORDER BY due_date ASC";
            $today_tasks_stmt = $conn->prepare($today_tasks_sql);
            $today_tasks_stmt->bind_param("is", $user_id, $selected_date);
            $today_tasks_stmt->execute();
            $today_tasks_result = $today_tasks_stmt->get_result();
            
            $has_items = ($today_events_result->num_rows > 0 || $today_tasks_result->num_rows > 0);
            
            // Get current hour to highlight - use server timezone
            date_default_timezone_set('Asia/Kolkata'); // Set to Indian timezone
            $current_hour = (int)date('G');
            
            // Process events and tasks for the Gantt chart
            $events = [];
            $tasks = [];
            
            // Process events
            while ($event = $today_events_result->fetch_assoc()) {
                $start_time = strtotime($event['event_time']);
                $end_time = !empty($event['event_end_time']) ? strtotime($event['event_end_time']) : $start_time + 3600; // Default 1 hour
                
                $start_hour = date('G', $start_time) + (date('i', $start_time) / 60);
                $end_hour = date('G', $end_time) + (date('i', $end_time) / 60);
                
                // Handle events that cross midnight
                if ($end_hour < $start_hour) {
                    $end_hour = 24;
                }
                
                $events[] = [
                    'id' => 'event_' . $event['id'],
                    'title' => $event['title'],
                    'start' => $start_hour,
                    'end' => $end_hour,
                    'color' => 'bg-blue-500',
                    'start_time' => date('g:i A', $start_time),
                    'end_time' => date('g:i A', $end_time),
                    'duration' => round(($end_hour - $start_hour) * 60) . ' min',
                    'type' => 'event'
                ];
            }
            
            // Process tasks
            while ($task = $today_tasks_result->fetch_assoc()) {
                $due_time = strtotime($task['due_date']);
                $due_hour = date('G', $due_time) + (date('i', $due_time) / 60);
                
                $color = 'bg-red-500';
                $icon = 'fa-hourglass-half';
                if ($task['status'] == 'completed') {
                    $color = 'bg-green-500';
                    $icon = 'fa-check-circle';
                } elseif ($task['status'] == 'in-progress') {
                    $color = 'bg-yellow-500';
                    $icon = 'fa-spinner';
                }
                
                $tasks[] = [
                    'id' => 'task_' . $task['id'],
                    'title' => $task['title'],
                    'start' => $due_hour,
                    'end' => $due_hour + 0.5, // Show as a 30-minute block
                    'color' => $color,
                    'icon' => $icon,
                    'status' => $task['status'],
                    'due_time' => date('g:i A', $due_time),
                    'type' => 'task'
                ];
            }
            
            // Function to check if two items overlap
            function doesOverlap($lane, $item) {
                foreach ($lane as $existing) {
                    // Check if there's any overlap
                    if ($item['start'] < $existing['end'] && $item['end'] > $existing['start']) {
                        return true;
                    }
                }
                return false;
            }
            
            // Function to arrange items in lanes to prevent overlapping
            function arrangeItems($items) {
                $lanes = [];
                foreach ($items as $item) {
                    $placed = false;
                    for ($i = 0; $i < count($lanes); $i++) {
                        if (!doesOverlap($lanes[$i], $item)) {
                            $lanes[$i][] = $item;
                            $placed = true;
                            break;
                        }
                    }
                    if (!$placed) {
                        $lanes[] = [$item];
                    }
                }
                return $lanes;
            }
            
            // Arrange events and tasks in separate lanes
            $event_lanes = arrangeItems($events);
            $task_lanes = arrangeItems($tasks);
            
            // Calculate total number of lanes for spacing
            $total_lanes = count($event_lanes) + count($task_lanes);
            ?>
            
            <?php if ($has_items): ?>
                <div class="relative mt-6">
                    <!-- Current time indicator -->
                    <?php 
                    $now = new DateTime('now', new DateTimeZone('Asia/Kolkata')); // Use Indian timezone
                    $current_hour_decimal = $now->format('G') + ($now->format('i') / 60);
                    $current_position = ($current_hour_decimal / 24) * 100;
                    ?>
                    <div class="absolute h-full" style="left: <?php echo $current_position; ?>%; top: 0; z-index: 10;">
                        <div class="w-0.5 bg-red-500 animate-pulse" style="height: 100%;"></div>
                        <div class="w-3 h-3 rounded-full bg-red-500 -ml-1.5 -mt-1"></div>
                        <div class="absolute top-0 -ml-10 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-2 py-0.5 rounded text-xs font-bold">
                            <?php echo $now->format('g:i A'); ?>
                        </div>
                    </div>
                    
                    <!-- Time indicators with improved styling -->
                    <div class="flex border-b-2 border-gray-300 dark:border-gray-600 pb-2 mb-2">
                        <?php for ($hour = 0; $hour < 24; $hour++): ?>
                            <div class="flex-1 text-xs text-center <?php echo ($hour == $current_hour) ? 'text-red-600 dark:text-red-400 font-bold' : 'text-gray-500 dark:text-gray-400'; ?>">
                                <?php if ($hour % 3 == 0): ?>
                                    <span class="inline-block"><?php echo date('g A', strtotime("$hour:00")); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <!-- Gantt chart grid with improved styling -->
                    <div class="h-6 w-full flex mb-1 bg-gray-50 dark:bg-gray-700 rounded">
                        <?php for ($hour = 0; $hour < 24; $hour++): ?>
                            <div class="flex-1 border-r border-gray-200 dark:border-gray-600 
                                <?php echo ($hour >= 9 && $hour < 17) ? 'bg-blue-50/50 dark:bg-blue-900/10' : 
                                    (($hour >= 22 || $hour < 6) ? 'bg-gray-100 dark:bg-gray-800' : ''); ?>">
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <!-- Render events -->
                    <?php 
                    $lane_index = 0;
                    foreach ($event_lanes as $lane): 
                        foreach ($lane as $event):
                            $left = ($event['start'] / 24) * 100;
                            $width = (($event['end'] - $event['start']) / 24) * 100;
                            $top = 30 + ($lane_index * 45); // 45px per lane
                            
                            // Determine if this event is current
                            $is_current = ($current_hour_decimal >= $event['start'] && $current_hour_decimal < $event['end']);
                            $border_class = $is_current ? 'border-2 border-white dark:border-gray-900' : '';
                            $shadow_class = $is_current ? 'shadow-lg' : 'shadow';
                    ?>
                            <div class="absolute rounded-md px-3 py-1.5 text-xs text-white overflow-hidden whitespace-nowrap transition-all duration-200 <?php echo $event['color']; ?> <?php echo $shadow_class; ?> <?php echo $border_class; ?>"
                                 style="left: <?php echo $left; ?>%; width: <?php echo max(5, $width); ?>%; top: <?php echo $top; ?>px; z-index: <?php echo $is_current ? 5 : 1; ?>;"
                                 onclick="window.location.href='edit_event.php?id=<?php echo substr($event['id'], 6); ?>'">
                                <div class="flex items-center">
                                    <i class="far fa-calendar-alt mr-1.5"></i>
                                    <span class="font-medium"><?php echo htmlspecialchars($event['title']); ?></span>
                                    <span class="ml-auto text-white/80"><?php echo $event['start_time']; ?> - <?php echo $event['end_time']; ?></span>
                                </div>
                            </div>
                    <?php 
                        endforeach;
                        $lane_index++;
                    endforeach; 
                    ?>
                    
                    <!-- Render tasks -->
                    <?php 
                    foreach ($task_lanes as $lane): 
                        foreach ($lane as $task):
                            $left = ($task['start'] / 24) * 100;
                            $width = (($task['end'] - $task['start']) / 24) * 100;
                            $top = 30 + ($lane_index * 45); // Continue from where events left off
                            
                            // Determine if this task is current
                            $is_current = ($current_hour_decimal >= $task['start'] && $current_hour_decimal < $task['end']);
                            $border_class = $is_current ? 'border-2 border-white dark:border-gray-900' : '';
                            $shadow_class = $is_current ? 'shadow-lg' : 'shadow';
                    ?>
                            <div class="absolute rounded-md px-3 py-1.5 text-xs text-white overflow-hidden whitespace-nowrap transition-all duration-200 <?php echo $task['color']; ?> <?php echo $shadow_class; ?> <?php echo $border_class; ?>"
                                 style="left: <?php echo $left; ?>%; width: <?php echo max(5, $width); ?>%; top: <?php echo $top; ?>px; z-index: <?php echo $is_current ? 5 : 1; ?>;"
                                 onclick="window.location.href='edit_task.php?id=<?php echo substr($task['id'], 5); ?>'">
                                <div class="flex items-center">
                                    <i class="fas <?php echo $task['icon']; ?> mr-1.5"></i>
                                    <span class="font-medium"><?php echo htmlspecialchars($task['title']); ?></span>
                                    <span class="ml-auto text-white/80"><?php echo $task['due_time']; ?></span>
                                </div>
                            </div>
                    <?php 
                        endforeach;
                        $lane_index++;
                    endforeach; 
                    ?>
                    
                    <!-- Add some space based on number of lanes -->
                    <div style="height: <?php echo max(100, ($total_lanes * 45) + 50); ?>px;"></div>
                    <div class="flex flex-wrap items-center gap-4 mt-4 text-sm bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-blue-500 rounded-full mr-1.5"></div>
                            <span class="text-gray-700 dark:text-gray-300">Event</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-red-500 rounded-full mr-1.5"></div>
                            <span class="text-gray-700 dark:text-gray-300">Pending Task</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-yellow-500 rounded-full mr-1.5"></div>
                            <span class="text-gray-700 dark:text-gray-300">In-Progress Task</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-1.5"></div>
                            <span class="text-gray-700 dark:text-gray-300">Completed Task</span>
                        </div>
                        <div class="flex items-center ml-auto">
                            <div class="w-0.5 h-4 bg-red-500 mr-1.5"></div>
                            <span class="text-gray-700 dark:text-gray-300">Current Time</span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-gray-600 dark:text-gray-300">Nothing found.</p>
            <?php endif; ?>
        </div>
        
        <!-- Tasks Section -->
        <?php if ($filter === 'all' || $filter === 'tasks'): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6 card hover-scale">
            <div class="flex justify-between items-center mb-6">
                <a href="tasks.php"> <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Your Tasks</h2></a>
                <a href="add_task.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                    Add Task
                </a>
            </div>
            <?php 
            // Modify the SQL query to limit to 3 tasks if not searching
            if ($filter === 'all' && empty($search)) {
                $task_sql = "SELECT * FROM tasks WHERE user_id = ? ORDER BY due_date ASC LIMIT 3";
                $task_stmt = $conn->prepare($task_sql);
                $task_stmt->bind_param("i", $user_id);
                $task_stmt->execute();
                $task_result = $task_stmt->get_result();
            }
            
            if ($task_result && $task_result->num_rows > 0): ?>
                <div class="space-y-6">
                    <?php while ($task = $task_result->fetch_assoc()): ?>
                        <!-- Wrap the task in a styled block that's clickable -->
                        <div onclick="window.location.href='tasks.php'" class="block border rounded-lg p-6 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 hover:shadow-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition duration-200 cursor-pointer">
                            <div class="flex justify-between items-start">
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($task['title']); ?></h3>
                                <span class="flex items-center space-x-2">
                                    <span class="w-3 h-3 rounded-full inline-block 
                                        <?php echo ($task['status'] == 'completed') ? 'bg-green-500' : (($task['status'] == 'in-progress') ? 'bg-yellow-500' : 'bg-red-500'); ?>">
                                    </span>
                                    <span class="text-sm text-gray-800 dark:text-gray-300">
                                        <?php echo ucfirst($task['status']); ?>
                                    </span>
                                </span>
                            </div>
                            <!-- Task Description -->
                            <p class="text-[17px] text-gray-700 dark:text-gray-300 mt-4 mb-4 leading-relaxed line-clamp-2">
                                <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                            </p>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-semibold">Due:</span> <?php echo date('F j, Y, g:i a', strtotime($task['due_date'])); ?>
                            </div>
                            <div class="flex space-x-2 mt-3" onclick="event.stopPropagation()">
                                <a href="edit_task.php?id=<?php echo $task['id']; ?>" 
                                   class="bg-blue-500 text-white px-3 py-1 text-sm rounded-md hover:bg-blue-600 transition duration-200">
                                    Edit
                                </a>
                                <?php if ($task['status'] != 'completed'): ?>
                                    <button onclick="markTaskComplete(<?php echo $task['id']; ?>)" 
                                            class="bg-green-500 text-white px-3 py-1 text-sm rounded-md hover:bg-green-600 transition duration-200">
                                        Mark as Complete
                                    </button>
                                <?php endif; ?>
                                <?php if ($task['status'] == 'completed'): ?>
                                    <button onclick="deleteTask(<?php echo $task['id']; ?>)" 
                                            class="bg-red-500 text-white px-3 py-1 text-sm rounded-md hover:bg-red-600 transition duration-200">
                                        Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- View All Tasks button -->
                <div class="mt-6 text-center">
                    <a href="tasks.php" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg transition duration-200">
                        <i class="fas fa-tasks mr-2"></i> View All Tasks
                    </a>
                </div>
            <?php else: ?>
                <p class="text-gray-600 dark:text-gray-300">No tasks found.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Events Section -->
        <?php if ($filter === 'all' || $filter === 'events'): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6 card hover-scale">
            <div class="flex justify-between items-center mb-6">
                <a href="events.php"><h2 class="text-2xl font-bold text-gray-800 dark:text-white">Your Events</h2></a>
                <a href="add_event.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                    Add Event
                </a>
            </div>
            <?php 
            // Modify the SQL query to limit to 3 upcoming events if not searching
            if ($filter === 'all' && empty($search)) {
                $event_sql = "SELECT * FROM events WHERE user_id = ? AND event_date >= CURDATE() ORDER BY event_date ASC, event_time ASC LIMIT 3";
                $event_stmt = $conn->prepare($event_sql);
                $event_stmt->bind_param("i", $user_id);
                $event_stmt->execute();
                $event_result = $event_stmt->get_result();
            }
            
            if ($event_result && $event_result->num_rows > 0): ?>
                <div class="space-y-6">
                    <?php while ($event = $event_result->fetch_assoc()): ?>
                         <div onclick="window.location.href='events.php'" class="block border rounded-lg p-6 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 hover:shadow-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition duration-200 cursor-pointer">
                            <!-- Event content remains unchanged -->
                            <div class="flex justify-between items-start">
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($event['title']); ?></h3>
                                <?php
                                    $event_date = strtotime($event['event_date']);
                                    $today = strtotime('today');
                                    $tomorrow = strtotime('tomorrow');
                                    $next_week = strtotime('+7 days');
                                    
                                    // Status code remains the same
                                ?>
                            </div>
                            <p class="text-[17px] text-gray-700 dark:text-gray-300 mt-4 mb-4 leading-relaxed line-clamp-2">
                                <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                            </p>
                            <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                                <div>
                                    <span class="font-semibold"><i class="far fa-calendar-alt mr-1"></i> Date:</span> 
                                    <?php echo date('F j, Y', strtotime($event['event_date'])); ?>
                                </div>
                                <div>
                                    <span class="font-semibold"><i class="far fa-clock mr-1"></i> Time:</span> 
                                    <?php echo date('g:i a', strtotime($event['event_time'])); ?>
                                    <?php if (!empty($event['event_end_time'])): ?>
                                     - <?php echo date('g:i a', strtotime($event['event_end_time'])); ?>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($event['location'])): ?>
                                <div>
                                    <span class="font-semibold"><i class="fas fa-map-marker-alt mr-1"></i> Location:</span> 
                                    <?php echo htmlspecialchars($event['location']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <!-- Rest of event content -->
                            <div class="flex space-x-2 mt-3" onclick="event.stopPropagation()">
                                <a href="edit_event.php?id=<?php echo $event['id']; ?>" 
                                   class="bg-blue-500 text-white px-3 py-1 text-sm rounded-md hover:bg-blue-600 transition duration-200">
                                    Edit
                                </a>
                                <button onclick="deleteEvent(<?php echo $event['id']; ?>)" 
                                        class="bg-red-500 text-white px-3 py-1 text-sm rounded-md hover:bg-red-600 transition duration-200">
                                    Delete
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- View All Events button -->
                <div class="mt-6 text-center">
                    <a href="events.php" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg transition duration-200">
                        <i class="fas fa-calendar-alt mr-2"></i> View All Events
                    </a>
                </div>
            <?php else: ?>
                <p class="text-gray-600 dark:text-gray-300">No events found.</p>
                
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <!-- Notes Section -->
        <?php if ($filter === 'all' || $filter === 'notes'): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6 card hover-scale">
            <div class="flex justify-between items-center mb-6">
                <a href="notes.php">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Your Notes</h2>
        </a>
        <a href="index.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                    Add Note
                </a>
            </div>
            <?php 
            // Modify the SQL query to limit to 3 notes if not searching
            if ($filter === 'all' && empty($search)) {
                $note_sql = "SELECT * FROM notes WHERE user_id = ? ORDER BY updated_at DESC, created_at DESC LIMIT 3";
                $note_stmt = $conn->prepare($note_sql);
                $note_stmt->bind_param("i", $user_id);
                $note_stmt->execute();
                $note_result = $note_stmt->get_result();
            }
            
            if ($note_result && $note_result->num_rows > 0): ?>
                <div class="space-y-6">
                    <?php while ($note = $note_result->fetch_assoc()): 
                        // Fetch attachments for the note (limit to 2)
                        $attach_sql = "SELECT file_name FROM attachments WHERE note_id = ? LIMIT 2";
                        $attach_stmt = $conn->prepare($attach_sql);
                        $attach_stmt->bind_param("i", $note['id']);
                        $attach_stmt->execute();
                        $attachments_result = $attach_stmt->get_result();
                        
                        // Count total attachments
                        $count_sql = "SELECT COUNT(*) as total FROM attachments WHERE note_id = ?";
                        $count_stmt = $conn->prepare($count_sql);
                        $count_stmt->bind_param("i", $note['id']);
                        $count_stmt->execute();
                        $total_attachments = $count_stmt->get_result()->fetch_assoc()['total'];
                    ?>
                        <!-- Note content remains unchanged -->
                        <a href="notes.php" class="block border rounded-lg p-6 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 hover:shadow-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition duration-200">
                            <div class="flex justify-between items-start">
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($note['title']); ?></h3>
                            </div>
                            <p class="text-[17px] text-gray-700 dark:text-gray-300 mt-4 mb-4 leading-relaxed line-clamp-3">
                                <?php echo nl2br(htmlspecialchars(substr($note['content'], 0, 300))); ?>...
                            </p>
                            
                            <?php if ($total_attachments > 0): ?>
                            <div class="flex items-center mt-3 mb-2">
                                <i class="fas fa-paperclip text-blue-500 dark:text-blue-400 mr-2"></i>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <?php 
                                    $attachment_names = [];
                                    while ($attachment = $attachments_result->fetch_assoc()) {
                                        $attachment_names[] = htmlspecialchars($attachment['file_name']);
                                    }
                                    echo implode(', ', $attachment_names);
                                    
                                    // Show how many more if there are more than 2
                                    if ($total_attachments > 2) {
                                        echo ' <span class="text-blue-500 dark:text-blue-400">+' . ($total_attachments - 2) . ' more</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <?php if ($note['updated_at'] !== null): ?>
                                    <span class="font-semibold">Updated:</span> <?php echo date('F j, Y, g:i a', strtotime($note['updated_at'])); ?>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
                
                <!-- View All Notes button -->
                <div class="mt-6 text-center">
                    <a href="notes.php" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg transition duration-200">
                        <i class="fas fa-sticky-note mr-2"></i> View All Notes
                    </a>
                </div>
            <?php else: ?>
                <p class="text-gray-600 dark:text-gray-300">No notes found.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('scheduleDate');
            if (dateInput) {
                dateInput.addEventListener('change', function() {
                    const dateForm = document.getElementById('dateSelectForm');
                    if (dateForm) {
                        dateForm.submit();
                    }
                });
            }
        });

        function deleteNote(noteId) {
            if (confirm('Are you sure you want to delete this note?')) {
                fetch('delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'note_id=' + noteId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting note: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting note');
                });
            }
        }

        function deleteTask(taskId) {
            if (confirm('Are you sure you want to delete this task?')) {
                fetch('delete_task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'task_id=' + encodeURIComponent(taskId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Reload the page to reflect changes
                    } else {
                        alert('Error deleting task: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting task');
                });
            }
        }

        function deleteEvent(eventId) {
            if (confirm('Are you sure you want to delete this event?')) {
                fetch('delete_event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'event_id=' + encodeURIComponent(eventId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Reload the page to reflect changes
                    } else {
                        alert('Error deleting event: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting event');
                });
            }
        }

        function markTaskComplete(taskId) {
            fetch('mark_complete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'task_id=' + encodeURIComponent(taskId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload the page to reflect changes
                } else {
                    alert('Error marking task as complete: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error marking task as complete');
            });
        }

        // Dark mode toggle functionality
        var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
        var htmlElement = document.documentElement;

        // Initial state setup
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            htmlElement.classList.add('dark');
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            htmlElement.classList.remove('dark');
            themeToggleDarkIcon.classList.remove('hidden');
        }

        var themeToggleBtn = document.getElementById('theme-toggle');

        themeToggleBtn.addEventListener('click', function() {
            // Toggle icons
            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');

            // Toggle dark mode class
            htmlElement.classList.toggle('dark');
            
            // Update local storage
            localStorage.setItem('color-theme', htmlElement.classList.contains('dark') ? 'dark' : 'light');
        });
        
    </script>
    

<!-- Chatbot UI -->
<div id="chatbot-container" class="fixed bottom-5 right-5 z-50 flex flex-col items-end">
    <!-- Chat Button -->
    <button id="chat-button" class="bg-blue-500 hover:bg-blue-600 text-white rounded-full p-4 shadow-lg flex items-center justify-center transition-all duration-300 focus:outline-none">
        <i class="fas fa-robot text-xl"></i>
    </button>
    
    <!-- Chat Interface -->
    <div id="chat-interface" class="hidden bg-white dark:bg-gray-800 rounded-lg w-80 sm:w-96 max-h-96 mt-4 shadow-lg overflow-hidden transition-all duration-300 animate-fadeIn">
        <!-- Chat Header -->
        <div class="bg-blue-500 text-white p-1 flex justify-between items-center">
            <h3 class="font-bold mx-2">AI Assistant</h3>
            <button id="close-chat" class="text-white hover:text-gray-200  focus:outline-none">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Chat Messages -->
        <div id="chat-messages" class="flex-1 overflow-y-auto p-3 space-y-3" style="max-height: 300px;">
            <div class="flex items-start mb-3">
                <div class="bg-blue-100 dark:bg-blue-900 rounded-lg p-2 max-w-3/4 break-words">
                    <p class="text-gray-800 dark:text-gray-200 text-sm">Hi! I'm your AI assistant. Ask me anything about your notes, tasks, or events!</p>
                </div>
            </div>
        </div>
        
        <!-- Chat Input -->
        <div class="border-t border-gray-200 dark:border-gray-700 p-3">
            <form id="chat-form" class="flex items-center">
                <input type="text" id="chat-input" class="flex-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-l-lg py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ask a question...">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white rounded-r-lg py-2 px-4 focus:outline-none transition-colors duration-200">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Chatbot functionality
    document.addEventListener('DOMContentLoaded', function() {
        const chatButton = document.getElementById('chat-button');
        const chatInterface = document.getElementById('chat-interface');
        const closeChat = document.getElementById('close-chat');
        const chatForm = document.getElementById('chat-form');
        const chatInput = document.getElementById('chat-input');
        const chatMessages = document.getElementById('chat-messages');
        
        // Toggle chat interface
        chatButton.addEventListener('click', function() {
            chatInterface.classList.toggle('hidden');
            if (!chatInterface.classList.contains('hidden')) {
                chatInput.focus();
            }
        });
        
        // Close chat interface
        closeChat.addEventListener('click', function() {
            chatInterface.classList.add('hidden');
        });
        
        // Handle form submission
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const question = chatInput.value.trim();
            
            if (question === '') return;
            
            // Add user message to chat
            addMessage(question, 'user');
            chatInput.value = '';
            
            // Show loading indicator
            const loadingId = addLoadingMessage();
            
            // Send question to server
            fetch('chatbot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'question=' + encodeURIComponent(question)
            })
            .then(response => response.json())
            .then(data => {
                // Remove loading indicator
                removeLoadingMessage(loadingId);
                
                if (data.answer) {
                    // Add bot response to chat
                    addMessage(data.answer, 'bot');
                } else if (data.error) {
                    // Add error message
                    addMessage('Sorry, I encountered an error: ' + data.error, 'bot error');
                }
            })
            .catch(error => {
                // Remove loading indicator
                removeLoadingMessage(loadingId);
                
                // Add error message
                addMessage('Sorry, I encountered an error. Please try again later.', 'bot error');
                console.error('Error:', error);
            });
        });
        
        // Function to add a message to the chat
        function addMessage(message, sender) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'flex items-start mb-3 ' + (sender === 'user' ? 'justify-end' : '');
            
            const messageBubble = document.createElement('div');
            messageBubble.className = sender === 'user' 
                ? 'bg-blue-500 text-white rounded-lg p-2 max-w-3/4 break-words'
                : sender === 'bot error'
                    ? 'bg-red-100 dark:bg-red-900 rounded-lg p-2 max-w-3/4 break-words'
                    : 'bg-blue-100 dark:bg-blue-900 rounded-lg p-2 max-w-3/4 break-words';
            
            const messageText = document.createElement('p');
            messageText.className = sender === 'user'
                ? 'text-white text-sm'
                : sender === 'bot error'
                    ? 'text-red-800 dark:text-red-200 text-sm'
                    : 'text-gray-800 dark:text-gray-200 text-sm';
            messageText.textContent = message;
            
            messageBubble.appendChild(messageText);
            messageDiv.appendChild(messageBubble);
            chatMessages.appendChild(messageDiv);
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Function to add a loading message
        function addLoadingMessage() {
            const loadingId = 'loading-' + Date.now();
            const loadingDiv = document.createElement('div');
            loadingDiv.id = loadingId;
            loadingDiv.className = 'flex items-start mb-3';
            
            const loadingBubble = document.createElement('div');
            loadingBubble.className = 'bg-gray-100 dark:bg-gray-700 rounded-lg p-2 max-w-3/4 break-words';
            
            const loadingText = document.createElement('p');
            loadingText.className = 'text-gray-500 dark:text-gray-400 text-sm';
            loadingText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Thinking...';
            
            loadingBubble.appendChild(loadingText);
            loadingDiv.appendChild(loadingBubble);
            chatMessages.appendChild(loadingDiv);
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            return loadingId;
        }
        
        // Function to remove loading message
        function removeLoadingMessage(loadingId) {
            const loadingElement = document.getElementById(loadingId);
            if (loadingElement) {
                loadingElement.remove();
            }
        }
    });
</script>

</div>
    <script src="js/dashboard.js"></script>
</body>
</html>
