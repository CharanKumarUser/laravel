/*!
 * Skeleton Pack - Minified JS
 * Created by ChronoSpark Solutions
 * Website: https://chronosparksolutions.com/
 * Version: 2.0.0
 * Description: A lightweight, optimized JS bundle with eager loading, modern data structures, full async/await support, and robust exception handling.
 * Author: ChronoSpark Solutions Team
 * License: MIT
 * Last Updated: September 12, 2025
 * 
 * © 2025 ChronoSpark Solutions. All rights reserved.
 * "Where Time Meets Technology"
 *
 * General utility class for managing frontend functionalities, including Axios requests, form handling,
 * modals, toasts, and various UI interactions. Provides robust error handling, modern JavaScript practices,
 * and support for concurrent operations.
 * Globally accessible via `window.general`.
 * @class
 */
// Eager loading of all dependencies
import $ from 'jquery';
import axios from 'axios';
import moment from 'moment';
import * as bootstrap from 'bootstrap';
import '@popperjs/core';
import PureCounter from "@srexi/purecounterjs";
import select2 from 'select2';
import 'datatables.net';
import 'datatables.net-bs5';
import Tagify from '@yaireo/tagify';
import Sortable from 'sortablejs';
import interact from 'interactjs';
import Cleave from 'cleave.js';
import 'cleave.js/dist/addons/cleave-phone.in';
import Quill from 'quill';
import confetti from 'canvas-confetti';
import AOS from 'aos';
import Dropzone from "dropzone";
import Croppie from "croppie";
import "dropzone/dist/dropzone.css";
import "croppie/croppie.css";
import '../../libs/alerts/css-toast/css-toast.min.js';
import Swal from 'sweetalert2';
// Eager load validation rules using fetch (async, but initialized early)
let validationRules = [];
import('./validation-rules.json')
  .then((module) => {
    validationRules = Array.isArray(module.default) ? module.default : [];
  })
  .catch((error) => {
    console.error('Failed to load validation rules:', error);
  });
