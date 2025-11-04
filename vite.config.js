import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import fs from 'fs';
import path from 'path';

export default defineConfig(({ mode }) => {
    // Load environment variables
    const env = loadEnv(mode, process.cwd(), '');

    // Developer flags
    const DEV_MODE = env.DEVELOPER_MODE === 'development';
    const VERBOSE_LOGS = env.DEVELOPER_VERBOSE_LOGS === 'true';
    const PERF_PROFILING = env.DEVELOPER_PERF_PROFILING === 'true';

    // Server configuration
    const ENABLE_HMR = env.VITE_ENABLE_HMR === 'true';
    const USE_HTTPS = env.VITE_USE_HTTPS === 'true';
    const USE_POLLING = env.VITE_USE_POLLING === 'true';
    const DEV_HOST = env.VITE_DEV_HOST || '192.168.1.103';
    const DEV_PORT = Number(env.VITE_DEV_PORT) || 5173;

    // HTTPS configuration
    let httpsConfig = false;
    if (USE_HTTPS) {
        const keyPath = env.VITE_SSL_KEY || path.resolve(process.cwd(), '192.168.1.103+1-key.pem');
        const certPath = env.VITE_SSL_CERT || path.resolve(process.cwd(), '192.168.1.103+1.pem');

        if (fs.existsSync(keyPath) && fs.existsSync(certPath)) {
            httpsConfig = {
                key: fs.readFileSync(keyPath),
                cert: fs.readFileSync(certPath),
            };
        } else {
            console.warn('SSL certificates not found. Falling back to default self-signed.');
            httpsConfig = true;
        }
    }

    return {
        // Dev server configuration
        server: ENABLE_HMR
            ? {
                  host: DEV_HOST,
                  port: DEV_PORT,
                  strictPort: true,
                  https: httpsConfig,
                  watch: { usePolling: USE_POLLING },
                  cors: true, // Allow CORS for Laravel origin
                  hmr: {
                      host: DEV_HOST,
                      protocol: USE_HTTPS ? 'wss' : 'ws',
                  },
              }
            : false,

        // Build configuration
        build: {
            outDir: 'public/build',
            sourcemap: DEV_MODE,
            minify: mode === 'production' ? 'esbuild' : false,
            rollupOptions: {
                output: {
                    manualChunks: {
                        human: ['@vladmandic/human'],
                        axios: ['axios'],
                    },
                },
            },
            chunkSizeWarningLimit: PERF_PROFILING ? 800 : 1500,
        },

        // Dependency optimization
        optimizeDeps: {
            include: ['@vladmandic/human', 'axios'],
            esbuildOptions: { target: 'es2020' },
        },

        // Global constants
        define: {
            __DEV__: DEV_MODE,
            __VERBOSE__: VERBOSE_LOGS,
            __PERF__: PERF_PROFILING,
        },

        // Laravel Vite plugin
        plugins: [
            laravel({
                input: [
                    // General assets
                    'resources/css/general/general.css',
                    'resources/js/general/index.css',

                    // Public/landing pages
                    'resources/css/lander.css',
                    'resources/js/lander.js',

                    // Authenticated core system
                    'resources/css/system.css',
                    'resources/js/system.js',

                    // Real-time features
                    'resources/css/realtime/smart.css',
                    'resources/js/realtime/notifications.js',
                    'resources/js/realtime/search.js',
                    'resources/js/realtime/smart.js',
                    'resources/js/realtime/smart-qr.js',
                    'resources/js/realtime/smart-bar.js',

                    // Page-specific assets
                    'resources/css/page/index.css',
                    'resources/js/page/index.js',
                    'resources/js/page/calendar.js',
                ],
                refresh: ENABLE_HMR,
            }),
        ],
    };
});
