// ============================================
// Skeleton Search - Fully Dynamic Implementation
// ============================================

// DOM Elements
const skeletonSearchInput = document.getElementById('skeleton-search-input');
const skeletonSearchDropdown = document.getElementById('skeleton-search-dropdown');
const skeletonSearchRecentSection = document.getElementById('skeleton-search-recent-section');
const skeletonSearchRecentSearches = document.getElementById('skeleton-search-recent-searches');
const skeletonSearchSuggestionsContainer = document.getElementById('skeleton-search-suggestions');

// State
let skeletonSearchResults = {};
let skeletonSearchTitles = {};
let skeletonSearchActiveIndex = -1;
let skeletonSearchActiveType = null;
let skeletonSearchRecent = JSON.parse(localStorage.getItem('skeletonSearchRecent')) || [];
let skeletonSearchInputTimeout = null;

// Initialize Recent Searches UI
skeletonSearchUpdateRecent();

// =============================
// UI Helpers
// =============================
function skeletonSearchShowDropdown() {
    if (!skeletonSearchDropdown) return;
    skeletonSearchDropdown.style.display = 'block';
    skeletonSearchDropdown.classList.add('skeleton-search-active');
}

function skeletonSearchHideDropdown() {
    if (!skeletonSearchDropdown) return;
    skeletonSearchDropdown.style.display = 'none';
    skeletonSearchDropdown.classList.remove('skeleton-search-active');
    skeletonSearchActiveIndex = -1;
    skeletonSearchActiveType = null;
}

function skeletonSearchShowLoading() {
    if (skeletonSearchSuggestionsContainer) {
        skeletonSearchSuggestionsContainer.innerHTML = `<div class="p-2 text-gray-500">Loading...</div>`;
    }
}

function skeletonSearchHandleEmptyResults(query) {
    skeletonSearchResults = {};
    skeletonSearchTitles = {};
    skeletonSearchSuggestionsContainer.innerHTML = `<div class="p-2 text-gray-500">No results found for "${query}"</div>`;
}

// =============================
// Render Suggestions Dynamically
// =============================
function skeletonSearchRenderSuggestions(query) {
    if (!skeletonSearchSuggestionsContainer) return;
    skeletonSearchSuggestionsContainer.innerHTML = '';

    const types = Object.keys(skeletonSearchResults).filter(type => (skeletonSearchResults[type] || []).length > 0);
    if (types.length === 0) {
        skeletonSearchHandleEmptyResults(query);
        return;
    }

    types.forEach(type => {
        const items = skeletonSearchResults[type] || [];
        if (items.length === 0) return;

        const section = document.createElement('div');
        section.className = 'skeleton-search-type-section';

        // Title
        const titleDiv = document.createElement('div');
        titleDiv.className = 'skeleton-search-type-title p-2 font-semibold';
        titleDiv.textContent = skeletonSearchTitles[type] || type;
        section.appendChild(titleDiv);

        // List
        const ul = document.createElement('ul');
        ul.className = 'skeleton-search-suggestions-list';

        items.forEach((item, index) => {
            const li = document.createElement('li');
            li.className = 'p-2 cursor-pointer hover:bg-gray-100';

            const highlightedText = skeletonSearchHighlightText(item.value, query);
            li.innerHTML = `<div>${highlightedText}</div>`;

            if (type === skeletonSearchActiveType && index === skeletonSearchActiveIndex) {
                li.classList.add('bg-gray-200');
            }

            li.addEventListener('click', () => {
                skeletonSearchAddRecent(item.value);
                if (skeletonSearchInput) skeletonSearchInput.value = item.value;
                skeletonSearchHideDropdown();
            });
            ul.appendChild(li);
        });

        section.appendChild(ul);
        skeletonSearchSuggestionsContainer.appendChild(section);
    });
}

// Highlight matched text
function skeletonSearchHighlightText(text, query) {
    if (!query) return text;
    const regex = new RegExp(`(${query})`, 'gi');
    return text.replace(regex, '<span class="font-bold text-blue-600">$1</span>');
}

