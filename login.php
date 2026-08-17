<?php

declare(strict_types=1);

require_once __DIR__ . '/src/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
    header('Location: ' . $redirect);
    exit;
}

$errorMessage = null;
$emailValue = '';
$redirectParam = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : 'index.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // Enforce 2 seconds delay to mitigate brute force attacks as requested
    sleep(2);

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $emailValue = $email;

    $expectedEmail = env('ADMIN_EMAIL');
    $expectedPassword = env('ADMIN_PASSWORD');

    if (empty($expectedEmail) || empty($expectedPassword)) {
        $errorMessage = 'ADMIN_EMAIL and ADMIN_PASSWORD must be configured in your .env file.';
    } elseif (
        hash_equals((string)$expectedEmail, $email) &&
        hash_equals((string)$expectedPassword, $password)
    ) {
        // Successful login
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['admin_email'] = $email;
        $_SESSION['login_time'] = time();

        $targetUrl = isset($_POST['redirect']) && trim($_POST['redirect']) !== '' ? $_POST['redirect'] : 'index.php';
        header('Location: ' . $targetUrl);
        exit;
    } else {
        $errorMessage = 'Invalid email or password. Please check your credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Stripe TestKit</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    colors: {
                        stripe: {
                            50: '#f4f6fb',
                            100: '#e7ecf7',
                            500: '#635bff',
                            600: '#5851df',
                            700: '#4b45bd',
                            900: '#0a2540',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-slate-50">

    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Logo -->
        <div class="flex justify-center mb-4">
            <div class="w-12 h-12 rounded-2xl bg-stripe-500 flex items-center justify-center text-white shadow-lg shadow-stripe-500/25">
                <span class="font-extrabold text-2xl tracking-tighter">S</span>
            </div>
        </div>
        <h2 class="text-center text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            Stripe<span class="text-stripe-500">TestKit</span>
        </h2>
        <p class="mt-1 text-center text-xs text-slate-500">
            Sign in to access your Stripe test dashboard & resources
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div class="bg-white py-8 px-6 sm:px-10 shadow-sm border border-slate-200 rounded-2xl">
            
            <?php if ($errorMessage): ?>
                <div class="mb-6 rounded-xl bg-rose-50 p-4 border border-rose-200 text-rose-800 flex items-start gap-3">
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="text-xs">
                        <span class="font-semibold block text-rose-900 mb-0.5">Authentication Failed</span>
                        <?= htmlspecialchars($errorMessage) ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-5" onsubmit="handleLoginSubmit(event)">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectParam) ?>">

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Admin Email
                    </label>
                    <div class="relative">
                        <input 
                            id="email" 
                            name="email" 
                            type="email" 
                            autocomplete="email" 
                            required 
                            value="<?= htmlspecialchars($emailValue) ?>"
                            placeholder="admin@example.com"
                            class="w-full px-3.5 py-2.5 bg-slate-50/50 border border-slate-300 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-stripe-500/20 focus:border-stripe-500 transition"
                        >
                    </div>
                </div>

                <!-- Password Input -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                            Password
                        </label>
                    </div>
                    <div class="relative">
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            autocomplete="current-password" 
                            required 
                            placeholder="••••••••"
                            class="w-full px-3.5 py-2.5 bg-slate-50/50 border border-slate-300 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-stripe-500/20 focus:border-stripe-500 transition"
                        >
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button 
                        type="submit" 
                        id="submitBtn"
                        class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-stripe-600 hover:bg-stripe-700 active:bg-stripe-800 shadow-sm shadow-stripe-500/25 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-stripe-500 transition"
                    >
                        <span id="btnText">Sign In</span>
                        <svg id="btnSpinner" class="hidden animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-4 border-t border-slate-100 text-center">
                <p class="text-2xs text-slate-400">
                    Security delay of 2s active on verification attempts.
                </p>
            </div>

        </div>
    </div>

    <script>
        function handleLoginSubmit(e) {
            const btn = document.getElementById('submitBtn');
            const text = document.getElementById('btnText');
            const spinner = document.getElementById('btnSpinner');
            
            btn.disabled = true;
            btn.classList.add('opacity-80', 'cursor-wait');
            text.innerText = 'Verifying...';
            spinner.classList.remove('hidden');
        }
    </script>
</body>
</html>
