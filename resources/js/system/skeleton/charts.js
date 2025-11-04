import * as echarts from 'echarts';

// ---- Utilities (safe & defensive) ----
function safeLog(level, msg, info = '') {
  try {
    const logFn = console[level] || console.log;
    logFn(`[charts] ${msg}`, info);
  } catch (e) {
    if (typeof window !== 'undefined' && window.general?.error) {
      window.general.error('[charts] Logging failed.', e);
    }
  }
}

function debounce(fn, delay) {
  try {
    let timeout;
    return (...args) => {
      try {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
          try {
            fn(...args);
          } catch (e) {
            safeLog('error', 'Debounced function execution failed.', e);
          }
        }, delay);
      } catch (e) {
        safeLog('error', 'Debounce setup failed.', e);
      }
    };
  } catch (e) {
    safeLog('error', 'Debounce function creation failed.', e);
    return () => {};
  }
}

function generateVibrantColors(num = 10) {
  try {
    const vibrantPalette = [
      '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEEAD', 
      '#D4A5A5', '#9B59B6', '#3498DB', '#E74C3C', '#2ECC71',
      '#F39C12', '#E91E63', '#00BCD4', '#8E44AD', '#1ABC9C',
      '#FFC107', '#FF5722', '#00E676', '#C2185B', '#7C4DFF'
    ];
    const result = [];
    for (let i = 0; i < num; i++) {
      if (i < vibrantPalette.length) {
        result.push(vibrantPalette[i]);
      } else {
        const r = Math.floor(Math.random() * 155 + 100);
        const g = Math.floor(Math.random() * 155 + 100);
        const b = Math.floor(Math.random() * 155 + 100);
        result.push(`rgb(${r},${g},${b})`);
      }
    }
    return result;
  } catch (e) {
    safeLog('error', 'Failed to generate vibrant colors.', e);
    return ['#00B4AF'];
  }
}

function parseColor(color) {
  try {
    if (!color || typeof color !== 'string') return [84, 112, 198]; // Fallback #00B4AF
    color = color.trim().toLowerCase();
    // Hex
    if (/^#[0-9a-f]{3,6}$/.test(color)) {
      let hex = color.slice(1);
      if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
      const r = parseInt(hex.substr(0, 2), 16);
      const g = parseInt(hex.substr(2, 2), 16);
      const b = parseInt(hex.substr(4, 2), 16);
      if (Number.isFinite(r) && Number.isFinite(g) && Number.isFinite(b)) {
        return [r, g, b];
      }
    }
    // RGB/RGBA
    const rgbMatch = color.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
    if (rgbMatch) {
      const [, r, g, b] = rgbMatch;
      const pr = parseInt(r, 10);
      const pg = parseInt(g, 10);
      const pb = parseInt(b, 10);
      if (Number.isFinite(pr) && Number.isFinite(pg) && Number.isFinite(pb)) {
        return [pr, pg, pb];
      }
    }
    // Common color names
    const colorMap = {
      red: [255, 0, 0],
      green: [0, 128, 0],
      blue: [0, 0, 255],
      yellow: [255, 255, 0],
      purple: [128, 0, 128],
      orange: [255, 165, 0],
      black: [0, 0, 0],
      white: [255, 255, 255]
    };
    if (colorMap[color]) return colorMap[color];
    return [84, 112, 198]; // Fallback
  } catch (e) {
    safeLog('warn', 'Failed to parse color; using fallback.', e);
    return [84, 112, 198];
  }
}

function generateGradientColor(color, index, value = null) {
  try {
    const [r, g, b] = parseColor(color);
    const offset = value != null && Number.isFinite(value) ? Math.min(Math.max(value / 100, 0), 1) : (index % 5) / 5;
    const alpha = (1 - offset).toFixed(2);
    return new echarts.graphic.LinearGradient(0, 0, 0, 1, [
      { offset: 0, color: `rgba(${r},${g},${b},1)` },
      { offset: 1, color: `rgba(${r},${g},${b},${alpha})` }
    ]);
  } catch (e) {
    safeLog('warn', 'Failed to generate gradient color; using fallback.', e);
    return color;
  }
}

function parseData(raw) {
  try {
    if (raw == null || String(raw).trim() === '') return [];
    const str = String(raw).trim();
    try {
      return JSON.parse(str);
    } catch {
      if (!str.includes(',')) {
        const num = Number(str);
        return Number.isFinite(num) ? [num] : [str];
      }
      return str.split(',').map(t => {
        const val = t.trim();
        const num = Number(val);
        return Number.isFinite(num) ? num : val;
      });
    }
  } catch (e) {
    safeLog('warn', 'Failed to parse data.', e);
    return [];
  }
}

function parseRichSettings(str) {
  try {
    if (!str || typeof str !== 'string') return {};
    const settings = String(str).split(',').map(s => s.trim().toLowerCase()).filter(Boolean);
    const rich = {};
    settings.forEach(s => {
      try {
        if (s === 'bold') rich.fontWeight = 'bold';
        if (s === 'italic') rich.fontStyle = 'italic';
        if (s.startsWith('color:')) rich.color = s.split(':')[1]?.trim() || 'inherit';
        if (s.startsWith('size:')) rich.fontSize = parseInt(s.split(':')[1], 10) || 12;
        if (s === 'underline') rich.textDecoration = 'underline';
        if (s === 'shadow') rich.textShadow = '1px 1px 2px rgba(0,0,0,0.5)';
      } catch (e) {
        safeLog('warn', `Failed to parse rich setting: ${s}`, e);
      }
    });
    return rich;
  } catch (e) {
    safeLog('warn', 'Failed to parse rich settings.', e);
    return {};
  }
}

function getDatasets(el) {
  try {
    if (!el || !el.dataset) return [[]];
    return Object.entries(el.dataset)
      .filter(([key]) => /^set\d+$/i.test(key))
      .sort(([a], [b]) => a.localeCompare(b, 'en', { numeric: true }))
      .map(([, value]) => parseData(value))
      .filter(ds => ds && (Array.isArray(ds) ? ds.length > 0 : true));
  } catch (e) {
    safeLog('warn', 'Failed to collect datasets.', e);
    return [[]];
  }
}

function parseSettings(str) {
  try {
    if (!str || typeof str !== 'string') return new Set();
    return new Set(String(str).split(',').map(x => x.trim().toLowerCase()).filter(Boolean));
  } catch (e) {
    safeLog('warn', 'Failed to parse settings.', e);
    return new Set();
  }
}

