// Ensure dependencies are loaded correctly using ES modules
import '../../../libs/forms/form-builder/jquery-ui.js';
import '../../../libs/forms/form-builder/jquery-ui.min.css';
import '../../../libs/forms/form-builder/form-builder.min.js';
/**
 * Initializes jQuery FormBuilder v3.20.0 with custom sidebar and hidden input update.
 * Dynamically reads allowed fields from `data-form-builder-fields` to control sidebar visibility.
 *
 * @param {string} id - Target form-builder ID (data-form-builder-id).
 * @param {string|Array|null} preTemplate - Optional JSON or Array with preloaded form data.
 * @throws {Error} If dependencies or target element are missing.
 */
export function formBuilder(id, preTemplate = null) {
  // Validate dependencies
  if (!window.jQuery || !$.fn.formBuilder) {
    throw new Error('Dependencies missing: jQuery or FormBuilder');
  }
  const selector = `div[data-form-builder-id="${id}"]`;
  const target = document.querySelector(selector);
  if (!target) {
    throw new Error(`No element found for form builder ID: ${id}`);
  }
  const inputName = getInputName(target);
  const { allowedFields, disabledFields } = getFieldConfig(target);
  // Create hidden input for form data
  const hiddenInput = document.createElement('input');
  hiddenInput.type = 'hidden';
  hiddenInput.name = inputName;
  hiddenInput.dataset.formBuilderName = inputName;
  hiddenInput.value = preTemplate && typeof preTemplate === 'string' ? preTemplate : JSON.stringify(preTemplate || []);
  target.before(hiddenInput);
  // FormBuilder configuration
  const formBuilderConfig = {
    scrollToFieldOnAdd: true,
    stickyControls: {
      enable: true,
      offset: { top: 20, right: 20, left: 'auto' },
    },
    disableFields: disabledFields,
    controlOrder: allowedFields,
    disabledAttrs: [
      'access',
      'className',
      'inline',
      'rows',
      'step',
      'style',
      'description',
      'name',
    ],
    controlPosition: 'left',
    showActionButtons: false,
    i18n: {
      locale: 'en-US',
      override: {
        'en-US': {
          addOption: 'Add Option',
          allFieldsRemoved: 'All fields were removed.',
          allowMultipleFiles: 'Allow multiple files',
          autocomplete: 'Autocomplete',
          button: 'Button',
          checkbox: 'Checkbox',
          date: 'Date',
          file: 'File',
          header: 'Header',
          hidden: 'Hidden',
          number: 'Number',
          paragraph: 'Paragraph',
          radio: 'Radio',
          select: 'Select',
          text: 'Text',
          textarea: 'Textarea',
        },
      },
    },
  };
  if (preTemplate) {
    const template = parsePreTemplate(preTemplate, allowedFields);
    if (template) formBuilderConfig.formData = template;
  }
  // Initialize FormBuilder
  const fbPromise = $(target).formBuilder(formBuilderConfig);
  fbPromise.promise
    .then((fb) => {
      updateHidden(fb);
      const debouncedUpdate = debounce(() => updateHidden(fb), 100);
      const observer = new MutationObserver(debouncedUpdate);
      observer.observe(target, { childList: true, subtree: true });
      // Attach event listeners
      ['input', 'click', 'change', 'keyup'].forEach((event) =>
        target.addEventListener(event, debouncedUpdate)
      );
    })
    .catch((err) => {
      showError(`Initialization failed: ${err.message}`);
    });
  /** --- Helpers --- **/
  function updateHidden(fb) {
    try {
      const rawJson = fb.actions.getData('json', true);
      const formData = rawJson ? JSON.parse(rawJson) : [];
      const hiddenInput = document.querySelector(
        `[data-form-builder-name="${inputName}"]`
      );
      if (hiddenInput) {
        hiddenInput.value = JSON.stringify(formData);
      } else {
        window.general.log(`Hidden input "${inputName}" not found.`);
      }
    } catch (e) {
      showError(`Failed to update content: ${e.message}`);
    }
  }
  function getInputName(el) {
    const name = el.getAttribute('data-form-builder-name')?.trim();
    if (!name || !/^[\w-]+$/.test(name)) {
      showWarn(`Invalid or missing input name: "${name}", using default "content"`);
      return 'content';
    }
    return name;
  }
  function getFieldConfig(el) {
    const allFields = [
      'autocomplete',
      'button',
      'checkbox-group',
      'date',
      'file',
      'header',
      'hidden',
      'number',
      'paragraph',
      'radio-group',
      'select',
      'starRating',
      'text',
      'textarea',
    ];
    const defaultFields = ['text', 'number', 'textarea', 'select', 'date'];
    const attr = el.getAttribute('data-form-builder-fields')?.trim();
    let allowedFields = defaultFields;
    if (attr) {
      allowedFields = attr
        .split('|')
        .map((f) => f.trim().toLowerCase())
        .filter((f) => allFields.includes(f));
      if (!allowedFields.length) {
        showWarn(`No valid fields in "${attr}", using defaults`);
        allowedFields = defaultFields;
      }
    }
    const disabledFields = allFields.filter((f) => !allowedFields.includes(f));
    return { allowedFields, disabledFields };
  }
  function showError(msg, title = 'Error') {
    window.general.error(msg);
    if (window.general?.showToast) {
      window.general.showToast({ icon: 'error', title, message: msg, duration: 5000 });
    }
  }
  function showWarn(msg) {
    if (window.general?.showToast) {
      window.general.showToast({ icon: 'warning', title: 'Warning', message: msg, duration: 5000 });
    }
  }
  function debounce(fn, delay) {
    let timeout;
    return (...args) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => fn(...args), delay);
    };
  }
  function parsePreTemplate(template, allowedFields) {
    try {
      const parsed = typeof template === 'string' ? JSON.parse(template) : template;
      if (!Array.isArray(parsed)) return null;
      return parsed.map((field) => {
        // Map 'email' to 'text' with subtype 'email' for FormBuilder compatibility
        if (field.type === 'email') {
          return { ...field, type: 'text', subtype: field.subtype || 'email' };
        }
        return field;
      }).filter((field) => allowedFields.includes(field.type));
    } catch (e) {
      showError(`Invalid preTemplate: ${e.message}`);
      return null;
    }
  }
}
/**
 * Renders a dynamic form in a specified DOM element based on a JSON configuration.
 * Supports normal and floating label styles, updates a hidden input with form data,
 * and uses Bootstrap for layout. Only renders if the formId is unique.
 *
 * @param {string} divId - ID of the DOM element to append the form to.
 * @param {string} jsonString - JSON string defining form fields.
 * @param {number} colSize - Bootstrap column size (1-12).
 * @param {string} formName - Name of the form for the header.
 * @param {string} formDescription - Description of the form for the header.
 * @param {string} labelStyle - Label style: 'normal' or 'floating'.
 * @param {string} inputName - Name attribute for the hidden input storing form data.
 * @param {string} formId - Unique identifier for the form.
 * @throws {Error} If parameters are invalid or dependencies are missing.
 */
