/**
 * Initializes face enrollment UI and functionality for elements with [data-face-enroll] attribute.
 * Supports face detection, enrollment, matching, and profile management using @vladmandic/human and IndexedDB.
 * Relies on window.speechSynthesis for voice feedback and navigator.mediaDevices for camera access.
 *
 * @requires @vladmandic/human
 */
import Human from '@vladmandic/human';

export async function faceEnroll() {
  // Validate required dependencies
  if (!Human) {
    console.error('Human.js is required but not loaded');
    return;
  }
  if (!window.speechSynthesis) {
    console.error('SpeechSynthesis is required but not available');
    return;
  }
  if (!navigator.mediaDevices) {
    console.error('MediaDevices API is required but not available');
    return;
  }

  const enrollAreas = document.querySelectorAll('[data-face-enroll]');
  if (!enrollAreas.length) {
    console.log('No face enrollment areas found');
    return;
  }

  const CONFIG = {
    BORDER_THICKNESS: 10,
    VALID_COLOR: '#1ec5a6',
    INVALID_COLOR: '#ff5a5a',
    NEUTRAL_COLOR: 'rgba(255,255,255,0.08)',
    ANIMATION_SPEED: 0.6,
    CAPTURES_PER_STEP: 3,
    MODEL_BASE: '/node_modules/@vladmandic/human/models', // Adjust based on your project structure
    VIDEO_CONSTRAINTS: { audio: false, video: { width: { ideal: 1280 }, height: { ideal: 1280 }, facingMode: 'user' } },
    DB_NAME: 'face-enroll-db',
    DB_STORE: 'profiles',
    THRESHOLD: 6
  };

  const STEPS = [
    { key: 'straight', label: 'Look straight', yaw: [-0.3, 0.3], pitch: [-0.3, 0.3] },
    { key: 'left', label: 'Turn head right', yaw: [0.3, Infinity] },
    { key: 'right', label: 'Turn head left', yaw: [-Infinity, -0.3] },
    { key: 'up', label: 'Look up', pitch: [-Infinity, -0.2] },
    { key: 'down', label: 'Look down', pitch: [0.2, Infinity] }
  ];

  enrollAreas.forEach(async area => {
    try {
      // DOM elements
      const videoEl = area.querySelector('#video');
      const guideCircleEl = area.querySelector('#guideCircle');
      const instructionEl = area.querySelector('#instruction');
      const enrollBtn = area.querySelector('#enrollBtn');
      const retryBtn = area.querySelector('#retryBtn');
      const matchBtn = area.querySelector('#matchBtn');
      const clearBtn = area.querySelector('#clearBtn');
      const userIdEl = area.querySelector('#userId');
      const profilesEl = area.querySelector('#profiles');
      const emotionEl = area.querySelector('#emotion');

      let human = null;
      let descriptorStore = {};
      let running = false;
      let currentStepIndex = 0;
      let videoStream = null;
      let streaming = false;
      let lastInstruction = '';

      // Initialize DOM and styles
      function initDOM() {
        document.documentElement.style.setProperty('--circle-border', `${CONFIG.BORDER_THICKNESS}px`);
        document.documentElement.style.setProperty('--valid-color', CONFIG.VALID_COLOR);
        document.documentElement.style.setProperty('--invalid-color', CONFIG.INVALID_COLOR);
        document.documentElement.style.setProperty('--neutral-color', CONFIG.NEUTRAL_COLOR);
        document.documentElement.style.setProperty('--animation-speed', `${CONFIG.ANIMATION_SPEED}s`);

        enrollBtn.addEventListener('click', toggleEnroll);
        retryBtn.addEventListener('click', () => beginEnroll(userIdEl.value.trim()));
        matchBtn.addEventListener('click', onMatch);
        clearBtn.addEventListener('click', async () => {
          if (!confirm('Delete all profiles? This cannot be undone.')) return;
          await clearProfiles();
          refreshProfiles();
          alert('All profiles deleted');
        });
      }

      // IndexedDB operations
      function openDB() {
        return new Promise((resolve, reject) => {
          const req = indexedDB.open(CONFIG.DB_NAME, 1);
          req.onupgradeneeded = ev => {
            const db = ev.target.result;
            if (!db.objectStoreNames.contains(CONFIG.DB_STORE)) db.createObjectStore(CONFIG.DB_STORE, { keyPath: 'id' });
          };
          req.onsuccess = () => resolve(req.result);
          req.onerror = () => reject(new Error('Failed to open IndexedDB'));
        });
      }

      async function saveProfile(id, payload) {
        const db = await openDB();
        return new Promise((resolve, reject) => {
          const tx = db.transaction(CONFIG.DB_STORE, 'readwrite');
          const store = tx.objectStore(CONFIG.DB_STORE);
          store.put({ id, payload, updatedAt: new Date().toISOString() });
          tx.oncomplete = () => resolve();
          tx.onerror = () => reject(new Error('Failed to save profile'));
        });
      }

      async function listProfiles() {
        const db = await openDB();
        return new Promise((resolve, reject) => {
          const tx = db.transaction(CONFIG.DB_STORE, 'readonly');
          const store = tx.objectStore(CONFIG.DB_STORE);
          const r = store.getAll();
          r.onsuccess = () => resolve(r.result || []);
          r.onerror = () => reject(new Error('Failed to list profiles'));
        });
      }

      async function clearProfiles() {
        const db = await openDB();
        return new Promise((resolve, reject) => {
          const tx = db.transaction(CONFIG.DB_STORE, 'readwrite');
          const store = tx.objectStore(CONFIG.DB_STORE);
          store.clear();
          tx.oncomplete = () => resolve();
          tx.onerror = () => reject(new Error('Failed to clear profiles'));
        });
      }

      // Human.js initialization
      async function loadHuman() {
        if (human) return;
        human = new Human({
          modelBasePath: CONFIG.MODEL_BASE,
          backend: 'webgl',
          debug: true,
          face: {
            enabled: true,
            detector: { rotation: true, maxDetected: 1, minConfidence: 0.5 },
            mesh: { enabled: true },
            description: { enabled: true },
            emotion: { enabled: true }
          }
        });
        await human.load();
        await human.warmup();
      }

      // Camera handling
      async function startCamera() {
        stopCamera();
        videoStream = await navigator.mediaDevices.getUserMedia(CONFIG.VIDEO_CONSTRAINTS);
        videoEl.srcObject = videoStream;
        await videoEl.play();
        streaming = true;
        setTimeout(resetGuideStroke, 50);
      }

      function stopCamera() {
        if (videoStream) {
          videoStream.getTracks().forEach(t => t.stop());
          videoEl.srcObject = null;
        }
        streaming = false;
      }

      // Guide circle management
      function resetGuideStroke() {
        const r = guideCircleEl.r.baseVal.value;
        const c = 2 * Math.PI * r;
        guideCircleEl.style.strokeDasharray = `${c} ${c}`;
        guideCircleEl.style.strokeDashoffset = `${c}`;
        guideCircleEl.style.strokeWidth = (CONFIG.BORDER_THICKNESS * 0.5).toString();
        guideCircleEl.style.transition = `stroke ${CONFIG.ANIMATION_SPEED}s ease, stroke-dashoffset ${CONFIG.ANIMATION_SPEED}s ease, stroke-width ${CONFIG.ANIMATION_SPEED}s ease`;
        guideCircleEl.style.stroke = CONFIG.NEUTRAL_COLOR;
      }

      function setProgress(fraction) {
        const r = guideCircleEl.r.baseVal.value;
        const c = 2 * Math.PI * r;
        const offset = c * (1 - clamp(fraction, 0, 1));
        guideCircleEl.style.strokeDashoffset = `${offset.toFixed(4)}`;
      }

      function setGuideState(state) {
        guideCircleEl.style.stroke = state === 'valid' ? CONFIG.VALID_COLOR : state === 'invalid' ? CONFIG.INVALID_COLOR : CONFIG.NEUTRAL_COLOR;
      }

      function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }

      // Voice feedback
      function speak(text) {
        if (text === lastInstruction) return;
        lastInstruction = text;
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'en-US';
        utterance.volume = 1.0;
        utterance.rate = 1.0;
        utterance.pitch = 1.0;
        window.speechSynthesis.speak(utterance);
      }

      // Emotion display
      function updateEmotion(emotionData) {
        if (!emotionData || emotionData.length === 0) {
          emotionEl.textContent = 'Emotion: None';
          return;
        }
        const dominant = emotionData.reduce((max, e) => e.score > max.score ? e : max, emotionData[0]);
        emotionEl.textContent = `Emotion: ${dominant.emotion.charAt(0).toUpperCase() + dominant.emotion.slice(1)}`;
      }

      // Enrollment process
      async function toggleEnroll() {
        if (!running) {
          const id = userIdEl.value.trim();
          if (!id) { alert('Enter a name or ID'); return; }
          retryBtn.style.display = 'none';
          await beginEnroll(id);
        } else {
          stopEnroll();
        }
      }

      async function beginEnroll(id) {
        try {
          enrollBtn.disabled = true;
          enrollBtn.textContent = 'Preparing…';
          await loadHuman();
          await startCamera();
          if (!streaming) {
            enrollBtn.disabled = false;
            enrollBtn.textContent = 'Enroll';
            instructionEl.textContent = 'Camera unavailable';
            retryBtn.style.display = 'block';
            speak('Camera unavailable');
            return;
          }

          running = true;
          currentStepIndex = 0;
          descriptorStore = {};
          for (const s of STEPS) descriptorStore[s.key] = [];
          const totalCaptures = STEPS.length * CONFIG.CAPTURES_PER_STEP;
          let capturedSoFar = 0;

          enrollBtn.textContent = 'Enrolling… (tap to stop)';
          instructionEl.textContent = STEPS[currentStepIndex].label;
          speak(STEPS[currentStepIndex].label);
          setGuideState('neutral');
          resetGuideStroke();
          updateEmotion();

          while (running) {
            await sleep(80);
            let res;
            try {
              res = await human.detect(videoEl, { swapRB: true });
            } catch (err) {
              instructionEl.textContent = 'Detection error';
              speak('Detection error');
              continue;
            }
            const face = res.face && res.face[0];
            if (!face || !face.embedding || !face.rotation) {
              setGuideState('invalid');
              instructionEl.textContent = STEPS[currentStepIndex].label;
              if (instructionEl.textContent !== lastInstruction) {
                speak(STEPS[currentStepIndex].label);
              }
              updateEmotion();
              continue;
            }

            updateEmotion(face.emotion);

            const yaw = face.rotation.angle.yaw;
            const pitch = face.rotation.angle.pitch;
            const pose = estimatePose(yaw, pitch);
            const expected = STEPS[currentStepIndex].key;
            const aligned = pose === expected;

            setGuideState(aligned ? 'valid' : 'invalid');

            if (aligned) {
              if (descriptorStore[expected].length < CONFIG.CAPTURES_PER_STEP) {
                descriptorStore[expected].push(new Float32Array(face.embedding));
                capturedSoFar++;
                setProgress(capturedSoFar / totalCaptures);
                await sleep(400);
              }
              if (descriptorStore[expected].length >= CONFIG.CAPTURES_PER_STEP) {
                currentStepIndex++;
                if (currentStepIndex >= STEPS.length) {
                  running = false;
                  setProgress(1);
                  break;
                } else {
                  instructionEl.textContent = STEPS[currentStepIndex].label;
                  speak(STEPS[currentStepIndex].label);
                  await sleep(600);
                }
              }
            } else {
              instructionEl.textContent = STEPS[currentStepIndex].label;
              if (instructionEl.textContent !== lastInstruction) {
                speak(STEPS[currentStepIndex].label);
              }
            }
          }

          enrollBtn.disabled = false;
          enrollBtn.textContent = 'Enroll';
          const merged = [];
          for (const arr of Object.values(descriptorStore)) {
            for (const e of arr) merged.push(Array.from(e));
          }
          if (merged.length > 0) {
            await saveProfile(id, { embeddings: merged, meta: { name: id, createdAt: new Date().toISOString() } });
            alert(`Enrolled ${id} with ${merged.length} embeddings`);
            speak(`Enrolled ${id} successfully`);
            await refreshProfiles();
          } else {
            alert('No embeddings captured');
            instructionEl.textContent = 'No embeddings captured';
            speak('No embeddings captured');
            retryBtn.style.display = 'block';
          }
          setGuideState('neutral');
          setProgress(0);
          instructionEl.textContent = 'Place your face inside the circle';
          speak('Place your face inside the circle');
          updateEmotion();
          userIdEl.value = id;
        } catch (err) {
          console.error('Enrollment error:', err);
          alert('Enrollment error: ' + (err.message || err));
          instructionEl.textContent = 'Enrollment failed';
          speak('Enrollment failed');
          enrollBtn.disabled = false;
          enrollBtn.textContent = 'Enroll';
          retryBtn.style.display = 'block';
        } finally {
          running = false;
        }
      }

      function stopEnroll() {
        running = false;
        enrollBtn.textContent = 'Enroll';
        setGuideState('neutral');
        setProgress(0);
        instructionEl.textContent = 'Enrollment cancelled';
        speak('Enrollment cancelled');
        updateEmotion();
        retryBtn.style.display = 'block';
      }

      async function onMatch() {
        try {
          await loadHuman();
          const profiles = await listProfiles();
          if (!profiles || profiles.length === 0) {
            alert('No enrolled profiles');
            speak('No enrolled profiles');
            return;
          }
          if (!streaming) await startCamera();

          const res = await human.detect(videoEl, { swapRB: true });
          const face = res.face && res.face[0];
          if (!face || !face.embedding) {
            alert('No face detected');
            speak('No face detected');
            return;
          }

          updateEmotion(face.emotion);

          let best = null, bestDist = Infinity;
          for (const p of profiles) {
            for (const e of p.payload.embeddings) {
              const d = euclideanDistance(e, face.embedding);
              if (d < bestDist) { bestDist = d; best = p; }
            }
          }
          const matched = bestDist < CONFIG.THRESHOLD;
          const matchText = `Match result: Name: ${best ? (best.payload.meta.name || best.id) : '—'}, Distance: ${bestDist.toFixed(4)}, Matched: ${matched ? 'Yes' : 'No'} (threshold ${CONFIG.THRESHOLD})`;
          alert(matchText);
          speak(matchText);
        } catch (err) {
          console.error('Match error:', err);
          alert('Match error: ' + (err.message || err));
          speak('Match error');
        }
      }

      function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

      function euclideanDistance(a, b) {
        let s = 0;
        for (let i = 0; i < a.length; i++) { const d = a[i] - b[i]; s += d * d; }
        return Math.sqrt(s);
      }

      function estimatePose(yaw, pitch) {
        for (const step of STEPS) {
          const yawRange = step.yaw;
          const pitchRange = step.pitch;
          const yawMatch = !yawRange || (yaw >= yawRange[0] && yaw <= yawRange[1]);
          const pitchMatch = !pitchRange || (pitch >= pitchRange[0] && pitch <= pitchRange[1]);
          if (yawMatch && pitchMatch) return step.key;
        }
        return null;
      }

      async function refreshProfiles() {
        try {
          const list = await listProfiles();
          profilesEl.innerHTML = '';
          if (!list || list.length === 0) { profilesEl.textContent = 'No profiles saved.'; return; }
          for (const p of list) {
            const div = document.createElement('div');
            div.className = 'profile-item';
            div.innerHTML = `<div>${p.payload.meta.name || p.id}</div><div style="opacity:0.7;font-size:0.9rem">${new Date(p.updatedAt).toLocaleString()}</div>`;
            profilesEl.appendChild(div);
          }
        } catch (err) {
          console.error('Refresh profiles error:', err);
          profilesEl.textContent = 'Error loading profiles';
          speak('Error loading profiles');
        }
      }

      // Initialize
      initDOM();
      resetGuideStroke();
      instructionEl.textContent = 'Initializing camera…';
      speak('Initializing camera');
      try {
        await startCamera();
        instructionEl.textContent = 'Place your face inside the circle';
        speak('Place your face inside the circle');
        updateEmotion();
      } catch (err) {
        instructionEl.textContent = 'Camera unavailable';
        speak('Camera unavailable');
        console.error('Init error:', err);
      }
      await refreshProfiles();
    } catch (e) {
      console.error('Error initializing face enrollment for area', { id: area.id, error: e.message });
    }
  });
}