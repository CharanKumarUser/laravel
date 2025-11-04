/**
 * Initializes daterangepicker for inputs with the [data-date-picker] attribute.
 * Supports single date, date range, datetime, and datetime range pickers with
 * past/future constraints, source/target dependencies, and modal/offcanvas cleanup.
 * Relies on window.general for logging, toasts, and utilities.
 * 
 * @requires jQuery
 * @requires moment.js
 * @requires daterangepicker
 */
export function datePicker() {
  // Validate window.general availability
  if (!window.general) {
    window.general?.error('window.general is required but not available');
    return;
  }

  // Validate required dependencies
  if (!window.jQuery) {
    window.general.error('jQuery is required but not loaded');
    return;
  }
  if (!window.moment) {
    window.general.error('Moment.js is required but not loaded');
    return;
  }
  if (!window.jQuery.fn.daterangepicker) {
    window.general.error('Daterangepicker is required but not loaded');
    return;
  }

  const $ = window.jQuery;
  const inputs = document.querySelectorAll('[data-date-picker]');
  if (!inputs.length) {
    window.general.log('No date picker inputs found');
    return;
  }

  const today = window.moment().startOf('day');

  inputs.forEach(input => {
    try {
      // Extract attributes
      const type = input.getAttribute('data-date-picker');
      const allow = input.getAttribute('data-date-picker-allow') || '';
      const targetId = input.getAttribute('data-date-picker-target');
      const sourceId = input.getAttribute('data-date-picker-source');
      const placeholder = input.getAttribute('placeholder') || 'Select date';
      input.type = 'text'; // Ensure input is text type
      input.placeholder = placeholder;

      // Determine date constraints
      let minDate, maxDate;
      const match = allow.match(/^(past|future)-(\d+)([ymd])$/);
      if (match) {
        const [, direction, value, unit] = match;
        const units = { y: 'years', m: 'months', d: 'days' };
        const date = window.moment(today)[direction === 'past' ? 'subtract' : 'add'](value, units[unit]);
        [minDate, maxDate] = direction === 'past' ? [date, today] : [today, date];
      } else if (allow === 'past') {
        maxDate = today;
      } else if (allow === 'future' || allow === 'future-range') {
        minDate = today;
      } else if (allow === 'past-range') {
        maxDate = today;
      }

      // Configure picker type and format
      const isTimeEnabled = type === 'datetime' || type === 'datetime-range';
      const format = isTimeEnabled ? 'YYYY-MM-DD HH:mm' : 'YYYY-MM-DD';
      const isRange = type === 'range' || type === 'datetime-range';

      // Initialize daterangepicker configuration
      const config = {
        singleDatePicker: !isRange,
        timePicker: isTimeEnabled,
        timePicker24Hour: true,
        autoApply: true,
        autoUpdateInput: false,
        locale: {
          format,
          cancelLabel: 'Clear',
          applyLabel: 'Apply',
          daysOfWeek: window.moment.weekdaysMin(),
          monthNames: window.moment.monthsShort(),
          firstDay: window.moment.localeData().firstDayOfWeek()
        },
        minDate,
        maxDate,
        showDropdowns: true
      };

      // Set parent element for modal or offcanvas
      const $modalBody = $(input).closest('.modal-body');
      const $offcanvasBody = $(input).closest('.offcanvas-body');
      if ($modalBody.length) {
        config.parentEl = $modalBody[0];
      } else if ($offcanvasBody.length) {
        config.parentEl = $offcanvasBody[0];
      }

      // Add predefined ranges for range pickers
      if (isRange) {
        config.ranges = {
          'Today': [today, today],
          'Yesterday': [today.clone().subtract(1, 'days'), today.clone().subtract(1, 'days')],
          'Last 7 Days': [today.clone().subtract(6, 'days'), today],
          'Last 30 Days': [today.clone().subtract(29, 'days'), today],
          'This Month': [today.clone().startOf('month'), today.clone().endOf('month')],
          'Last Month': [
            today.clone().subtract(1, 'month').startOf('month'),
            today.clone().subtract(1, 'month').endOf('month')
          ]
        };
      }

      // Handle existing value
      const existingValue = $(input).val();
      if (existingValue) {
        const parsed = window.moment(existingValue, format, true);
        if (parsed.isValid()) {
          config.startDate = parsed;
          if (isRange) {
            const endValue = existingValue.split(' - ')[1];
            config.endDate = window.moment(endValue, format, true);
          } else {
            config.endDate = parsed;
          }
        }
      } else {
        config.startDate = false;
        config.endDate = false;
      }

      // Handle source input dependency
      if (sourceId) {
        const sourceInput = document.getElementById(sourceId);
        if (sourceInput) {
          const updateFromSource = () => {
            const sourceValue = $(sourceInput).val();
            const sourceDate = sourceValue && window.moment(sourceValue, format, true);
            if (sourceDate?.isValid()) {
              config[allow === 'source-future' ? 'minDate' : 'maxDate'] = sourceDate;
              const picker = $(input).data('daterangepicker');
              if (picker) {
                picker[allow === 'source-future' ? 'minDate' : 'maxDate'] = sourceDate;
                const current = window.moment($(input).val(), format, true);
                if (
                  current.isValid() &&
                  (allow === 'source-future' ? current.isBefore(sourceDate) : current.isAfter(sourceDate))
                ) {
                  $(input).val(sourceDate.format(format));
                  picker.setStartDate(sourceDate);
                  picker.setEndDate(sourceDate);
                }
              }
            }
          };
          $(sourceInput).on('apply.daterangepicker', updateFromSource);
          updateFromSource();
        } else {
          window.general.log('Source input not found', { sourceId });
        }
      }

      // Initialize daterangepicker
      try {
        $(input).daterangepicker(config);
      } catch (e) {
        window.general.error('Error initializing daterangepicker', { id: input.id, error: e.message });
        return;
      }

      // Handle date/range selection
      if (!isRange) {
        $(input).on('apply.daterangepicker', (ev, picker) => {
          const value = picker.startDate.format(format);
          $(input).val(value);
          picker.hide();

          // Update target input if applicable
          if (targetId) {
            const target = document.getElementById(targetId);
            if (target && $(target).data('daterangepicker')) {
              const tPicker = $(target).data('daterangepicker');
              if (allow === 'source-future' && tPicker.startDate.isBefore(picker.startDate)) {
                tPicker.minDate = picker.startDate;
                tPicker.setStartDate(picker.startDate);
                tPicker.setEndDate(picker.startDate);
                $(target).val(picker.startDate.format(format));
              } else if (allow === 'source-past' && tPicker.startDate.isAfter(picker.startDate)) {
                tPicker.maxDate = picker.startDate;
                tPicker.setStartDate(picker.startDate);
                tPicker.setEndDate(picker.startDate);
                $(target).val(picker.startDate.format(format));
              }
            }
          }
        });

        $(input).on('cancel.daterangepicker', () => {
          $(input).val('');
          if (targetId) {
            const target = document.getElementById(targetId);
            if (target && $(target).data('daterangepicker')) {
              const tPicker = $(target).data('daterangepicker');
              tPicker.minDate = undefined;
              tPicker.maxDate = undefined;
            }
          }
        });
      } else {
        $(input).on('apply.daterangepicker', (ev, picker) => {
          let { startDate, endDate } = picker;
          if (allow === 'future-range' && startDate.isBefore(today)) {
            startDate = today;
            picker.setStartDate(today);
          }
          if (allow === 'past-range' && endDate.isAfter(today)) {
            endDate = today;
            picker.setEndDate(today);
          }
          const value = `${startDate.format(format)} - ${endDate.format(format)}`;
          $(input).val(value);
        });

        $(input).on('cancel.daterangepicker', () => {
          $(input).val('');
        });
      }

      // Clean up on modal or offcanvas close
      const $modal = $(input).closest('.modal');
      const $offcanvas = $(input).closest('.offcanvas');
      if ($modal.length) {
        $modal.on('hidden.bs.modal', () => {
          const picker = $(input).data('daterangepicker');
          if (picker) {
            picker.remove();
            $(input).off('apply.daterangepicker cancel.daterangepicker');
            window.general.log('Date picker cleaned up on modal close', { id: input.id });
          }
        });
      } else if ($offcanvas.length) {
        $offcanvas.on('hidden.bs.offcanvas', () => {
          const picker = $(input).data('daterangepicker');
          if (picker) {
            picker.remove();
            $(input).off('apply.daterangepicker cancel.daterangepicker');
            window.general.log('Date picker cleaned up on offcanvas close', { id: input.id });
          }
        });
      }

    } catch (e) {
      window.general.error('Error initializing date picker for input', { id: input.id, error: e.message });
    }
  });
}