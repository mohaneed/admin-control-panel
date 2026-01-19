/**
 * Admins Page - Admin Management
 * Controls ALL params structure and handles admin-specific logic
 */

document.addEventListener('DOMContentLoaded', () => {
    const headers = ["ID", "Email", "Created At"];
    const rows = ["id", "email", "createdAt"];

    const searchForm = document.getElementById('admins-search-form');
    const resetBtn = document.getElementById('btn-reset');
    const inputAdminId = document.getElementById('filter-admin-id');
    const inputEmail = document.getElementById('filter-email');
    const inputDateFrom = document.getElementById('filter-date-from');
    const inputDateTo = document.getElementById('filter-date-to');

    // ========================================================================
    // Custom Renderers - Define ONCE at the top
    // ========================================================================

    /**
     * Custom renderer for email column
     * Clickable to copy to clipboard
     */
    const emailRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';

        return `
            <span class="text-blue-600 hover:text-blue-800 cursor-pointer underline decoration-dotted email-copy" 
                  data-email="${value}"
                  title="Click to copy: ${value}">
                ${value}
            </span>
        `;
    };

    /**
     * Custom renderer for ID column
     */
    const idRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';
        return `<span class="font-mono text-sm text-gray-700">#${value}</span>`;
    };

    /**
     * Custom renderer for created_at column
     */
    const createdAtRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';
        return `<span class="text-sm text-gray-600">${value}</span>`;
    };

    // ========================================================================
    // Initialize
    // ========================================================================

    init();

    function init() {
        setupEventListeners();
        setupTableEventListeners();
        loadAdmins(); // ✅ Load data on page load
    }

    function setupTableFiltersAfterRender() {
        setTimeout(() => setupTableFilters(), 100);
    }

    // ========================================================================
    // Event Listeners
    // ========================================================================

    function setupEventListeners() {
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();

                // Validate date pair
                const dateFrom = inputDateFrom?.value;
                const dateTo = inputDateTo?.value;

                if ((dateFrom && !dateTo) || (!dateFrom && dateTo)) {
                    showAlert('w', 'Date filter must include both FROM and TO dates');
                    return;
                }

                loadAdmins();
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (inputAdminId) inputAdminId.value = '';
                if (inputEmail) inputEmail.value = '';
                if (inputDateFrom) inputDateFrom.value = '';
                if (inputDateTo) inputDateTo.value = '';
                loadAdmins();
            });
        }

        // Setup click handler for email copy
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('email-copy')) {
                const email = e.target.getAttribute('data-email');
                copyToClipboard(email, e.target);
            }
        });
    }

    function setupTableEventListeners() {
        document.addEventListener('tableAction', async (e) => {
            const { action, value, currentParams } = e.detail;
            console.log("📨 Table event:", action, value);

            let newParams = JSON.parse(JSON.stringify(currentParams));

            switch(action) {
                case 'pageChange':
                    newParams.page = value;
                    break;

                case 'perPageChange':
                    newParams.per_page = value;
                    newParams.page = 1;
                    break;
            }

            // Clean empty values
            if (newParams.search) {
                if (!newParams.search.global || !newParams.search.global.trim()) {
                    delete newParams.search.global;
                }

                if (newParams.search.columns) {
                    Object.keys(newParams.search.columns).forEach(key => {
                        if (!newParams.search.columns[key] || !newParams.search.columns[key].toString().trim()) {
                            delete newParams.search.columns[key];
                        }
                    });

                    if (Object.keys(newParams.search.columns).length === 0) {
                        delete newParams.search.columns;
                    }
                }

                if (Object.keys(newParams.search).length === 0) {
                    delete newParams.search;
                }
            }

            // Clean empty date filter
            if (newParams.date) {
                if (!newParams.date.from || !newParams.date.to) {
                    delete newParams.date;
                }
            }

            await loadAdminsWithParams(newParams);
        });
    }

    // ========================================================================
    // Table Filters (Custom UI)
    // ========================================================================

    function setupTableFilters() {
        const filterContainer = document.getElementById('table-custom-filters');
        if (!filterContainer) return;

        filterContainer.innerHTML = `
            <div class="flex gap-4 items-center flex-wrap">
                <div class="w-64">
                    <input id="admins-global-search" 
                        class="w-full border rounded-lg px-3 py-1 text-sm" 
                        placeholder="Search admins..." />
                </div>
            </div>
        `;

        const globalSearch = document.getElementById('admins-global-search');
        if (globalSearch) {
            globalSearch.addEventListener('keyup', (e) => {
                const value = e.target.value.trim();
                clearTimeout(globalSearch.searchTimeout);
                globalSearch.searchTimeout = setTimeout(() => {
                    handleGlobalSearch(value);
                }, 500);
            });
        }
    }

    function handleGlobalSearch(searchValue) {
        console.log("🔍 Global search:", searchValue);
        const params = buildParams(1, 10);

        if (searchValue && searchValue.trim()) {
            if (!params.search) {
                params.search = {};
            }
            params.search.global = searchValue.trim();
        }

        loadAdminsWithParams(params);
    }

    // ========================================================================
    // Params Builder
    // ========================================================================

    function buildParams(pageNumber = 1, perPage = 10) {
        const params = {
            page: pageNumber,
            per_page: perPage
        };

        const searchColumns = {};

        // Build column search
        if (inputAdminId && inputAdminId.value.trim()) {
            searchColumns.id = inputAdminId.value.trim();
        }
        if (inputEmail && inputEmail.value.trim()) {
            searchColumns.email = inputEmail.value.trim();
        }

        if (Object.keys(searchColumns).length > 0) {
            params.search = { columns: searchColumns };
        }

        // Build date filter (must be atomic pair)
        const dateFrom = inputDateFrom?.value;
        const dateTo = inputDateTo?.value;

        if (dateFrom && dateTo) {
            params.date = {
                from: dateFrom,
                to: dateTo
            };
        }

        return params;
    }

    // ========================================================================
    // Pagination Info Callback
    // ========================================================================

    /**
     * Custom pagination info - Admins page business logic
     * Returns what should be displayed based on filtered/total
     */
    function getAdminsPaginationInfo(pagination, params) {
        console.log("🎯 getAdminsPaginationInfo called with:", pagination);

        const { page = 1, per_page = 10, total = 0, filtered = total } = pagination;

        // Check if we're filtering
        const hasFilter = (params.search &&
                (params.search.global ||
                    (params.search.columns && Object.keys(params.search.columns).length > 0))) ||
            (params.date && params.date.from && params.date.to);

        const isFiltered = hasFilter && filtered !== total;

        console.log("🔍 Filter status - hasFilter:", hasFilter, "isFiltered:", isFiltered);

        // Calculate based on filtered when applicable
        const displayCount = isFiltered ? filtered : total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem = Math.min(page * per_page, displayCount);

        // Build info text
        let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
        if (isFiltered) {
            infoText += ` <span class="text-gray-500 text-xs">(filtered from ${total} total)</span>`;
        }

        console.log("📤 Returning:", { total: displayCount, info: infoText });

        return {
            total: displayCount,  // Use filtered count for pagination calculations
            info: infoText
        };
    }

    // ========================================================================
    // Load Admins
    // ========================================================================

    async function loadAdmins(pageNumber = 1) {
        const params = buildParams(pageNumber, 10);
        await loadAdminsWithParams(params);
    }

    async function loadAdminsWithParams(params) {
        console.log("🚀 Admins sending:", JSON.stringify(params, null, 2));

        if (typeof createTable === 'function') {
            try {
                const result = await createTable(
                    "admins/query",
                    params,
                    headers,
                    rows,
                    false, // No selection for admins
                    'id',
                    null, // No selection callback
                    {
                        id: idRenderer,
                        email: emailRenderer,
                        createdAt: createdAtRenderer
                    },
                    null, // No selectable IDs
                    getAdminsPaginationInfo
                );

                if (result && result.success) {
                    console.log("✅ Admins loaded:", result.data.length);
                    console.log("📊 Pagination:", result.pagination);
                    setupTableFiltersAfterRender();
                }
            } catch (error) {
                console.error("❌ Error:", error);
                showAlert('d', 'Failed to load admins');
            }
        } else {
            console.error("❌ createTable not found");
        }
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    /**
     * Copy text to clipboard and show notification
     */
    function copyToClipboard(text, element) {
        // Use modern Clipboard API
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                showCopyNotification(element);
            }).catch(err => {
                console.error('Failed to copy:', err);
                fallbackCopy(text, element);
            });
        } else {
            fallbackCopy(text, element);
        }
    }

    /**
     * Fallback copy method for older browsers
     */
    function fallbackCopy(text, element) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand('copy');
            showCopyNotification(element);
        } catch (err) {
            console.error('Fallback copy failed:', err);
        }

        document.body.removeChild(textarea);
    }

    /**
     * Show small "Copied!" notification near the element
     */
    function showCopyNotification(element) {
        // Create notification
        const notification = document.createElement('div');
        notification.textContent = 'Copied!';
        notification.className = 'absolute bg-green-600 text-white text-xs px-2 py-1 rounded shadow-lg z-50 animate-fade-in';
        notification.style.cssText = 'font-size: 10px; font-weight: 500; pointer-events: none;';

        // Position near the element
        const rect = element.getBoundingClientRect();
        notification.style.position = 'fixed';
        notification.style.left = (rect.left + rect.width / 2) + 'px';
        notification.style.top = (rect.top - 30) + 'px';
        notification.style.transform = 'translateX(-50%)';

        document.body.appendChild(notification);

        // Fade out and remove
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, 1000);
    }

    function showAlert(type, message) {
        if (typeof window.showAlert === 'function') {
            window.showAlert(type, message);
        } else {
            console.log(`[${type}] ${message}`);
        }
    }
});