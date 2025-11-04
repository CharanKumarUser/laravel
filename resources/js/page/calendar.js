import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

// Global styles (injected once)
if (!window._hcCalendarStylesInjected) {
  const styleEl = document.createElement('style');
  styleEl.textContent = `
    .fc-pill { display: flex; align-items: center; padding: 2px 6px; border-radius: 4px; color: #fff; font-size: 12px; overflow: hidden; position: relative; }
    .fc-pill-avatar { width: 16px; height: 16px; border-radius: 50%; margin-right: 4px; flex-shrink: 0; }
    .fc-pill-title { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fc-pill-right { margin-left: 4px; }
    .fc-pill-assignees { display: flex; align-items: center; }
    .fc-pill-assignee { width: 16px; height: 16px; border-radius: 50%; margin-left: -4px; border: 1px solid #fff; }
    .fc-pill-assignee:first-child { margin-left: 0; }
    .fc-assignee-count { background: rgba(0,0,0,0.4); border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 10px; margin-left: -4px; border: 1px solid #fff; }
    .hc-assignees-tooltip { padding: 8px; }
    .hc-assignee-item { display: flex; align-items: center; margin-bottom: 8px; }
    .hc-assignee-item:last-child { margin-bottom: 0; }
    .hc-assignee-avatar { width: 32px; height: 32px; border-radius: 50%; margin-right: 8px; }
    .hc-assignee-info strong { display: block; }
    .hc-assignee-info small { display: block; color: #666; }
    .hc-holiday-wrap { pointer-events: none; }
    .hc-holiday-img { pointer-events: none; }
    .hc-holiday-menu { pointer-events: auto; }
    .hc-holidays-tooltip, .hc-day-popup { padding: 8px; }
    .hc-holiday-item, .hc-item { margin-bottom: 8px; }
    .hc-item-title { padding: 4px 8px; }
    .hc-item-body { padding: 0 8px 8px; }
    .hc-badge { background: #eee; color: #333; padding: 2px 4px; border-radius: 3px; font-size: 10px; margin-left: 4px; }
    .hc-time { color: #666; font-size: 12px; margin-left: 4px; }
    .hc-day-popup-header { font-weight: bold; padding: 8px; border-bottom: 1px solid #eee; }
    .hc-day-popup-body { max-height: 400px; overflow-y: auto; }
  `;
  document.head.appendChild(styleEl);
  window._hcCalendarStylesInjected = true;
}

const INSTANCES = new Map(); // rootEl -> { calendar, holidayNodes: Set<el>, timers: Set<timeout>, tippyInstances: Set<tippy> }

/**
 * Main entry point for calendar initialization
 * @param {Object|Array} config - Configuration object or array of events
 * @returns {Object} Calendar API with set, get, and reload methods
 */
