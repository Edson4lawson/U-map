import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Only initialize Echo if Reverb environment variables are configured
const hasReverbConfig = import.meta.env.VITE_REVERB_APP_KEY && 
                        import.meta.env.VITE_REVERB_HOST && 
                        import.meta.env.VITE_REVERB_PORT;

let echo = null;

if (hasReverbConfig) {
    const scheme = import.meta.env.VITE_REVERB_SCHEME || 'http';
    const isHttps = scheme === 'https';

    echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: parseInt(import.meta.env.VITE_REVERB_PORT),
        wssPort: parseInt(import.meta.env.VITE_REVERB_PORT),
        forceTLS: isHttps,
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        authorizer: (channel, options) => {
            return {
                authorize: (socketId, callback) => {
                    const token = localStorage.getItem('u_map_token');
                    fetch(`${import.meta.env.VITE_API_URL || 'http://localhost:8000/api'}/broadcasting/auth`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            socket_id: socketId,
                            channel_name: channel.name
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Unauthorized');
                        }
                        return response.json();
                    })
                    .then(data => {
                        callback(false, data);
                    })
                    .catch(error => {
                        callback(true, error);
                    });
                }
            };
        }
    });
}

export default echo;
