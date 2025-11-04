// resources/js/system/realtime/smart-qr.js
import QRCodeStyling from "qr-code-styling";
import "../../general/index";
import "../../broadcast/index";

const businessId = document.querySelector('meta[name="business-id"]')?.content ?? null;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? null;

const qrState = new Map(); // companyId => state
let stopSent = false; // Flag to prevent multiple stops
let inactivityTimer = null;
let lastScanTime = Date.now(); // Shared across all, but per-company in backend

/**
 * ------------------------------------------------------------
 *  REVEAL-ANIMATION HELPERS
 * ------------------------------------------------------------
 */
// Create a masked wrapper that will reveal the QR from the center outward
const createQrWithReveal = (container) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'qr-canvas-wrapper waiting';
    container.appendChild(wrapper);

    const qr = new QRCodeStyling({
        width: 250,
        height: 250,
        data: "", // Will be updated later; start empty to avoid initial render issues
        image: `/treasury/company/favicon/favicon.png`,
        dotsOptions: { color: "#000f23ff", type: "classy" },
        backgroundOptions: { color: "#ffffff" },
        imageOptions: { crossOrigin: "anonymous", margin: 5, imageSize: 0.2 },
    });
    // Do NOT append yet – we'll append after first data update to ensure canvas is ready

    let rafId = null;
    const startReveal = (onComplete) => {
        let progress = 0;
        const duration = 800; // ms
        const startTime = performance.now();
        const step = (now) => {
            const elapsed = now - startTime;
            progress = Math.min(elapsed / duration, 1);
            wrapper.style.setProperty('--progress', progress);

            if (progress >= 1) {
                wrapper.classList.remove('waiting');
                wrapper.style.removeProperty('overflow');
                // Ensure QR is appended if not already
                const canvas = wrapper.querySelector('canvas');
                if (!canvas) qr.append(wrapper);
                if (onComplete) onComplete();
                return;
            }
            rafId = requestAnimationFrame(step);
        };
        rafId = requestAnimationFrame(step);
    };

    const cancel = () => rafId && cancelAnimationFrame(rafId);

    return { qr, wrapper, startReveal, cancel };
};

/**
 * Initialize all divs marked for QR rendering
 */
const initQrDivs = () => {
    const qrDivs = document.querySelectorAll("[data-render-qr]");
    qrDivs.forEach((div) => {
        const companyId = div.dataset.companyId;
        if (!companyId) return;

        // Remove any pre-existing manual spinner that Blade may have added
        const manualSpinner = div.querySelector('.qr-loading');
        if (manualSpinner) manualSpinner.remove();

        // Create masked QR (wrapper stays hidden until first token)
        const { qr, wrapper, startReveal } = createQrWithReveal(div);

        // Store per company
        qrState.set(companyId, { div, qr, wrapper, startReveal, revealed: false, echoListener: null });
    });
};

/**
 * Tell backend to start generating/updating QR tokens for all companies
 */
const startBackendQrSchedule = async () => {
    const companyIds = Array.from(qrState.keys());
    if (companyIds.length === 0) return;

    try {
        const formData = new FormData();
        formData.append('company_ids', JSON.stringify(companyIds)); // Send as JSON array
        formData.append('_token', csrfToken);

        await fetch(`/s/smart/presence/qr/start`, {
            method: "POST",
            body: formData,
        });
        console.log("Started QR schedule for companies:", companyIds);
        startInactivityTimer();
    } catch (err) {
        console.error("Failed to start QR schedule", err);
    }
};

/**
 * Stop backend QR schedule reliably for all companies
 */