export function renderForm(divId, jsonString, colSize, formName, formDescription, labelStyle, inputName, formId) {
  // Validate jQuery
  if (typeof jQuery === 'undefined') {
    throw new Error('jQuery is required for renderForm');
  }
  const $ = jQuery;
  // Input validation
  if (!divId || typeof divId !== 'string') {
    throw new Error('divId must be a non-empty string');
  }
  if (!jsonString || typeof jsonString !== 'string') {
    throw new Error('jsonString must be a non-empty string');
  }
  if (!inputName || typeof inputName !== 'string') {
    throw new Error('inputName must be a non-empty string');
  }
  if (!formName || typeof formName !== 'string') {
    throw new Error('formName must be a non-empty string');
  }
  if (typeof formDescription !== 'string') {
    throw new Error('formDescription must be a string');
  }
  if (!formId || typeof formId !== 'string') {
    throw new Error('formId must be a non-empty string');
  }
  // Check if form with formId already exists
  if ($(`#${formId}`).length) {
    return;
  }
  // Validate labelStyle and colSize
  const validLabelStyle = labelStyle === 'floating' ? 'floating' : 'normal';
  const validColSize = Math.min(Math.max(parseInt(colSize, 10) || 12, 1), 12);
  // Parse JSON
  let formData;
  try {
    formData = JSON.parse(jsonString);
    if (!Array.isArray(formData)) {
      throw new Error('jsonString must parse to an array of field objects');
    }
  } catch (e) {
    throw new Error(`Failed to parse jsonString: ${e.message}`);
  }
  // Validate DOM element
  const $formContainer = $(`#${divId}`);
  if (!$formContainer.length) {
    throw new Error(`Element with ID "${divId}" not found in the DOM`);
  }
  // Create a wrapper for this specific form
  const $formWrapper = $(`<div id="${formId}" class="form-wrapper mb-2 bg-light p-2 rounded"></div>`);
  // Create form header
  const $header = createFormHeader(formName, formDescription);
  // Create form element
  const $form = $('<div class="row g-3"></div>');
  // Create hidden input
  const $hiddenInput = $(`<input type="hidden" name="${inputName}" value='${JSON.stringify(formData)}'>`);
  $form.append($hiddenInput);
  // Render form fields
  formData.forEach((field, index) => {
    try {
      const $fieldElement = renderField(field, validColSize, validLabelStyle, index);
      if ($fieldElement) {
        $form.append($fieldElement);
        $fieldElement
          .find('input, select, textarea')
          .on('input change', () => updateHiddenInput($form, formData, $hiddenInput));
      }
    } catch (e) {
      window.general.error(`Failed to render field at index ${index}: ${e.message}`);
    }
  });
  // Append header and form to wrapper
  $formWrapper.append($header).append($form);
  // Append wrapper to container
  $formContainer.append($formWrapper);
  // Handle close button
  $header.find('.btn-close').on('click', () => $formWrapper.remove());
}
/**
 * Creates the form header with name, description, and close button.
 * @param {string} formName - The form name.
 * @param {string} formDescription - The form description.
 * @returns {jQuery} The header element.
 */
