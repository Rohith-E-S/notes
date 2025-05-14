<?php
session_start();

// Check if user has requested password reset
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp'])) {
    header("Location: forgot_password.php");
    exit();
}

// Check if OTP has expired
if (time() > $_SESSION['otp_expiry']) {
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_otp']);
    unset($_SESSION['otp_expiry']);
    header("Location: forgot_password.php?error=OTP expired. Please request a new one.");
    exit();
}

// If form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = $_POST['otp'];
    
    if ($otp == $_SESSION['reset_otp']) {
        header("Location: reset_password.php");
        exit();
    } else {
        $error = "Invalid OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .dark body { color: #e2e8f0; background-color: #121212; }
        .dark .dark\:bg-gray-800 { background-color: #1e1e1e !important; }
        .dark .dark\:text-white { color: #ffffff !important; }
        .dark .dark\:text-gray-300 { color: #d1d5db !important; }
        .dark .dark\:border-gray-700 { border-color: #4b5563 !important; }
        .dark input,
        .dark textarea,
        .dark select {
            color: #fff !important;
            background-color: #374151 !important;
            border-color: #4b5563 !important;
        }
        .dark input::placeholder,
        .dark textarea::placeholder {
            color: #a1a1aa !important;
            opacity: 1;
        }
        .dark label { color: #d1d5db !important; }
    </style>
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-200">
    <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-lg w-96 transition-all duration-300 transform hover:scale-105">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Verify OTP</h2>
            <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2 transition-colors duration-200">
                <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                </svg>
                <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.707.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <p class="text-gray-600 dark:text-gray-300 mb-4">
            We have sent an OTP to your email address. Please enter it below to reset your password.
        </p>
        
        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block mb-2 text-gray-700 dark:text-gray-300">OTP:</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fas fa-key text-gray-400"></i>
                    </div>
                    <input type="text" name="otp" pattern="[0-9]{6}" maxlength="6" class="w-full p-2.5 pl-10 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-500 text-white p-3 rounded-lg hover:bg-blue-600 transition-colors duration-200 transform hover:scale-105">
                <i class="fas fa-check mr-2"></i> Verify OTP
            </button>
        </form>
        
        <p class="mt-6 text-gray-600 dark:text-gray-300 text-center">
            Didn't receive OTP? <a href="forgot_password.php" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200">Request again</a>
        </p>
    </div>

    <script>
        // Dark mode toggle functionality
        var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
        var htmlElement = document.documentElement;

        if (localStorage.getItem('color-theme') === 'dark' || 
            (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            htmlElement.classList.add('dark');
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            htmlElement.classList.remove('dark');
            themeToggleDarkIcon.classList.remove('hidden');
        }

        var themeToggleBtn = document.getElementById('theme-toggle');

        themeToggleBtn.addEventListener('click', function() {
            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');
            htmlElement.classList.toggle('dark');
            localStorage.setItem('color-theme', htmlElement.classList.contains('dark') ? 'dark' : 'light');
        });
    </script>
</body>
</html>