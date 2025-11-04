// drag.js
import Sortable from 'sortablejs';
export function drag() {
  const containers = document.querySelectorAll('[data-drag-container]');
  containers.forEach(container => {
    const areas = container.querySelectorAll('[data-drag-area]');
    areas.forEach(area => {
      new Sortable(area, {
        group: 'shared-dnd',
        animation: 150,
        onSort: updateAllInputs,
        onAdd: enforceMaxOptions,
      });
      // Click toggle for items
      area.addEventListener('click', e => {
        const item = e.target.closest('[data-drag-item]');
        if (!item) return;
        toggleItem(item, area);
      });
    });
  });
  /**
   * Update all elements matching selector
   * - If target has data-html → innerHTML
   * - Else → value
   * - If target has data-int → parseInt
   */
  function setTargetValue(selector, value) {
    let elements = document.querySelectorAll(selector);
    if (!elements.length) {
      // Create fallback hidden input if nothing found
      let el = document.createElement('input');
      el.type = 'hidden';
      if (selector.startsWith('.')) {
        el.className = selector.slice(1);
      } else if (selector.startsWith('#')) {
        el.id = selector.slice(1);
      } else {
        el.className = selector;
      }
      document.body.appendChild(el);
      elements = [el];
    }
    elements.forEach(el => {
      const isHtmlTarget = el.hasAttribute('data-html');
      const asInteger = el.hasAttribute('data-int');
      // apply int/float formatting
      let newVal = value;
      if (!isNaN(newVal) && newVal !== '') {
        if (asInteger) {
          newVal = parseInt(newVal, 10);
        } else {
          newVal = parseFloat(newVal).toFixed(2);
        }
      }
      const oldValue = isHtmlTarget ? el.innerHTML : el.value;
      if (isHtmlTarget) {
        el.innerHTML = newVal;
      } else {
        el.value = newVal;
      }
      const changedValue = isHtmlTarget ? el.innerHTML : el.value;
      if (oldValue !== changedValue) {
        el.dispatchEvent(new Event('click', { bubbles: true }));
      }
    });
  }
  /**
   * Update all inputs/outputs when drag/click happens
   */
  function updateAllInputs() {
    document.querySelectorAll('[data-drag-area]').forEach(area => {
      const items = area.querySelectorAll('[data-drag-item]');
      const values = Array.from(items).map(i => i.getAttribute('data-value'));
      const sums = Array.from(items).map(i => parseFloat(i.getAttribute('data-sum')) || 0);
      const sep = area.getAttribute('data-seperator') || ',';
      // update string values
      const strSel = area.getAttribute('data-input-string');
      if (strSel) {
        setTargetValue(strSel, values.join(sep));
      }
      // update sum values
      const sumSel = area.getAttribute('data-input-sum');
      if (sumSel) {
        const sumValue = sums.reduce((a, b) => a + b, 0);
        setTargetValue(sumSel, sumValue);
      }
    });
  }
  /**
   * Get max items allowed in area
   */
  function getMax(area) {
    const raw = area.getAttribute('data-max');
    if (!raw) return Infinity; // missing → unlimited
    if (/[uU]/.test(raw)) return Infinity; // contains "u" → unlimited
    const num = parseInt(raw, 10);
    return isNaN(num) ? Infinity : num;
  }
  /**
   * Enforce max options on drag/drop
   */
  function enforceMaxOptions(evt) {
    const area = evt.to;
    const max = getMax(area);
    if (max !== Infinity) {
      const items = area.querySelectorAll('[data-drag-item]');
      if (items.length > max) {
        evt.from.appendChild(evt.item); // send back
      }
    }
    updateAllInputs();
  }
  /**
   * Toggle item on click between areas
   */
  function toggleItem(item, currentArea) {
    const containers = document.querySelectorAll('[data-drag-container]');
    let targetArea = null;
    containers.forEach(container => {
      const areas = container.querySelectorAll('[data-drag-area]');
      areas.forEach(area => {
        if (area !== currentArea && !targetArea) {
          const max = getMax(area);
          if (area.querySelectorAll('[data-drag-item]').length < max) {
            targetArea = area;
          }
        }
      });
    });
    if (targetArea) {
      targetArea.appendChild(item);
      updateAllInputs();
    }
  }
  // Initial update
  updateAllInputs();
}