function createFormHeader(formName, formDescription) {
  return $(`
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <h6 class="mb-0">${formName}</h6>
        <small class="sf-10">${formDescription}</small>
      </div>
      <button type="button" class="btn-close sf-10" aria-label="Close"></button>
    </div>
  `);
}
/**
 * Renders a single form field based on its type and style.
 * @param {Object} field - The field configuration object.
 * @param {number} colSize - Bootstrap column size.
 * @param {string} labelStyle - 'normal' or 'floating'.
 * @param {number} index - Field index for error logging.
 * @returns {jQuery|null} The rendered field element or null if invalid.
 * @throws {Error} If field configuration is invalid.
 */
function renderField(field, colSize, labelStyle, index) {
  if (!field || typeof field !== 'object' || !field.type || !field.name || !field.label) {
    throw new Error(
      `Field at index ${index} is missing required properties (type, name, label)`
    );
  }
  const inputId = field.name;
  const requiredStar = field.required ? '<span class="text-danger">*</span>' : '';
  const $formGroup = $(`<div class="col-${colSize}"></div>`);
  let $input;
  const fieldRenderers = {
    text: () => renderTextLikeField(field, inputId, requiredStar, labelStyle, field.subtype || 'text'),
    number: () => renderTextLikeField(field, inputId, requiredStar, labelStyle, 'number'),
    date: () => renderTextLikeField(field, inputId, requiredStar, labelStyle, 'date'),
    email: () => renderTextLikeField(field, inputId, requiredStar, labelStyle, 'email'),
    tel: () => renderTextLikeField(field, inputId, requiredStar, labelStyle, 'tel'),
    url: () => renderTextLikeField(field, inputId, requiredStar, labelStyle, 'url'),
    password: () => renderTextLikeField(field, inputId, requiredStar, labelStyle, 'password'),
    textarea: () => renderTextareaField(field, inputId, requiredStar, labelStyle),
    select: () => renderSelectField(field, inputId, requiredStar, labelStyle),
    checkbox: () => renderCheckboxRadioField(field, inputId, requiredStar, 'checkbox'),
    radio: () => renderCheckboxRadioField(field, inputId, requiredStar, 'radio'),
    file: () => renderFileField(field, inputId, requiredStar, labelStyle),
  };
  const renderer = fieldRenderers[field.type.toLowerCase()];
  if (!renderer) {
    throw new Error(`Unsupported field type "${field.type}" at index ${index}`);
  }
  $input = renderer();
  $formGroup.append($input);
  return $formGroup;
}
/**
 * Renders text-like fields (text, number, date, etc.).
 * @param {Object} field - Field configuration.
 * @param {string} inputId - Input ID.
 * @param {string} requiredStar - Required indicator.
 * @param {string} labelStyle - Label style.
 * @param {string} inputType - HTML input type.
 * @returns {jQuery} The rendered field.
 */
