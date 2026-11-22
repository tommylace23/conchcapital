/**
 * Recent Payouts ticker (public social proof).
 * Renders into any element with id="payoutsTicker".
 * Usage: <div id="payoutsTicker" data-limit="12"></div> + <script src="js/payouts.js"></script>
 */
(function () {
    'use strict';

    function timeAgo(iso) {
        if (!iso) return 'recently';
        const then = new Date(iso).getTime();
        if (isNaN(then)) return 'recently';
        const secs = Math.max(0, Math.floor((Date.now() - then) / 1000));
        if (secs < 60) return 'just now';
        const mins = Math.floor(secs / 60);
        if (mins < 60) return mins + ' min ago';
        const hrs = Math.floor(mins / 60);
        if (hrs < 24) return hrs + (hrs === 1 ? ' hr ago' : ' hrs ago');
        const days = Math.floor(hrs / 24);
        return days + (days === 1 ? ' day ago' : ' days ago');
    }

    function money(amount, currency) {
        const n = Number(amount) || 0;
        return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function itemHtml(p) {
        return (
            '<div class="payout-chip">' +
                '<span class="payout-dot"></span>' +
                '<span class="payout-name">' + p.name + '</span>' +
                '<span class="payout-action">withdrew</span>' +
                '<span class="payout-amount">' + money(p.amount) + '</span>' +
                '<span class="payout-time">' + timeAgo(p.at) + '</span>' +
            '</div>'
        );
    }

    async function render(el) {
        const track = el.querySelector('.payout-track');
        if (!track) return;

        try {
            const data = await API.get('/api/activity/public');
            const payouts = (data && data.payouts) || [];

            if (!payouts.length) {
                el.classList.add('hidden');
                return;
            }

            // Duplicate the list so the marquee loops seamlessly.
            const html = payouts.map(itemHtml).join('');
            track.innerHTML = html + html;

            el.classList.remove('hidden');
            // Slower for longer lists, keeps a consistent, calm pace.
            const seconds = Math.max(24, payouts.length * 5);
            track.style.animationDuration = seconds + 's';
        } catch (err) {
            console.error('Failed to load payouts ticker:', err);
            el.classList.add('hidden');
        }
    }

    function init() {
        document.querySelectorAll('#payoutsTicker').forEach(render);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
