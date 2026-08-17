<?php

declare(strict_types=1);

require_once __DIR__ . '/src/config.php';

requireAuth();

$pageTitle = 'Transactions - Stripe Dashboard';
$stripe = getStripeClient();

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if (!in_array($limit, [10, 25, 50, 100], true)) {
    $limit = 10;
}

$startingAfter = isset($_GET['starting_after']) && trim($_GET['starting_after']) !== '' ? trim($_GET['starting_after']) : null;
$endingBefore = isset($_GET['ending_before']) && trim($_GET['ending_before']) !== '' ? trim($_GET['ending_before']) : null;
$customerFilter = isset($_GET['customer']) && trim($_GET['customer']) !== '' ? trim($_GET['customer']) : null;

$charges = [];
$hasMore = false;
$errorMessage = null;
$firstId = null;
$lastId = null;

if ($stripe !== null) {
    try {
        $params = ['limit' => $limit];
        if ($startingAfter) {
            $params['starting_after'] = $startingAfter;
        } elseif ($endingBefore) {
            $params['ending_before'] = $endingBefore;
        }

        if ($customerFilter) {
            $params['customer'] = $customerFilter;
        }

        $response = $stripe->charges->all($params);
        $charges = $response->data ?? [];
        $hasMore = $response->has_more ?? false;

        if (!empty($charges)) {
            $firstId = $charges[0]->id;
            $lastId = $charges[count($charges) - 1]->id;
        }
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $errorMessage = $e->getMessage();
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

// Determine Previous / Next Pagination State
$canGoPrev = false;
$canGoNext = false;
$prevUrl = '#';
$nextUrl = '#';

if (!empty($charges)) {
    if ($startingAfter) {
        $canGoPrev = true;
        $prevUrl = buildPageUrl(['ending_before' => $firstId, 'starting_after' => null]);
        if ($hasMore) {
            $canGoNext = true;
            $nextUrl = buildPageUrl(['starting_after' => $lastId, 'ending_before' => null]);
        }
    } elseif ($endingBefore) {
        $canGoNext = true;
        $nextUrl = buildPageUrl(['starting_after' => $lastId, 'ending_before' => null]);
        if ($hasMore) {
            $canGoPrev = true;
            $prevUrl = buildPageUrl(['ending_before' => $firstId, 'starting_after' => null]);
        }
    } else {
        // First page
        if ($hasMore) {
            $canGoNext = true;
            $nextUrl = buildPageUrl(['starting_after' => $lastId, 'ending_before' => null]);
        }
    }
}

require_once __DIR__ . '/templates/header.php';
?>

<!-- Header & Controls -->
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Transactions</h1>
        <p class="text-sm text-slate-500 mt-1">Live charges, payments, and refunds directly from Stripe.</p>
    </div>

    <!-- Customer Filter Pill if active -->
    <?php if ($customerFilter): ?>
    <div
        class="flex items-center gap-2 bg-stripe-50 border border-stripe-200 px-3.5 py-2 rounded-xl text-xs text-stripe-800 shadow-xs">
        <span class="text-slate-500">Filtered by Customer:</span>
        <a href="customer-detail.php?id=<?= urlencode($customerFilter) ?>"
            class="font-mono font-bold text-stripe-700 hover:underline flex items-center gap-1">
            <?= htmlspecialchars($customerFilter) ?>
            <svg class="w-3 h-3 text-stripe-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
        </a>
        <a href="transactions.php"
            class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded bg-stripe-200 text-stripe-900 hover:bg-stripe-300 font-bold transition"
            title="Clear customer filter">
            &times; Clear
        </a>
    </div>
    <?php endif; ?>
</div>

<?php if ($errorMessage): ?>
<div class="mb-6 rounded-xl bg-rose-50 p-4 border border-rose-200 text-rose-800 flex items-start gap-3">
    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
    </svg>
    <div>
        <h3 class="text-sm font-semibold text-rose-900">Stripe API Error</h3>
        <p class="text-xs text-rose-700 mt-0.5"><?= htmlspecialchars($errorMessage) ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Transactions Table Container -->
<div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">

    <!-- Top Table Header Bar: Results count & Limit selector -->
    <div
        class="px-6 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600">
        <div class="flex items-center gap-2">
            <span>Showing <strong><?= count($charges) ?></strong> transactions on this page</span>
            <?php if ($customerFilter): ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-stripe-100 text-stripe-700">Customer:
                <?= htmlspecialchars($customerFilter) ?></span>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-slate-500">Per page:</span>
            <div class="inline-flex rounded-lg shadow-xs bg-white border border-slate-300 p-0.5">
                <?php foreach ([10, 25, 50, 100] as $l): ?>
                <a href="<?= buildPageUrl(['limit' => $l, 'starting_after' => null, 'ending_before' => null]) ?>"
                    class="px-2.5 py-1 text-xs font-medium rounded-md transition <?= $limit === $l ? 'bg-stripe-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' ?>">
                    <?= $l ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if (empty($charges)): ?>
    <div class="p-16 text-center text-slate-500">
        <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-400">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
        </div>
        <h3 class="text-base font-semibold text-slate-900">No transactions found</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
            <?= $customerFilter ? 'No transaction records found for this customer.' : 'There are no transaction records in your Stripe account yet.' ?>
        </p>
        <?php if ($customerFilter): ?>
        <div class="mt-4">
            <a href="transactions.php" class="text-xs font-semibold text-stripe-600 hover:underline">Clear customer
                filter</a>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50/75 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                <tr>
                    <th scope="col" class="px-6 py-3.5">Charge ID</th>
                    <th scope="col" class="px-6 py-3.5">Amount</th>
                    <th scope="col" class="px-6 py-3.5">Status</th>
                    <th scope="col" class="px-6 py-3.5">Customer</th>
                    <th scope="col" class="px-6 py-3.5">Payment Method</th>
                    <th scope="col" class="px-6 py-3.5">Description</th>
                    <th scope="col" class="px-6 py-3.5">Date</th>
                    <th scope="col" class="px-6 py-3.5 text-right">Receipt / Link</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php foreach ($charges as $charge): ?>
                <tr class="hover:bg-slate-50/75 transition group">
                    <!-- Charge ID -->
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-1.5 font-mono text-xs text-slate-700 font-medium">
                            <span><?= htmlspecialchars($charge->id) ?></span>
                            <button type="button"
                                onclick="copyToClipboard('<?= htmlspecialchars($charge->id) ?>', this)"
                                class="text-slate-400 hover:text-slate-700 p-0.5 rounded transition"
                                title="Copy Charge ID">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>
                        </div>
                        <?php if (!empty($charge->payment_intent)): ?>
                        <div class="text-2xs font-mono text-slate-400 mt-0.5" title="Payment Intent ID">
                            pi: <?= htmlspecialchars(substr((string)$charge->payment_intent, 0, 18)) ?>...
                        </div>
                        <?php endif; ?>
                    </td>

                    <!-- Amount & Refund Status -->
                    <td class="px-6 py-4">
                        <div class="font-bold text-slate-900">
                            <?= htmlspecialchars(formatCurrency($charge->amount, $charge->currency)) ?>
                        </div>
                        <?php if ($charge->amount_refunded > 0): ?>
                        <div class="text-2xs text-rose-600 font-medium mt-0.5">
                            -<?= htmlspecialchars(formatCurrency($charge->amount_refunded, $charge->currency)) ?>
                            refunded
                        </div>
                        <?php endif; ?>
                    </td>

                    <!-- Status Badge -->
                    <td class="px-6 py-4">
                        <?= renderStatusBadge($charge->refunded ? 'refunded' : $charge->status) ?>
                        <?php if ($charge->disputed): ?>
                        <span
                            class="inline-flex items-center px-1.5 py-0.5 rounded text-2xs font-bold bg-red-100 text-red-800 ml-1">Disputed</span>
                        <?php endif; ?>
                    </td>

                    <!-- Customer Info -->
                    <td class="px-6 py-4 text-xs">
                        <div class="font-medium text-slate-800">
                            <?= htmlspecialchars($charge->billing_details->name ?? $charge->billing_details->email ?? 'Guest') ?>
                        </div>
                        <?php if (!empty($charge->customer)): ?>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <a href="customer-detail.php?id=<?= urlencode((string)$charge->customer) ?>"
                                class="text-2xs font-mono text-stripe-600 hover:underline font-semibold"
                                title="View Customer Profile">
                                <?= htmlspecialchars((string)$charge->customer) ?>
                            </a>
                            <?php if ($customerFilter !== $charge->customer): ?>
                            <a href="transactions.php?customer=<?= urlencode((string)$charge->customer) ?>"
                                class="text-3xs text-slate-400 hover:text-slate-700 bg-slate-100 px-1 py-0.2 rounded"
                                title="Filter by this customer">
                                filter
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>

                    <!-- Payment Method details -->
                    <td class="px-6 py-4 text-xs text-slate-600">
                        <?php
                                $card = $charge->payment_method_details->card ?? null;
                                if ($card) {
                                    echo '<div class="font-medium capitalize text-slate-800">' . htmlspecialchars($card->brand ?? 'Card') . ' •••• ' . htmlspecialchars($card->last4 ?? '') . '</div>';
                                    echo '<div class="text-2xs text-slate-400 capitalize">Expires ' . htmlspecialchars((string)$card->exp_month) . '/' . htmlspecialchars((string)$card->exp_year) . ' • ' . htmlspecialchars($card->funding ?? '') . '</div>';
                                } else {
                                    echo '<div class="capitalize text-slate-700">' . htmlspecialchars($charge->payment_method_details->type ?? 'Payment') . '</div>';
                                }
                                ?>
                    </td>

                    <!-- Description / Statement Descriptor -->
                    <td class="px-6 py-4 text-xs text-slate-500 max-w-xs truncate">
                        <?= htmlspecialchars($charge->description ?? $charge->calculated_statement_descriptor ?? '—') ?>
                    </td>

                    <!-- Date -->
                    <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                        <?= htmlspecialchars(formatDateTime($charge->created)) ?>
                    </td>

                    <!-- Receipt URL / Stripe link -->
                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                        <div class="flex items-center justify-end gap-2">
                            <?php if (!empty($charge->receipt_url)): ?>
                            <a href="<?= htmlspecialchars($charge->receipt_url) ?>" target="_blank" rel="noreferrer"
                                class="text-xs text-slate-600 hover:text-slate-900 underline"
                                title="View customer receipt">
                                Receipt
                            </a>
                            <?php endif; ?>
                            <a href="https://dashboard.stripe.com/test/payments/<?= htmlspecialchars($charge->id) ?>"
                                target="_blank" rel="noreferrer"
                                class="inline-flex items-center gap-1 text-stripe-600 hover:text-stripe-700 font-medium hover:underline">
                                Stripe
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Stripe Cursor Pagination Footer Bar -->
    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
        <div class="text-xs text-slate-500">
            <span>Stripe Cursor Pagination</span>
            <?php if ($startingAfter): ?>
            <span class="ml-1 text-slate-400">(after: <code
                    class="font-mono text-2xs"><?= htmlspecialchars(substr($startingAfter, 0, 14)) ?>...</code>)</span>
            <?php elseif ($endingBefore): ?>
            <span class="ml-1 text-slate-400">(before: <code
                    class="font-mono text-2xs"><?= htmlspecialchars(substr($endingBefore, 0, 14)) ?>...</code>)</span>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-2">
            <!-- Prev Button -->
            <?php if ($canGoPrev): ?>
            <a href="<?= htmlspecialchars($prevUrl) ?>"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Previous
            </a>
            <?php else: ?>
            <span
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-medium text-slate-300 bg-slate-100 border border-slate-200 rounded-lg cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Previous
            </span>
            <?php endif; ?>

            <!-- Next Button -->
            <?php if ($canGoNext): ?>
            <a href="<?= htmlspecialchars($nextUrl) ?>"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-medium text-white bg-stripe-600 rounded-lg hover:bg-stripe-700 shadow-xs transition">
                Next
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
            <?php else: ?>
            <span
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-medium text-slate-300 bg-slate-100 border border-slate-200 rounded-lg cursor-not-allowed">
                Next
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>