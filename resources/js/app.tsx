import { createInertiaApp } from '@inertiajs/react';
import '../css/app.css';

const appName = import.meta.env.VITE_APP_NAME || 'Clutch.';

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    strictMode: true,
    progress: {
        color: '#cf5015',
    },
});