function renderTextLikeField(field, inputId, requiredStar, labelStyle, inputType) {
  const className = field.className || 'form-control';
  if (labelStyle === 'floating') {
    return $(`
      <div class="float-input-control">
        <input type="${inputType}" id="${inputId}" name="${field.name}" 
               class="form-float-input ${className}" placeholder="${field.label}" 
               ${field.value ? `value="${field.value}"` : 'ooooooooooo'} 
               ${field.required ? 'required' : ''}>
        <label for="${inputId}" class="form-float-label">${field.label}${requiredStar}</label>
      </div>
    `);
  }
  return $(`
    <div class="form-group">
      <label for="${inputId}">${field.label}${requiredStar}</label>
      <input type="${inputType}" id="${inputId}" name="${field.name}" 
             class="${className}" 
             ${field.placeholder ? `placeholder="${field.placeholder}"` : ''} 
             ${field.value ? `value="${field.value}"` : 'ooooooooooo'} 
             ${field.required ? 'required' : ''}>
    </div>
  `);
}
/**
 * Renders textarea fields.
 * @param {Object} field - Field configuration.
 * @param {string} inputId - Input ID.
 * @param {string} requiredStar - Required indicator.
 * @param {string} labelStyle - Label style.
 * @returns {jQuery} The rendered field.
 */
function renderTextareaField(field, inputId, requiredStar, labelStyle) {
  const className = field.className || 'form-control';
  if (labelStyle === 'floating') {
    return $(`
      <div class="float-input-control">
        <textarea id="${inputId}" name="${field.name}" 
                  class="form-float-input ${className}" 
                  ${field.placeholder ? `placeholder="${field.label}"` : ''} 
                  ${field.required ? 'required' : ''}>${field.value || ''}</textarea>
        <label for="${inputId}" class="form-float-label">${field.label}${requiredStar}</label>
      </div>
    `);
  }
  return $(`
    <div class="form-group">
      <label for="${inputId}">${field.label}${requiredStar}</label>
      <textarea id="${inputId}" name="${field.name}" 
                class="${className}" 
                ${field.placeholder ? `placeholder="${field.placeholder}"` : ''} 
                ${field.required ? 'required' : ''}>${field.value || ''}</textarea>
    </div>
  `);
}
/**
 * Renders select fields.
 * @param {Object} field - Field configuration.
 * @param {string} inputId - Input ID.
 * @param {string} requiredStar - Required indicator.
 * @returns {jQuery} The rendered field.
 */
