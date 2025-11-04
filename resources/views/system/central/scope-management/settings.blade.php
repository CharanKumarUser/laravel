{{-- Template: Settings Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Settings')
@push('styles')
    <style>
        .panel {
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 10px;
            width: 45%;
            min-height: 400px;
            display: inline-block;
            vertical-align: top;
            margin-right: 10px;
        }

        .panel h3 {
            margin: 0 0 10px;
        }

        .path-item {
            display: flex;
            align-items: center;
            padding: 2px 5px;
            cursor: grab;
            margin-bottom: 2px;
        }

        .path-item.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .path-item:hover:not(.disabled) {
            background: #f0f0f0;
        }

        .path-item svg {
            display: inline-block;
            vertical-align: middle;
            margin-right: 2px;
        }

        .path-item input {
            width: 200px;
            display: inline-block;
            vertical-align: middle;
            margin-left: 5px;
        }

        .path-item span[data-bs-toggle="tooltip"] {
            color: inherit;
        }

        .drop-area {
            padding: 10px;
            border: 2px dashed #ccc;
            border-radius: 4px;
            min-height: 50px;
            background: #fafafa;
            margin-top: 5px;
        }

        .drop-area.drag-over {
            background: #e0ffe0 !important;
        }

        .section {
            margin-bottom: 10px;
        }

        #output {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            background: #f9f9f9;
            width: 90%;
        }

        .context-menu {
            position: absolute;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        .context-menu div {
            padding: 8px 12px;
            cursor: pointer;
        }

        .context-menu div:hover {
            background: #f0f0f0;
        }

        #createSectionBtn {
            margin-bottom: 10px;
        }

        .action-btn {
            margin-left: 5px;
        }

        .ui-sortable-placeholder {
            background: #e0e0e0;
            height: 30px;
            border-radius: 4px;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function scopeMenu(available, previous = []) {
            const availableTree = document.getElementById('availableTree');
            const menuTree = document.getElementById('menuTree');
            const jsonOutput = document.getElementById('jsonOutput');
            const createSectionBtn = document.getElementById('createSectionBtn');

            // Normalize children to arrays
            function normalizeChildren(data) {
                // Handle non-array inputs
                if (!data) return [];
                if (!Array.isArray(data)) {
                    data = Object.values(data || {});
                }
                return data.map(node => ({
                    ...node,
                    children: normalizeChildren(node.children)
                }));
            }

            // Deep clone and normalize inputs
            let menuData = JSON.parse(JSON.stringify(previous)).map(section => ({
                ...section,
                children: Array.isArray(section.children) ? section.children : []
            }));
            let usedIds = new Set(menuData.flatMap(sec => [sec.id, ...(sec.children || []).map(i => i.id)]));
            const normalizedAvailable = normalizeChildren(JSON.parse(JSON.stringify(available || [])));

            // SVG line helper
            const createSvgLine = (type, depth) => {
                const svgNS = "http://www.w3.org/2000/svg";
                const unitWidth = 10;
                const svgWidth = depth * unitWidth + (type !== 'empty' ? unitWidth : 0);
                const svg = document.createElementNS(svgNS, 'svg');
                svg.setAttribute('width', svgWidth);
                svg.setAttribute('height', '24');
                svg.setAttribute('viewBox', `0 0 ${svgWidth} 24`);
                svg.style.display = 'inline-block';
                svg.style.verticalAlign = 'middle';
                svg.style.marginRight = '2px';
                const path = document.createElementNS(svgNS, 'path');
                if (type === 'vertical') {
                    path.setAttribute('d', `M${depth * unitWidth + unitWidth / 2} 0 V24`);
                } else if (type === 'branch') {
                    path.setAttribute('d',
                        `M${depth * unitWidth + unitWidth / 2} 12 H${depth * unitWidth + unitWidth} M${depth * unitWidth + unitWidth / 2} 0 V24`
                        );
                } else if (type === 'last') {
                    path.setAttribute('d',
                        `M${depth * unitWidth + unitWidth / 2} 12 H${depth * unitWidth + unitWidth} M${depth * unitWidth + unitWidth / 2} 0 V12`
                        );
                }
                if (type !== 'empty') {
                    path.setAttribute('stroke', '#666');
                    path.setAttribute('stroke-width', '1');
                    svg.appendChild(path);
                }
                return svg;
            };

            // Build tree for available items
            const buildAvailableTree = (node, depth = 0, parentsLast = [], isLast = true) => {
                const item = document.createElement('div');
                item.className = `path-item ${usedIds.has(node.id) ? 'disabled' : ''}`;
                item.dataset.id = node.id;
                item.dataset.type = 'available';
                item.draggable = !usedIds.has(node.id);

                const lineContainer = document.createElement('span');
                lineContainer.style.display = 'inline-block';
                for (let i = 0; i < depth; i++) {
                    lineContainer.appendChild(createSvgLine(parentsLast[i] ? 'empty' : 'vertical', i));
                }
                if (depth > 0) {
                    lineContainer.appendChild(createSvgLine(isLast ? 'last' : 'branch', depth));
                }
                const labelSpan = document.createElement('span');
                labelSpan.textContent = node.data?.name || node.name || 'Unnamed';
                labelSpan.style.display = 'inline-block';
                labelSpan.style.verticalAlign = 'middle';
                labelSpan.style.color = node.color || '#333333';
                labelSpan.setAttribute('data-bs-toggle', 'tooltip');
                labelSpan.setAttribute('data-bs-original-title', node.group || '');
                item.appendChild(lineContainer);
                item.appendChild(labelSpan);

                availableTree.appendChild(item);
                const children = Array.isArray(node.children) ? node.children : Object.values(node.children || {});
                children.forEach((child, idx) => {
                    buildAvailableTree(child, depth + 1, [...parentsLast, isLast], idx === children.length - 1);
                });
            };

            // Render available tree
            function renderAvailable() {
                availableTree.innerHTML = '';
                normalizedAvailable.forEach((item, idx) => {
                    buildAvailableTree(item, 0, [], idx === normalizedAvailable.length - 1);
                });
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
            }

            // Get display name for section
            const getSectionDisplayName = (section) => {
                if (section.customName) return section.customName;
                return section.children && section.children.length === 1 ?
                    section.children[0].data?.name || section.children[0].name || 'Section' :
                    section.children && section.children.length > 1 ?
                    section.children.map(item => item.data?.name || item.name).join(', ') :
                    section.data?.name || section.name || 'Section';
            };

            // Render menu tree
            function renderMenu() {
                menuTree.innerHTML = '';
                menuData.forEach((section, secIndex) => {
                    const secDiv = document.createElement('div');
                    secDiv.className = 'section';
                    secDiv.dataset.id = section.id;
                    secDiv.dataset.type = 'section';

                    const headerDiv = document.createElement('div');
                    headerDiv.className = 'path-item';
                    const secInput = document.createElement('input');
                    secInput.type = 'text';
                    secInput.placeholder = 'Enter section name';
                    secInput.value = getSectionDisplayName(section);
                    secInput.className = 'form-control form-control-sm';
                    secInput.addEventListener('input', (e) => {
                        section.customName = e.target.value || null;
                        section.data.name = getSectionDisplayName(section);
                        jsonOutput.textContent = JSON.stringify(menuData, null, 2);
                    });
                    const closeSecBtn = document.createElement('button');
                    closeSecBtn.className = 'btn btn-danger btn-sm action-btn';
                    closeSecBtn.textContent = 'Close';
                    closeSecBtn.addEventListener('click', () => {
                        const index = menuData.findIndex(sec => sec.id === section.id);
                        if (index === -1) return;
                        const sec = menuData.splice(index, 1)[0];
                        (sec.children || []).forEach(item => usedIds.delete(item.id));
                        usedIds.delete(sec.id);
                        renderAvailable();
                        renderMenu();
                        initSortables();
                    });
                    headerDiv.appendChild(secInput);
                    headerDiv.appendChild(closeSecBtn);
                    secDiv.appendChild(headerDiv);

                    const dropArea = document.createElement('div');
                    dropArea.className = 'drop-area';
                    dropArea.dataset.sectionId = section.id;
                    (section.children || []).forEach((item, itemIndex) => {
                        const itemDiv = document.createElement('div');
                        itemDiv.className = 'path-item item';
                        itemDiv.dataset.id = item.id;
                        itemDiv.dataset.type = 'item';
                        const lineContainer = document.createElement('span');
                        lineContainer.style.display = 'inline-block';
                        lineContainer.appendChild(createSvgLine(itemIndex === section.children.length - 1 ?
                            'last' : 'branch', 1));
                        const itemLabel = document.createElement('span');
                        itemLabel.textContent = item.data?.name || item.name || 'Item';
                        itemLabel.style.display = 'inline-block';
                        itemLabel.style.verticalAlign = 'middle';
                        itemLabel.style.color = item.color || '#333333';
                        itemLabel.setAttribute('data-bs-toggle', 'tooltip');
                        itemLabel.setAttribute('data-bs-original-title', item.group || '');
                        const closeItemBtn = document.createElement('button');
                        closeItemBtn.className = 'btn btn-danger btn-sm action-btn';
                        closeItemBtn.textContent = 'Close';
                        closeItemBtn.addEventListener('click', () => {
                            const secIndex = menuData.findIndex(sec => sec.children && sec.children
                                .some(i => i.id === item.id));
                            if (secIndex === -1) return;
                            const itemIndex = menuData[secIndex].children.findIndex(i => i.id ===
                                item.id);
                            if (itemIndex === -1) return;
                            menuData[secIndex].children.splice(itemIndex, 1);
                            usedIds.delete(item.id);
                            menuData[secIndex].data.name = getSectionDisplayName(menuData[
                            secIndex]);
                            if (menuData[secIndex].children.length === 0) {
                                usedIds.delete(menuData[secIndex].id);
                                menuData.splice(secIndex, 1);
                            }
                            renderAvailable();
                            renderMenu();
                            initSortables();
                        });
                        itemDiv.appendChild(lineContainer);
                        itemDiv.appendChild(itemLabel);
                        itemDiv.appendChild(closeItemBtn);
                        dropArea.appendChild(itemDiv);
                    });

                    secDiv.appendChild(dropArea);
                    menuTree.appendChild(secDiv);
                });

                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
                jsonOutput.textContent = JSON.stringify(menuData, null, 2);
                initSortables();
            }

            // Initialize jQuery UI Sortable
            function initSortables() {
                $('#menuTree').sortable({
                    items: '> .section',
                    placeholder: 'ui-sortable-placeholder',
                    update: function(event, ui) {
                        const newOrder = $(this).sortable('toArray', {
                            attribute: 'data-id'
                        });
                        const newMenuData = newOrder.map(id => menuData.find(sec => sec.id === id)).filter(
                            sec => sec);
                        menuData = newMenuData;
                        jsonOutput.textContent = JSON.stringify(menuData, null, 2);
                        renderMenu();
                    }
                });

                $('.drop-area[data-section-id]').sortable({
                    items: '> .item',
                    connectWith: '.drop-area[data-section-id]',
                    placeholder: 'ui-sortable-placeholder',
                    start: function(event, ui) {
                        ui.item.data('fromSectionId', ui.item.closest('.drop-area').data('sectionId'));
                    },
                    stop: function(event, ui) {
                        const itemId = ui.item.data('id');
                        const toSectionId = ui.item.closest('.drop-area').data('sectionId');
                        const fromSectionId = ui.item.data('fromSectionId');

                        if (!itemId || !toSectionId || !fromSectionId) return;

                        const fromSecIndex = menuData.findIndex(sec => sec.id === fromSectionId);
                        const toSecIndex = menuData.findIndex(sec => sec.id === toSectionId);

                        if (fromSecIndex === -1 || toSecIndex === -1) return;

                        if (fromSecIndex === toSecIndex) {
                            // Reorder within section
                            const newOrder = $(ui.item.closest('.drop-area')).sortable('toArray', {
                                attribute: 'data-id'
                            });
                            const newItems = newOrder.map(id => menuData[toSecIndex].children.find(i => i.id ===
                                id)).filter(item => item);
                            menuData[toSecIndex].children = newItems;
                        } else {
                            // Move between sections
                            const itemIndex = menuData[fromSecIndex].children.findIndex(i => i.id === itemId);
                            if (itemIndex === -1) return;
                            const [itemObj] = menuData[fromSecIndex].children.splice(itemIndex, 1);
                            menuData[toSecIndex].children.push(itemObj);

                            // Update section names
                            menuData[fromSecIndex].data.name = getSectionDisplayName(menuData[fromSecIndex]);
                            if (menuData[fromSecIndex].children.length === 0) {
                                usedIds.delete(menuData[fromSecIndex].id);
                                menuData.splice(fromSecIndex, 1);
                            }
                        }

                        // Update target section name
                        if (menuData[toSecIndex]) {
                            menuData[toSecIndex].data.name = getSectionDisplayName(menuData[toSecIndex]);
                        }

                        // Rebuild usedIds
                        usedIds.clear();
                        menuData.forEach(sec => {
                            usedIds.add(sec.id);
                            (sec.children || []).forEach(item => usedIds.add(item.id));
                        });

                        renderAvailable();
                        renderMenu();
                    }
                });
            }

            // Create new section
            createSectionBtn.addEventListener('click', () => {
                const newSection = {
                    id: `SEC_${Date.now()}`,
                    data: {
                        name: 'New Section',
                        type: 'section',
                        schema: 'menu_section'
                    },
                    children: [],
                    code: `SEC_${Date.now()}`,
                    group: 'Section',
                    description: 'Menu Section',
                    background: '#dceeff',
                    color: '#333333',
                    schema: [],
                    allow_form: 1,
                    customName: null
                };
                menuData.push(newSection);
                usedIds.add(newSection.id);
                renderMenu();
            });

            // Context menu for item/section conversion
            let contextTarget = null;
            const contextMenu = document.createElement('div');
            contextMenu.className = 'context-menu';
            contextMenu.innerHTML = `
                <div data-action="to-section">Convert to Section</div>
                <div data-action="to-item">Convert to Item</div>
            `;
            document.body.appendChild(contextMenu);

            document.addEventListener('contextmenu', e => {
                e.preventDefault();
                if (e.target.closest('.path-item') && e.target.closest('#menuTree') && !e.target.classList.contains(
                        'action-btn')) {
                    contextTarget = e.target.closest('.path-item');
                    contextMenu.style.display = 'block';
                    contextMenu.style.left = `${e.pageX}px`;
                    contextMenu.style.top = `${e.pageY}px`;
                } else {
                    contextMenu.style.display = 'none';
                    contextTarget = null;
                }
            });

            document.addEventListener('click', () => {
                contextMenu.style.display = 'none';
                contextTarget = null;
            });

            contextMenu.addEventListener('click', e => {
                if (!contextTarget) return;
                const action = e.target.dataset.action;
                const id = contextTarget.dataset.id;
                const type = contextTarget.dataset.type;

                if (action === 'to-section' && type === 'item') {
                    const secIndex = menuData.findIndex(sec => sec.children && sec.children.some(i => i.id === id));
                    if (secIndex === -1) return;
                    const itemIndex = menuData[secIndex].children.findIndex(i => i.id === id);
                    if (itemIndex === -1) return;
                    const item = menuData[secIndex].children.splice(itemIndex, 1)[0];
                    menuData.push({
                        id: `SEC_${Date.now()}`,
                        data: {
                            name: item.data?.name || item.name,
                            type: 'section',
                            schema: 'menu_section'
                        },
                        children: [],
                        code: `SEC_${item.code || Date.now()}`,
                        group: 'Section',
                        description: 'Menu Section',
                        background: '#dceeff',
                        color: '#333333',
                        schema: [],
                        allow_form: 1,
                        customName: null
                    });
                    usedIds.delete(id);
                    menuData[secIndex].data.name = getSectionDisplayName(menuData[secIndex]);
                    if (menuData[secIndex].children.length === 0) {
                        usedIds.delete(menuData[secIndex].id);
                        menuData.splice(secIndex, 1);
                    }
                } else if (action === 'to-item' && type === 'section') {
                    const secIndex = menuData.findIndex(sec => sec.id === id);
                    if (secIndex === -1) return;
                    const section = menuData.splice(secIndex, 1)[0];
                    if (section.children.length === 0) {
                        if (menuData.length === 0) {
                            menuData.push({
                                id: `SEC_${Date.now()}`,
                                data: {
                                    name: 'New Section',
                                    type: 'section',
                                    schema: 'menu_section'
                                },
                                children: [],
                                code: `SEC_${Date.now()}`,
                                group: 'Section',
                                description: 'Menu Section',
                                background: '#dceeff',
                                color: '#333333',
                                schema: [],
                                allow_form: 1,
                                customName: null
                            });
                        }
                        menuData[0].children.push({
                            ...section,
                            data: {
                                ...section.data,
                                type: 'item',
                                schema: 'menu_item'
                            },
                            children: [],
                            customName: null
                        });
                        usedIds.add(section.id);
                        menuData[0].data.name = getSectionDisplayName(menuData[0]);
                    }
                }
                contextMenu.style.display = 'none';
                contextTarget = null;
                renderAvailable();
                renderMenu();
            });

            // Drag-and-drop for dragging items to sections or back to availableTree
            let draggedEl = null;
            document.addEventListener('dragstart', e => {
                if (e.target.dataset.id && !e.target.classList.contains('disabled') && !e.target.classList.contains(
                        'action-btn')) {
                    draggedEl = e.target;
                    e.dataTransfer.setData('text/plain', e.target.dataset.id);
                }
            });
            document.addEventListener('dragover', e => {
                e.preventDefault();
                const dropArea = e.target.closest('.drop-area');
                if (dropArea) {
                    dropArea.classList.add('drag-over');
                }
            });
            document.addEventListener('dragleave', e => {
                const dropArea = e.target.closest('.drop-area');
                if (dropArea) {
                    dropArea.classList.remove('drag-over');
                }
            });
            document.addEventListener('drop', e => {
                e.preventDefault();
                const dropArea = e.target.closest('.drop-area');
                if (!draggedEl || !dropArea) return;
                dropArea.classList.remove('drag-over');

                const draggedId = draggedEl.dataset.id;
                const draggedType = draggedEl.dataset.type;

                // Drag item to availableTree
                if (dropArea.id === 'availableTree' && draggedType === 'item') {
                    const secIndex = menuData.findIndex(sec => sec.children && sec.children.some(i => i.id ===
                        draggedId));
                    if (secIndex === -1) return;
                    const itemIndex = menuData[secIndex].children.findIndex(i => i.id === draggedId);
                    if (itemIndex === -1) return;
                    menuData[secIndex].children.splice(itemIndex, 1);
                    usedIds.delete(draggedId);
                    menuData[secIndex].data.name = getSectionDisplayName(menuData[secIndex]);
                    if (menuData[secIndex].children.length === 0) {
                        usedIds.delete(menuData[secIndex].id);
                        menuData.splice(secIndex, 1);
                    }
                }
                // Drag item to section drop-area
                else if (dropArea.dataset.sectionId && (draggedType === 'available' || draggedType === 'item')) {
                    const toSecIndex = menuData.findIndex(sec => sec.id === dropArea.dataset.sectionId);
                    if (toSecIndex === -1) return;
                    if (draggedType === 'available') {
                        const src = normalizedAvailable.flatMap(node => {
                            const children = Array.isArray(node.children) ? node.children : Object.values(
                                node.children || {});
                            return [node, ...children.flatMap(child => {
                                const grandChildren = Array.isArray(child.children) ? child
                                    .children : Object.values(child.children || {});
                                return [child, ...grandChildren.flatMap(grandchild => {
                                    const greatGrandChildren = Array.isArray(
                                            grandchild.children) ? grandchild
                                        .children : Object.values(grandchild
                                            .children || {});
                                    return [grandchild, ...greatGrandChildren];
                                })];
                            })];
                        }).find(node => node.id === draggedId);
                        if (!src || usedIds.has(src.id)) return;
                        menuData[toSecIndex].children.push({
                            id: src.id,
                            data: {
                                name: src.data?.name || src.name,
                                type: 'item',
                                schema: 'menu_item'
                            },
                            children: [],
                            code: src.code,
                            group: src.group,
                            description: src.description,
                            background: src.background,
                            color: src.color,
                            schema: src.schema || [],
                            allow_form: src.allow_form
                        });
                        usedIds.add(src.id);
                    } else if (draggedType === 'item') {
                        const fromSecIndex = menuData.findIndex(sec => sec.children && sec.children.some(i => i
                            .id === draggedId));
                        if (fromSecIndex === -1) return;
                        const itemIndex = menuData[fromSecIndex].children.findIndex(i => i.id === draggedId);
                        if (itemIndex === -1) return;
                        if (fromSecIndex !== toSecIndex) {
                            const [itemObj] = menuData[fromSecIndex].children.splice(itemIndex, 1);
                            menuData[toSecIndex].children.push(itemObj);
                            menuData[fromSecIndex].data.name = getSectionDisplayName(menuData[fromSecIndex]);
                            if (menuData[fromSecIndex].children.length === 0) {
                                usedIds.delete(menuData[fromSecIndex].id);
                                menuData.splice(fromSecIndex, 1);
                            }
                        }
                    }
                    menuData[toSecIndex].data.name = getSectionDisplayName(menuData[toSecIndex]);
                }

                draggedEl = null;
                renderAvailable();
                renderMenu();
            });

            // Ensure two-level structure
            menuData = menuData.map(section => ({
                ...section,
                data: {
                    ...section.data,
                    type: 'section',
                    schema: 'menu_section'
                },
                children: (section.children || []).map(item => ({
                    ...item,
                    data: {
                        ...item.data,
                        type: 'item',
                        schema: 'menu_item'
                    },
                    children: []
                }))
            }));

            renderAvailable();
            renderMenu();
            return menuData;
        }

        // Sample input
        const availableJSON = @json($data['scopes']);
        console.log(availableJSON);

        const previousJSON = [{
            "id": "SEC_001",
            "code": "SEC_DHA001",
            "name": "Main Section",
            "group": "Section",
            "description": "Menu Section",
            "background": "#dceeff",
            "color": "#333333",
            "data": [],
            "schema": [],
            "allow_form": 1,
            "children": [{
                "id": "SCPUrA1XB",
                "code": "DHA001",
                "name": "Digitalhub Academy",
                "group": "Company",
                "description": "Form data entered here can be accessed globally based on your configuration.",
                "background": "#00b4af",
                "color": "#ffffff",
                "data": [{
                        "type": "text",
                        "required": true,
                        "label": "Name",
                        "placeholder": "Enter Your Full Name",
                        "className": "form-control",
                        "name": "text-1752943148353-0",
                        "value": "R Kiran Kumar",
                        "subtype": "text"
                    },
                    {
                        "type": "date",
                        "required": true,
                        "label": "Date of Birth",
                        "className": "form-control",
                        "name": "date-1752943169748-0",
                        "subtype": "date"
                    }
                ],
                "schema": [{
                        "type": "text",
                        "required": true,
                        "label": "Name",
                        "placeholder": "Enter Your Full Name",
                        "className": "form-control",
                        "name": "text-1752943148353-0",
                        "value": "R Kiran Kumar",
                        "subtype": "text"
                    },
                    {
                        "type": "date",
                        "required": true,
                        "label": "Date of Birth",
                        "className": "form-control",
                        "name": "date-1752943169748-0",
                        "subtype": "date"
                    }
                ],
                "allow_form": 1,
                "children": []
            }]
        }];

        // Initialize
        scopeMenu(availableJSON, previousJSON);
    </script>
@endpush
@section('content')
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Settings</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/scope-management') }}">Scope Management</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Settings</a></li>
                    </ol>
                </nav>
            </div>
            <div></div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                <div class="live-time-container head-icons">
                    <span class="live-time-icon me-2"><i class="fa-thin fa-clock"></i></span>
                    <div class="live-time"></div>
                </div>
                <div class="ms-2 head-icons">
                    <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-chevrons-up"></i></a>
                </div>
            </div>
        </div>
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <ul class="nav nav-tabs nav-tabs-solid bg-transparent border-bottom-0 data-skl-action"
                            id="skeleton-settings" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="settings_general-tab" data-skl-action="b"
                                    data-bs-toggle="tab" href="#settings_general" role="tab"
                                    aria-controls="settings_general" aria-selected="true">General</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="settings-tab" data-skl-action="b" data-bs-toggle="tab"
                                    href="#settings_card_view" role="tab" aria-controls="settings_card_view"
                                    aria-selected="false">Navigation</a>
                            </li>
                        </ul>
                    </div>
                    <div class="tab-content mt-2 pt-2 border-top">
                        <div class="tab-pane fade show active" id="settings_general" role="tabpanel"
                            aria-labelledby="settings_general-tab">
                            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                                <div class="panel">
                                    <h3>Available Items (Tree View)</h3>
                                    <div id="availableTree" class="drop-area"></div>
                                </div>
                                <div class="panel">
                                    <h3>Menu Structure</h3>
                                    <button id="createSectionBtn" class="btn btn-primary">Add Section</button>
                                    <div id="menuTree"></div>
                                </div>
                            </div>
                            <div id="output">
                                <h4>Real-time JSON Output</h4>
                                <pre id="jsonOutput"></pre>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="settings_card_view" role="tabpanel" aria-labelledby="settings-tab">
                            OK
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
