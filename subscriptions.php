<?php

declare(strict_types=1);

require_once __DIR__ . '/src/config.php';

requireAuth();

$pageTitle = 'Subscriptions - Stripe Dashboard';
$stripe = getStripeClient();

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if (!in_array($limit, [10, 25, 50, 100], true)) {
    $limit = 10;
}

$status = isset($_GET['status']) && trim($_GET['status']) !== '' ? trim($_GET['status']) : 'all';
$validStatuses = ['all', 'active', 'trialing', 'past_due', 'canceled', 'unpaid', 'incomplete'];
if (!in_array($status, $validStatuses, true)) {
    $status = 'all';
}

$startingAfter = isset($_GET['starting_after']) && trim($_GET['starting_after']) !== '' ? trim($_GET['starting_after']) : null;
$endingBefore = isset($_GET['ending_before']) && trim($_GET['ending_before']) !== '' ? trim($_GET['ending_before']) : null;
$customerFilter = isset($_GET['customer']) && trim($_GET['customer']) !== '' ? trim($_GET['customer']) : null;

$subscriptions = [];
$hasMore = false;
$errorMessage = null;
$firstId = null;
$lastId = null;

if ($stripe !== null) {
    try {
        $params = [
            'limit' => $limit,
            'status' => $status,
            'expand' => ['data.customer', 'data.default_payment_method'],
        ];

        if ($startingAfter) {
            $params['starting_after'] = $startingAfter;
        } elseif ($endingBefore) {
            $params['ending_before'] = $endingBefore;
        }

        if ($customerFilter) {
            $params['customer'] = $customerFilter;
        }

        $response = $stripe->subscriptions->all($params);
        $subscriptions = $response->data ?? [];
        $hasMore = $response->has_more ?? false;

        if (!empty($subscriptions)) {
            $firstId = $subscriptions[0]->id;
            $lastId = $subscriptions[count($subscriptions) - 1]->id;
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

if (!empty($subscriptions)) {
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
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Subscriptions</h1>
        <p class="text-sm text-slate-500 mt-1">Live customer subscriptions and recurring plans directly from Stripe.</p>
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
        <a href="<?= buildPageUrl(['customer' => null]) ?>"
            class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded bg-stripe-200 text-stripe-900 hover:bg-stripe-300 font-bold transition"
            title="Clear customer filter">
            &times; Clear
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Status Filter Tabs -->
<div class="mb-6 border-b border-slate-200 flex space-x-2 sm:space-x-4 overflow-x-auto">
    <?php foreach ($validStatuses as $st): ?>
    <?php $isActive = ($status === $st); ?>
    <a href="<?= buildPageUrl(['status' => $st, 'starting_after' => null, 'ending_before' => null]) ?>"
        class="pb-3 px-2 text-xs sm:text-sm font-medium border-b-2 whitespace-nowrap transition <?= $isActive ? 'border-stripe-600 text-stripe-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' ?>">
        <?= ucfirst(str_replace('_', ' ', $st)) ?>
    </a>
    <?php endforeach; ?>
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

<!-- Subscriptions Table Container -->
<div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">

    <!-- Top Table Header Bar: Results count & Limit selector -->
    <div
        class="px-6 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600">
        <div class="flex items-center gap-2">
            <span>Showing <strong><?= count($subscriptions) ?></strong> subscriptions on this page</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-slate-200 text-slate-700">Status:
                <?= ucfirst($status) ?></span>
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

    <?php if (empty($subscriptions)): ?>
    <div class="p-16 text-center text-slate-500">
        <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-400">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
        </div>
        <h3 class="text-base font-semibold text-slate-900">No subscriptions found</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
            <?= $customerFilter ? 'No subscriptions found for this customer.' : 'No ' . ($status !== 'all' ? htmlspecialchars($status) . ' ' : '') . 'subscriptions found in this Stripe account.' ?>
        </p>
        <?php if ($customerFilter): ?>
        <div class="mt-4">
            <a href="<?= buildPageUrl(['customer' => null]) ?>"
                class="text-xs font-semibold text-stripe-600 hover:underline">Clear customer filter</a>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50/75 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                <tr>
                    <th scope="col" class="px-6 py-3.5">Subscription ID</th>
                    <th scope="col" class="px-6 py-3.5">Customer</th>
                    <th scope="col" class="px-6 py-3.5">Plan / Price</th>
                    <th scope="col" class="px-6 py-3.5">Status</th>
                    <th scope="col" class="px-6 py-3.5">Current Period</th>
                    <th scope="col" class="px-6 py-3.5">Created Date</th>
                    <th scope="col" class="px-6 py-3.5 text-right">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php foreach ($subscriptions as $sub): ?>
                <tr class="hover:bg-slate-50/75 transition group">
                    <!-- Subscription ID & Flag -->
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-1.5 font-mono text-xs text-slate-700 font-medium">
                            <span><?= htmlspecialchars($sub->id) ?></span>
                            <button type="button" onclick="copyToClipboard('<?= htmlspecialchars($sub->id) ?>', this)"
                                class="text-slate-400 hover:text-slate-700 p-0.5 rounded transition"
                                title="Copy Subscription ID">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>
                        </div>
                        <?php if ($sub->cancel_at_period_end): ?>
                        <div
                            class="inline-flex items-center gap-1 text-2xs text-amber-700 font-semibold bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded mt-1">
                            Cancels at period end
                        </div>
                        <?php endif; ?>
                    </td>

                    <!-- Customer Info with link to customer detail -->
                    <td class="px-6 py-4 text-xs">
                        <?php
                                $custName = 'Customer';
                                $custId = is_string($sub->customer) ? $sub->customer : ($sub->customer->id ?? '—');
                                if (is_object($sub->customer)) {
                                    $custName = $sub->customer->name ?? $sub->customer->email ?? 'Unnamed Customer';
                                }
                                ?>
                        <a href="customer-detail.php?id=<?= urlencode($custId) ?>"
                            class="font-semibold text-slate-800 hover:text-stripe-600 transition block">
                            <?= htmlspecialchars($custName) ?>
                        </a>
                        <div class="flex items-center gap-1.5 font-mono text-2xs text-slate-400 mt-0.5">
                            <a href="customer-detail.php?id=<?= urlencode($custId) ?>"
                                class="text-stripe-600 hover:underline">
                                <?= htmlspecialchars($custId) ?>
                            </a>
                            <?php if ($customerFilter !== $custId): ?>
                            <a href="subscriptions.php?customer=<?= urlencode($custId) ?>"
                                class="text-3xs text-slate-400 hover:text-slate-700 bg-slate-100 px-1 py-0.2 rounded"
                                title="Filter by this customer">
                                filter
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>

                    <!-- Plan / Price / Interval -->
                    <td class="px-6 py-4 text-xs">
                        <?php
                                $firstItem = $sub->items->data[0] ?? null;
                                if ($firstItem && isset($firstItem->price)) {
                                    $price = $firstItem->price;
                                    $unitAmount = $price->unit_amount ?? 0;
                                    $currency = $price->currency ?? 'usd';
                                    $interval = $price->recurring->interval ?? 'month';
                                    $intervalCount = $price->recurring->interval_count ?? 1;
                                    $qty = $firstItem->quantity ?? 1;

                                    echo '<div class="font-semibold text-slate-900">';
                                    echo htmlspecialchars(formatCurrency($unitAmount, $currency));
                                    echo '<span class="font-normal text-slate-500 text-2xs"> / ' . ($intervalCount > 1 ? "{$intervalCount} " : '') . htmlspecialchars($interval) . '</span>';
                                    if ($qty > 1) {
                                        echo '<span class="ml-1 text-2xs text-slate-500 font-normal">(&times;' . htmlspecialchars((string)$qty) . ')</span>';
                                    }
                                    echo '</div>';
                                    if (!empty($price->nickname)) {
                                        echo '<div class="text-2xs text-slate-400">' . htmlspecialchars($price->nickname) . '</div>';
                                    }
                                } else {
                                    echo '<span class="text-slate-400">—</span>';
                                }
                                ?>
                    </td>

                    <!-- Status Badge -->
                    <td class="px-6 py-4">
                        <?= renderStatusBadge($sub->status) ?>
                    </td>

                    <!-- Current Period -->
                    <td class="px-6 py-4 text-xs text-slate-600 whitespace-nowrap">
                        <div><?= htmlspecialchars(formatDate($sub->current_period_start ?? null)) ?></div>
                        <div class="text-slate-400 text-2xs">to
                            <?= htmlspecialchars(formatDate($sub->current_period_end ?? null)) ?></div>
                    </td>

                    <!-- Created Date -->
                    <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                        <?= htmlspecialchars(formatDateTime($sub->created)) ?>
                    </td>

                    <!-- External Link / Details -->
                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                        <a href="https://dashboard.stripe.com/test/subscriptions/<?= htmlspecialchars($sub->id) ?>"
                            target="_blank" rel="noreferrer"
                            class="inline-flex items-center gap-1 text-stripe-600 hover:text-stripe-700 font-medium hover:underline">
                            Stripe
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
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