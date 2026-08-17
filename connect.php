<?php

declare(strict_types=1);

require_once __DIR__ . '/src/config.php';

requireAuth();

$pageTitle = 'Stripe Connection - Stripe TestKit';
$errorMessage = null;
$successMessage = null;

// Handle Form Submissions
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'disconnect') {
        unset(
            $_SESSION['stripe_secret_key_enc'],
            $_SESSION['stripe_public_key_enc'],
            $_SESSION['stripe_secret_key'],
            $_SESSION['stripe_public_key']
        );
        $successMessage = 'Stripe API keys have been removed from your current session.';
    } elseif ($action === 'save') {
        $secretKey = isset($_POST['stripe_secret_key']) ? trim($_POST['stripe_secret_key']) : '';
        $publicKey = isset($_POST['stripe_public_key']) ? trim($_POST['stripe_public_key']) : '';

        if (empty($secretKey)) {
            $errorMessage = 'Stripe Secret Key is required.';
        } elseif (!str_starts_with($secretKey, 'sk_') && !str_starts_with($secretKey, 'rk_')) {
            $errorMessage = 'Invalid Secret Key format. Stripe secret keys typically start with sk_test_, sk_live_, or rk_.';
        } else {
            // Test the API key against Stripe
            try {
                $testClient = new \Stripe\StripeClient($secretKey);
                // Verify by fetching account balance
                $testClient->balance->retrieve();

                // Successfully verified! Save encrypted keys to session
                $_SESSION['stripe_secret_key_enc'] = encryptData($secretKey);
                $_SESSION['stripe_public_key_enc'] = $publicKey !== '' ? encryptData($publicKey) : null;

                // Ensure raw keys are not kept in session
                unset($_SESSION['stripe_secret_key'], $_SESSION['stripe_public_key']);

                $successMessage = 'Stripe API keys encrypted, verified, and saved to session!';
            } catch (\Stripe\Exception\AuthenticationException $e) {
                $errorMessage = 'Authentication failed: The provided Stripe secret key was rejected by Stripe API.';
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $errorMessage = 'Stripe API Error: ' . $e->getMessage();
            } catch (\Exception $e) {
                $errorMessage = 'Error validating key: ' . $e->getMessage();
            }
        }
    }
}

$currentSecret = getStripeSecretKey();
$currentPublic = getStripePublicKey();
$stripeMode = getStripeMode();
$isConnected = !empty($currentSecret);

require_once __DIR__ . '/templates/header.php';
?>

<!-- Header -->
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Stripe API Connection</h1>
        <p class="text-sm text-slate-500 mt-1">Manage and store Stripe API keys in your active session.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="index.php" class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-semibold px-3.5 py-2 rounded-lg shadow-xs transition">
            &larr; Back to Dashboard
        </a>
    </div>
</div>