function calendar(config = {}) {
  const cfg = Array.isArray(config) ? { events: config } : (config || {});
  const selector = cfg.selector || '[data-calendar]';
  const options = cfg.options || {};
  const events = Array.isArray(cfg.events) ? cfg.events : [];
  const holidaysMap = buildHolidaysMap(events);
  const fcEvents = events
    .filter(e => typeof e === 'object' && (e.type || '').toLowerCase() !== 'holiday')
    .map(toFcEvent)
    .filter(e => e.start);

  // Process each root element independently
  document.querySelectorAll(selector).forEach(rootEl => {
    destroyInstance(rootEl);
    const inst = { calendar: null, holidayNodes: new Set(), timers: new Set(), tippyInstances: new Set() };
    INSTANCES.set(rootEl, inst);

    const calendar = new Calendar(rootEl, {
      plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
      initialView: options.initialView || 'dayGridMonth',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
      },
      initialDate: normalizeDate(options.initialDate) || new Date(),
      height: options.height || 'auto',
      expandRows: true,
      dayMaxEvents: 2,
      locale: options.locale || 'en',
      firstDay: Number.isInteger(options.firstDay) ? options.firstDay : 0,
      editable: false,
      selectable: false,
      droppable: false,
      events: fcEvents,
      eventContent: (arg) => {
        const ep = arg.event.extendedProps || {};
        const color = ep.color || getTypeColor(ep.type);
        const avatar = ep.img ? `<img src="${escapeAttr(ep.img)}" class="fc-pill-avatar" alt="Event avatar">` : '';
        const assignees = Array.isArray(ep.assignees) ? ep.assignees : [];
        const assigneesHtml = assignees.length
          ? `<span class="fc-pill-assignees" data-assignees="${escapeAttr(JSON.stringify(ep.assignees))}">${
              assignees.slice(0, 3).map(a => {
                if (!a) return '';
                return `<img src="${escapeAttr(a.avatar || a.img || '')}" class="fc-pill-assignee" alt="Assignee avatar">`;
              }).join('')
            }${
              assignees.length > 3 ? `<span class="fc-pill-assignees"><span class="fc-assignee-count">+${assignees.length - 3}</span>` : ''
            }</span>`
          : '';
        return {
          html: `<div class="fc-pill" style="background:${escapeAttr(color)}">
                  ${avatar}
                  <span class="fc-pill-title">${escapeHtml(arg.event.title || '')}</span>
                  <span class="fc-pill-right">${assigneesHtml}</span>
                </div>`
        };
      },
      eventDidMount: (info) => {
        const ep = info.event.extendedProps || {};
        let tip;
        if (ep.html) {
          tip = tippy(info.el, {
            content: ep.html,
            allowHTML: true,
            interactive: true,
            appendTo: document.body,
            placement: 'top',
            delay: [80, 50],
            maxWidth: 520
          });
          inst.tippyInstances.add(tip);
        }
        const assigneesEl = info.el.querySelector('.fc-pill-assignees');
        if (assigneesEl) {
          const assignees = JSON.parse(assigneesEl.getAttribute('data-assignees') || '[]');
          if (assignees.length) {
            const assigneeTip = tippy(assigneesEl, {
              content: `<div class="hc-assignees-tooltip">${assignees.map(a => `
                <div class="hc-assignee-item">
                  <img src="${escapeAttr(a.avatar || a.img || '')}" class="hc-assignee-avatar" alt="Assignee avatar">
                  <div class="hc-assignee-info">
                    <strong>${escapeHtml(a.name || 'Unknown')}</strong>
                    <small>ID: ${escapeHtml(a.id || 'N/A')}</small>
                    <small>Role: ${escapeHtml(a.role || 'N/A')}</small>
                  </div>
                </div>`).join('')}</div>`,
              allowHTML: true,
              interactive: true,
              appendTo: document.body,
              placement: 'right',
              delay: [100, 50],
              maxWidth: 300
            });
            inst.tippyInstances.add(assigneeTip);
          }
        }
      },
      dayCellDidMount: (info) => {
        const dateKey = ymd(info.date);
        const holidays = holidaysMap.get(dateKey) || [];
        const existing = info.el.querySelector('.hc-holiday-wrap');
        if (existing) existing.remove();
        if (!holidays.length) return;
        const frame = info.el.querySelector('.fc-daygrid-day-frame') || info.el;
        frame.style.position = 'relative';
        const wrap = document.createElement('div');
        wrap.className = 'hc-holiday-wrap';
        Object.assign(wrap.style, {
          position: 'absolute',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          zIndex: 1,
          overflow: 'hidden'
        });
        const imgEl = document.createElement('div');
        imgEl.className = 'hc-holiday-img';
        Object.assign(imgEl.style, {
          width: '100%',
          height: '100%',
          backgroundSize: 'cover',
          backgroundPosition: 'center',
          display: 'flex',
          alignItems: 'flex-end',
          justifyContent: 'center',
          color: '#fff',
          fontSize: '12px',
          padding: '6px',
          boxSizing: 'border-box'
        });
        const updateHolidayDisplay = () => {
          const randomHoliday = holidays[Math.floor(Math.random() * holidays.length)] || {};
          if (randomHoliday.img) {
            imgEl.style.backgroundImage = `url('${escapeAttr(randomHoliday.img)}')`;
          } else {
            imgEl.style.backgroundColor = randomHoliday.color || '#ef4444';
          }
          imgEl.innerHTML = `<div style="width:100%;text-align:center;background:linear-gradient(to top, rgba(0,0,0,.4), rgba(0,0,0,0));padding:4px;border-radius:4px;">
                            ${escapeHtml(randomHoliday.title || '')}
                          </div>`;
        };
        updateHolidayDisplay();
        let timer;
        if (holidays.length > 1) {
          timer = setInterval(updateHolidayDisplay, 3000);
          inst.timers.add(timer);
        }
        const menuIcon = document.createElement('div');
        menuIcon.className = 'hc-holiday-menu';
        Object.assign(menuIcon.style, {
          position: 'absolute',
          top: '4px',
          left: '4px',
          width: '16px',
          height: '16px',
          zIndex: 10,
          cursor: 'pointer',
          background: 'rgba(0,0,0,0.5)',
          borderRadius: '10px',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          color: '#fff',
          fontSize: '12px',
          lineHeight: '16px'
        });
        menuIcon.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>';
        let menuTip;
        if (holidays.length) {
          menuTip = tippy(menuIcon, {
            content: `<div class="hc-holidays-tooltip">${holidays.map(h => `
              <div class="hc-holiday-item">
                <strong>${escapeHtml(h.title || 'Holiday')}</strong>
                <div>${h.html || ''}</div>
              </div>`).join('')}</div>`,
            allowHTML: true,
            interactive: true,
            appendTo: document.body,
            placement: 'right',
            delay: [100, 50],
            maxWidth: 400
          });
          inst.tippyInstances.add(menuTip);
        }
        wrap.append(imgEl, menuIcon);
        frame.append(wrap);
        let wrapTip;
        if (holidays[0]?.html) {
          wrapTip = tippy(wrap, {
            content: holidays[0].html,
            allowHTML: true,
            interactive: true,
            appendTo: document.body,
            placement: 'top',
            delay: [80, 40],
            maxWidth: 420
          });
          inst.tippyInstances.add(wrapTip);
        }
        inst.holidayNodes.add(wrap);
      },
      moreLinkClick: (arg) => {
        const date = arg.date || (arg.dayEl?.getAttribute?.('data-date') && new Date(arg.dayEl.getAttribute('data-date')));
        const anchor = arg.dayEl || arg.jsEvent?.currentTarget || arg.moreEl || null;
        showDayDetailPopup(rootEl, date, anchor, calendar, holidaysMap, inst);
      },
      viewDidMount: () => {
        const toolbar = rootEl.querySelector('.fc-header-toolbar');
        if (toolbar) {
          toolbar.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
              calendar.render();
            });
          });
        }
      }
    });
    calendar.render();
    inst.calendar = calendar;
  });

  return {
    set(newEvents = []) {
      document.querySelectorAll(selector).forEach(rootEl => {
        const inst = INSTANCES.get(rootEl);
        if (!inst || !inst.calendar) return;
        clearInstanceResources(inst);
        const newHolidaysMap = buildHolidaysMap(newEvents);
        const newFcEvents = (newEvents || [])
          .filter(e => typeof e === 'object' && (e.type || '').toLowerCase() !== 'holiday')
          .map(toFcEvent)
          .filter(e => e.start);
        inst.calendar.removeAllEvents();
        inst.calendar.addEventSource(newFcEvents);
        inst.calendar.render();
        // Re-apply holidays by forcing day re-render (via view switch or refetch placeholder)
        inst.calendar.refetchEvents();
      });
    },
    get() {
      const out = [];
      document.querySelectorAll(selector).forEach(rootEl => {
        const inst = INSTANCES.get(rootEl);
        if (!inst || !inst.calendar) return;
        inst.calendar.getEvents().forEach(ev => out.push(fromFcEvent(ev)));
      });
      return out;
    },
    reload(newConfig = {}) {
      document.querySelectorAll(selector).forEach(rootEl => destroyInstance(rootEl));
      return calendar(Object.assign({}, cfg, newConfig));
    }
  };
}

