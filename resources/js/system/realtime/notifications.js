import '../../general/index';
import '../../broadcast/index';

// Configuration
const config = {
    userId: document.querySelector('meta[name="user-id"]')?.content ?? null,
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content ?? null,
    endpoints: {
        fetchNotifications: '/realtime/notifications/fetch',
        markAsRead: '/realtime/notifications/mark-read',
        markAllAsRead: '/realtime/notifications/mark-all-read',
        remindLater: '/realtime/notifications/remind-later'
    }
};

// Priority badge styles
const priorityStyles = {
    low: 'text-secondary',
    medium: 'text-info',
    high: 'text-warning',
    critical: 'text-danger'
};

// Notification type configurations
const notificationTypes = {
    info: { color: '#00b4af', icon: 'fas fa-info-circle' },
    success: { color: '#1b5e20', icon: 'fas fa-check-circle' },
    warning: { color: '#e65100', icon: 'fas fa-exclamation-triangle' },
    error: { color: '#b71c1c', icon: 'fas fa-times-circle' },
    alert: { color: '#c62828', icon: 'fas fa-bell' },
    reminder: { color: '#1565c0', icon: 'fas fa-clock' },
    update: { color: '#1976d2', icon: 'fas fa-sync' },
    promotion: { color: '#ef6c00', icon: 'fas fa-bullhorn' },
    announcement: { color: '#1e88e5', icon: 'fas fa-bullhorn' },
    achievement: { color: '#2e7d32', icon: 'fas fa-trophy' },
    event: { color: '#006064', icon: 'fas fa-calendar-alt' },
    deadline: { color: '#d32f2f', icon: 'fas fa-hourglass-end' },
    system: { color: '#455a64', icon: 'fas fa-cogs' },
    critical: { color: '#e53935', icon: 'fas fa-exclamation-circle' },
    custom: { color: '#546e7a', icon: 'fas fa-bell' },
    approval: { color: '#0288d1', icon: 'fas fa-check-double' },
    rejection: { color: '#f44336', icon: 'fas fa-ban' },
    request_pending: { color: '#fb8c00', icon: 'fas fa-hourglass-half' },
    leave_request: { color: '#2196f3', icon: 'fas fa-umbrella-beach' },
    leave_approved: { color: '#43a047', icon: 'fas fa-check' },
    leave_rejected: { color: '#ef5350', icon: 'fas fa-times' },
    overtime_request: { color: '#42a5f5', icon: 'fas fa-clock' },
    overtime_approved: { color: '#66bb6a', icon: 'fas fa-check' },
    overtime_rejected: { color: '#e57373', icon: 'fas fa-times' },
    expense_request: { color: '#64b5f6', icon: 'fas fa-receipt' },
    expense_approved: { color: '#81c784', icon: 'fas fa-check' },
    expense_rejected: { color: '#d81b60', icon: 'fas fa-times' },
    travel_request: { color: '#009688', icon: 'fas fa-plane' },
    travel_approved: { color: '#4caf50', icon: 'fas fa-check' },
    travel_rejected: { color: '#ad1457', icon: 'fas fa-times' },
    promotion_request: { color: '#9c27b0', icon: 'fas fa-arrow-up' },
    transfer_request: { color: '#00acc1', icon: 'fas fa-exchange-alt' },
    job_posting: { color: '#5e35b1', icon: 'fas fa-briefcase' },
    application_received: { color: '#3949ab', icon: 'fas fa-file-alt' },
    interview_scheduled: { color: '#039be5', icon: 'fas fa-calendar-check' },
    interview_feedback: { color: '#3f51b5', icon: 'fas fa-comment-dots' },
    offer_made: { color: '#2e7d32', icon: 'fas fa-handshake' },
    offer_accepted: { color: '#43a047', icon: 'fas fa-check-circle' },
    offer_rejected: { color: '#e53935', icon: 'fas fa-times-circle' },
    onboarding: { color: '#66bb6a', icon: 'fas fa-user-plus' },
    probation: { color: '#ff9800', icon: 'fas fa-user-clock' },
    confirmation: { color: '#4caf50', icon: 'fas fa-user-check' },
    contract: { color: '#1e88e5', icon: 'fas fa-file-contract' },
    contract_renewal: { color: '#039be5', icon: 'fas fa-sync-alt' },
    contract_expiry: { color: '#b71c1c', icon: 'fas fa-hourglass-end' },
    termination: { color: '#d32f2f', icon: 'fas fa-user-slash' },
    resignation: { color: '#c2185b', icon: 'fas fa-sign-out-alt' },
    retirement: { color: '#6d4c41', icon: 'fas fa-umbrella' },
    offboarding: { color: '#455a64', icon: 'fas fa-sign-out-alt' },
    transfer: { color: '#00bcd4', icon: 'fas fa-exchange-alt' },
    role_change: { color: '#1976d2', icon: 'fas fa-user-tag' },
    attendance: { color: '#0288d1', icon: 'fas fa-user-check' },
    absent: { color: '#f44336', icon: 'fas fa-user-times' },
    late: { color: '#ff7043', icon: 'fas fa-clock' },
    early_leave: { color: '#ffa726', icon: 'fas fa-sign-out-alt' },
    shift_change: { color: '#42a5f5', icon: 'fas fa-exchange-alt' },
    shift_swap: { color: '#29b6f6', icon: 'fas fa-retweet' },
    schedule_update: { color: '#26a69a', icon: 'fas fa-calendar-alt' },
    payroll: { color: '#2e7d32', icon: 'fas fa-money-check-alt' },
    payslip: { color: '#388e3c', icon: 'fas fa-file-invoice-dollar' },
    salary_credit: { color: '#43a047', icon: 'fas fa-money-bill-wave' },
    deduction: { color: '#d32f2f', icon: 'fas fa-minus-circle' },
    bonus: { color: '#66bb6a', icon: 'fas fa-gift' },
    incentive: { color: '#81c784', icon: 'fas fa-award' },
    reimbursement: { color: '#4caf50', icon: 'fas fa-money-check' },
    tax_update: { color: '#1e88e5', icon: 'fas fa-calculator' },
    benefits: { color: '#43a047', icon: 'fas fa-hand-holding-heart' },
    insurance: { color: '#1976d2', icon: 'fas fa-shield-alt' },
    performance_review: { color: '#0288d1', icon: 'fas fa-chart-line' },
    goal_setting: { color: '#3949ab', icon: 'fas fa-bullseye' },
    goal_update: { color: '#5c6bc0', icon: 'fas fa-sync' },
    goal_completed: { color: '#2e7d32', icon: 'fas fa-check-circle' },
    feedback: { color: '#3f51b5', icon: 'fas fa-comment-dots' },
    training_assigned: { color: '#1e88e5', icon: 'fas fa-graduation-cap' },
    training_completed: { color: '#43a047', icon: 'fas fa-certificate' },
    certification: { color: '#66bb6a', icon: 'fas fa-award' },
    policy_update: { color: '#757575', icon: 'fas fa-file-alt' },
    compliance: { color: '#039be5', icon: 'fas fa-check-square' },
    audit: { color: '#607d8b', icon: 'fas fa-clipboard-check' },
    document_required: { color: '#f57c00', icon: 'fas fa-file-upload' },
    document_verified: { color: '#388e3c', icon: 'fas fa-file-check' },
    document_expiry: { color: '#d32f2f', icon: 'fas fa-file-exclamation' },
    message: { color: '#1e88e5', icon: 'fas fa-envelope' },
    survey: { color: '#039be5', icon: 'fas fa-poll' },
    announcement_hr: { color: '#1565c0', icon: 'fas fa-bullhorn' },
    wellbeing: { color: '#43a047', icon: 'fas fa-heart' },
    birthday: { color: '#5e35b1', icon: 'fas fa-birthday-cake' },
    work_anniversary: { color: '#4caf50', icon: 'fas fa-trophy' },
    celebration: { color: '#ffb300', icon: 'fas fa-glass-cheers' }
};

