import '../../../libs/addons/tree/raphael.min.js';
import '../../../libs/addons/tree/Treant.min.js';
export function path(divId, jsonData, selectedIds, options = "single", schema = false) {
  try {
    // Validate options
    const validOptions = ["single", "multiple"];
    if (!validOptions.includes(options)) {
      window.general.error(`Invalid options value: ${options}. Must be one of: ${validOptions.join(", ")}`);
      return;
    }
    const container = document.querySelector(`[data-path-id="${divId}"]`);
    if (!container) {
      window.general.error('Container element not found for divId:', divId);
      return;
    }
    const trigger = container.querySelector('.path-trigger');
    const menu = container.querySelector('.path-dropdown-menu');
    if (!trigger || !menu) {
      window.general.error('Trigger or menu element missing in container:', divId);
      return;
    }
    // Normalize JSON data to ensure children is an array
    const normalizeChildren = (node) => {
      if (node.children && !Array.isArray(node.children)) {
        node.children = Object.keys(node.children).length === 0 ? [] : [node.children];
      }
      (node.children || []).forEach(child => normalizeChildren(child));
      return node;
    };
    const data = typeof jsonData === 'string' ? JSON.parse(jsonData) : jsonData;
    if (!Array.isArray(data) || data.length === 0) {
      container.innerHTML = '<div class="alert alert-warning">No data available</div>';
      window.general.error('Invalid or empty JSON data for path:', jsonData);
      return;
    }
    // Normalize the entire data structure
    const normalizedData = data.map(node => normalizeChildren({ ...node }));
    // Parse selectedIds
    let selectedIdArray = [];
    if (selectedIds) {
      if (typeof selectedIds === 'string') {
        selectedIdArray = selectedIds.split(',').map(id => id.trim()).filter(id => id);
      } else if (Array.isArray(selectedIds)) {
        selectedIdArray = selectedIds.map(id => String(id));
      } else {
        selectedIdArray = [String(selectedIds)];
      }
      if (options === "single" && selectedIdArray.length > 1) {
        selectedIdArray = [selectedIdArray[0]];
      }
    }
    // Initialize hidden input
    let hiddenInput = container.querySelector('input[data-scope]');
    if (!hiddenInput) {
      hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = container.getAttribute('data-path-name') || 'selected_ids';
      hiddenInput.id = `hidden-${divId}`;
      container.appendChild(hiddenInput);
    }
    // Styling
    const pastelColors = ['#FFFFFF', '#FAFAFA', '#FFF2F2', '#F2FFF4', '#F2F2FF', '#FAF2FF', '#F2FFFF', '#FEFFF2'];
    const companyColors = {};
    let colorIndex = 0;
    normalizedData.forEach(item => {
      companyColors[item.id] = item.background || pastelColors[colorIndex++ % pastelColors.length];
    });
    menu.innerHTML = '';
    // SVG line helper
    const createSvgLine = (type, depth) => {
      const svgNS = "http://www.w3.org/2000/svg";
      const unitWidth = 10;
      const svgWidth = depth * unitWidth + (type !== 'empty' ? unitWidth : 0);
      const svg = document.createElementNS(svgNS, 'svg');
      svg.setAttribute('width', `${svgWidth}`);
      svg.setAttribute('height', '24');
      svg.setAttribute('viewBox', `0 0 ${svgWidth} 24`);
      svg.style.display = 'inline-block';
      svg.style.verticalAlign = 'middle';
      svg.style.marginRight = '2px';
      const path = document.createElementNS(svgNS, 'path');
      if (type === 'vertical') {
        path.setAttribute('d', `M${depth * unitWidth + unitWidth / 2} 0 V24`);
      } else if (type === 'branch') {
        path.setAttribute('d', `M${depth * unitWidth + unitWidth / 2} 12 H${depth * unitWidth + unitWidth} M${depth * unitWidth + unitWidth / 2} 0 V24`);
      } else if (type === 'last') {
        path.setAttribute('d', `M${depth * unitWidth + unitWidth / 2} 12 H${depth * unitWidth + unitWidth} M${depth * unitWidth + unitWidth / 2} 0 V12`);
      }
      if (type !== 'empty') {
        path.setAttribute('stroke', '#666');
        path.setAttribute('stroke-width', '1');
        svg.appendChild(path);
      }
      return svg;
    };
    // Find node by ID
    const findNodeById = (id) => {
      return normalizedData.find(n => n.id === id) || (function findNode(nodes) {
        for (const n of nodes) {
          if (n.id === id) return n;
          const found = findNode(Array.isArray(n.children) ? n.children : []);
          if (found) return found;
        }
      })(normalizedData);
    };
    // Get all child IDs recursively
    const getAllChildIds = (node) => {
      const childIds = [];
      (Array.isArray(node.children) ? node.children : []).forEach(child => {
        childIds.push(child.id);
        childIds.push(...getAllChildIds(child));
      });
      return childIds;
    };
    // Get parent IDs for a given node ID
    const getParentIds = (nodeId, nodes) => {
      const parentIds = [];
      const findParents = (items, targetId) => {
        for (const item of items) {
          if (item.id === targetId) return true;
          if ((Array.isArray(item.children) ? item.children : []).some(child => child.id === targetId || findParents([child], targetId))) {
            parentIds.push(item.id);
            return true;
          }
        }
        return false;
      };
      findParents(nodes, nodeId);
      return parentIds;
    };
    // Update hidden input
    const updateHiddenInput = () => {
      const filteredIds = selectedIdArray.filter(id => {
        const parentIds = getParentIds(id, normalizedData);
        return options !== "multiple" || !parentIds.some(parentId => selectedIdArray.includes(parentId));
      });
      const newValue = options === "multiple"
        ? filteredIds.join(',')
        : (filteredIds[0] || '');
      // Update hidden input
      hiddenInput.value = newValue;
      // Dispatch change event so listeners react
      hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
      hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
      // Update visible trigger text
      trigger.textContent = filteredIds.map(id => {
        const node = findNodeById(id);
        return node?.name || '';
      }).filter(label => label).join(', ')
        || trigger.getAttribute('data-placeholder')
        || 'Select an option';
    };
    // Update disabled state for multiple mode
    const updateDisabledState = () => {
      menu.querySelectorAll('.path-item').forEach(item => {
        const id = item.dataset.id;
        const parentIds = getParentIds(id, normalizedData);
        const isDisabled = options === "multiple" && parentIds.some(parentId => selectedIdArray.includes(parentId));
        item.classList.toggle('path-disabled', isDisabled);
        item.style.backgroundColor = isDisabled ? '#e0e0e0' : ('transparent' || '#ffffff');
        item.style.cursor = isDisabled ? 'not-allowed' : 'pointer';
      });
    };
    // Build dropdown tree
    const buildTree = (node, label, depth = 0, parentsLast = [], isLast = true, companyKey = '') => {
      if (!node || !label) return;
      const item = document.createElement('div');
      item.className = 'path-item';
      item.dataset.id = node.id;
      item.dataset.label = label;
      // Add data-schema attribute if schema parameter is true and node has schema
      if (schema && node.schema) {
        try {
          item.dataset.schema = JSON.stringify(node.schema);
          item.dataset.desc = node.description;
          item.dataset.allow = node.allow_form;
        } catch (e) {
          console.warn(`Failed to stringify schema for node ${node.id}:`, e);
        }
      }
      const lineContainer = document.createElement('span');
      lineContainer.style.display = 'inline-block';
      for (let i = 0; i < depth; i++) {
        lineContainer.appendChild(createSvgLine(parentsLast[i] ? 'empty' : 'vertical', i));
      }
      if (depth > 0) {
        lineContainer.appendChild(createSvgLine(isLast ? 'last' : 'branch', depth));
      }
      const labelSpan = document.createElement('span');
      labelSpan.textContent = label;
      labelSpan.style.display = 'inline-block';
      labelSpan.style.verticalAlign = 'middle';
      item.appendChild(lineContainer);
      item.appendChild(labelSpan);
      item.style.background = companyKey ? companyColors[companyKey] : node.background || '#ffffff';
      item.style.color = node.background || '#333333';
      if (selectedIdArray.includes(String(node.id))) {
        item.classList.add('path-active');
      }
      item.addEventListener('click', e => {
        e.stopPropagation();
        const id = String(node.id);
        const parentIds = getParentIds(id, normalizedData);
        if (options === "multiple" && parentIds.some(parentId => selectedIdArray.includes(parentId))) {
          return;
        }
        if (options === "multiple") {
          const childIds = getAllChildIds(node);
          if (selectedIdArray.includes(id)) {
            selectedIdArray = selectedIdArray.filter(selectedId => selectedId !== id);
            item.classList.remove('path-active');
          } else {
            selectedIdArray = selectedIdArray.filter(selectedId => !childIds.includes(selectedId));
            selectedIdArray.push(id);
            item.classList.add('path-active');
            childIds.forEach(childId => {
              const childItem = menu.querySelector(`.path-item[data-id="${childId}"]`);
              if (childItem) childItem.classList.remove('path-active');
            });
          }
        } else {
          menu.querySelectorAll('.path-item').forEach(i => i.classList.remove('path-active'));
          selectedIdArray = [id];
          item.classList.add('path-active');
          container.classList.remove('open');
        }
        updateHiddenInput();
        updateDisabledState();
      });
      menu.appendChild(item);
      (Array.isArray(node.children) ? node.children : []).forEach((child, idx) => {
        buildTree(child, child.name, depth + 1, [...parentsLast, isLast], idx === node.children.length - 1, companyKey || (depth === 0 ? node.id : companyKey));
      });
    };
    normalizedData.forEach((item, idx) => {
      buildTree(item, item.name, 0, [], idx === normalizedData.length - 1, item.id);
    });
    updateHiddenInput();
    updateDisabledState();
    const toggleDropdown = () => {
      const rect = container.getBoundingClientRect();
      const below = window.innerHeight - rect.bottom;
      const above = rect.top;
      container.classList.toggle('open');
      if (container.classList.contains('open')) {
        container.classList.toggle('path-open-up', below < 300 && above > below);
      }
    };
    trigger.removeEventListener('click', toggleDropdown);
    trigger.addEventListener('click', toggleDropdown);
    const closeDropdown = e => {
      if (!container.contains(e.target)) {
        container.classList.remove('open');
      }
    };
    document.removeEventListener('click', closeDropdown);
    document.addEventListener('click', closeDropdown);
  } catch (e) {
    window.general.error('Error in path:', e);
  }
}
export function tree(containerId, jsonData, focusId = null, update = false) {
  try {
    const Treant = window.Treant;
    if (typeof Treant !== 'function') {
      window.general.error?.('Treant is not available');
      window.general.errorToast?.('Tree Loading Failed', 'Treant library not available');
      throw new Error('Treant is not available.');
    }
    const container = document.getElementById(containerId);
    if (!container) {
      window.general.error?.(`Container "${containerId}" not found`);
      throw new Error(`Container "${containerId}" not found.`);
    }
    const token = container.dataset.token || '';
    if (!container.style.width) container.style.width = '100vw';
    if (!container.style.height) container.style.height = '600px';
    // Parse JSON data
    let data = typeof jsonData === 'string' ? JSON.parse(jsonData) : jsonData;
    if (!data || typeof data !== 'object' || Object.keys(data).length === 0) {
      const errorNode = window.general.errorDivEmpty?.();
      if (errorNode instanceof Node) {
        container.appendChild(errorNode);
      } else {
        container.innerHTML = '<div class="tree-error">No data available</div>';
      }
      window.general.error?.('Invalid or empty JSON data for tree:', jsonData);
      window.general.errorToast?.('Tree Loading Failed', 'No data available');
      return;
    }
    // Convert to Treant format
    function createNodeHTML(data, depth) {
      const profileHTML = data.profile?.startsWith('http')
        ? `<div class="tree-profile-circle"><img src="${data.profile}" alt="${data.name || 'Profile'}" /></div>`
        : `<div class="tree-profile-circle">${data.profile || (data.name ? data.name[0] : '?')}</div>`;
      const desc = data.description || 'No description';
      const shortDesc = desc.length > 30 ? desc.substring(0, 27) + '...' : desc;
      const actionButtons = depth > 0 ? `
        <div class="tree-node-actions" data-id="${data.id}" data-token="${token}">
            <span class="skeleton-popup" data-token="${token}_v_${data.id}"><i class="ti ti-eye" title="View"></i></span>
            <span class="skeleton-popup" data-token="${token}_e_${data.id}"><i class="ti ti-edit" title="Edit"></i></span>
            <span class="skeleton-popup" data-token="${token}_d_${data.id}"><i class="ti ti-trash" title="Delete"></i></span>
        </div>
      ` : '';
      return `
        <div class="tree-node-box" style="background-color: ${data.background || data.color || '#FFD700'};">
            <div class="tree-node-row">
                ${profileHTML}
                <div class="d-flex flex-column">
                    <div class="tree-node-title">${data.name || 'Unnamed'}</div>
                    <div class="tree-node-code">Code: <b>${data.code || '-'}</b></div>
                    <div class="tree-node-desc" data-toggle="tooltip" data-placement="top" title="${desc}">${shortDesc}</div>
                </div>
            </div>
            ${actionButtons}
        </div>
      `;
    }
    function buildTree(node, label, depth = 1) {
      if (!node || typeof node !== 'object' || !node.id) return null;
      const treeNode = {
        HTMLid: `node-${node.id}`,
        innerHTML: createNodeHTML({ ...node, name: label }, depth),
        children: [],
        collapsable: false
      };
      // Handle children (JSON hierarchy)
      let children = [];
      if (node.children && typeof node.children === 'object' && Object.keys(node.children).length > 0) {
        children = Object.entries(node.children).map(([childLabel, childNode]) =>
          buildTree(childNode, childNode.name || childLabel, depth + 1)
        ).filter(Boolean);
      }
      if (children.length > 0) {
        treeNode.children = children;
        treeNode.collapsable = true;
      } else {
        delete treeNode.children;
      }
      return treeNode;
    }
    let rootNodes;
    if (Array.isArray(data)) {
      // JSON hierarchy
      rootNodes = data.map(node => buildTree(node, node.name, 1)).filter(Boolean);
    } else {
      // Flat paths
      const map = {};
      const treeData = [];
      Object.entries(data).forEach(([id, path]) => {
        const parts = path.split('->');
        let parent = null;
        let currentPath = '';
        parts.forEach((name, idx) => {
          const nodePath = currentPath ? `${currentPath}->${name}` : name;
          const nodeId = idx === parts.length - 1 ? id : `virtual-${nodePath}`;
          if (!map[nodeId]) {
            map[nodeId] = {
              id: nodeId,
              name: name.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase()),
              code: idx === parts.length - 1 ? id.replace('SCP', 'DHA') : '',
              background: idx === parts.length - 1 ? '#FFD700' : '#FFFFFF',
              color: '#000000',
              profile: idx === parts.length - 1 ? name[0].toUpperCase() : name[0].toUpperCase(),
              parent_id: parent ? parent.id : null
            };
            treeData.push(map[nodeId]);
          }
          parent = map[nodeId];
          currentPath = nodePath;
        });
      });
      // Build hierarchy from flat data
      const nodeMap = {};
      treeData.forEach(node => {
        nodeMap[node.id] = { ...node, children: {} };
      });
      treeData.forEach(node => {
        if (node.parent_id && nodeMap[node.parent_id]) {
          nodeMap[node.parent_id].children[node.id] = nodeMap[node.id];
        }
      });
      rootNodes = Object.values(nodeMap)
        .filter(node => !node.parent_id)
        .map(node => buildTree(node, node.name, 1))
        .filter(Boolean);
    }
    if (rootNodes.length === 0) {
      const errorNode = window.general.errorDivEmpty?.();
      if (errorNode instanceof Node) {
        container.appendChild(errorNode);
      } else {
        container.innerHTML = '<div class="tree-error">No data available</div>';
      }
      window.general.error?.('No valid nodes for tree');
      window.general.errorToast?.('Tree Loading Failed', 'No valid nodes available');
      return;
    }
    const config = {
      chart: {
        container: `#${containerId}`,
        rootOrientation: 'NORTH',
        node: { collapsable: true },
        connectors: {
          type: 'step',
          style: {
            stroke: '#666',
            'stroke-width': 1.5,
            'arrow-end': 'block-wide-short'
          }
        },
        animation: {
          nodeAnimation: 'easeOutQuad',
          nodeSpeed: 500,
          connectorsAnimation: 'linear',
          connectorsSpeed: 500
        },
        levelSeparation: 60,
        siblingSeparation: 30,
        subTeeSeparation: 30
      },
      nodeStructure: rootNodes.length === 1
        ? rootNodes[0]
        : {
          text: { name: container.dataset.rootName || 'Root' },
          HTMLclass: 'root-node',
          children: rootNodes
        }
    };
    if (update) {
      container.innerHTML = '';
      container.className = '';
    }
    new Treant(config);
    if (focusId) {
      setTimeout(() => {
        const target = document.getElementById(`node-${focusId}`);
        if (target) {
          target.scrollIntoView({ behavior: 'smooth', block: 'center' });
          target.classList.add('highlight-beat');
          setTimeout(() => target.classList.remove('highlight-beat'), 5000);
        }
      }, 1000);
    }
  } catch (e) {
    window.general.error?.('Error in tree:', e);
    window.general.errorToast?.('Tree Loading Failed', 'Failed to load tree visualization');
    const container = document.getElementById(containerId);
    if (container) {
      const errorNode = window.general.errorDivEmpty?.();
      if (errorNode instanceof Node) {
        container.appendChild(errorNode);
      } else {
        container.innerHTML = '<div class="tree-error">Failed to load tree</div>';
      }
    }
  }
}