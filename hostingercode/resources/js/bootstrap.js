window._ = require('lodash');

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Only initialize when Pusher key is set
 * to avoid unnecessary connections and unload-listener permissions violations.
 */
const pusherKey = process.env.MIX_PUSHER_APP_KEY;
if (pusherKey && pusherKey !== '') {
    const Echo = require('laravel-echo').default;
    window.Pusher = require('pusher-js');
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: process.env.MIX_PUSHER_APP_CLUSTER || 'mt1',
        forceTLS: true
    });
} else {
    window.Echo = { channel: () => ({ listen: () => {} }), private: () => ({ listen: () => {} }), leave: () => {} };
}
