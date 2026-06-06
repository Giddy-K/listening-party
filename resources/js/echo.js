import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// _tcConfig is injected server-side by the blade layout so values are
// always correct at runtime regardless of how the JS was built.
const cfg = window._tcConfig ?? {};

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: cfg.pusherKey ?? import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: cfg.pusherCluster ?? import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    forceTLS: true,
});
