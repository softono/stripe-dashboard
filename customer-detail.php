<?php

declare(strict_types=1);

require_once __DIR__ . '/src/config.php';

requireAuth();

$customerId = isset($_GET['id']) && trim($_GET['id']) !== '' ? trim($_GET['id']) : (isset($_GET['customer_id']) ? trim($_GET['customer_id']) : null);
$pageTitle = ($customerId ? 'Customer: ' . $customerId : 'Customer Details') . ' - Stripe Dashboard';
$stripe = getStripeClient();

$customer = null;
$paymentMethods = [];
$charges = [];
$subscriptions = [];
$errorMessage = null;

if (!$customerId) {
    $errorMessage = 'No Customer ID provided. Please specify a valid Stripe customer ID.';
} elseif ($stripe === null) {
    $errorMessage = 'Stripe API key is not configured.';
} else {
    try {
        // Retrieve Customer details
        $customer = $stripe->customers->retrieve($customerId, [
            'expand' => ['default_source']
        ]);

        // Retrieve Payment Methods (Cards)
        try {
            $pmList = $stripe->paymentMethods->all([
                'customer' => $customerId,
                'type' => 'card',
                'limit' => 10,
            ]);
            $paymentMethods = $pmList->data ?? [];
        } catch (\Exception $e) {
            // Some customers might use legacy sources or have no PM permissions
            $paymentMethods = [];
        }

        // Retrieve Customer Charges
        try {
            $chargesList = $stripe->charges->all([
                'customer' => $customerId,
                'limit' => 10,
            ]);
            $charges = $chargesList->data ?? [];
        } catch (\Exception $e) {
            $charges = [];
        }

        // Retrieve Customer Subscriptions
        try {
            $subsList = $stripe->subscriptions->all([
                'customer' => $customerId,
                'limit' => 10,
                'status' => 'all',
            ]);
            $subscriptions = $subsList->data ?? [];
        } catch (\Exception $e) {
            $subscriptions = [];
        }
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $errorMessage = $e->getMessage();
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

require_once __DIR__ . '/templates/header.php';
?>

<!-- Breadcrumbs & Back Navigation -->
<div class="mb-6 flex items-center justify-between">
    <nav class="flex items-center text-xs text-slate-500 gap-1.5">
        <a href="customers.php" class="hover:text-stripe-600 transition flex items-center gap-1 font-medium">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Customers
        </a>
        <span>/</span>
        <span class="text-slate-900 font-mono font-semibold"><?= htmlspecialchars($customerId ?? 'Unknown') ?></span>
    </nav>
    <a href="customers.php"
        class="inline-flex items-center gap-1 text-xs font-medium text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3 py-1.5 rounded-lg shadow-xs transition">
        &larr; Back to Customers
    </a>
</div>

<?php if ($errorMessage): ?>
<div class="mb-6 rounded-xl bg-rose-50 p-4 border border-rose-200 text-rose-800 flex items-start gap-3">
    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
    </svg>
    <div>
        <h3 class="text-sm font-semibold text-rose-900">Unable to load customer</h3>
        <p class="text-xs text-rose-700 mt-0.5"><?= htmlspecialchars($errorMessage) ?></p>
    </div>
</div>
<?php endif; ?>

<?php if ($customer): ?>

<!-- Customer Profile Header Card -->
<div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs mb-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
        <div class="flex items-start sm:items-center gap-4">
            <div
                class="w-14 h-14 rounded-2xl bg-stripe-50 text-stripe-600 border border-stripe-100 flex items-center justify-center text-xl font-bold uppercase shrink-0">
                <?= htmlspecialchars(substr($customer->name ?? $customer->email ?? 'C', 0, 2)) ?>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold text-slate-900">
                        <?= htmlspecialchars($customer->name ?? 'Unnamed Customer') ?></h1>
                    <?php if ($customer->delinquent): ?>
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">Delinquent</span>
                    <?php else: ?>
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Good
                        Standing</span>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-slate-500">
                    <div class="flex items-center gap-1 font-mono text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                        <span><?= htmlspecialchars($customer->id) ?></span>
                        <button type="button" onclick="copyToClipboard('<?= htmlspecialchars($customer->id) ?>', this)"
                            class="text-slate-400 hover:text-slate-700" title="Copy Customer ID">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                    </div>
                    <span>•</span>
                    <span>Created <?= htmlspecialchars(formatDateTime($customer->created)) ?></span>
                </div>
            </div>
        </div>

        <!-- Quick Action Links -->
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="transactions.php?customer=<?= urlencode($customer->id) ?>"
                class="inline-flex items-center gap-2 bg-stripe-50 hover:bg-stripe-100 text-stripe-700 border border-stripe-200 text-xs font-semibold px-3.5 py-2 rounded-lg transition shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                Filter All Transactions
            </a>
            <a href="subscriptions.php?customer=<?= urlencode($customer->id) ?>"
                class="inline-flex items-center gap-2 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 text-xs font-semibold px-3.5 py-2 rounded-lg transition shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Filter All Subscriptions
            </a>
            <a href="https://dashboard.stripe.com/test/customers/<?= htmlspecialchars($customer->id) ?>" target="_blank"
                rel="noreferrer"
                class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-semibold px-3.5 py-2 rounded-lg transition shadow-xs">
                Stripe Dashboard
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        </div>
    </div>

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-slate-100 text-xs">
        <div>
            <span class="text-slate-400 font-medium">Email Address</span>
            <div class="font-semibold text-slate-800 text-sm mt-0.5 truncate">
                <?= htmlspecialchars($customer->email ?? '—') ?></div>
        </div>
        <div>
            <span class="text-slate-400 font-medium">Phone Number</span>
            <div class="font-semibold text-slate-800 text-sm mt-0.5"><?= htmlspecialchars($customer->phone ?? '—') ?>
            </div>
        </div>
        <div>
            <span class="text-slate-400 font-medium">Account Balance</span>
            <div
                class="font-semibold text-sm mt-0.5 <?= ($customer->balance ?? 0) < 0 ? 'text-emerald-600' : (($customer->balance ?? 0) > 0 ? 'text-rose-600' : 'text-slate-800') ?>">
                <?= htmlspecialchars(formatCurrency($customer->balance ?? 0, $customer->currency ?? 'usd')) ?>
                <span
                    class="text-slate-400 text-2xs uppercase ml-0.5"><?= htmlspecialchars($customer->currency ?? 'usd') ?></span>
            </div>
        </div>
        <div>
            <span class="text-slate-400 font-medium">Description</span>
            <div class="font-semibold text-slate-800 text-sm mt-0.5 truncate">
                <?= htmlspecialchars($customer->description ?? '—') ?></div>
        </div>
    </div>
</div>

<!-- Main Content 2-Column Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">

    <!-- Left Column: Payment Methods / Cards & Address Details -->
    <div class="space-y-6">
        <!-- Saved Cards / Payment Methods Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    Saved Cards / Payment Methods
                </h2>
                <span class="text-xs text-slate-500 font-medium"><?= count($paymentMethods) ?></span>
            </div>

            <div class="p-5">
                <?php if (empty($paymentMethods)): ?>
                <div class="text-center py-6 text-slate-400 text-xs">
                    <svg class="w-8 h-8 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    No payment methods attached
                </div>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($paymentMethods as $pm): ?>
                    <?php $card = $pm->card ?? null; ?>
                    <div
                        class="flex items-center justify-between p-3 rounded-lg border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-7 rounded bg-white border border-slate-200 flex items-center justify-center font-bold text-xs uppercase text-slate-700 shadow-xs">
                                <?= htmlspecialchars(substr($card->brand ?? 'Card', 0, 4)) ?>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-800">
                                    •••• <?= htmlspecialchars($card->last4 ?? '••••') ?>
                                </div>
                                <div class="text-2xs text-slate-400">
                                    Exp:
                                    <?= htmlspecialchars((string)($card->exp_month ?? '')) ?>/<?= htmlspecialchars((string)($card->exp_year ?? '')) ?>
                                    • <span class="capitalize"><?= htmlspecialchars($card->funding ?? '') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <?php if (($customer->invoice_settings->default_payment_method ?? null) === $pm->id): ?>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-2xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Default</span>
                            <?php endif; ?>
                            <div class="font-mono text-3xs text-slate-400 mt-1">
                                <?= htmlspecialchars(substr($pm->id, 0, 14)) ?>...</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Address & Metadata Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5 space-y-4 text-xs">
            <h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-2">Customer Details & Metadata</h3>

            <div>
                <span class="text-slate-400 font-medium block mb-1">Billing Address</span>
                <?php
                    $addr = $customer->address ?? null;
                    if ($addr && ($addr->line1 || $addr->city || $addr->country)): ?>
                <div class="text-slate-700 leading-relaxed font-medium">
                    <?= htmlspecialchars($addr->line1 ?? '') ?><?= !empty($addr->line2) ? ', ' . htmlspecialchars($addr->line2) : '' ?><br>
                    <?= htmlspecialchars($addr->city ?? '') ?><?= !empty($addr->state) ? ', ' . htmlspecialchars($addr->state) : '' ?>
                    <?= htmlspecialchars($addr->postal_code ?? '') ?><br>
                    <span
                        class="uppercase font-bold text-2xs text-slate-500"><?= htmlspecialchars($addr->country ?? '') ?></span>
                </div>
                <?php else: ?>
                <span class="text-slate-400">No address provided</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($customer->metadata) && count($customer->metadata->toArray()) > 0): ?>
            <div class="pt-3 border-t border-slate-100">
                <span class="text-slate-400 font-medium block mb-1.5">Metadata</span>
                <div class="bg-slate-50 rounded-lg p-2.5 space-y-1 font-mono text-2xs border border-slate-200">
                    <?php foreach ($customer->metadata->toArray() as $k => $v): ?>
                    <div class="flex justify-between">
                        <span class="text-slate-500"><?= htmlspecialchars($k) ?>:</span>
                        <span class="text-slate-800 font-semibold"><?= htmlspecialchars((string)$v) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column (2 spans): Subscriptions & Transactions for this customer -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Subscriptions Section -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Subscriptions (<?= count($subscriptions) ?>)</h2>
                    <p class="text-xs text-slate-500">Recurring plans linked to this customer.</p>
                </div>
                <a href="subscriptions.php?customer=<?= urlencode($customer->id) ?>"
                    class="text-xs font-semibold text-stripe-600 hover:text-stripe-700 hover:underline">
                    View all in Subscriptions &rarr;
                </a>
            </div>

            <?php if (empty($subscriptions)): ?>
            <div class="p-8 text-center text-slate-400 text-xs">
                No active or past subscriptions for this customer.
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider text-2xs">
                        <tr>
                            <th class="px-6 py-3">Subscription ID</th>
                            <th class="px-6 py-3">Plan / Price</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Period End</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php foreach ($subscriptions as $sub): ?>
                        <tr class="hover:bg-slate-50/75 transition">
                            <td class="px-6 py-3 font-mono font-medium text-slate-800">
                                <div class="flex items-center gap-1.5">
                                    <span><?= htmlspecialchars($sub->id) ?></span>
                                    <button type="button"
                                        onclick="copyToClipboard('<?= htmlspecialchars($sub->id) ?>', this)"
                                        class="text-slate-400 hover:text-slate-700" title="Copy Subscription ID">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-3">
                                <?php
                                            $firstItem = $sub->items->data[0] ?? null;
                                            if ($firstItem && isset($firstItem->price)) {
                                                $price = $firstItem->price;
                                                echo '<span class="font-semibold text-slate-900">' . htmlspecialchars(formatCurrency($price->unit_amount ?? 0, $price->currency ?? 'usd')) . '</span>';
                                                echo '<span class="text-slate-400 text-2xs"> / ' . htmlspecialchars($price->recurring->interval ?? 'month') . '</span>';
                                            } else {
                                                echo '—';
                                            }
                                            ?>
                            </td>
                            <td class="px-6 py-3">
                                <?= renderStatusBadge($sub->status) ?>
                            </td>
                            <td class="px-6 py-3 text-slate-500 whitespace-nowrap">
                                <?= htmlspecialchars(formatDate($sub->current_period_end ?? null)) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Transactions Section -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Recent Transactions (<?= count($charges) ?>)</h2>
                    <p class="text-xs text-slate-500">Charges and payments made by this customer.</p>
                </div>
                <a href="transactions.php?customer=<?= urlencode($customer->id) ?>"
                    class="text-xs font-semibold text-stripe-600 hover:text-stripe-700 hover:underline">
                    View all in Transactions &rarr;
                </a>
            </div>

            <?php if (empty($charges)): ?>
            <div class="p-8 text-center text-slate-400 text-xs">
                No transactions found for this customer.
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider text-2xs">
                        <tr>
                            <th class="px-6 py-3">Charge ID</th>
                            <th class="px-6 py-3">Amount</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Payment Method</th>
                            <th class="px-6 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php foreach ($charges as $ch): ?>
                        <tr class="hover:bg-slate-50/75 transition">
                            <td class="px-6 py-3 font-mono font-medium text-slate-800">
                                <div class="flex items-center gap-1.5">
                                    <span><?= htmlspecialchars($ch->id) ?></span>
                                    <button type="button"
                                        onclick="copyToClipboard('<?= htmlspecialchars($ch->id) ?>', this)"
                                        class="text-slate-400 hover:text-slate-700" title="Copy Charge ID">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-3">
                                <span
                                    class="font-bold text-slate-900"><?= htmlspecialchars(formatCurrency($ch->amount, $ch->currency)) ?></span>
                                <?php if ($ch->refunded): ?>
                                <span class="text-2xs text-rose-600 block">Refunded</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-3">
                                <?= renderStatusBadge($ch->refunded ? 'refunded' : $ch->status) ?>
                            </td>
                            <td class="px-6 py-3 text-slate-600 capitalize">
                                <?= htmlspecialchars($ch->payment_method_details->card->brand ?? ($ch->payment_method_details->type ?? 'Card')) ?>
                                <?= !empty($ch->payment_method_details->card->last4) ? ' •••• ' . htmlspecialchars($ch->payment_method_details->card->last4) : '' ?>
                            </td>
                            <td class="px-6 py-3 text-slate-500 whitespace-nowrap">
                                <?= htmlspecialchars(formatDateTime($ch->created)) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>