import Human from '@vladmandic/human';
import jsQR from 'jsqr';

// Configuration for Human.js
const cfg = {
  debug: true,
  backend: 'webgl',
  modelBasePath: 'https://unpkg.com/@vladmandic/human@3.3.6/models/',
  face: {
    enabled: true,
    detector: { rotation: true, maxDetected: 3 },
    mesh: false,
    attention: { enabled: false },
    description: { enabled: true },
    emotion: { enabled: true }
  },
  body: { enabled: false },
  hand: { enabled: false },
  gesture: { enabled: false },
  filter: { enabled: true }
};

// Singleton Human instance
let humanInstance = null;
async function getHumanInstance() {
  if (!humanInstance) {
    humanInstance = new Human(cfg);
    humanInstance.env.log = 'debug';
    try {
      await humanInstance.load();
      console.log('Human.js models loaded successfully');
    } catch (e) {
      console.error('Failed to load Human.js models:', e.message);
      throw e;
    }
  }
  return humanInstance;
}

// Helper: Get camera stream
async function getCameraStream() {
  try {
    const constraints = {
      video: {
        facingMode: 'user',
        width: { ideal: 640 },
        height: { ideal: 480 },
        frameRate: { ideal: 30 }
      },
      audio: false
    };
    const stream = await navigator.mediaDevices.getUserMedia(constraints);
    console.log('Camera stream acquired:', stream.getVideoTracks()[0].getSettings());
    return stream;
  } catch (error) {
    console.error('Camera access failed:', error.message);
    throw new Error('Failed to access camera: ' + error.message);
  }
}

// Helper: Create video and canvas
function createVideoAndCanvas() {
  const video = document.createElement('video');
  video.autoplay = true;
  video.playsinline = true;
  video.muted = true;
  video.style.display = 'block';
  video.style.maxWidth = '100%';
  video.style.border = '1px solid #ccc';
  const canvas = document.createElement('canvas');
  canvas.style.border = '1px solid #ccc';
  return { video, canvas };
}

// Helper: Capture frame to canvas
function captureFrame(video, canvas) {
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
  return ctx.getImageData(0, 0, canvas.width, canvas.height);
}

// Helper: Get base64 from canvas
function getBase64(canvas) {
  return canvas.toDataURL('image/jpeg', 0.6);
}

// Helper: Cosine similarity
function cosineSimilarity(a, b) {
  if (!a || !b || a.length !== b.length) return 0;
  let dot = 0, normA = 0, normB = 0;
  for (let i = 0; i < a.length; i++) {
    dot += a[i] * b[i];
    normA += a[i] * a[i];
    normB += b[i] * b[i];
  }
  if (normA === 0 || normB === 0) return 0;
  return dot / (Math.sqrt(normA) * Math.sqrt(normB));
}

// Helper: Haversine distance
function haversine(lat1, lon1, lat2, lon2) {
  const R = 6371000; // meters
  const phi1 = lat1 * Math.PI / 180;
  const phi2 = lat2 * Math.PI / 180;
  const dphi = (lat2 - lat1) * Math.PI / 180;
  const dlambda = (lon2 - lon1) * Math.PI / 180;
  const a = Math.sin(dphi / 2) ** 2 + Math.cos(phi1) * Math.cos(phi2) * Math.sin(dlambda / 2) ** 2;
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c;
}

// Helper: Sleep
const sleep = ms => new Promise(r => setTimeout(r, ms));

// Helper: Draw face overlay
function drawOverlay(canvas, faces, matchText = '') {
  const ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ctx.lineWidth = 2;
  ctx.font = '14px ui-monospace, monospace';
  ctx.textBaseline = 'top';
  for (const f of faces) {
    const [x, y, w, h] = f.box || [0, 0, 0, 0];
    ctx.strokeStyle = '#4f8cff';
    ctx.strokeRect(x, y, w, h);
    const emo = f.emotion?.[0];
    const label = `${matchText} ${emo ? `${emo.emotion} ${(emo.score * 100).toFixed(0)}%` : ''}`.trim();
    if (label) {
      ctx.fillStyle = 'rgba(0,0,0,.6)';
      ctx.fillRect(x, Math.max(0, y - 18), ctx.measureText(label).width + 8, 18);
      ctx.fillStyle = '#e6edf3';
      ctx.fillText(label, x + 4, Math.max(0, y - 16));
    }
  }
}

