import Human from '@vladmandic/human';

// Utility Modules
const mapUtils = {
  /**
   * Loads Google Maps API with error handling and polling for service availability.
   * @param {string} apiKey - Google Maps API key
   * @returns {Promise<void>}
   */
  async loadGoogleMaps(apiKey) {
    if (window.google?.maps?.Map && window.google?.maps?.Geocoder) {
      console.log('Google Maps API already loaded');
      return;
    }

    const existingScript = document.querySelector('#google-maps-script');
    if (existingScript) {
      return new Promise((resolve, reject) => {
        existingScript.addEventListener('load', () => {
          if (window.google?.maps?.Map && window.google?.maps?.Geocoder) {
            console.log('Google Maps API loaded via existing script');
            resolve();
          } else {
            reject(new Error('Google Maps API failed to initialize'));
          }
        }, { once: true });
        existingScript.addEventListener('error', () => reject(new Error('Failed to load existing Google Maps script')), { once: true });
      });
    }

    const script = document.createElement('script');
    script.id = 'google-maps-script';
    script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=marker,places&loading=async`;
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);

    return new Promise((resolve, reject) => {
      let pollAttempts = 0;
      const maxPollAttempts = 50;
      const pollInterval = 100;

      script.addEventListener('load', () => {
        const checkServices = setInterval(() => {
          if (window.google?.maps?.Map && window.google?.maps?.Geocoder) {
            clearInterval(checkServices);
            console.log('Google Maps API loaded successfully');
            resolve();
          } else if (pollAttempts++ >= maxPollAttempts) {
            clearInterval(checkServices);
            reject(new Error('Timeout waiting for Google Maps services'));
          }
        }, pollInterval);
      }, { once: true });

      script.addEventListener('error', () => reject(new Error('Failed to load Google Maps API script')), { once: true });
    });
  },

  /**
   * Retrieves current geolocation.
   * @returns {Promise<{lat: number, lng: number}>}
   */
  async getCurrentLocation() {
    if (!navigator.geolocation) {
      throw new Error('Geolocation not supported');
    }
    return new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(
        (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
        (err) => reject(new Error(`Geolocation error: ${err.message}`))
      );
    });
  },

  /**
   * Gets address from coordinates using Google Maps Geocoder.
   * @param {number} lat - Latitude
   * @param {number} lng - Longitude
   * @param {string} apiKey - Google Maps API key
   * @returns {Promise<string>}
   */
  async getAddress(lat, lng, apiKey) {
    const maxAttempts = 5;
    for (let attempt = 1; attempt <= maxAttempts; attempt++) {
      try {
        await this.loadGoogleMaps(apiKey);
        const geocoder = new google.maps.Geocoder();
        return await new Promise((resolve) => {
          geocoder.geocode({ location: { lat, lng } }, (results, status) => {
            if (status === 'OK' && results?.[0]) {
              console.log(`Geocoder success for lat: ${lat}, lng: ${lng}, address: ${results[0].formatted_address}`);
              resolve(results[0].formatted_address);
            } else {
              console.warn(`Geocoder failed for lat: ${lat}, lng: ${lng}, status: ${status}`);
              resolve('Address not available');
            }
          });
        });
      } catch (err) {
        console.warn(`Address fetch attempt ${attempt} failed: ${err.message}`);
        if (attempt === maxAttempts) {
          console.error('Failed to get address after retries:', err);
          return 'Address not available';
        }
        await new Promise(resolve => setTimeout(resolve, 2000 * attempt));
      }
    }
    return 'Address not available';
  }
};

const uiUtils = {
  /**
   * Creates a hidden input element.
   * @param {string} name - Input name
   * @param {string} value - Input value
   * @param {HTMLElement} container - Parent element
   * @returns {HTMLInputElement}
   */
  createHiddenInput(name, value, container) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    container.insertAdjacentElement('afterend', input);
    console.log(`Created hidden input: name=${name}, value=${value}`);
    return input;
  },

  /**
   * Updates a hidden input's value.
   * @param {string} name - Input name
   * @param {string} value - New value
   */
  updateHiddenInput(name, value) {
    const input = document.querySelector(`input[name="${name}"]`);
    if (input) {
      input.value = value;
      console.log(`Updated hidden input: name=${name}, value=${value}`);
    } else {
      console.warn(`Hidden input with name=${name} not found`);
    }
  },

  /**
   * Displays a loading overlay.
   * @param {HTMLElement} container - Parent element
   * @param {string} text - Loading text
   * @returns {HTMLElement}
   */
  showLoading(container, text) {
    const div = document.createElement('div');
    div.className = 'smart-map-loading';
    div.textContent = text;
    div.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #fff; background: rgba(0,0,0,0.7); padding: 10px 20px; border-radius: 5px;';
    container.appendChild(div);
    return div;
  },

  /**
   * Displays an error message.
   * @param {HTMLElement} container - Parent element
   * @param {string} message - Error message
   */
  showError(container, message) {
    const div = document.createElement('div');
    div.className = 'smart-map-error';
    div.textContent = message;
    div.style.cssText = 'color: #dc3545; background: rgba(255,255,255,0.9); padding: 10px; border-radius: 5px; margin-top: 10px;';
    container.insertAdjacentElement('afterend', div);
  }
};

const mathUtils = {
  /**
   * Clamps a value between min and max.
   * @param {number} value - Value to clamp
   * @param {number} min - Minimum value
   * @param {number} max - Maximum value
   * @returns {number}
   */
  clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
  },

  /**
   * Calculates cosine similarity between two vectors.
   * @param {number[]} vecA - First vector
   * @param {number[]} vecB - Second vector
   * @returns {number}
   */
  cosineSimilarity(vecA, vecB) {
    if (!vecA || !vecB || vecA.length !== vecB.length) return 0;
    let dotProduct = 0, normA = 0, normB = 0;
    for (let i = 0; i < vecA.length; i++) {
      dotProduct += vecA[i] * vecB[i];
      normA += vecA[i] * vecA[i];
      normB += vecB[i] * vecB[i];
    }
    normA = Math.sqrt(normA);
    normB = Math.sqrt(normB);
    return normA && normB ? dotProduct / (normA * normB) : 0;
  },

  /**
   * Calculates distance between two coordinates in meters.
   * @param {number} lat1 - Latitude 1
   * @param {number} lon1 - Longitude 1
   * @param {number} lat2 - Latitude 2
   * @param {number} lon2 - Longitude 2
   * @returns {number}
   */
  calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // Earth's radius in meters
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  },

  /**
   * Debounces a function.
   * @param {Function} func - Function to debounce
   * @param {number} wait - Wait time in ms
   * @returns {Function}
   */
  debounce(func, wait) {
    let timeout;
    return (...args) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => func(...args), wait);
    };
  },

  /**
   * Pauses execution for a specified time.
   * @param {number} ms - Time in milliseconds
   * @returns {Promise<void>}
   */
  sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
};

const faceUtils = {
  /**
   * Initializes Human.js for face detection.
   * @param {string} modelBase - Base path for models
   * @returns {Promise<Human>}
   */
  async initializeHuman(modelBase) {
    const human = new Human({
      modelBasePath: modelBase,
      backend: 'webgl',
      debug: false,
      face: {
        enabled: true,
        detector: { rotation: true, maxDetected: 1, minConfidence: 0.5 },
        mesh: { enabled: true },
        description: { enabled: true },
        emotion: { enabled: true },
        age: { enabled: true },
        gender: { enabled: true }
      }
    });
    await human.load();
    await human.warmup();
    return human;
  },

  /**
   * Lists available cameras.
   * @returns {Promise<MediaDeviceInfo[]>}
   */
  async getCameras() {
    try {
      const devices = await navigator.mediaDevices.enumerateDevices();
      return devices.filter(device => device.kind === 'videoinput');
    } catch (err) {
      console.error('Failed to list cameras:', err);
      return [];
    }
  }
};

/**
 * Initializes an interactive map with draggable marker and radius circle.
 */
async function smartMap() {
  const mapElements = document.querySelectorAll('[data-smart-map]');
  if (!mapElements.length) {
    console.warn('No elements with [data-smart-map] found.');
    return;
  }

  const mapsApiKey = mapElements[0].getAttribute('data-maps-api');
  if (!mapsApiKey) {
    mapElements.forEach(el => {
      uiUtils.showError(el, 'Google Maps API key is missing.');
      const inputName = el.getAttribute('data-name') || 'map_coordinates';
      const fallbackLatLng = { lat: 37.7749, lng: -122.4194 };
      uiUtils.createHiddenInput(inputName, `${fallbackLatLng.lat},${fallbackLatLng.lng}`, el);
      uiUtils.createHiddenInput('current_coordinates', `${fallbackLatLng.lat},${fallbackLatLng.lng}`, el);
      uiUtils.createHiddenInput('current_address', 'Address not available', el);
      uiUtils.createHiddenInput('pin_address', 'Address not available', el);
    });
    return;
  }

  mapElements.forEach(el => el.removeAttribute('data-maps-api')); // Security

  for (const el of mapElements) {
    const loadingEl = uiUtils.showLoading(el, 'Loading map...');
    try {
      const size = el.getAttribute('data-size') || '100%*400px';
      const [width, height] = size.split('*').map(s => s.trim());
      el.style.width = width;
      el.style.height = height;
      const inputName = el.getAttribute('data-name') || 'map_coordinates';
      const radius = parseFloat(el.getAttribute('data-radius')) || 100;
      const radiusCaptureSelector = el.getAttribute('data-radius-capture');
      const coordinatesAttr = el.getAttribute('data-coordinates');

      let initialLatLng, userLocation;
      try {
        userLocation = await mapUtils.getCurrentLocation();
        initialLatLng = userLocation;
      } catch (err) {
        console.warn('Geolocation failed, using fallback location:', err.message);
        userLocation = { lat: 37.7749, lng: -122.4194 };
        initialLatLng = userLocation;
      }

      if (coordinatesAttr) {
        const [lat, lng] = coordinatesAttr.split(',').map(Number);
        if (!isNaN(lat) && !isNaN(lng)) {
          initialLatLng = { lat, lng };
        }
      }

      const [currentAddress, pinAddress] = await Promise.all([
        mapUtils.getAddress(userLocation.lat, userLocation.lng, mapsApiKey),
        mapUtils.getAddress(initialLatLng.lat, initialLatLng.lng, mapsApiKey)
      ]);

      uiUtils.createHiddenInput(inputName, `${initialLatLng.lat},${initialLatLng.lng}`, el);
      uiUtils.createHiddenInput('current_coordinates', `${userLocation.lat},${userLocation.lng}`, el);
      uiUtils.createHiddenInput('current_address', currentAddress, el);
      uiUtils.createHiddenInput('pin_address', pinAddress, el);

      await mapUtils.loadGoogleMaps(mapsApiKey);
      const map = new google.maps.Map(el, {
        center: initialLatLng,
        zoom: 14,
        mapId: "SMART_MAP_ID"
      });

      const markerElement = document.createElement("div");
      markerElement.innerHTML = `
        <svg width="26" height="40" viewBox="0 0 26 40" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M13 0C5.82 0 0 5.82 0 13C0 18.63 3.11 23.44 8.08 26.39L13 40L17.92 26.39C22.89 23.44 26 18.63 26 13C26 5.82 20.18 0 13 0ZM13 18C10.24 18 8 15.76 8 13C8 10.24 10.24 8 13 8C15.76 8 18 10.24 18 13C18 15.76 15.76 18 13 18Z" fill="#00b4af"/>
          <circle cx="13" cy="13" r="5" fill="white"/>
        </svg>
      `;

      const marker = new google.maps.marker.AdvancedMarkerElement({
        position: initialLatLng,
        map,
        content: markerElement,
        gmpDraggable: true
      });

      let circle = new google.maps.Circle({
        map,
        radius,
        fillColor: '#00b4af',
        fillOpacity: 0.2,
        strokeColor: '#00b4af',
        strokeWeight: 2,
        center: initialLatLng
      });

      const infoWindow = new google.maps.InfoWindow({
        content: `<div style="font-family: Arial, sans-serif; max-width: 200px;">
                    <p><strong>Coordinates:</strong> ${initialLatLng.lat.toFixed(6)}, ${initialLatLng.lng.toFixed(6)}</p>
                    <p><strong>Address:</strong> ${pinAddress}</p>
                  </div>`,
        position: initialLatLng
      });

      marker.addListener('click', () => infoWindow.open(map, marker));
      marker.addListener('dragend', async () => {
        const pos = marker.position;
        if (pos && typeof pos.lat === 'number' && typeof pos.lng === 'number') {
          uiUtils.updateHiddenInput(inputName, `${pos.lat},${pos.lng}`);
          circle.setCenter(pos);
          const newPinAddress = await mapUtils.getAddress(pos.lat, pos.lng, mapsApiKey);
          uiUtils.updateHiddenInput('pin_address', newPinAddress);
          infoWindow.setContent(`<div style="font-family: Arial, sans-serif; max-width: 200px;">
                                  <p><strong>Coordinates:</strong> ${pos.lat.toFixed(6)}, ${pos.lng.toFixed(6)}</p>
                                  <p><strong>Address:</strong> ${newPinAddress}</p>
                                </div>`);
          infoWindow.setPosition(pos);
          infoWindow.open(map, marker);
          console.log(`Marker dragged to: ${pos.lat}, ${pos.lng}, Address: ${newPinAddress}`);
        }
      });

      if (radiusCaptureSelector) {
        const maxAttachAttempts = 5;
        let attachAttempts = 0;
        const tryAttachRadiusListener = async () => {
          const radiusInput = document.querySelector(radiusCaptureSelector);
          if (radiusInput) {
            console.log(`Radius input found: ${radiusInput.id || radiusInput.name || radiusCaptureSelector}`);
            radiusInput.value = radius;
            const updateRadius = mathUtils.debounce(() => {
              const newRadius = parseFloat(radiusInput.value);
              if (!isNaN(newRadius) && newRadius >= 0) {
                circle.setRadius(newRadius);
                console.log(`Updated circle radius to ${newRadius}m`);
                circle.setMap(null);
                circle.setMap(map);
              } else {
                console.warn(`Invalid radius value: ${radiusInput.value}`);
              }
            }, 300);
            radiusInput.addEventListener('input', updateRadius);
            radiusInput.addEventListener('change', updateRadius);
            return true;
          }
          return false;
        };

        while (attachAttempts < maxAttachAttempts && !await tryAttachRadiusListener()) {
          attachAttempts++;
          await mathUtils.sleep(1000 * attachAttempts);
        }
        if (attachAttempts >= maxAttachAttempts) {
          console.error(`Radius input with selector ${radiusCaptureSelector} not found after retries`);
        }
      }

      loadingEl.remove();
    } catch (err) {
      loadingEl.remove();
      uiUtils.showError(el, `Error initializing map: ${err.message}`);
      console.error('Map initialization error:', err);
      const inputName = el.getAttribute('data-name') || 'map_coordinates';
      const fallbackLatLng = { lat: 37.7749, lng: -122.4194 };
      uiUtils.createHiddenInput(inputName, `${fallbackLatLng.lat},${fallbackLatLng.lng}`, el);
      uiUtils.createHiddenInput('current_coordinates', `${fallbackLatLng.lat},${fallbackLatLng.lng}`, el);
      uiUtils.createHiddenInput('current_address', 'Address not available', el);
      uiUtils.createHiddenInput('pin_address', 'Address not available', el);
    }
  }
}

/**
 * Handles face enrollment with camera input and face detection.
 */
async function smartFace() {
  const enrollDiv = document.querySelector('[data-smart-face-enroll]');
  if (!enrollDiv) return;

  const config = {
    BORDER_THICKNESS: 8,
    VALID_COLOR: '#28a745',
    INVALID_COLOR: '#dc3545',
    NEUTRAL_COLOR: 'rgba(255,255,255,0.1)',
    ANIMATION_SPEED: 0.4,
    MODEL_BASE: `${window.location.origin}/models`,
    VIDEO_CONSTRAINTS: { audio: false, video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' } },
    IDLE_TIMEOUT: 3000,
    MOVEMENT_THRESHOLD: 10,
    ANGLE_THRESHOLD: 0.1,
    PLACEHOLDER_IMAGE: 'data:image/svg+xml,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20width=%2224%22%20height=%2224%22%20viewBox=%220%200%2024%2024%22%20fill=%22%23f3f3f3%22%20class=%22icon%20icon-tabler%20icons-tabler-filled%20icon-tabler-user%22%3E%3Cpath%20stroke=%22none%22%20d=%22M0%200h24v24H0z%22%20fill=%22none%22/%3E%3Cpath%20d=%22M12%202a5%205%200%201%201%20-5%205l.005%20-.217a5%205%200%200%201%204.995%20-4.783z%22/%3E%3Cpath%20d=%22M14%2014a5%205%200%200%201%205%205v1a2%202%200%200%201%20-2%202h-10a2%202%200%200%201%20-2%20-2v-1a5%205%200%200%201%205%20-5h4z%22/%3E%3C/svg%3E'
  };

  const EMOJI_MAP = {
    angry: '😣',
    disgusted: '🤢',
    fear: '😨',
    happy: '😄',
    neutral: '😐',
    sad: '😔',
    surprised: '😲'
  };

  const STEPS = [
    { key: 'straight', label: 'Look straight ahead', yaw: [-0.2, 0.2], pitch: [-0.2, 0.2] },
    { key: 'left', label: 'Turn your head left', yaw: [-Infinity, -0.2] },
    { key: 'right', label: 'Turn your head right', yaw: [0.2, Infinity] },
    { key: 'up', label: 'Look up slightly', pitch: [-Infinity, -0.1] },
    { key: 'down', label: 'Look down slightly', pitch: [0.1, Infinity] }
  ];

  const dataName = enrollDiv.dataset.name || 'user_face';
  const targetControls = enrollDiv.dataset.targetControls || '.controls_area';
  const targetInstruction = enrollDiv.dataset.targetInstruction || '.instruction_area';
  const targetEmotion = enrollDiv.dataset.targetEmotion || '.captured_emotion';
  const targetGender = enrollDiv.dataset.targetGender || '.captured_gender';
  const targetAge = enrollDiv.dataset.targetAge || '.captured_age';

  const elements = {
    controlsArea: document.querySelector(targetControls),
    instructionArea: document.querySelector(targetInstruction),
    emotionDisplay: document.querySelector(targetEmotion),
    genderDisplay: document.querySelector(targetGender),
    ageDisplay: document.querySelector(targetAge)
  };

  if (Object.values(elements).some(el => !el)) return;

  let human = null;
  let videoEl, imageEl, guideCircleEl, enrollBtn, cameraSelect;
  let descriptorStore = new Map(STEPS.map(step => [step.key, []]));
  let running = false;
  let currentStepIndex = 0;
  let videoStream = null;
  let streaming = false;
  let snapshotBlob = null;
  let dominantEmotion = 'unknown';
  let detectedGender = 'unknown';
  let detectedAge = 0;
  let cameras = [];
  let currentCameraIndex = 0;
  let lastFaceDetectedTime = null;
  let lastFacePosition = null;
  let lastFaceRotation = null;
  let idleTimeoutId = null;
  let isUpdateMode = false;

  const existingData = {
    capture: enrollDiv.dataset.captureDatauriValue || '',
    embedding: enrollDiv.dataset.embeddingValue || '',
    emotion: enrollDiv.dataset.emotionValue || '',
    gender: enrollDiv.dataset.genderValue || '',
    age: parseInt(enrollDiv.dataset.ageValue || '0')
  };

  // Setup UI
  const cameraArea = document.createElement('div');
  cameraArea.className = 'camera-area position-relative';
  enrollDiv.appendChild(cameraArea);

  imageEl = document.createElement('img');
  imageEl.id = 'placeholder';
  imageEl.src = config.PLACEHOLDER_IMAGE;
  imageEl.style.cssText = 'width: 100%; height: 100%; object-fit: cover; display: block;';
  cameraArea.appendChild(imageEl);

  videoEl = document.createElement('video');
  videoEl.id = 'video';
  videoEl.autoplay = true;
  videoEl.playsInline = true;
  videoEl.muted = true;
  videoEl.setAttribute('aria-hidden', 'true');
  videoEl.style.cssText = 'width: 100%; height: 100%; object-fit: cover; display: block;';
  cameraArea.appendChild(videoEl);

  const guideWrap = document.createElement('div');
  guideWrap.className = 'guide-wrap position-absolute top-0 start-0 w-100 h-100';
  guideWrap.id = 'guideWrap';
  guideWrap.setAttribute('aria-hidden', 'true');
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svg.id = 'guideSVG';
  svg.setAttribute('viewBox', '0 0 100 100');
  svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
  svg.style.cssText = 'width: 100%; height: 100%;';
  guideCircleEl = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
  guideCircleEl.id = 'guideCircle';
  guideCircleEl.setAttribute('cx', '50');
  guideCircleEl.setAttribute('cy', '50');
  guideCircleEl.setAttribute('r', '47.5');
  guideCircleEl.setAttribute('fill', 'none');
  svg.appendChild(guideCircleEl);
  guideWrap.appendChild(svg);
  cameraArea.appendChild(guideWrap);

  elements.controlsArea.innerHTML = '';
  const controls = document.createElement('div');
  controls.className = 'controls d-flex justify-content-center gap-3';
  cameraSelect = document.createElement('select');
  cameraSelect.id = 'cameraSelect';
  cameraSelect.className = 'form-control camera-select';
  cameraSelect.innerHTML = '<option value="">Select Camera</option>';
  controls.appendChild(cameraSelect);
  enrollBtn = document.createElement('button');
  enrollBtn.id = 'enrollBtn';
  enrollBtn.className = 'btn btn-primary camera-btn';
  enrollBtn.textContent = 'Start';
  controls.appendChild(enrollBtn);
  elements.controlsArea.appendChild(controls);

  elements.instructionArea.innerHTML = '';
  const instructionEl = document.createElement('div');
  instructionEl.id = 'instruction';
  instructionEl.className = 'text-primary';
  instructionEl.setAttribute('role', 'status');
  instructionEl.setAttribute('aria-live', 'polite');
  instructionEl.innerHTML = 'Click Start to begin enrollment. <small class="text-muted d-block mt-1">Ensure good lighting and center your face.</small>';
  elements.instructionArea.appendChild(instructionEl);

  const progressBar = elements.instructionArea.parentElement.querySelector('.progress');
  const progressInner = progressBar?.querySelector('.progress-bar');
  if (!progressBar || !progressInner) return;

  const hiddenInputs = {
    emotion: uiUtils.createHiddenInput(`${dataName}_emotion`, 'unknown', enrollDiv),
    encoding: uiUtils.createHiddenInput(`${dataName}_encoding`, '', enrollDiv),
    gender: uiUtils.createHiddenInput(`${dataName}_gender`, 'unknown', enrollDiv),
    age: uiUtils.createHiddenInput(`${dataName}_age`, '0', enrollDiv),
    capture: uiUtils.createHiddenInput(`${dataName}_capture_file`, '', enrollDiv)
  };
  hiddenInputs.capture.type = 'file';
  hiddenInputs.capture.accept = 'image/png';
  hiddenInputs.capture.style.display = 'none';

  if (existingData.capture || existingData.embedding) {
    isUpdateMode = true;
    if (existingData.capture) {
      imageEl.src = existingData.capture;
      imageEl.style.display = 'block';
    }
    dominantEmotion = existingData.emotion;
    detectedGender = existingData.gender;
    detectedAge = existingData.age;
    updateHiddenInputs(dominantEmotion, existingData.embedding, detectedGender, detectedAge);
    updateDisplays(dominantEmotion, detectedGender, detectedAge);
    enrollBtn.textContent = 'Re-enroll';
    instructionEl.innerHTML = 'Existing data loaded. Click Re-enroll to update. <small class="text-muted d-block mt-1">Ensure good lighting and center your face.</small>';
    setProgress(1);
  }

  enrollBtn.addEventListener('click', toggleEnroll);
  cameraSelect.addEventListener('change', async () => {
    if (cameraSelect.value) {
      currentCameraIndex = cameras.findIndex(cam => cam.deviceId === cameraSelect.value);
      await startCamera();
    }
  });

  async function startCamera() {
    console.log("Starting camera");
    // stopCamera();
    try {
      if (cameras[currentCameraIndex]?.deviceId) {
        config.VIDEO_CONSTRAINTS.video.deviceId = { exact: cameras[currentCameraIndex].deviceId };
      }
      videoStream = await navigator.mediaDevices.getUserMedia(config.VIDEO_CONSTRAINTS);
      videoEl.srcObject = videoStream;
      await videoEl.play();
      streaming = true;
      videoEl.style.display = 'block';
      imageEl.style.display = 'none';
      setTimeout(resetGuideStroke, 50); 
      lastFaceDetectedTime = Date.now();
      lastFacePosition = null;
      lastFaceRotation = null;
    } catch (err) {
      streaming = false;
      updateInstruction('Camera access denied. Please allow camera access.', 'danger');
      throw err;
    }
  }

  function stopCamera() {
    if (videoStream) {
      videoStream.getTracks().forEach(t => t.stop());
      videoEl.srcObject = null;
    }
    streaming = false;
    if (idleTimeoutId) {
      clearTimeout(idleTimeoutId);
      idleTimeoutId = null;
    }
    videoEl.style.display = 'none';
    imageEl.style.display = 'block';
    lastFaceDetectedTime = null;
    lastFacePosition = null;
    lastFaceRotation = null;
  }

  function resetGuideStroke() {
    const r = guideCircleEl.r.baseVal.value;
    const c = 2 * Math.PI * r;
    guideCircleEl.style.strokeDasharray = `${c} ${c}`;
    guideCircleEl.style.strokeDashoffset = `${c}`;
    guideCircleEl.style.strokeWidth = config.BORDER_THICKNESS.toString();
    guideCircleEl.style.transition = `stroke ${config.ANIMATION_SPEED}s ease, stroke-dashoffset ${config.ANIMATION_SPEED}s ease, stroke-width ${config.ANIMATION_SPEED}s ease`;
    guideCircleEl.style.stroke = config.NEUTRAL_COLOR;
  }

  function setProgress(fraction) {
    const r = guideCircleEl.r.baseVal.value;
    const c = 2 * Math.PI * r;
    const offset = c * (1 - mathUtils.clamp(fraction, 0, 1));
    guideCircleEl.style.strokeDashoffset = `${offset.toFixed(4)}`;
    progressInner.style.width = `${(fraction * 100).toFixed(0)}%`;
    progressInner.setAttribute('aria-valuenow', (fraction * 100).toFixed(0));
  }

  function setGuideState(state) {
    guideCircleEl.style.stroke = state === 'valid' ? config.VALID_COLOR : state === 'invalid' ? config.INVALID_COLOR : config.NEUTRAL_COLOR;
  }

  async function toggleEnroll() {
    running ? stopEnroll() : await beginEnroll();
  }

  async function beginEnroll() {
    try {
      enrollBtn.disabled = true;
      enrollBtn.textContent = 'Preparing...';
      human = await faceUtils.initializeHuman(config.MODEL_BASE);
      cameras = await faceUtils.getCameras();

      cameraSelect.innerHTML = '';
      const defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = 'Select Camera';
      cameraSelect.appendChild(defaultOption);
      cameras.forEach((cam, i) => {
        const option = document.createElement('option');
        option.value = cam.deviceId;
        option.textContent = cam.label || `Camera ${i + 1}`;
        cameraSelect.appendChild(option);
      });

      if (cameras.length > 0) {
        currentCameraIndex = 0;
        cameraSelect.value = cameras[0].deviceId;
      } else {
        cameraSelect.innerHTML = '<option value="">No cameras available</option>';
      }

      await startCamera();
      if (!streaming) {
        enrollBtn.disabled = false;
        enrollBtn.textContent = isUpdateMode ? 'Re-enroll' : 'Start';
        updateInstruction('Camera unavailable. Check connections.', 'danger');
        return;
      }

      running = true;
      currentStepIndex = 0;
      descriptorStore = new Map(STEPS.map(step => [step.key, []]));
      snapshotBlob = null;
      dominantEmotion = 'unknown';
      detectedGender = 'unknown';
      detectedAge = 0;
      updateHiddenInputs('unknown', '', 'unknown', 0);
      updateDisplays('unknown', 'unknown', 0);
      enrollBtn.textContent = 'Stop';
      setGuideState('neutral');
      setProgress(0);
      updateInstruction(STEPS[0].label, 'primary', `Step ${currentStepIndex + 1}/${STEPS.length}`);

      while (running) {
        await mathUtils.sleep(50);
        let res;
        try {
          res = await human.detect(videoEl, { swapRB: true });
        } catch (err) {
          updateInstruction('Detection error. Try restarting.', 'danger');
          checkIdleTimeout();
          continue;
        }

        const face = res.face?.[0];
        let movementDetected = false;

        if (face?.box && face?.rotation?.angle) {
          const currentPosition = face.box;
          const currentRotation = face.rotation.angle;
          if (lastFacePosition && lastFaceRotation) {
            const posDiff = Math.sqrt(
              Math.pow(currentPosition[0] - lastFacePosition[0], 2) +
              Math.pow(currentPosition[1] - lastFacePosition[1], 2)
            );
            const angleDiff = Math.sqrt(
              Math.pow(currentRotation.yaw - lastFaceRotation.yaw, 2) +
              Math.pow(currentRotation.pitch - lastFaceRotation.pitch, 2)
            );
            movementDetected = posDiff > config.MOVEMENT_THRESHOLD || angleDiff > config.ANGLE_THRESHOLD;
          }
          lastFacePosition = currentPosition;
          lastFaceRotation = currentRotation;
        }

        if (!face || !face.embedding || !face.rotation?.angle) {
          setGuideState('invalid');
          updateInstruction('No face detected. Please center your face.', 'warning');
          if (!movementDetected) {
            checkIdleTimeout();
          } else {
            lastFaceDetectedTime = Date.now();
          }
          continue;
        }

        lastFaceDetectedTime = Date.now();
        if (idleTimeoutId) {
          clearTimeout(idleTimeoutId);
          idleTimeoutId = null;
        }

        const yaw = face.rotation.angle.yaw;
        const pitch = face.rotation.angle.pitch;
        const expected = STEPS[currentStepIndex].key;
        const aligned = isAligned(expected, yaw, pitch);
        const currentEmotion = face.emotion?.length ? face.emotion.reduce((max, e) => e.score > max.score ? e : max).emotion : 'unknown';
        const currentGender = face.gender || 'unknown';
        const currentAge = face.age ? Math.round(face.age) : 0;

        updateDisplays(currentEmotion, currentGender, currentAge);
        let canCapture = aligned;
        let state = 'invalid';
        if (aligned) {
          state = 'valid';
          updateInstruction(STEPS[currentStepIndex].label, 'primary', `Step ${currentStepIndex + 1}/${STEPS.length}`);
        } else {
          updateInstruction(STEPS[currentStepIndex].label, 'warning', 'Adjust your head as shown.');
        }
        setGuideState(state);

        if (canCapture && descriptorStore.get(expected).length < 1) {
          descriptorStore.get(expected).push(Array.from(face.embedding));
          if (expected === 'straight') {
            const canvas = document.createElement('canvas');
            canvas.width = videoEl.videoWidth;
            canvas.height = videoEl.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(videoEl, 0, 0);
            snapshotBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            imageEl.src = URL.createObjectURL(snapshotBlob);
            const file = new File([snapshotBlob], 'captured_face.png', { type: 'image/png' });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            hiddenInputs.capture.files = dataTransfer.files;
            dominantEmotion = currentEmotion;
            detectedGender = currentGender;
            detectedAge = currentAge;
            updateHiddenInputs(dominantEmotion, JSON.stringify({ [expected]: face.embedding }), detectedGender, detectedAge);
          }
          await mathUtils.sleep(200);
        }

        if (descriptorStore.get(expected).length >= 1) {
          currentStepIndex++;
          setProgress(currentStepIndex / STEPS.length);
          if (currentStepIndex >= STEPS.length) {
            running = false;
            setProgress(1);
            break;
          }
          updateInstruction(STEPS[currentStepIndex].label, 'primary', `Step ${currentStepIndex + 1}/${STEPS.length}`);
          await mathUtils.sleep(300);
        }
      }

      enrollBtn.disabled = false;
      enrollBtn.textContent = isUpdateMode ? 'Re-enroll' : 'Start';
      if (descriptorStore.size === STEPS.length && [...descriptorStore.values()].every(arr => arr.length >= 1)) {
        const simplifiedStore = Object.fromEntries(descriptorStore);
        hiddenInputs.encoding.value = JSON.stringify(simplifiedStore);
        updateInstruction(isUpdateMode ? 'Update complete! Face data updated.' : 'Enrollment complete! Face data saved.', 'success', 'You’re all set!');
        imageEl.style.display = 'block';
        videoEl.style.display = 'none';
      } else {
        updateInstruction('Enrollment cancelled.', 'warning', 'Start again if needed.');
        if (isUpdateMode) {
          resetInputsToExisting();
        } else {
          resetInputs();
        }
      }
      setGuideState('neutral');
      setProgress(isUpdateMode ? 1 : 0);
      stopCamera();
    } catch (err) {
      updateInstruction(`Enrollment failed: ${err.message}`, 'danger');
      enrollBtn.disabled = false;
      enrollBtn.textContent = isUpdateMode ? 'Re-enroll' : 'Start';
      if (isUpdateMode) {
        resetInputsToExisting();
      } else {
        resetInputs();
      }
      stopCamera();
    }
  }

  function checkIdleTimeout() {
    if (!lastFaceDetectedTime) return;
    if (Date.now() - lastFaceDetectedTime > config.IDLE_TIMEOUT && !idleTimeoutId) {
      idleTimeoutId = setTimeout(() => {
        if (running) {
          stopEnroll();
          updateInstruction('Camera stopped due to inactivity.', 'warning', 'Start again to continue.');
        }
      }, config.IDLE_TIMEOUT);
    }
  }

  function isAligned(key, yaw, pitch) {
    const step = STEPS.find(s => s.key === key);
    if (!step) return false;
    const yawRange = step.yaw || [-Infinity, Infinity];
    const pitchRange = step.pitch || [-Infinity, Infinity];
    return yaw >= yawRange[0] && yaw <= yawRange[1] && pitch >= pitchRange[0] && pitch <= pitchRange[1];
  }

  function updateInstruction(message, color = 'primary', note = '') {
    elements.instructionArea.innerHTML = `<div id="instruction" class="text-${color}" role="status" aria-live="polite">${message} <small class="text-${color} d-block mt-1">${note}</small></div>`;
  }

  function updateDisplays(emotion, gender, age) {
    elements.emotionDisplay.textContent = `${emotion.charAt(0).toUpperCase() + emotion.slice(1)} ${EMOJI_MAP[emotion] || ''}`;
    elements.emotionDisplay.className = `captured_emotion text-${emotion === 'happy' ? 'success' : 'warning'}`;
    elements.genderDisplay.textContent = gender.charAt(0).toUpperCase() + gender.slice(1);
    elements.genderDisplay.className = 'captured_gender text-info';
    elements.ageDisplay.textContent = age > 0 ? `~${age}` : 'Unknown';
    elements.ageDisplay.className = 'captured_age text-primary';
  }

  function updateHiddenInputs(emotion, encoding, gender, age) {
    hiddenInputs.emotion.value = emotion;
    hiddenInputs.encoding.value = encoding;
    hiddenInputs.gender.value = gender;
    hiddenInputs.age.value = age.toString();
  }

  function resetInputs() {
    imageEl.src = config.PLACEHOLDER_IMAGE;
    imageEl.style.display = 'block';
    videoEl.style.display = 'none';
    hiddenInputs.capture.value = '';
    updateHiddenInputs('unknown', '', 'unknown', 0);
    updateDisplays('unknown', 'unknown', 0);
  }

  function resetInputsToExisting() {
    if (existingData.capture) {
      imageEl.src = existingData.capture;
      imageEl.style.display = 'block';
    }
    videoEl.style.display = 'none';
    hiddenInputs.capture.value = '';
    updateHiddenInputs(existingData.emotion, existingData.embedding, existingData.gender, existingData.age);
    updateDisplays(existingData.emotion, existingData.gender, existingData.age);
  }

  function stopEnroll() {
    running = false;
    enrollBtn.textContent = isUpdateMode ? 'Re-enroll' : 'Start';
    setGuideState('neutral');
    setProgress(isUpdateMode ? 1 : 0);
    updateInstruction('Enrollment stopped.', 'info', 'You can start again.');
    if (isUpdateMode) {
      resetInputsToExisting();
    } else {
      resetInputs();
    }
    stopCamera();
  }

  resetGuideStroke();
}

/**
 * Performs face and location verification for attendance.
 * @param {Object} jsonData - Configuration data
 * @param {string} divId - Container ID
 * @param {string} resultNotesSelector - Selector for result notes
 */
async function smartMatch(jsonData, divId, resultNotesSelector) {
  console.log('json Data', jsonData);
  if (!jsonData || typeof jsonData !== 'object') {
    throw new Error('Invalid JSON data provided to smartMatch.');
  }
  const container = document.getElementById(divId);
  if (!container) {
    throw new Error(`Container with ID '${divId}' not found.`);
  }
  const resultNotes = document.querySelector(resultNotesSelector);
  if (!resultNotes) {
    throw new Error(`Result notes selector '${resultNotesSelector}' not found.`);
  }

  try {
    const dataName = container.dataset.name || 'smart_match';
    const punchInText = container.dataset.text || 'Punch In';
    const apiKey = container.dataset.mapsApi || '';
    const backendUrl = container.dataset.backendUrl || '/api/punch';
    container.style.width = '100%';
    container.style.height = 'auto';
    container.innerHTML = '';

    if (jsonData.location && !apiKey) {
      throw new Error('Google Maps API key not provided.');
    }

    const loadingShim = uiUtils.showLoading(container, 'Initializing Smart Match...');
    const faceConfig = jsonData.face || null;
    const locationConfig = jsonData.location || null;
    const userData = jsonData.user || null;

    const prefix = `${dataName}_`;
    const hiddenInputs = {
      face: uiUtils.createHiddenInput(`${prefix}face`, '', container),
      accuracy: uiUtils.createHiddenInput(`${prefix}accuracy`, '0', container),
      location: uiUtils.createHiddenInput(`${prefix}location`, '', container),
      radius: uiUtils.createHiddenInput(`${prefix}radius`, '0', container),
      distance: uiUtils.createHiddenInput(`${prefix}distance`, '0', container),
      in_radius: uiUtils.createHiddenInput(`${prefix}in_radius`, '0', container),
      timestamp: uiUtils.createHiddenInput(`${prefix}timestamp`, '', container),
      is_verified: uiUtils.createHiddenInput(`${prefix}is_verified`, '0', container)
    };
    hiddenInputs.face.type = 'file';
    hiddenInputs.face.accept = 'image/png';
    hiddenInputs.face.style.display = 'none';
    hiddenInputs.timestamp.type = 'hidden';
    hiddenInputs.is_verified.type = 'hidden';

    let faceMatch = !faceConfig;
    let geoMatch = !locationConfig;
    let overallMatch = true;

    resultNotes.innerHTML = '';
    resultNotes.classList.remove('alert-success', 'alert-warning', 'alert-danger');

    const rowContainer = document.createElement('div');
    rowContainer.className = 'row g-3';
    container.appendChild(rowContainer);

    let faceContainer = null;
    let punchInBtn = null;
    let submitBtn = null;
    let instructionArea = null;
    let guideCircleEl = null;
    let videoEl = null;
    let imageEl = null;
    let toggleMapBtn = null;

    if (faceConfig) {
      try {
        faceContainer = document.createElement('div');
        faceContainer.className = locationConfig ? 'col-lg-12 col-sm-12' : 'col-12';
        rowContainer.appendChild(faceContainer);

        const innerContainer = document.createElement('div');
        innerContainer.className = 'container h-100 d-flex flex-column align-items-center';
        faceContainer.appendChild(innerContainer);

        const cameraArea = document.createElement('div');
        cameraArea.className = 'camera-area position-relative flex-grow-1 w-100';
        cameraArea.style.cssText = 'height: calc(100% - 80px);';
        innerContainer.appendChild(cameraArea);

        imageEl = document.createElement('img');
        imageEl.id = 'placeholder';
        imageEl.src = 'data:image/svg+xml,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20width=%2224%22%20height=%2224%22%20viewBox=%220%200%2024%2024%22%20fill=%22%23f3f3f3%22%3E%3Cpath%20d=%22M12%202a5%205%200%201%201%20-5%205l.005%20-.217a5%205%200%200%201%204.995%20-4.783z%22/%3E%3Cpath%20d=%22M14%2014a5%205%200%200%201%205%205v1a2%202%200%200%201%20-2%202h-10a2%202%200%200%201%20-2%20-2v-1a5%205%200%200%201%205%20-5h4z%22/%3E%3C/svg%3E';
        imageEl.style.cssText = 'width: 100%; height: 100%; object-fit: cover; display: block;';
        cameraArea.appendChild(imageEl);

        videoEl = document.createElement('video');
        videoEl.id = 'video';
        videoEl.autoplay = true;
        videoEl.playsInline = true;
        videoEl.muted = true;
        videoEl.setAttribute('aria-hidden', 'true');
        videoEl.style.cssText = 'width: 100%; height: 100%; object-fit: cover; display: block;';
        cameraArea.appendChild(videoEl);

        const guideWrap = document.createElement('div');
        guideWrap.className = 'guide-wrap position-absolute top-0 start-0 w-100 h-100';
        guideWrap.id = 'guideWrap';
        guideWrap.setAttribute('aria-hidden', 'true');
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.id = 'guideSVG';
        svg.setAttribute('viewBox', '0 0 100 100');
        svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
        svg.style.cssText = 'width: 100%; height: 100%;';
        guideCircleEl = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        guideCircleEl.id = 'guideCircle';
        guideCircleEl.setAttribute('cx', '50');
        guideCircleEl.setAttribute('cy', '50');
        guideCircleEl.setAttribute('r', '47.5');
        guideCircleEl.setAttribute('fill', 'none');
        guideCircleEl.setAttribute('stroke', '#6c757d');
        guideCircleEl.setAttribute('stroke-width', '5');
        guideCircleEl.setAttribute('stroke-dasharray', '298.45');
        guideCircleEl.setAttribute('stroke-dashoffset', '298.45');
        guideCircleEl.style.transition = 'stroke-dashoffset 0.4s ease, stroke 0.4s ease';
        svg.appendChild(guideCircleEl);
        guideWrap.appendChild(svg);
        cameraArea.appendChild(guideWrap);

        const controlsArea = document.createElement('div');
        controlsArea.className = 'controls d-flex justify-content-center gap-3 mt-2';
        innerContainer.appendChild(controlsArea);

        punchInBtn = document.createElement('button');
        punchInBtn.className = 'btn btn-primary punch-in-btn';
        punchInBtn.textContent = punchInText;
        punchInBtn.setAttribute('type', 'button');
        controlsArea.appendChild(punchInBtn);

        submitBtn = document.createElement('button');
        submitBtn.className = 'btn btn-success submit-btn';
        submitBtn.textContent = 'Submit';
        submitBtn.setAttribute('type', 'submit');
        submitBtn.disabled = true;
        controlsArea.appendChild(submitBtn);

        if (locationConfig) {
          toggleMapBtn = document.createElement('button');
          toggleMapBtn.className = 'btn btn-secondary toggle-map-btn';
          toggleMapBtn.textContent = 'Show Map';
          toggleMapBtn.setAttribute('type', 'button');
          controlsArea.appendChild(toggleMapBtn);
        }

        instructionArea = document.createElement('div');
        instructionArea.className = 'instruction_area text-primary mt-2';
        instructionArea.setAttribute('role', 'status');
        instructionArea.setAttribute('aria-live', 'polite');
        instructionArea.innerHTML = `Click "${punchInText}" to verify your attendance. <small class="text-muted d-block mt-1">Ensure good lighting and center your face.</small>`;
        innerContainer.appendChild(instructionArea);

        const faceStrict = faceConfig['strict-mode'] || false;
        const accuracy = parseFloat(faceConfig.accuracy || '70') / 100;
        const maxAttempts = parseInt(faceConfig.attempts || '5', 10);
        const minSuccesses = 3;
        const providedEmbedding = faceConfig.embedding?.straight?.[0] || null;
        console.log(providedEmbedding);
        console.log(faceConfig);

        if (!providedEmbedding) {
          throw new Error('No face embedding provided.');
        }

        async function verifyFace() {
          try {
            if (!geoMatch && locationConfig && locationConfig['strict-mode']) {
              instructionArea.innerHTML = `Cannot proceed with ${punchInText.toLowerCase()}. You are not within the designated location. <small class="text-muted d-block mt-1">Please move to the correct location.</small>`;
              punchInBtn.disabled = true;
              submitBtn.disabled = true;
              hiddenInputs.is_verified.value = '0';
              return;
            }

            punchInBtn.disabled = true;
            punchInBtn.textContent = `${punchInText}...`;
            instructionArea.innerHTML = `Position your face in front of the camera. <small class="text-muted d-block mt-1">Ensure good lighting.</small>`;
            guideCircleEl.setAttribute('stroke', '#6c757d');
            guideCircleEl.setAttribute('stroke-dashoffset', '298.45');
            human = await faceUtils.initializeHuman(config.MODEL_BASE);
            await startCamera();
            let attempts = 0;
            let successCount = 0;
            const startTime = Date.now();
            let lastAccuracy = 0;

            while (streaming && attempts < maxAttempts && (Date.now() - startTime) < config.IDLE_TIMEOUT) {
              await mathUtils.sleep(50);
              attempts++;
              const res = await human.detect(videoEl, { swapRB: true });
              const face = res.face?.[0];

              if (!face || !face.embedding) {
                instructionArea.innerHTML = `No face detected. Attempt ${attempts} of ${maxAttempts}. ${successCount}/3 successes needed. <small class="text-muted d-block mt-1">Center your face.</small>`;
                updateProgress(attempts, maxAttempts, successCount);
                lastFaceDetectedTime = Date.now();
                continue;
              }

              const capturedEmbedding = Array.from(face.embedding);
              const sim = mathUtils.cosineSimilarity(capturedEmbedding, providedEmbedding);
              const isMatch = sim >= accuracy;
              if (isMatch) {
                successCount++;
                const canvas = document.createElement('canvas');
                canvas.width = videoEl.videoWidth;
                canvas.height = videoEl.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.translate(canvas.width, 0);
                ctx.scale(-1, 1);
                ctx.drawImage(videoEl, 0, 0);
                snapshotBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                imageEl.src = URL.createObjectURL(snapshotBlob);
                const file = new File([snapshotBlob], 'punched_face.png', { type: 'image/png' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                hiddenInputs.face.files = dataTransfer.files;
                lastAccuracy = sim;
              }
              hiddenInputs.accuracy.value = `${(sim * 100).toFixed(2)}%`;

              instructionArea.innerHTML = `Attempt ${attempts} of ${maxAttempts}: ${isMatch ? `Match successful (${(sim * 100).toFixed(2)}%).` : `Match failed (${(sim * 100).toFixed(2)}%).`} ${successCount}/3 successes needed. <small class="text-muted d-block mt-1">Center your face.</small>`;
              updateProgress(attempts, maxAttempts, successCount);
              lastFaceDetectedTime = Date.now();

              if (successCount >= minSuccesses) {
                break;
              }
            }

            faceMatch = successCount >= minSuccesses;
            stopCamera();
            punchInBtn.disabled = false;
            punchInBtn.textContent = punchInText;

            if (faceMatch) {
              instructionArea.innerHTML = locationConfig ? `Face verified. Awaiting location verification for ${punchInText.toLowerCase()}. <small class="text-muted d-block mt-1">Ensure you are within the designated location or click "Show Map" to verify.</small>` : `Face verified. Click "Submit" to record attendance. <small class="text-muted d-block mt-1">Attendance will be recorded.</small>`;
              resultNotes.innerHTML = `<div class="alert alert-success mt-2">Face verified for ${punchInText.toLowerCase()}. Accuracy: ${(lastAccuracy * 100).toFixed(2)}%. ${successCount}/3 successes achieved.</div>`;
              resultNotes.classList.add('alert-success');
            } else {
              instructionArea.innerHTML = `Face verification failed for ${punchInText.toLowerCase()} after ${attempts} attempts. <small class="text-muted d-block mt-1">Please try again.</small>`;
              resultNotes.innerHTML = `<div class="alert alert-warning mt-2">Face verification failed. Accuracy: ${(hiddenInputs.accuracy.value || '0').replace('%', '')}%. ${successCount}/3 successes achieved. Please try again.</div>`;
              resultNotes.classList.add('alert-warning');
              faceMatch = !faceConfig['strict-mode'];
              hiddenInputs.is_verified.value = '0';
            }

            overallMatch = faceMatch && geoMatch;
            if (overallMatch) {
              const punchInTimestamp = formatTimestamp();
              hiddenInputs.timestamp.value = punchInTimestamp;
              hiddenInputs.is_verified.value = '1';
              submitBtn.disabled = false;
              console.log('Verification complete, ready to submit:', {
                user: userData?.name || 'Unknown',
                timestamp: punchInTimestamp,
                image: hiddenInputs.face.files?.[0],
                location: hiddenInputs.location.value,
                accuracy: hiddenInputs.accuracy.value,
                is_verified: hiddenInputs.is_verified.value
              });
              instructionArea.innerHTML = `Verification complete. Click "Submit" to record attendance. <small class="text-muted d-block mt-1">Attendance will be recorded.</small>`;
            } else {
              hiddenInputs.is_verified.value = '0';
              submitBtn.disabled = true;
            }
          } catch (err) {
            stopCamera();
            punchInBtn.disabled = false;
            punchInBtn.textContent = punchInText;
            instructionArea.innerHTML = `Verification failed for ${punchInText.toLowerCase()}. <small class="text-muted d-block mt-1">${geoMatch || !locationConfig ? 'Check camera permissions.' : 'You are not within the designated location.'}</small>`;
            resultNotes.innerHTML = `<div class="alert alert-danger mt-2">Verification error: ${err.message}</div>`;
            resultNotes.classList.add('alert-danger');
            guideCircleEl.setAttribute('stroke', '#dc3545');
            faceMatch = !faceConfig['strict-mode'];
            overallMatch = faceMatch && geoMatch;
            hiddenInputs.is_verified.value = '0';
            submitBtn.disabled = true;
          }
        }

        punchInBtn.addEventListener('click', async () => {
          try {
            await verifyFace();
          } catch (err) {
            resultNotes.innerHTML = `<div class="alert alert-danger mt-2">Face verification error: ${err.message}</div>`;
            resultNotes.classList.add('alert-danger');
            console.error('Face verification error:', err);
          }
        });

        
      } catch (err) {
        resultNotes.innerHTML = `<div class="alert alert-danger mt-2">Face verification setup error: ${err.message}</div>`;
        resultNotes.classList.add('alert-danger');
        console.error('Face verification setup error:', err);
        if (punchInBtn) {
          punchInBtn.disabled = true;
          punchInBtn.textContent = punchInText;
        }
        if (submitBtn) {
          submitBtn.disabled = true;
        }
        if (guideCircleEl) {
          guideCircleEl.setAttribute('stroke', '#dc3545');
        }
        hiddenInputs.is_verified.value = '0';
        throw err;
      }
    }

    const config = {
      MODEL_BASE: `${window.location.origin}/models`,
      VIDEO_CONSTRAINTS: { audio: false, video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' } },
      IDLE_TIMEOUT: 3000
    };

    let human = null;
    let videoStream = null;
    let streaming = false;
    let snapshotBlob = null;
    let lastFaceDetectedTime = null;
    let mapSection = null;

    function formatTimestamp() {
      const date = new Date();
      const pad = (num) => String(num).padStart(2, '0');
      const year = date.getFullYear();
      const month = pad(date.getMonth() + 1);
      const day = pad(date.getDate());
      const hours = pad(date.getHours());
      const minutes = pad(date.getMinutes());
      const seconds = pad(date.getSeconds());
      return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }

    if (locationConfig) {
      try {
        const locationStrict = locationConfig['strict-mode'] || false;
        const targetCoordsStr = locationConfig.coordinates || '';
        if (!targetCoordsStr) {
          throw new Error('Target coordinates not provided.');
        }
        const [targetLat, targetLng] = targetCoordsStr.split(',').map(Number);
        if (isNaN(targetLat) || isNaN(targetLng)) {
          throw new Error('Invalid target coordinates format. Expected "lat,lng".');
        }
        const radius = Math.max(1, parseFloat(locationConfig.radius || '100'));
        const mapWidth = locationConfig.width || '100%';
        const mapHeight = locationConfig.height || '300px';

        mapSection = document.createElement('div');
        mapSection.className = faceConfig ? 'col-lg-6 col-sm-12 location-match-section' : 'col-12 location-match-section';
        mapSection.style.display = faceConfig ? 'none' : 'block';
        rowContainer.appendChild(mapSection);

        const mapTitle = document.createElement('h5');
        mapTitle.className = 'text-center';
        mapTitle.textContent = `Location Verification for ${punchInText}`;
        mapSection.appendChild(mapTitle);

        const mapContainer = document.createElement('div');
        mapContainer.id = 'mapContainer';
        mapContainer.style.cssText = `width: ${mapWidth}; height: ${mapHeight};`;
        mapSection.appendChild(mapContainer);

        let currentLocation;
        try {
          currentLocation = await mapUtils.getCurrentLocation();
        } catch (geoErr) {
          throw new Error(`Geolocation unavailable for ${punchInText.toLowerCase()}: ${geoErr.message}.`);
        }

        const distance = mathUtils.calculateDistance(currentLocation.lat, currentLocation.lng, targetLat, targetLng);
        geoMatch = distance <= radius;

        hiddenInputs.location.value = `${currentLocation.lat},${currentLocation.lng}`;
        hiddenInputs.radius.value = radius.toString();
        hiddenInputs.distance.value = distance.toFixed(2);
        hiddenInputs.in_radius.value = geoMatch ? '1' : '0';

        await mapUtils.loadGoogleMaps(apiKey);
        const map = new google.maps.Map(mapContainer, {
          center: { lat: targetLat, lng: targetLng },
          zoom: 14,
          mapId: 'smart-match-map'
        });

        new google.maps.marker.AdvancedMarkerElement({
          position: { lat: targetLat, lng: targetLng },
          map,
          title: `${punchInText} Location`,
          content: createMarkerPin('http://maps.google.com/mapfiles/ms/icons/red-dot.png')
        });

        let currentIconUrl = 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png';
        let pinTitle = `Current Location (${distance.toFixed(2)}m away)`;
        if (userData?.image && userData.image.startsWith('data:image/')) {
          currentIconUrl = userData.image;
          pinTitle = `${userData.name || 'User'} (${distance.toFixed(2)}m away)`;
        }
        new google.maps.marker.AdvancedMarkerElement({
          position: currentLocation,
          map,
          title: pinTitle,
          content: createMarkerPin(currentIconUrl)
        });

        new google.maps.Circle({
          map,
          center: { lat: targetLat, lng: targetLng },
          radius,
          fillColor: '#00b4af',
          fillOpacity: 0.2,
          strokeColor: '#00b4af',
          strokeWeight: 2
        });

        new google.maps.Polyline({
          map,
          path: [
            { lat: targetLat, lng: targetLng },
            { lat: currentLocation.lat, lng: currentLocation.lng }
          ],
          geodesic: true,
          strokeColor: '#000000',
          strokeOpacity: 0.8,
          strokeWeight: 2,
          icons: [{
            icon: { path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW },
            offset: '100%',
            repeat: '20px'
          }]
        });

        resultNotes.innerHTML = faceConfig ? resultNotes.innerHTML : `<div class="alert ${geoMatch ? 'alert-success' : 'alert-warning'} mt-2">${geoMatch ? `Location verified for ${punchInText.toLowerCase()}. You are within ${distance.toFixed(2)}m of the designated ${radius}m radius.` : `Location verification failed for ${punchInText.toLowerCase()}. You are ${distance.toFixed(2)}m away, outside the ${radius}m radius.`}</div>`;
        resultNotes.classList.add(geoMatch ? 'alert-success' : 'alert-warning');

        if (!geoMatch && faceConfig && locationConfig['strict-mode']) {
          punchInBtn.disabled = true;
          submitBtn.disabled = true;
          instructionArea.innerHTML = `Cannot proceed with ${punchInText.toLowerCase()}. You are not within the designated location. <small class="text-muted d-block mt-1">Please move to the correct location or click "Show Map" to verify.</small>`;
        } else if (!faceConfig && geoMatch) {
          overallMatch = geoMatch;
          const punchInTimestamp = formatTimestamp();
          hiddenInputs.timestamp.value = punchInTimestamp;
          hiddenInputs.is_verified.value = '1';
          console.log('Verification complete, ready to submit:', {
            user: userData?.name || 'Unknown',
            timestamp: hiddenInputs.timestamp.value,
            image: null,
            location: hiddenInputs.location.value,
            accuracy: hiddenInputs.accuracy.value,
            is_verified: hiddenInputs.is_verified.value
          });
          resultNotes.innerHTML = `<div class="alert alert-success mt-2">${punchInText} successful! Location verified at ${new Date().toLocaleString('en-IN', { timeZone: 'Asia/Kolkata' })}. Click "Submit" to record.</div>`;
        } else if (!faceConfig && !geoMatch && !locationConfig['strict-mode']) {
          overallMatch = true;
          const punchInTimestamp = formatTimestamp();
          hiddenInputs.timestamp.value = punchInTimestamp;
          hiddenInputs.is_verified.value = '1';
          console.log('Verification complete, ready to submit:', {
            user: userData?.name || 'Unknown',
            timestamp: hiddenInputs.timestamp.value,
            image: null,
            location: hiddenInputs.location.value,
            accuracy: hiddenInputs.accuracy.value,
            is_verified: hiddenInputs.is_verified.value
          });
          resultNotes.innerHTML = `<div class="alert alert-success mt-2">${punchInText} successful! Location verification bypassed (non-strict mode) at ${new Date().toLocaleString('en-IN', { timeZone: 'Asia/Kolkata' })}. Click "Submit" to record.</div>`;
        }

        if (!faceConfig) {
          const controlsArea = document.createElement('div');
          controlsArea.className = 'controls d-flex justify-content-center gap-3 mt-2';
          mapSection.appendChild(controlsArea);

          submitBtn = document.createElement('button');
          submitBtn.className = 'btn btn-success submit-btn';
          submitBtn.textContent = 'Submit';
          submitBtn.setAttribute('type', 'submit');
          submitBtn.disabled = !(geoMatch || !locationConfig['strict-mode']);
          controlsArea.appendChild(submitBtn);
        }
      } catch (locationErr) {
        resultNotes.innerHTML = faceConfig ? `<div class="alert alert-danger mt-2">Location verification failed: ${locationErr.message}. ${locationConfig['strict-mode'] ? 'Punch-in cannot proceed.' : 'Proceeding without location verification.'}</div>` : `<div class="alert alert-danger mt-2">Location verification failed for ${punchInText.toLowerCase()}: ${locationErr.message}. ${locationConfig['strict-mode'] ? 'Punch-in cannot proceed.' : 'Proceeding without location verification.'}</div>`;
        resultNotes.classList.add('alert-danger');
        geoMatch = !locationConfig['strict-mode'];
        overallMatch = faceMatch && geoMatch;
        hiddenInputs.is_verified.value = geoMatch && !faceConfig ? '1' : '0';
        if (faceConfig && locationConfig['strict-mode']) {
          punchInBtn.disabled = true;
          submitBtn.disabled = true;
          instructionArea.innerHTML = `Cannot proceed with ${punchInText.toLowerCase()}. Location verification failed: ${locationErr.message}. <small class="text-muted d-block mt-1">Please check location settings or click "Show Map" to verify.</small>`;
        } else if (!faceConfig && !locationConfig['strict-mode']) {
          overallMatch = true;
          const punchInTimestamp = formatTimestamp();
          hiddenInputs.timestamp.value = punchInTimestamp;
          hiddenInputs.is_verified.value = '1';
          console.log('Verification complete, ready to submit:', {
            user: userData?.name || 'Unknown',
            timestamp: punchInTimestamp,
            image: null,
            location: hiddenInputs.location.value,
            accuracy: hiddenInputs.accuracy.value,
            is_verified: hiddenInputs.is_verified.value
          });
          resultNotes.innerHTML = `<div class="alert alert-success mt-2">${punchInText} successful! Location verification bypassed (non-strict mode) at ${new Date().toLocaleString('en-IN', { timeZone: 'Asia/Kolkata' })}. Click "Submit" to record.</div>`;
          
          const controlsArea = document.createElement('div');
          controlsArea.className = 'controls d-flex justify-content-center gap-3 mt-2';
          mapSection.appendChild(controlsArea);

          submitBtn = document.createElement('button');
          submitBtn.className = 'btn btn-success submit-btn';
          submitBtn.textContent = 'Submit';
          submitBtn.setAttribute('type', 'submit');
          submitBtn.disabled = false;
          controlsArea.appendChild(submitBtn);

        }
      }
    }

    if (toggleMapBtn) {
      try {
        toggleMapBtn.addEventListener('click', () => {
          const isMapVisible = mapSection.style.display === 'block';
          if (isMapVisible) {
            mapSection.style.display = 'none';
            faceContainer.className = 'col-lg-12 col-sm-12';
            toggleMapBtn.textContent = 'Show Map';
          } else {
            mapSection.style.display = 'block';
            faceContainer.className = 'col-lg-6 col-sm-12';
            toggleMapBtn.textContent = 'Hide Map';
          }
        });
      } catch (err) {
        resultNotes.innerHTML = `<div class="alert alert-danger mt-2">Map toggle error: ${err.message}</div>`;
        resultNotes.classList.add('alert-danger');
        console.error('Map toggle error:', err);
      }
    }

    if (faceConfig && !geoMatch && locationConfig && locationConfig['strict-mode']) {
      punchInBtn.disabled = true;
      submitBtn.disabled = true;
      instructionArea.innerHTML = `Cannot proceed with ${punchInText.toLowerCase()}. You are not within the designated location. <small class="text-muted d-block mt-1">Please move to the correct location or click "Show Map" to verify.</small>`;
      hiddenInputs.is_verified.value = '0';
    } else if (faceConfig) {
      punchInBtn.disabled = false;
      instructionArea.innerHTML = `Click "${punchInText}" to verify your attendance. <small class="text-muted d-block mt-1">Ensure good lighting and center your face.</small>`;
    }

    loadingShim.remove();

    async function startCamera() {
      if (!videoEl) {
        throw new Error('Video element not initialized.');
      }
      try {
        videoStream = await navigator.mediaDevices.getUserMedia(config.VIDEO_CONSTRAINTS);
        videoEl.srcObject = videoStream;
        await videoEl.play();
        streaming = true;
        videoEl.style.display = 'block';
        imageEl.style.display = 'none';
        lastFaceDetectedTime = Date.now();
      } catch (err) {
        throw new Error(`Camera access denied for ${punchInText.toLowerCase()}: ${err.message}`);
      }
    }

    function stopCamera() {
      if (videoStream) {
        videoStream.getTracks().forEach(t => t.stop());
        videoStream = null;
      }
      streaming = false;
      if (videoEl) {
        videoEl.srcObject = null;
        videoEl.style.display = 'none';
      }
      if (imageEl) {
        imageEl.style.display = 'block';
      }
      lastFaceDetectedTime = null;
    }

    function updateProgress(attempt, maxAttempts, successCount) {
      if (!guideCircleEl) return;
      const fraction = attempt / maxAttempts;
      const offset = 298.45 * (1 - fraction);
      guideCircleEl.setAttribute('stroke-dashoffset', offset.toFixed(4));
      if (attempt === maxAttempts) {
        guideCircleEl.setAttribute('stroke', successCount >= 3 ? '#28a745' : '#dc3545');
      }
    }

    function createMarkerPin(url) {
      const pin = document.createElement('div');
      const img = document.createElement('img');
      img.src = url;
      img.style.width = '32px';
      img.style.height = '32px';
      pin.appendChild(img);
      return pin;
    }
  } catch (globalErr) {
    resultNotes.innerHTML = `<div class="alert alert-danger mt-2">Initialization error for ${punchInText.toLowerCase()}: ${globalErr.message}</div>`;
    resultNotes.classList.add('alert-danger');
    console.error('Smart Match global error:', globalErr);
    if (punchInBtn) {
      punchInBtn.disabled = true;
      punchInBtn.textContent = punchInText;
    }
    if (submitBtn) {
      submitBtn.disabled = true;
    }
    if (guideCircleEl) {
      guideCircleEl.setAttribute('stroke', '#dc3545');
    }
    hiddenInputs.is_verified.value = '0';
    loadingShim.remove();
  }
}

// Expose functions to global scope
window.presence = window.presence || {};
Object.assign(window.presence, { smartMap, smartFace, smartMatch });

export { smartMap, smartFace, smartMatch };