// State management
let notificationState = new Map();

// Logging utility
const log = (message) => console.log(`[Notifications] ${message}`);

// Subscribe to Echo channels
const initEchoListeners = () => {
    if (!window.Echo || !config.userId) {
        log('Missing Echo or userId');
        window.general.showToast({ icon: 'error', title: 'Setup Error', message: 'Failed to initialize notifications.', duration: 5000 });
        return;
    }
    const channel = `user.${config.userId}`;
    window.Echo.private(channel)
        .listen('.UserNotification', handleNotification)
        .listen('.RoleNotification', handleNotification)
        .listen('.ScopeNotification', handleNotification)
        .listen('.CompanyNotification', handleNotification);
    log(`Subscribed to ${channel}`);
    window.Echo.connector.pusher.connection.bind('error', (err) => {
        log(`Echo error: ${err.message}`);
        window.general.showToast({ icon: 'error', title: 'Connection Error', message: 'Failed to connect to notification service.', duration: 5000 });
    });
};

// Handle incoming notifications
const handleNotification = (event) => {
    window.general.showToast({ icon: event.type || 'info', title: event.title, message: event.message, duration: 5000 });
    notificationState.set(event.notification_id, { ...event, status: event.read_at ? 'read' : 'unread' });
    renderNotifications();
};

