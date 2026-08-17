<?php

declare(strict_types=1);

require_once __DIR__ . '/src/config.php';

requireAuth();

$pageTitle = 'Dashboard - Stripe TestKit';
$stripe = getStripeClient();

$errorMessage = null;
$balance = null;
$recentCharges = [];
$customerCount = 0;
$subscriptionCount = 0;

if ($stripe !== null) {
    try {
        // Fetch Balance
        $balanceObj = $stripe->balance->retrieve();
        $balance = $balanceObj;

        // Fetch recent charges preview
        $chargesList = $stripe->charges->all(['limit' => 5]);
        $recentCharges = $chargesList->data ?? [];

        // Fetch recent customers count preview
        $customersList = $stripe->customers->all(['limit' => 5]);
        $customerCount = count($customersList->data ?? []);

        // Fetch recent active subscriptions count preview
        $subsList = $stripe->subscriptions->all(['limit' => 5, 'status' => 'all']);
        $subscriptionCount = count($subsList->data ?? []);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $errorMessage = $e->getMessage();
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

require_once __DIR__ . '/templates/header.php';
?>

<!-- Dashboard Header -->
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Stripe Dashboard</h1>
        <p class="text-sm text-slate-500 mt-1">Live data & direct API inspection for testing Stripe integration.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="transactions.php" class="inline-flex items-center gap-2 bg-stripe-600 hover:bg-stripe-700 text-white text-sm font-medium px-4 py-2 rounded-lg shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            View Transactions
        </a>
        <a href="customers.php" class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-sm font-medium px-4 py-2 rounded-lg shadow-xs transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            View Customers
        </a>
    </div>
</div>

<?php if ($errorMessage): ?>
    <div class="mb-8 rounded-xl bg-rose-50 p-4 border border-rose-200 text-rose-800 flex items-start gap-3">
        <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <h3 class="text-sm font-semibold text-rose-900">Stripe API Error</h3>
            <p class="text-xs text-rose-700 mt-0.5"><?= htmlspecialchars($errorMessage) ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if ($stripe === null): ?>
    <!-- API Key Onboarding Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm mb-8 text-center max-w-2xl mx-auto">
        <div class="w-14 h-14 bg-stripe-50 text-stripe-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-stripe-100">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-slate-900">Connect your Stripe API Keys</h2>
        <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">
            To view live customers, transactions, and subscriptions, enter your Stripe API Keys to store them in your active session.
        </p>
        <div class="mt-6 flex justify-center gap-3">
            <a href="connect.php" class="inline-flex items-center gap-2 bg-stripe-600 hover:bg-stripe-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-sm shadow-stripe-500/25 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Connect Stripe Keys Now &rarr;
            </a>
        </div>
    </div>
<?php else: ?>

    <!-- Overview Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Balance: Available -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs hover:border-slate-300 transition">
            <div class="flex items-center justify-between text-slate-500 text-xs font-medium uppercase tracking-wider">
                <span>Available Balance</span>
                <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <?php
                if ($balance && !empty($balance->available)) {
                    $avail = $balance->available[0];
                    echo '<div class="text-2xl font-bold text-slate-900">' . htmlspecialchars(formatCurrency($avail->amount, $avail->currency)) . '</div>';
                    echo '<div class="text-xs text-slate-400 mt-1 uppercase">' . htmlspecialchars($avail->currency) . ' • Ready for payout</div>';
                } else {
                    echo '<div class="text-2xl font-bold text-slate-900">$0.00</div>';
                    echo '<div class="text-xs text-slate-400 mt-1">Ready for payout</div>';
                }
                ?>
            </div>
        </div>

        <!-- Balance: Pending -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs hover:border-slate-300 transition">
            <div class="flex items-center justify-between text-slate-500 text-xs font-medium uppercase tracking-wider">
                <span>Pending Balance</span>
                <div class="p-2 bg-amber-50 text-amber-600 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <?php
                if ($balance && !empty($balance->pending)) {
                    $pend = $balance->pending[0];
                    echo '<div class="text-2xl font-bold text-slate-900">' . htmlspecialchars(formatCurrency($pend->amount, $pend->currency)) . '</div>';
                    echo '<div class="text-xs text-slate-400 mt-1 uppercase">' . htmlspecialchars($pend->currency) . ' • In transit</div>';
                } else {
                    echo '<div class="text-2xl font-bold text-slate-900">$0.00</div>';
                    echo '<div class="text-xs text-slate-400 mt-1">In transit</div>';
                }
                ?>
            </div>
        </div>

        <!-- Quick Customers Card -->
        <a href="customers.php" class="group bg-white rounded-xl border border-slate-200 p-5 shadow-xs hover:border-stripe-500/50 hover:shadow-md transition block">
            <div class="flex items-center justify-between text-slate-500 text-xs font-medium uppercase tracking-wider">
                <span class="group-hover:text-stripe-600 transition">Customers</span>
                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg group-hover:bg-stripe-50 group-hover:text-stripe-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <div class="text-2xl font-bold text-slate-900">Direct List &rarr;</div>
            </div>
            <div class="text-xs text-slate-400 mt-1">Browse & paginate customers</div>
        </a>

        <!-- Quick Subscriptions Card -->
        <a href="subscriptions.php" class="group bg-white rounded-xl border border-slate-200 p-5 shadow-xs hover:border-stripe-500/50 hover:shadow-md transition block">
            <div class="flex items-center justify-between text-slate-500 text-xs font-medium uppercase tracking-wider">
                <span class="group-hover:text-stripe-600 transition">Subscriptions</span>
                <div class="p-2 bg-purple-50 text-purple-600 rounded-lg group-hover:bg-stripe-50 group-hover:text-stripe-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <div class="text-2xl font-bold text-slate-900">Direct List &rarr;</div>
            </div>
            <div class="text-xs text-slate-400 mt-1">Active, trialing & past due</div>
        </a>
    </div>

    <!-- Recent Charges / Activity -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Recent Transactions</h2>
                <p class="text-xs text-slate-500">Latest payment activities retrieved directly from Stripe.</p>
            </div>
            <a href="transactions.php" class="text-xs font-semibold text-stripe-600 hover:text-stripe-700 hover:underline">
                View all transactions &rarr;
            </a>
        </div>

        <?php if (empty($recentCharges)): ?>
            <div class="p-12 text-center text-slate-500">
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="font-medium text-slate-700">No recent transactions found</p>
                <p class="text-xs text-slate-400 mt-1">Create test payments in Stripe to see them appear here.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="px-6 py-3">Charge ID</th>
                            <th scope="col" class="px-6 py-3">Amount</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3">Customer / Email</th>
                            <th scope="col" class="px-6 py-3">Payment Method</th>
                            <th scope="col" class="px-6 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php foreach ($recentCharges as $charge): ?>
                            <tr class="hover:bg-slate-50/75 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 font-mono text-xs text-slate-700 font-medium">
                                        <span><?= htmlspecialchars($charge->id) ?></span>
                                        <button type="button" onclick="copyToClipboard('<?= htmlspecialchars($charge->id) ?>', this)" class="text-slate-400 hover:text-slate-600 p-1 rounded transition" title="Copy ID">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-semibold text-slate-900">
                                        <?= htmlspecialchars(formatCurrency($charge->amount, $charge->currency)) ?>
                                    </div>
                                    <?php if ($charge->refunded): ?>
                                        <span class="text-xs text-rose-600 font-medium">Refunded</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= renderStatusBadge($charge->status) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-600">
                                    <?= htmlspecialchars($charge->billing_details->email ?? $charge->customer ?? 'Guest / None') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-600 text-xs uppercase">
                                    <?php
                                    $brand = $charge->payment_method_details->card->brand ?? ($charge->payment_method_details->type ?? 'Card');
                                    $last4 = $charge->payment_method_details->card->last4 ?? '';
                                    echo htmlspecialchars($brand . ($last4 ? " •••• {$last4}" : ''));
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                    <?= htmlspecialchars(formatDateTime($charge->created)) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
