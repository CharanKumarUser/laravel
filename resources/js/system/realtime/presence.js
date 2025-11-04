import '../../broadcast/index';

// Utility to safely read meta tags
const getMeta = (name, fallback = null) =>
    document.querySelector(`meta[name="${name}"]`)?.content ?? fallback;

// Core config object
const config = {
    userId: getMeta('user-id', window.skeleton?.userId ?? null),
    businessId: getMeta('business-id', window.skeleton?.businessId ?? 'default'),
    companyId: getMeta('company-id', window.skeleton?.companyId ?? 'default'),
    scopeId: getMeta('scope-id', window.skeleton?.scopeId ?? 'default'),
    csrfToken: getMeta('csrf-token'),
    endpoints: {
        updateStatus: '/realtime/presence/status',
        userTyping: '/realtime/presence/typing',
        getStatus: '/realtime/presence/status'
    },
    idleTimeout: 5 * 60 * 1000 // 5 minutes
};

// Status display configuration
const statusConfig = {
    online: { color: '#28A745', text: 'Online', icon: 'fa fa-circle' },
    offline: { color: '#6C757D', text: 'Offline', icon: 'fa fa-circle' },
    away: { color: '#FFC107', text: 'Away', icon: 'fa fa-clock' },
    dnd: { color: '#DC3545', text: 'Do Not Disturb', icon: 'fa fa-ban' },
    invisible: { color: '#6C757D', text: 'Offline', icon: 'fa fa-circle' }
};

// Track connected users by channel
const connectedUsers = new Map();

// Track typing users by channel
const typingUsers = new Map();

// Idle timer
let idleTimer;

// Safe logger wrapper
function log(message) {
    window.general?.log?.(message) ?? console.log(message);
}

/**
 * Initialize presence channel subscriptions.
 */
function initPresenceListeners() {
    if (!window.Echo || !config.userId) {
        log('Echo or userId not available, skipping presence subscriptions');
        window.general.showToast({
            icon: 'error',
            title: 'Setup Error',
            message: 'Presence system could not be initialized.',
            duration: 5000
        });
        return;
    }

    // Subscribe to business channel
    subscribeToChannel('business', config.businessId);

    // Subscribe to company channel
    subscribeToChannel('company', config.companyId);

    // Subscribe to scope channel
    subscribeToChannel('scope', config.scopeId);

    window.Echo.connector.pusher.connection.bind('error', (err) => {
        log(`Echo connection error: ${JSON.stringify(err)}`);
        window.general.showToast({
            icon: 'error',
            title: 'Connection Error',
            message: 'Failed to connect to presence service.',
            duration: 5000
        });
    });
}

/**
 * Subscribe to a specific channel.
 */
function subscribeToChannel(channelType, channelId) {
    if (channels.has(`${channelType}.${channelId}`)) return;

    if (!channelId) {
        log(`Skipping subscription to presence-${channelType}: missing channel ID`);
        return;
    }

    const channel = window.Echo.join(`presence-${channelType}.${channelId}`)
        .here((users) => {
            log(`Presence-${channelType} channel users:`, users);
            users.forEach(user => {
                if (user.status !== 'invisible') {
                    connectedUsers.set(user.id, user);
                    updateUserStatusUI(user.id, user.status, user.last_seen_at, channelType, channelId);
                }
            });
        })
        .joining((user) => {
            log(`User joined presence-${channelType}: ${user.id}`);
            if (user.status !== 'invisible') {
                connectedUsers.set(user.id, user);
                updateUserStatusUI(user.id, user.status, user.last_seen_at, channelType, channelId);
            }
        })
        .leaving((user) => {
            log(`User left presence-${channelType}: ${user.id}`);
            connectedUsers.delete(user.id);
            updateUserStatusUI(user.id, 'offline', new Date().toISOString(), channelType, channelId);
        })
        .listen('UserStatusUpdated', (event) => {
            if (event.channel_type === channelType && event.channel_id === channelId) {
                log(`User status updated in ${channelType}: ${event.user_id} to ${event.status}`);
                if (event.status !== 'invisible') {
                    connectedUsers.set(event.user_id, {
                        id: event.user_id,
                        status: event.status,
                        last_seen_at: event.last_seen_at
                    });
                    updateUserStatusUI(event.user_id, event.status, event.last_seen_at, channelType, channelId);
                } else {
                    connectedUsers.delete(event.user_id);
                    updateUserStatusUI(event.user_id, 'offline', event.last_seen_at, channelType, channelId);
                }
            }
        })
        .listen('UserTyping', (event) => {
            if (event.channel_type === channelType && event.channel_id === channelId) {
                log(`User typing in ${channelType}: ${event.user_id} in chat ${event.chat_id}`);
                handleTyping(event.user_id, event.chat_id, channelType, channelId);
            }
        });

    channels.set(`${channelType}.${channelId}`, channel);
    log(`Subscribed to presence-${channelType}.${channelId}`);
}

/**
 * Update user status UI.
 */
function updateUserStatusUI(userId, status, lastSeenAt, channelType, channelId) {
    const userElement = document.querySelector(`[data-user-id="${userId}"][data-channel-type="${channelType}"][data-channel-id="${channelId}"]`);
    if (!userElement) return;

    const config = statusConfig[status] || statusConfig.offline;
    const statusDot = userElement.querySelector('.status-dot');
    const statusText = userElement.querySelector('.status-text');

    if (statusDot) {
        statusDot.style.backgroundColor = config.color;
        statusDot.title = config.text;
    }
    if (statusText) {
        statusText.textContent = status === 'offline' && lastSeenAt
            ? `Last seen ${calculateTimeAgo(lastSeenAt)}`
            : config.text;
    }
}

