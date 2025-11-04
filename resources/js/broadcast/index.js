/**
 * BroadcastConfig.js
 * Centralized Echo + Pusher/Reverb configuration
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Expose dependencies to global scope if needed
window.Pusher = Pusher;

// Extract meta values safely
const getMetaContent = (name, fallback = null) =>
  document.querySelector(`meta[name="${name}"]`)?.content ?? fallback;

// Determine mode
const mode = getMetaContent('modal', 'lander');
const isAuth = mode !== 'lander';

// Config builder with Map for clarity & easy overrides
const configMap = new Map([
  ['broadcaster', 'reverb'],
  ['key', import.meta.env.VITE_REVERB_APP_KEY],
  ['wsHost', import.meta.env.VITE_REVERB_HOST],
  ['wsPort', import.meta.env.VITE_REVERB_PORT ?? 80],
  ['wssPort', import.meta.env.VITE_REVERB_PORT ?? 443],
  [
    'forceTLS',
    (import.meta.env.VITE_REVERB_SCHEME ?? 'https').toLowerCase() === 'https'
  ],
  ['transports', ['ws', 'wss']]
]);

// Convert map to plain object
const echoConfig = Object.fromEntries(configMap);

// Add authentication details only if required
if (isAuth) {
  echoConfig.authEndpoint = '/broadcasting/auth';
  echoConfig.auth = {
    headers: {
      'X-CSRF-Token': getMetaContent('csrf-token')
    }
  };
}

// Initialize Echo
window.Echo = new Echo(echoConfig);

// System log on ready
document.addEventListener('DOMContentLoaded', () => {
  const status = isAuth ? 'authenticated' : 'public';
  if (window.general?.log) {
    window.general.log(`System initialized in **${status}** mode`);
  } else {
    console.warn(`System initialized in ${status} mode (General not loaded)`);
  }
});