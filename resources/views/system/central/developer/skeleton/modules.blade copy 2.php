@extends('layouts.system-app')

@section('title', 'Skeleton Modules')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify@4.17.0/dist/tagify.css" />

@endpush

@section('content')
<div class="content">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
        <div class="my-auto mb-2">
            <h3 class="mb-1">Skeleton Modules</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ url('/developer') }}">Developer</a></li>
                    <li class="breadcrumb-item active">Skeleton Modules</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
            <div class="live-time-container head-icons">
                <span class="live-time-icon me-2"><i class="fa-thin fa-clock"></i></span>
                <div class="live-time"></div>
            </div>
            <div class="ms-2 head-icons">
                <a href="javascript:void(0);" data-bs-toggle="tooltip" title="Collapse" id="collapse-header"><i class="ti ti-chevrons-up"></i></a>
            </div>
        </div>
    </div>

    <div class="col-xl-12">
        <div class="card mt-4">
            <div class="card-header">
                <h4 class="card-title">Dynamic Tagify with User Profiles</h4>
            </div>
            <div class="card-body">
                <div>
                    <label for="roleSingleSelectTagify" class="form-label">Select Role (Single)</label>
                    <input id="roleSingleSelectTagify"
                           name="role-single"
                           data-select="single"
                           data-token="roles"
                           data-value="RLID001"
                           data-enforce-whitelist="true"
                           data-max-tags="1"
                           data-dropdown-enabled="0"
                           data-mode="select"
                           data-add-tag-on="enter"
                           data-placeholder="Choose a role (Single)"
                           data-a11y-focusable-tags="true"
                           data-auto-complete-enabled="true"
                           data-paste-as-tags="true"
                           data-edit-tags="2"
                           data-blacklist='["Guest","Support"]' />
                </div>

                <div>
                    <label for="roleMultiSelectTagify" class="form-label">Select Roles (Multiple)</label>
                    <input id="roleMultiSelectTagify"
                           name="role-multi"
                           data-select="multiple"
                           data-token="roles"
                           data-value="RLID002,RLID003"
                           data-enforce-whitelist="true"
                           data-max-tags="5"
                           data-dropdown-enabled="0"
                           data-placeholder="Choose roles (Multiple)"
                           data-a11y-focusable-tags="true"
                           data-auto-complete-enabled="true"
                           data-paste-as-tags="true"
                           data-edit-tags="2"
                           data-blacklist='["Admin"]' />
                </div>

                <div>
                    <label for="userSingleSelectTagify" class="form-label">Select User Profile (Single, Depends on Single Role)</label>
                    <input id="userSingleSelectTagify"
                           name="user-single"
                           data-select="single"
                           data-token="users"
                           data-target="role-single"
                           data-value="USR001"
                           data-custom-template="user-profile"
                           data-enforce-whitelist="true"
                           data-max-tags="1"
                           data-dropdown-enabled="0"
                           data-mode="select"
                           data-add-tag-on="enter"
                           data-placeholder="Choose a user profile (Single)"
                           data-a11y-focusable-tags="true"
                           data-auto-complete-enabled="true"
                           data-paste-as-tags="true"
                           data-edit-tags="2" />
                </div>

                <div>
                    <label for="userMultiSelectTagify" class="form-label">Select User Profiles (Multiple, Depends on Multi Role)</label>
                    <input id="userMultiSelectTagify"
                           name="user-multi"
                           data-select="multiple"
                           data-token="users"
                           data-target="role-multi"
                           data-value="USR003,USR004"
                           data-custom-template="user-profile"
                           data-enforce-whitelist="true"
                           data-max-tags="10"
                           data-dropdown-enabled="0"
                           data-placeholder="Choose user profiles (Multiple)"
                           data-a11y-focusable-tags="true"
                           data-auto-complete-enabled="true"
                           data-paste-as-tags="true"
                           data-edit-tags="2" />
                </div>

                <pre id="output"></pre>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify@4.17.0"></script>
