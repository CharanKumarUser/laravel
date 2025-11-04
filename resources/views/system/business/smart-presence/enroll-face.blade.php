{{-- Template: Enroll Face Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Enroll Face')
@push('styles')
    <style>
        .camera-area {
            position: relative;
            width: 100%;
            max-width: 600px;
            aspect-ratio: 1/1;
            overflow: hidden;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
            -webkit-transform: scaleX(-1);
        }

        .guide-wrap {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 2;
        }

        #guideSVG { width: 100%; height: 100%; }

        #guideCircle {
            cx: 50; cy: 50; r: 47.5;
            stroke: var(--neutral-color);
            stroke-width: calc(var(--circle-border) * 0.5);
            stroke-linecap: butt;
            transition: stroke var(--animation-speed) ease, stroke-dashoffset var(--animation-speed) ease, stroke-width var(--animation-speed) ease;
            fill: none;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }

        .instruction {
            width: 100%;
            text-align: center;
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--white);
            margin-top: 10px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .controls { display: flex; gap: 10px; width: 100%; justify-content: center; flex-wrap: wrap; margin-top: 10px; }
        .controls button {
            padding: 12px 20px;
            border-radius: 10px;
            background: linear-gradient(90deg, #1ec5a6, #17a589);
            border: none;
            color: var(--white);
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .controls button:hover { transform: scale(1.05); }
        .controls select {
            padding: 12px;
            border-radius: 10px;
            background: transparent;
            border: 1px solid rgba(255,255,255,0.06);
            color: var(--white);
            font-weight: 500;
            cursor: pointer;
        }

        @media (max-width: 520px) {
            :root { --circle-border: 6px; }
            .camera-area { max-width: 400px; }
            .instruction { font-size: 1rem; }
        }
    </style>
@endpush
@push('pre-scripts')
    <script src="{{ asset('libs/human.js') }}"></script>
@endpush
@push('scripts')
    <script>
        function enrollFace() {
            const enrollDiv = document.querySelector('[data-smart-face-enroll]');
            const dataName = enrollDiv.dataset.name;

            const CONFIG = {
                BORDER_THICKNESS: 8,
                VALID_COLOR: '#1ec5a6',
                INVALID_COLOR: '#ff5a5a',
                NEUTRAL_COLOR: 'rgba(255,255,255,0.08)',
                ANIMATION_SPEED: 0.6,
                MODEL_BASE: '{{ asset('public/models') }}/',
                VIDEO_CONSTRAINTS: { audio: false, video: { width: { ideal: 1280 }, height: { ideal: 1280 }, facingMode: 'user' } }
            };

            const STEPS = [
                { key: 'straight', label: 'Look straight', yaw: [-0.2, 0.2], pitch: [-0.2, 0.2] },
                { key: 'left', label: 'Look left', yaw: [-Infinity, -0.2] },
                { key: 'right', label: 'Look right', yaw: [0.2, Infinity] },
                { key: 'up', label: 'Look up', pitch: [-Infinity, -0.1] },
                { key: 'down', label: 'Look down', pitch: [0.1, Infinity] }
            ];

            let human = null;
            let videoEl, guideCircleEl, instructionEl, enrollBtn, cameraSelect;
            let descriptorStore = {};
            let running = false;
            let currentStepIndex = 0;
            let videoStream = null;
            let streaming = false;
            let snapshot = '';
            let dominantEmotion = '';
            let cameras = [];
            let currentCameraIndex = 0;

            // Create elements
            const container = document.createElement('div');
            container.className = 'face-enroll-container';
            enrollDiv.appendChild(container);

            const card = document.createElement('div');
            card.className = 'card';
            container.appendChild(card);

            const cameraArea = document.createElement('div');
            cameraArea.className = 'camera-area';
            videoEl = document.createElement('video');
            videoEl.id = 'video';
            videoEl.autoplay = true;
            videoEl.playsinline = true;
            videoEl.muted = true;
            videoEl.setAttribute('aria-hidden', 'true');
            cameraArea.appendChild(videoEl);

            const guideWrap = document.createElement('div');
            guideWrap.className = 'guide-wrap';
            guideWrap.id = 'guideWrap';
            guideWrap.setAttribute('aria-hidden', 'true');

            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.id = 'guideSVG';
            svg.setAttribute('viewBox', '0 0 100 100');
            svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
            guideCircleEl = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            guideCircleEl.id = 'guideCircle';
            guideCircleEl.setAttribute('cx', '50');
            guideCircleEl.setAttribute('cy', '50');
            guideCircleEl.setAttribute('r', '47.5');
            guideCircleEl.setAttribute('fill', 'none');
            svg.appendChild(guideCircleEl);
            guideWrap.appendChild(svg);
            cameraArea.appendChild(guideWrap);
            card.appendChild(cameraArea);

            instructionEl = document.createElement('div');
            instructionEl.id = 'instruction';
            instructionEl.className = 'instruction';
            instructionEl.setAttribute('role', 'status');
            instructionEl.setAttribute('aria-live', 'polite');
            instructionEl.textContent = 'Click Start to begin enrollment';
            card.appendChild(instructionEl);

            const controls = document.createElement('div');
            controls.className = 'controls';
            controls.setAttribute('aria-hidden', 'false');

            cameraSelect = document.createElement('select');
            cameraSelect.id = 'cameraSelect';
            cameraSelect.innerHTML = '<option value="">Select Camera</option>';
            controls.appendChild(cameraSelect);

            enrollBtn = document.createElement('button');
            enrollBtn.id = 'enrollBtn';
            enrollBtn.textContent = 'Start';
            controls.appendChild(enrollBtn);
            card.appendChild(controls);

            // Create hidden inputs
            const emotionInput = document.createElement('input');
            emotionInput.type = 'hidden';
            emotionInput.id = `${dataName}_emotion`;
            emotionInput.name = `${dataName}_emotion`;
            enrollDiv.appendChild(emotionInput);

            const encodingInput = document.createElement('input');
            encodingInput.type = 'hidden';
            encodingInput.id = `${dataName}_encoding`;
            encodingInput.name = `${dataName}_encoding`;
            enrollDiv.appendChild(encodingInput);

            const captureInput = document.createElement('input');
            captureInput.type = 'hidden';
            captureInput.id = `${dataName}_capture`;
            captureInput.name = `${dataName}_capture`;
            enrollDiv.appendChild(captureInput);

            // Set CSS properties
            document.documentElement.style.setProperty('--circle-border', `${CONFIG.BORDER_THICKNESS}px`);
            document.documentElement.style.setProperty('--valid-color', CONFIG.VALID_COLOR);
            document.documentElement.style.setProperty('--invalid-color', CONFIG.INVALID_COLOR);
            document.documentElement.style.setProperty('--neutral-color', CONFIG.NEUTRAL_COLOR);
            document.documentElement.style.setProperty('--animation-speed', `${CONFIG.ANIMATION_SPEED}s`);

            // Event listeners
            enrollBtn.addEventListener('click', toggleEnroll);
            cameraSelect.addEventListener('change', async () => {
                if (cameraSelect.value) {
                    currentCameraIndex = cameras.findIndex(cam => cam.deviceId === cameraSelect.value);
                    await startCamera();
                }
            });

            // Functions
            async function loadHuman() {
                if (human) return;
                try {
                    human = new Human.default({
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
                    
                } catch (err) {
                    console.error('Human.js load error:', err);
                    instructionEl.textContent = 'Failed to load face detection model';
                    throw err;
                }
            }

            async function getCameras() {
                try {
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    cameras = devices.filter(device => device.kind === 'videoinput');
                    cameraSelect.innerHTML = cameras.map((cam, i) => `<option value="${cam.deviceId}">${cam.label || `Camera ${i + 1}`}</option>`).join('');
                    if (cameras.length > 0) {
                        currentCameraIndex = 0;
                        cameraSelect.value = cameras[0].deviceId;
                    }
                } catch (err) {
                    console.error('Error enumerating cameras:', err);
                    instructionEl.textContent = 'Failed to list cameras';
                }
            }

            async function startCamera() {
                stopCamera();
                try {
                    CONFIG.VIDEO_CONSTRAINTS.video.deviceId = cameras[currentCameraIndex]?.deviceId ? { exact: cameras[currentCameraIndex].deviceId } : undefined;
                    videoStream = await navigator.mediaDevices.getUserMedia(CONFIG.VIDEO_CONSTRAINTS);
                    videoEl.srcObject = videoStream;
                    await videoEl.play();
                    streaming = true;
                    
                    setTimeout(resetGuideStroke, 50);
                } catch (err) {
                    streaming = false;
                    instructionEl.textContent = 'Camera access denied or unavailable';
                    console.error('Camera error:', err);
                    throw err;
                }
            }

            function stopCamera() {
                if (videoStream) {
                    videoStream.getTracks().forEach(t => t.stop());
                    videoEl.srcObject = null;
                }
                streaming = false;
            }

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
                if (state === 'valid') {
                    guideCircleEl.style.stroke = CONFIG.VALID_COLOR;
                } else if (state === 'invalid') {
                    guideCircleEl.style.stroke = CONFIG.INVALID_COLOR;
                } else {
                    guideCircleEl.style.stroke = CONFIG.NEUTRAL_COLOR;
                }
            }

            function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }

            async function toggleEnroll() {
                if (!running) {
                    await beginEnroll();
                } else {
                    stopEnroll();
                }
            }

            async function beginEnroll() {
                try {
                    enrollBtn.disabled = true;
                    enrollBtn.textContent = 'Preparing…';
                    await loadHuman();
                    await getCameras();
                    await startCamera();
                    if (!streaming) {
                        enrollBtn.disabled = false;
                        enrollBtn.textContent = 'Start';
                        instructionEl.textContent = 'Camera unavailable';
                        return;
                    }

                    running = true;
                    currentStepIndex = 0;
                    descriptorStore = {};
                    for (const s of STEPS) descriptorStore[s.key] = [];
                    snapshot = '';
                    dominantEmotion = '';

                    enrollBtn.textContent = 'Stop';
                    setGuideState('neutral');
                    resetGuideStroke();
                    setProgress(0);
                    updateInstruction();

                    while (running) {
                        await sleep(80);
                        let res;
                        try {
                            res = await human.detect(videoEl, { swapRB: true });
                        } catch (err) {
                            console.error('Detection error:', err);
                            instructionEl.textContent = 'Detection error';
                            continue;
                        }
                        const face = res.face && res.face[0];
                        if (!face || !face.embedding || !face.rotation || !face.rotation.angle) {
                            setGuideState('invalid');
                            updateInstruction();
                            continue;
                        }

                        const yaw = face.rotation.angle.yaw;
                        const pitch = face.rotation.angle.pitch;
                        const expected = STEPS[currentStepIndex].key;
                        const aligned = isAligned(expected, yaw, pitch);

                        setGuideState(aligned ? 'valid' : 'invalid');

                        if (aligned) {
                            if (descriptorStore[expected].length < 1) {
                                descriptorStore[expected].push(Array.from(face.embedding));
                                if (expected === 'straight') {
                                    // Capture snapshot
                                    const canvas = document.createElement('canvas');
                                    canvas.width = videoEl.videoWidth;
                                    canvas.height = videoEl.videoHeight;
                                    const ctx = canvas.getContext('2d');
                                    ctx.translate(canvas.width, 0);
                                    ctx.scale(-1, 1);
                                    ctx.drawImage(videoEl, 0, 0);
                                    snapshot = canvas.toDataURL('image/png');

                                    // Capture dominant emotion
                                    if (face.emotion && face.emotion.length > 0) {
                                        const dominant = face.emotion.reduce((max, e) => e.score > max.score ? e : max, face.emotion[0]);
                                        dominantEmotion = dominant.emotion;
                                    }
                                }
                                await sleep(400);
                            }
                            if (descriptorStore[expected].length >= 1) {
                                currentStepIndex++;
                                setProgress(currentStepIndex / STEPS.length);
                                if (currentStepIndex >= STEPS.length) {
                                    running = false;
                                    setProgress(1);
                                    break;
                                } else {
                                    updateInstruction();
                                    await sleep(600);
                                }
                            }
                        } else {
                            updateInstruction();
                        }
                    }

                    enrollBtn.disabled = false;
                    enrollBtn.textContent = 'Start';
                    if (Object.keys(descriptorStore).length === STEPS.length) {
                        const simplifiedStore = {};
                        for (const key in descriptorStore) {
                            simplifiedStore[key] = descriptorStore[key][0];
                        }
                        emotionInput.value = dominantEmotion;
                        encodingInput.value = JSON.stringify(simplifiedStore);
                        captureInput.value = snapshot;
                        instructionEl.textContent = 'Enrollment complete! Face data saved.';
                    } else {
                        instructionEl.textContent = 'Enrollment cancelled';
                    }
                    setGuideState('neutral');
                    setProgress(0);
                    stopCamera();
                } catch (err) {
                    console.error('Enrollment error:', err);
                    instructionEl.textContent = 'Enrollment failed';
                    enrollBtn.disabled = false;
                    enrollBtn.textContent = 'Start';
                } finally {
                    running = false;
                    stopCamera();
                }
            }

            function stopEnroll() {
                running = false;
                enrollBtn.textContent = 'Start';
                setGuideState('neutral');
                setProgress(0);
                instructionEl.textContent = 'Enrollment stopped';
            }

            function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

            function isAligned(key, yaw, pitch) {
                const step = STEPS.find(s => s.key === key);
                if (!step) return false;
                const yawRange = step.yaw;
                const pitchRange = step.pitch || [-Infinity, Infinity];
                const yawMatch = yaw >= (yawRange ? yawRange[0] : -Infinity) && yaw <= (yawRange ? yawRange[1] : Infinity);
                const pitchMatch = pitch >= pitchRange[0] && pitch <= pitchRange[1];
                return yawMatch && pitchMatch;
            }

            function updateInstruction() {
                instructionEl.textContent = `${STEPS[currentStepIndex].label} (${currentStepIndex + 1}/${STEPS.length})`;
            }

            // Initial reset
            resetGuideStroke();
        }

        document.addEventListener('DOMContentLoaded', enrollFace);
    </script>
@endpush
@section('content')
<div class="content">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
        <div class="my-auto mb-2">
            <h3 class="mb-1">Enroll Face</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item "><a href="{{ url('/smart-presence') }}">Smart Presence</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><a href="#">Enroll Face</a></li>
                </ol>
            </nav>
        </div>
        <div></div>
        <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                <div class="live-time-container head-icons">
                    <span class="live-time-icon me-2"><i class="fa-thin fa-clock"></i></span>
                    <div class="live-time"></div>
                </div>
            <div class="ms-2 head-icons">
                <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-chevrons-up"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-12">
        {{--************************************************************************************************
        *                                                                                                  *
        *                             >>> MODIFY THIS SECTION (START) <<<                                  *
        *                                                                                                  *
        ************************************************************************************************--}}
        <div class="d-flex flex-column align-items-center justify-content-center text-center w-100" style="height:calc(100vh - 200px) !important;">
            <div data-smart-face-enroll data-name="enrolled_data"></div>
        </div>
        {{--************************************************************************************************
        *                                                                                                  *
        *                             >>> MODIFY THIS SECTION (END) <<<                                    *
        *                                                                                                  *
        ************************************************************************************************--}}
    </div>
</div>
@endsection