function showDayDetailPopup(rootEl, date, anchorEl, calendar, holidaysMap, inst) {
  if (!date || !calendar) return;
  const dateKey = ymd(date);
  const holidays = holidaysMap.get(dateKey) || [];
  const events = calendar.getEvents().filter(ev => isEventOnDate(ev, date));
  const wrapper = document.createElement('div');
  wrapper.className = 'hc-day-popup';
  wrapper.setAttribute('aria-label', `Events for ${dateKey}`);
  wrapper.innerHTML = `
    <div class="hc-day-popup-header">${escapeHtml(dateKey)} — ${holidays.length + events.length} items</div>
    <div class="hc-day-popup-body">
      ${holidays.map(h => `
        <div class="hc-item hc-item-holiday">
          <div class="hc-item-title" style="border-left:4px solid ${escapeAttr(h.color || '#ef4444')}">
            <strong>${escapeHtml(h.title || 'Holiday')}</strong>
          </div>
          <div class="hc-item-body">${h.html || ''}</div>
        </div>
      `).join('')}
      ${events.map(ev => {
        const ep = ev.extendedProps || {};
        return `
          <div class="hc-item hc-item-event">
            <div class="hc-item-title" style="border-left:4px solid ${escapeAttr(ep.color || getTypeColor(ep.type))}">
              <strong>${escapeHtml(ev.title || '')}</strong>
              ${ev.allDay ? '<span class="hc-badge">All-day</span>' : `<span class="hc-time">${escapeHtml(fmtDateTime(ev.start))}${ev.end ? ' — ' + escapeHtml(fmtDateTime(ev.end)) : ''}</span>`}
            </div>
            <div class="hc-item-body">${ep.html || ''}</div>
          </div>`;
      }).join('')}
    </div>
  `;
  const reference = anchorEl || rootEl;
  const tip = tippy(reference, {
    content: wrapper,
    allowHTML: true,
    interactive: true,
    trigger: 'manual',
    appendTo: document.body,
    placement: 'top',
    maxWidth: 640,
    theme: 'light-border'
  });
  inst.tippyInstances.add(tip);
  tip.show();
  const closeOnClick = (ev) => {
    if (!wrapper.contains(ev.target) && !reference.contains(ev.target)) {
      try { tip.hide(); tip.destroy(); inst.tippyInstances.delete(tip); } catch (e) { }
      document.removeEventListener('click', closeOnClick);
    }
  };
  setTimeout(() => document.addEventListener('click', closeOnClick), 50);
}

