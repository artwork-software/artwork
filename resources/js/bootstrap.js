import axios from 'axios';
import pusher from 'pusher-js';


/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

window.Pusher = pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.MIX_PUSHER_APP_KEY ?? '12345',
    cluster: import.meta.env.MIX_PUSHER_APP_CLUSTER ?? 'eu',
    forceTLS: false,
    wsHost: window.location.hostname,
    wsPort: import.meta.env.MIX_PUSHER_PORT,
});

