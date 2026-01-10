document.addEventListener('DOMContentLoaded', () => {
    // Defines
    const headers = [
        "user ID",
        "session ID",
        "is current",
        "Status",
        "expires at"
    ];
    const rows = [
        "admin_id",
        "session_id",
        "is_current",
        "status",
        "expires_at"
    ];

    const searchForm = document.getElementById('sessions-search-form');
    const resetBtn = document.getElementById('btn-reset');
    const selectedCount = document.getElementById('selected-count');
    
    
    // Inputs
    const inputSessionId = document.getElementById('filter-session-id');
    const inputAdminId = document.getElementById('filter-admin-id');
    const inputStatus = document.getElementById('filter-status');

    // Init
    init();

    function init() {
        loadSessions(); // Initial load
        setupEventListeners();
    }

    function setupEventListeners() {
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                loadSessions();
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                // Clear inputs
                if(inputSessionId) inputSessionId.value = '';
                if(inputAdminId) inputAdminId.value = '';
                if(inputStatus) inputStatus.value = '';
                
                loadSessions();
            });
        }
        
        const btnBulkRevoke = document.getElementById('btn-bulk-revoke');
        if (btnBulkRevoke) {
            btnBulkRevoke.addEventListener('click', revokeAllSessionsSelected);
        }
    }

    // Callback for selection change
    function updateSelectionCount(selectedItems) {
        const count = selectedItems.size;
        console.log("Selected count:", selectedItems);
        // Update count badge
        if(selectedCount) selectedCount.textContent = count;
        
        // Update button state (enabled if > 0)
        const btnBulkRevoke = document.getElementById('btn-bulk-revoke');
        if (btnBulkRevoke) {
            btnBulkRevoke.disabled = count === 0;
            if (count > 0) {
               btnBulkRevoke.classList.remove('cursor-not-allowed');
            } else {
               btnBulkRevoke.classList.add('cursor-not-allowed');
            }
        }
    }
    let selectedSessions = new Set();
    function revokeAllSessionsSelected() {
        const items = getSelectedItems();
        selectedSessions = new Set(items);
        items.forEach(sessionId => revokeSession(sessionId));
    }
    
    async function revokeSession(sessionId) {
        console.log("Revoking session:", sessionId);
        try {
            const response = await fetch('/api/sessions/' + sessionId, {
                method: 'DELETE',
                 headers: {
                    'Content-Type': 'application/json'
                }
            });

            console.log("Session:", response);
            if (response.ok) {
                // Remove from local tracking set
                if(selectedSessions instanceof Set) {
                    selectedSessions.delete(sessionId);
                }
                
                loadSessions(); // Reload table
                showAlert('Session revoked successfully.');
            } else {
                try {
                    const data = await response.json();
                    console.log("Data:", data);
                    showAlert('w', (data.error || 'Unknown error'));
                } catch (e) {
                    console.error(e);
                    showAlert('Failed to revoke session');
                }
            }
        } catch (e) {
            console.error(e);
            showAlert('d','Error revoking session');
        }
    }

    async function loadSessions() {
        // ... (existing filter logic)
        const filters = {};
        
        if (inputSessionId && inputSessionId.value.trim()) {
            filters.session_id = inputSessionId.value.trim();
        }
        if (inputAdminId && inputAdminId.value.trim()) {
            filters.admin_id = inputAdminId.value.trim();
        }
        if (inputStatus && inputStatus.value) {
            filters.status = inputStatus.value;
        }

        const params = {
            limit: 10,
            per_page: 10, 
            filters: filters
        };

        // Call global createTable from data_table.js
        if (typeof createTable === 'function') {
            await createTable("sessions/query", params, headers, rows, true, 'session_id', updateSelectionCount);
        } else {
            console.error("createTable function not found. Ensure data_table.js is loaded.");
        }
    }
});