// =============================
// Fetch Results (Dynamic Types)
// =============================
function skeletonSearchFetchResults(query, filterType = null) {
    skeletonSearchShowLoading();
    axios.get('/global/search/by/skeleton', {
        params: { query, type: filterType },
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(response => {
        if (response.data.success && response.data.results) {
            skeletonSearchResults = response.data.results || {};
            skeletonSearchTitles = response.data.titles || {};
            skeletonSearchActiveType = Object.keys(skeletonSearchResults).find(t => (skeletonSearchResults[t] || []).length > 0) || null;
            skeletonSearchActiveIndex = -1;
            skeletonSearchShowDropdown();
            skeletonSearchRenderSuggestions(query);
        } else {
            skeletonSearchHandleEmptyResults(query);
        }
    })
    .catch(() => skeletonSearchHandleEmptyResults(query));
}

// =============================
// Event Listeners
// =============================

// Input with debounce + min length check
if (skeletonSearchInput) {
    skeletonSearchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        clearTimeout(skeletonSearchInputTimeout);

        if (query.length < 3) {
            skeletonSearchHideDropdown();
            return;
        }

        skeletonSearchInputTimeout = setTimeout(() => {
            skeletonSearchFetchResults(query);
        }, 300);
    });

    skeletonSearchInput.addEventListener('keydown', (e) => {
        if (!skeletonSearchDropdown.classList.contains('skeleton-search-active')) return;

        const types = Object.keys(skeletonSearchResults).filter(t => (skeletonSearchResults[t] || []).length > 0);
        if (types.length === 0) return;

        const currentTypeIndex = types.indexOf(skeletonSearchActiveType);
        let items = skeletonSearchResults[skeletonSearchActiveType] || [];

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (skeletonSearchActiveIndex + 1 < items.length) {
                skeletonSearchActiveIndex++;
            } else {
                skeletonSearchActiveIndex = 0;
                skeletonSearchActiveType = types[(currentTypeIndex + 1) % types.length];
            }
            skeletonSearchRenderSuggestions(skeletonSearchInput.value);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (skeletonSearchActiveIndex > 0) {
                skeletonSearchActiveIndex--;
            } else {
                const prevTypeIndex = (currentTypeIndex - 1 + types.length) % types.length;
                skeletonSearchActiveType = types[prevTypeIndex];
                items = skeletonSearchResults[skeletonSearchActiveType];
                skeletonSearchActiveIndex = items.length - 1;
            }
            skeletonSearchRenderSuggestions(skeletonSearchInput.value);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (skeletonSearchActiveIndex >= 0 && items[skeletonSearchActiveIndex]) {
                skeletonSearchAddRecent(items[skeletonSearchActiveIndex].value);
                skeletonSearchInput.value = items[skeletonSearchActiveIndex].value;
                skeletonSearchHideDropdown();
            }
        } else if (e.key === 'Escape') {
            e.preventDefault();
            skeletonSearchHideDropdown();
        }
    });
}

// Hide dropdown on outside click
document.addEventListener('click', (e) => {
    if (!e.target.closest('.skeleton-search-container')) {
        skeletonSearchHideDropdown();
    }
});

// Quick Filters click
document.querySelectorAll('.skeleton-search-recent-pill[data-type]').forEach(btn => {
    btn.addEventListener('click', () => {
        const type = btn.getAttribute('data-type');
        skeletonSearchFetchResults('', type);
        skeletonSearchShowDropdown();
    });
});

// =============================
// Recent Searches
// =============================
function skeletonSearchAddRecent(term) {
    if (!skeletonSearchRecent.includes(term)) {
        skeletonSearchRecent.unshift(term);
        skeletonSearchRecent = skeletonSearchRecent.slice(0, 5);
        localStorage.setItem('skeletonSearchRecent', JSON.stringify(skeletonSearchRecent));
        skeletonSearchUpdateRecent();
    }
}

function skeletonSearchUpdateRecent() {
    if (!skeletonSearchRecentSearches || !skeletonSearchRecentSection) return;
    skeletonSearchRecentSearches.innerHTML = '';
    skeletonSearchRecent.forEach(term => {
        const span = document.createElement('span');
        span.className = 'skeleton-search-recent-pill';
        span.textContent = term;
        span.addEventListener('click', () => {
            skeletonSearchInput.value = term;
            skeletonSearchFetchResults(term);
        });
        skeletonSearchRecentSearches.appendChild(span);
    });
    skeletonSearchRecentSection.style.display = skeletonSearchRecent.length ? 'block' : 'none';
}

function skeletonSearchClearRecent() {
    skeletonSearchRecent = [];
    localStorage.removeItem('skeletonSearchRecent');
    skeletonSearchUpdateRecent();
}
