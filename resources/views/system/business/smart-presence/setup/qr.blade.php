@extends('layouts.system-app')
@section('title', 'Qr')
@push('styles') 
    <style>
        .qr-container {
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        #qr-code {
            display: block;
            margin: 0 auto;
        }
        .text-danger {
            color: #dc3545;
        }
    </style>
@endpush  
@push('pre-scripts') 
    <!-- QRCode.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
    <script>
        // Function to generate a random token
        function generateToken(length = 30) {
            const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let token = '';
            for (let i = 0; i < length; i++) {
                token += characters.charAt(Math.floor(Math.random() * characters.length));
            }
            return token;
        }

        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('qr-code');
            const timestamp = document.getElementById('timestamp');

            if (!canvas) {
                console.error('QR code canvas not found');
                return;
            }

            // Function to generate and save QR code
            function generateQRCode() {
                const token = generateToken();
                const time = Date.now();
                const baseUrl = '{{ url("/t/smart-presence/qr") }}';
                const qrUrl   = baseUrl + '/token=' + encodeURIComponent(token) + '&time=' + time;
                const saveUrl = qrUrl + '&save=1'; // same route, extra flag for AJAX

                // Generate QR in canvas
                QRCode.toCanvas(canvas, qrUrl, { width: 300, margin: 2 }, (err) => {
                    if (err) {
                        console.error('QR generation failed:', err);
                        canvas.insertAdjacentHTML("afterend", '<p class="text-danger">QR generation failed</p>');
                        return;
                    }
                    timestamp.textContent = new Date().toLocaleString();
                    console.log("Generated QR:", qrUrl);
 
                    // AJAX request to save token
                    fetch(saveUrl)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Token saved successfully');
                            } else {
                                console.error('Failed to save token:', data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error saving token:', error);
                        });
                });
            }

            // Generate immediately and then repeat every 10 seconds
            generateQRCode();
            setInterval(generateQRCode, 60000);
        });
    </script>
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Qr</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/smart-presence') }}">Smart Presence</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/smart-presence/setup') }}">Setup</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Qr</a></li>
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
            <div class="d-flex flex-column align-items-center justify-content-center text-center w-100 qr-container">
                <h1 class="h3 mb-2 fw-bold">Dynamic QR Code</h1>
                <p class="text-muted mb-2" style="max-width: 600px;">
                    Scan the QR code below to validate attendance. The code refreshes every 10 seconds.
                </p>
                <canvas id="qr-code"></canvas>
                <p class="text-muted mt-2">Last updated: <span id="timestamp"></span></p>
                <div class="d-flex flex-wrap justify-content-center gap-3 mt-2">
                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary rounded-pill">Go Back</a>
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary rounded-pill">Explore Dashboard</a>
                </div>
            </div>
        </div>
    </div>
@endsection