<script>
    // Debounce utility to limit rapid event firing
    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Generic select() function for dynamic Tagify
    function select(input) {
        // Parse data attributes for Tagify settings
        const settings = {
            enforceWhitelist: input.dataset.enforceWhitelist === 'true',
            mode: input.dataset.mode || null,
            maxTags: parseInt(input.dataset.maxTags) || undefined,
            whitelist: [],
            dropdown: {
                enabled: parseInt(input.dataset.dropdownEnabled) || 0,
                maxItems: 20,
                closeOnSelect: input.dataset.select === 'single',
                position: 'all'
            },
            templates: {},
            delimiters: input.dataset.delimiters || ',',
            pattern: input.dataset.pattern || null,
            tagTextProp: input.dataset.tagTextProp || 'value',
            keepInvalidTags: input.dataset.keepInvalidTags === 'true',
            createInvalidTags: input.dataset.createInvalidTags === 'true',
            mixTagsAllowedAfter: input.dataset.mixTagsAllowedAfter || /,|\.|\:|\s/,
            mixTagsInterpolator: [input.dataset.mixTagsInterpolatorOpen || '[[', input.dataset.mixTagsInterpolatorClose || ']]'],
            backspace: input.dataset.backspace === 'true' || 'edit',
            skipInvalid: input.dataset.skipInvalid === 'true',
            pasteAsTags: input.dataset.pasteAsTags === 'true',
            editTags: parseInt(input.dataset.editTags) || 1,
            transformTag: input.dataset.transformTag ? new Function('tagData', input.dataset.transformTag) : function(tagData) {},
            autoComplete: {
                enabled: input.dataset.autoCompleteEnabled === 'true',
                rightKey: input.dataset.autoCompleteRightKey === 'true'
            },
            originalInputValueFormat: input.dataset.originalInputValueFormat ? new Function('valuesArr', input.dataset.originalInputValueFormat) : function(valuesArr) { return valuesArr.map(item => item.value).join(',') },
            a11y: {
                focusableTags: input.dataset.a11yFocusableTags === 'true'
            },
            addTagOnBlur: input.dataset.addTagOnBlur === 'true',
            duplicates: input.dataset.duplicates === 'true',
            trim: input.dataset.trim === 'true',
            readonly: input.dataset.readonly === 'true',
            disabled: input.dataset.disabled === 'true',
            userInput: input.dataset.userInput !== 'false',
            validate: input.dataset.validate ? new Function('tagData', input.dataset.validate) : null,
            classNames: JSON.parse(input.dataset.classNames || '{}'),
            blacklist: JSON.parse(input.dataset.blacklist || '[]'),
            addTagOn: input.dataset.addTagOn ? input.dataset.addTagOn.split(',') : ['blur', 'enter', 'comma']
        };

        const isSingle = input.dataset.select === 'single';
        const token = input.dataset.token;
        const targetName = input.dataset.target;
        const preselectedValues = input.dataset.value ? input.dataset.value.split(',') : [];
        const customTemplate = input.dataset.customTemplate;

        // Cache for fetched data
        const cache = new Map();

        // Custom templates for user profiles
        if (customTemplate === 'user-profile') {
            settings.templates.dropdownItem = function(item) {
                return `
                    <div class="tagify__dropdown__item">
                        <img class="avatar" src="${item.avatar || 'https://i.pravatar.cc/30'}" alt="${item.value}">
                        <div class="user-info">
                            <span class="user-name">${item.value}</span>
                            <span class="user-details">Role: ${item.role || 'N/A'} | Group: ${item.group || 'N/A'}</span>
                        </div>
                    </div>
                `;
            };
            settings.templates.tag = function(tagData) {
                return `
                    <tag title="${tagData.value}" contenteditable='false' spellcheck='false' class='tagify__tag ${tagData.class ? tagData.class : ''}'>
                        <x title='' class='tagify__tag__removeBtn' role='button' aria-label='remove tag'></x>
                        <div style="display: flex; align-items: center;">
                            <img class="avatar" src="${tagData.avatar || 'https://i.pravatar.cc/30'}" alt="${tagData.value}" style="margin-right: 5px;">
                            <span class='tagify__tag-text'>${tagData.value}</span>
                        </div>
                    </tag>
                `;
            };
            settings.templates.dropdownItemNoMatch = function(data) {
                return `<div class="tagify__dropdown__item">No match for: ${data.value}</div>`;
            };
        }

        // Initialize Tagify
        const tagify = new Tagify(input, settings);

        // Fetch initial data with caching
        const fetchData = (url) => {
            if (cache.has(url)) {
                return Promise.resolve(cache.get(url));
            }
            return fetch(url)
                .then(res => res.json())
                .then(data => {
                    cache.set(url, data);
                    return data;
                });
        };

        if (token) {
            tagify.loading(true);
            fetchData(`{{ url('/') }}/${token}`)
                .then(data => {
                    let whitelist;
                    if (token === 'roles') {
                        whitelist = Object.entries(data).map(([key, value]) => ({
                            id: key,
                            value: value
                        }));
                    } else {
                        whitelist = data.map(user => ({
                            id: user.id,
                            value: user.value,
                            avatar: user.avatar,
                            role: user.role,
                            group: user.group
                        }));
                    }
                    tagify.settings.whitelist = whitelist;

                    // Preselect values
                    if (preselectedValues.length > 0) {
                        const matches = whitelist.filter(item => preselectedValues.includes(item.id));
                        if (matches.length > 0) {
                            tagify.addTags(matches);
                        }
                    }

                    tagify.loading(false);
                    tagify.dropdown.show();
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    tagify.loading(false);
                });
        }

        // Debounced dependent update
        const updateDependent = debounce(() => {
            if (!targetName) return;
            const sourceInput = document.querySelector(`input[name=${targetName}]`);
            if (!sourceInput) return;

            const sourceTagify = sourceInput.tagify || select(sourceInput);
            const sourceValues = sourceTagify.value.map(v => v.id);
            tagify.removeAllTags();
            tagify.settings.whitelist = [];

            if (sourceValues.length === 0) {
                tagify.dropdown.hide();
                return;
            }

            const url = `{{ url('/') }}/${token}?role_id=${sourceValues.join(',')}`;
            tagify.loading(true);
            fetchData(url)
                .then(users => {
                    const userList = users.map(user => ({
                        id: user.id,
                        value: user.value,
                        avatar: user.avatar,
                        role: user.role,
                        group: user.group
                    }));
                    tagify.settings.whitelist = userList;

                    // Reapply preselection
                    if (preselectedValues.length > 0) {
                        const matches = userList.filter(item => preselectedValues.includes(item.id));
                        if (matches.length > 0) {
                            tagify.addTags(matches);
                        }
                    }

                    tagify.loading(false);
                    tagify.dropdown.show();
                })
                .catch(error => {
                    console.error('Dependent fetch error:', error);
                    tagify.loading(false);
                });
        }, 300);

        // Handle dependent fields
        if (targetName) {
            const sourceInput = document.querySelector(`input[name=${targetName}]`);
            if (sourceInput) {
                const sourceTagify = sourceInput.tagify || select(sourceInput);
                sourceTagify.on('add', updateDependent);
                sourceTagify.on('remove', updateDependent);
                sourceTagify.on('edit:updated', updateDependent);

                if (sourceTagify.value.length > 0) {
                    updateDependent();
                }
            }
        }

        // Bind Tagify events with validation for dropdown:select
        tagify.on('input', e => console.log('input', e.detail));
        tagify.on('add', e => { console.log('add', e.detail); updateOutput(); });
        tagify.on('remove', e => { console.log('remove', e.detail); updateOutput(); });
        tagify.on('invalid', e => console.log('invalid', e.detail));
        tagify.on('edit:start', e => console.log('edit:start', e.detail));
        tagify.on('edit:updated', e => console.log('edit:updated', e.detail));
        tagify.on('edit:input', e => console.log('edit:input', e.detail));
        tagify.on('click', e => console.log('click', e.detail));
        tagify.on('dblclick', e => console.log('dblclick', e.detail));
        tagify.on('focus', e => console.log('focus', e.detail));
        tagify.on('blur', e => console.log('blur', e.detail));
        tagify.on('dropdown:show', e => console.log('dropdown:show', e.detail));
        tagify.on('dropdown:hide', e => console.log('dropdown:hide', e.detail));
        tagify.on('dropdown:select', e => {
            if (e.detail.data) {
                console.log('dropdown:select', e.detail);
            }
        });
        tagify.on('dropdown:updated', e => console.log('dropdown:updated', e.detail));
        tagify.on('dropdown:noMatch', e => console.log('dropdown:noMatch', e.detail));
        tagify.on('dropdown:scroll', e => console.log('dropdown:scroll', e.detail));

        input.tagify = tagify;
        return tagify;
    }

    // Initialize all Tagify inputs
    document.querySelectorAll('input[data-select]').forEach(input => {
        select(input);
    });

    // Update output
    function updateOutput() {
        const output = document.getElementById('output');
        const data = {};
        document.querySelectorAll('input[data-select]').forEach(input => {
            const tagify = input.tagify;
            data[input.name] = tagify ? tagify.value : [];
        });
        output.textContent = JSON.stringify(data, null, 2);
    }
</script>
@endpush