function parseColors(str) {
  try {
    const colors = parseData(str);
    return Array.isArray(colors)
      ? colors.map(String).filter(c => {
          c = c.trim().toLowerCase();
          return (
            /^#[0-9a-fA-F]{3,6}$/.test(c) ||
            /^rgb\(\d+,\s*\d+,\s*\d+\)$/.test(c) ||
            /^rgba\(\d+,\s*\d+,\s*\d+,\s*[0-1](\.\d+)?\)$/.test(c) ||
            ['red', 'green', 'blue', 'yellow', 'purple', 'orange', 'black', 'white'].includes(c)
          );
        })
      : [];
  } catch (e) {
    safeLog('warn', 'Failed to parse colors.', e);
    return [];
  }
}

function fitArray(arr, length, fallbackVal = 0) {
  try {
    const out = Array.isArray(arr) ? arr.slice(0, length) : [];
    while (out.length < length) out.push(fallbackVal);
    return out;
  } catch (e) {
    safeLog('warn', 'Failed to fit array.', e);
    return Array(length).fill(fallbackVal);
  }
}

function inferLabelsFromDatasets(datasets) {
  try {
    const first = Array.isArray(datasets[0]) ? datasets[0] : [];
    const len = first.length || 5;
    return Array.from({ length: len }, (_, i) => `Item ${i + 1}`);
  } catch (e) {
    safeLog('warn', 'Failed to infer labels.', e);
    return ['Item 1', 'Item 2', 'Item 3', 'Item 4', 'Item 5'];
  }
}

function applySize(el, size) {
  try {
    if (!el || !el.style) throw new Error('Invalid element');
    if (!size) {
      el.style.width = el.style.width || '600px';
      el.style.height = el.style.height || '400px';
      return;
    }
    const [w = '600', h = '400'] = String(size).toLowerCase().split('x').map(s => s.trim());
    el.style.width = (/^\d+%$/.test(w) || /^\d+px$/.test(w) || Number(w)) ? (/^\d+%$/.test(w) || /^\d+px$/.test(w) ? w : `${w}px`) : '600px';
    el.style.height = (/^\d+%$/.test(h) || /^\d+px$/.test(h) || Number(h)) ? (/^\d+%$/.test(h) || /^\d+px$/.test(h) ? h : `${h}px`) : '400px';
  } catch (e) {
    safeLog('warn', 'Failed to apply size.', e);
    if (el && el.style) {
      el.style.width = el.style.width || '600px';
      el.style.height = el.style.height || '400px';
    }
  }
}

function extractFirstNumber(datasets) {
  try {
    for (const ds of datasets) {
      if (ds == null) continue;
      if (Array.isArray(ds)) {
        for (const v of ds) {
          if (v == null) continue;
          const num = Array.isArray(v) ? Number(v[0]) : Number(v);
          if (Number.isFinite(num)) return num;
        }
      } else if (Number.isFinite(Number(ds))) {
        return Number(ds);
      }
    }
    return NaN;
  } catch (e) {
    safeLog('warn', 'Failed to extract first number.', e);
    return NaN;
  }
}

function detectChartType(datasets, labels, labelsX, labelsY) {
  try {
    const first = datasets[0] || [];
    if (datasets.length === 1 && first.length === 1 && Number.isFinite(first[0])) return 'gauge';
    if (datasets.length === 1 && Array.isArray(first) && first.every(item => Array.isArray(item) && item.length >= 3 && Number.isFinite(item[2]))) return 'heatmap';
    if (datasets.length === 1 && Array.isArray(first) && first.every(item => Array.isArray(item) && item.length === 4 && item.every(Number.isFinite))) return 'candlestick';
    if (datasets.length === 1 && typeof first === 'object' && !Array.isArray(first) && Array.isArray(first.nodes) && Array.isArray(first.links)) return 'sankey';
    if (datasets.length === 1 && Array.isArray(first) && first.some(item => typeof item === 'object' && Array.isArray(item.children))) return 'sunburst';
    if (datasets.length === 1 && Array.isArray(first) && first.every(item => Array.isArray(item) && item.length === 5 && item.every(Number.isFinite))) return 'boxplot';
    if (datasets.length === 1 && Array.isArray(first) && first.every(item => typeof item === 'object' && Array.isArray(item.coords))) return 'lines';
    if (datasets.length === 2 && Array.isArray(datasets[0]) && Array.isArray(datasets[1]) && datasets[0].every(item => typeof item === 'object')) return 'graph';
    if (datasets.length === 1 && Array.isArray(first) && first.some(item => typeof item === 'object' && (item.parent || item.children))) return 'tree';
    if (datasets.length === 1 && Array.isArray(first) && first.every(item => typeof item === 'object' && item.name && Number.isFinite(item.value))) return 'treemap';
    if (datasets.length === 1 && Array.isArray(first) && first.every(item => Array.isArray(item) && item.length > 2)) return 'parallel';
    if (datasets.length === 1 && Array.isArray(labels) && labels.length && datasets[0].length === labels.length) return 'funnel';
    if (datasets.length >= 1 && Array.isArray(labels) && labels.length && datasets[0].length === labels.length) return 'radar';
    if (datasets.length === 1 && Array.isArray(first) && first.every(item => Array.isArray(item) && item.length === 2)) return 'scatter';
    if (datasets.length === 1 && Array.isArray(first) && Array.isArray(labels) && labels.length && first.length <= 5) return 'pie';
    return 'bar';
  } catch (e) {
    safeLog('warn', 'Failed to auto-detect chart type; defaulting to bar.', e);
    return 'bar';
  }
}

function observeResize(el, chart) {
  try {
    const debouncedResize = debounce(() => {
      try {
        if (!chart || chart.isDisposed()) return;
        chart.resize();
      } catch (e) {
        safeLog('warn', 'Chart resize failed.', e);
      }
    }, 100);
    if (typeof ResizeObserver === 'function') {
      const ro = new ResizeObserver(debouncedResize);
      ro.observe(el);
      return () => {
        try {
          ro.disconnect();
          if (chart && !chart.isDisposed()) chart.dispose();
        } catch (e) {
          safeLog('warn', 'Cleanup resize observer failed.', e);
        }
      };
    }
    window.addEventListener('resize', debouncedResize);
    return () => {
      try {
        window.removeEventListener('resize', debouncedResize);
        if (chart && !chart.isDisposed()) chart.dispose();
      } catch (e) {
        safeLog('warn', 'Cleanup window resize listener failed.', e);
      }
    };
  } catch (e) {
    safeLog('warn', 'Resize observer setup failed.', e);
    return () => {};
  }
}