/**
 * Handle typing indicator.
 */
function handleTyping(userId, chatId, channelType, channelId) {
    const chatElement = document.querySelector(`[data-chat-id="${chatId}"][data-channel-type="${channelType}"][data-channel-id="${channelId}"]`);
    if (!chatElement) return;

    typingUsers.set(userId, Date.now());
    updateTypingUI(chatId, channelType, channelId);

    // Clear typing after 5 seconds
    setTimeout(() => {
        typingUsers.delete(userId);
        updateTypingUI(chatId, channelType, channelId);
    }, 5000);
}

/**
 * Update typing UI.
 */
function updateTypingUI(chatId, channelType, channelId) {
    const chatElement = document.querySelector(`[data-chat-id="${chatId}"][data-channel-type="${channelType}"][data-channel-id="${channelId}"]`);
    if (!chatElement) return;

    const typingIndicator = chatElement.querySelector('.typing-indicator');
    if (typingIndicator) {
        const typingCount = Array.from(typingUsers.keys()).length;
        typingIndicator.textContent = typingCount > 0
            ? `${typingCount} user${typingCount > 1 ? 's' : ''} typing...`
            : '';
    }
}

/**
 * Calculate time ago for last seen.
 */
function calculateTimeAgo(createdAt) {
    const now = new Date();
    const created = new Date(createdAt);
    const diffMs = now - created;
    const diffMins = Math.round(diffMs / 60000);
    if (diffMins < 1) return 'just now';
    if (diffMins < 60) return `${diffMins} min${diffMins > 1 ? 's' : ''} ago`;
    const diffHours = Math.round(diffMins / 60);
    if (diffHours < 24) return `${diffHours} hr${diffHours > 1 ? 's' : ''} ago`;
    const diffDays = Math.round(diffHours / 24);
    return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
}

/**
 * Update user status via API.
 */
async function updateStatus(status, channelType, channelId, lastSeenAt = null) {
    try {
        const { data } = await axios.post(config.endpoints.updateStatus, {
            status,
            channel_type: channelType,
            channel_id: channelId,
            last_seen_at: lastSeenAt || new Date().toISOString()
        }, {
            headers: { 'X-CSRF-Token': config.csrfToken }
        });
        if (!data.success) {
            throw new Error('Failed to update status');
        }
        log(`Status updated to ${status} on ${channelType}.${channelId}`);
    } catch (error) {
        log(`Error updating status: ${error.message}`);
        window.general.showToast({
            icon: 'error',
            title: 'Status Error',
            message: 'Failed to update status.',
            duration: 5000
        });
    }
}

/**
 * Broadcast typing event.
 */
async function broadcastTyping(chatId, channelType, channelId) {
    try {
        const { data } = await axios.post(config.endpoints.userTyping, {
            chat_id: chatId,
            channel_type: channelType,
            channel_id: channelId
        }, {
            headers: { 'X-CSRF-Token': config.csrfToken }
        });
        if (!data.success) {
            throw new Error('Failed to broadcast typing');
        }
        log(`Typing broadcast for chat ${chatId} on ${channelType}.${channelId}`);
    } catch (error) {
        log(`Error broadcasting typing: ${error.message}`);
    }
}

/**
 * Detect idle state.
 */
function setupIdleDetection(channelType, channelId) {
    function resetIdleTimer() {
        clearTimeout(idleTimer);
        if (config.userId && connectedUsers.get(config.userId)?.status !== 'dnd') {
            updateStatus('online', channelType, channelId);
            idleTimer = setTimeout(() => {
                updateStatus('away', channelType, channelId);
            }, config.idleTimeout);
        }
    }

    window.addEventListener('mousemove', resetIdleTimer);
    window.addEventListener('keydown', resetIdleTimer);
    window.addEventListener('click', resetIdleTimer);
    resetIdleTimer();
}

// Boot presence system
document.addEventListener('DOMContentLoaded', () => {
    log('Initializing presence system');
    initPresenceListeners();
    setupIdleDetection('business', config.businessId);
    setupIdleDetection('company', config.companyId);
    setupIdleDetection('scope', config.scopeId);

    // Example: Handle typing in a chat input
    const chatInputs = document.querySelectorAll('.chat-input');
    chatInputs.forEach(input => {
        input.addEventListener('input', () => {
            const chatElement = input.closest('[data-chat-id]');
            const chatId = chatElement?.dataset.chatId;
            const channelType = chatElement?.dataset.channelType;
            const channelId = chatElement?.dataset.channelId;
            if (chatId && channelType && channelId) {
                broadcastTyping(chatId, channelType, channelId);
            }
        });
    });

    // Example: Handle status toggle (e.g., from a dropdown)
    const statusToggle = document.querySelector('#status-toggle');
    if (statusToggle) {
        statusToggle.addEventListener('change', (e) => {
            const channelType = e.target.dataset.channelType; // e.g., 'business'
            const channelId = e.target.dataset.channelId; // e.g., 'BIZ000001'
            updateStatus(e.target.value, channelType, channelId);
        });
    }
});