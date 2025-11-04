@extends('layouts.empty-app')
@section('title', 'Got It :: Onboarding - Biometric Device Setup')
@section('top-style')
    <style>
        .loading-div {
            display: none;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            position: relative;
        }
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading-message {
            font-size: 0.9rem;
            color: #333;
            margin-top: 10px;
        }
        .highlight-url {
            background-color: #e9f7ef;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }
        #device-list table {
            font-size: 0.9rem;
        }
        #device-list th, #device-list td {
            padding: 8px;
            text-align: left;
        }
        #device-list .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f9f9f9;
        }
    </style>
@endsection
@section('bottom-script')
    <script>
        window.addEventListener('load', () => {
            // Check if Laravel Echo is loaded
            if (!window.Echo) {
                console.error('Laravel Echo is not loaded. Please check script inclusion and configuration.');
                return;
            }

            const loadingDiv = document.getElementById('loading-animation');
            const deviceListDiv = document.getElementById('device-list');
            const messages = [
                'Make sure the device is connected to the internet.',
                'Please wait while we sync your devices...',
                'Ensure you entered the correct URL.',
                'This may take 1 to 5 minutes.',
                'You can skip this step if needed.',
                'Checking device compatibility with ADMS server...',
                'Verifying network stability for seamless sync...',
                'Syncing biometric data securely with HTTPS...',
                'Almost there, connecting to the server...',
                'Ensure devices are powered on during sync.'
            ];
            let messageIndex = 0;
            let devices = []; // Cache devices locally
            let loadingInterval = null;

            function showLoading() {
                if (loadingDiv.style.display !== 'block') {
                    loadingDiv.style.display = 'block';
                    loadingInterval = setInterval(() => {
                        document.getElementById('loading-message').textContent = messages[messageIndex];
                        messageIndex = (messageIndex + 1) % messages.length;
                    }, 3000);
                }
            }

            function hideLoading() {
                if (loadingInterval) {
                    clearInterval(loadingInterval);
                    loadingInterval = null;
                }
                loadingDiv.style.display = 'none';
            }

            function updateDeviceList(newDevices, deviceCount) {
                devices = newDevices; // Update local cache
                deviceListDiv.innerHTML = ''; // Clear existing content
                if (devices.length === 0) {
                    deviceListDiv.innerHTML = '<p class="text-muted">No devices synced yet.</p>';
                    return;
                }

                const table = document.createElement('table');
                table.className = 'table table-bordered table-striped mt-3';
                table.innerHTML = `
                    <thead>
                        <tr>
                            <th>Device Name</th>
                            <th>Serial Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${devices.map(device => `
                            <tr>
                                <td>${device.DeviceName || 'N/A'}</td>
                                <td>${device.SerialNumber || device.slno || 'N/A'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                `;
                deviceListDiv.appendChild(table);

                if (devices.length >= deviceCount) {
                    hideLoading();
                    // alert('All devices synced successfully!');
                }
            }

            async function fetchDeviceInfo() {
                try {
                    const response = await axios.post('/g/onboarding/devices', {
                        onboarding_id: '{{ $onboarding->onboarding_id }}'
                    }, {
                        headers: {
                            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    console.log('Fetched Device Info:', response.data);
                    const { devices, device_count } = response.data;
                    if (!devices || !Array.isArray(devices)) {
                        console.error('Invalid device data received:', response.data);
                        return;
                    }
                    updateDeviceList(devices, device_count);
                } catch (error) {
                    console.error('Error fetching device info:', error.response?.data?.error || error.message);
                }
            }

            document.querySelector('.initiate-sync-btn')?.addEventListener('click', () => {
                showLoading();
                fetchDeviceInfo(); // Initial fetch on button click
            });

            // Broadcasting listener
            window.Echo.channel('device-compatibility-check.{{ $onboarding->onboarding_id }}')
                .listen('.deviceCompatibilityCheck', (e) => {
                    // Log broadcasted values
                    console.log('Broadcasted DeviceCompatibilityCheck Event:', {
                        deviceCount: e.deviceCount,
                        syncedDevices: e.syncedDevices,
                        latestDevice: e.latestDevice
                    });

                    const { deviceCount, syncedDevices } = e;
                    const message = `Synced ${syncedDevices} of ${deviceCount} devices.`;
                    document.getElementById('loading-message').textContent = message;

                    // Trigger Axios request on broadcast
                    fetchDeviceInfo();
                });
                fetchDeviceInfo();
        });
    </script>
@endsection
@section('content')
    @php
        $deviceCode = $onboarding->device_code ?? 'BIZ001';
        $deviceUrl = url('/') . '/dc/' . ($onboarding->device_code ?? 'BIZ001');
    @endphp
    <main class="empty-main">
        <section class="support-section py-5" data-aos="fade-up">
            <div class="container">
                <div class="row g-4">
                    <div class="col-lg-12">
                        <div class="section-title">
                            <h2 class="white-line">Configure Your Biometric Devices</h2>
                            <p>Connect one or more biometric devices to the Got It platform using ADMS servers for seamless HR management.</p>
                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif
                        </div>
                        <div class="row justify-content-center" data-aos="fade-up" data-aos-delay="200">
                            <div class="col-lg-8">
                                <div class="card border-0 p-2 rounded-4 shadow-sm">
                                    <div class="card-body text-start">
                                        @if (empty($onboarding->device_count))
                                            <!-- Device Count Input Form -->
                                            <h3 class="card-title mb-4">Enter Number of Devices</h3>
                                            <form method="POST" action="{{ route('onboarding.forms') }}" class="landing-form" data-prevent="n">
                                                @csrf
                                                <input type="hidden" name="save_type" value="device">
                                                <input type="hidden" name="onboarding_id" value="{{ $onboarding->onboarding_id ?? \Illuminate\Support\Str::random(30) }}">
                                                <div class="row g-4">
                                                    <div class="col-12">
                                                        <label class="form-label fw-bold">Number of Devices to Sync</label>
                                                        <input type="number" name="device_count" class="form-control" placeholder="Enter number of devices" required min="1" max="100">
                                                        @error('device_count')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="col-12 text-center">
                                                        <button type="submit" class="btn btn-primary rounded-pill">Set Device Count</button>
                                                    </div>
                                                </div>
                                            </form>
                                        @else
                                            <!-- Device Setup Instructions -->
                                            <h3 class="card-title mb-4">Device Setup Instructions</h3>
                                            <p class="fst-italic mb-4">Use the unique code below to link your {{ $onboarding->device_count }} biometric device(s) to our ADMS-compatible server.</p>
                                            <div class="row g-4">
                                                <div class="col-12">
                                                    <label class="form-label fw-bold">Your Device Code</label>
                                                    <p class="bg-light p-2 rounded-2 font-monospace fw-bold small mb-2">{{ $deviceCode }}</p>
                                                    <p>Enter this URL in your devices: <a href="{{ $deviceUrl }}" class="highlight-url" target="_blank">{{ $deviceUrl }}</a></p>
                                                </div>
                                                <div class="col-12">
                                                    <div class="row">
                                                        <div class="col-sm-6 text-start"><label class="form-label fw-bold">Quick Setup Steps</label></div>
                                                        <div class="col-sm-6 text-end sf-12"><a href="#" type="button" class="text-primary" data-bs-toggle="modal" data-bs-target="#detailedInstructionsModal">View Detailed Instructions</a></div>
                                                    </div>
                                                    <ol class="mb-3">
                                                        <li>Access each device's menu.</li>
                                                        <li>Navigate to Communication settings.</li>
                                                        <li>Enable Cloud Sync with ADMS (HTTPS).</li>
                                                        <li>After restart, return to Cloud Sync settings.</li>
                                                        <li>Enter the URL: <span class="highlight-url">{{ $deviceUrl }}</span>.</li>
                                                        <li>Verify Wi-Fi or LAN connection.</li>
                                                        <li>Click Connect on each device.</li>
                                                    </ol>
                                                    <div class="text-center">
                                                        <button class="action-btn initiate-sync-btn">Initiate Device Sync</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Loading Animation Div -->
                                            <div id="loading-animation" class="loading-div mt-4">
                                                <div class="loading-spinner"></div>
                                                <p id="loading-message">Initializing device sync...</p>
                                                <button type="button" class="btn btn-warning btn-sm rounded-pill mt-3 px-3" onclick="window.location.href='{{ route('onboarding.type', ['type' => 'device']) }}'">Cancle</button>
                                            </div>
                                            <div id="device-list" class="mt-4"></div>
                                            <div class="col-12 d-flex justify-content-between mt-5">
                                                <a href="{{ route('onboarding.type', ['type' => 'business']) }}" data-loading-text="Going Back..." class="btn btn-secondary rounded-pill px-3">Go Back</a>
                                                <a href="{{ route('onboarding.type', ['type' => 'payment']) }}" data-loading-text="Processing..." class="btn btn-primary rounded-pill">Continue</a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Support and Query Section -->
                    <div class="row g-4 mt-5">
                        <div class="col-lg-6 text-center">
                            <h2 class="section-title text-white">Need Assistance?</h2>
                            <p class="text-white mb-3">Our support team is here to assist with ADMS-compatible biometric device setup!</p>
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <i class="bi bi-telephone-fill me-3"></i>
                                <p class="mb-0"><strong>Call:</strong> <a href="tel:+919030990395" class="text-white">+91 90309 90395</a></p>
                            </div>
                            <div class="d-flex justify-content-center align-items-center mb-3">
                                <i class="bi bi-envelope-fill me-3"></i>
                                <p class="mb-0"><strong>Email:</strong> <a href="mailto:info@gotit4all.com" class="text-white">info@gotit4all.com</a></p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <h2 class="section-title text-white">Ask Us Anything</h2>
                            <form method="POST" action="{{ route('landing.forms') }}" class="landing-form">
                                @csrf
                                <input type="hidden" name="save_type" value="faqs">
                                <input type="hidden" name="category" value="onboarding-device-query">
                                <input type="hidden" name="sub_category" value="-">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <input type="text" name="name" class="form-control" placeholder="Your Name" value="{{ old('name') }}" required pattern="^[A-Za-z\s]{3,50}$" title="Only letters and spaces, 3-50 characters.">
                                        @error('name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <input type="email" name="email" class="form-control" placeholder="Your Email" value="{{ old('email') }}" required pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$" title="Enter a valid email address.">
                                        @error('email')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <textarea name="message" class="form-control" rows="4" placeholder="Your Question About Device Setup" required pattern=".{10,500}" title="Message should be 10 to 500 characters.">{{ old('message') }}</textarea>
                                        @error('message')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-primary">Send Query</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Abstract Background Elements -->
                <div class="shape shape-1">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <path d="M41.3,-49.1C54.4,-39.3,66.6,-27.2,71.1,-12.1C75.6,3,72.4,20.9,63.3,34.4C54.2,47.9,39.2,56.9,23.2,62.3C7.1,67.7,-10,69.4,-24.8,64.1C-39.7,58.8,-52.3,46.5,-60.1,31.5C-67.9,16.4,-70.9,-1.4,-66.3,-16.6C-61.8,-31.8,-49.7,-44.3,-36.3,-54C-22.9,-63.7,-8.2,-70.6,3.6,-75.1C15.4,-79.6,28.2,-58.9,41.3,-49.1Z" transform="translate(100 100)" fill="#ffffff"></path>
                    </svg>
                </div>
            </section>
            <!-- Detailed Instructions Modal -->
            <div class="modal fade" id="detailedInstructionsModal" tabindex="-1" aria-labelledby="detailedInstructionsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="detailedInstructionsModalLabel">Detailed Biometric Device Setup Instructions for ADMS</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="alert alert-warning mb-4" role="alert">
                                <strong>Important:</strong> Follow each step carefully to ensure successful connection to our ADMS-compatible server. Contact <a href="mailto:info@gotit4all.com">info@gotit4all.com</a> or call +91 90309 90395 for assistance.
                            </div>
                            <div class="p-4 bg-light rounded-3 mb-4">
                                <p class="fw-bold text-dark mb-2">Step 1: Access the Device Menu</p>
                                <p>Power on each biometric device and access the main menu by pressing the <strong>"Menu"</strong> or <strong>"Settings"</strong> button. Ensure devices are charged or connected to power to prevent interruptions during setup.</p>
                                <div class="p-3 bg-success-subtle border-start border-4 border-success rounded mb-4">
                                    <strong>Note:</strong> Some devices may prompt for an admin password. Refer to your ADMS device manual for default credentials.
                                </div>
                                <p>Your device menu</p>
                            </div>
                            <div class="p-4 bg-light rounded-3 mb-4">
                                <p class="fw-bold text-dark mb-2">Step 2: Navigate to Communication Settings</p>
                                <p>From the main menu, select <strong>Communication</strong> or <strong>Network</strong> to configure server connectivity for attendance data transfer via ADMS.</p>
                                <div class="alert alert-info mb-4" role="alert">
                                    <strong>Tip:</strong> Look for a network or globe icon if the device uses a graphical interface.
                                </div>
                                <p>Your communication settings</p>
                            </div>
                            <div class="p-4 bg-light rounded-3 mb-4">
                                <p class="fw-bold text-dark mb-2">Step 3: Enable Cloud Sync with ADMS</p>
                                <p>Locate the <strong>Cloud Sync</strong> or <strong>ADMS Server</strong> setting. Enable <strong>HTTPS Requests</strong> for secure communication with our ADMS server. <strong>Warning:</strong> <em>Disabling HTTPS will cause connection failures and compromise security.</em> The device will restart after enabling.</p>
                                <div class="alert alert-danger mb-4" role="alert">
                                    <strong>Warning:</strong> Do not power off or interrupt the device during restart to avoid configuration errors.
                                </div>
                                <p>Your cloud sync settings</p>
                            </div>
                            <div class="p-4 bg-light rounded-3 mb-4">
                                <p class="fw-bold text-dark mb-2">Step 4: Return to Cloud Sync Settings</p>
                                <p>After restart, navigate back to <strong>Cloud Sync</strong> or <strong>ADMS Server</strong> settings to proceed with URL entry.</p>
                                <div class="p-3 bg-success-subtle border-start border-4 border-success rounded mb-4">
                                    <strong>Note:</strong> Manually return to Communication if the device does not redirect automatically.
                                </div>
                                <p>Your cloud sync settings after restart</p>
                            </div>
                            <div class="p-4 bg-light rounded-3 mb-4">
                                <p class="fw-bold text-dark mb-2">Step 5: Enter the Server URL</p>
                                <p>In the <strong>Domain Address</strong> or <strong>Server URL</strong> field, enter: <span class="highlight-url">{{ $deviceUrl }}</span>. <strong>Warning:</strong> <em>Verify the URL for accuracy; typos will prevent ADMS server connection.</em></p>
                                <div class="alert alert-warning mb-4" role="alert">
                                    <strong>Warning:</strong> Include "https://" and the exact device code as shown.
                                </div>
                                <p>Your server URL input</p>
                            </div>
                            <div class="p-4 bg-light rounded-3 mb-4">
                                <p class="fw-bold text-dark mb-2">Step 6: Verify Internet Connection</p>
                                <p>Ensure each device is connected via <strong>Wi-Fi</strong> or <strong>LAN</strong> with a stable internet connection. <strong>Warning:</strong> <em>Unstable connections may disrupt ADMS syncing.</em></p>
                                <div class="alert alert-info mb-4" role="alert">
                                    <strong>Tip:</strong> Use the device's network test feature or check the status indicator to confirm connectivity.
                                </div>
                                <p>Your internet connection settings</p>
                            </div>
                            <div class="p-4 bg-light rounded-3 mb-4">
                                <p class="fw-bold text-dark mb-2">Step 7: Connect to the ADMS Server</p>
                                <p>Press <strong>Connect</strong> on each device to initiate syncing with our ADMS server. Wait a few moments for confirmation. <strong>Note:</strong> Do not interrupt the sync process.</p>
                                <div class="p-3 bg-success-subtle border-start border-4 border-success rounded mb-4">
                                    <strong>Note:</strong> If syncing fails, recheck the URL, internet connection, and HTTPS settings, then retry.
                                </div>
                                <p>Your connect button</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    @endsection