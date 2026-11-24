/**
 * Market ticker (US equities + crypto).
 * Renders into <div id="marketTicker"> using data from /api/market/overview.
 */
(function () {
    'use strict';

    function fmtPrice(p) {
        const n = Number(p) || 0;
        return n >= 1000
            ? '$' + n.toLocaleString('en-US', { maximumFractionDigits: 0 })
            : '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function chip(item) {
        const up = Number(item.change_pct) >= 0;
        const arrow = up ? '▲' : '▼';
        const cls = up ? 'mkt-up' : 'mkt-down';
        return (
            '<span class="mkt-chip">' +
                '<span class="mkt-sym">' + item.symbol + '</span>' +
                '<span class="mkt-price">' + fmtPrice(item.price) + '</span>' +
                '<span class="' + cls + '">' + arrow + ' ' + Math.abs(Number(item.change_pct) || 0).toFixed(2) + '%</span>' +
            '</span>'
        );
    }

    async function render(el) {
        const track = el.querySelector('.mkt-track');
        if (!track) return;

        try {
            const data = await API.get('/api/market/overview');
            const items = [].concat(data.stocks || [], data.crypto || []);

            if (!items.length) {
                el.classList.add('hidden');
                return;
            }

            const html = items.map(chip).join('');
            track.innerHTML = html + html;
            el.classList.remove('hidden');

            const seconds = Math.max(30, items.length * 4);
            track.style.animationDuration = seconds + 's';
        } catch (err) {
            console.error('Market ticker failed:', err);
            el.classList.add('hidden');
        }
    }

    function init() {
        document.querySelectorAll('#marketTicker').forEach(render);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