function renderSelectField(field, inputId, requiredStar, labelStyle) {
  if (!Array.isArray(field.values)) {
    throw new Error('Select field must have a "values" array');
  }
  if (labelStyle === 'floating') {
    return $(`
      <div class="float-input-control">
        <select id="${inputId}" name="${field.name}" placeholder="${field.label}" data-select="dropdown"
              class="form-float-input ${field.className || 'form-control'}" 
              ${field.multiple ? 'multiple' : ''} ${field.required ? 'required' : ''}>
        ${field.values
        .map(
          (option) => `
          <option value="${option.value || ''}" ${option.selected ? 'selected' : ''}>
            ${option.label || option.value || ''}
          </option>
        `
        )
        .join('')}
      </select>
        <label for="${inputId}" class="form-float-label">${field.label}${requiredStar}</label>
      </div>
    `);
  }
  return $(`
    <div class="form-group">
      <label for="${inputId}">${field.label}${requiredStar}</label>
      <select id="${inputId}" name="${field.name}" placeholder="${field.label}" data-select="dropdown"
              class="${field.className || 'form-control'}" 
              ${field.multiple ? 'multiple' : ''} ${field.required ? 'required' : ''}>
        ${field.values
      .map(
        (option) => `
          <option value="${option.value || ''}" ${option.selected ? 'selected' : ''}>
            ${option.label || option.value || ''}
          </option>
        `
      )
      .join('')}
      </select>
    </div>
  `);
}
/**
 * Renders checkbox or radio fields.
 * @param {Object} field - Field configuration.
 * @param {string} inputId - Input ID.
 * @param {string} requiredStar - Required indicator.
 * @param {string} type - 'checkbox' or 'radio'.
 * @returns {jQuery} The rendered field.
 */
function renderCheckboxRadioField(field, inputId, requiredStar, type) {
  if (!Array.isArray(field.values)) {
    throw new Error(`${type} field must have a "values" array`);
  }
  return $(`
    <div class="form-group">
      <label>${field.label}${requiredStar}</label>
      ${field.values
      .map(
        (option) => `
        <div class="${type}-inline">
          <input type="${type}" name="${field.name}" value="${option.value || ''}" 
                 ${option.selected ? 'checked' : ''} 
                 ${field.required ? 'required' : ''} 
                 class="${field.className || 'form-check-input'}">
          <label>${option.label || option.value || ''}</label>
        </div>
      `
      )
      .join('')}
    </div>
  `);
}
/**
 * Renders file fields.
 * @param {Object} field - Field configuration.
 * @param {string} inputId - Input ID.
 * @param {string} requiredStar - Required indicator.
 * @param {string} labelStyle - Label style.
 * @returns {jQuery} The rendered field.
 */
function renderFileField(field, inputId, requiredStar, labelStyle) {
  const className = field.className || 'form-control';
  if (labelStyle === 'floating') {
    return $(`
      <div class="float-input-control">
        <input type="file" id="${inputId}" name="${field.name}" 
               class="form-float-input ${className}" 
               ${field.required ? 'required' : ''}>
        <label for="${inputId}" class="form-float-label">${field.label}${requiredStar}</label>
      </div>
    `);
  }
  return $(`
    <div class="form-group">
      <label for="${inputId}">${field.label}${requiredStar}</label>
      <input type="file" id="${inputId}" name="${field.name}" 
             class="${className}" 
             ${field.required ? 'required' : ''}>
    </div>
  `);
}
/**
 * Updates the hidden input with current form values.
 * @param {jQuery} $form - The form element.
 * @param {Array} formData - Original form data.
 * @param {jQuery} $hiddenInput - Hidden input to update.
 */
function updateHiddenInput($form, formData, $hiddenInput) {
  try {
    const updatedData = formData.map((field) => {
      const updatedField = { ...field };
      const $input = $form.find(`[name="${field.name}"]`);
      if ($input.length) {
        if (field.type === 'select') {
          updatedField.values = field.values.map((option) => ({
            ...option,
            selected: field.multiple
              ? $input.val()?.includes(option.value)
              : option.value === $input.val(),
          }));
        } else if (field.type === 'checkbox' || field.type === 'radio') {
          updatedField.values = field.values.map((option) => ({
            ...option,
            selected: $form.find(`[name="${field.name}"][value="${option.value}"]`).is(':checked'),
          }));
        } else if (field.type === 'file') {
          updatedField.value = $input[0].files.length > 0 ? $input[0].files[0].name : '';
        } else {
          updatedField.value = $input.val() || '';
        }
      }
      return updatedField;
    });
    $hiddenInput.val(JSON.stringify(updatedData));
  } catch (e) {
    window.general.error('Failed to update hidden input:', e.message);
  }
}