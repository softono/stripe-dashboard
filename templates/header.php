<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/config.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$stripeMode = getStripeMode();
$stripeClient = getStripeClient();
$loggedInEmail = getLoggedInEmail();

$navItems = [
    'index.php' => ['label' => 'Dashboard', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>'],
    'customers.php' => ['label' => 'Customers', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>'],
    'transactions.php' => ['label' => 'Transactions', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>'],
    'subscriptions.php' => ['label' => 'Subscriptions', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>'],
    'connect.php' => ['label' => 'Stripe Keys', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>'],
];
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-900 antialiased">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Stripe Testing Dashboard' ?></title>
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
    body {
        font-family: 'Inter', system-ui, sans-serif;
    }
    </style>
</head>

<body class="min-h-full flex flex-col bg-slate-50">
    <!-- Top Navigation Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <!-- Logo & Brand -->
                <div class="flex items-center gap-8">
                    <a href="index.php"
                        class="flex items-center gap-2.5 font-bold text-lg text-slate-900 hover:opacity-90 transition">
                        <div
                            class="w-8 h-8 rounded-lg bg-stripe-500 flex items-center justify-center text-white shadow-sm shadow-stripe-500/30">
                            <span class="font-extrabold text-sm tracking-tighter">S</span>
                        </div>
                        <span class="tracking-tight">Stripe<span
                                class="text-stripe-500 font-semibold">Dashboard</span></span>
                    </a>

                    <!-- Nav Links -->
                    <nav class="hidden md:flex space-x-1">
                        <?php foreach ($navItems as $url => $item): ?>
                        <?php $isActive = ($currentPage === $url); ?>
                        <a href="<?= $url ?>"
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-sm font-medium transition-all <?= $isActive ? 'bg-stripe-50 text-stripe-600 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                            <?= $item['icon'] ?>
                            <?= $item['label'] ?>
                        </a>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <!-- Right Side Badge & User / Logout -->
                <div class="flex items-center gap-3">
                    <?php if ($stripeMode === 'test'): ?>
                    <a href="connect.php"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-300 ring-1 ring-amber-400/20 shadow-xs hover:bg-amber-100 transition"
                        title="Manage Stripe Keys">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        Test Mode
                    </a>
                    <?php elseif ($stripeMode === 'live'): ?>
                    <a href="connect.php"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-300 ring-1 ring-emerald-400/20 shadow-xs hover:bg-emerald-100 transition"
                        title="Manage Stripe Keys">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Live Mode
                    </a>
                    <?php else: ?>
                    <a href="connect.php"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-300 ring-1 ring-rose-400/20 hover:bg-rose-100 transition"
                        title="Click to Connect Stripe Keys">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        Connect Stripe Keys
                    </a>
                    <?php endif; ?>

                    <?php if (isLoggedIn()): ?>
                    <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                        <div class="hidden lg:flex flex-col text-right">
                            <span class="text-2xs text-slate-400 font-medium">Logged in as</span>
                            <span
                                class="text-xs font-semibold text-slate-700 max-w-[150px] truncate"><?= htmlspecialchars($loggedInEmail) ?></span>
                        </div>
                        <a href="logout.php"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 px-3 py-1.5 rounded-lg transition"
                            title="Sign out of dashboard">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Logout
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Sub-bar -->
        <div class="md:hidden border-t border-slate-100 px-4 py-2 flex overflow-x-auto space-x-1 bg-white">
            <?php foreach ($navItems as $url => $item): ?>
            <?php $isActive = ($currentPage === $url); ?>
            <a href="<?= $url ?>"
                class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium <?= $isActive ? 'bg-stripe-50 text-stripe-600 font-semibold' : 'text-slate-600 hover:bg-slate-100' ?>">
                <?= $item['label'] ?>
            </a>
            <?php endforeach; ?>
        </div>
    </header>

    <!-- Global API Key Warning Banner if not configured -->
    <?php if ($stripeMode === 'unconfigured'): ?>
    <div class="bg-amber-50 border-b border-amber-200 px-4 py-3 sm:px-6">
        <div class="max-w-7xl mx-auto flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 text-amber-600 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                        clip-rule="evenodd" />
                </svg>
                <p class="text-sm text-amber-800">
                    <strong class="font-semibold">Stripe API key not connected.</strong> Connect your Stripe Secret Key
                    to view live data.
                </p>
            </div>
            <a href="connect.php"
                class="inline-flex items-center gap-1 text-xs font-bold text-stripe-700 bg-white border border-amber-300 hover:bg-amber-100 px-3 py-1 rounded-md transition shadow-2xs">
                Connect Keys &rarr;
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">