// Helper: Set emotion bar
function setEmotionBar(element, score) {
  const pct = Math.max(0, Math.min(100, Math.round(score * 100)));
  element.style.width = `${pct}%`;
  element.style.background = pct > 70 ? 'var(--ok, #28a745)' : pct > 45 ? 'var(--warn, #ffc107)' : 'var(--bad, #dc3545)';
}

// Init function to scan for presence divs
function initPresence() {
  document.querySelectorAll('[data-presence-enroll-face]').forEach(div => initFaceEnroll(div));
  document.querySelectorAll('[data-presence-enroll-location]').forEach(div => initLocationEnroll(div));
  document.querySelectorAll('[data-presence-match]').forEach(div => initMatchPresence(div));
}

// Init Face Enroll
async function initFaceEnroll(div) {
  const name = div.dataset.name || 'face_enroll';
  const reqEmotion = div.dataset.reqEmotion === 'true';
  let existingData = null;
  if (div.dataset.value) {
    try {
      existingData = JSON.parse(div.dataset.value);
    } catch (e) {
      console.error('Invalid data-value for face enroll:', e.message);
      const status = document.createElement('div');
      status.className = 'status';
      status.textContent = 'Error: Invalid data-value format';
      div.appendChild(status);
      return;
    }
  }

  // Create UI elements
  const container = document.createElement('div');
  container.className = 'face-enroll-container';
  div.appendChild(container);

  const video = document.createElement('video');
  video.style.display = 'block';
  video.style.maxWidth = '100%';
  video.style.border = '1px solid #ccc';
  container.appendChild(video);

  const canvas = document.createElement('canvas');
  canvas.style.border = '1px solid #ccc';
  container.appendChild(canvas);

  const status = document.createElement('div');
  status.className = 'status';
  status.textContent = existingData ? 'Ready to update face data' : 'Hold “Scan” to enroll face';
  container.appendChild(status);

  const emoBar = document.createElement('div');
  emoBar.className = 'emotion-bar';
  emoBar.style.height = '10px';
  emoBar.style.background = '#f0f0f0';
  emoBar.style.borderRadius = '5px';
  emoBar.style.overflow = 'hidden';
  container.appendChild(emoBar);

  const emoFill = document.createElement('div');
  emoFill.className = 'emotion-fill';
  emoFill.style.height = '100%';
  emoFill.style.width = '0%';
  emoBar.appendChild(emoFill);

  const scanBtn = document.createElement('button');
  scanBtn.textContent = existingData ? 'Update Face Enrollment' : 'Scan Face (Hold)';
  container.appendChild(scanBtn);

  const spinner = document.createElement('div');
  spinner.className = 'spinner';
  spinner.style.display = 'none';
  spinner.textContent = 'Processing...';
  container.appendChild(spinner);

  let loopTimer = null;
  let holdTimer = null;
  let isHolding = false;
  async function startVideoLoop() {
    try {
      const stream = await getCameraStream();
      video.srcObject = stream;
      await video.play();
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;

      const loop = async () => {
        const imageData = captureFrame(video, canvas);
        const result = await humanInstance.detect(canvas);
        const faces = result.face || [];
        drawOverlay(canvas, faces);
        if (faces.length) {
          setEmotionBar(emoFill, faces[0].emotion?.[0]?.score || 0);
          status.textContent = `Detecting: ${faces[0].emotion?.[0]?.emotion || 'neutral'} (${(faces[0].emotion?.[0]?.score * 100 || 0).toFixed(0)}%)`;
        } else {
          setEmotionBar(emoFill, 0);
          status.textContent = 'No face detected';
        }
        loopTimer = setTimeout(loop, 200);
      };
      loop();
    } catch (error) {
      status.textContent = `Error: ${error.message}`;
      setEmotionBar(emoFill, 0);
      spinner.style.display = 'none';
      scanBtn.disabled = false;
    }
  }

  scanBtn.addEventListener('mousedown', async () => {
    try {
      isHolding = true;
      spinner.style.display = 'block';
      scanBtn.disabled = true;
      if (!humanInstance) humanInstance = await getHumanInstance();
      if (!video.srcObject) await startVideoLoop();

      const directions = ['straight', 'left', 'right'];
      const embeddings = existingData ? [existingData.embedding] : [];
      const captures = existingData ? [existingData.capture] : [];
      const ages = existingData ? [existingData.age] : [];
      const genders = existingData ? [existingData.gender] : [];
      const emotions = existingData ? [existingData.emotion] : [];
      let captured = existingData ? 3 : 0;

      for (let i = captured; i < directions.length && isHolding; i++) {
        const direction = directions[i];
        status.textContent = `Hold button to capture ${direction} pose (2 seconds)`;
        setEmotionBar(emoFill, 0);

        let detected = false;
        let holdStart = performance.now();
        while (isHolding && !detected && performance.now() - holdStart < 2000) {
          await sleep(100);
          const imageData = captureFrame(video, canvas);
          const result = await humanInstance.detect(canvas);
          const faces = result.face || [];
          if (faces.length && faces[0].score > 0.8 && faces[0].embedding?.length >= 128) {
            const face = faces[0];
            embeddings.push(face.embedding);
            ages.push(face.age || 0);
            genders.push(face.gender || 'unknown');
            emotions.push(face.emotion?.[0]?.emotion || 'neutral');
            captures.push(getBase64(canvas));
            detected = true;
            captured++;
            setEmotionBar(emoFill, face.emotion?.[0]?.score || 0);
            status.textContent = `Captured ${direction} | Accuracy: ${(face.score * 100).toFixed(2)}% | Poses: ${captured}/3`;
            drawOverlay(canvas, faces, `Captured ${direction}`);
          }
        }
        if (!detected) {
          status.textContent = `Failed to detect ${direction} pose`;
          setEmotionBar(emoFill, 0);
          throw new Error(`Failed to detect clean face for ${direction}`);
        }
        await sleep(500); // Brief pause before next pose
      }

      if (!isHolding) {
        status.textContent = 'Enrollment canceled: Button released';
        setEmotionBar(emoFill, 0);
        return;
      }

      const avgEmbedding = embeddings[0].map((_, i) => 
        embeddings.reduce((sum, emb) => sum + (emb[i] || 0), 0) / embeddings.length
      );
      const avgAge = Math.round(ages.reduce((sum, a) => sum + (a || 0), 0) / ages.length);
      const avgGender = genders[0] || 'unknown';
      const avgEmotion = emotions[0] || 'neutral';
      const captureBase64 = captures[1] || captures[0];

      if (video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
        clearTimeout(loopTimer);
      }

      let hiddenInput = div.querySelector(`input[name="${name}"]`);
      if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = name;
        div.appendChild(hiddenInput);
      }
      hiddenInput.value = JSON.stringify({
        embedding: avgEmbedding,
        capture: captureBase64,
        age: avgAge,
        gender: avgGender,
        emotion: avgEmotion,
        req_emotion: reqEmotion ? 1 : 0,
      });

      status.textContent = 'Enrollment Complete | Progress: 100%';
      setEmotionBar(emoFill, 1);
    } catch (error) {
      status.textContent = `Error: ${error.message}`;
      setEmotionBar(emoFill, 0);
    } finally {
      spinner.style.display = 'none';
      scanBtn.disabled = false;
    }
  });

  scanBtn.addEventListener('mouseup', () => {
    isHolding = false;
    clearTimeout(holdTimer);
  });
  scanBtn.addEventListener('mouseleave', () => {
    isHolding = false;
    clearTimeout(holdTimer);
  });
}

