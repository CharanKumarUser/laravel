import { Html5Qrcode } from "html5-qrcode";
document.addEventListener("DOMContentLoaded", () => {
  // ===== Greeting Function =====
  window.greetings = function (userName = "User", withBreak = true, defaulte = false) {
    const hour = new Date().getHours();
    // Time-based greeting sets
    const greetingsByTime = {
      lateNight: [
        `Good late night, <b>${userName}</b>! Keep the momentum going!`,
        `Good late night, <b>${userName}</b>! You're unstoppable!`,
        `Good late night, <b>${userName}</b>! Big dreams need late hours!`,
        `Good late night, <b>${userName}</b>! Power through the night!`,
        `Good late night, <b>${userName}</b>! Fuel your ambitions!`,
        `Good late night, <b>${userName}</b>! The night is young!`,
        `Good late night, <b>${userName}</b>! Chase those goals!`,
        `Good late night, <b>${userName}</b>! Stay inspired!`
      ],
      earlyMorning: [
        `Good early morning, <b>${userName}</b>! Rise and shine!`,
        `Good early morning, <b>${userName}</b>! Start your day strong!`,
        `Good early morning, <b>${userName}</b>! Embrace the calm!`,
        `Good early morning, <b>${userName}</b>! Make today count!`,
        `Good early morning, <b>${userName}</b>! Dawn of new opportunities!`,
        `Good early morning, <b>${userName}</b>! Energize your spirit!`,
        `Good early morning, <b>${userName}</b>! Fresh start ahead!`,
        `Good early morning, <b>${userName}</b>! Conquer the day!`
      ],
      morning: [
        `Good morning, <b>${userName}</b>! Let's make today amazing!`,
        `Good morning, <b>${userName}</b>! Seize the day!`,
        `Good morning, <b>${userName}</b>! Your energy is infectious!`,
        `Good morning, <b>${userName}</b>! Begin with purpose!`,
        `Good morning, <b>${userName}</b>! Shine bright today!`,
        `Good morning, <b>${userName}</b>! Positive vibes only!`,
        `Good morning, <b>${userName}</b>! Unlock your potential!`,
        `Good morning, <b>${userName}</b>! Adventure awaits!`
      ],
      afternoon: [
        `Good afternoon, <b>${userName}</b>! Keep the energy high!`,
        `Good afternoon, <b>${userName}</b>! You're halfway to greatness!`,
        `Good afternoon, <b>${userName}</b>! Stay focused and strong!`,
        `Good afternoon, <b>${userName}</b>! Keep pushing forward!`,
        `Good afternoon, <b>${userName}</b>! Maintain the momentum!`,
        `Good afternoon, <b>${userName}</b>! Success is near!`,
        `Good afternoon, <b>${userName}</b>! Stay motivated!`,
        `Good afternoon, <b>${userName}</b>! Achieve more!`
      ],
      evening: [
        `Good evening, <b>${userName}</b>! Time to unwind and reflect.`,
        `Good evening, <b>${userName}</b>! You made today count!`,
        `Good evening, <b>${userName}</b>! Relax and recharge.`,
        `Good evening, <b>${userName}</b>! You've earned a break!`,
        `Good evening, <b>${userName}</b>! Celebrate your wins!`,
        `Good evening, <b>${userName}</b>! Peaceful moments ahead.`,
        `Good evening, <b>${userName}</b>! Wind down gracefully.`,
        `Good evening, <b>${userName}</b>! Reflect on the good.`
      ],
      night: [
        `Good night, <b>${userName}</b>! Rest well for a new start.`,
        `Good night, <b>${userName}</b>! Sweet dreams await!`,
        `Good night, <b>${userName}</b>! Sleep tight and recharge.`,
        `Good night, <b>${userName}</b>! Tomorrow's a fresh canvas!`,
        `Good night, <b>${userName}</b>! Dream big tonight!`,
        `Good night, <b>${userName}</b>! Restore your energy.`,
        `Good night, <b>${userName}</b>! Peaceful slumber!`,
        `Good night, <b>${userName}</b>! Until tomorrow!`
      ]
    };
    // Motivational quotes
    const quotes = [
      "Stay positive, work hard, make it happen.",
      "Dream big and dare to achieve.",
      "You’re stronger than you think.",
      "Every day is a second chance.",
      "Keep your spirit bright!",
      "Believe you can, and you’re halfway there.",
      "Progress, not perfection.",
      "Do what makes your soul shine.",
      "Your energy introduces you before you even speak.",
      "Make today ridiculously amazing!",
      "Small steps create big results.",
      "Keep shining, superstar!",
      "Smile — it’s your best accessory.",
      "Success starts with self-belief.",
      "Stay humble, work hard, be kind.",
      "Good things take time — keep going!",
      "Turn your dreams into plans.",
      "Be proud of how far you’ve come.",
      "Let your light guide the way.",
      "The best is yet to come.",
      "Embrace the journey, not just the destination.",
      "Your only limit is your mind.",
      "Be the change you wish to see.",
      "Hustle in silence, let success make the noise.",
      "Every accomplishment starts with the decision to try.",
      "Focus on the good.",
      "Rise above the storm and you will find the sunshine.",
      "The harder you work, the luckier you get.",
      "Believe in yourself and all that you are.",
      "Make your life a masterpiece.",
      "Opportunities don't happen, you create them.",
      "Don't watch the clock; do what it does. Keep going.",
      "Your attitude determines your direction.",
      "Success is a journey, not a destination.",
      "Keep going, you're getting there.",
      "The future belongs to those who believe in the beauty of their dreams.",
      "You are capable of amazing things.",
      "Start where you are. Use what you have. Do what you can.",
      "Don't stop until you're proud.",
      "Push yourself, because no one else is going to do it for you."
    ];
    // Determine time period
    let timeGroup = "";
    if (hour >= 0 && hour < 4) timeGroup = "lateNight";
    else if (hour >= 4 && hour < 6) timeGroup = "earlyMorning";
    else if (hour >= 6 && hour < 12) timeGroup = "morning";
    else if (hour >= 12 && hour < 17) timeGroup = "afternoon";
    else if (hour >= 17 && hour < 20) timeGroup = "evening";
    else timeGroup = "night";
    // Random selection helper
    const randomFrom = arr => arr[Math.floor(Math.random() * arr.length)];
    const separator = withBreak ? "<br>" : " ";
    // Normal random mode
    const greeting = randomFrom(greetingsByTime[timeGroup]);
    const quote = randomFrom(quotes);
    // Default mode (formatted HTML output)
    if (defaulte) {
      return `<span class="sf-13 fw-normal">${greeting}</span>${separator}<span class="sf-11 text-muted">${quote}</span>`;
    }
    return `${greeting}${separator}${quote}`;
  };
  // ===== Element References =====
  const bottomBar = document.querySelector(".smart-bar-bottom");
  const arrow = document.querySelector(".smart-bar-toggle-arrow");
  const container = document.querySelector(".smart-bar-listWrap");
  const qrModalEl = document.getElementById("qrScannerModal");
  const cameraSelect = document.getElementById("cameraSelect");
  const wrapperId = "qr-scanner-wrapper";
  const qrWrapper = document.getElementById(wrapperId);
  // Validate required elements
  if (!bottomBar || !container || !arrow) {
    return;
  }
  // ===== Greeting Setup =====
  const greetingMeta = document.querySelector('meta[name="user-name"]');
  const userName = greetingMeta?.content || "User";
  const greeting = window.greetings(userName, true, true);
  // Add greeting to QR modal
  const modalBody = qrModalEl?.querySelector('.modal-body');
  if (modalBody && qrWrapper) {
    const greetingEl = document.createElement('p');
    greetingEl.className = 'mb-1 sf-11 text-center';
    greetingEl.innerHTML = greeting;
    greetingEl.setAttribute('aria-live', 'polite');
    modalBody.insertBefore(greetingEl, qrWrapper);
  }
  // Add timer display to modal
  const timerDisplay = document.createElement('div');
  timerDisplay.id = 'timerDisplay';
  timerDisplay.className = 'mt-1 text-muted text-center';
  timerDisplay.setAttribute('aria-live', 'polite');
  if (modalBody && cameraSelect) {
    modalBody.insertBefore(timerDisplay, cameraSelect.nextSibling);
  } else if (modalBody) {
    modalBody.appendChild(timerDisplay);
  }
  // ===== Infinite Scroll Setup =====
  const originalContent = container.innerHTML;
  for (let i = 1; i < 25; i++) container.innerHTML += originalContent;
  const allItems = container.querySelectorAll(".smart-bar-list:not(.smart-bar-indicator)");
  let lastActive = null;
  function centerScroll() {
    const total = container.scrollWidth;
    const visible = container.offsetWidth;
    container.scrollLeft = total / 2 - visible / 2;
  }
  function highlightMiddle() {
    const rect = container.getBoundingClientRect();
    const mid = rect.left + rect.width / 2;
    let closest = null, dist = Infinity;
    allItems.forEach(it => {
      const r = it.getBoundingClientRect();
      const c = r.left + r.width / 2;
      const d = Math.abs(c - mid);
      if (d < dist) {
        dist = d;
        closest = it;
      }
    });
    if (closest && closest !== lastActive) {
      lastActive?.classList.remove("active");
      closest.classList.add("active");
      lastActive = closest;
      // Announce active item for accessibility
      const label = closest.querySelector(".smart-bar-text")?.textContent?.trim();
      if (label) {
        const announceEl = document.createElement('div');
        announceEl.setAttribute('aria-live', 'polite');
        announceEl.className = 'visually-hidden';
        announceEl.textContent = `Active item: ${label}`;
        document.body.appendChild(announceEl);
        setTimeout(() => announceEl.remove(), 1000);
      }
    }
  }
  requestAnimationFrame(() => {
    centerScroll();
    highlightMiddle();
  });
  container.addEventListener("scroll", () => {
    requestAnimationFrame(highlightMiddle);
    const total = container.scrollWidth;
    const left = container.scrollLeft;
    if (left < total / 3) container.scrollLeft += total / 3;
    else if (left > total * 2 / 3) container.scrollLeft -= total / 3;
  });
  // ===== Smart Bar Toggle =====
  function toggleBar() {
    const isExpanded = bottomBar.classList.toggle("expanded");
    arrow.setAttribute('aria-expanded', isExpanded);
    // Announce state change for accessibility
    const announceEl = document.createElement('div');
    announceEl.setAttribute('aria-live', 'polite');
    announceEl.className = 'visually-hidden';
    announceEl.textContent = `Smart bar ${isExpanded ? 'expanded' : 'collapsed'}`;
    document.body.appendChild(announceEl);
    setTimeout(() => announceEl.remove(), 1000);
  }
  arrow.addEventListener("click", toggleBar);
  arrow.setAttribute('role', 'button');
  arrow.setAttribute('aria-label', 'Toggle smart bar');
  arrow.setAttribute('aria-expanded', bottomBar.classList.contains("expanded"));
  // ===== Swipe Up/Down =====
  let touchStartY = 0;
  arrow.addEventListener("touchstart", e => {
    touchStartY = e.touches[0].clientY;
  }, { passive: true });
  arrow.addEventListener("touchend", e => {
    const endY = e.changedTouches[0].clientY;
    const deltaY = endY - touchStartY;
    const threshold = 50;
    if (Math.abs(deltaY) > threshold) {
      if (deltaY < 0 && !bottomBar.classList.contains("expanded")) toggleBar();
      else if (deltaY > 0 && bottomBar.classList.contains("expanded")) toggleBar();
    }
  });
  // ===== Click Events for Items =====
  container.addEventListener("click", (e) => {
    const anchor = e.target.closest("a");
    const item = e.target.closest(".smart-bar-list");
    if (!item && !anchor) return;
    // Handle data-type logic
    if (anchor?.dataset?.type) {
      const type = anchor.dataset.type.toLowerCase();
      if (type === "link") {
        const href = anchor.getAttribute("href");
        if (href && href !== "javascript:void(0);") {
          window.location.href = href;
        }
        return;
      } else if (type === "btn") {
        e.preventDefault();
        return;
      }
    }
    // Default behavior: if active
    if (item?.classList.contains("active")) {
      const label = item.querySelector(".smart-bar-text")?.textContent?.trim();
      if (label === "Scan & Go") {
        openQrModal();
      } else {
        alert(`Clicked: ${label}`);
      }
    }
  });
  // ===== Motion / Shake Detection =====
  let lastShake = 0;
  const VERTICAL_THRESHOLD = 15;
  const SIDE_THRESHOLD = 10;
  const COOLDOWN = 1500;
  function handleMotion(event) {
    let acc = event.acceleration || event.accelerationIncludingGravity;
    if (!acc || !acc.x || !acc.y || !acc.z) return;
    const now = Date.now();
    const total = Math.sqrt(acc.x ** 2 + acc.y ** 2 + acc.z ** 2);
    const side = Math.abs(acc.x);
    if (total > VERTICAL_THRESHOLD && now - lastShake > COOLDOWN) {
      toggleBar();
      lastShake = now;
    } else if (side > SIDE_THRESHOLD && now - lastShake > COOLDOWN) {
      openQrModal();
      lastShake = now;
    }
  }
  if (typeof DeviceMotionEvent !== "undefined" && typeof DeviceMotionEvent.requestPermission === "function") {
    DeviceMotionEvent.requestPermission()
      .then(state => {
        if (state === "granted") {
          window.addEventListener("devicemotion", handleMotion);
        }
      })
      .catch(err => {
        window.addEventListener("devicemotion", handleMotion); // Fallback for older browsers
      });
  } else {
    window.addEventListener("devicemotion", handleMotion);
  }
  // ===== QR Scanner Modal Setup =====
  let scanner = null;
  let cameras = [];
  let currentCam = 0;
  let countdownInterval = null;
  const bsModal = qrModalEl ? new bootstrap.Modal(qrModalEl, { backdrop: "static" }) : null;
  const TIMEOUT_SECONDS = 10;
  function computeQrBoxSize() {
    const w = qrWrapper?.clientWidth || 300;
    const h = qrWrapper?.clientHeight || 300;
    return Math.max(150, Math.min(Math.min(w, h) * 0.9, 600));
  }
  function populateCameraDropdown() {
    if (!cameraSelect || !cameras.length) return;
    cameraSelect.innerHTML = '';
    cameras.forEach((cam, index) => {
      const option = document.createElement('option');
      option.value = index;
      option.textContent = cam.label || `Camera ${index + 1}`;
      cameraSelect.appendChild(option);
    });
    cameraSelect.value = currentCam;
    cameraSelect.classList.toggle("d-none", cameras.length <= 1);
    cameraSelect.setAttribute('aria-label', 'Select camera');
  }
  function startCountdown() {
    let remaining = TIMEOUT_SECONDS;
    timerDisplay.innerHTML = `<span class="sf-11">Scan in <b>${remaining}</b>s</span>`;
    countdownInterval = setInterval(() => {
      remaining--;
      timerDisplay.innerHTML = `<span class="sf-11">Scan in <b>${remaining}</b>s</span>`;
      if (remaining <= 0) {
        clearInterval(countdownInterval);
        stopScanner();
        bsModal?.hide();
      }
    }, 1000);
  }
  async function startScanner() {
    if (!qrWrapper) {
      timerDisplay.textContent = 'QR scanner wrapper not found';
      return;
    }
    if (scanner) {
      await stopScanner();
    }
    scanner = new Html5Qrcode(wrapperId);
    try {
      if (!cameras.length) {
        cameras = await Html5Qrcode.getCameras();
      }
      if (!cameras.length) {
        timerDisplay.textContent = 'No cameras available';
        return;
      }
      const back = cameras.findIndex(c => /back|rear|environment/i.test(c.label));
      if (currentCam === 0 && back >= 0) {
        currentCam = back;
      }
      populateCameraDropdown();
      await scanner.start(
        cameras[currentCam].id,
        { fps: 10, qrbox: computeQrBoxSize(), aspectRatio: 1, disableFlip: false },
        (msg) => {
          clearInterval(countdownInterval);
          timerDisplay.textContent = '';
          stopScanner();
          bsModal?.hide();
          if (/^https?:\/\//i.test(msg)) {
            window.location.href = msg;
          }
        },
        (err) => {
          // if (err && err.name !== "NotFoundException") {
          //   timerDisplay.textContent = 'Error scanning QR code';
          // }
        }
      );
      startCountdown();
    } catch (err) {
      timerDisplay.textContent = 'Camera access denied';
    }
  }
  async function stopScanner() {
    clearInterval(countdownInterval);
    timerDisplay.textContent = '';
    if (!scanner) return;
    try {
      await scanner.stop();
      scanner.clear();
      scanner = null;
    } catch (err) {
    }
  }
  async function switchCamera() {
    if (cameras.length <= 1) return;
    clearInterval(countdownInterval);
    currentCam = parseInt(cameraSelect.value, 10);
    await stopScanner();
    await startScanner();
  }
  if (cameraSelect) {
    cameraSelect.addEventListener("change", (e) => {
      e.preventDefault();
      switchCamera();
    });
  }
  if (qrModalEl) {
    qrModalEl.addEventListener("shown.bs.modal", () => startScanner());
    qrModalEl.addEventListener("hidden.bs.modal", () => stopScanner());
  }
  function openQrModal() {
    if (!bsModal) {
      return;
    }
    bsModal.show();
  }
  // Attach direct Scan & Go button triggers
  const scanTriggers = document.querySelectorAll('a[data-scan="true"], .smart-bar-list .ti-qrcode');
  scanTriggers.forEach(btn => {
    btn.addEventListener("click", e => {
      e.preventDefault();
      openQrModal();
    });
    btn.setAttribute('role', 'button');
    btn.setAttribute('aria-label', 'Open QR scanner');
  });
});