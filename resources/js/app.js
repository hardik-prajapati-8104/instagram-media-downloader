import Alpine from 'alpinejs';
import { initDownloader } from './downloader';
import { refreshIcons } from './icons';

window.Alpine = Alpine;

const boot = () => {
    document.querySelectorAll('[data-downloader]').forEach((root) => {
        try {
            initDownloader(root);
        } catch (error) {
            console.error('[Instagram Downloader] Failed to initialize downloader:', error);
        }
    });

    try {
        refreshIcons();
    } catch (error) {
        console.error('[Instagram Downloader] Failed to initialize icons:', error);
    }
};

Alpine.start();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
