import axios from 'axios';
import pusher from 'pusher-js';


/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Session-Expiry bei direkten API-Calls abfangen
window.axios.interceptors.response.use(
    response => response,
    error => {
        const status = error.response?.status
        if (status === 401 || status === 419) {
            alert('Deine Sitzung ist abgelaufen. Die Seite wird neu geladen, damit du dich wieder einloggen kannst.')
            window.location.reload()
        }
        return Promise.reject(error)
    }
);

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import { cfg } from './runtimeConfig';

window.Pusher = pusher;

// Verbindungsdaten kommen zur Laufzeit vom Server (config/frontend.php ->
// window.__APP_CONFIG__), nicht mehr per import.meta.env aus dem Build. Nur so
// bleibt das Bundle umgebungsneutral und muss nicht pro Kunde neu gebaut werden.
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: cfg('reverb.key', 'app-key'),
    cluster: cfg('reverb.cluster', 'eu'),
    forceTLS: false,
    wsHost: cfg('reverb.host', 'localhost'),
    wssPort: cfg('reverb.port', 8080),
});
