<?php

declare(strict_types=1);

/**
 * Check if user is authenticated
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

/**
 * Require authentication on protected pages
 */
function requireAuth(): void
{
    if (!isLoggedIn()) {
        $currentUri = $_SERVER['REQUEST_URI'] ?? 'index.php';
        header('Location: login.php?redirect=' . urlencode($currentUri));
        exit;
    }
}

/**
 * Get logged in admin email
 */
function getLoggedInEmail(): string
{
    return $_SESSION['admin_email'] ?? (env('ADMIN_EMAIL') ?: 'admin@example.com');
}

/**
 * Encrypt sensitive strings before storing in session
 */
function encryptData(string $data): string
{
    $salt = env('ADMIN_PASSWORD') ?: 'stripe_testkit_secret_salt';
    $key = hash('sha256', $salt . '__session_vault__', true);
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($iv . '::' . $encrypted);
}

/**
 * Decrypt sensitive strings retrieved from session
 */
function decryptData(?string $encoded): ?string
{
    if (empty($encoded)) {
        return null;
    }
    $salt = env('ADMIN_PASSWORD') ?: 'stripe_testkit_secret_salt';
    $key = hash('sha256', $salt . '__session_vault__', true);
    $decoded = base64_decode($encoded, true);
    if (!$decoded || !str_contains($decoded, '::')) {
        return null;
    }
    list($iv, $encrypted) = explode('::', $decoded, 2);
    $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
    return $decrypted !== false ? $decrypted : null;
}

/**
 * Get Stripe Secret Key (prioritizing encrypted Session, then fallback to .env)
 */
function getStripeSecretKey(): ?string
{
    if (!empty($_SESSION['stripe_secret_key_enc'])) {
        $decrypted = decryptData((string)$_SESSION['stripe_secret_key_enc']);
        if (!empty($decrypted)) {
            return trim($decrypted);
        }
    }
    // Backward compatibility for raw session if any
    if (!empty($_SESSION['stripe_secret_key'])) {
        return trim((string)$_SESSION['stripe_secret_key']);
    }
    $fromEnv = env('STRIPE_SECRET_KEY');
    return !empty($fromEnv) ? trim((string)$fromEnv) : null;
}

/**
 * Get Stripe Public Key (prioritizing encrypted Session, then fallback to .env)
 */
function getStripePublicKey(): ?string
{
    if (!empty($_SESSION['stripe_public_key_enc'])) {
        $decrypted = decryptData((string)$_SESSION['stripe_public_key_enc']);
        if (!empty($decrypted)) {
            return trim($decrypted);
        }
    }
    // Backward compatibility for raw session if any
    if (!empty($_SESSION['stripe_public_key'])) {
        return trim((string)$_SESSION['stripe_public_key']);
    }
    $fromEnv = env('STRIPE_PUBLIC_KEY');
    return !empty($fromEnv) ? trim((string)$fromEnv) : null;
}

/**
 * Check if Stripe key is configured
 */
function hasStripeKey(): bool
{
    $key = getStripeSecretKey();
    return !empty($key);
}

/**
 * Get StripeClient instance
 */
function getStripeClient(): ?\Stripe\StripeClient
{
    static $client = null;
    $secretKey = getStripeSecretKey();
    
    if (empty($secretKey)) {
        return null;
    }

    if ($client === null) {
        $client = new \Stripe\StripeClient($secretKey);
    }
    return $client;
}

/**
 * Check if current key is test mode or live mode
 */
function getStripeMode(): string
{
    $secretKey = getStripeSecretKey();
    if (empty($secretKey)) {
        return 'unconfigured';
    }
    if (str_starts_with($secretKey, 'sk_test_') || str_starts_with($secretKey, 'rk_test_')) {
        return 'test';
    }
    return 'live';
}

/**
 * Format currency amounts (converts cents to decimal and formats with symbol)
 */
function formatCurrency(int|float $amountInCents, string $currency = 'usd'): string
{
    $currency = strtoupper($currency);
    $amount = $amountInCents / 100;

    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'INR' => '₹',
        'CAD' => 'CA$',
        'AUD' => 'AU$',
        'JPY' => '¥',
    ];

    $symbol = $symbols[$currency] ?? ($currency . ' ');
    
    // JPY does not have decimal cents in Stripe
    if ($currency === 'JPY') {
        return $symbol . number_format($amountInCents);
    }

    return $symbol . number_format($amount, 2);
}

/**
 * Format timestamp to human readable date/time
 */
function formatDateTime(?int $timestamp): string
{
    if (!$timestamp) {
        return '—';
    }
    return date('M d, Y • h:i A', $timestamp);
}

/**
 * Format short date
 */
function formatDate(?int $timestamp): string
{
    if (!$timestamp) {
        return '—';
    }
    return date('M d, Y', $timestamp);
}

/**
 * Build URL with query params
 */
function buildPageUrl(array $params = []): string
{
    $currentParams = $_GET;
    // Remove conflicting cursor params if setting a specific one
    if (isset($params['starting_after'])) {
        unset($currentParams['ending_before']);
    }
    if (isset($params['ending_before'])) {
        unset($currentParams['starting_after']);
    }
    
    $merged = array_merge($currentParams, $params);
    // Remove null or empty keys
    $filtered = array_filter($merged, fn($v) => $v !== null && $v !== '');
    
    $query = http_build_query($filtered);
    $path = basename($_SERVER['PHP_SELF']);
    return $query ? "{$path}?{$query}" : $path;
}

/**
 * Render status badge HTML
 */
function renderStatusBadge(string $status): string
{
    $statusLower = strtolower($status);

    $badgeClasses = [
        'succeeded'  => 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-1 ring-emerald-600/20',
        'active'     => 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-1 ring-emerald-600/20',
        'paid'       => 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-1 ring-emerald-600/20',
        'trialing'   => 'bg-blue-50 text-blue-700 border-blue-200 ring-1 ring-blue-600/20',
        'pending'    => 'bg-amber-50 text-amber-700 border-amber-200 ring-1 ring-amber-600/20',
        'past_due'   => 'bg-amber-50 text-amber-700 border-amber-200 ring-1 ring-amber-600/20',
        'canceled'   => 'bg-slate-100 text-slate-600 border-slate-200 ring-1 ring-slate-400/20',
        'failed'     => 'bg-rose-50 text-rose-700 border-rose-200 ring-1 ring-rose-600/20',
        'unpaid'     => 'bg-rose-50 text-rose-700 border-rose-200 ring-1 ring-rose-600/20',
        'incomplete' => 'bg-orange-50 text-orange-700 border-orange-200 ring-1 ring-orange-600/20',
        'refunded'   => 'bg-purple-50 text-purple-700 border-purple-200 ring-1 ring-purple-600/20',
    ];

    $class = $badgeClasses[$statusLower] ?? 'bg-gray-100 text-gray-700 border-gray-200 ring-1 ring-gray-400/20';
    $label = ucfirst(str_replace('_', ' ', $status));

    return sprintf(
        '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-md border %s">
            <span class="h-1.5 w-1.5 rounded-full currentColor bg-current opacity-75"></span>
            %s
        </span>',
        htmlspecialchars($class),
        htmlspecialchars($label)
    );
}
