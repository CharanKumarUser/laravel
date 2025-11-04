export function shift() {
  const selectors = {
    breakContainer: '.has-break',
    breakRules: '.break-allowed-rules',
    fixedBreaks: '.break-allowed-multiple-container',
    numBreaks: '.break-allowed-multiple',
    breakDuration: '.break-allowed-rules-duration',
    container: '.break-multiple-repeat-container',
    workingHours: '.working-hours',
    breakRulesInput: '#break_rules'
  };

  let allBreaks = [];

  const parseTime = (time) => {
    if (!time) return 0;
    const [hours, minutes] = time.split(':').map(Number);
    return hours * 60 + minutes;
  };

  const formatDuration = (minutes) => {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
  };

  const updateJSON = () => {
    const breakDuration = $('input[name="break_duration"]').val();
    const settings = {
      multiple_breaks: $('select[name="allow_multiple_breaks"]').val() || '0',
      variable_break: $('select[name="has_variable_break"]').val() || '0',
      include_break: $('select[name="include_break"]').val() || '0',
      break_termination: $('select[name="allow_break_termination"]').val() || '0'
    };

    const json = {
      no_of_breaks: allBreaks.length,
      breaks: allBreaks.map(breakItem => ({
        name: breakItem.name,
        start_time: breakItem.start_time || '',
        end_time: breakItem.end_time || '',
        grace_period: breakItem.grace_period || ''
      })),
      break_duration: breakDuration,
      ...settings
    };

    $(selectors.breakRulesInput).val(JSON.stringify(json, null, 2));
  };

  const calculateWorkingHours = () => {
    const startTime = $('input[name="start_time"]').val();
    const endTime = $('input[name="end_time"]').val();
    const includeBreak = $('select[name="include_break"]').val() === '1';
    const breakDuration = $('input[name="break_duration"]').val();

    if (!startTime || !endTime) return;

    let startMin = parseTime(startTime);
    let endMin = parseTime(endTime);
    if (endMin < startMin) endMin += 1440;

    let breakMinutes = 0;
    if (breakDuration && !includeBreak) {
      breakMinutes = parseTime(breakDuration);
    }

    const totalMinutes = endMin - startMin - breakMinutes;
    $(`${selectors.workingHours} input[name="working_hours"]`).val(formatDuration(totalMinutes));
  };

  const toggleStrictShift = () => {
    const value = $('select[name="strict_shift"]').val();
    const startTime = $('input[name="start_time"]').val();
    const endTime = $('input[name="end_time"]').val();

    if (value === '1' && (!startTime || !endTime)) {
      window.general.errorToast('Invalid Input', 'Enter shift start and end time');
      $(selectors.breakContainer).addClass('d-none');
      return false;
    }

    $(selectors.breakContainer).toggleClass('d-none', value !== '1');
    $(`${selectors.workingHours} input[name="working_hours"]`).prop('readonly', value === '1');
    if (value !== '1') {
      $([selectors.breakRules, selectors.fixedBreaks, selectors.numBreaks, selectors.breakDuration, selectors.container].join(',')).addClass('d-none');
      $(`${selectors.workingHours} input[name="working_hours"]`).val('').addClass('time-input');
    } else {
      toggleBreakAllowed();
    }
    calculateWorkingHours();
    return true;
  };

  const toggleBreakAllowed = () => {
    const value = $('select[name="break_allowed"]').val();
    $([selectors.breakRules, selectors.breakDuration].join(',')).toggleClass('d-none', value !== '1');
    if (value === '1') {
      toggleFixedBreaks();
    } else {
      $([selectors.fixedBreaks, selectors.numBreaks, selectors.container].join(',')).addClass('d-none');
    }
    updateJSON();
    calculateWorkingHours();
  };

  const toggleFixedBreaks = () => {
    const allowMultiple = $('select[name="allow_multiple_breaks"]').val() || '0';
    const hasVariable = $('select[name="has_variable_break"]').val() || '0';

    $([selectors.container, selectors.numBreaks, selectors.fixedBreaks].join(',')).addClass('d-none');
    $(`${selectors.breakDuration} input[name="break_duration"]`).prop('readonly', true);

    if (allowMultiple === '0' && hasVariable === '0') {
      $(selectors.fixedBreaks).removeClass('d-none');
      if (!allBreaks.length) {
        allBreaks = [{ name: 'Break 1', start_time: '', end_time: '', grace_period: '' }];
      }
      updateBreakFields();
    } else if (allowMultiple === '1' && hasVariable === '0') {
      $(selectors.numBreaks).removeClass('d-none');
      const count = parseInt($('input[name="no_of_breaks"]').val()) || 0;
      if (count > 0) {
        $(selectors.container).removeClass('d-none');
        renderBreaks(count);
      }
    } else {
      $(selectors.breakDuration).removeClass('d-none');
      $(`${selectors.breakDuration} input[name="break_duration"]`).prop('readonly', false);
    }
    updateJSON();
    calculateWorkingHours();
  };

  const updateBreakFields = () => {
    if (allBreaks.length) {
      $('input[name="break_name"]').val(allBreaks[0].name);
      $('input[name="break_start_time"]').val(allBreaks[0].start_time);
      $('input[name="break_end_time"]').val(allBreaks[0].end_time);
      $('input[name="break_grace_period"]').val(allBreaks[0].grace_period);
    }
  };

  const renderBreaks = (count) => {
    $(selectors.container).empty();
    allBreaks = allBreaks.slice(0, count);
    for (let i = 0; i < count; i++) {
      if (!allBreaks[i]) {
        allBreaks.push({ name: `Break ${i + 1}`, start_time: '', end_time: '', grace_period: '' });
      }
      $(selectors.container).append(`
        <div class="row g-3 mt-2 break-row" data-index="${i}">
          <div class="col-3">
            <div class="float-input-control">
              <input type="text" name="break_name[]" class="form-float-input break-name" placeholder="Break Name ${i + 1}" value="${allBreaks[i].name}">
              <label class="form-float-label">Break Name</label>
            </div>
          </div>
          <div class="col-3">
            <div class="float-input-control">
              <input type="time" name="break_start_time[]" class="form-float-input break-start-time" data-index="${i}" value="${allBreaks[i].start_time}">
              <label class="form-float-label">Break Start Time</label>
            </div>
          </div>
          <div class="col-3">
            <div class="float-input-control">
              <input type="time" name="break_end_time[]" class="form-float-input break-end-time" data-index="${i}" value="${allBreaks[i].end_time}">
              <label class="form-float-label">Break End Time</label>
            </div>
          </div>
          <div class="col-3">
            <div class="float-input-control">
              <input type="text" name="break_grace_period[]" class="form-float-input break-grace time-input" data-index="${i}" value="${allBreaks[i].grace_period}" placeholder="HH:MM">
              <label class="form-float-label">Break Grace Period</label>
            </div>
          </div>
        </div>
      `);
    }
    $('.time-input').each((_, el) => new Cleave(el, { time: true, timePattern: ['h', 'm'], delimiters: [':'], blocks: [2, 2] }));
    updateJSON();
    calculateBreakDuration(true);
  };

  const initializeForm = () => {
    const breakRules = $(selectors.breakRulesInput).val();
    if (breakRules) {
      try {
        const data = JSON.parse(breakRules);
        $('select[name="allow_multiple_breaks"]').val(data.multiple_breaks || '0');
        $('select[name="has_variable_break"]').val(data.variable_break || '0');
        $('select[name="include_break"]').val(data.include_break || '0');
        $('select[name="allow_break_termination"]').val(data.break_termination || '0');
        $('input[name="break_duration"]').val(data.break_duration || '');
        if (data.breaks && data.breaks.length) {
          allBreaks = data.breaks.map(breakItem => ({
            name: breakItem.name,
            start_time: breakItem.start_time || '',
            end_time: breakItem.end_time || '',
            grace_period: breakItem.grace_period || ''
          }));
          $('input[name="no_of_breaks"]').val(data.no_of_breaks || 0);
          if (data.no_of_breaks > 0 && data.multiple_breaks === '1') {
            renderBreaks(data.no_of_breaks);
          } else if (data.multiple_breaks === '0' && data.variable_break === '0') {
            updateBreakFields();
          }
        }
      } catch (e) {
        console.error('Error parsing break_rules JSON:', e);
      }
    }
    $('select[data-value]').each(function() {
      $(this).val($(this).data('value'));
    });
    toggleStrictShift();
    toggleBreakAllowed();
    calculateWorkingHours();
  };

  $('select[name="strict_shift"]').on('change', toggleStrictShift);
  $('input[name="start_time"], input[name="end_time"]').on('change', () => {
    toggleStrictShift();
    calculateWorkingHours();
  });

  $('select[name="break_allowed"]').on('change', toggleBreakAllowed);

  $('input[name="no_of_breaks"]').on('input', () => {
    const count = parseInt($('input[name="no_of_breaks"]').val()) || 0;
    $(selectors.container).toggleClass('d-none', count < 1);
    renderBreaks(count);
  });

  $(selectors.container).on('input', '.break-name, .break-start-time, .break-end-time, .break-grace', (e) => {
    const index = parseInt($(e.target).data('index'));
    if (isNaN(index) || !allBreaks[index]) return;

    const type = e.target.name.match(/break_(name|start_time|end_time|grace_period)/)?.[1];
    if (type) {
      const keyMap = {
        break_name: 'name',
        break_start_time: 'start_time',
        break_end_time: 'end_time',
        break_grace_period: 'grace_period'
      };
      allBreaks[index][keyMap[type]] = $(e.target).val();
      updateJSON();
      calculateBreakDuration(true);
    }
  });

  $('input[name="break_name"], input[name="break_start_time"], input[name="break_end_time"], input[name="break_grace_period"]').on('input', () => {
    if (allBreaks.length) {
      allBreaks[0].name = $('input[name="break_name"]').val();
      allBreaks[0].start_time = $('input[name="break_start_time"]').val();
      allBreaks[0].end_time = $('input[name="break_end_time"]').val();
      allBreaks[0].grace_period = $('input[name="break_grace_period"]').val();
      updateJSON();
      calculateBreakDuration();
    }
  });

  $('select[name="break_allowed"], select[name="allow_multiple_breaks"], select[name="has_variable_break"], select[name="allow_break_termination"]').on('change', () => {
    toggleFixedBreaks();
    updateJSON();
  });

  $('select[name="include_break"]').on('change', () => {
    updateJSON();
    calculateWorkingHours();
  });

  $('input[name="break_duration"]').on('input', () => {
    updateJSON();
    calculateWorkingHours();
  });

  const calculateBreakDuration = (isMultiple = false) => {
    const shiftStart = parseTime($('input[name="start_time"]').val());
    const shiftEnd = parseTime($('input[name="end_time"]').val());
    let totalMinutes = 0;
    let isValid = true;

    const validateBreak = (start, end, grace, index) => {
      if (!start || !end || !grace) return false;
      const startMin = parseTime(start);
      const endMin = parseTime(end);
      const graceMin = parseTime(grace);

      if (endMin <= startMin) {
        window.general.errorToast('Invalid Break', `Break ${index + 1}: End time must be greater than start time`);
        return false;
      }
      if (startMin < shiftStart || endMin > shiftEnd) {
        window.general.errorToast('Invalid Break', `Break ${index + 1}: Break must be within shift time`);
        return false;
      }
      if (graceMin >= (endMin - startMin)) {
        window.general.errorToast('Invalid Break', `Break ${index + 1}: Grace must be less than break duration`);
        return false;
      }
      return true;
    };

    if (isMultiple) {
      $(`${selectors.container} .break-row`).each((index, row) => {
        const $row = $(row);
        const breakStart = $row.find('input[name="break_start_time[]"]').val();
        const breakEnd = $row.find('input[name="break_end_time[]"]').val();
        const grace = $row.find('input[name="break_grace_period[]"]').val();

        if (breakStart && breakEnd && grace && validateBreak(breakStart, breakEnd, grace, index)) {
          totalMinutes += parseTime(breakEnd) - parseTime(breakStart);
          allBreaks[index].start_time = breakStart;
          allBreaks[index].end_time = breakEnd;
          allBreaks[index].grace_period = grace;
        } else {
          isValid = false;
        }
      });
    } else {
      const breakStart = $('input[name="break_start_time"]').val();
      const breakEnd = $('input[name="break_end_time"]').val();
      const grace = $('input[name="break_grace_period"]').val();

      if (breakStart && breakEnd && grace && validateBreak(breakStart, breakEnd, grace, 0)) {
        totalMinutes = parseTime(breakEnd) - parseTime(breakStart);
        allBreaks[0].start_time = breakStart;
        allBreaks[0].end_time = breakEnd;
        allBreaks[0].grace_period = grace;
      } else {
        isValid = false;
      }
    }

    if (isValid) {
      $(`${selectors.breakDuration} input[name="break_duration"]`).val(formatDuration(totalMinutes));
      updateJSON();
      calculateWorkingHours();
    } else {
      $(`${selectors.breakDuration} input[name="break_duration"]`).val('');
      $(`${selectors.workingHours} input[name="working_hours"]`).val('');
    }
  };

  $(document).on('input', 'input[name="break_start_time"], input[name="break_end_time"], input[name="break_grace_period"]', () => calculateBreakDuration());
  $(document).on('input', 'input[name="break_start_time[]"], input[name="break_end_time[]"], input[name="break_grace_period[]"]', () => calculateBreakDuration(true));
  $(document).on('input change', 'input[name="start_time"], input[name="end_time"]', calculateWorkingHours);

  $('select[name="has_variable_break"], select[name="allow_multiple_breaks"]').on('change', toggleFixedBreaks);

  $('.time-input').each((_, el) => new Cleave(el, { time: true, timePattern: ['h', 'm'], delimiters: [':'], blocks: [2, 2] }));

  initializeForm();
}

export function shiftSchedule() {
  const typeSelector = $('[data-id="shift_type"]');
  const form = typeSelector.closest('.row');

  const toggleFields = () => {
    const type = typeSelector.val();
    $('#day-container, #week-container, #from-date-container, #to-date-container').addClass('d-none');
    if (type === 'day') {
      $('#day-container').removeClass('d-none');
    } else if (type === 'week') {
      $('#week-container, #day-container').removeClass('d-none');
    } else if (type === 'custom') {
      $('#from-date-container, #to-date-container, #day-container').removeClass('d-none');
    }
  };
  typeSelector.off('change').on('change', toggleFields);
  toggleFields();
}