const stopBackendQrSchedule = () => {
    if (stopSent) return; // Prevent duplicate calls
    stopSent = true;

    const companyIds = Array.from(qrState.keys());

    // Prepare data for sendBeacon (it sends as FormData or Blob)
    const params = new URLSearchParams();
    params.append('company_ids', JSON.stringify(companyIds));
    params.append('_token', csrfToken);

    // Use sendBeacon for reliable delivery on unload
    const blob = new Blob([params.toString()], { type: 'application/x-www-form-urlencoded' });
    const sent = navigator.sendBeacon(`/s/smart/presence/qr/stop`, blob);

    console.log("Stop signal sent via sendBeacon:", sent ? 'success' : 'failed', "for companies:", companyIds);

    // Also unsubscribe from Echo channels to stop receiving events
    qrState.forEach((state, companyId) => {
        const channelName = `business.${businessId}.${companyId}`;
        if (window.Echo && state.echoListener) {
            window.Echo.leave(channelName); // Leave the channel
            console.log("Left Echo channel:", channelName);
        }
    });

    if (inactivityTimer) clearTimeout(inactivityTimer);
};

/**
 * Start inactivity timer: check every 3 sec, stop if no update in 13 sec per company
 */
const startInactivityTimer = () => {
    if (inactivityTimer) clearTimeout(inactivityTimer);
    inactivityTimer = setInterval(() => {
        const now = Date.now();
        let allInactive = true;
        qrState.forEach((state, companyId) => {
            const lastUpdate = state.lastUpdate || 0;
            if (now - lastUpdate < 13000) {
                allInactive = false;
            }
        });
        if (allInactive) {
            console.log("No QR updates for 13 seconds across companies, stopping...");
            stopBackendQrSchedule();
        }
    }, 3000); // Check every 3 sec to align with interval
};

/**
 * Listen to public QR update events for all companies
 */
const startQrBroadcast = () => {
    qrState.forEach((state, companyId) => {
        const { qr, wrapper, startReveal, div } = state;

        const channelName = `business.${businessId}.${companyId}`;

        const listener = (e) => {
            console.log("Received QR Event for company", companyId, ":", e);
            const token = e.qr;

            if (!token) {
                console.warn("Empty QR token received for", companyId);
                return;
            }

            state.lastUpdate = Date.now(); // Update timestamp

            // Update data – this generates the canvas if not already
            const fullToken = window.general.baseUrl + '/s/smart/presence/qr/' + token;
            qr.update({ data: fullToken }); 

            // Append the canvas if not already in DOM (QRCodeStyling creates <canvas> on update/append)
            let canvas = wrapper.querySelector('canvas');
            if (!canvas) {
                qr.append(wrapper);
                canvas = wrapper.querySelector('canvas');
            }

            // Ensure canvas is visible and sized
            if (canvas) {
                canvas.style.width = '100%';
                canvas.style.height = '100%';
                canvas.style.display = 'block';
            }

            // Run reveal animation (only the first time)
            if (!state.revealed) {
                state.revealed = true;
                // Initially hide the wrapper content via opacity on the canvas or wrapper
                wrapper.style.opacity = '0';
                startReveal(() => {
                    wrapper.style.transition = "opacity 0.3s ease";
                    wrapper.style.opacity = '1';
                    div.style.transition = "opacity 0.3s ease";
                    div.style.opacity = 1;
                });
            }
        };

        window.Echo.channel(channelName)
            .listen(".QrUpdated", listener);

        state.echoListener = listener;
    });
};

/**
 * Lifecycle: init, start backend schedule, and listen
 */
window.addEventListener("load", () => {
    stopSent = false; // Reset flag on load
    qrState.clear();
    initQrDivs();
    if (qrState.size > 0) {
        startBackendQrSchedule();
        startQrBroadcast();
    }
});

// Use multiple events for better reliability on page leave
window.addEventListener("beforeunload", stopBackendQrSchedule);
window.addEventListener("pagehide", stopBackendQrSchedule); // For mobile/tab hide
window.addEventListener("unload", stopBackendQrSchedule);

// Optional: Visibility change to pause/resume if tab is hidden (but not full stop)
document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === 'hidden') {
        // Treat as leave if needed, or just leave channels
        stopBackendQrSchedule();
    }
});

/**
 * Add base CSS for masks and animations globally
 */
const style = document.createElement("style");
style.innerHTML = `
.qr-canvas-wrapper {
    opacity: 0; /* Start hidden until reveal */
}
`;
document.head.appendChild(style);