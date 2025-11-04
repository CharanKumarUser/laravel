/**
 * Skeleton class for managing application-specific functionality.
 * Relies on window.general for utility functions like logging, toasts, and Axios helpers.
 */
import 'daterangepicker';
import Sortable from 'sortablejs';
import { editor } from './editor.js';
import { template } from './template.js'; // Added template import
import { formBuilder, renderForm } from './form-builder.js'; // Added template import
import { generateDataTableLoading, initializeDataTable, reloadTable } from './table.js';
import { generateCardLoading, initializeCardSet, reloadCard } from './card.js';
import { Geofence } from './geofence.js';
import { permissions } from './permissions.js';
import { datePicker } from './date.js';
import { path, tree } from './tree.js';
import { shift, shiftSchedule } from './shift.js';
import { code } from './code.js';
import { drag } from './drag.js';
import { charts } from './charts.js';
import { faceEnroll } from './enrollface.js'; // Example additional module

class Skeleton {
    constructor() {
        // Configuration properties
        this.changedFields = new Set(); // Tracks changed form fields
        this.dataTableMap = new Map(); // Stores DataTable instances
        this.cardSetMap = new Map(); // Stores card set instances
        this.maxRetries = 2; // Max retries for failed requests
        this.retryDelay = 1000; // Delay between retries (ms)
        this.popoverDelay = 2000; // Popover display delay in ms

        // Bind methods to ensure proper context

        this.editor = editor.bind(this);
        this.formBuilder = formBuilder.bind(this); // Added template binding
        this.renderForm = renderForm.bind(this); // Added template binding
        this.template = template.bind(this); // Added template binding
        this.geofence = Geofence.bind(this);
        this.generateDataTableLoading = generateDataTableLoading.bind(this);
        this.initializeDataTable = initializeDataTable.bind(this);
        this.reloadTable = reloadTable.bind(this);
        this.generateCardLoading = generateCardLoading.bind(this);
        this.initializeCardSet = initializeCardSet.bind(this);
        this.reloadCard = reloadCard.bind(this);

        this.code = code.bind(this);
        this.drag = drag.bind(this);
        this.path = path.bind(this);
        this.tree = tree.bind(this);
        this.shift = shift.bind(this);
        this.charts = charts.bind(this);
        this.shiftSchedule = shiftSchedule.bind(this);
        this.datePicker = datePicker.bind(this);
        this.permissions = permissions.bind(this);
        this.faceEnroll = faceEnroll.bind(this);
    }

    /**
     * Initializes application-specific components
     */
    async init() {
        try {
            // Dependency checks
            if (!window.axios) throw new Error('Axios is required but not loaded');
            if (!window.jQuery) throw new Error('jQuery is required but not loaded');
            if (!jQuery.fn.DataTable) throw new Error('DataTables is required but not loaded');
            if (!window.Tagify) throw new Error('Tagify is required but not loaded');
            if (!window.bootstrap) throw new Error('Bootstrap is required but not loaded');
            if (!window.Quill) throw new Error('Quill is required but not loaded');
            if (!window.Cleave) throw new Error('Cleave.js is required but not loaded');
            if (!window.general) throw new Error('General class is required but not loaded');
            if (!Sortable) throw new Error('Sortable.js is required but not loaded');
            // if (!window.grapesjs) throw new Error('GrapesJS is required but not loaded');

            // Initialize components
            await Promise.all([
                this.initializeDataTable(),
                this.initializeCardSet()
            ]);
            window.general.log('Skeleton initialized successfully');
        } catch (e) {
            (window.general?.error || console.error)('Skeleton initialization error:', e);
        }
    }

    /**
     * Reloads the Skeleton application
     */
    async reloadSkeleton() {
        const btn = document.querySelector('.reload-skeleton');
        const icon = btn?.querySelector('i');
        if (!btn || !icon) {
            window.general?.error('Reload button or icon not found');
            return;
        }
        try {
            icon.classList.add('ti-reload', 'fa-spin');
            btn.disabled = true;
            const response = await window.general.axiosRequest({
                method: 'get',
                url: '/reload-skeleton'
            });
            const { data } = response;
            window.general.showToast({
                icon: data?.status ? 'success' : 'error',
                title: data?.title || (data?.status ? 'Success' : 'Error'),
                message: data?.message || 'Reload response incomplete.',
                duration: 5000
            });
            if (data?.status) {
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        } catch (error) {
            const errorMessage = error?.response?.data?.message || 'An unexpected error occurred during reload.';
            const errorTitle = error?.response?.data?.title || 'Reload Failed';
            window.general.showToast({
                icon: 'error',
                title: errorTitle,
                message: errorMessage,
                duration: 5000
            });
            window.general?.error?.('Reload failed:', error);
        } finally {
            icon.classList.remove('fa-spin');
            btn.disabled = false;
        }
    }
}

try {
    window.skeleton = new Skeleton();
    window.skeleton.init();
} catch (e) {
    (window.general?.error || console.error)('Failed to initialize Skeleton:', e);
}

// Event listeners for reload actions
document.addEventListener('click', (e) => {
    if (e.target.closest('.reload-skeleton')) {
        e.preventDefault();
        window.skeleton.reloadSkeleton();
    }
    if (e.target.closest('.reload-card')) {
        e.preventDefault();
        const token = e.target.closest('.reload-card').dataset.cardToken;
        if (token) {
            window.skeleton.reloadCardSet(token);
        } else {
            window.general?.errorToast?.('No card token provided for reload');
            window.general?.log?.('No card token provided for reload');
        }
    }
});

export default Skeleton;