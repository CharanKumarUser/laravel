import '../../broadcast/index';
// Utility to safely read meta tags
const getMeta = (name, fallback = null) =>
    document.querySelector(`meta[name="${name}"]`)?.content ?? fallback;
// Core config object
const config = {
    businessId: getMeta('business-id', window.skeleton?.businessId ?? 'default'),
    csrfToken: getMeta('csrf-token'),
    endpoints: {
        sendKey: '/get-token/skeleton-key'
    }
};
// Safe logger wrapper
function log(message) {
    if (window.general?.log) {
        window.general.log(message);
    }
}
// Registry of active Echo channels
const channels = new Map();
/**
 * Initialize Echo subscriptions
 */
function initEchoListeners() {
    if (!window.Echo || !config.businessId) return;
    const channelName = `business.${config.businessId}.dataset`;
    if (channels.has(channelName)) return;
    const channel = window.Echo.private(channelName)
        .listen('.dataset.updated', handleDatasetUpdate);
    channels.set(channelName, channel);
    // connection errors only
    window.Echo.connector.pusher.connection.bind('error', (err) => {
        log(`Echo connection error: ${JSON.stringify(err)}`);
    });
}
/**
 * Check if channel is listening
 */
function isChannelListening(channelName) {
    return channels.has(channelName);
}
/**
 * Handle dataset updates from Echo
 */
async function handleDatasetUpdate(event) {
    if (event?.token) {
        await sendSkeletonKey(event.token);
    }
}
/**
 * Send skeleton key to backend and reload all matching tokens
 */
async function sendSkeletonKey(key) {
    if (!config.csrfToken) return;
    try {
        const { data } = await axios.post(
            config.endpoints.sendKey,
            { key },
            { headers: { 'X-CSRF-Token': config.csrfToken } }
        );
        if (Array.isArray(data?.tokens)) {
            data.tokens.forEach(tk => {
                window.skeleton?.reloadTable(`${tk}_t`);
                window.skeleton?.reloadCard(`${tk}_c`);
            });
        }
    } catch (error) {
        log(`Error sending key: ${error.message}`);
    }
}
// Boot Echo listeners
initEchoListeners();