// Init Location Enroll
function initLocationEnroll(div) {
  const name = div.dataset.name || 'location_enroll';
  let existingData = null;
  if (div.dataset.value) {
    try {
      existingData = JSON.parse(div.dataset.value);
    } catch (e) {
      console.error('Invalid data-value for location enroll:', e.message);
      const status = document.createElement('div');
      status.className = 'status';
      status.textContent = 'Error: Invalid data-value format';
      div.appendChild(status);
      return;
    }
  }

  if (typeof google === 'undefined') {
    console.error('Google Maps API not loaded');
    const status = document.createElement('div');
    status.className = 'status';
    status.textContent = 'Error: Google Maps API not loaded';
    div.appendChild(status);
    return;
  }

  const container = document.createElement('div');
  container.className = 'location-enroll-container';
  div.appendChild(container);

  const mapContainer = document.createElement('div');
  mapContainer.className = 'map-container';
  container.appendChild(mapContainer);

  const status = document.createElement('div');
  status.className = 'status';
  status.textContent = existingData ? 'Click map to update location' : 'Click map to set location';
  container.appendChild(status);

  const radiusInput = document.createElement('input');
  radiusInput.type = 'number';
  radiusInput.min = 1;
  radiusInput.value = existingData ? existingData.radius : 100;
  radiusInput.placeholder = 'Radius (meters)';
  container.appendChild(radiusInput);

  const spinner = document.createElement('div');
  spinner.className = 'spinner';
  spinner.style.display = 'none';
  spinner.textContent = 'Loading map...';
  container.appendChild(spinner);

  const map = new google.maps.Map(mapContainer, {
    center: existingData ? { lat: existingData.latitude, lng: existingData.longitude } : { lat: 0, lng: 0 },
    zoom: existingData ? 15 : 2,
    gestureHandling: 'greedy',
  });

  let marker = null;
  let circle = null;
  if (existingData) {
    marker = new google.maps.Marker({
      position: { lat: existingData.latitude, lng: existingData.longitude },
      map,
    });
    circle = new google.maps.Circle({
      strokeColor: '#FF0000',
      strokeOpacity: 0.8,
      strokeWeight: 2,
      fillColor: '#FF0000',
      fillOpacity: 0.35,
      map,
      center: { lat: existingData.latitude, lng: existingData.longitude },
      radius: existingData.radius,
    });
  }

  map.addListener('click', event => {
    spinner.style.display = 'block';
    if (marker) marker.setMap(null);
    if (circle) circle.setMap(null);
    marker = new google.maps.Marker({
      position: event.latLng,
      map,
    });
    circle = new google.maps.Circle({
      strokeColor: '#FF0000',
      strokeOpacity: 0.8,
      strokeWeight: 2,
      fillColor: '#FF0000',
      fillOpacity: 0.35,
      map,
      center: event.latLng,
      radius: parseInt(radiusInput.value, 10) || 100,
    });
    updateHidden();
    status.textContent = 'Location updated';
    spinner.style.display = 'none';
  });

  let debounceTimer;
  radiusInput.addEventListener('input', e => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      const radius = parseInt(e.target.value, 10);
      if (circle && !isNaN(radius) && radius > 0) {
        spinner.style.display = 'block';
        circle.setRadius(radius);
        updateHidden();
        status.textContent = 'Radius updated';
        spinner.style.display = 'none';
      }
    }, 200);
  });

  function updateHidden() {
    if (!marker) return;
    const lat = marker.getPosition().lat();
    const lng = marker.getPosition().lng();
    const radius = circle ? circle.getRadius() : 100;

    let hiddenInput = div.querySelector(`input[name="${name}"]`);
    if (!hiddenInput) {
      hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = name;
      div.appendChild(hiddenInput);
    }
    hiddenInput.value = JSON.stringify({ latitude: lat, longitude: lng, radius });
  }
}

