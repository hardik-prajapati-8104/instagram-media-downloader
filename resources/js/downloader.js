import { refreshIcons } from './icons';

const STATE = { IDLE: 'IDLE', VALIDATING: 'VALIDATING', PROCESSING: 'PROCESSING', SUCCESS: 'SUCCESS', ERROR: 'ERROR', EXPIRED: 'EXPIRED' };

// Mirrors the server-side InstagramUrlValidator (the server always validates again).
const URL_RE = /^https:\/\/(?:www\.|m\.)?instagram\.com\/(?:[A-Za-z0-9._]{1,30}\/)?(?:p|reel|reels|tv)\/[A-Za-z0-9_-]{5,40}\/?(?:[?#]\S*)?$/i;

const MESSAGES = {
    invalid_url: 'Please paste a valid Instagram link (https://www.instagram.com/...).',
    unsupported_url: 'This link is not supported. Use a post, reel or video link.',
    network: 'Network error. Please check your connection and try again.',
    timeout: 'This is taking longer than expected. Please try again.',
    rate_limit: 'Too many requests. Please wait a moment and try again.',
    expired_result: 'This result has expired. Please submit the link again.',
    processing_failed: 'Processing failed. Please try again.',
};

const el = (tag, cls = '', text = null, attrs = {}) => {
    const n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text !== null) n.textContent = text;
    Object.entries(attrs).forEach(([k, v]) => v !== null && v !== undefined && n.setAttribute(k, v));
    return n;
};
const icon = (name, cls = 'h-4 w-4') => el('i', cls, null, { 'data-lucide': name });
const sleep = (ms, signal) => new Promise((res, rej) => {
    const t = setTimeout(res, ms);
    signal?.addEventListener('abort', () => { clearTimeout(t); rej(new DOMException('Aborted', 'AbortError')); }, { once: true });
});
const fmtBytes = (b) => !b ? '—' : b < 1048576 ? `${(b / 1024).toFixed(1)} KB` : `${(b / 1048576).toFixed(2)} MB`;
const fmtDur = (s) => { if (!s) return '—'; s = Math.round(s); return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`; };
const isHttps = (u) => typeof u === 'string' && u.startsWith('https://');

export function initDownloader(root) {
    const $ = (s) => root.querySelector(s);
    const form = $('[data-form]'), input = $('[data-input]'), pasteBtn = $('[data-paste]'), clearBtn = $('[data-clear]'),
        submitBtn = $('[data-submit]'), alertBox = $('[data-alert]'), progress = $('[data-progress]'), result = $('[data-result]');
    if (!form) return;

    const type = root.dataset.type || 'media';
    const endpoint = root.dataset.endpoint;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    let state = STATE.IDLE, controller = null, expiryTimer = null;

    const api = async (url, opts = {}) => {
        let res;
        try {
            res = await fetch(url, {
                ...opts,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}), ...(opts.body ? { 'Content-Type': 'application/json' } : {}) },
                credentials: 'same-origin',
            });
        } catch (e) {
            if (e.name === 'AbortError') throw e;
            throw Object.assign(new Error(MESSAGES.network), { code: 'network' });
        }
        const body = await res.json().catch(() => null);
        if (res.status === 419) throw Object.assign(new Error('Your session expired. Please reload the page and try again.'), { code: 'session' });
        if (!res.ok || !body || body.success === false) {
            const code = body?.error?.code || (res.status === 429 ? 'rate_limit' : 'processing_failed');
            throw Object.assign(new Error(body?.message || MESSAGES[code] || MESSAGES.processing_failed), { code, status: res.status });
        }
        return body;
    };

    function setState(next, payload) {
        state = next;
        root.dataset.state = next;
        const busy = next === STATE.VALIDATING || next === STATE.PROCESSING;
        submitBtn.disabled = busy;
        input.readOnly = busy;
        progress.classList.toggle('hidden', !busy);
        alertBox.classList.add('hidden');
        if (next === STATE.IDLE || busy) result.replaceChildren();
        if (next === STATE.ERROR || next === STATE.EXPIRED) showError(payload);
        const label = submitBtn.querySelector('[data-label]');
        if (label) label.textContent = busy ? 'Processing…' : 'Download';
        clearBtn.classList.toggle('hidden', input.value.trim() === '' || busy);
    }

    function showError(message) {
        alertBox.replaceChildren(icon('circle-alert', 'mt-0.5 h-5 w-5 shrink-0'), el('p', 'text-sm', message || MESSAGES.processing_failed));
        alertBox.classList.remove('hidden');
        refreshIcons();
    }

    async function waitForResult(data, signal) {
        const deadline = Date.now() + 180000;
        let delay = 1200;
        while (Date.now() < deadline) {
            await sleep(delay, signal);
            const { data: s } = await api(data.links.status, { signal });
            if (s.status === 'completed') return (await api(data.links.result, { signal })).data;
            if (s.status === 'failed') throw Object.assign(new Error(s.error?.message || MESSAGES.processing_failed), { code: s.error?.code });
            if (s.status === 'expired') throw Object.assign(new Error(MESSAGES.expired_result), { code: 'expired_result' });
            delay = Math.min(delay * 1.3, 4000);
        }
        throw Object.assign(new Error(MESSAGES.timeout), { code: 'timeout' });
    }

    async function submit() {
        if (state === STATE.VALIDATING || state === STATE.PROCESSING) return; // no duplicate submissions
        const url = input.value.trim();
        setState(STATE.VALIDATING);
        if (!URL_RE.test(url)) return setState(STATE.ERROR, url ? MESSAGES.unsupported_url : MESSAGES.invalid_url);

        controller?.abort();
        controller = new AbortController();
        const { signal } = controller;
        setState(STATE.PROCESSING);

        try {
            const first = await api(endpoint, { method: 'POST', body: JSON.stringify({ url, type }), signal });
            const data = first.data.status === 'completed' ? first.data : await waitForResult(first.data, signal);
            render(data);
            setState(STATE.SUCCESS);
            renderInto(data);
        } catch (e) {
            if (e.name === 'AbortError') return;
            setState(e.code === 'expired_result' ? STATE.EXPIRED : STATE.ERROR, e.message);
        }
    }

    // ---------- rendering (textContent only: provider data is never injected as HTML) ----------
    let lastData = null;
    function render(data) { lastData = data; }
    function renderInto(data) {
        result.replaceChildren(data.media_type === 'carousel' ? carouselCard(data) : singleCard(data));
        refreshIcons();
        if (data.expires_at) {
            clearTimeout(expiryTimer);
            const ms = new Date(data.expires_at).getTime() - Date.now();
            if (ms > 0 && ms < 2147483000) expiryTimer = setTimeout(() => {
                result.querySelectorAll('a[data-dl]').forEach((a) => { a.removeAttribute('href'); a.classList.add('pointer-events-none', 'opacity-50'); });
                setState(STATE.EXPIRED, MESSAGES.expired_result);
            }, ms);
        }
    }

    const typeLabel = { video: 'Video', reel: 'Reel', photo: 'Photo', carousel: 'Carousel', audio: 'Audio' };

    function preview(item, thumb) {
        if (item.type === 'video') {
            const v = el('video', 'aspect-video max-h-[28rem] w-full rounded-xl bg-black object-contain', null, { controls: '', preload: 'metadata', playsinline: '', src: item.preview_url });
            if (isHttps(thumb)) v.poster = thumb;
            return v;
        }
        if (item.type === 'image') return el('img', 'max-h-[28rem] w-full rounded-xl bg-gray-50 object-contain', null, { src: item.preview_url, alt: 'Preview', loading: 'lazy' });
        const wrap = el('div', 'flex w-full items-center gap-4 rounded-xl bg-brand-50 p-4');
        if (isHttps(thumb)) wrap.append(el('img', 'h-20 w-20 rounded-lg object-cover', null, { src: thumb, alt: '' }));
        wrap.append(el('audio', 'w-full', null, { controls: '', preload: 'metadata', src: item.preview_url }));
        return wrap;
    }

    const meta = (pairs) => {
        const dl = el('dl', 'grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-3');
        pairs.forEach(([k, v]) => { const d = el('div'); d.append(el('dt', 'text-xs uppercase tracking-wide text-gray-400', k), el('dd', 'font-medium text-ink', v)); dl.append(d); });
        return dl;
    };

    const dlButton = (item, label = 'Download') => {
        const a = el('a', 'btn-primary w-full sm:w-auto', null, { href: item.download_url, download: item.file_name, 'data-dl': '' });
        a.append(icon('download'), document.createTextNode(label));
        return a;
    };

    function singleCard(data) {
        const item = data.items[0];
        const card = el('div', 'card mt-8 overflow-hidden p-4 text-left sm:p-6');
        const head = el('div', 'mb-4 flex flex-wrap items-center gap-2');
        head.append(el('span', 'rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700', typeLabel[data.media_type] || 'Media'));
        if (data.media_type === 'audio') head.append(el('span', 'rounded-full bg-ink px-3 py-1 text-xs font-semibold text-white', 'MP3'));
        if (data.title) head.append(el('span', 'truncate text-sm text-gray-500', data.title));
        const rows = [['Type', typeLabel[data.media_type] || item.type], ['Format', (item.extension || '').toUpperCase() || '—']];
        if (item.width && item.height) rows.splice(1, 0, ['Resolution', `${item.width} × ${item.height}`]);
        if (item.type !== 'image') rows.push(['Duration', fmtDur(item.duration)]);
        rows.push(['File size', fmtBytes(item.file_size)]);
        if (item.type === 'image') rows.push(['MIME type', item.mime_type]);
        const foot = el('div', 'mt-5'); foot.append(dlButton(item, data.media_type === 'audio' ? 'Download MP3' : 'Download'));
        card.append(head, preview(item, item.thumbnail || data.thumbnail), el('div', 'mt-5'), meta(rows), foot);
        return card;
    }

    function carouselCard(data) {
        const card = el('div', 'card mt-8 p-4 text-left sm:p-6');
        const top = el('div', 'mb-5 flex flex-wrap items-center justify-between gap-3');
        top.append(el('p', 'text-sm font-medium', `${data.items.length} item${data.items.length === 1 ? '' : 's'} found`));
        if (data.archive && data.items.length > 0) top.append(dlButton(data.archive, 'Download All (ZIP)'));
        const grid = el('div', 'grid gap-4 sm:grid-cols-2 lg:grid-cols-3');
        data.items.forEach((item, i) => {
            const c = el('div', 'rounded-xl border border-gray-200 p-3');
            const h = el('div', 'mb-2 flex items-center justify-between');
            h.append(el('span', 'text-sm font-semibold', `#${i + 1}`), el('span', 'rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700', item.type === 'image' ? 'Photo' : 'Video'));
            const f = el('div', 'mt-3'); f.append(dlButton(item));
            c.append(h, preview(item, item.thumbnail), f);
            grid.append(c);
        });
        card.append(top, grid);
        return card;
    }

    // ---------- controls ----------
    form.addEventListener('submit', (e) => { e.preventDefault(); submit(); });
    input.addEventListener('input', () => clearBtn.classList.toggle('hidden', input.value.trim() === ''));
    clearBtn.addEventListener('click', () => { controller?.abort(); clearTimeout(expiryTimer); input.value = ''; setState(STATE.IDLE); input.focus(); });
    pasteBtn?.addEventListener('click', async () => {
        try {
            if (navigator.clipboard?.readText) {
                input.value = (await navigator.clipboard.readText()).trim();
                input.dispatchEvent(new Event('input'));
                return;
            }
            throw new Error('unavailable');
        } catch {
            // Fallback: Clipboard API missing or permission denied -> focus the field so the user can paste (Ctrl/Cmd+V).
            input.focus(); input.select();
            alertBox.replaceChildren(icon('circle-alert', 'mt-0.5 h-5 w-5 shrink-0'), el('p', 'text-sm', 'Clipboard access is unavailable. Press Ctrl+V (or long-press and Paste) in the field.'));
            alertBox.classList.remove('hidden'); refreshIcons();
        }
    });

    setState(STATE.IDLE);
}