// ---- Shimmer Loading Setup ----
function injectShimmerStyle() {
  try {
    if (!document.querySelector('#skeleton-shimmer-style')) {
      const style = document.createElement('style');
      style.id = 'skeleton-shimmer-style';
      style.textContent = `
        .skeleton-loading {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
          background-size: 200% 100%;
          animation: shimmer 1.5s infinite linear;
          z-index: 10;
        }
        @keyframes shimmer {
          0% { background-position: 200% 0; }
          100% { background-position: -200% 0; }
        }
      `;
      document.head.appendChild(style);
    }
  } catch (e) {
    safeLog('warn', 'Failed to inject shimmer style.', e);
  }
}

// ---- Main Renderer ----
function charts() {
  try {
    injectShimmerStyle();
    const nodes = document.querySelectorAll('[data-chart],[data-chart-auto]');
    if (!nodes.length) throw new Error('No chart elements found');

    for (const el of nodes) {
      try {
        if (!el) continue;

        // Ensure element has relative positioning for shimmer overlay
        if (el.style.position === '' || el.style.position === 'static') {
          el.style.position = 'relative';
        }

        // Apply shimmer effect immediately
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'skeleton-loading';
        el.appendChild(loadingDiv);

        // Apply size
        applySize(el, el.dataset.size);

        // Parse chart attributes
        const isAuto = el.hasAttribute('data-chart-auto');
        const type = isAuto ? '' : String(el.dataset.chart || '').toLowerCase();
        const settings = parseSettings(el.dataset.settings);
        const richSettings = parseRichSettings(el.dataset.rich);
        const title = String(el.dataset.title || '');
        const subtitle = String(el.dataset.subtitle || '');
        const labels = parseData(el.dataset.labels);
        const labelsX = parseData(el.dataset.labelsX);
        const labelsY = parseData(el.dataset.labelsY);
        const datasets = getDatasets(el);
        const xAxisName = String(el.dataset.xaxisName || '');
        const yAxisName = String(el.dataset.yaxisName || '');
        const min = parseFloat(el.dataset.min) || 0;
        const max = parseFloat(el.dataset.max) || 100;
        const unit = String(el.dataset.unit || '');
        const set2Labels = parseData(el.dataset.set2Labels);
        let colors = parseColors(el.dataset.colors);

        // Ensure enough colors
        const maxItems = Math.max(
          datasets.length || 1,
          datasets[0]?.length || 0,
          labels.length || 0,
          set2Labels.length || 0
        );
        if (!colors.length) {
          colors = generateVibrantColors(maxItems);
        } else if (colors.length < maxItems) {
          colors = [...colors, ...generateVibrantColors(maxItems - colors.length)];
        }

        const symbol = String(el.dataset.symbol || 'rect');
        const chartType = isAuto ? detectChartType(datasets, labels, labelsX, labelsY) : type || 'bar';

        // Validate chart type
        if (!chartType) {
          safeLog('warn', 'No chart type specified; skipping element.', el);
          el.removeChild(loadingDiv);
          continue;
        }
        if (chartType === 'map') {
          safeLog('warn', 'Map charts are disabled; skipping element.', el);
          el.removeChild(loadingDiv);
          continue;
        }

        // Configure toolbox features
        const supportsDataZoom = ['bar', 'line', 'scatter', 'effectscatter', 'radar', 'heatmap', 'candlestick', 'boxplot', 'parallel', 'lines', 'graph', 'custom'].includes(chartType);
        const toolboxFeatures = {};
        try {
          toolboxFeatures.saveAsImage = { title: 'Save', show: true };
          toolboxFeatures.dataView = { readOnly: false, title: 'Data', show: true };
          toolboxFeatures.restore = { title: 'Reset', show: true };
          if (supportsDataZoom && settings.has('zoom')) {
            toolboxFeatures.dataZoom = { title: { zoom: 'Zoom', back: 'Back' }, show: true };
          }
          if (supportsDataZoom && settings.has('brush')) {
            toolboxFeatures.brush = {
              type: ['rect', 'polygon', 'lineX', 'lineY', 'keep', 'clear'],
              title: { rect: 'Box Select', polygon: 'Lasso', lineX: 'H Line', lineY: 'V Line', keep: 'Keep', clear: 'Clear' },
              show: true
            };
          }
          if (['bar', 'line'].includes(chartType)) {
            toolboxFeatures.magicType = { type: ['line', 'bar', 'stack'], title: { line: 'Line', bar: 'Bar', stack: 'Stack' }, show: true };
          }
        } catch (e) {
          safeLog('warn', 'Failed to configure toolbox features.', e);
        }

        // Base chart options
        const option = {
          color: colors,
          title: (title || subtitle) ? {
            text: title,
            subtext: subtitle,
            left: 'center',
            top: 0,
            textStyle: richSettings,
            subtextStyle: richSettings
          } : undefined,
          tooltip: settings.has('tooltip') ? {
            trigger: ['pie', 'donut', 'funnel', 'sunburst', 'treemap', 'sankey', 'themeriver'].includes(chartType) ? 'item' : 'axis',
            axisPointer: { type: chartType === 'bar' ? 'shadow' : 'line' },
            textStyle: richSettings
          } : undefined,
          legend: settings.has('legend') && (datasets.length > 1 || chartType === 'pie') ? {
            top: (title || subtitle) ? 28 : 0,
            left: 'center',
            textStyle: richSettings
          } : undefined,
          toolbox: settings.has('export') ? {
            right: 12,
            feature: toolboxFeatures
          } : undefined,
          grid: { top: (title || subtitle) ? 72 : (settings.has('legend') ? 48 : 24), left: 48, right: 24, bottom: 48, containLabel: true },
          animation: settings.has('animation')
        };

        // Chart type-specific configurations
        switch (chartType) {
          case 'pie':
          case 'donut': {
            try {
              const lbls = Array.isArray(labels) && labels.length ? labels : inferLabelsFromDatasets(datasets);
              const arr = Array.isArray(datasets[0]) ? datasets[0] : [];
              const data = fitArray(arr, lbls.length, 0).map((v, i) => ({
                name: String(lbls[i] || `Item ${i + 1}`),
                value: Number(v) || 0,
                itemStyle: {
                  color: settings.has('gradient')
                    ? generateGradientColor(colors[i % colors.length], i, v)
                    : colors[i % colors.length]
                }
              }));
              option.series = [{
                type: 'pie',
                radius: chartType === 'donut' ? ['40%', '70%'] : (settings.has('rose') ? ['20%', '55%'] : '70%'),
                roseType: settings.has('rose') ? 'area' : undefined,
                data,
                label: settings.has('labels') ? {
                  show: true,
                  formatter: '{b}: {c} ({d}%)',
                  rich: richSettings
                } : undefined,
                avoidLabelOverlap: true,
                animationType: settings.has('animation') ? 'expansion' : undefined
              }];
            } catch (e) {
              safeLog('warn', 'Failed to configure pie/donut chart.', e);
            }
            break;
          }
          case 'bar':
          case 'line': {
            try {
              const horizontal = settings.has('horizontal');
              const seriesCount = Math.max(1, datasets.length);
              const baseLabels = Array.isArray(labels) && labels.length ? labels : inferLabelsFromDatasets(datasets);
              const normDatasets = Array.from({ length: seriesCount }, (_, i) =>
                fitArray(Array.isArray(datasets[i]) ? datasets[i] : [], baseLabels.length, 0)
              );
              const xType = settings.has('timeaxis') ? 'time' : (settings.has('logaxis') ? 'log' : 'category');
              const yType = settings.has('logaxis') ? 'log' : 'value';
              if (horizontal) {
                option.xAxis = { type: yType, scale: true, name: xAxisName };
                option.yAxis = { type: xType, data: baseLabels, name: yAxisName };
              } else {
                option.xAxis = { type: xType, data: baseLabels, name: xAxisName };
                option.yAxis = { type: yType, scale: true, name: yAxisName };
              }
              option.series = normDatasets.map((data, idx) => ({
                name: set2Labels[idx] || `Set ${idx + 1}`,
                type: chartType,
                data: settings.has('timeaxis') ? data.map((v, i) => [baseLabels[i], v]) : data,
                smooth: chartType === 'line' && settings.has('smooth'),
                step: chartType === 'line' && settings.has('step') ? 'middle' : undefined,
                areaStyle: chartType === 'line' && settings.has('area') ? (settings.has('gradient') ? {
                  color: generateGradientColor(colors[idx % colors.length], idx)
                } : {}) : undefined,
                stack: settings.has('stacked') ? 'total' : undefined,
                label: settings.has('labels') ? {
                  show: true,
                  position: chartType === 'bar' ? 'top' : 'right',
                  rich: richSettings
                } : undefined,
                markLine: settings.has('markline') ? { data: [{ type: 'average', name: 'Avg' }], silent: true } : undefined,
                markArea: settings.has('markarea') ? { data: [[{ name: 'Range', yAxis: 'min' }, { yAxis: 'max' }]], silent: true } : undefined,
                itemStyle: {
                  color: settings.has('gradient')
                    ? (params) => generateGradientColor(colors[idx % colors.length], idx, Array.isArray(params.data) ? params.data[1] : params.data)
                    : colors[idx % colors.length]
                }
              }));
              if (settings.has('zoom')) {
                option.dataZoom = [
                  { type: 'inside', start: 0, end: 100 },
                  { type: 'slider', show: true }
                ];
              }
              if (settings.has('waterfall') && datasets.length === 1) {
                option.series.push({
                  type: 'bar',
                  data: normDatasets[0].map((v, i) => i === 0 ? 0 : normDatasets[0][i - 1]),
                  stack: 'waterfall',
                  itemStyle: { opacity: 0 },
                  tooltip: { show: false }
                });
              }
              if (settings.has('bump')) {
                option.series.forEach(s => s.type = 'line');
                option.yAxis.inverse = true;
              }
              if (settings.has('confidenceband') && datasets.length >= 3) {
                option.series.push(
                  {
                    type: 'line',
                    name: 'Upper',
                    data: normDatasets[1],
                    lineStyle: { opacity: 0 },
                    stack: 'confidence-band',
                    symbol: 'none'
                  },
                  {
                    type: 'line',
                    name: 'Lower',
                    data: normDatasets[2].map((v, i) => normDatasets[0][i] - v),
                    areaStyle: { color: '#ccc' },
                    lineStyle: { opacity: 0 },
                    stack: 'confidence-band',
                    symbol: 'none'
                  }
                );
              }
              if (settings.has('matrix') && datasets.length > 1) {
                option.grid = Array.from({ length: datasets.length }, (_, i) => ({
                  left: 48, right: 24, top: 72 + i * 120, height: 100, containLabel: true
                }));
                option.xAxis = Array.from({ length: datasets.length }, () => ({ type: xType, data: baseLabels, name: xAxisName }));
                option.yAxis = Array.from({ length: datasets.length }, () => ({ type: yType, scale: true, name: yAxisName }));
                option.series = normDatasets.map((data, idx) => ({
                  name: set2Labels[idx] || `Set ${idx + 1}`,
                  type: chartType,
                  data: settings.has('timeaxis') ? data.map((v, i) => [baseLabels[i], v]) : data,
                  xAxisIndex: idx,
                  yAxisIndex: idx,
                  label: settings.has('labels') ? {
                    show: true,
                    position: chartType === 'bar' ? 'top' : 'right',
                    rich: richSettings
                  } : undefined,
                  itemStyle: {
                    color: settings.has('gradient')
                      ? (params) => generateGradientColor(colors[idx % colors.length], idx, Array.isArray(params.data) ? params.data[1] : params.data)
                      : colors[idx % colors.length]
                  }
                }));
              }
            } catch (e) {
              safeLog('warn', 'Failed to configure bar/line chart.', e);
            }
            break;
          }
          case 'scatter':
          case 'effectscatter': {
            try {
              option.xAxis = { type: 'value', scale: true, splitLine: { show: false }, name: xAxisName };
              option.yAxis = { type: 'value', scale: true, splitLine: { show: false }, name: yAxisName };
              const sets = datasets.length ? datasets : [[]];
              option.series = sets.map((data, idx) => ({
                name: set2Labels[idx] || `Set ${idx + 1}`,
                type: chartType === 'effectscatter' ? 'effectScatter' : 'scatter',
                data: Array.isArray(data) ? data.map((item, i) => Array.isArray(item) && item.length >= 2 ? {
                  value: item.slice(0, 2),
                  itemStyle: {
                    color: settings.has('gradient')
                      ? generateGradientColor(colors[i % colors.length], i, item[1])
                      : colors[i % colors.length]
                  }
                } : { value: [0, 0], itemStyle: { color: colors[i % colors.length] } }) : [],
                rippleEffect: chartType === 'effectscatter' && settings.has('effect') ? { scale: 5, brushType: 'stroke' } : undefined,
                label: settings.has('labels') ? {
                  show: true,
                  position: 'top',
                  rich: richSettings
                } : undefined,
                symbolSize: settings.has('bubble') ? (value) => Math.sqrt(value[2] || 10) * 5 : 10
              }));
              if (settings.has('fisheye')) {
                option.graphic = [{
                  type: 'circle',
                  shape: { r: 50 },
                  style: { fill: 'rgba(0,0,0,0.2)' },
                  draggable: true,
                  ondrag: function () { try { this.__chart.dispatchAction({ type: 'hideTip' }); } catch (e) { safeLog('warn', 'Fisheye drag failed.', e); } },
                  __chart: null
                }];
              }
              if (settings.has('matrix') && datasets.length > 1) {
                option.grid = Array.from({ length: datasets.length }, (_, i) => ({
                  left: 48 + (i % 3) * 200, top: 72 + Math.floor(i / 3) * 200, width: 180, height: 180, containLabel: true
                }));
                option.xAxis = Array.from({ length: datasets.length }, () => ({ type: 'value', scale: true, name: xAxisName }));
                option.yAxis = Array.from({ length: datasets.length }, () => ({ type: 'value', scale: true, name: yAxisName }));
                option.series = sets.map((data, idx) => ({
                  name: set2Labels[idx] || `Set ${idx + 1}`,
                  type: chartType === 'effectscatter' ? 'effectScatter' : 'scatter',
                  data: Array.isArray(data) ? data.map((item, i) => Array.isArray(item) ? {
                    value: item.slice(0, 2),
                    itemStyle: {
                      color: settings.has('gradient')
                        ? generateGradientColor(colors[i % colors.length], i, item[1])
                        : colors[i % colors.length]
                    }
                  } : { value: [0, 0], itemStyle: { color: colors[i % colors.length] } }) : [],
                  xAxisIndex: idx,
                  yAxisIndex: idx,
                  label: settings.has('labels') ? {
                    show: true,
                    rich: richSettings
                  } : undefined
                }));
              }
            } catch (e) {
              safeLog('warn', 'Failed to configure scatter/effectscatter chart.', e);
            }
            break;
          }
          case 'radar': {
            try {
              const inds = (Array.isArray(labels) && labels.length ? labels : inferLabelsFromDatasets(datasets))
                .map(n => ({ name: String(n), max: max }));
              option.radar = { indicator: inds };
              option.series = [{
                type: 'radar',
                data: (datasets.length ? datasets : [[]]).map((vals, idx) => ({
                  value: fitArray(Array.isArray(vals) ? vals : [], inds.length, 0),
                  name: set2Labels[idx] || `Set ${idx + 1}`,
                  areaStyle: settings.has('area') ? {
                    color: settings.has('gradient')
                      ? generateGradientColor(colors[idx % colors.length], idx)
                      : colors[idx % colors.length]
                  } : undefined
                })),
                label: settings.has('labels') ? {
                  show: true,
                  rich: richSettings
                } : undefined
              }];
            } catch (e) {
              safeLog('warn', 'Failed to configure radar chart.', e);
            }
            break;
          }
          case 'gauge': {
            try {
              const v = Number(extractFirstNumber(datasets));
              let series = [{
                type: 'gauge',
                min,
                max,
                progress: settings.has('progress') ? { show: true, width: 18, roundCap: true } : undefined,
                pointer: { length: '75%', width: 8 },
                axisLine: {
                  lineStyle: {
                    width: 18,
                    color: [
                      [0.3, colors[0] || '#67e0e3'],
                      [0.7, colors[1 % colors.length] || '#37a2da'],
                      [1, colors[2 % colors.length] || '#fd666d']
                    ]
                  }
                },
                axisTick: { show: settings.has('tick') },
                splitLine: { show: settings.has('split') },
                axisLabel: { show: settings.has('axislabel'), formatter: `{value} ${unit}`, ...richSettings },
                title: { show: !!title, offsetCenter: [0, '-40%'], fontSize: 16, ...richSettings },
                detail: {
                  fontSize: 32,
                  offsetCenter: [0, '-10%'],
                  valueAnimation: settings.has('animation'),
                  formatter: `{value}${unit}`,
                  ...richSettings
                },
                data: set2Labels.length
                  ? set2Labels.map((label, i) => ({
                      name: label,
                      value: datasets[i]?.[0] || 0,
                      itemStyle: { color: colors[i % colors.length] }
                    }))
                  : [{ value: Number.isFinite(v) ? v : 0, name: title, itemStyle: { color: colors[0] } }]
              }];
              if (settings.has('labels')) {
                series[0].label = { show: true, ...richSettings };
              }
              if (settings.has('ring')) {
                series[0].pointer.show = false;
                series[0].progress = { show: true, overlap: false, roundCap: true, clip: false, itemStyle: { borderWidth: 1, borderColor: colors[0] } };
                series[0].axisLine.lineStyle.width = 30;
                series[0].splitLine.show = false;
                series[0].axisTick.show = false;
                series[0].axisLabel.show = false;
                series[0].title.show = false;
                series[0].detail = { formatter: `{value}${unit}`, color: 'inherit', fontSize: 25, offsetCenter: [0, 0], ...richSettings };
              }
              if (settings.has('speed') || settings.has('car')) {
                series[0].min = 0;
                series[0].max = 200;
                series[0].splitNumber = 10;
                series[0].axisLine.lineStyle.color = [
                  [0.2, colors[0] || '#91c7ae'],
                  [0.8, colors[1 % colors.length] || '#63869e'],
                  [1, colors[2 % colors.length] || '#c23531']
                ];
                series[0].pointer.width = 15;
                series[0].detail.formatter = `{value} ${unit || 'km/h'}`;
                series[0].axisLabel.show = true;
                series[0].splitLine.show = true;
                series[0].axisTick.show = true;
                if (settings.has('car')) {
                  series[0].startAngle = 225;
                  series[0].endAngle = -45;
                  series[0].pointer.icon = 'path://M2.9,0.7L2.9,0.7c1.4,0,2.6,1.2,2.6,2.6v115c0,1.4-1.2,2.6-2.6,2.6s-2.6-1.2-2.6-2.6V3.3C0.3,1.9,1.4,0.7,2.9,0.7z';
                  series[0].pointer.length = '12%';
                  series[0].pointer.offsetCenter = [0, '-55%'];
                  series[0].detail.fontSize = 50;
                  series[0].detail.offsetCenter = [0, '40%'];
                  series[0].axisLine.lineStyle.width = 40;
                  series[0].splitLine.length = 30;
                  series[0].axisTick.length = 20;
                  series[0].axisLabel.distance = 25;
                }
              }
              if (settings.has('temperature')) {
                series[0].min = -20;
                series[0].max = 60;
                series[0].axisLine.lineStyle.color = [
                  [0.3, colors[0] || '#fd666d'],
                  [0.7, colors[1 % colors.length] || '#37a2da'],
                  [1, colors[2 % colors.length] || '#67e0e3']
                ];
                series[0].detail.formatter = `{value} ${unit || '°C'}`;
                series[0].detail.offsetCenter = [0, '70%'];
                series[0].pointer.length = '95%';
              }
              if (settings.has('grade')) {
                series[0].axisLine.lineStyle.color = [
                  [0.6, colors[0] || '#fd666d'],
                  [0.8, colors[1 % colors.length] || '#37a2da'],
                  [1, colors[2 % colors.length] || '#67e0e3']
                ];
                series[0].detail.formatter = (value) => {
                  if (value >= 90) return 'A';
                  if (value >= 80) return 'B';
                  if (value >= 70) return 'C';
                  if (value >= 60) return 'D';
                  return 'E';
                };
              }
              if (settings.has('multi-title')) {
                series = set2Labels.map((label, i) => ({
                  type: 'gauge',
                  center: [`${20 + (i * 30)}%`, '50%'],
                  radius: '50%',
                  progress: { show: true },
                  pointer: { show: false },
                  axisLine: { lineStyle: { width: 10, color: [[1, colors[i % colors.length]]] } },
                  axisTick: { show: false },
                  splitLine: { show: false },
                  axisLabel: { show: false },
                  detail: { show: false },
                  data: [{ value: datasets[i]?.[0] || 0 }],
                  title: { show: true, offsetCenter: [0, '-40%'], fontSize: 16, color: colors[i % colors.length], ...richSettings }
                }));
                option.title = set2Labels.map((label, i) => ({
                  text: label,
                  left: `${18 + (i * 30)}%`,
                  top: '40%',
                  textAlign: 'center',
                  textStyle: { ...richSettings, color: colors[i % colors.length] }
                }));
              }
              if (settings.has('barometer')) {
                series[0].startAngle = 180;
                series[0].endAngle = 0;
                series[0].axisLine.lineStyle.color = [
                  [0.2, colors[0] || '#91c7ae'],
                  [0.8, colors[1 % colors.length] || '#63869e'],
                  [1, colors[2 % colors.length] || '#c23531']
                ];
                series[0].axisLine.lineStyle.width = 20;
                series[0].splitNumber = 5;
                series[0].axisLabel.formatter = `{value} ${unit || 'hPa'}`;
                series[0].min = 900;
                series[0].max = 1100;
                series[0].detail.formatter = `{value} ${unit || 'hPa'}`;
              }
              option.series = series;
            } catch (e) {
              safeLog('warn', 'Failed to configure gauge chart.', e);
            }
            break;
          }
          case 'heatmap': {
            try {
              const x = Array.isArray(labelsX) && labelsX.length ? labelsX : ['X1', 'X2', 'X3'];
              const y = Array.isArray(labelsY) && labelsY.length ? labelsY : ['Y1', 'Y2'];
              const heat = Array.isArray(datasets[0]) ? datasets[0] : [];
              const vmax = heat.reduce((m, r) => Math.max(m, Number(r?.[2]) || 0), 10);
              option.xAxis = { type: 'category', data: x, splitArea: { show: true }, name: xAxisName };
              option.yAxis = { type: 'category', data: y, splitArea: { show: true }, name: yAxisName };
              option.visualMap = settings.has('visualmap') ? {
                min: 0,
                max: vmax,
                calculable: true,
                orient: 'horizontal',
                left: 'center',
                bottom: 10,
                inRange: { color: colors.slice(0, Math.min(3, colors.length)) }
              } : undefined;
              option.series = [{
                type: 'heatmap',
                data: heat.map((item, i) => Array.isArray(item) && item.length >= 3 ? {
                  value: item.slice(0, 3),
                  itemStyle: {
                    color: settings.has('gradient')
                      ? generateGradientColor(colors[i % colors.length], i, item[2])
                      : colors[i % colors.length]
                  }
                } : { value: [0, 0, 0], itemStyle: { color: colors[i % colors.length] } }),
                label: settings.has('labels') ? {
                  show: true,
                  rich: richSettings
                } : undefined
              }];
            } catch (e) {
              safeLog('warn', 'Failed to configure heatmap chart.', e);
            }
            break;
          }
          case 'candlestick': {
            try {
              const xs = Array.isArray(labels) && labels.length ? labels : inferLabelsFromDatasets(datasets);
              option.xAxis = { type: 'category', data: xs, name: xAxisName };
              option.yAxis = { type: 'value', scale: true, name: yAxisName };
              option.series = [{
                type: 'candlestick',
                data: Array.isArray(datasets[0]) ? datasets[0].map((item, i) => Array.isArray(item) && item.length === 4 ? {
                  value: item,
                  itemStyle: {
                    color: colors[i % colors.length],
                    color0: colors[(i + 1) % colors.length],
                    borderColor: colors[i % colors.length],
                    borderColor0: colors[(i + 1) % colors.length]
                  }
                } : { value: [0, 0, 0, 0], itemStyle: { color: colors[i % colors.length] } }) : [],
                label: settings.has('labels') ? {
                  show: true,
                  position: 'top',
                  rich: richSettings
                } : undefined
              }];
              if (settings.has('breaks')) {
                option.dataZoom = [{ type: 'inside' }];
              }
            } catch (e) {
              safeLog('warn', 'Failed to configure candlestick chart.', e);
            }
            break;
          }
          case 'funnel': {
            try {
              const lbls = Array.isArray(labels) && labels.length ? labels : inferLabelsFromDatasets(datasets);
              const arr = Array.isArray(datasets[0]) ? datasets[0] : [];
              const data = fitArray(arr, lbls.length, 0).map((v, i) => ({
                name: String(lbls[i]),
                value: Number(v) || 0,
                itemStyle: {
                  color: settings.has('gradient')
                    ? generateGradientColor(colors[i % colors.length], i, v)
                    : colors[i % colors.length]
                }
              }));
              option.series = [{
                type: 'funnel',
                left: '10%',
                top: 60,
                bottom: 20,
                width: '80%',
                label: settings.has('labels') ? {
                  show: true,
                  formatter: '{b}: {c}',
                  rich: richSettings
                } : undefined,
                data
              }];
            } catch (e) {
              safeLog('warn', 'Failed to configure funnel chart.', e);
            }
            break;
          }
          case 'treemap': {
            try {
              const items = Array.isArray(datasets[0]) ? datasets[0].filter(item => item && item.name && Number.isFinite(item.value)) : [];
              option.series = [{
                type: 'treemap',
                data: items.map((item, i) => ({
                  ...item,
                  itemStyle: {
                    color: settings.has('gradient')
                      ? generateGradientColor(colors[i % colors.length], i, item.value)
                      : colors[i % colors.length]
                  }
                })),
                label: settings.has('labels') ? {
                  show: true,
                  rich: richSettings
                } : undefined,
                upperLabel: settings.has('showparent') ? {
                  show: true,
                  rich: richSettings
                } : undefined
              }];
            } catch (e) {
              safeLog('warn', 'Failed to configure treemap chart.', e);
            }
            break;
          }
          case 'sunburst': {
            try {
              const data = Array.isArray(datasets[0]) ? datasets[0].filter(item => item && item.name) : [];
              option.series = [{
                type: 'sunburst',
                radius: ['0%', '90%'],
                data: data.map((item, i) => ({
                  ...item,
                  itemStyle: {
                    color: settings.has('gradient')
                      ? generateGradientColor(colors[i % colors.length], i, item.value)
                      : colors[i % colors.length]
                  }
                })),
                emphasis: settings.has('emphasis') ? { focus: 'descendant' } : undefined,
                label: settings.has('labels') ? {
                  show: true,
                  rotate: 'radial',
                  rich: richSettings
                } : undefined
              }];
            } catch (e) {
              safeLog('warn', 'Failed to configure sunburst chart.', e);
            }
            break;
          }
          case 'sankey': {
            try {
              const obj = (() => {
                try {
                  return (datasets[0] && typeof datasets[0] === 'object' && !Array.isArray(datasets[0]))
                    ? datasets[0]
                    : JSON.parse(String(datasets[0] || '{}'));
                } catch {
                  return { nodes: [], links: [] };
                }
              })();
              option.series = [{
                type: 'sankey',
                emphasis: { focus: 'adjacency' },
                data: Array.isArray(obj.nodes) ? obj.nodes.map((node, i) => ({
                  ...node,
                  itemStyle: {
                    color: settings.has('gradient')
                      ? generateGradientColor(colors[i % colors.length], i)
                      : colors[i % colors.length]
                  }
                })) : [],
                links: Array.isArray(obj.links) ? obj.links.filter(link => link.source != null && link.target != null && Number.isFinite(link.value)) : [],
                label: settings.has('labels') ? {
                  show: true,
                  rich: richSettings
                } : undefined,
                orient: settings.has('vertical') ? 'vertical' : 'horizontal',
                nodeAlign: settings.has('left') ? 'left' : 'right'
              }];
            } catch (e) {
              safeLog('warn', 'Failed to configure sankey chart.', e);
            }
            break;
          }
          case 'boxplot': {
            try {
              const xs = Array.isArray(labels) && labels.length ? labels : inferLabelsFromDatasets(datasets);
              option.xAxis = { type: 'category', data: xs, name: xAxisName };
              option.yAxis = { type: 'value', scale: true, name: yAxisName };
              option.series = [{
                type: 'boxplot',
                data: Array.isArray(datasets[0]) ? datasets[0].map((item, i) => Array.isArray(item) && item.length === 5 ? {
                  value: item,
                  itemStyle: {
                    color: colors[i % colors.length],
                    borderColor: colors[i % colors.length]
                  }
                } : { value: [0, 0, 0, 0, 0], itemStyle: { color: colors[i % colors.length] } }) : [],
                label: settings.has('labels') ? {
                  show: true,
                  rich: richSettings
                } : undefined
              }];
            } catch (e) {
              safeLog('warn', 'Failed to configure boxplot chart.', e);
            }
            break;
          }
          case 'parallel': {
            try {
              const parallelAxes = (Array.isArray(labels) && labels.length ? labels : inferLabelsFromDatasets(datasets))
                .map((name, i) => ({ dim: i, name: String(name) }));
              option.parallelAxis = parallelAxes;
              option.parallel = { left: 80, right: 80, top: 60, bottom: 60 };
              option.series = [{
                type: 'parallel',
                data: Array.isArray(datasets[0]) ? datasets[0].map((item, i) => Array.isArray(item) ? {
                  value: item,
                  lineStyle: {
                    color: colors[i % colors.length]
                  }
                } : { value: [], lineStyle: { color: colors[i % colors.length] } }) : [],
                label: settings.has('labels') ? {
                  show: true,
                  rich: richSettings
                } : undefined
              }];
            } catch (e) {
              safeLog('warn', 'Failed to configure parallel chart.', e);
            }
            break;
          }
          case 'lines': {
            try {
              const linesData = Array.isArray(datasets[0]) ? datasets[0].filter(item => item && Array.isArray(item.coords)) : [];
              option.xAxis = { type: 'value', scale: true, name: xAxisName };
              option.yAxis = { type: 'value', scale: true, name: yAxisName };
              option.series = [{
                type: 'lines',
                coordinateSystem: 'cartesian2d',
                data: linesData.map((item, i) => ({
                  coords: Array.isArray(item.coords) ? item.coords : [],
                  name: item.name || '',
                  lineStyle: {
                    color: settings.has('gradient')
                      ? generateGradientColor(colors[i % colors.length], i)
                      : colors[i % colors.length]
                  }
                })),
                polyline: true,
                effect: settings.has('effect') ? { show: true, symbol: 'circle', period: 4 } : undefined,
                label: settings.has('labels') ? {
                  show: true,
                  rich: richSettings
                } : undefined
              }];
            } catch (e) {
              safeLog('warn', 'Failed to configure lines chart.', e);
            }
            break;
          }
          case 'graph': {
            try {
              const nodes = Array.isArray(datasets[0]) ? datasets[0].filter(item => item && item.name) : [];
              const links = Array.isArray(datasets[1]) ? datasets[1].filter(link => link.source != null && link.target != null) : [];
              option.series = [{
                type: 'graph',
                layout: 'force',
                data: nodes.map((node, i) => ({
                  ...node,
                  itemStyle: {
                    color: settings.has('gradient')
                      ? generateGradientColor(colors[i % colors.length], i)
                      : colors[i % colors.length]
                  }
                })),
                links,
                force: { repulsion: 50, edgeLength: [50, 100] },
                roam: settings.has('roam'),
                label: settings.has('labels') ? {
                  show: true,
                  rich: richSettings
                } : undefined,
                draggable: settings.has('draggable')
              }];
            } catch (e) {
              safeLog('warn', 'Failed to configure graph chart.', e);
            }
            break;
          }
          case 'tree': {
            try {
              const treeData = Array.isArray(datasets[0]) ? datasets[0].filter(item => item && item.name) : [];
              option.series = [{
                type: 'tree',
                data: treeData.map((item, i) => ({
                  ...item,
                  itemStyle: {
                    color: settings.has('gradient')
                      ? generateGradientColor(colors[i % colors.length], i)
                      : colors[i % colors.length]
                  }
                })),
                layout: settings.has('radial') ? 'radial' : 'orthogonal',
                label: settings.has('labels') ? {
                  show: true,
                  rich: richSettings
                } : undefined,
                edgeShape: (!settings.has('radial') && settings.has('polyline')) ? 'polyline' : 'curve'
              }];
            } catch (e) {
              safeLog('warn', 'Failed to configure tree chart.', e);
            }
            break;
          }
          case 'themeriver': {
            try {
              const riverData = Array.isArray(datasets[0]) ? datasets[0].filter(item => Array.isArray(item) && item.length >= 3) : [];
              option.series = [{
                type: 'themeRiver',
                data: riverData.map((item, i) => Array.isArray(item) && item.length >= 3 ? {
                  value: item,
                  itemStyle: {
                    color: settings.has('gradient')
                      ? generateGradientColor(colors[i % colors.length], i, item[1])
                      : colors[i % colors.length]
                  }
                } : { value: ['2023-01-01', 0, 'Stream'], itemStyle: { color: colors[i % colors.length] } }),
                label: settings.has('labels') ? {
                  show: true,
                  rich: richSettings
                } : undefined
              }];
              option.singleAxis = { type: 'time' };
            } catch (e) {
              safeLog('warn', 'Failed to configure themeRiver chart.', e);
            }
            break;
          }
          case 'pictorialbar': {
            try {
              const baseLabels = Array.isArray(labels) && labels.length ? labels : inferLabelsFromDatasets(datasets);
              const data = Array.isArray(datasets[0]) ? datasets[0] : [];
              option.xAxis = { type: 'category', data: baseLabels, name: xAxisName };
              option.yAxis = { type: 'value', scale: true, name: yAxisName };
              option.series = [{
                type: 'pictorialBar',
                symbol,
                data: data.map((v, i) => ({
                  value: v,
                  itemStyle: {
                    color: settings.has('gradient')
                      ? generateGradientColor(colors[i % colors.length], i, v)
                      : colors[i % colors.length]
                  }
                })),
                label: settings.has('labels') ? {
                  show: true,
                  position: 'top',
                  rich: richSettings
                } : undefined,
                barWidth: settings.has('dotted') ? '99%' : undefined,
                itemStyle: settings.has('dotted') ? {
                  barBorderWidth: 1,
                  barBorderColor: '#ccc'
                } : undefined
              }];
            } catch (e) {
              safeLog('warn', 'Failed to configure pictorialBar chart.', e);
            }
            break;
          }
          case 'custom': {
            try {
              let renderItemStr = el.dataset.renderItem || `
                const data = [api.value(0), api.value(1)];
                if (data[0] == null || data[1] == null) return { type: 'group' };
                const [x, y] = api.coord(data);
                return {
                  type: 'circle',
                  shape: { cx: x, cy: y, r: 10 },
                  style: { fill: data[0] > 0 ? '${colors[0]}' : '${colors[1 % colors.length]}' }
                };
              `;
              renderItemStr = renderItemStr.replace(/params\.data/g, 'data');
              let renderItem;
              try {
                renderItem = new Function('params', 'api', `
                  try {
                    const data = [api.value(0), api.value(1)];
                    if (data[0] == null || data[1] == null) {
                      console.warn('Invalid data in renderItem');
                      return { type: 'group' };
                    }
                    ${renderItemStr}
                  } catch (e) {
                    console.warn('RenderItem error:', e);
                    return { type: 'group' };
                  }
                `);
              } catch (e) {
                safeLog('warn', 'Invalid renderItem; using default.', e);
                renderItem = (params, api) => {
                  try {
                    const val0 = api.value(0);
                    const val1 = api.value(1);
                    if (val0 == null || val1 == null) {
                      safeLog('warn', 'Invalid data in custom renderItem');
                      return { type: 'group' };
                    }
                    const [x, y] = api.coord([val0, val1]);
                    return {
                      type: 'circle',
                      shape: { cx: x, cy: y, r: 10 },
                      style: {
                        fill: settings.has('gradient')
                          ? generateGradientColor(colors[params.dataIndex % colors.length], params.dataIndex, val1)
                          : colors[params.dataIndex % colors.length]
                      }
                    };
                  } catch (e) {
                    safeLog('warn', 'Custom renderItem execution failed.', e);
                    return { type: 'group' };
                  }
                };
              }
              option.xAxis = { type: 'value', scale: true, name: xAxisName };
              option.yAxis = { type: 'value', scale: true, name: yAxisName };
              option.series = [{
                type: 'custom',
                renderItem,
                data: Array.isArray(datasets[0]) ? datasets[0].map((item, i) => Array.isArray(item) && item.length >= 2 ? {
                  value: item.slice(0, 2),
                  itemStyle: {
                    color: colors[i % colors.length]
                  }
                } : { value: [0, 0], itemStyle: { color: colors[i % colors.length] } }) : [],
                label: settings.has('labels') ? {
                  show: true,
                  rich: richSettings
                } : undefined
              }];
            } catch (e) {
              safeLog('warn', 'Failed to configure custom chart.', e);
            }
            break;
          }
          default:
            safeLog('warn', `Unsupported chart type "${chartType}".`, el);
            el.removeChild(loadingDiv);
            continue;
        }

        // Initialize chart and remove shimmer
        let chart;
        try {
          chart = echarts.init(el, null, { renderer: 'canvas' });
          chart.setOption(option, { notMerge: true, lazyUpdate: true, silent: true });
          if (settings.has('fisheye') && option.graphic && option.graphic[0]) {
            option.graphic[0].__chart = chart;
          }
          // Remove shimmer after successful initialization
          if (el.contains(loadingDiv)) {
            el.removeChild(loadingDiv);
          }
        } catch (e) {
          safeLog('warn', 'Chart initialization/setOption failed.', e);
          if (el.contains(loadingDiv)) {
            el.removeChild(loadingDiv);
          }
          continue;
        }

        // Setup resize observer
        const cleanup = observeResize(el, chart);
        el.__echartsInstance = chart;
        el.__cleanup = cleanup;
      } catch (e) {
        safeLog('warn', 'Non-fatal error processing chart element.', e);
        if (el.contains(loadingDiv)) {
          el.removeChild(loadingDiv);
        }
      }
    }
  } catch (e) {
    safeLog('error', 'Charts rendering failed.', e);
  }
}

export { charts };