<?php

declare(strict_types=1);
?>
</main>

<!-- Footer -->
<footer class="bg-white border-t border-slate-200 mt-auto">
    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
        <div class="flex items-center gap-2">
            <span class="font-medium text-slate-700">Stripe Dashboard</span>
            <span>•</span>
            <span>Direct Stripe PHP SDK (v<?= \Stripe\Stripe::VERSION ?? '21.x' ?>)</span>
        </div>
        <div class="flex items-center gap-4">
            <a href="https://dashboard.stripe.com/test/dashboard" target="_blank" rel="noreferrer"
                class="hover:text-stripe-600 transition flex items-center gap-1">
                Stripe Dashboard
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
            <a href="https://stripe.com/docs/api" target="_blank" rel="noreferrer"
                class="hover:text-stripe-600 transition flex items-center gap-1">
                API Docs
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        </div>
    </div>
</footer>

<!-- Copy to Clipboard helper script -->
<script>
function copyToClipboard(text, btnElement) {
    navigator.clipboard.writeText(text).then(() => {
        const originalHtml = btnElement.innerHTML;
        btnElement.innerHTML = `
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="text-xs text-emerald-600 font-semibold">Copied!</span>
                `;
        setTimeout(() => {
            btnElement.innerHTML = originalHtml;
        }, 1500);
    }).catch(err => {
        console.error('Failed to copy text: ', err);
    });
}
</script>
</body>

</html>