// Init Match Presence
async function initMatchPresence(div) {
  const matchType = div.dataset.presenceMatch;
  const name = div.dataset.name || 'presence_match';
  let faceValue = null;
  let geoValue = null;
  try {
    if (div.dataset.faceValue) faceValue = JSON.parse(div.dataset.faceValue);
    if (div.dataset.geoValue) geoValue = JSON.parse(div.dataset.geoValue);
  } catch (e) {
    console.error('Invalid data-value for match presence:', e.message);
    const status = document.createElement('div');
    status.className = 'status';
    status.textContent = 'Error: Invalid data-value format';
    div.appendChild(status);
    return;
  }

  const container = document.createElement('div');
  container.className = 'match-presence-container';
  div.appendChild(container);

  const video = document.createElement('video');
  video.style.display = 'block';
  video.style.maxWidth = '100%';
  video.style.border = '1px solid #ccc';
  container.appendChild(video);

  const canvas = document.createElement('canvas');
  canvas.style.border = '1px solid #ccc';
  container.appendChild(canvas);

  const status = document.createElement('div');
  status.className = 'status';
  status.textContent = 'Click “Start” to match presence';
  container.appendChild(status);

  const emoBar = document.createElement('div');
  emoBar.className = 'emotion-bar';
  emoBar.style.height = '10px';
  emoBar.style.background = '#f0f0f0';
  emoBar.style.borderRadius = '5px';
  emoBar.style.overflow = 'hidden';
  container.appendChild(emoBar);

  const emoFill = document.createElement('div');
  emoFill.className = 'emotion-fill';
  emoFill.style.height = '100%';
  emoFill.style.width = '0%';
  emoBar.appendChild(emoFill);

  const startBtn = document.createElement('button');
  startBtn.textContent = 'Start Presence Match';
  container.appendChild(startBtn);

  const spinner = document.createElement('div');
  spinner.className = 'spinner';
  spinner.style.display = 'none';
  spinner.textContent = 'Processing...';
  container.appendChild(spinner);

  let loopTimer = null;
  async function startVideoLoop() {
    try {
      const stream = await getCameraStream();
      video.srcObject = stream;
      await video.play();
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;

      const loop = async () => {
        const imageData = captureFrame(video, canvas);
        const result = await humanInstance.detect(canvas);
        const faces = result.face || [];
        drawOverlay(canvas, faces);
        if (faces.length) {
          setEmotionBar(emoFill, faces[0].emotion?.[0]?.score || 0);
          status.textContent = `Detecting: ${faces[0].emotion?.[0]?.emotion || 'neutral'} (${(faces[0].emotion?.[0]?.score * 100 || 0).toFixed(0)}%)`;
        } else {
          setEmotionBar(emoFill, 0);
          status.textContent = 'No face detected';
        }
        loopTimer = setTimeout(loop, 200);
      };
      loop();
    } catch (error) {
      status.textContent = `Error: ${error.message}`;
      setEmotionBar(emoFill, 0);
      spinner.style.display = 'none';
      startBtn.disabled = false;
    }
  }

  startBtn.addEventListener('click', async () => {
    try {
      spinner.style.display = 'block';
      startBtn.disabled = true;
      if (!humanInstance) humanInstance = await getHumanInstance();
      if (!video.srcObject) await startVideoLoop();

      const result = { method: matchType, valid: false, details: {} };

      if (matchType.includes('geo')) {
        if (!geoValue) throw new Error('No geo value provided');
        const position = await new Promise((res, rej) => 
          navigator.geolocation.getCurrentPosition(res, rej, { enableHighAccuracy: true, timeout: 5000 })
        );
        const currentLat = position.coords.latitude;
        const currentLng = position.coords.longitude;
        const dist = haversine(geoValue.latitude, geoValue.longitude, currentLat, currentLng);
        result.details.geo = { distance: dist, coordinates: `${currentLat},${currentLng}` };
        result.details.geo.valid = dist <= geoValue.radius;
        status.textContent = `Geo: ${result.details.geo.valid ? 'Within radius' : 'Outside radius'} (${dist.toFixed(0)}m)`;
      }

      if (matchType.includes('face')) {
        if (!faceValue) throw new Error('No face value provided');
        const imageData = captureFrame(video, canvas);
        const resultDetect = await humanInstance.detect(canvas);
        const faces = resultDetect.face || [];
        if (!faces.length || faces[0].score < 0.8 || !faces[0].embedding || faces[0].embedding.length < 128) {
          throw new Error('No clean face detected or invalid embedding');
        }
        const face = faces[0];
        const similarity = cosineSimilarity(faceValue.embedding, face.embedding);
        const emotionMatch = faceValue.req_emotion ? faceValue.emotion === face.emotion?.[0]?.emotion : true;
        result.details.face = { 
          similarity, 
          emotion: face.emotion?.[0]?.emotion || 'neutral', 
          selfi: getBase64(canvas) 
        };
        result.details.face.valid = similarity > 0.8 && emotionMatch;
        setEmotionBar(emoFill, face.emotion?.[0]?.score || 0);
        drawOverlay(canvas, faces, result.details.face.valid ? 'Match' : 'No match');
        status.textContent = `Face: ${result.details.face.valid ? 'Matched' : 'Not matched'} (Similarity: ${(similarity * 100).toFixed(0)}%)`;
      }

      switch (matchType) {
        case 'geo':
          result.valid = result.details.geo.valid;
          break;
        case 'face':
          result.valid = result.details.face.valid;
          break;
        case 'face-geo':
          result.valid = result.details.geo.valid && result.details.face.valid;
          break;
      }

      if (video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
        clearTimeout(loopTimer);
      }

      let hiddenInput = div.querySelector(`input[name="${name}"]`);
      if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = name;
        div.appendChild(hiddenInput);
      }
      hiddenInput.value = JSON.stringify(result);

      status.textContent = result.valid ? 'Match Successful' : 'Match Failed';
      setEmotionBar(emoFill, result.valid ? 1 : 0);
    } catch (error) {
      status.textContent = `Error: ${error.message}`;
      setEmotionBar(emoFill, 0);
    } finally {
      spinner.style.display = 'none';
      startBtn.disabled = false;
    }
  });
}

// Expose initPresence for Google Maps callback
window.initPresence = initPresence;

export default { initPresence };