<?php

declare(strict_types=1);

require_once __DIR__ . '/src/config.php';

requireAuth();

$pageTitle = 'Customers - Stripe TestKit';
$stripe = getStripeClient();

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if (!in_array($limit, [10, 25, 50, 100], true)) {
    $limit = 10;
}

$startingAfter = isset($_GET['starting_after']) && trim($_GET['starting_after']) !== '' ? trim($_GET['starting_after']) : null;
$endingBefore = isset($_GET['ending_before']) && trim($_GET['ending_before']) !== '' ? trim($_GET['ending_before']) : null;
$searchQuery = isset($_GET['q']) && trim($_GET['q']) !== '' ? trim($_GET['q']) : null;

$customers = [];
$hasMore = false;
$errorMessage = null;
$firstId = null;
$lastId = null;

if ($stripe !== null) {
    try {
        if ($searchQuery) {
            // Using Stripe search API for customers
            $searchResult = $stripe->customers->search([
                'query' => "name~'{$searchQuery}' OR email~'{$searchQuery}' OR id:'{$searchQuery}'",
                'limit' => $limit,
            ]);
            $customers = $searchResult->data ?? [];
            $hasMore = $searchResult->has_more ?? false;
        } else {
            $params = ['limit' => $limit];
            if ($startingAfter) {
                $params['starting_after'] = $startingAfter;
            } elseif ($endingBefore) {
                $params['ending_before'] = $endingBefore;
            }

            $response = $stripe->customers->all($params);
            $customers = $response->data ?? [];
            $hasMore = $response->has_more ?? false;
        }

        if (!empty($customers)) {
            $firstId = $customers[0]->id;
            $lastId = $customers[count($customers) - 1]->id;
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

if (!$searchQuery && !empty($customers)) {
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
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Customers</h1>
        <p class="text-sm text-slate-500 mt-1">Live customer accounts retrieved via direct Stripe API cursor pagination.</p>
    </div>

    <!-- Search Box -->
    <div class="flex items-center gap-3">
        <form method="GET" action="customers.php" class="relative flex items-center">
            <input type="hidden" name="limit" value="<?= htmlspecialchars((string)$limit) ?>">
            <div class="relative w-full max-w-xs">
                <input 
                    type="text" 
                    name="q" 
                    value="<?= htmlspecialchars($searchQuery ?? '') ?>"
                    placeholder="Search name, email, ID..."
                    class="w-64 pl-9 pr-8 py-2 bg-white border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-stripe-500/20 focus:border-stripe-500 transition"
                >
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <?php if ($searchQuery): ?>
                    <a href="customers.php?limit=<?= $limit ?>" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600" title="Clear Search">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                <?php endif; ?>
            </div>
            <button type="submit" class="sr-only">Search</button>
        </form>
    </div>
</div>

<?php if ($errorMessage): ?>
    <div class="mb-6 rounded-xl bg-rose-50 p-4 border border-rose-200 text-rose-800 flex items-start gap-3">
        <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <h3 class="text-sm font-semibold text-rose-900">Stripe API Error</h3>
            <p class="text-xs text-rose-700 mt-0.5"><?= htmlspecialchars($errorMessage) ?></p>
        </div>
    </div>
<?php endif; ?>

<!-- Customers Table Container -->
<div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    
    <!-- Top Table Header Bar: Results count & Limit selector -->
    <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600">
        <div class="flex items-center gap-2">
            <span>Showing <strong><?= count($customers) ?></strong> <?= $searchQuery ? 'search results' : 'customers on this page' ?></span>
            <?php if ($searchQuery): ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-stripe-100 text-stripe-700">Filter: "<?= htmlspecialchars($searchQuery) ?>"</span>
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

    <?php if (empty($customers)): ?>
        <div class="p-16 text-center text-slate-500">
            <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-400">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-slate-900">No customers found</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                <?= $searchQuery ? 'No customer records matched your search query.' : 'There are no customers created in your Stripe account yet.' ?>
            </p>
            <?php if ($searchQuery): ?>
                <div class="mt-4">
                    <a href="customers.php" class="text-xs font-medium text-stripe-600 hover:underline">Clear search filter</a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50/75 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="px-6 py-3.5">Customer / ID</th>
                        <th scope="col" class="px-6 py-3.5">Email / Phone</th>
                        <th scope="col" class="px-6 py-3.5">Balance</th>
                        <th scope="col" class="px-6 py-3.5">Delinquent</th>
                        <th scope="col" class="px-6 py-3.5">Created Date</th>
                        <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php foreach ($customers as $cust): ?>
                        <tr class="hover:bg-slate-50/75 transition group">
                            <!-- Customer Name & ID -->
                            <td class="px-6 py-4">
                                <a href="customer-detail.php?id=<?= urlencode($cust->id) ?>" class="font-semibold text-slate-900 hover:text-stripe-600 transition flex items-center gap-1.5">
                                    <?= htmlspecialchars($cust->name ?? 'Unnamed Customer') ?>
                                    <svg class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 text-stripe-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                                <div class="flex items-center gap-1.5 font-mono text-xs text-slate-400 mt-0.5">
                                    <a href="customer-detail.php?id=<?= urlencode($cust->id) ?>" class="hover:text-stripe-600 hover:underline">
                                        <?= htmlspecialchars($cust->id) ?>
                                    </a>
                                    <button type="button" onclick="copyToClipboard('<?= htmlspecialchars($cust->id) ?>', this)" class="text-slate-400 hover:text-slate-700 p-0.5 rounded transition" title="Copy Customer ID">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    </button>
                                </div>
                            </td>

                            <!-- Email / Phone -->
                            <td class="px-6 py-4 text-xs">
                                <div class="font-medium text-slate-700">
                                    <?= htmlspecialchars($cust->email ?? '—') ?>
                                </div>
                                <?php if (!empty($cust->phone)): ?>
                                    <div class="text-slate-400 mt-0.5">
                                        <?= htmlspecialchars($cust->phone) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Balance & Currency -->
                            <td class="px-6 py-4 text-xs">
                                <span class="font-semibold <?= ($cust->balance ?? 0) < 0 ? 'text-emerald-700' : (($cust->balance ?? 0) > 0 ? 'text-rose-700' : 'text-slate-700') ?>">
                                    <?= htmlspecialchars(formatCurrency($cust->balance ?? 0, $cust->currency ?? 'usd')) ?>
                                </span>
                                <span class="text-slate-400 text-2xs uppercase ml-1"><?= htmlspecialchars($cust->currency ?? 'usd') ?></span>
                            </td>

                            <!-- Delinquent Status -->
                            <td class="px-6 py-4 text-xs">
                                <?php if ($cust->delinquent): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-2xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">Delinquent</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-2xs font-medium bg-slate-100 text-slate-600">Good Standing</span>
                                <?php endif; ?>
                            </td>

                            <!-- Created Date -->
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                <?= htmlspecialchars(formatDateTime($cust->created)) ?>
                            </td>

                            <!-- Action Links -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Customer Detail Page -->
                                    <a href="customer-detail.php?id=<?= urlencode($cust->id) ?>" class="inline-flex items-center gap-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 px-2.5 py-1 rounded-md text-xs font-semibold shadow-2xs hover:border-slate-300 transition" title="View Customer Full Detail">
                                        Detail
                                    </a>

                                    <!-- Filter Transactions for this Customer -->
                                    <a href="transactions.php?customer=<?= urlencode($cust->id) ?>" class="inline-flex items-center gap-1 bg-stripe-50 hover:bg-stripe-100 text-stripe-700 border border-stripe-200 px-2.5 py-1 rounded-md text-xs font-semibold transition" title="Filter Transactions for this customer">
                                        Charges
                                    </a>

                                    <!-- Filter Subscriptions for this Customer -->
                                    <a href="subscriptions.php?customer=<?= urlencode($cust->id) ?>" class="inline-flex items-center gap-1 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 px-2.5 py-1 rounded-md text-xs font-semibold transition" title="Filter Subscriptions for this customer">
                                        Subs
                                    </a>

                                    <!-- Stripe External Link -->
                                    <a href="https://dashboard.stripe.com/test/customers/<?= htmlspecialchars($cust->id) ?>" target="_blank" rel="noreferrer" class="p-1 text-slate-400 hover:text-slate-700 transition" title="Open in Stripe Dashboard">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
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
                    <span class="ml-1 text-slate-400">(after: <code class="font-mono text-2xs"><?= htmlspecialchars(substr($startingAfter, 0, 14)) ?>...</code>)</span>
                <?php elseif ($endingBefore): ?>
                    <span class="ml-1 text-slate-400">(before: <code class="font-mono text-2xs"><?= htmlspecialchars(substr($endingBefore, 0, 14)) ?>...</code>)</span>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-2">
                <!-- Prev Button -->
                <?php if ($canGoPrev): ?>
                    <a href="<?= htmlspecialchars($prevUrl) ?>" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 shadow-xs transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Previous
                    </a>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-medium text-slate-300 bg-slate-100 border border-slate-200 rounded-lg cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Previous
                    </span>
                <?php endif; ?>

                <!-- Next Button -->
                <?php if ($canGoNext): ?>
                    <a href="<?= htmlspecialchars($nextUrl) ?>" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-medium text-white bg-stripe-600 rounded-lg hover:bg-stripe-700 shadow-xs transition">
                        Next
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-medium text-slate-300 bg-slate-100 border border-slate-200 rounded-lg cursor-not-allowed">
                        Next
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
