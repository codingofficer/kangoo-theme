// ==UserScript==
// @name         Kangoo GSC URL Inspection Helper
// @namespace    https://kangoopouches.co.uk/
// @version      1.0.0
// @description  Queue Kangoo Pouches URLs and speed up Google Search Console URL Inspection without auto-clicking request-indexing actions.
// @author       Kangoo Pouches
// @match        https://search.google.com/search-console/*
// @grant        GM_setClipboard
// ==/UserScript==

(function () {
    'use strict';

    const STORAGE_KEY = 'kangoo_gsc_inspection_queue_v1';
    const PROPERTY = 'sc-domain:kangoopouches.co.uk';
    const DEFAULT_URLS = [
        'https://kangoopouches.co.uk/sitemap_index.xml',
        'https://kangoopouches.co.uk/kangoo_blog-sitemap.xml',
        'https://kangoopouches.co.uk/product_cat-sitemap.xml',
        'https://kangoopouches.co.uk/blog/what-is-zyn-uk-guide-to-zyn-nicotine-pouches/',
        'https://kangoopouches.co.uk/blog/what-are-velo-nicotine-pouches-uk-guide/',
        'https://kangoopouches.co.uk/blog/what-are-pablo-nicotine-pouches-uk-guide/',
        'https://kangoopouches.co.uk/blog/what-are-killa-nicotine-pouches-uk-guide/',
        'https://kangoopouches.co.uk/blog/what-are-nordic-spirit-nicotine-pouches-uk-guide/',
        'https://kangoopouches.co.uk/blog/what-are-ubbs-nicotine-pouches-uk-guide/',
        'https://kangoopouches.co.uk/blog/what-are-fumi-nicotine-pouches-uk-guide/',
        'https://kangoopouches.co.uk/blog/what-are-xqs-nicotine-pouches-uk-guide/',
        'https://kangoopouches.co.uk/blog/nicotine-pouch-brands-uk-zyn-velo-pablo-killa-nordic-spirit-ubbs-fumi-and-xqs-compared/',
        'https://kangoopouches.co.uk/product-category/zyn/',
        'https://kangoopouches.co.uk/product-category/velo/',
        'https://kangoopouches.co.uk/product-category/pablo/',
        'https://kangoopouches.co.uk/product-category/killa/',
        'https://kangoopouches.co.uk/product-category/nordic-spirit/',
        'https://kangoopouches.co.uk/product-category/ubbs/',
        'https://kangoopouches.co.uk/product-category/fumi/',
        'https://kangoopouches.co.uk/product-category/xqs/'
    ];

    function normalizeUrl(value) {
        const url = String(value || '').trim();
        if (!url || !/^https:\/\/kangoopouches\.co\.uk\//i.test(url)) {
            return '';
        }

        return url.split('#')[0];
    }

    function uniqueUrls(urls) {
        return Array.from(new Set(urls.map(normalizeUrl).filter(Boolean)));
    }

    function loadQueue() {
        try {
            const parsed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
            if (Array.isArray(parsed)) {
                return parsed
                    .filter((item) => item && item.url)
                    .map((item) => ({
                        url: normalizeUrl(item.url),
                        status: item.status === 'done' ? 'done' : 'pending',
                        inspectedAt: item.inspectedAt || ''
                    }))
                    .filter((item) => item.url);
            }
        } catch (error) {
            console.warn('Kangoo URL queue reset after parse error', error);
        }

        return DEFAULT_URLS.map((url) => ({ url, status: 'pending', inspectedAt: '' }));
    }

    function saveQueue(queue) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(queue));
    }

    let queue = loadQueue();
    let activeUrl = '';
    let statusText = 'Ready';

    function pendingItems() {
        return queue.filter((item) => item.status !== 'done');
    }

    function doneItems() {
        return queue.filter((item) => item.status === 'done');
    }

    function copyText(text) {
        if (typeof GM_setClipboard === 'function') {
            GM_setClipboard(text, 'text');
            return Promise.resolve();
        }

        return navigator.clipboard.writeText(text);
    }

    function inspectionUrl(url) {
        const resource = encodeURIComponent(PROPERTY);
        const inspected = encodeURIComponent(url);
        return `https://search.google.com/search-console/inspect?resource_id=${resource}&url=${inspected}`;
    }

    function findInspectionInput() {
        const candidates = Array.from(document.querySelectorAll('input, textarea, [contenteditable="true"]'));
        return candidates.find((node) => {
            const label = [
                node.getAttribute('aria-label'),
                node.getAttribute('placeholder'),
                node.getAttribute('data-placeholder'),
                node.closest('[aria-label]') ? node.closest('[aria-label]').getAttribute('aria-label') : ''
            ].join(' ').toLowerCase();

            return label.includes('inspect') || label.includes('url');
        }) || null;
    }

    function fillInspectionInput(url) {
        const input = findInspectionInput();

        if (!input) {
            return false;
        }

        input.focus();

        if (input.isContentEditable) {
            input.textContent = url;
        } else {
            input.value = url;
        }

        input.dispatchEvent(new InputEvent('input', { bubbles: true, inputType: 'insertText', data: url }));
        input.dispatchEvent(new Event('change', { bubbles: true }));

        setTimeout(() => {
            input.dispatchEvent(new KeyboardEvent('keydown', {
                bubbles: true,
                cancelable: true,
                key: 'Enter',
                code: 'Enter',
                which: 13,
                keyCode: 13
            }));
        }, 150);

        return true;
    }

    function inspectNext() {
        const next = pendingItems()[0];

        if (!next) {
            statusText = 'Queue complete';
            render();
            return;
        }

        activeUrl = next.url;
        copyText(activeUrl).catch(() => {});

        const filled = fillInspectionInput(activeUrl);
        statusText = filled
            ? 'Filled inspection search. When Google finishes, click Request indexing, then mark done here.'
            : 'Copied URL. If Search Console did not fill, paste it into URL Inspection.';

        if (!filled) {
            window.location.href = inspectionUrl(activeUrl);
        }

        render();
    }

    function markActiveDone() {
        if (!activeUrl) {
            statusText = 'No active URL to mark done';
            render();
            return;
        }

        queue = queue.map((item) => item.url === activeUrl
            ? { ...item, status: 'done', inspectedAt: new Date().toISOString() }
            : item
        );
        saveQueue(queue);
        statusText = 'Marked submitted';
        render();
    }

    function markCurrentVisibleDone() {
        const url = normalizeUrl(new URLSearchParams(window.location.search).get('url'));

        if (url) {
            activeUrl = url;
        }

        markActiveDone();
    }

    function resetQueue() {
        queue = DEFAULT_URLS.map((url) => ({ url, status: 'pending', inspectedAt: '' }));
        activeUrl = '';
        statusText = 'Priority queue reset';
        saveQueue(queue);
        render();
    }

    function importUrls() {
        const textarea = document.querySelector('[data-kangoo-gsc-import]');
        const imported = uniqueUrls((textarea ? textarea.value : '').split(/\s+/));

        if (!imported.length) {
            statusText = 'No valid Kangoo Pouches URLs found';
            render();
            return;
        }

        const existing = new Set(queue.map((item) => item.url));
        imported.forEach((url) => {
            if (!existing.has(url)) {
                queue.push({ url, status: 'pending', inspectedAt: '' });
            }
        });

        saveQueue(queue);
        statusText = `Imported ${imported.length} URL(s)`;
        render();
    }

    function exportRemaining() {
        const urls = pendingItems().map((item) => item.url).join('\n');
        copyText(urls).then(() => {
            statusText = 'Remaining URLs copied';
            render();
        }).catch(() => {
            statusText = 'Could not copy remaining URLs';
            render();
        });
    }

    function render() {
        let panel = document.querySelector('[data-kangoo-gsc-panel]');

        if (!panel) {
            panel = document.createElement('aside');
            panel.setAttribute('data-kangoo-gsc-panel', '');
            document.body.appendChild(panel);
        }

        const pending = pendingItems();
        const done = doneItems();
        const next = pending[0] ? pending[0].url : 'Nothing pending';

        panel.innerHTML = `
            <style>
                [data-kangoo-gsc-panel] {
                    position: fixed;
                    z-index: 2147483647;
                    right: 18px;
                    bottom: 18px;
                    width: 360px;
                    max-width: calc(100vw - 36px);
                    max-height: calc(100vh - 36px);
                    overflow: auto;
                    background: #fff;
                    color: #111827;
                    border: 1px solid #d1d5db;
                    box-shadow: 0 18px 45px rgba(15, 23, 42, .22);
                    border-radius: 10px;
                    font: 13px/1.45 Arial, sans-serif;
                }
                [data-kangoo-gsc-panel] * { box-sizing: border-box; }
                .kangoo-gsc-head { padding: 12px 14px; border-bottom: 1px solid #e5e7eb; }
                .kangoo-gsc-head strong { display: block; font-size: 14px; }
                .kangoo-gsc-body { padding: 12px 14px; }
                .kangoo-gsc-row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
                .kangoo-gsc-button {
                    border: 1px solid #cbd5e1;
                    background: #f8fafc;
                    color: #111827;
                    border-radius: 7px;
                    padding: 7px 9px;
                    cursor: pointer;
                    font-weight: 700;
                }
                .kangoo-gsc-button--primary { background: #0b57d0; border-color: #0b57d0; color: #fff; }
                .kangoo-gsc-button--done { background: #16a34a; border-color: #16a34a; color: #fff; }
                .kangoo-gsc-button--danger { color: #b91c1c; }
                .kangoo-gsc-next {
                    margin-top: 8px;
                    padding: 8px;
                    border: 1px solid #e5e7eb;
                    border-radius: 7px;
                    background: #f9fafb;
                    overflow-wrap: anywhere;
                }
                .kangoo-gsc-status { margin-top: 8px; color: #475569; }
                .kangoo-gsc-import {
                    width: 100%;
                    min-height: 70px;
                    margin-top: 10px;
                    border: 1px solid #cbd5e1;
                    border-radius: 7px;
                    padding: 8px;
                    font: 12px/1.4 monospace;
                }
                .kangoo-gsc-small { color: #64748b; font-size: 12px; }
            </style>
            <div class="kangoo-gsc-head">
                <strong>Kangoo URL Inspection</strong>
                <span class="kangoo-gsc-small">${pending.length} pending, ${done.length} done</span>
            </div>
            <div class="kangoo-gsc-body">
                <div class="kangoo-gsc-small">Next URL</div>
                <div class="kangoo-gsc-next">${escapeHtml(next)}</div>
                <div class="kangoo-gsc-row">
                    <button class="kangoo-gsc-button kangoo-gsc-button--primary" data-kangoo-gsc-action="inspect">Inspect next</button>
                    <button class="kangoo-gsc-button kangoo-gsc-button--done" data-kangoo-gsc-action="done">Mark done</button>
                    <button class="kangoo-gsc-button" data-kangoo-gsc-action="current-done">Mark current URL done</button>
                </div>
                <div class="kangoo-gsc-row">
                    <button class="kangoo-gsc-button" data-kangoo-gsc-action="copy">Copy remaining</button>
                    <button class="kangoo-gsc-button kangoo-gsc-button--danger" data-kangoo-gsc-action="reset">Reset queue</button>
                </div>
                <textarea class="kangoo-gsc-import" data-kangoo-gsc-import placeholder="Paste extra Kangoo Pouches URLs, one per line"></textarea>
                <div class="kangoo-gsc-row">
                    <button class="kangoo-gsc-button" data-kangoo-gsc-action="import">Import URLs</button>
                </div>
                <div class="kangoo-gsc-status">${escapeHtml(statusText)}</div>
            </div>
        `;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-kangoo-gsc-action]');

        if (!button) {
            return;
        }

        event.preventDefault();

        const action = button.getAttribute('data-kangoo-gsc-action');

        if (action === 'inspect') inspectNext();
        if (action === 'done') markActiveDone();
        if (action === 'current-done') markCurrentVisibleDone();
        if (action === 'copy') exportRemaining();
        if (action === 'reset') resetQueue();
        if (action === 'import') importUrls();
    });

    render();
})();
