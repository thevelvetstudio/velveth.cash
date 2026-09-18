import './bootstrap';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { FlashToaster } from '@/Components/shared/FlashToaster';

if (import.meta.env.PROD && 'serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch((error) => {
        console.error('No se pudo registrar el Service Worker de Velvet:', error);
    });
}

type InitialFlashProps = {
    flash?: {
        success?: string | null;
        error?: string | null;
        status?: string | null;
    };
};

const appName = import.meta.env.VITE_APP_NAME || 'The Velvet Studio';

document.documentElement.classList.add('dark');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob('./Pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <>
                <App {...props} />
                <FlashToaster initialFlash={(props.initialPage.props as unknown as InitialFlashProps).flash} />
            </>
        );
    },
    progress: {
        color: '#D4AF37',
    },
});