Dropzone.autoDiscover = false;
class General {
  /**
   * Initializes the General utility class with configuration and dependencies.
   * Uses modern data structures like Map and Set for better performance.
   * @constructor
   */
  constructor() {
    // Configuration properties with modern defaults
    this.developerMode = true; // Enables debug logging
    this.baseUrl = window.location.origin; // Base URL for API requests
    this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || ''; // CSRF token
    this.modal = document.querySelector('meta[name="modal"]')?.content || 'lander'; // Modal type
    this.lastClickTime = 0; // Tracks last click time for rate limiting
    this.clickDelay = 3000; // Delay between clicks (ms)
    this.maxConcurrentRequests = 5; // Max concurrent Axios requests
    this.activeRequests = 0; // Current active requests (use Atomic-like counter if needed)
    this.requestQueue = []; // Queue for pending requests (Array for FIFO)
    this.cancelTokens = new Map(); // Map of Axios cancel tokens
    this.toastCache = new Map(); // Cache to prevent duplicate toasts (Map for O(1) lookup)
    this.toastTimeout = 10000; // Toast cache duration (ms)
    this.requestTimeout = 60000; // Axios request timeout (ms)
    this.debounceDelay = 300; // Debounce delay for functions (ms)
    this.emptyValue = '-'; // Default value for empty inputs
    this.retryAttempts = 2; // Increased retry attempts for robustness
    this.retryDelay = 1000; // Delay between retries (ms)
    this.cacheDurationMinutes = 5; // Cache duration for data
    this.currentForm = null; // Current form being validated
    // Croppie state
    this.isCroppieOpen = false;
    this.currentKey = '';
    this.croppieInst = null;
    // Modern data structures for concurrent operations
    this.concurrentTasks = new Set(); // Track simultaneous async tasks
    this.debouncedFunctions = new WeakMap(); // WeakMap for debounced funcs to avoid memory leaks
    this.alertCache = new Map(); // Cache for alerts to prevent duplicates
    // Attach dependencies to window for global access with error handling
    this.initializeDependencies();
    // Bind methods to ensure proper context
    this.bindMethods();
    // Initialize utilities and addons asynchronously
    this.init().catch(error => {
      this.error('General initialization error:', error);
      this.errorToast('Initialization Error', 'Failed to initialize utilities');
    });
  }
  /**
   * Attaches dependencies to the global window object for compatibility with full exception handling.
   * @private
   */
  initializeDependencies() {
    try {
      window.$ = window.jQuery = $;
      window.axios = axios;
      window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
      window.bootstrap = bootstrap;
      select2(window.jQuery);
      window.Swal = Swal;
      window.Quill = Quill;
      window.Cleave = Cleave;
      window.Tagify = Tagify;
      window.moment = moment;
      this.log('Dependencies initialized successfully');
    } catch (error) {
      this.error('Dependency initialization error:', error);
      throw error; // Re-throw to halt if critical
    }
  }
  /**
   * Binds methods to ensure correct `this` context using modern bind.
   * @private
   */
  bindMethods() {
    const methods = [
      'init', 'log', 'error', 'updateLiveClock', 'manageCookie',
      'sanitizeInput', 'isEmpty', 'debounce', 'canProceed', 'modifyToken',
      'requestAction', 'setupAxiosInterceptors', 'processQueue', 'generateFormClass',
      'setupEventListeners', 'showForm', 'saveForm',
      'handleSaveSuccess', 'makeDraggable', 'makeResizableModal', 'makeResizableOffcanvas',
      'setupFullscreenToggle', 'setupDownloadShareButtons', 'setupFormCookieStorage', 'clearCookies',
      'setupReloadButton', 'validateForm', 'tooltip', 'popover', 'actions',
      'manageTabState', 'configureToast', 'showToast', 'getToastStyles', 'stepper', 'repeater',
      'successToast', 'errorToast', 'warningToast', 'axiosRequest', 'errorDiv400', 'errorDiv500', 'fullscreen',
      'errorDivEmpty', 'clone', 'copy', 'unique', 'select', 'pills', 'files', 'confirmCroppie', 'validateSize',
      'getAcceptedMimes', 'setInputFile', 'showAlert', 'getAlertStyles', 'successAlert',
      'errorAlert', 'warningAlert', 'infoAlert', 'questionAlert'
    ];
    methods.forEach((method) => {
      if (typeof this[method] === 'function') {
        this[method] = this[method].bind(this);
      }
    });
    this.log('Methods bound successfully');
  }
  /**
   * Initializes core utilities and addons asynchronously with full error handling.
   */
  async init() {
    try {
      // Wait for validation rules if needed
      while (validationRules.length === 0) {
        await new Promise(resolve => setTimeout(resolve, 100));
      }
      // Dependency checks with exceptions
      if (!window.axios) throw new Error('Axios is required but not loaded');
      if (!window.jQuery) throw new Error('jQuery is required but not loaded');
      if (!window.bootstrap) throw new Error('Bootstrap is required but not loaded');
      // Parallel initialization for speed
      await Promise.all([
        this.setupEventListeners(),
        this.setupAxiosInterceptors(),
        this.configureToast(),
        this.updateLiveClock(),
        this.tooltip(),
        this.popover(),
        this.actions(),
        this.copy(),
        this.clone(),
        this.select(),
        this.pills(),
        this.fullscreen(),
        this.unique(),
        this.toggle(),
        this.files(),
        this.repeater(),
        this.stepper()
      ]);
      AOS.init({ once: true });
      const pure = new PureCounter();
      this.togglePassword();
      this.setupLoadingButtons();
      this.log('Initialization completed successfully');
    } catch (error) {
      this.error('General initialization error:', error);
      throw error; // Re-throw for caller handling
    }
  }
  /**
   * Sets up loading animation for elements with data-loading-text attribute.
   * Applies to <button> and <a> elements with event delegation.
   * @private
   */
  setupLoadingButtons() {
    try {
      document.addEventListener('click', (event) => {
        const el = event.target.closest('[data-loading-text]');
        if (!el || el.classList.contains('loading')) return;
        try {
          // Save original content and store it for reset
          const loadingText = el.getAttribute('data-loading-text');
          const originalContent = el.innerHTML;
          el.setAttribute('data-original-content', originalContent);
          // Set loading content with spinner
          el.innerHTML = `${loadingText} <i class="fa fa-refresh fa-spin"></i>`;
          el.classList.add('loading');
          // Disable the element
          if (el.tagName === 'BUTTON') {
            el.disabled = true;
          } else if (el.tagName === 'A') {
            el.style.pointerEvents = 'none';
            el.style.opacity = '0.6';
          }
          this.log('Loading button activated for:', el);
        } catch (error) {
          this.error('Error setting up loading for element:', error, el);
        }
      });
    } catch (error) {
      this.error('Error setting up loading buttons:', error);
    }
  }
  /**
   * Sets up password toggle functionality for inputs with full error handling.
   * @private
   */
  togglePassword() {
    try {
      document.addEventListener('click', (event) => {
        const toggle = event.target.closest('.toggle-password');
        if (!toggle) return;
        try {
          const inputControl = toggle.closest('.float-input-control');
          if (!inputControl) return;
          const input = inputControl.querySelector('.form-float-input');
          if (!input) return;
          const icon = toggle.querySelector('i');
          if (!icon) return;
          icon.classList.toggle('ti-eye');
          icon.classList.toggle('ti-eye-off');
          input.type = input.type === 'password' ? 'text' : 'password';
          this.log('Password toggle activated for input:', input);
        } catch (error) {
          this.error('Error toggling password:', error);
        }
      });
    } catch (error) {
      this.error('Error setting up password toggles:', error);
    }
  }
  /**
   * Sets up fullscreen toggle functionality for divs while preserving styles with async handling.
   * @private
   */
  fullscreen() {
    if (!document.fullscreenEnabled) {
      this.log('Fullscreen API is not supported in this browser.');
      return;
    }
    try {
      document.querySelectorAll('[data-full-screen]').forEach(button => {
        const selector = button.getAttribute('data-full-screen');
        const target = document.querySelector(selector);
        if (!target) {
          this.error(`No element found for selector: "${selector}"`);
          return;
        }
        const icon = button.querySelector('i');
        // Store original styles to restore them later using WeakMap for GC
        const originalStyles = new WeakMap();
        originalStyles.set(target, {
          style: target.getAttribute('style'),
          className: target.className
        });
        const toggleIcon = (isFullscreen) => {
          if (!icon) return;
          icon.classList.toggle('ti-maximize', !isFullscreen);
          icon.classList.toggle('ti-minimize', isFullscreen);
        };
        const toggleFullscreen = async () => {
          try {
            if (document.fullscreenElement === target) {
              await document.exitFullscreen();
              // Restore original styles
              const styles = originalStyles.get(target);
              if (styles.style) {
                target.setAttribute('style', styles.style);
              } else {
                target.removeAttribute('style');
              }
              target.className = styles.className;
              toggleIcon(false);
            } else {
              // Store current styles before entering fullscreen
              originalStyles.set(target, {
                style: target.getAttribute('style'),
                className: target.className
              });
              await target.requestFullscreen();
              // Ensure styles are preserved in fullscreen
              const computedStyles = window.getComputedStyle(target);
              const styleProperties = [
                'background', 'color', 'font', 'padding', 'margin',
                'border', 'width', 'height', 'display', 'position'
              ];
              let fullscreenStyles = '';
              styleProperties.forEach(prop => {
                const value = computedStyles.getPropertyValue(prop);
                if (value) fullscreenStyles += `${prop}: ${value}; `;
              });
              target.setAttribute('style', fullscreenStyles);
              target.className = originalStyles.get(target).className; // Preserve classes
              toggleIcon(true);
            }
          } catch (error) {
            this.error('Error toggling fullscreen:', error);
          }
        };
        button.addEventListener('click', toggleFullscreen);
        // Handle fullscreen exit (e.g., via Esc key)
        document.addEventListener('fullscreenchange', () => {
          if (document.fullscreenElement !== target) {
            // Restore original styles when exiting fullscreen
            const styles = originalStyles.get(target);
            if (styles.style) {
              target.setAttribute('style', styles.style);
            } else {
              target.removeAttribute('style');
            }
            target.className = styles.className;
            toggleIcon(false);
          }
        });
        this.log('Fullscreen toggle set up for:', selector);
      });
    } catch (error) {
      this.error('Error setting up fullscreen toggles:', error);
    }
  }
  /**
   * Initializes Select2 for <select> elements with the [data-select] attribute.
   * Enhanced with async fetching and full error handling.
   */
 async select() {
  try {
    const selects = document.querySelectorAll('select[data-select]');
    if (!selects.length) return;
    if (!window.jQuery || !window.jQuery.fn.select2) {
      throw new Error('jQuery or Select2 is required but not loaded');
    }
    const $ = window.jQuery;

    /** Debounce utility using WeakMap for instances */
    const debounce = (fn, delay) => {
      let timeout;
      const debounced = (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn.apply(this, args), delay);
      };
      this.debouncedFunctions = this.debouncedFunctions || new WeakMap();
      this.debouncedFunctions.set(fn, debounced);
      return debounced;
    };

    /** Parse preselected values safely */
    const parseDataValue = value => {
      if (!value && value !== 0) return [];
      if (Array.isArray(value)) return value;
      if (typeof value === 'number') return [String(value)];
      if (typeof value !== 'string') return [String(value)];
      let cleaned = value.trim();
      try {
        const parsed = JSON.parse(cleaned);
        return Array.isArray(parsed) ? parsed : [parsed];
      } catch {
        return cleaned.replace(/[\[\]"]/g, '').split(',').map(v => v.trim()).filter(Boolean);
      }
    };

    /** Toggle loading spinner & dropdown loading class */
    const toggleLoading = ($el, on, placeholderText = 'Loading…') => {
      const s2 = $el.data('select2');
      if (on) {
        $el.data('loading', true);
        if (!$el.find('option[value=""]').length) {
          $el.prepend($('<option/>', { value: '', text: placeholderText, disabled: true }));
        } else {
          $el.find('option[value=""]').text(placeholderText);
        }
        if (s2?.$container) s2.$container.addClass('s2-loading');
        if (s2?.$dropdown) s2.$dropdown.addClass('s2-loading');
      } else {
        $el.data('loading', false);
        $el.find('option[value=""]').remove();
        if (s2?.$container) s2.$container.removeClass('s2-loading');
        if (s2?.$dropdown) s2.$dropdown.removeClass('s2-loading');
      }
    };

    /** Custom rendering for options & selection */
    const buildFormatter = ($ownerEl) => {
      return function formatOption(opt) {
        if (!opt.id || opt.loading) {
          if ($ownerEl.data('loading')) {
            return $('<span class="s2-loading-row"><span class="s2-loading-dot"></span><span>Loading…</span></span>');
          }
          return opt.text;
        }

        const $optEl = $(opt.element);
        const avatar = $optEl.data('avatar');
        const group = $optEl.data('group');
        const uid = $optEl.data('id');
        const name = opt.text || '';

        if (!avatar && !group && !uid) return $('<span>' + name + '</span>');

        let avatarHtml = '';
        if (avatar) {
          avatarHtml = `<img src="${avatar}" alt="${name}" class="select2-user-avatar"
            onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block';" />`;
        } else {
          const initials = name
            .split(' ')
            .filter(Boolean)
            .map(w => w[0].toUpperCase())
            .slice(0, 2)
            .join('');
          const gradients = [
            ['#FF6B6B', '#FFD93D'],
            ['#6BCB77', '#4D96FF'],
            ['#FF7B00', '#FFB200'],
            ['#845EC2', '#FF9671'],
            ['#00C9A7', '#92FE9D'],
            ['#FF6CAB', '#7366FF']
          ];
          const randomGradient = gradients[Math.floor(Math.random() * gradients.length)];
          const gradientStyle = `background: linear-gradient(135deg, ${randomGradient[0]}, ${randomGradient[1]});`;
          avatarHtml = `
            <div class="select2-default-avatar" style="${gradientStyle}">
              <span>${initials}</span>
            </div>
          `;
        }

        return $(`
          <div class="select2-user-option">
            ${avatarHtml}
            <div class="select2-user-info">
              <span class="select2-user-name">${name}</span>
              ${
                (group || uid)
                  ? `<span class="select2-user-meta">${group || ''}${group && uid ? ' • ' : ''}<i>${uid || ''}</i></span>`
                  : ''
              }
            </div>
          </div>
        `);
      };
    };

    /** Initialize a Select2 element with all behaviors */
    const initSelect2 = ($el, optionsList, context) => {
      if ($el.hasClass('select2-hidden-accessible')) {
        try { $el.select2('destroy'); } catch { }
      }
      if (!$el.prop('multiple') && !$el.find('option[value=""]').length) {
        $el.prepend($('<option/>', { value: '', text: 'Select an option', disabled: true }));
      }
      const formatOption = buildFormatter($el);
      const placeholder = $el.prop('multiple') ? ($el.data('multiple-placeholder') || '') : ($el.data('single-placeholder') || 'Select an option');
      const config = {
        placeholder: placeholder,
        width: '100%',
        allowClear: !($el.prop('multiple') || $el.prop('required')),
        dropdownParent: $el.closest('.modal, .offcanvas').length ? $el.closest('.modal, .offcanvas') : $('body'),
        minimumInputLength: $el.data('min-input') || 0,
        dropdownCssClass: 'select2-dropdown',
        templateResult: formatOption,
        templateSelection: formatOption,
        escapeMarkup: m => m
      };
      if ($el.data('select') === 'dropdown' && optionsList && optionsList.length && $el.prop('multiple')) {
        try {
          const Dropdown = $.fn.select2.amd.require('select2/dropdown');
          if (typeof Dropdown?.extend === 'function') {
            config.dropdownAdapter = Dropdown.extend({
              render() {
                const $dropdown = this._super();
                const $header = $('<div class="select2-dropdown__header">' +
                  '<div class="select2-dropdown__item select2-dropdown__item__clear-all" tabindex="0" data-group="option">Clear All</div>' +
                  '<div class="select2-dropdown__item select2-dropdown__item__select-all" tabindex="0" data-group="option">Select All</div>' +
                  '</div>');
                $dropdown.prepend($header);
                return $dropdown;
              }
            });
          }
        } catch (e) {
          context.error('Error extending Select2 dropdown adapter', { id: $el.attr('id'), error: e.message });
        }
      }
      try {
        $el.select2(config);
      } catch (e) {
        context.error('Error initializing Select2', { id: $el.attr('id'), error: e.message });
        return;
      }
      const updateSelect2Value = () => {
        const $container = $el.data('select2').$container;
        const values = $el.val();
        if ($el.prop('multiple') && values && values.length) {
          $container.attr('data-select2-value', 'true');
        } else {
          $container.removeAttr('data-select2-value');
        }
      };
      $el.on('change.select2', updateSelect2Value);
      updateSelect2Value();
      $el.on('select2:open', () => {
        const $dropdown = $('.select2-dropdown');
        if ($el.data('loading')) {
          $dropdown.addClass('s2-loading');
        } else {
          $dropdown.removeClass('s2-loading');
        }
        $dropdown.find('.select2-dropdown__item__clear-all')
          .off('click').on('click', () => {
            if ($el.data('loading')) return;
            $el.val(null).trigger('change.select2');
            $el.select2('close');
          });
        $dropdown.find('.select2-dropdown__item__select-all')
          .off('click').on('click', () => {
            if ($el.data('loading')) return;
            const allValues = (optionsList || []).map(item => item.value ?? item.id);
            $el.val(allValues).trigger('change.select2');
            $el.select2('close');
          });
      });
    };

    /** Fetch options dynamically with clean loading animation using async/await */
    const fetchOptions = async ($el, context, params = {}) => {
      const token = params.source || $el.data('source');
      if (!token || $el.data('fetching')) return;
      $el.data('fetching', true);
      const preselectValue = parseDataValue($el.data('value'));
      try {
        if (!$el.hasClass('select2-hidden-accessible')) {
          initSelect2($el, [], context);
        }
        toggleLoading($el, true, 'Loading…');
        $el.find('option').not('[value=""]').remove();
        if (!$el.find('option[value=""]').length) {
          $el.prepend($('<option/>', { value: '', text: 'Loading…', disabled: true }));
        } else {
          $el.find('option[value=""]').prop('disabled', true).text('Loading…');
        }
        const payload = {
          skeleton_token: token,
          q: '',
          selected: preselectValue.length ? preselectValue : null,
          ...params
        };
        console.log('Fetching options for', $el.attr('id'), 'with parent value:', params.selected_value);
        const response = await context.requestAction(token, payload);
        if (response?.data?.status && Array.isArray(response.data.data)) {
          $el.find('option').not('[value=""]').remove();
          const availableValues = [];
          response.data.data.forEach(item => {
            const value = item.value;
            const view = item.view;
            const is_selected = !!item.is_selected;
            const avatar = item.avatar || item.image || item.photo || '';
            const group = item.group || '';
            const uid = item.id || item.uid || '';
            const $opt = $('<option/>', { value, text: view });
            if (avatar) $opt.attr('data-avatar', avatar);
            if (group) $opt.attr('data-group', group);
            if (uid) $opt.attr('data-id', uid);
            if (is_selected) $opt.prop('selected', true);
            $el.append($opt);
            availableValues.push(value);
          });
          const selectValues = preselectValue.filter(v => availableValues.includes(v));
          if (selectValues.length) $el.val(selectValues);
          $el.trigger('change.select2');
          toggleLoading($el, false);
          const s2 = $el.data('select2');
          if (s2?.$dropdown) s2.$dropdown.removeClass('s2-loading');
          if (s2?.$container) s2.$container.removeClass('s2-loading');
          $el.removeData('loading');
        } else {
          throw new Error('Invalid AJAX response');
        }
      } catch (e) {
        context.error('Error fetching dynamic options', { token, error: e.message });
        context.showToast?.({
          icon: 'error',
          title: 'Error',
          message: 'Failed to load dropdown options',
          duration: 5000
        });
        $el.find('option').not('[value=""]').remove();
        $el.append($('<option/>', { value: '', text: 'Failed to load options', disabled: true, selected: true }));
        $el.val(null).trigger('change.select2');
      } finally {
        toggleLoading($el, false);
        $el.data('fetching', false);
        $el.removeData('loading');
        const s2 = $el.data('select2');
        if (s2?.$dropdown) s2.$dropdown.removeClass('s2-loading');
        if (s2?.$container) s2.$container.removeClass('s2-loading');
      }
    };

    /** Initialize a single select element based on its type asynchronously */
    const initSelect = async ($select, optionsList, context) => {
      try {
        const type = $select.data('select');
        if (!type) throw new Error('data-select attribute is missing or invalid');
        if (type === 'dropdown') {
          $select.find('option').not('[value=""]').remove();
          if (optionsList?.length) {
            optionsList.forEach(({ value, view, avatar, group, id, uid }) => {
              const $opt = $('<option/>', { value, text: view || value });
              if (avatar) $opt.attr('data-avatar', avatar);
              if (group) $opt.attr('data-group', group);
              if (uid ?? id) $opt.attr('data-id', (uid ?? id));
              $select.append($opt);
            });
          }
          const preVals = parseDataValue($select.data('value'));
          const available = optionsList.map(o => o.value);
          const valid = preVals.filter(v => available.includes(v));
          initSelect2($select, optionsList, context);
          if (valid.length) $select.val(valid).trigger('change.select2');
        } else if (type === 'dynamic') {
          initSelect2($select, [], context);
          if ($select.data('source')) {
            const parent = document.querySelector(`[data-target="${$select.data('source')}"]`);
            const parentVal = parent ? $(parent).val() : null;
            console.log('Initializing dynamic select', $select.attr('id'), 'with parent value:', parentVal);
            await fetchOptions($select, context, { selected_value: parentVal, source: $select.data('source') });
          }
        } else {
          throw new Error(`Invalid data-select type: ${type}`);
        }
        if ($select.prop('required')) {
          $select.off('change.validate').on('change.validate', () => {
            const invalid = !$select.val() || ($select.prop('multiple') && !$select.val().length);
            $select.toggleClass('is-invalid', invalid);
            context.validateForm?.({ isSubmit: false });
          });
          $select.trigger('change.validate');
        }
      } catch (e) {
        context.error('Error initializing select', { id: $select.attr('id'), type: $select.data('select'), error: e.message });
      }
    };

    // Sort selects to initialize parents before dependents
    const sortedSelects = Array.from(selects).sort((a, b) => {
      const aIsParent = a.hasAttribute('data-target');
      const bIsParent = b.hasAttribute('data-target');
      const aIsDependent = a.hasAttribute('data-source');
      const bIsDependent = b.hasAttribute('data-source');
      if (aIsParent && bIsDependent) return -1; // Parents first
      if (bIsParent && aIsDependent) return 1;  // Dependents after
      return 0; // No change
    });

    // Initialize all selects in parallel, respecting dependency order
    await Promise.all(
      sortedSelects.map(async (select) => {
        try {
          const $select = $(select);
          let optionsList = [];
          try {
            const optionsData = $select.data('options');
            if (optionsData) {
              optionsList = typeof optionsData === 'string' ? JSON.parse(optionsData) : optionsData;
              if (!Array.isArray(optionsList)) {
                optionsList = Object.entries(optionsList).map(([value, view]) => ({ value, view }));
              }
            } else if ($select.data('select') === 'dropdown') {
              optionsList = $select.find('option').map((_, opt) => ({
                value: opt.value,
                view: opt.text,
                avatar: $(opt).data('avatar'),
                group: $(opt).data('group'),
                id: $(opt).data('id')
              })).get();
            }
            $select.data('options-list', optionsList);
          } catch (e) {
            this.error('Invalid data-select-options format', { id: select.id, error: e.message });
            return;
          }
          await initSelect($select, optionsList, this);
        } catch (e) {
          this.error('Error processing select element', { id: select.id, error: e.message });
        }
      })
    );

    // Setup MutationObserver and events for elements with data-select-trigger
    const initTriggerObserver = (context) => {
      const triggers = document.querySelectorAll('[data-select-trigger]');
      if (!triggers.length) return;
      triggers.forEach(trigger => {
        const targetSelector = trigger.dataset.selectTrigger;
        const setType = trigger.dataset.set;
        const source = trigger.dataset.source;
        if (!source || !targetSelector) {
          context.error('Trigger element missing data-source or data-select-trigger', { trigger });
          return;
        }
        const updateTargets = debounce(() => {
          const idVal = trigger.value;
          const targetSelects = document.querySelectorAll(targetSelector);
          targetSelects.forEach(target => {
            const $target = $(target);
            if ($target.data('select') === 'dynamic') {
              fetchOptions($target, context, { id: idVal, set: setType, source });
            }
          });
        }, 120);
        const observer = new MutationObserver(mutations => {
          mutations.forEach(mutation => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
              updateTargets();
            }
          });
        });
        observer.observe(trigger, {
          attributes: true,
          attributeFilter: ['value']
        });
        const events = ['input', 'change'];
        $(trigger).off(events.map(e => `${e}.selectTrigger`).join(' '))
          .on(events.map(e => `${e}.selectTrigger`).join(' '), updateTargets);
        // Trigger initial update for trigger elements
        if (trigger.value) updateTargets();
      });
    };
    initTriggerObserver(this);

    // Dependencies (cascade) + modal/offcanvas re-init
    await Promise.all(
      sortedSelects.map(async (select) => {
        const $select = $(select);
        if ($select.data('target')) {
          $select.off('change.selectHandler').on('change.selectHandler', debounce(async () => {
            const dependents = document.querySelectorAll(`[data-source="${$select.data('target')}"]`);
            for (const dep of dependents) {
              const $dep = $(dep);
              if (['dropdown', 'dynamic'].includes($dep.data('select'))) {
                await fetchOptions($dep, this, { selected_value: $select.val(), source: $dep.data('source') });
              }
            }
          }, 120));
          // Trigger initial fetch for dependents if parent has a value
          if ($select.val()) {
            const dependents = document.querySelectorAll(`[data-source="${$select.data('target')}"]`);
            for (const dep of dependents) {
              const $dep = $(dep);
              if ($dep.data('select') === 'dynamic') {
                await fetchOptions($dep, this, { selected_value: $select.val(), source: $dep.data('source') });
              }
            }
          }
        }
        ['shown.bs.modal', 'shown.bs.offcanvas'].forEach(evt => {
          $(select.closest('.modal, .offcanvas')).on(evt, async () => {
            try {
              const $sel = $(select);
              let opts = $sel.data('options-list') || [];
              if (!opts.length && $sel.data('select') === 'dropdown') {
                const optionsData = $sel.data('options');
                if (optionsData) {
                  opts = typeof optionsData === 'string' ? JSON.parse(optionsData) : optionsData;
                  if (!Array.isArray(opts)) {
                    opts = Object.entries(opts).map(([value, view]) => ({ value, view }));
                  }
                } else {
                  opts = $sel.find('option').map((_, opt) => ({
                    value: opt.value,
                    view: opt.text,
                    avatar: $(opt).data('avatar'),
                    group: $(opt).data('group'),
                    id: $(opt).data('id')
                  })).get();
                }
                $sel.data('options-list', opts);
              }
              await initSelect($sel, opts, this);
              // Trigger fetch for dependent dropdowns after parent initialization
              if ($sel.data('target') && $sel.val()) {
                const dependents = document.querySelectorAll(`[data-source="${$sel.data('target')}"]`);
                for (const dep of dependents) {
                  const $dep = $(dep);
                  if ($dep.data('select') === 'dynamic') {
                    await fetchOptions($dep, this, { selected_value: $sel.val(), source: $dep.data('source') });
                  }
                }
              }
            } catch (e) {
              this.error('Error reinitializing select in modal/offcanvas', { id: $(select).attr('id'), error: e.message });
            }
          });
        });
      })
    );

    this.log('Select2 initialization completed');
  } catch (error) {
    this.error('Error initializing Select2:', error);
  }
}
  // repeater.js - Enhanced with async and error handling
  async repeater() {
    try {
      const containers = document.querySelectorAll('[data-repeater-container]');
      await Promise.all(
        Array.from(containers).map(async (container) => {
          try {
            const hiddenInputName = container.dataset.input;
            const dataType = container.dataset.type;
            const preUpdate = container.dataset.previous || '';
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = hiddenInputName;
            container.appendChild(hiddenInput);
            const template = container.querySelector('[data-repeater]').cloneNode(true);
            const inputKeyFieldName = dataType === 'pair' ? 'label' : null;
            const repeaterWrapper = document.createElement('div');
            repeaterWrapper.classList.add('repeater-sortable-wrapper');
            repeaterWrapper.setAttribute('data-repeater-wrapper', '');
            container.appendChild(repeaterWrapper);
            container.querySelectorAll('[data-repeater]').forEach(block => {
              repeaterWrapper.appendChild(block);
            });
            const updateHiddenInput = () => {
              const blocks = [...repeaterWrapper.querySelectorAll('[data-repeater]')];
              const result = dataType === 'array' ? [] : {};
              const keysSeen = new Set(); // Modern Set for uniqueness
              const duplicateBlocks = [];
              blocks.forEach(block => {
                const inputs = [...block.querySelectorAll('input, select')];
                const entry = {};
                inputs.forEach(input => {
                  entry[input.name] = input.value;
                });
                if (dataType === 'array') {
                  if (Object.values(entry).some(v => v !== '')) result.push(entry);
                } else if (dataType === 'pair') {
                  const key = entry.label;
                  const value = entry.value;
                  if (!key) return;
                  if (keysSeen.has(key)) {
                    duplicateBlocks.push(block);
                  } else {
                    keysSeen.add(key);
                    result[key] = value || '';
                  }
                }
              });
              duplicateBlocks.forEach(block => {
                const keyInput = block.querySelector(`[name="${inputKeyFieldName}"]`);
                if (keyInput) keyInput.tagName === 'SELECT' ? keyInput.selectedIndex = 0 : keyInput.value = '';
                const valueInput = block.querySelector('[name="value"]');
                if (valueInput) valueInput.value = '';
              });
              hiddenInput.value = JSON.stringify(result);
            };
            const bindEvents = block => {
              [...block.querySelectorAll('input, select')].forEach(input => {
                input.addEventListener('keyup', updateHiddenInput);
                input.addEventListener('change', updateHiddenInput);
              });
            };
            const createRemoveButton = () => {
              const removeBtn = document.createElement('button');
              removeBtn.type = 'button';
              removeBtn.setAttribute('data-repeater-remove', '');
              removeBtn.innerHTML = '<i class="ti ti-x"></i>';
              removeBtn.addEventListener('click', e => {
                const block = e.currentTarget.closest('[data-repeater]');
                block.remove();
                updateHiddenInput();
              });
              return removeBtn;
            };
            const addRepeaterBlock = (data = {}) => {
              const newBlock = template.cloneNode(true);
              // Replace add button with remove button in the same position
              const originalAddBtn = newBlock.querySelector('[data-repeater-add]');
              if (originalAddBtn) {
                const removeBtn = createRemoveButton();
                originalAddBtn.replaceWith(removeBtn);
              }
              Object.entries(data).forEach(([key, value]) => {
                const el = newBlock.querySelector(`[name="${key}"]`);
                if (el) el.value = value;
              });
              repeaterWrapper.appendChild(newBlock);
              bindEvents(newBlock);
              updateHiddenInput();
            };
            const baseBlock = repeaterWrapper.querySelector('[data-repeater]');
            const addBtn = baseBlock.querySelector('[data-repeater-add]');
            if (addBtn) {
              addBtn.addEventListener('click', () => addRepeaterBlock());
            }
            bindEvents(baseBlock);
            if (preUpdate.trim()) {
              try {
                const parsed = JSON.parse(preUpdate);
                if (dataType === 'array' && Array.isArray(parsed)) {
                  if (parsed.length > 0) {
                    Object.entries(parsed[0]).forEach(([key, value]) => {
                      const el = baseBlock.querySelector(`[name="${key}"]`);
                      if (el) el.value = value;
                    });
                    parsed.slice(1).forEach(item => addRepeaterBlock(item));
                  }
                } else if (dataType === 'pair' && typeof parsed === 'object') {
                  const keys = Object.keys(parsed);
                  if (keys.length > 0) {
                    baseBlock.querySelector('[name="label"]').value = keys[0];
                    baseBlock.querySelector('[name="value"]').value = parsed[keys[0]];
                    keys.slice(1).forEach(k => {
                      addRepeaterBlock({ label: k, value: parsed[k] });
                    });
                  }
                }
              } catch (e) {
                this.error('Invalid JSON in data-previous:', e);
              }
            }
            Sortable.create(repeaterWrapper, {
              animation: 150,
              ghostClass: 'sortable-ghost',
              onEnd: updateHiddenInput
            });
            updateHiddenInput();
          } catch (error) {
            this.error('Error initializing repeater for container:', error, container);
          }
        })
      );
      this.log('Repeater initialization completed');
    } catch (error) {
      this.error('Error in repeater setup:', error);
    }
  }
  // Enhanced Stepper with Smooth Animations and async transitions
  async stepper() {
    try {
      const containers = document.querySelectorAll('[data-stepper-container]');
      await Promise.all(
        Array.from(containers).map(async (container) => {
          try {
            // Cleanup to prevent duplicate UI
            container.querySelectorAll('.step-icon, .progress-bar-vertical, .fill, .btn, .d-flex.justify-content-between, .my-3').forEach(el => el.remove());
            const steps = [...container.querySelectorAll('[data-step]')];
            const type = container.dataset.stepperType || 'linear';
            const progressType = container.dataset.progressType || 'bar+icon';
            const progressColor = container.dataset.progressColor || '#00b4af';
            const alignProgress = container.dataset.stepperAlignProgress || 'top';
            const alignBtns = container.dataset.stepperAlignBtns || 'bottom';
            const submitText = container.dataset.submitBtnText || 'Submit';
            const btnsClass = container.dataset.btnClass || '';
            let current = 0;
            const completed = new Set();
            const progressUI = document.createElement('div');
            const iconRow = document.createElement('div');
            const barWrap = document.createElement('div');
            const barFill = document.createElement('div');
            progressUI.className = 'my-3';
            iconRow.className = 'd-flex flex-column gap-3';
            barWrap.className = 'progress-bar-vertical';
            barFill.className = 'fill';
            barWrap.append(barFill);
            const progressContainer = document.createElement('div');
            progressContainer.className = 'd-flex';
            if (alignProgress === 'left' || alignProgress === 'right') {
              progressContainer.classList.add('stepper-vertical');
              progressContainer.append(iconRow, barWrap);
              if (alignProgress === 'left') container.prepend(progressContainer);
              else container.append(progressContainer);
            } else {
              iconRow.className = 'd-flex flex-row gap-2 justify-content-between';
              progressUI.append(iconRow);
              if (progressType.includes('bar')) {
                const barTop = document.createElement('div');
                barTop.className = 'w-100 bg-light rounded overflow-hidden';
                barTop.style.height = '6px';
                const barInner = document.createElement('div');
                barInner.className = 'h-100';
                barInner.style.width = '0%';
                barInner.style.backgroundColor = progressColor;
                barInner.style.transition = 'width .5s ease-in-out';
                barTop.append(barInner);
                progressUI.append(barTop);
                barFill._horizontal = barInner;
              }
              if (alignProgress === 'top') container.prepend(progressUI);
              else container.append(progressUI);
            }
            // Create step icons
            steps.forEach((step, i) => {
              const btn = document.createElement('div');
              btn.className = 'step-icon cursor-pointer transition-all';
              btn.dataset.stepIndex = i;
              const icon = step.dataset.icon || 'fa-circle';
              const title = step.dataset.title || `Step ${i + 1}`;
              btn.innerHTML = `<i class="fa ${icon}"></i><div><small>${title}</small></div>`;
              btn.addEventListener('click', () => {
                if (type === 'non-linear' || i <= current || completed.has(i)) goTo(i);
              });
              iconRow.append(btn);
            });
            // Create navigation buttons
            const btns = document.createElement('div');
            btns.className = 'd-flex justify-content-between mt-3 gap-2';
            const prev = document.createElement('button');
            const next = document.createElement('button');
            prev.type = 'button';
            prev.className = 'btn btn-secondary ' + btnsClass;
            prev.textContent = 'Previous';
            next.type = 'button';
            next.className = 'btn btn-primary ' + btnsClass;
            next.textContent = 'Next';
            btns.append(prev, next);
            if (alignBtns === 'bottom') container.append(btns);
            else container.prepend(btns);
            // Animate step transitions with async await for smooth effect
            const animateStep = async (from, to) => {
              if (from === to) return;
              steps[from].style.opacity = 0;
              steps[from].style.transform = 'translateX(-20px)';
              await new Promise(resolve => setTimeout(resolve, 300));
              steps[from].style.display = 'none';
              steps[to].style.display = 'block';
              steps[to].style.opacity = 0;
              steps[to].style.transform = 'translateX(20px)';
              await new Promise(resolve => setTimeout(resolve, 10));
              steps[to].style.opacity = 1;
              steps[to].style.transform = 'translateX(0)';
            };
            // Update next button type (with delay if it's final step)
            const updateNextButtonType = () => {
              const isFinalStep = current === steps.length - 1;
              const delay = isFinalStep ? 200 : 0;
              setTimeout(() => {
                next.setAttribute('data-final-step', isFinalStep ? 'true' : 'false');
                next.type = isFinalStep ? 'submit' : 'button';
                next.textContent = isFinalStep ? submitText : 'Next';
              }, delay);
            };
            // Update UI
            const updateUI = () => {
              steps.forEach((step, i) => {
                const isActive = i === current;
                step.classList.toggle('active', isActive);
                step.style.opacity = isActive ? '1' : '0';
                step.style.transform = isActive ? 'translateY(0)' : 'translateY(10px)';
                step.style.pointerEvents = isActive ? 'auto' : 'none';
                const iconBtn = iconRow.querySelector(`[data-step-index="${i}"]`);
                iconBtn.classList.remove('active', 'completed');
                if (i < current) iconBtn.classList.add('completed');
                else if (isActive) iconBtn.classList.add('active');
              });
              if (barFill._horizontal) {
                const pct = (current / (steps.length - 1)) * 100;
                barFill._horizontal.style.width = `${pct}%`;
              } else {
                const heightPct = (current / (steps.length - 1)) * 100;
                barFill.style.height = `${heightPct}%`;
              }
              prev.disabled = current === 0;
              updateNextButtonType(); // delayed logic here
            };
            // Validation
            const validate = (index) => {
              const inputs = steps[index].querySelectorAll('[required]');
              for (const input of inputs) {
                if (!input.value.trim()) {
                  input.focus();
                  if (typeof window.showToast === 'function') {
                    window.showToast({
                      icon: 'warning',
                      title: 'Input Error',
                      message: 'Please fill all required fields.',
                      duration: 5000
                    });
                  }
                  return false;
                }
              }
              return true;
            };
            // Step navigation with async
            const goTo = async (index) => {
              if (index > current && !validate(current)) return;
              if (index > current) completed.add(current);
              const previous = current;
              current = index;
              await animateStep(previous, current);
              updateUI();
            };
            // Prev/Next button handlers
            prev.addEventListener('click', (e) => {
              goTo(current - 1);
            });
            next.addEventListener('click', async (e) => {
              const isFinalStep = current === steps.length - 1;
              const isSubmitButton = next.getAttribute('data-final-step') === 'true';
              if (isFinalStep && isSubmitButton) {
                if (validate(current)) {
                  container.querySelector('form')?.requestSubmit?.();
                }
              } else {
                await goTo(current + 1);
              }
            });
            // Initial setup
            steps.forEach((step, i) => {
              step.style.transition = 'all 0.3s ease';
              step.style.display = i === 0 ? 'block' : 'none';
            });
            updateUI();
          } catch (error) {
            this.error('Error initializing stepper for container:', error, container);
          }
        })
      );
      this.log('Stepper initialization completed');
    } catch (error) {
      this.error('Error in stepper setup:', error);
    }
  }
  /**
   * Initializes Tagify for <input> elements with the [data-pills] attribute.
   * Enhanced with modern error handling.
   */
  pills() {
    try {
      const inputs = document.querySelectorAll('input[data-pills]');
      if (!inputs.length) return;
      if (typeof window.Tagify !== 'function') {
        throw new Error('Tagify is required but not loaded');
      }
      Array.from(inputs).forEach(input => {
        try {
          // Skip if already initialized
          if (input.dataset.tagified === 'true') return;
          // Parse data-pills-list
          let pillList = [];
          try {
            pillList = JSON.parse(input.dataset.pillsList || '[]');
            if (!Array.isArray(pillList)) {
              throw new Error('data-pills-list must be an array');
            }
          } catch (error) {
            this.error(`Invalid JSON in data-pills-list for input ${input.id || 'unknown'}:`, error.message);
            this.showToast({
              icon: 'error',
              title: 'Configuration Error',
              message: `Invalid JSON in data-pills-list for input ${input.id || 'unknown'}`,
              duration: 5000
            });
            return;
          }
          // Get data-pills type
          const type = input.dataset.pills || 'normal';
          // Parse max-tags
          let maxTags = Infinity;
          if (input.dataset.maxTags !== undefined) {
            const parsedMaxTags = parseInt(input.dataset.maxTags, 10);
            if (isNaN(parsedMaxTags) || parsedMaxTags < 1) {
              this.log(`Invalid data-max-tags for input ${input.id || 'unknown'}: ${input.dataset.maxTags}`);
              this.showToast({
                icon: 'warning',
                title: 'Configuration Warning',
                message: `Invalid max tags value for input ${input.id || 'unknown'}. Using unlimited tags.`,
                duration: 5000
              });
            } else {
              maxTags = parsedMaxTags;
            }
          }
          // Set input attributes
          input.type = 'text';
          input.classList.add('pills-list');
          input.placeholder = input.placeholder || '-';
          input.dataset.tagified = 'true';
          // Tagify configuration
          const config = {
            whitelist: pillList,
            maxTags: maxTags,
            enforceWhitelist: type !== 'normal',
            skipInvalid: true,
            dropdown: {
              maxItems: Infinity,
              closeOnSelect: maxTags === 1,
              enabled: 0,
              classname: 'pills-list',
              searchKeys: type === 'normal' ? ['value', 'group'] : ['id', 'value', 'group']
            },
            originalInputValueFormat: values =>
              values.map(v => typeof v === 'string' ? v : (v.id || v.value)).join(input.dataset.pillsSeparator || ','),
            tagTextProp: (type === 'user' || type === 'option') ? 'id' : 'value'
          };
          // Templates
          config.templates = {
            tag: tagData => `
              <tag title="${tagData.id || tagData.value || ''}" 
                  contenteditable="false" spellcheck="false" tabIndex="-1" 
                  class="tagify__tag ${tagData.class || ''}" 
                  ${Object.entries(tagData).map(([k, v]) => `${k}="${v || ''}"`).join(' ')}>
                  <x class="tagify__tag__removeBtn" role="button" aria-label="remove tag"></x>
                  <div>
                      ${type === 'user' && tagData.avatar
                ? `<div class="tagify__tag__avatar-wrap"><img src="${tagData.avatar}" onerror="this.style.display='none'"></div>`
                : ''}
                      <span class="tagify__tag-text">${tagData.value || ''}</span>
                  </div>
              </tag>`,
            dropdownItem: item => `
              <div ${Object.entries(item).map(([k, v]) => `${k}="${v || ''}"`).join(' ')} 
                  class="tagify__dropdown__item sk_list ${item.class || ''}" 
                  tabindex="0" role="option">
                  ${type === 'user' && item.avatar
                ? `<div class="tagify__dropdown__item__avatar-wrap"><img src="${item.avatar}" onerror="this.style.display='none'"></div>`
                : ''}
                  <div class="info_sec">
                      <div>${item.value || ''}${type === 'option' ? ` <label>- ${item.id || ''}</label>` : ''}</div>
                      ${type === 'user' ? `<span>${item.role || '' ? `<small>${item.role || ''}</small>` : ''}<i>${item.id || ''}</i></span>` : ''}
                  </div>
              </div>`
          };
          // Initialize with existing values
          const existingValue = input.value.trim();
          if (existingValue) {
            const separator = input.dataset.pillsSeparator || ',';
            const values = existingValue.split(separator).filter(Boolean);
            config.value = values.map(val => {
              const match = pillList.find(item =>
                type === 'normal' ? item.value === val : (item.id || item.value) === val
              );
              return match || val;
            });
          }
          // Initialize Tagify
          let tagify;
          try {
            tagify = new window.Tagify(input, config);
          } catch (error) {
            this.error(`Error initializing Tagify for input ${input.id || 'unknown'}:`, error.message);
            this.showToast({
              icon: 'error',
              title: 'Initialization Error',
              message: 'Failed to initialize tag input',
              duration: 5000
            });
            return;
          }
          // Update not-empty class
          const updateNotEmptyClass = () => {
            input.classList.toggle('tagify--not-empty', tagify.value.length > 0);
          };
          tagify.on('add remove', updateNotEmptyClass);
          updateNotEmptyClass();
          // Custom dropdown with groups
          tagify.dropdown.createListHTML = items => {
            try {
              const grouped = items.reduce((acc, item) => {
                const group = item.group || 'Not Assigned';
                acc[group] = (acc[group] || []).concat(item);
                return acc;
              }, {});
              const hasGroups = Object.keys(grouped).length > 1 || Object.keys(grouped)[0] !== 'Not Assigned';
              return `
                          <div class="tagify__dropdown__header">
                              <div class="tagify__dropdown__item tagify__dropdown__item__clear-all" tabindex="0" role="option"><strong>Clear All</strong></div>
                              <div class="tagify__dropdown__item tagify__dropdown__item__select-all" tabindex="0" role="option"><strong>Select All</strong></div>
                          </div>
                          ${Object.entries(grouped)
                  .map(([group, groupItems]) => `
                                  <div class="tagify__dropdown__itemsGroup">
                                      ${hasGroups ? `<div class="tagify__dropdown__items_grpset"><div>${group}</div><div class="tagify__dropdown__item tagify__dropdown__item__select-group" data-group="${group}" tabindex="0" role="option"><strong>Select Group</strong></div></div>` : ''}
                                      ${groupItems.map(item => tagify.settings.templates.dropdownItem.apply(tagify, [typeof item === 'object' ? item : { value: item }])).join('')}
                                  </div>`
                  ).join('')}`;
            } catch (error) {
              this.error(`Error creating dropdown HTML for input ${input.id || 'unknown'}:`, error.message);
              return '';
            }
          };
          // Dropdown select handling
          tagify.on('dropdown:select', e => {
            try {
              const target = e.detail.elm;
              if (!target) return;
              if (target.classList.contains('tagify__dropdown__item__clear-all')) {
                tagify.removeAllTags();
              } else if (target.classList.contains('tagify__dropdown__item__select-all')) {
                const allItems = tagify.settings.whitelist
                  .filter(item => !tagify.value.some(tag => (tag.id || tag.value) === (item.id || item.value)))
                  .slice(0, maxTags === Infinity ? undefined : maxTags - tagify.value.length);
                if (allItems.length) tagify.addTags(allItems);
              } else if (target.classList.contains('tagify__dropdown__item__select-group')) {
                const group = target.dataset.group;
                const groupItems = tagify.settings.whitelist
                  .filter(item => item.group === group)
                  .filter(item => !tagify.value.some(tag => (tag.id || tag.value) === (item.id || item.value)))
                  .slice(0, maxTags === Infinity ? undefined : maxTags - tagify.value.length);
                if (groupItems.length) tagify.addTags(groupItems);
              }
            } catch (error) {
              this.error(`Error handling dropdown select for input ${input.id || 'unknown'}:`, error.message);
            }
          });
          // Initialize SortableJS for drag-and-drop
          if (typeof Sortable === 'function') {
            try {
              new Sortable(tagify.DOM.scope, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                filter: '.tagify__tag__removeBtn, .tagify__input',
                onEnd: () => {
                  try {
                    tagify.updateValueByDOMTags();
                  } catch (e) {
                    this.error(`Error updating Tagify values after sort for input ${input.id || 'unknown'}:`, e.message);
                  }
                }
              });
            } catch (e) {
              this.error(`Error initializing Sortable for input ${input.id || 'unknown'}:`, e.message);
            }
          }
          // Required validation
          if (input.hasAttribute('required')) {
            const validateRequired = () => {
              input.classList.toggle('is-invalid', tagify.value.length === 0);
            };
            tagify.on('change', validateRequired);
            validateRequired();
          }
          this.log('Tagify initialized for input:', input.id || 'unknown');
        } catch (e) {
          this.error(`Error in pills for input ${input.id || 'unknown'}:`, e.message);
          this.showToast({
            icon: 'error',
            title: 'Error',
            message: 'Failed to initialize tag input',
            duration: 5000
          });
        }
      });
      this.log('Pills (Tagify) initialization completed');
    } catch (error) {
      this.error('Error initializing pills:', error);
    }
  }
  /**
   * Validates file size against maximum allowed size with error handling.
   */
  validateSize(file, maxMB) {
    try {
      if (file.size / (1024 * 1024) > maxMB) {
        this.showToast({
          icon: 'warning',
          title: 'File Size Error',
          message: `File too large. Max: ${maxMB}MB.`,
          duration: 5000
        });
        return false;
      }
      return true;
    } catch (error) {
      this.error('Error validating file size:', error);
      return false;
    }
  }
  /**
   * Returns accepted MIME types based on file type.
   */
  getAcceptedMimes(type) {
    const mimes = {
      image: 'image/*',
      video: 'video/*',
      audio: 'audio/*',
      document: '.pdf,.doc,.docx,.xls,.xlsx,.txt',
      preview: 'image/*'
    };
    return mimes[type] || '*/*';
  }
  /**
   * Sets file input value with DataTransfer for modern browsers.
   */
  setInputFile(input, file, multiple) {
    if (!input) return;
    try {
      const dt = new DataTransfer();
      if (multiple && input.files.length) {
        Array.from(input.files).forEach(f => dt.items.add(f));
      }
      dt.items.add(file);
      input.files = dt.files;
    } catch (error) {
      this.error('Error setting file input:', error);
    }
  }
  /**
   * Initializes file upload functionality with Dropzone and Croppie, async for multiple setups.
   */
  async files() {
    try {
      const createCropModal = () => {
        if (document.querySelector('#dynamic-crop-modal')) return;
        document.body.insertAdjacentHTML(
          'beforeend',
          `       
                  <div class="modal fade" id="dynamic-crop-modal" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content crop-modal-bg">
                              <div class="modal-body p-0">
                                  <div data-croppie-container style="min-height:300px;"></div>
                              </div>
                              <div class="modal-footer border-0 p-0">
                                  <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" aria-label="Close" data-cancel-crop>Cancel</button>
                                  <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal" data-confirm-crop>Confirm</button>
                              </div>
                          </div>
                      </div>
                  </div>`
        );
      };
      createCropModal();
      const cropModalEl = document.querySelector('#dynamic-crop-modal');
      if (!cropModalEl) {
        throw new Error('Failed to create crop modal');
      }
      const cropModal = new window.bootstrap.Modal(cropModalEl, { backdrop: 'static' });
      await Promise.all(
        Array.from(document.querySelectorAll('.file-upload-container')).map(async (container, index) => {
          try {
            const setupUploader = (container, index, cropModal, modalEl) => {
              const type = container.dataset.file;
              const crop = container.dataset.fileCrop || null;
              const cropSize = container.dataset.cropSize || '400:400';
              const multiple = container.hasAttribute('data-multiple');
              const label = container.dataset.label || 'Upload';
              const inputName = container.dataset.name || 'file';
              const recommendedSize = container.dataset.recommendedSize || '-';
              const defaultSrc = container.dataset.src || '/default/preview-square.svg';
              const maxSizeMB = parseFloat(container.dataset.fileSize) || 5;
              const uniqueClass = `btn-upload-${index}`;
              const inputId = `${inputName}-input`;
              const blobInputId = crop ? `${inputName}-blob-input` : null;
              const sizeNote = `<span class="text-muted small">(Max: <b>${maxSizeMB}MB</b>)</span>`;
              if (crop || type === 'preview') {
                const previewId = `${crop || type}-preview`;
                const style = crop === 'profile'
                  ? 'width:100px;height:100px;object-fit:cover;border-radius:50%;'
                  : 'max-width:100%;max-height:200px;object-fit:cover;border:1px solid #ccc;border-radius:5px;';
                container.innerHTML = `
                            <div>
                                <img src="${defaultSrc}" id="${previewId}" class="result" style="${style}">
                            </div>
                            <div class="d-flex flex-column">
                                <h6>${label}</h6>
                                <span class="small mb-2">Recommended: ${recommendedSize} | ${sizeNote}</span>
                                <div>
                                    <input type="file" name="${inputName}" hidden id="input-${uniqueClass}">
                                    <button type="button" class="btn btn-primary btn-sm px-3 ${uniqueClass}" data-upload="${crop || type}">Upload</button>
                                    <button type="button" class="btn btn-secondary btn-sm px-3 btn-reset" data-reset="${crop || type}">Reset</button>
                                </div>
                            </div>`;
                const img = container.querySelector(`#${previewId}`);
                if (defaultSrc && !defaultSrc.includes('preview-window.svg')) {
                  img.src = defaultSrc;
                }
              } else {
                container.classList.add('dropzone', 'dz-container');
                if (!container.querySelector(`.${uniqueClass}`)) {
                  const btn = document.createElement('button');
                  btn.type = 'button';
                  btn.textContent = label;
                  btn.className = `btn btn-primary btn-sm px-3 ${uniqueClass}`;
                  container.appendChild(btn);
                }
                if (!container.querySelector('.dz-preview-container')) {
                  const previews = document.createElement('div');
                  previews.className = 'dz-preview-container mt-2';
                  container.appendChild(previews);
                }
                container.innerHTML += `<input type="file" name="${inputName}" id="${inputId}" ${multiple ? 'multiple' : ''} hidden>`;
              }
              const uploadBtn = container.querySelector(`.${uniqueClass}`);
              const resetBtn = container.querySelector('.btn-reset');
              const fileInput = container.querySelector(`#input-${uniqueClass}`);
              const blobInput = crop ? container.querySelector(`#${blobInputId}`) : null;
              const hiddenInput = container.querySelector(`#${inputId}`);
              let dz = null;
              if (!crop && type !== 'preview') {
                dz = new Dropzone(container, {
                  url: '#',
                  autoProcessQueue: false,
                  clickable: `.${uniqueClass}`,
                  previewsContainer: container.querySelector('.dz-preview-container'),
                  acceptedFiles: this.getAcceptedMimes(type),
                  maxFiles: multiple ? 10 : 1,
                  uploadMultiple: multiple,
                  addRemoveLinks: true,
                  dictRemoveFile: 'Remove',
                  previewTemplate: `
                                <div class="dz-preview dz-file-preview d-flex align-items-center mt-2">
                                    <div class="dz-image me-2"><img data-dz-thumbnail style="max-width:50px;max-height:50px;" /></div>
                                    <div class="dz-details flex-grow-1">
                                        <div class="dz-filename"><span data-dz-name></span></div>
                                        <div class="dz-size" data-dz-size></div>
                                    </div>
                                    <a class="dz-remove text-danger ms-2" href="#" data-dz-remove>Remove</a>
                                </div>`,
                  init() {
                    this.on('addedfile', (file) => {
                      if (!this.validateSize(file, maxSizeMB)) {
                        this.removeFile(file);
                        return;
                      }
                      this.setInputFile(hiddenInput, file, multiple);
                    });
                    this.on('removedfile', () => {
                      hiddenInput.value = '';
                    });
                  }
                });
              }
              uploadBtn?.addEventListener('click', () => fileInput?.click());
              resetBtn?.addEventListener('click', () => {
                if (dz) {
                  dz.removeAllFiles(true);
                  hiddenInput.value = '';
                } else {
                  const img = container.querySelector(`#${crop || type}-preview`);
                  img.src = '/default/preview-square.svg';
                  hiddenInput.value = '';
                  if (blobInput) blobInput.value = '';
                }
              });
              fileInput?.addEventListener('change', () => {
                const file = fileInput.files[0];
                if (!file) return;
                if (!this.validateSize(file, maxSizeMB)) {
                  fileInput.value = '';
                  return;
                }
                const key = uploadBtn.dataset.upload;
                if (['profile', 'banner', 'cover'].includes(key)) {
                  if (this.isCroppieOpen) return;
                  this.isCroppieOpen = true;
                  const reader = new FileReader();
                  reader.onload = () => {
                    cropModal.show();
                    setTimeout(() => {
                      this.croppieInst?.destroy();
                      const [w, h] = cropSize.split(':').map(Number);
                      this.croppieInst = new Croppie(modalEl.querySelector('[data-croppie-container]'), {
                        viewport: key === 'profile' ? { width: 200, height: 200, type: 'square' } : { width: w, height: h },
                        boundary: { width: w + 100, height: h + 100 },
                        showZoomer: true,
                        enableOrientation: true,
                        mouseWheelZoom: 'ctrl',
                        enforceBoundary: true
                      });
                      this.croppieInst.bind({ url: reader.result });
                      this.currentKey = key;
                    }, 200);
                  };
                  reader.readAsDataURL(file);
                } else {
                  const reader = new FileReader();
                  reader.onload = () => {
                    const img = container.querySelector('#preview-preview');
                    if (img) img.src = reader.result;
                    this.setInputFile(hiddenInput, file, multiple);
                  };
                  reader.readAsDataURL(file);
                }
                fileInput.value = '';
              });
              const confirmCrop = modalEl.querySelector('[data-confirm-crop]');
              const cancelCrop = modalEl.querySelector('[data-cancel-crop]');
              if (!confirmCrop.dataset.listenerAdded) {
                confirmCrop.addEventListener('click', () => this.confirmCroppie(cropModal, container));
                confirmCrop.dataset.listenerAdded = 'true';
              }
              if (!cancelCrop.dataset.listenerAdded) {
                cancelCrop.addEventListener('click', () => {
                  this.croppieInst?.destroy();
                  this.croppieInst = null;
                  cropModal.hide();
                  this.isCroppieOpen = false;
                  this.currentKey = '';
                });
                cancelCrop.dataset.listenerAdded = 'true';
              }
            };
            setupUploader(container, index, cropModal, cropModalEl);
          } catch (error) {
            this.error('Error setting up uploader for container:', error, container);
          }
        })
      );
      document.addEventListener('hidden.bs.modal', () => {
        if (document.querySelectorAll('.modal.show').length > 0) {
          document.body.classList.add('modal-open');
          if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.classList.add('modal-backdrop', 'fade', 'show');
            document.body.appendChild(backdrop);
          }
        }
      });
      this.log('File upload initialization completed');
    } catch (error) {
      this.error('Error initializing files:', error);
    }
  }
  async confirmCroppie(cropModal, container) {
    if (!this.croppieInst || !this.currentKey) return;
    try {
      const cropSize = container?.dataset.cropSize || '400:400';
      const [w, h] = cropSize.split(':').map(Number);
      const blobInput = container.querySelector(`input[name$="_blob"]`);
      const hiddenInput = container.querySelector(`input[name="${container.dataset.name}"]`);
      const blob = await new Promise((resolve, reject) => {
        this.croppieInst.result({
          type: 'blob',
          size: { width: w, height: h },
          format: 'jpeg',
          quality: 1
        }).then(resolve).catch(reject);
      });
      const file = new File([blob], `cropped-${this.currentKey}.jpg`, { type: 'image/jpeg' });
      const img = document.querySelector(`#${this.currentKey}-preview`);
      img.src = URL.createObjectURL(blob);
      const reader = new FileReader();
      reader.onload = () => {
        if (blobInput) blobInput.value = reader.result;
        this.setInputFile(hiddenInput, file, false);
      };
      reader.readAsDataURL(blob);
      this.croppieInst.destroy();
      this.croppieInst = null;
      cropModal.hide();
      this.isCroppieOpen = false;
      this.currentKey = '';
      this.log('Croppie confirmed successfully');
    } catch (err) {
      this.showToast({
        icon: 'error',
        title: 'Crop Error',
        message: 'Failed to crop image. Please try again.',
        duration: 5000
      });
      this.error('Error in confirmCroppie', { error: err.message });
    }
  }
  /**
   * Logs messages to console when developerMode is enabled.
   * @param {...any} args - Arguments to log
   */
  log(...args) {
    if (this.developerMode) {
      console.log('[General Log]', ...args);
    }
  }
  /**
   * Logs error messages to console when developerMode is enabled.
   * @param {...any} args - Error arguments to log
   */
  error(...args) {
    if (this.developerMode) {
      console.error('[General Error]', ...args);
    }
  }
  /**
   * Updates all elements with class .live-time with current time using requestAnimationFrame for smoothness.
   */
  updateLiveClock() {
    try {
      const update = () => {
        const elements = document.querySelectorAll('.live-time');
        if (!elements.length) return;
        const now = new Date();
        const time = [
          now.getHours().toString().padStart(2, '0'),
          now.getMinutes().toString().padStart(2, '0'),
          now.getSeconds().toString().padStart(2, '0'),
        ].join(':');
        elements.forEach((el) => (el.textContent = time));
        requestAnimationFrame(update); // Smooth 60fps updates, but throttle if needed
      };
      update();
    } catch (error) {
      this.error('Error updating live clock:', error);
    }
  }
  /**
   * Manages cookies (set, get, delete) with enhanced error handling.
   * @param {Object} options - Cookie options
   * @param {string} options.action - Action to perform ('set', 'get', 'delete')
   * @param {string} options.name - Cookie name
   * @param {any} [options.value] - Cookie value (for set action)
   * @param {number} [options.hours] - Cookie expiry in hours (for set action)
   * @returns {any|null} Cookie value or null
   */
  manageCookie({ action, name, value, hours }) {
    try {
      switch (action) {
        case 'set': {
          if (!name || value === undefined) throw new Error('Name and value required for set action');
          const date = new Date(Date.now() + hours * 3600000);
          document.cookie = `${name}=${encodeURIComponent(JSON.stringify(value))};expires=${date.toUTCString()};path=/;SameSite=Strict`;
          return null;
        }
        case 'get': {
          if (!name) throw new Error('Name required for get action');
          const parts = `; ${document.cookie}`.split(`; ${name}=`);
          return parts.length === 2 ? JSON.parse(decodeURIComponent(parts.pop().split(';').shift())) : null;
        }
        case 'delete': {
          if (!name) throw new Error('Name required for delete action');
          document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;SameSite=Strict`;
          return null;
        }
        default:
          throw new Error(`Invalid cookie action: ${action}`);
      }
    } catch (error) {
      this.error('Cookie management error:', error);
      return null;
    }
  }
  /**
   * Sanitizes input to prevent XSS with DOMPurify-like approach.
   * @param {any} input - Input to sanitize
   * @returns {string} Sanitized input
   */
  sanitizeInput(input) {
    try {
      const div = document.createElement('div');
      div.textContent = input ?? this.emptyValue;
      return div.innerHTML;
    } catch (error) {
      this.error('Input sanitization error:', error);
      return this.emptyValue;
    }
  }
  /**
   * Checks if a value is empty using modern checks.
   * @param {any} value - Value to check
   * @returns {boolean} True if empty
   */
  isEmpty(value) {
    try {
      return (
        value === null ||
        value === undefined ||
        value === '' ||
        (Array.isArray(value) && value.length === 0) ||
        (typeof value === 'object' && Object.keys(value).length === 0)
      );
    } catch (error) {
      this.error('Error checking empty value:', error);
      return true;
    }
  }
  /**
   * Debounces a function using WeakMap for instance management.
   * @param {Function} func - Function to debounce
   * @param {number} [delay=this.debounceDelay] - Debounce delay in ms
   * @returns {Function} Debounced function
   */
  debounce(func, delay = this.debounceDelay) {
    try {
      let timeout;
      const debounced = (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), delay);
      };
      this.debouncedFunctions.set(func, debounced);
      return debounced;
    } catch (error) {
      this.error('Debounce function error:', error);
      return func;
    }
  }
  /**
   * Prevents rapid successive clicks with timestamp check.
   * @returns {boolean} True if can proceed
   */
  canProceed() {
    try {
      const now = Date.now();
      if (now - this.lastClickTime < this.clickDelay) {
        this.warningToast('Slow Down', 'Please wait a few seconds before trying again', 2000);
        return false;
      }
      this.lastClickTime = now;
      return true;
    } catch (error) {
      this.error('Click rate limiting error:', error);
      return false;
    }
  }
  /**
   * Modifies a token by appending a string with safe splitting.
   * @param {string} token - Original token
   * @param {string} string - String to append
   * @returns {string} Modified token
   */
  modifyToken(token = '', string = '') {
    try {
      const parts = token.split('_');
      while (parts.length < 5) parts.push('');
      parts[4] += string;
      return parts.join('_');
    } catch (error) {
      this.error('Token modification error:', error);
      return token;
    }
  }
  /**
   * Sends an action request to the server using async/await with full retry logic.
   * @param {string} token - Action token
   * @param {Object} [additionalData={}] - Additional data to send
   * @returns {Promise<Object>} Axios response
   */
  async requestAction(token = '', additionalData = {}) {
    try {
      if (!token) throw new Error('Token is required');
      const actionUrl =
        this.modal === 'system'
          ? `${this.baseUrl}/skeleton-action/${token}`
          : `${this.baseUrl}/lander-action/${token}`;
      return await this.axiosRequest({
        method: 'post',
        url: actionUrl,
        data: { skeleton_token: token, ...additionalData },
        headers: { 'X-CSRF-TOKEN': this.csrfToken },
        requestId: `action-${token}`,
      });
    } catch (error) {
      this.error('Request action error:', error);
      throw error;
    }
  }
  /**
   * Sets up Axios interceptors for request queuing and cancellation with modern Promise handling.
   */
  setupAxiosInterceptors() {
    try {
      if (!window.axios) throw new Error('Axios not loaded');
      axios.interceptors.request.use(
        async (config) => {
          while (this.activeRequests >= this.maxConcurrentRequests) {
            await new Promise(resolve => this.requestQueue.push({ config, resolve }));
          }
          this.activeRequests++;
          config.cancelToken = new axios.CancelToken((cancel) => this.cancelTokens.set(config.url, cancel));
          config.timeout = this.requestTimeout;
          return config;
        },
        (error) => {
          this.error('Axios request interceptor error:', error);
          return Promise.reject(error);
        }
      );
      axios.interceptors.response.use(
        async (response) => {
          this.activeRequests--;
          this.cancelTokens.delete(response.config.url);
          await this.processQueue();
          return response;
        },
        async (error) => {
          this.activeRequests--;
          if (error.config) this.cancelTokens.delete(error.config.url);
          await this.processQueue();
          this.error('Axios response interceptor error:', error);
          return Promise.reject(error);
        }
      );
      this.log('Axios interceptors set up successfully');
    } catch (error) {
      this.error('Error setting up Axios interceptors:', error);
    }
  }
  /**
   * Processes the request queue asynchronously.
   */
  async processQueue() {
    try {
      while (this.requestQueue.length > 0 && this.activeRequests < this.maxConcurrentRequests) {
        const { config, resolve } = this.requestQueue.shift();
        resolve(config);
      }
    } catch (error) {
      this.error('Error processing request queue:', error);
    }
  }
  /**
   * Generates a form class based on token or key with safe string manipulation.
   * @param {string} tokenOrKey - Token or key
   * @returns {string} Form class
   */
  generateFormClass(tokenOrKey = '') {
    try {
      return tokenOrKey
        ? `skeleton-form-${tokenOrKey.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')}`
        : 'default-form';
    } catch (error) {
      this.error('Error generating form class:', error);
      return 'default-form';
    }
  }
  /**
   * Initializes tooltips with Bootstrap and error handling.
   */
  tooltip() {
    try {
      const elements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
      if (!elements.length) return;
      if (!bootstrap?.Tooltip) throw new Error('Bootstrap Tooltip not available');
      Array.from(elements).forEach((element) => {
        try {
          new bootstrap.Tooltip(element);
        } catch (error) {
          this.error('Error initializing tooltip for element:', error, element);
        }
      });
      this.log('Tooltips initialized');
    } catch (error) {
      this.error('Error initializing tooltips:', error);
    }
  }
  /**
   * Initializes popovers with Bootstrap and error handling.
   */
  popover() {
    try {
      const elements = document.querySelectorAll('[data-bs-toggle="popover"]');
      if (!elements.length) return;
      if (!bootstrap?.Popover) throw new Error('Bootstrap Popover not available');
      Array.from(elements).forEach((element) => {
        try {
          new bootstrap.Popover(element);
        } catch (error) {
          this.error('Error initializing popover for element:', error, element);
        }
      });
      this.log('Popovers initialized');
    } catch (error) {
      this.error('Error initializing popovers:', error);
    }
  }
  /**
   * Handles action containers and tabs with event delegation.
   */
  actions() {
    try {
      const containers = document.querySelectorAll('.data-skl-action');
      containers.forEach((container) => {
        const containerId = container.id;
        if (!containerId) return;
        const tabLinks = container.querySelectorAll('[data-skl-action]');
        tabLinks.forEach((link) => {
          link.addEventListener('click', () => {
            try {
              const action = link.getAttribute('data-skl-action') || '';
              const token = link.getAttribute('data-token') || '';
              const targetSelector = link.getAttribute('data-target') || '';
              const targetBtn = targetSelector ? document.querySelector(targetSelector) : null;
              const updateButton = () => {
                if (!targetBtn) return;
                const text = link.getAttribute('data-text');
                const classes = (link.getAttribute('data-class') || '').split(/\s+/).filter(Boolean);
                if (text) targetBtn.textContent = text;
                targetBtn.setAttribute('data-type', link.getAttribute('data-type') || '');
                targetBtn.setAttribute('data-token', token);
                classes.forEach((cls) => {
                  if (!targetBtn.classList.contains(cls)) targetBtn.classList.add(cls);
                  if (cls === 'd-none') targetBtn.classList.remove('d-block');
                  if (cls === 'd-block') targetBtn.classList.remove('d-none');
                });
              };
              if (action.includes('b')) updateButton();
              if (action.includes('r')) {
                window.skeleton?.reloadTable?.(token);
                window.skeleton?.reloadCard?.(token);
              }
              this.manageTabState({ action: 'set', name: containerId, value: link.id, hours: 1 });
            } catch (error) {
              this.error('Tab action error:', error);
            }
          });
        });
        const savedTabId = this.manageTabState({ action: 'get', name: containerId });
        const savedTab = savedTabId ? document.getElementById(savedTabId) : null;
        const fallbackTab = container.querySelector('.nav-link');
        const tabToActivate = savedTab || fallbackTab;
        if (tabToActivate && window.bootstrap?.Tab) {
          const bsTab = new bootstrap.Tab(tabToActivate);
          bsTab.show();
          tabToActivate.click();
        }
      });
    } catch (error) {
      this.error('Error initializing actions:', error);
    }
  }
  /**
   * Manages tab state using localStorage or cookies with try-catch.
   * @param {Object} options - Tab state options
   * @param {string} options.action - Action ('set', 'get')
   * @param {string} options.name - Tab name
   * @param {string} [options.value] - Tab value (for set action)
   * @param {number} [options.hours] - Expiry in hours (for set action)
   * @returns {string|null} Tab value or null
   */
  manageTabState({ action, name, value, hours = 1 } = {}) {
    try {
      const key = `tab-${name}`;
      if (action === 'set') {
        if (!name || !value) throw new Error('Name and value required for set action');
        if (window.localStorage) {
          localStorage.setItem(key, value);
        } else {
          this.manageCookie({ action: 'set', name: key, value, hours });
        }
      } else if (action === 'get') {
        if (!name) throw new Error('Name required for get action');
        if (window.localStorage) {
          return localStorage.getItem(key);
        } else {
          return this.manageCookie({ action: 'get', name: key });
        }
      }
      return null;
    } catch (error) {
      this.error('Tab state management error:', error);
      return null;
    }
  }
  /**
   * Configures toast notifications with error handling.
   */
  configureToast() {
    try {
      if (typeof window.cssToast === 'undefined') {
        throw new Error('cssToast library not loaded');
      }
      window.cssToast.settings({
        position: 'bottomRight',
        timeout: false,
        close: true,
        pauseOnHover: true,
        transitionIn: 'fadeInUp',
        transitionOut: 'fadeOutDown',
        theme: 'light',
      });
      this.log('Toast configuration completed');
    } catch (error) {
      this.error('Error configuring toasts:', error);
    }
  }
  /**
   * Shows a toast notification with cache check.
   * @param {Object} options - Toast options
   * @param {string} options.icon - Toast icon type
   * @param {string} [options.title=''] - Toast title
   * @param {string} [options.message=''] - Toast message
   * @param {number} [options.duration=5000] - Toast duration
   */
  showToast({ icon, title = '', message = '', duration = 5000 }) {
    try {
      const toastKey = `${icon}:${title}:${message}`;
      if (this.toastCache.has(toastKey)) {
        if (this.developerMode) {
          this.showToast({
            icon: 'dark',
            title: 'Developer Warning',
            message: `Duplicate toast suppressed: ${title} - ${message}`,
            duration: 5000,
          });
        }
        return;
      }
      if (typeof window.cssToast === 'undefined') {
        throw new Error('cssToast not loaded');
      }
      const options = {
        title,
        message,
        timeout: duration,
        theme: 'light',
        ...this.getToastStyles(icon),
      };
      window.cssToast.show(options);
      this.toastCache.set(toastKey, true);
      setTimeout(() => this.toastCache.delete(toastKey), this.toastTimeout);
      this.log('Toast shown:', title);
    } catch (error) {
      this.error('Error showing toast:', error);
    }
  }
  /**
   * Gets toast styles based on icon type with fallback.
   * @param {string} icon - Toast icon type
   * @returns {Object} Toast styles
   */
  getToastStyles(icon) {
    try {
      const styles = {
        success: {
          titleColor: '#ffffff',
          messageColor: '#ffffff',
          icon: 'fa fa-check-circle',
          backgroundColor: '#00ee36',
          iconColor: '#ffffff',
        },
        error: {
          titleColor: '#ffffff',
          messageColor: '#ffffff',
          icon: 'fa fa-times-circle',
          backgroundColor: '#ff0018',
          iconColor: '#ffffff',
        },
        warning: {
          icon: 'fa fa-exclamation-triangle',
          backgroundColor: '#ffd000',
          iconColor: '#212529',
        },
        info: {
          icon: 'fa fa-info-circle',
          backgroundColor: '#00d5ff',
          iconColor: '#ffffff',
        },
        question: {
          titleColor: '#ffffff',
          messageColor: '#ffffff',
          icon: 'fa fa-circle-question',
          backgroundColor: '#0087ff',
          iconColor: '#ffffff',
        },
        light: {
          icon: 'fa fa-message-lines',
          backgroundColor: '#dedede',
          iconColor: '#333333',
        },
        dark: {
          titleColor: '#ffffff',
          messageColor: '#ffffff',
          icon: 'fa fa-message-lines',
          backgroundColor: '#002343',
          iconColor: '#ffffff',
        },
        default: {
          icon: 'fa fa-bell',
          backgroundColor: '#6c757d',
          iconColor: '#ffffff',
        },
      };
      return styles[icon] || styles.default;
    } catch (error) {
      this.error('Error getting toast styles:', error);
      return styles.default;
    }
  }
  /**
   * Shows a success toast.
   * @param {string} title - Toast title
   * @param {string} message - Toast message
   * @param {number} [duration=5000] - Toast duration
   */
  successToast(title, message, duration = 5000) {
    this.showToast({ icon: 'success', title, message, duration });
  }
  /**
   * Shows an error toast.
   * @param {string} title - Toast title
   * @param {string} message - Toast message
   * @param {number} [duration=5000] - Toast duration
   */
  errorToast(title, message, duration = 5000) {
    this.showToast({ icon: 'error', title, message, duration });
  }
  /**
   * Shows a warning toast.
   * @param {string} title - Toast title
   * @param {string} message - Toast message
   * @param {number} [duration=5000] - Toast duration
   */
  warningToast(title, message, duration = 5000) {
    this.showToast({ icon: 'warning', title, message, duration });
  }
  /**
   * Shows a SweetAlert2 popup notification with cache and error handling.
   * @param {Object} options - Alert options
   * @param {string} options.icon - Alert icon type ('success', 'error', 'warning', 'info', 'question')
   * @param {string} [options.title=''] - Alert title
   * @param {string} [options.message=''] - Alert message
   * @param {number} [options.duration=5000] - Alert duration in milliseconds (0 for no auto-close)
   * @param {boolean} [options.showConfirmButton=true] - Whether to show the confirm button
   */
  showAlert({ icon, title = '', message = '', duration = 5000, showConfirmButton = true }) {
    try {
      const alertKey = `${icon}:${title}:${message}`;
      if (this.alertCache.has(alertKey)) {
        if (this.developerMode) {
          this.showToast({
            icon: 'dark',
            title: 'Developer Warning',
            message: `Duplicate alert suppressed: ${title} - ${message}`,
            duration: 5000,
          });
        }
        return;
      }
      if (typeof window.Swal === 'undefined') {
        throw new Error('SweetAlert2 not loaded');
      }
      const options = {
        icon,
        title,
        text: message,
        timer: duration > 0 ? duration : undefined,
        showConfirmButton,
        ...this.getAlertStyles(icon),
      };
      window.Swal.fire(options);
      this.alertCache.set(alertKey, true);
      if (duration > 0) {
        setTimeout(() => this.alertCache.delete(alertKey), duration);
      }
      this.log('Alert shown:', title);
    } catch (error) {
      this.error('Error showing alert:', error);
    }
  }
  /**
   * Gets SweetAlert2 styles based on icon type with fallback.
   * @param {string} icon - Alert icon type
   * @returns {Object} Alert styles
   */
  getAlertStyles(icon) {
    try {
      const styles = {
        success: {
          iconColor: '#00ee36',
          confirmButtonColor: '#00ee36',
          background: '#ffffff',
          color: '#212529',
        },
        error: {
          iconColor: '#ff0018',
          confirmButtonColor: '#ff0018',
          background: '#ffffff',
          color: '#212529',
        },
        warning: {
          iconColor: '#ffd000',
          confirmButtonColor: '#ffd000',
          background: '#ffffff',
          color: '#212529',
        },
        info: {
          iconColor: '#00d5ff',
          confirmButtonColor: '#00d5ff',
          background: '#ffffff',
          color: '#212529',
        },
        question: {
          iconColor: '#0087ff',
          confirmButtonColor: '#0087ff',
          background: '#ffffff',
          color: '#212529',
        },
        default: {
          iconColor: '#6c757d',
          confirmButtonColor: '#6c757d',
          background: '#ffffff',
          color: '#212529',
        },
      };
      return styles[icon] || styles.default;
    } catch (error) {
      this.error('Error getting alert styles:', error);
      return styles.default;
    }
  }
  /**
   * Shows a success alert.
   * @param {string} title - Alert title
   * @param {string} message - Alert message
   * @param {number} [duration=5000] - Alert duration
   * @param {boolean} [showConfirmButton=true] - Whether to show the confirm button
   */
  successAlert(title, message, duration = 5000, showConfirmButton = true) {
    this.showAlert({ icon: 'success', title, message, duration, showConfirmButton });
  }
  /**
   * Shows an error alert.
   * @param {string} title - Alert title
   * @param {string} message - Alert message
   * @param {number} [duration=5000] - Alert duration
   * @param {boolean} [showConfirmButton=true] - Whether to show the confirm button
   */
  errorAlert(title, message, duration = 5000, showConfirmButton = true) {
    this.showAlert({ icon: 'error', title, message, duration, showConfirmButton });
  }
  /**
   * Shows a warning alert.
   * @param {string} title - Alert title
   * @param {string} message - Alert message
   * @param {number} [duration=5000] - Alert duration
   * @param {boolean} [showConfirmButton=true] - Whether to show the confirm button
   */
  warningAlert(title, message, duration = 5000, showConfirmButton = true) {
    this.showAlert({ icon: 'warning', title, message, duration, showConfirmButton });
  }
  /**
   * Shows an info alert.
   * @param {string} title - Alert title
   * @param {string} message - Alert message
   * @param {number} [duration=5000] - Alert duration
   * @param {boolean} [showConfirmButton=true] - Whether to show the confirm button
   */
  infoAlert(title, message, duration = 5000, showConfirmButton = true) {
    this.showAlert({ icon: 'info', title, message, duration, showConfirmButton });
  }
  /**
   * Shows a question alert.
   * @param {string} title - Alert title
   * @param {string} message - Alert message
   * @param {number} [duration=5000] - Alert duration
   * @param {boolean} [showConfirmButton=true] - Whether to show the confirm button
   */
  questionAlert(title, message, duration = 5000, showConfirmButton = true) {
    this.showAlert({ icon: 'question', title, message, duration, showConfirmButton });
  }
  /**
   * Performs an Axios request with retries, progress tracking, and cancellation using full async/await.
   * Supports multiple simultaneous requests via queuing.
   * @param {Object} config - Axios config
   * @param {string} config.method - HTTP method
   * @param {string} config.url - Request URL
   * @param {Object} [config.data={}] - Request data
   * @param {Object} [config.headers={}] - Request headers
   * @param {string} [config.requestId] - Request ID
   * @param {Function} [config.onProgress] - Progress callback
   * @param {number} [config.retry=this.retryAttempts] - Retry attempts
   * @returns {Promise<Object>} Axios response
   */
  async axiosRequest({
    method = 'get',
    url,
    data = {},
    headers = {},
    requestId = `req-${Date.now()}`,
    onProgress = null,
    retry = this.retryAttempts,
  }) {
    if (!url) throw new Error('URL is required');
    const config = {
      method,
      url: url.startsWith('http') ? url : `${this.baseUrl}${url}`,
      data,
      headers: { 'X-CSRF-TOKEN': this.csrfToken, ...headers },
      onUploadProgress: (progressEvent) => {
        if (onProgress && progressEvent.total) {
          const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          onProgress({ requestId, type: 'upload', percent });
          this.log(`Upload progress for ${requestId}: ${percent}%`);
        }
      },
      onDownloadProgress: (progressEvent) => {
        if (onProgress && progressEvent.total) {
          const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          onProgress({ requestId, type: 'download', percent });
          this.log(`Download progress for ${requestId}: ${percent}%`);
        }
      },
    };
    let attempts = 0;
    while (attempts <= retry) {
      try {
        this.concurrentTasks.add(requestId);
        const response = await axios(config);
        this.concurrentTasks.delete(requestId);
        return response;
      } catch (error) {
        attempts++;
        this.concurrentTasks.delete(requestId);
        if (attempts > retry || axios.isCancel(error)) {
          throw error;
        }
        this.log(`Retrying ${method.toUpperCase()} request to ${url} (${attempts}/${retry})`);
        await new Promise((resolve) => setTimeout(resolve, this.retryDelay * attempts)); // Exponential backoff
      }
    }
  }
  /**
   * Initializes clone functionality for elements with data-clone attribute using event delegation.
   */
  clone() {
    try {
      const sources = document.querySelectorAll('[data-clone]');
      const debouncedHandler = this.debounce(() => { }, 100);
      Array.from(sources).forEach(source => {
        if (source.disabled) return;
        const targetSelector = source.dataset.clone;
        if (!targetSelector) return;
        const events = ['input', 'change'];
        const handler = (e) => {
          try {
            const type = (source.type || '').toLowerCase();
            const tag = source.tagName.toLowerCase();
            const value = this.getValue(source, tag, type);
            const targets = document.querySelectorAll(targetSelector);
            if (!targets.length) return;
            Array.from(targets).forEach(target => {
              if (target.disabled) return;
              const targetType = (target.type || '').toLowerCase();
              const targetTag = target.tagName.toLowerCase();
              this.setValue(target, targetTag, targetType, value, source);
            });
          } catch (err) {
            this.showToast({
              icon: 'error',
              title: 'Clone Error',
              message: 'Failed to process clone operation',
              duration: 5000,
            });
          }
        };
        events.forEach(event => {
          source.addEventListener(event, handler);
        });
      });
      this.log('Clone functionality initialized');
    } catch (error) {
      this.error('Error initializing clone:', error);
    }
  }
  getValue(el, tag, type) {
    if (type === 'checkbox') {
      return el.checked ? 'Checked' : 'Unchecked';
    } else if (type === 'radio') {
      return el.checked ? el.value : null;
    } else if (type === 'file') {
      return el.files.length > 0 ? el.files[0].name : '';
    } else if (tag === 'input' || tag === 'select' || tag === 'textarea') {
      return el.value;
    } else {
      return el.textContent;
    }
  }
  setValue(target, tag, type, value, source) {
    if (value === null) return;
    if (type === 'checkbox') {
      target.checked = value === 'Checked';
    } else if (type === 'radio') {
      if (target.value === source.value) {
        target.checked = true;
      }
    } else if (tag === 'select') {
      const options = Array.from(target.options);
      const matchingOption = options.find(opt =>
        opt.value.toLowerCase() === value.toLowerCase()
      );
      if (matchingOption) {
        target.value = matchingOption.value;
      }
    } else if (tag === 'input' || tag === 'textarea') {
      target.value = value;
    } else {
      target.textContent = value;
    }
  }
  /**
   * Initializes copy functionality for elements with data-copy attribute.
   */
  copy() {
    try {
      const copyElements = document.querySelectorAll('[data-copy]');
      if (!copyElements.length) return;
      Array.from(copyElements).forEach(el => {
        if (el.disabled) return;
        el.addEventListener('click', async () => {
          try {
            const copyValue = el.dataset.copy;
            let textToCopy = '';
            if (copyValue === 'this') {
              textToCopy = el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' ? el.value : el.textContent;
            } else {
              const target = document.querySelector(copyValue);
              if (target) {
                textToCopy = target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT'
                  ? target.value
                  : target.textContent;
              }
            }
            if (!textToCopy) {
              this.showToast({
                icon: 'warning',
                title: 'No Text',
                message: 'No text found to copy',
                duration: 5000,
              });
              return;
            }
            if (navigator.clipboard && window.isSecureContext) {
              await navigator.clipboard.writeText(textToCopy);
              this.showToast({
                icon: 'success',
                title: 'Copied!',
                message: 'Text copied to clipboard',
                duration: 5000,
              });
            } else {
              this.fallbackCopy(textToCopy);
            }
          } catch (err) {
            this.showToast({
              icon: 'error',
              title: 'Copy Error',
              message: 'Failed to process copy operation',
              duration: 5000,
            });
          }
        });
      });
      this.log('Copy functionality initialized');
    } catch (error) {
      this.error('Error initializing copy:', error);
    }
  }
  fallbackCopy(text) {
    try {
      const textarea = document.createElement('textarea');
      textarea.value = text;
      textarea.style.position = 'fixed';
      textarea.style.opacity = '0';
      document.body.appendChild(textarea);
      textarea.focus();
      textarea.select();
      document.execCommand('copy');
      document.body.removeChild(textarea);
      this.showToast({
        icon: 'success',
        title: 'Copied!',
        message: 'Text copied to clipboard (fallback)',
        duration: 5000,
      });
    } catch (err) {
      this.showToast({
        icon: 'error',
        title: 'Copy Failed',
        message: 'Unable to copy text (fallback)',
        duration: 5000,
      });
    }
  }
  /**
   * Returns HTML for a 400 error division.
   * @returns {string} HTML string
   */
  errorDiv400() {
    try {
      return `
        <div class="d-flex flex-column align-items-center justify-content-center text-center w-100" style="height:calc(100vh - 200px) !important;">
          <img src="/errors/400.svg" alt="Something Went Wrong" class="img-fluid mb-2 h-50" />
          <h1 class="h3 mb-2 fw-bold">Let’s Try That Again</h1>
          <p class="text-muted mb-2" style="max-width: 600px;">
            Something didn’t go quite right. It might be a small hiccup. Please refresh the page or go back and try again.
          </p>
          <div class="d-flex flex-wrap justify-content-center gap-3 mt-2">
            <a href="javascript:location.reload();" class="btn btn-outline-secondary rounded-pill">Refresh Page</a>
            <a href="javascript:history.back();" class="btn btn-primary rounded-pill">Go Back</a>
          </div>
        </div>`;
    } catch (error) {
      this.error('Error generating 400 error div:', error);
      return '<div>Error generating 400 error page</div>';
    }
  }
  /**
   * Returns HTML for a 500 error division.
   * @returns {string} HTML string
   */
  errorDiv500() {
    try {
      return `
        <div class="d-flex flex-column align-items-center justify-content-center text-center w-100" style="height:calc(100vh - 200px) !important;">
          <img src="/errors/500.svg" alt="Server Issue" class="img-fluid mb-2 h-50" />
          <h1 class="h3 mb-2 fw-bold">Oops! Something Broke</h1>
          <p class="text-muted mb-2" style="max-width: 600px;">
            We’re fixing things on our end right now.
          </p>
          <p class="text-muted" style="max-width: 600px;">
            Please try again in a few moments, or refresh the page.
          </p>
          <div class="d-flex flex-wrap justify-content-center gap-3 mt-2">
            <a href="javascript:location.reload();" class="btn btn-outline-danger rounded-pill">Try Again</a>
            <a href="javascript:history.back();" class="btn btn-primary rounded-pill">Go Back</a>
          </div>
        </div>`;
    } catch (error) {
      this.error('Error generating 500 error div:', error);
      return '<div>Error generating 500 error page</div>';
    }
  }
  /**
   * Returns HTML for an empty error division.
   * @returns {string} HTML string
   */
  errorDivEmpty() {
    try {
      return `
        <div class="d-flex flex-column align-items-center justify-content-center text-center w-100" style="height:calc(100vh - 200px) !important;">
          <img src="/errors/empty.svg" alt="No Content" class="img-fluid mb-2 h-50" />
          <h1 class="h3 mb-2 fw-bold">Nothing Here Yet</h1>
          <p class="text-muted" style="max-width: 600px;">
            This content is currently empty — it's just a placeholder for now. Feel free to explore other sections while we get things set up here.
          </p>
          <div class="d-flex flex-wrap justify-content-center gap-3 mt-2">
            <a href="/" class="btn btn-outline-secondary rounded-pill">Go to Home</a>
            <a href="javascript:history.back();" class="btn btn-primary rounded-pill">Go Back</a>
          </div>
        </div>`;
    } catch (error) {
      this.error('Error generating empty error div:', error);
      return '<div>Error generating empty error page</div>';
    }
  }
  /**
   * Sets up global event listeners for popup triggers, form submissions, and validation with delegation.
   */
  async setupEventListeners() {
    try {
      document.addEventListener('click', async (e) => {
        const target = e.target.closest('.skeleton-popup');
        if (!target) return;
        e.preventDefault();
        await this.showForm({
          element: target,
          token: target.dataset.token || '',
          id: target.dataset.id || '',
        });
      });
      document.addEventListener('submit', async (e) => {
        const form = e.target.closest('form');
        if (!form) return;
        const prevent = form.dataset.prevent || 'y';
        if (prevent === 'y') {
          e.preventDefault();
        }
        this.currentForm = form;
        const button = form.querySelector('[type="submit"]');
        if (!button) return;
        const beforeText = button.dataset.beforeText || 'Saving...';
        const afterText = button.dataset.afterText || 'Saved';
        if (this.validateForm({ isSubmit: true }) && prevent === 'y') {
          await this.saveForm({ formElement: form, beforeText, afterText });
        } else {
          button.disabled = true;
          button.classList.add('disabled');
          button.innerHTML = `${beforeText} <i class="fa-solid fa-arrows-rotate fa-spin"></i>`;
        }
      });
      ['blur', 'change', 'focus', 'paste', 'cut'].forEach((event) => {
        document.addEventListener(
          event,
          (e) => {
            const element = e.target.closest('input[data-validate], select[data-validate]');
            if (!element) return;
            const form = element.closest('form');
            if (!form) return;
            this.currentForm = form;
            this.validateForm();
          },
          event === 'blur' ? { capture: true } : false
        );
      });
      this.log('Event listeners set up successfully');
    } catch (error) {
      this.error('Error setting up event listeners:', error);
    }
  }
  /**
   * Displays a popup (modal or offcanvas) based on the provided token with async handling.
   * @param {Object} options - Popup options
   * @param {HTMLElement} [options.element] - Trigger element
   * @param {string} [options.token=''] - Token for AJAX request
   * @param {string} [options.id=''] - ID for AJAX request
   * @returns {Promise<void>}
   */
  async showForm({ element, token = '', id = '' } = {}) {
    if (!this.canProceed()) {
      this.log('Cannot proceed with popup display');
      return;
    }
    let originalHtml = '';
    const loadingText = element?.dataset?.loadingText || '';
    try {
      if (element) {
        originalHtml = element.innerHTML || '';
        element.disabled = true;
        element.classList.add('disabled');
        element.innerHTML = loadingText
          ? `${loadingText} <i class="fa-light fa-arrows-rotate fa-spin"></i>`
          : '<i class="fa-light fa-arrows-rotate fa-spin"></i>';
      }
      const response = await this.requestAction(token, { id });
      if (!response?.data) {
        throw new Error('Invalid or empty response data');
      }
      const actionUrl =
        this.modal === 'system'
          ? `${this.baseUrl}/skeleton-action/${this.modifyToken(token, 's')}`
          : `${this.baseUrl}/lander-action/${this.modifyToken(token, 's')}`;
      await this.handlePopupSuccess(response.data, element, originalHtml, actionUrl);
    } catch (error) {
      this.error('Error in showForm:', error);
      this.errorToast('Popup Error', error.message || 'Failed to load popup content');
    } finally {
      if (element) {
        element.disabled = false;
        element.classList.remove('disabled');
        element.innerHTML = originalHtml;
      }
    }
  }
  /**
   * Saves form data via AJAX submission with async/await.
   * @param {Object} options - Save options
   * @param {HTMLFormElement} [options.formElement] - Form element
   * @param {string} [options.beforeText='Saving'] - Button text during submission
   * @param {string} [options.afterText='Saved'] - Button text after submission
   * @returns {Promise<void>}
   */
  async saveForm({ formElement, beforeText = 'Saving', afterText = 'Saved' } = {}) {
    if (!formElement) {
      this.error('No form element provided for saveForm');
      return;
    }
    const button = formElement.querySelector('button[type="submit"]');
    let originalHtml = '';
    try {
      if (button) {
        originalHtml = button.innerHTML || '';
        button.disabled = true;
        button.classList.add('disabled');
        button.innerHTML = `${beforeText} <i class="fa-solid fa-arrows-rotate fa-spin"></i>`;
      }
      const formData = new FormData(formElement);
      const response = await window.axios.post(formElement.action, formData, {
        headers: {
          'X-CSRF-TOKEN': this.csrfToken,
          'Content-Type': 'multipart/form-data',
        },
      });
      if (!response?.data) {
        throw new Error('Invalid response data');
      }
      await this.handleSaveSuccess(response.data, formElement, button, originalHtml, afterText);
    } catch (error) {
      this.error('Error in saveForm:', error);
      this.errorToast(
        error.response?.data?.title || 'Error',
        error.response?.data?.message || 'Failed to save form data'
      );
    } finally {
      if (button) {
        button.disabled = false;
        button.classList.remove('disabled');
        button.innerHTML = originalHtml;
      }
    }
  }
  /**
   * Handles successful form submission responses with async script execution.
   * @param {Object} data - Server response data
   * @param {HTMLFormElement} formElement - Form element
   * @param {HTMLButtonElement} button - Submit button
   * @param {string} originalHtml - Original button HTML
   * @param {string} afterText - Text after submission
   */
  async handleSaveSuccess(data, formElement, button, originalHtml, afterText) {
    try {
      if (button) {
        button.disabled = false;
        button.classList.remove('disabled');
        button.innerHTML = afterText || originalHtml;
      }
      if (!data?.status) {
        if (data?.alert) {
          this.errorAlert(data?.title || 'Error', data?.message || 'Operation failed');
        } else {
          this.errorToast(data?.title || 'Error', data?.message || 'Operation failed');
        }
        if (data?.errors) {
          this.error('Operation errors:', data.errors);
        }
        return;
      }
      if (data.redirect) {
        window.location.href = data.redirect;
        return;
      }
      const modal = formElement.closest('.modal');
      const offcanvas = formElement.closest('.offcanvas');
      const instance = modal
        ? window.bootstrap?.Modal.getInstance(modal)
        : offcanvas
          ? window.bootstrap?.Offcanvas.getInstance(offcanvas)
          : null;
      if (data.token && data.reload_table) {
        const tableId = data.reload_table === true ? `${data.token}_t` : `${data.token}_t_${data.reload_table}`;
        window.skeleton?.reloadTable?.(tableId);
      }
      if (data.token && data.reload_card) {
        const cardId = data.reload_card === true ? `${data.token}_c` : `${data.token}_c_${data.reload_card}`;
        window.skeleton?.reloadCard?.(cardId);
      }
      if (data.reload_page) {
        window.location.reload(true);
      }
      if (data?.script) {
        try {
          const result = typeof data.script === 'function' ? await data.script() : await new Function('"use strict"; ' + data.script)();
        } catch (error) {
          this.error('Script execution failed:', error);
        }
      }
      if (!data.hold_popup) {
        instance?.hide();
      }
      // Reset the form on success
      formElement.reset();
      if (data?.alert) {
        this.successAlert(data.title || 'Success', data.message || 'Operation successful');
      } else {
        this.successToast(data.title || 'Success', data.message || 'Operation successful');
      }
      this.log('Form save handled successfully');
    } catch (error) {
      this.error('Error in handleSaveSuccess:', error);
      if (data?.alert) {
        this.errorAlert('Error', 'Failed to handle form submission');
      } else {
        this.errorToast('Error', 'Failed to handle form submission');
      }
    }
  }
  /**
   * Handles successful popup data responses with async rendering.
   * @param {Object} data - Server response data
   * @param {HTMLElement} [element] - Trigger element
   * @param {string} originalHtml - Original element HTML
   * @param {string} formUrl - Form submission URL
   * @returns {Promise<void>}
   */
  async handlePopupSuccess(data, element, originalHtml, formUrl) {
    try {
      if (element) {
        element.disabled = false;
        element.classList.remove('disabled');
        element.innerHTML = originalHtml;
      }
      if (!data || typeof data !== 'object' || !data.status) {
        throw new Error(data?.message || 'Invalid popup data');
      }
      const formClass = this.generateFormClass(data.token || 'default');
      const isModal = data.type === 'modal';
      const validateClass = data.type === 'validate' ? 'was-validated' : '';
      const popupClass = isModal ? `skeleton-modal-${formClass}` : `skeleton-offcanvas-${formClass}`;
      const modalType = this.modal === 'system' ? 'skeleton' : 'lander';
      document.querySelectorAll(`.${popupClass}, form.${formClass}`).forEach((el) => el.remove());
      const popupHTML = isModal
        ? `
        <div class="modal fade ${modalType}-modal ${popupClass}" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
          <div class="modal-dialog modal-dialog-centered ${data.size || 'modal-lg'} resizable-modal">
            <div class="modal-content draggable-modal">
              <div class="modal-header ${modalType}-modal-header ${data.header === 'hide' ? 'd-none' : ''}">
                <div class="${modalType}-mdl-hdr-lbl-grp">
                  <button type="button" class="btn modal-drag-handle"><span>⋮⋮</span></button>
                  <div class="d-flex flex-column">
                    <h5 class="modal-title ${modalType}-modal-label m-0">${data.label || 'Info'}</h5>
                    <label class="sf-11">${data.short_label || ''}</label>
                  </div>
                </div>
                <div class="${modalType}-mdl-hdr-btn-grp">
                  ${modalType === 'system' ? `
                    <button type="button" class="download-btn ${data.download_btn || 'd-none'}"><i class="fa-light fa-download"></i></button>
                    <button type="button" class="share-btn ${data.share_btn || 'd-none'}"><i class="fa-light fa-share-from-square"></i></button>
                  ` : ''}
                  <button type="button" class="modal-data-reload-btn update-form-data-dyn ${data.reload_btn || 'd-block'}" data-form-name=".${formClass}"><i class="fa-light fa-refresh"></i></button>
                  <button type="button" class="modal-fullscreen-btn ${data.fullscreen_btn || 'd-block'}"><i class="fa-light fa-expand"></i></button>
                  <button type="button" class="${data.top_close_btn || 'd-block'}" data-bs-dismiss="modal"><i class="fa fa-times"></i></button>
                </div>
              </div>
              <form action="${formUrl || ''}" method="POST" class="${modalType}-form ${formClass} ${validateClass}" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="${this.csrfToken}">
                <div class="modal-body ${modalType}-modal-body ${data.header === 'hide' || data.footer === 'hide' ? '' : ''}">
                  <div class="p-1px">${data.content || ''}</div>
                </div>
                <div class="modal-footer ${modalType}-modal-footer ${data.footer === 'hide' ? 'd-none' : ''}">
                  <button type="button" class="btn btn-secondary ${modalType}-form-btn btn-md ${data.bottom_close_btn || 'd-block'}" data-bs-dismiss="modal">Close</button>
                  <button type="submit" class="btn btn-md ${modalType}-form-btn ${data.button_class || 'btn-primary'}" data-before-text="${data.load_before_text || 'Saving...'}" data-after-text="${data.load_after_text || 'Saved'}">
                    ${data.button || '<i class="fa-solid fa-plus me-2"></i>Save'}
                  </button>
                </div>
              </form>
              <div class="modal-resize-handle bottom-right"></div>
            </div>
          </div>
        </div>`
        : `
        <form action="${formUrl || ''}" method="POST" class="${modalType}-form ${formClass} ${validateClass}" enctype="multipart/form-data">
          <div class="offcanvas ${modalType}-offcanvas ${popupClass} offcanvas-${data.position || 'end'} ${data.size || ''}" data-bs-backdrop="static" tabindex="-1">
            <div class="offcanvas-header ${modalType}-offcanvas-header ${data.header === 'hide' ? 'd-none' : ''}">
              <div class="d-flex flex-column">
                <h5 class="offcanvas-title ${modalType}-offcanvas-label">${data.label || 'Resizable Off-Canvas'}</h5>
                <label class="sf-11">${data.short_label || ''}</label>
              </div>
              <div class="${modalType}-ofc-hdr-btn-grp">
                ${modalType === 'system' ? `
                  <button type="button" class="download-btn ${data.download_btn || 'd-none'}"><i class="fa-light fa-download"></i></button>
                  <button type="button" class="share-btn ${data.share_btn || 'd-none'}"><i class="fa-light fa-share-from-square"></i></button>
                  <button type="button" class="offcanvas-data-reload-btn update-form-data-dyn ${data.reload_btn || 'd-block'}" data-form-name=".${formClass}"><i class="fa-light fa-refresh"></i></button>
                  <button type="button" class="offcanvas-fullscreen-btn ${data.fullscreen_btn || 'd-block'}"><i class="fa-light fa-expand"></i></button>
                ` : `
                  <button type="button" class="offcanvas-data-reload-btn update-form-data-dyn ${data.reload_btn || 'd-block'}" data-form-name=".${formClass}"><i class="fa-light fa-refresh"></i></button>
                `}
                <button type="button" class="${data.top_close_btn || 'd-block'}" data-bs-dismiss="offcanvas"><i class="fa fa-times"></i></button>
              </div>
            </div>
            <div class="offcanvas-body ${modalType}-offcanvas-body h-100 ${data.header === 'hide' || data.footer === 'hide' ? 'pb-5' : ''}">
              <input type="hidden" name="_token" value="${this.csrfToken}">
              <div class="p-1px">${data.content || ''}</div>
            </div>
            <div class="offcanvas-dragbar"><i class="fa-solid fa-ellipsis-vertical"></i></div>
            <div class="offcanvas-footer text-end ${modalType}-offcanvas-footer ${data.footer === 'hide' ? 'd-none' : ''}">
              <button type="button" class="btn btn-secondary btn-md ${data.bottom_close_btn || 'd-block'}" data-bs-dismiss="offcanvas">Close</button>
              <button type="submit" class="btn ${modalType}-form-btn ${data.button_class || 'btn-primary'}" data-before-text="${data.load_before_text || 'Saving...'}" data-after-text="${data.load_after_text || 'Saved'}">
                ${data.button || '<i class="fa-solid fa-plus me-2"></i>Save'}
              </button>
            </div>
          </div>
        </form>`;
      document.body.insertAdjacentHTML('beforeend', popupHTML.trim());
      const popup = document.querySelector(`.${popupClass}`);
      const form = document.querySelector(`form.${formClass}`);
      if (!popup || !form) throw new Error('Popup or form not found');
      if (data.script) {
        try {
          await new Function(data.script)();
        } catch (error) {
          this.error('Script execution failed:', error);
        }
      }
      const instance = isModal
        ? new window.bootstrap.Modal(popup, { backdrop: 'static', keyboard: false })
        : new window.bootstrap.Offcanvas(popup, { backdrop: 'static' });
      instance?.show();
      const closeEvent = isModal ? 'hidden.bs.modal' : 'hidden.bs.offcanvas';
      popup.addEventListener(
        closeEvent,
        () => {
          (element || document.body).focus();
          popup.remove();
          form.remove();
        },
        { once: true }
      );
      popup.addEventListener(isModal ? 'hide.bs.modal' : 'hide.bs.offcanvas', () => (popup.inert = true), {
        once: true,
      });
      if (isModal) {
        this.makeDraggable(popup);
        this.makeResizableModal(popup);
      } else {
        this.makeResizableOffcanvas(popup);
      }
      this.setupFullscreenToggle(popup);
      this.setupDownloadShareButtons(popup);
      this.setupFormCookieStorage(form, formClass, popup);
      this.setupReloadButton(popup, formClass);
      this.log('Popup rendered successfully');
    } catch (error) {
      this.error('Popup rendering failed:', error);
      if (data?.alert) {
        this.errorAlert('Error', error.message || 'Failed to render popup');
      } else {
        this.errorToast('Error', error.message || 'Failed to render popup');
      }
    }
  }
  /**
   * Makes a modal draggable using interact.js with error handling.
   * @param {HTMLElement} popup - Modal element
   */
  makeDraggable(popup) {
    if (!popup || typeof interact !== 'function') {
      this.error('Interact.js not loaded or popup is invalid');
      return;
    }
    const dragHandle = popup.querySelector('.modal-drag-handle');
    if (!dragHandle) {
      this.log('No drag handle found for modal');
      return;
    }
    try {
      interact(dragHandle).draggable({
        listeners: {
          move: (event) => {
            const modal = event.target.closest('.modal-dialog');
            if (!modal) return;
            const x = (parseFloat(modal.dataset.x) || 0) + event.dx;
            const y = (parseFloat(modal.dataset.y) || 0) + event.dy;
            modal.style.transform = `translate(${x}px, ${y}px)`;
            modal.dataset.x = x;
            modal.dataset.y = y;
          },
        },
        modifiers: [interact.modifiers.restrict({ restriction: 'parent', endOnly: true })],
        inertia: true,
      });
      this.log('Draggable set up for modal');
    } catch (error) {
      this.error('Error in makeDraggable:', error);
    }
  }
  /**
   * Makes a modal resizable using interact.js with bounds.
   * @param {HTMLElement} popup - Modal element
   */
  makeResizableModal(popup) {
    if (!popup || typeof interact !== 'function') {
      this.error('Interact.js not loaded or popup is invalid');
      return;
    }
    const resizeHandle = popup.querySelector('.modal-resize-handle');
    if (!resizeHandle) {
      this.log('No resize handle found for modal');
      return;
    }
    try {
      interact(resizeHandle).resizable({
        edges: { bottom: true, right: true },
        listeners: {
          move: (event) => {
            const modal = event.target.closest('.modal-dialog');
            if (!modal) return;
            const width = Math.max(200, Math.min(event.rect.width, 1200));
            const height = Math.max(200, Math.min(event.rect.height, 1200));
            modal.style.width = `${width}px`;
            modal.style.height = `${height}px`;
            modal.classList.add('resized');
          },
        },
        modifiers: [
          interact.modifiers.restrictSize({ min: { width: 200, height: 200 }, max: { width: 1200, height: 1200 } }),
        ],
        inertia: true,
      });
      this.log('Resizable modal set up');
    } catch (error) {
      this.error('Error in makeResizableModal:', error);
    }
  }
  /**
   * Makes an offcanvas resizable using interact.js.
   * @param {HTMLElement} offcanvas - Offcanvas element
   */
  makeResizableOffcanvas(offcanvas) {
    if (!offcanvas || typeof interact !== 'function') {
      this.error('Interact.js not loaded or offcanvas is invalid');
      return;
    }
    const dragbar = offcanvas.querySelector('.offcanvas-dragbar');
    if (!dragbar) {
      this.log('No dragbar found for offcanvas');
      return;
    }
    try {
      const position = ['end', 'start', 'top', 'bottom'].find((pos) => offcanvas.classList.contains(`offcanvas-${pos}`)) || 'end';
      const direction = position === 'start' || position === 'end' ? 'horizontal' : 'vertical';
      interact(dragbar).draggable({
        listeners: {
          start: () => offcanvas.classList.add('highlight'),
          move: (event) => {
            const rect = offcanvas.getBoundingClientRect();
            const clientX = event.clientX || event.touches?.[0]?.clientX || 0;
            const clientY = event.clientY || event.touches?.[0]?.clientY || 0;
            if (direction === 'horizontal') {
              const newWidth = position === 'start'
                ? Math.max(200, Math.min(clientX - rect.left, 1200))
                : Math.max(200, Math.min(rect.left + rect.width - clientX, 1200));
              offcanvas.style.width = `${newWidth}px`;
            } else {
              const newHeight = position === 'top'
                ? Math.max(200, Math.min(clientY - rect.top, 1200))
                : Math.max(200, Math.min(rect.top + rect.height - clientY, 1200));
              offcanvas.style.height = `${newHeight}px`;
            }
          },
          end: () => offcanvas.classList.remove('highlight'),
        },
        modifiers: [interact.modifiers.restrict({ restriction: 'parent', endOnly: true })],
        axis: direction === 'horizontal' ? 'x' : 'y',
        inertia: true,
      });
      this.log('Resizable offcanvas set up');
    } catch (error) {
      this.error('Error in makeResizableOffcanvas:', error);
    }
  }
  /**
   * Sets up fullscreen toggle for modals and offcanvas.
   * @param {HTMLElement} popup - Modal or offcanvas element
   */
  setupFullscreenToggle(popup) {
    if (!popup) {
      this.log('Popup not found for fullscreen toggle');
      return;
    }
    const btn = popup.querySelector('.modal-fullscreen-btn, .offcanvas-fullscreen-btn');
    if (!btn || !document.fullscreenEnabled) {
      this.log('Fullscreen not supported or button not found');
      return;
    }
    const target = popup.classList.contains('modal') ? popup.querySelector('.modal-dialog') : popup;
    const content = popup.querySelector('.modal-content, .offcanvas-body');
    if (!target || !content) {
      this.log('Target or content not found for fullscreen toggle');
      return;
    }
    const updateState = () => {
      const icon = btn.querySelector('i');
      if (!icon) return;
      if (document.fullscreenElement) {
        target.classList.add('fullscreen');
        content.classList.add('fullscreen');
        icon.classList.replace('fa-expand', 'fa-compress');
      } else {
        target.classList.remove('fullscreen');
        content.classList.remove('fullscreen');
        icon.classList.replace('fa-compress', 'fa-expand');
      }
    };
    btn.addEventListener('click', async () => {
      try {
        if (document.fullscreenElement) {
          await document.exitFullscreen();
        } else {
          await target.requestFullscreen();
        }
      } catch (error) {
        this.error('Error toggling fullscreen:', error);
      }
    });
    document.addEventListener('fullscreenchange', updateState);
    updateState();
    this.log('Fullscreen toggle set up');
  }
  /**
   * Sets up download and share buttons for modals and offcanvas.
   * @param {HTMLElement} popup - Modal or offcanvas element
   */
  setupDownloadShareButtons(popup) {
    if (!popup) {
      this.log('Popup not found for download/share buttons');
      return;
    }
    const downloadBtn = popup.querySelector('.download-btn');
    const shareBtn = popup.querySelector('.share-btn');
    if (downloadBtn) {
      downloadBtn.addEventListener('click', () => {
        this.showToast({
          icon: 'warning',
          title: 'Download',
          message: 'Downloading content...',
          duration: 5000,
        });
        this.log('Download triggered');
      });
    }
    if (shareBtn) {
      shareBtn.addEventListener('click', () => {
        this.showToast({
          icon: 'warning',
          title: 'Share',
          message: 'Sharing content...',
          duration: 5000,
        });
        this.log('Share triggered');
      });
    }
    this.log('Download/share buttons set up');
  }
  /**
   * Sets up form data storage in cookies for persistence with modern storage API.
   * @param {HTMLFormElement} form - Form element
   * @param {string} formClass - Unique form class
   * @param {HTMLElement} popup - Modal or offcanvas element
   */
  setupFormCookieStorage(form, formClass, popup) {
    if (!form || !formClass || !popup) {
      this.log('Invalid parameters for setupFormCookieStorage');
      return;
    }
    const storageKey = `form-data-${formClass}`;
    const updateStorageField = (name, value) => {
      try {
        const existingData = JSON.parse(localStorage.getItem(storageKey) || '{}');
        if (JSON.stringify(existingData[name]) !== JSON.stringify(value)) {
          existingData[name] = value;
          localStorage.setItem(storageKey, JSON.stringify(existingData));
        }
      } catch (error) {
        this.error('Error updating storage field:', error);
      }
    };
    const saveFormData = () => {
      try {
        const existingData = JSON.parse(localStorage.getItem(storageKey) || '{}');
        const formData = {};
        Array.from(form.elements).forEach((el) => {
          const name = el.name;
          if (!name || ['submit', 'button', 'file'].includes(el.type) || el.tagName === 'IMG') return;
          if (el.type === 'checkbox' || el.type === 'radio') {
            if (el.checked) formData[name] = el.value;
          } else if (el.tagName === 'SELECT' && el.multiple) {
            formData[name] = Array.from(el.selectedOptions).map((opt) => opt.value);
          } else {
            formData[name] = el.value;
          }
        });
        localStorage.setItem(storageKey, JSON.stringify({ ...existingData, ...formData }));
      } catch (error) {
        this.error('Error saving form data to storage:', error);
      }
    };
    const handleFieldBlur = (event) => {
      const el = event.target;
      const name = el.name;
      if (!name || ['submit', 'button', 'file'].includes(el.type) || el.tagName === 'IMG') return;
      let value;
      if (el.type === 'checkbox' || el.type === 'radio') {
        value = el.checked ? el.value : '';
      } else if (el.tagName === 'SELECT' && el.multiple) {
        value = Array.from(el.selectedOptions).map((opt) => opt.value);
      } else {
        value = el.value;
      }
      updateStorageField(name, value);
    };
    const handleClose = () => {
      saveFormData();
      popup.removeEventListener('hidden.bs.modal', handleClose);
      popup.removeEventListener('hidden.bs.offcanvas', handleClose);
    };
    popup.addEventListener('hidden.bs.modal', handleClose, { once: true });
    popup.addEventListener('hidden.bs.offcanvas', handleClose, { once: true });
    form.addEventListener('blur', handleFieldBlur, true);
    this.log('Form cookie storage set up');
  }
  clearCookies() {
    try {
      document.cookie.split(';').forEach(cookie => {
        const name = cookie.split('=')[0].trim();
        this.manageCookie({ action: 'delete', name });
      });
      this.log('All cookies cleared');
    } catch (error) {
      this.error('Error clearing cookies:', error);
    }
  }
  /**
   * Sets up the reload button to restore form data from cookies asynchronously.
   * @param {HTMLElement} popup - Modal or offcanvas element
   * @param {string} formClass - Unique form class
   */
  async setupReloadButton(popup, formClass) {
    if (!popup || !formClass) {
      this.log('Popup or formClass not found for reload button');
      return;
    }
    const btn = popup.querySelector('.modal-data-reload-btn, .offcanvas-data-reload-btn');
    const form = document.querySelector(`form.${formClass}`);
    if (!btn || !form) {
      this.log('Reload button or form not found');
      return;
    }
    btn.addEventListener('click', async () => {
      const icon = btn.querySelector('i');
      if (!icon) return;
      try {
        icon.classList.remove('fa-refresh');
        icon.classList.add('fa-arrows-rotate', 'fa-spin');
        const data = JSON.parse(localStorage.getItem(`form-data-${formClass}`) || '{}');
        if (!data || Object.keys(data).length === 0) {
          this.log('No storage data found for form', { formClass });
          return;
        }
        await new Promise((resolve) => setTimeout(resolve, 500)); // Simulate load
        Array.from(form.elements).forEach((el) => {
          const name = el.name;
          if (!name || ['submit', 'button', 'file'].includes(el.type) || el.tagName === 'IMG') return;
          const value = data[name];
          if (value === undefined || value === null) return;
          const isEmpty =
            (el.type === 'checkbox' && !el.checked) ||
            (el.type === 'radio' && !el.checked) ||
            (el.tagName === 'SELECT' && !el.selectedOptions.length) ||
            (el.value === '');
          if (isEmpty) {
            if (el.type === 'checkbox' || el.type === 'radio') {
              el.checked = value === el.value;
            } else if (el.tagName === 'SELECT') {
              const $el = window.jQuery?.(el);
              if ($el?.data('select2')) {
                $el.val(value).trigger('change');
              } else {
                Array.from(el.options).forEach((opt) => {
                  opt.selected = el.multiple ? Array.isArray(value) && value.includes(opt.value) : opt.value === value;
                });
              }
            } else {
              el.value = value;
            }
          }
        });
        this.log('Form data reloaded successfully');
      } catch (error) {
        this.error('Error reloading form data:', error);
      } finally {
        icon.classList.remove('fa-arrows-rotate', 'fa-spin');
        icon.classList.add('fa-refresh');
      }
    });
    this.log('Reload button set up');
  }
  /**
   * Validates a form based on data-validate attributes and required fields with full async support.
   * @param {Object} [options={}] - Validation options
   * @param {boolean} [options.isSubmit=false] - Whether validation is triggered on form submission
   * @returns {boolean} True if form is valid, false otherwise
   */
  async validateForm({ isSubmit = false } = {}) {
    // Validate form availability
    const form = this.currentForm;
    if (!form) {
      return false;
    }
    // Validate jQuery dependency
    if (!window.jQuery) {
      this.error('jQuery is required but not loaded');
      this.showToast({
        icon: 'error',
        title: 'Validation Error',
        message: 'jQuery is required',
        duration: 5000
      });
      return false;
    }
    const $ = window.jQuery;
    // Wait for validation rules if needed
    while (validationRules.length === 0) {
      await new Promise(resolve => setTimeout(resolve, 100));
    }
    if (!validationRules || !Array.isArray(validationRules) || validationRules.length === 0) {
      this.error('Validation rules are missing or invalid');
      this.showToast({
        icon: 'error',
        title: 'Validation Error',
        message: 'Validation rules not loaded',
        duration: 5000
      });
      return false;
    }
    /**
     * Validates an input value against its format asynchronously if needed.
     * @param {string} format - The validation format (e.g., 'email').
     * @param {string} value - The input value to validate.
     * @param {HTMLInputElement|HTMLSelectElement} input - The input element.
     * @returns {boolean} - True if valid, false otherwise.
     */
    const validateInputFormat = async (format, value, input) => {
      // Check for dynamic regex and message
      const dynamicRegex = input.dataset.validateReg;
      if (dynamicRegex) {
        try {
          const regex = new RegExp(dynamicRegex);
          return regex.test(value);
        } catch (e) {
          this.error('Invalid dynamic regex', { regex: dynamicRegex, error: e.message });
          return !input.required;
        }
      }
      // Fallback to predefined rules
      const rule = validationRules.find(r => r.key === format);
      if (!rule) return !input.required;
      if (!value) return !input.required;
      let isValid = new RegExp(rule.regex).test(value);
      if (rule.extraValidation) {
        try {
          const fn = new Function('value', `return ${rule.extraValidation}`);
          isValid = isValid && fn(value);
        } catch (e) {
          this.error('Error in extra validation', { format, error: e.message });
        }
      }
      return isValid;
    };
    /**
     * Validates an input and updates its UI state.
     * @param {HTMLInputElement|HTMLSelectElement} input - The input element.
     * @param {string} value - The input value to validate.
     * @returns {boolean} - True if valid, false otherwise.
     */
    const validateAndUpdate = async (input, value) => {
      const format = input.dataset.validate?.toLowerCase();
      const isValid = await validateInputFormat(format, value, input);
      const parent = input.parentElement;
      const errorClass = parent.classList.contains('float-input-control')
        ? 'skl-error-float-input'
        : 'skl-error-normal-input';
      // Remove existing error icon
      const errorIcon = parent.querySelector('.skl-error-icon');
      if (errorIcon) {
        bootstrap.Tooltip.getInstance(errorIcon)?.dispose();
        errorIcon.remove();
      }
      // Update validity classes
      input.classList.remove('is-invalid', 'is-valid');
      input.classList.add(isValid ? 'is-valid' : 'is-invalid');
      // Add error icon if invalid
      if (!isValid) {
        const dynamicMsg = input.dataset.validateMsg;
        const rule = validationRules.find(r => r.key === format);
        const errorIcon = document.createElement('span');
        errorIcon.className = `skl-error-icon ${errorClass}`;
        errorIcon.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i>';
        errorIcon.setAttribute('data-bs-toggle', 'tooltip');
        errorIcon.setAttribute('data-bs-placement', 'top');
        errorIcon.setAttribute('data-bs-title', dynamicMsg || rule?.error || 'Invalid format');
        parent.style.position = 'relative';
        parent.appendChild(errorIcon);
        new bootstrap.Tooltip(errorIcon);
      }
      return isValid;
    };
    /**
     * Initializes validation for all inputs in the form asynchronously.
     * @param {HTMLFormElement} form - The form element to validate.
     */
    const initializeFormValidation = async form => {
      const inputs = form.querySelectorAll('input[data-validate], select[data-validate]');
      await Promise.all(
        Array.from(inputs).map(async input => {
          if (input.dataset.validationInitialized) return;
          const format = input.dataset.validate?.toLowerCase();
          const dynamicRegex = input.dataset.validateReg;
          const rule = dynamicRegex
            ? { allowedChars: new RegExp(dynamicRegex) }
            : validationRules.find(r => r.key === format);
          if (!rule && !dynamicRegex) {
            this.log('No validation rule found', { input: input.id || input.name });
            return;
          }
          // Initialize Cleave.js if applicable
          let cleaveInstance = null;
          if (rule?.cleave) {
            try {
              cleaveInstance = new Cleave(input, {
                ...rule.cleave,
                onValueChanged: e => validateAndUpdate(input, e.target.rawValue || e.target.value)
              });
              input.cleaveInstance = cleaveInstance;
              this.log('Cleave initialized', { format, input: input.id || input.name });
            } catch (e) {
              this.error('Error initializing Cleave', { format, error: e.message });
            }
          }
          // Clean invalid characters
          const cleanInput = async () => {
            let value = input.value;
            if (rule?.allowedChars) {
              const allowed = new RegExp(`[^${rule.allowedChars}]`, 'g');
              value = value.replace(allowed, '');
              if (value !== input.value) {
                input.value = value;
                if (cleaveInstance) cleaveInstance.setRawValue(value);
              }
            }
            await validateAndUpdate(input, value);
          };
          // Event listeners for real-time validation
          ['input', 'paste', 'change', 'keyup', 'blur'].forEach(event => {
            input.addEventListener(event, cleanInput);
          });
          // Prevent invalid keypress
          if (rule?.allowedChars) {
            input.addEventListener('keypress', e => {
              if (!new RegExp(rule.allowedChars).test(e.key)) {
                e.preventDefault();
                this.log('Invalid keypress prevented', {
                  key: e.key,
                  input: input.id || input.name
                });
              }
            });
          }
          // Initial validation
          if (input.value) await cleanInput();
          // Clean up Cleave on modal close
          const modal = input.closest('.modal');
          if (modal) {
            modal.addEventListener(
              'hidden.bs.modal',
              () => {
                if (cleaveInstance) {
                  cleaveInstance.destroy();
                  input.reinitCleave = true;
                  input.cleaveInstance = null;
                  this.log('Cleave destroyed', { input: input.id || input.name });
                }
              },
              { once: true }
            );
          }
          input.dataset.validationInitialized = 'true';
          this.log('Validation initialized', { input: input.id || input.name, format });
        })
      );
    };
    try {
      await initializeFormValidation(form);
      // Validate required fields on submit
      const missingFields = [];
      if (isSubmit) {
        form.querySelectorAll('[required]').forEach(input => {
          let isEmpty = false;
          const fieldName = (
            input.labels?.[0]?.textContent ||
            input.name ||
            input.placeholder ||
            'Field'
          )
            .replace(/\*$/, '')
            .trim();
          if (input.type === 'checkbox' || input.type === 'radio') {
            isEmpty = !form.querySelector(`[name="${input.name}"][required]:checked`);
          } else if (input.tagName === 'SELECT') {
            isEmpty = !input.value || input.value === '';
          } else {
            isEmpty = !input.value?.trim();
          }
          if (isEmpty) missingFields.push(fieldName);
        });
        if (missingFields.length) {
          this.showToast({
            icon: 'error',
            title: 'Missing Fields',
            message: `Required: ${missingFields.join(', ')}`,
            duration: 5000
          });
        }
      }
      // Validate data-validate fields asynchronously
      const invalidFormats = [];
      await Promise.all(
        Array.from(form.querySelectorAll('input[data-validate], select[data-validate]')).map(async (input) => {
          const format = input.dataset.validate?.toLowerCase();
          const value = input.value;
          if (value && !(await validateInputFormat(format, value, input))) {
            const fieldName = (
              input.labels?.[0]?.textContent ||
              input.name ||
              input.placeholder ||
              'Field'
            )
              .replace(/\*$/, '')
              .trim();
            const dynamicMsg = input.dataset.validateMsg;
            const rule = validationRules.find(r => r.key === format);
            invalidFormats.push(`${fieldName}: ${dynamicMsg || rule?.error || 'Invalid format'}`);
          }
        })
      );
      const isValid = !(missingFields.length && isSubmit) && !invalidFormats.length;
      this.log('Form validation completed', {
        form: form.id,
        isValid,
        isSubmit,
        missingFields: missingFields.length,
        invalidFormats: invalidFormats.length
      });
      return isValid;
    } catch (e) {
      this.error('Form validation error', { form: form.id, error: e.message });
      this.showToast({
        icon: 'error',
        title: 'Validation Error',
        message: 'Failed to validate form',
        duration: 5000
      });
      return false;
    }
  }
  /**
   * Initializes unique field validation for elements with data-unique attribute with async checks.
   */
  async unique() {
    try {
      const inputs = document.querySelectorAll('input[data-unique]');
      if (!inputs.length) {
        return;
      }
      await Promise.all(
        Array.from(inputs).map(async input => {
          input.addEventListener('blur', async () => {
            const value = input.value.trim();
            const token = input.dataset.unique;
            if (!value || !token) {
              input.classList.remove('is-invalid', 'is-valid');
              this.log('No value or token for unique validation', {
                input: input.id || input.name
              });
              return;
            }
            const parent = input.parentElement;
            const dynamicMsg = input.dataset.uniqueMsg || `This value "${value}" is already in use.`;
            const errorClass = parent.classList.contains('float-input-control')
              ? 'skl-error-float-input'
              : 'skl-error-normal-input';
            // Remove existing error icon
            const errorIcon = parent.querySelector('.skl-error-icon');
            if (errorIcon) {
              bootstrap.Tooltip.getInstance(errorIcon)?.dispose();
              errorIcon.remove();
            }
            try {
              const response = await this.requestAction(token, {
                skeleton_value: value
              });
              if (!response.data || typeof response.data.isUnique === 'undefined') {
                throw new Error('Invalid response from server');
              }
              const isUnique = response.data.isUnique;
              input.classList.toggle('is-invalid', !isUnique && value !== '');
              input.classList.toggle('is-valid', isUnique);
              if (!isUnique && value !== '') {
                const errorIcon = document.createElement('span');
                errorIcon.className = `skl-error-icon ${errorClass}`;
                errorIcon.innerHTML = '<i class="fa-regular fa-circle-info"></i>';
                errorIcon.setAttribute('data-bs-toggle', 'tooltip');
                errorIcon.setAttribute('data-bs-placement', 'top');
                errorIcon.setAttribute('data-bs-title', dynamicMsg);
                parent.style.position = 'relative';
                parent.appendChild(errorIcon);
                new bootstrap.Tooltip(errorIcon);
              }
              this.log('Unique validation completed', {
                input: input.id || input.name,
                value,
                isUnique
              });
            } catch (e) {
              input.classList.add('is-invalid');
              input.classList.remove('is-valid');
              const errorIcon = document.createElement('span');
              errorIcon.className = `skl-error-icon ${errorClass}`;
              errorIcon.innerHTML = '<i class="fa-regular fa-circle-info"></i>';
              errorIcon.setAttribute('data-bs-toggle', 'tooltip');
              errorIcon.setAttribute('data-bs-placement', 'top');
              errorIcon.setAttribute('data-bs-title', 'Unable to validate the input. Please try again later.');
              parent.style.position = 'relative';
              parent.appendChild(errorIcon);
              new bootstrap.Tooltip(errorIcon);
              this.error('Error in unique validation', {
                input: input.id || input.name,
                token,
                error: e.message
              });
              this.showToast({
                icon: 'error',
                title: 'Validation Error',
                message: 'Unable to validate uniqueness',
                duration: 5000
              });
            }
          });
        })
      );
      this.log('Unique input validation initialized', { count: inputs.length });
    } catch (e) {
      this.error('Error initializing unique validation', { error: e.message });
      this.showToast({
        icon: 'error',
        title: 'Initialization Error',
        message: 'Failed to initialize unique validation',
        duration: 5000
      });
    }
  }
  async toggle() {
    document.querySelectorAll('[data-toggle="multiple"]').forEach(button => {
      let raw = button.getAttribute("data-text").trim();
      // Example raw: ["morning"=>"#3498db","afternoon"=>"#9b59b6","night"=>"#2ecc71"]
      // Step 1: Remove surrounding [ ]
      let inside = raw.replace(/^\[|\]$/g, "");
      // Step 2: Split into key=>value pairs
      let pairs = inside.split(",").map(part => part.trim());
      // Step 3: Convert into a JS object
      let stateMap = {};
      pairs.forEach(pair => {
        let [key, value] = pair.split("=>").map(s => s.trim().replace(/^["']|["']$/g, ""));
        stateMap[key] = value;
      });
      let states = Object.keys(stateMap);
      let currentIndex = 0;
      // Create hidden input dynamically
      let hiddenInput = document.createElement("input");
      hiddenInput.type = "hidden";
      hiddenInput.name = button.getAttribute("data-name");
      button.insertAdjacentElement("afterend", hiddenInput);
      function updateButton() {
        let state = states[currentIndex];
        button.textContent = state;
        button.style.backgroundColor = stateMap[state];
        hiddenInput.value = state;
      }
      updateButton();
      button.addEventListener("click", () => {
        currentIndex = (currentIndex + 1) % states.length;
        updateButton();
      });
    });
  }
}
// Initialize and expose General instance globally with error handling
try {
  window.general = new General();
  console.log('Skeleton Pack v2.0.0 initialized successfully');
} catch (error) {
  console.error('Failed to initialize Skeleton Pack:', error);
}