<!-- Alerts -->
<?php if ($successMessage): ?>
    <div class="mb-6 rounded-xl bg-emerald-50 p-4 border border-emerald-200 text-emerald-800 flex items-start gap-3">
        <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="text-xs">
            <span class="font-semibold block text-emerald-900 mb-0.5">Success</span>
            <?= htmlspecialchars($successMessage) ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($errorMessage): ?>
    <div class="mb-6 rounded-xl bg-rose-50 p-4 border border-rose-200 text-rose-800 flex items-start gap-3">
        <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div class="text-xs">
            <span class="font-semibold block text-rose-900 mb-0.5">Connection Error</span>
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- Main Configuration Card -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
            <h2 class="text-lg font-bold text-slate-900 mb-1">
                <?= $isConnected ? 'Update Stripe API Keys' : 'Connect Stripe Account' ?>
            </h2>
            <p class="text-xs text-slate-500 mb-6">
                Keys submitted here are verified against Stripe live and securely stored in your session.
            </p>

            <form method="POST" action="connect.php" class="space-y-5">
                <input type="hidden" name="action" value="save">

                <!-- Secret Key Input -->
                <div>
                    <label for="stripe_secret_key" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Stripe Secret Key <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            id="stripe_secret_key" 
                            name="stripe_secret_key" 
                            type="password" 
                            required 
                            placeholder="sk_test_..." 
                            value="<?= htmlspecialchars($currentSecret ?? '') ?>"
                            class="w-full font-mono text-sm px-3.5 py-2.5 bg-slate-50/50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-stripe-500/20 focus:border-stripe-500 transition"
                        >
                    </div>
                    <p class="mt-1 text-2xs text-slate-400">
                        Use a test secret key (<code class="bg-slate-100 px-1 py-0.5 rounded">sk_test_...</code>) or restricted key (<code class="bg-slate-100 px-1 py-0.5 rounded">rk_test_...</code>).
                    </p>
                </div>

                <!-- Public Key Input -->
                <div>
                    <label for="stripe_public_key" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Stripe Publishable Key <span class="text-slate-400 text-2xs font-normal">(Optional)</span>
                    </label>
                    <div class="relative">
                        <input 
                            id="stripe_public_key" 
                            name="stripe_public_key" 
                            type="text" 
                            placeholder="pk_test_..." 
                            value="<?= htmlspecialchars($currentPublic ?? '') ?>"
                            class="w-full font-mono text-sm px-3.5 py-2.5 bg-slate-50/50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-stripe-500/20 focus:border-stripe-500 transition"
                        >
                    </div>
                </div>

                <!-- Submit / Save Button -->
                <div class="pt-2 flex items-center gap-3">
                    <button 
                        type="submit" 
                        class="inline-flex items-center justify-center gap-2 py-2.5 px-5 rounded-xl text-xs font-bold text-white bg-stripe-600 hover:bg-stripe-700 shadow-sm shadow-stripe-500/25 transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Verify & Save to Session
                    </button>
                    <?php if ($isConnected): ?>
                        <a href="index.php" class="text-xs text-slate-500 hover:text-slate-800 font-medium px-3 py-2">
                            Go to Dashboard &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Side Status & Help Card -->
    <div class="space-y-6">
        <!-- Current Connection Status -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
            <h3 class="text-sm font-bold text-slate-900 mb-4 pb-3 border-b border-slate-100 flex items-center justify-between">
                <span>Session Status</span>
                <?php if ($isConnected): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-2xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Active
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-2xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                        Disconnected
                    </span>
                <?php endif; ?>
            </h3>

            <?php if ($isConnected): ?>
                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-slate-400 font-medium block">Environment Mode</span>
                        <span class="font-semibold text-slate-800 uppercase text-xs"><?= htmlspecialchars($stripeMode) ?> mode</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium block">Secret Key</span>
                        <span class="font-mono text-2xs text-slate-700 bg-slate-100 px-2 py-1 rounded block truncate mt-0.5">
                            <?= htmlspecialchars(substr($currentSecret, 0, 10) . '••••••••' . substr($currentSecret, -4)) ?>
                        </span>
                    </div>
                    <?php if ($currentPublic): ?>
                        <div>
                            <span class="text-slate-400 font-medium block">Public Key</span>
                            <span class="font-mono text-2xs text-slate-700 bg-slate-100 px-2 py-1 rounded block truncate mt-0.5">
                                <?= htmlspecialchars(substr($currentPublic, 0, 10) . '••••••••' . substr($currentPublic, -4)) ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="pt-4 border-t border-slate-100">
                        <form method="POST" action="connect.php" onsubmit="return confirm('Are you sure you want to disconnect your Stripe keys from this session?');">
                            <input type="hidden" name="action" value="disconnect">
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-lg text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Disconnect Keys
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-xs text-slate-500 space-y-2">
                    <p>No keys currently active in your session.</p>
                    <p class="text-slate-400 text-2xs">Enter your secret key on the left to start inspecting Stripe customers, transactions, and subscriptions.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Help Card -->
        <div class="bg-stripe-50 border border-stripe-100 rounded-2xl p-6 text-xs text-slate-700">
            <h4 class="font-bold text-stripe-900 mb-1.5 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-stripe-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Where to find your keys
            </h4>
            <p class="text-slate-600 leading-relaxed mb-3">
                You can get your test API keys directly from your Stripe Dashboard developers portal.
            </p>
            <a href="https://dashboard.stripe.com/test/apikeys" target="_blank" rel="noreferrer" class="inline-flex items-center gap-1 text-stripe-600 font-semibold hover:underline">
                Stripe API Keys Portal &rarr;
            </a>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