// Fetch notifications
const fetchNotifications = async () => {
    try {
        const response = await axios.get(config.endpoints.fetchNotifications, {
            headers: { 'X-CSRF-Token': config.csrfToken }
        });
        if (!response.data.success || !Array.isArray(response.data.notifications)) {
            throw new Error('Invalid response format');
        }
        notificationState.clear();
        response.data.notifications.forEach(notification =>
            notificationState.set(notification.notification_id, {
                ...notification,
                status: notification.read_at ? 'read' : 'unread'
            })
        );
        renderNotifications();
    } catch (error) {
        log(`Fetch error: ${error.message}`);
    }
};

// Render notifications with fade-in
const renderNotifications = () => {
    const container = document.querySelector('.notifications-container');
    const emptyState = document.querySelector('.empty-state');
    if (!container || !emptyState) {
        log('Container or empty state not found');
        return;
    }
    container.innerHTML = '';
    const notifications = Array.from(notificationState.values())
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    emptyState.classList.toggle('d-none', notifications.length > 0);
    document.querySelectorAll('.mark-all-read').forEach(btn => btn.toggleAttribute('disabled', notifications.length === 0));
    notifications.forEach(notification => {
        const typeConfig = notificationTypes[notification.type] || notificationTypes.custom;
        const priorityStyle = priorityStyles[notification.priority] || 'text-secondary';
        const timeAgo = calculateTimeAgo(notification.created_at);
        const title = notification.title ?? '';
        const message = notification.message ?? '';
        const truncatedTitle = title.length > 40 ? title.substring(0, 40) + '...' : title;
        const truncatedMessage = message.length > 90 ? message.substring(0, 90) + '...' : message;
        container.insertAdjacentHTML('beforeend', `
        <div class="notification-item pb-1 animate__animated animate__fadeIn" 
             data-notification-id="${notification.notification_id}">
            <div class="d-flex align-items-start position-relative">
                <span class="${priorityStyle} position-absolute top-0 end-0 rounded-pill sf-10 fw-semibold">
                    ${notification.priority.charAt(0).toUpperCase() + notification.priority.slice(1)}
                </span>
                <div class="flex-shrink-0 me-1">
                    ${notification.image ? `
                        <img src="/files/${notification.image}" alt="Notification Image" class="rounded-circle" style="width: 48px; height: 48px; object-fit: cover;">
                    ` : `
                        <div class="d-flex justify-content-center align-items-center rounded-circle bg-light" style="width: 48px; height: 48px;">
                            <i class="${typeConfig.icon} sf-20" style="color: ${typeConfig.color};"></i>
                        </div>
                    `}
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold sf-12 text-dark" title="${notification.title}">
                        ${truncatedTitle}
                    </div>
                    <div class="text-muted sf-9" title="${notification.message}">
                        ${truncatedMessage}
                    </div>
                    ${notification.html ? `
                        <div>${notification.html}</div>
                    ` : ''}
                    <div class="d-flex justify-content-between align-items-center">
                        <i class="text-muted sf-9">${timeAgo}</i>
                        ${notification.status !== 'read' ? `
                            <div class="d-flex gap-1">
                                <button class="remind-later" data-notification-id="${notification.notification_id}">
                                    <i class="ti ti-clock"></i>
                                </button>
                                <button class="mark-read" data-notification-id="${notification.notification_id}">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        </div>
    `);
    });
    updateNotificationCount();
};

// Calculate time since notification
const calculateTimeAgo = (createdAt) => {
    const diffMs = Date.now() - new Date(createdAt);
    const diffMins = Math.round(diffMs / 60000);
    if (diffMins < 1) return 'Just Now';
    if (diffMins < 60) return `${diffMins} min${diffMins > 1 ? 's' : ''} ago`;
    const diffHours = Math.round(diffMins / 60);
    if (diffHours < 24) return `${diffHours} hr${diffHours > 1 ? 's' : ''} ago`;
    const diffDays = Math.round(diffHours / 24);
    return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
};

// Update notification count
const updateNotificationCount = () => {
    const count = Array.from(notificationState.values()).filter(n => n.status === 'unread').length;
    const countElement = document.querySelector('.notification-count');
    const titleElement = document.querySelector('.notification-title');
    if (countElement) {
        countElement.textContent = count;
        countElement.classList.toggle('d-none', count === 0);
    }
    if (titleElement) {
        titleElement.textContent = `Notifications (${count})`;
    }
};

// Mark a single notification as read with fade-out
const markAsRead = async (notificationId) => {
    if (!notificationState.has(notificationId)) return;
    const item = document.querySelector(`.notification-item[data-notification-id="${notificationId}"]`);
    if (item) {
        item.classList.add('animate__animated', 'animate__fadeOut');
        item.addEventListener('animationend', () => item.remove(), { once: true });
        await new Promise(resolve => setTimeout(resolve, 500));
    }
    const original = { ...notificationState.get(notificationId) };
    notificationState.delete(notificationId);
    renderNotifications();
    try {
        const response = await axios.post(config.endpoints.markAsRead, { notification_id: notificationId }, {
            headers: { 'X-CSRF-Token': config.csrfToken }
        });
        if (!response.data.success) {
            throw new Error('Failed to mark as read');
        }
    } catch (error) {
        log(`Mark read error: ${error.message}`);
        notificationState.set(notificationId, original);
        renderNotifications();
        window.general.showToast({ icon: 'error', title: 'Action Error', message: 'Failed to mark notification as read.', duration: 5000 });
    }
};

// Mark all notifications as read with fade-out
const markAllAsRead = async () => {
    const unread = [];
    notificationState.forEach((notification, id) => {
        if (notification.status === 'unread') {
            unread.push({ id, original: { ...notification } });
        }
    });
    if (unread.length) {
        const items = document.querySelectorAll('.notification-item');
        items.forEach(item => {
            item.classList.add('animate__animated', 'animate__fadeOut');
            item.addEventListener('animationend', () => item.remove(), { once: true });
        });
        await new Promise(resolve => setTimeout(resolve, 500));
    }
    notificationState.clear();
    renderNotifications();
    try {
        const response = await axios.post(config.endpoints.markAllAsRead, {}, {
            headers: { 'X-CSRF-Token': config.csrfToken }
        });
        if (!response.data.success) {
            throw new Error('Failed to mark all as read');
        }
    } catch (error) {
        log(`Mark all read error: ${error.message}`);
        unread.forEach(({ id, original }) => notificationState.set(id, original));
        renderNotifications();
        window.general.showToast({ icon: 'error', title: 'Action Error', message: 'Failed to mark all notifications as read.', duration: 5000 });
    }
};

// Set notification to remind later with fade-out
const remindLater = async (notificationId) => {
    if (!notificationState.has(notificationId)) return;
    const item = document.querySelector(`.notification-item[data-notification-id="${notificationId}"]`);
    if (item) {
        item.classList.add('animate__animated', 'animate__fadeOut');
        item.addEventListener('animationend', () => item.remove(), { once: true });
        await new Promise(resolve => setTimeout(resolve, 500));
    }
    const original = { ...notificationState.get(notificationId) };
    notificationState.delete(notificationId);
    renderNotifications();
    try {
        const remindAt = new Date(Date.now() + 30 * 60 * 1000).toISOString().slice(0, 19).replace('T', ' ');
        const response = await axios.post(config.endpoints.remindLater, {
            notification_id: notificationId,
            remind_at: remindAt
        }, {
            headers: { 'X-CSRF-Token': config.csrfToken }
        });
        if (!response.data.success) {
            throw new Error('Failed to set reminder');
        }
    } catch (error) {
        log(`Remind later error: ${error.message}`);
        notificationState.set(notificationId, original);
        renderNotifications();
        window.general.showToast({ icon: 'error', title: 'Action Error', message: 'Failed to set reminder.', duration: 5000 });
    }
};

// Initialize UI event listeners
const initUIListeners = () => {
    const toggle = document.querySelector('.notification-toggle');
    const dropdown = document.querySelector('.noti-content');
    const clearAllButtons = document.querySelectorAll('.mark-all-read');
    const cancelButton = document.querySelector('.btn-cancel');
    const container = document.querySelector('.notifications-container');
    if (!toggle || !dropdown || !container) {
        log('Missing UI elements');
        window.general.showToast({ icon: 'error', title: 'Setup Error', message: 'Notification UI elements missing.', duration: 5000 });
        return;
    }
    toggle.addEventListener('click', (e) => e.preventDefault());
    clearAllButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            markAllAsRead();
        });
    });
    if (cancelButton) {
        cancelButton.addEventListener('click', (e) => {
            e.preventDefault();
            dropdown.classList.remove('show');
        });
    }
    container.addEventListener('click', (e) => {
        const markReadBtn = e.target.closest('.mark-read');
        const remindLaterBtn = e.target.closest('.remind-later');
        if (markReadBtn) {
            markAsRead(markReadBtn.dataset.notificationId);
        } else if (remindLaterBtn) {
            remindLater(remindLaterBtn.dataset.notificationId);
        }
    });
};

// Initialize with retry
const initialize = (retries = 3, delay = 100) => {
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        if (!config.userId || !config.csrfToken) {
            log('Missing userId or csrfToken');
            window.general.showToast({ icon: 'error', title: 'Setup Error', message: 'Failed to initialize notifications.', duration: 5000 });
            return;
        }
        if (!document.querySelector('.notification-toggle') || !document.querySelector('.noti-content') || !document.querySelector('.notifications-container')) {
            if (retries > 0) {
                log(`UI elements not found, retrying in ${delay}ms`);
                setTimeout(() => initialize(retries - 1, delay * 2), delay);
            } else {
                log('Failed to find UI elements');
                window.general.showToast({ icon: 'error', title: 'Setup Error', message: 'Notification UI elements not found.', duration: 5000 });
            }
            return;
        }
        initEchoListeners();
        fetchNotifications();
        initUIListeners();
    } else {
        document.addEventListener('DOMContentLoaded', () => initialize(retries, delay));
    }
};

// Start initialization
initialize();