function clearInstanceResources(inst) {
  inst.tippyInstances.forEach(tip => {
    try { tip.destroy(); } catch (e) {}
  });
  inst.tippyInstances.clear();
  inst.timers.forEach(clearInterval);
  inst.timers.clear();
  inst.holidayNodes.forEach(node => {
    if (node && node.parentElement) node.remove();
  });
  inst.holidayNodes.clear();
}

function buildHolidaysMap(events) {
  const m = new Map();
  (events || [])
    .filter(e => typeof e === 'object' && (e.type || '').toLowerCase() === 'holiday')
    .forEach(h => {
      const start = normalizeDate(h.start);
      const end = normalizeDate(h.end) || start;
      if (!start) return;
      const payload = {
        id: h.id,
        title: h.title || '',
        img: h.img || '',
        color: h.color || '',
        html: h.html || ''
      };
      for (const d of expandDates(start, end)) {
        const cur = m.get(d) || [];
        cur.push(payload);
        m.set(d, cur);
      }
    });
  return m;
}

function toFcEvent(e = {}) {
  if (!e || typeof e !== 'object') return {};
  const start = normalizeDate(e.start);
  if (!start) return {};
  const end = normalizeDate(e.end);
  const id = String(e.id || makeId(e));
  const allDay = typeof e.allDay === 'boolean' ? e.allDay : (!hasTime(start) && (!end || !hasTime(end)));
  return {
    id,
    title: e.title || '',
    start,
    end,
    allDay,
    extendedProps: {
      type: e.type || 'event',
      img: e.img || '',
      color: e.color || '',
      html: e.html || '',
      assignees: Array.isArray(e.assignees) ? e.assignees.filter(a => a && typeof a === 'object') : [],
      meta: e.meta || {}
    }
  };
}

function fromFcEvent(ev) {
  if (!ev) return {};
  const ep = ev.extendedProps || {};
  return {
    id: ev.id,
    title: ev.title || '',
    start: ev.start ? ev.start.toISOString() : null,
    end: ev.end ? ev.end.toISOString() : null,
    allDay: ev.allDay,
    type: ep.type || '',
    img: ep.img || '',
    color: ep.color || '',
    html: ep.html || '',
    assignees: ep.assignees || [],
    meta: ep.meta || {}
  };
}

function isEventOnDate(ev, date) {
  if (!ev || !ev.start || !date) return false;
  const dd = ymd(date);
  const s = new Date(ev.start);
  const e = ev.end ? new Date(ev.end) : new Date(ev.start);
  return dd >= ymd(s) && dd <= ymd(e);
}

function fmtDateTime(d) {
  try {
    return new Date(d).toLocaleString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    });
  } catch (e) {
    return String(d);
  }
}

function normalizeDate(input) {
  if (!input) return null;
  if (input instanceof Date) return input.toISOString();
  const s = String(input).trim();
  try {
    const d = new Date(s);
    if (isNaN(d.getTime())) return null;
    return d.toISOString();
  } catch (e) {
    return null;
  }
}

function hasTime(s) {
  return typeof s === 'string' && s.includes('T');
}

function* expandDates(startIso, endIso) {
  if (!startIso) return;
  const s = new Date(startIso);
  const e = new Date(endIso || startIso);
  if (isNaN(s.getTime()) || isNaN(e.getTime())) return;
  for (let d = new Date(s); d <= e; d.setDate(d.getDate() + 1)) {
    yield ymd(d);
  }
}

function ymd(d) {
  try {
    const D = (d instanceof Date) ? d : new Date(d);
    if (isNaN(D.getTime())) return '';
    const y = D.getFullYear();
    const m = String(D.getMonth() + 1).padStart(2, '0');
    const da = String(D.getDate()).padStart(2, '0');
    return `${y}-${m}-${da}`;
  } catch (e) {
    return '';
  }
}

function makeId(e) {
  return 'evt_' + Math.random().toString(36).slice(2, 9);
}

function getTypeColor(type = '') {
  switch ((type || '').toLowerCase()) {
    case 'holiday': return '#ef4444';
    case 'leave': return '#dc2626';
    case 'shift': return '#2563eb';
    case 'meeting': return '#9333ea';
    case 'training': return '#d97706';
    case 'deadline': return '#ef4444';
    default: return '#6b7280';
  }
}

function escapeHtml(s = '') {
  return String(s).replace(/[&<>"]/g, c => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;'
  })[c]);
}

function escapeAttr(s = '') {
  return String(s).replace(/"/g, '&quot;');
}

function destroyInstance(root) {
  const inst = INSTANCES.get(root);
  if (!inst) return;
  clearInstanceResources(inst);
  try {
    if (inst.calendar) inst.calendar.destroy();
  } catch (e) { }
  INSTANCES.delete(root);
}

window.page = window.page || {};
window.page.calendar = calendar;
export default calendar;