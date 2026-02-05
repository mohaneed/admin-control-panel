# 📊 Data Table Component - Complete Documentation

**Date:** February 5, 2026  
**Version:** 2.0 (Enhanced)  
**File:** `data_table.js`  
**Status:** ✅ Production Ready  

---

## 🎯 Overview

Generic, reusable data table component for displaying tabular data with pagination, filtering, and custom rendering.

### Key Features:
- ✅ **Two Modes**: `createTable()` (with API) or `TableComponent()` (static data)
- ✅ **Pagination**: Server-side pagination with custom controls
- ✅ **Selection**: Optional row selection with "Select All"
- ✅ **Custom Renderers**: Inject custom HTML per column
- ✅ **Export**: CSV, Excel, PDF export
- ✅ **Sorting**: Client-side sorting support
- ✅ **Event System**: Custom events for parent interaction
- ✅ **Error Handling**: Built-in error UI with retry

---

## 🔧 Two Ways to Use

### Method 1: `createTable()` - With API Fetching

**Use when:** You want the component to fetch data from API.

```javascript
async function loadUsers() {
    await createTable(
        'users/query',           // API endpoint
        { page: 1, per_page: 25 },  // Request params
        headers,                 // Column headers
        rowKeys,                 // Data property keys
        false,                   // Selection enabled?
        'id',                    // Primary key
        null,                    // Selection callback
        customRenderers,         // Custom column renderers
        null,                    // Selectable IDs whitelist
        getPaginationInfo        // Pagination info callback
    );
}
```

---

### Method 2: `TableComponent()` - Static Data

**Use when:** You already have the data (no API call needed).

```javascript
// You already fetched the data
const data = [...];  // Array of objects
const pagination = {page: 1, per_page: 25, total: 100};

TableComponent(
    data,                    // Array of data objects
    headers,                 // Column headers
    rowKeys,                 // Data property keys
    pagination,              // Pagination info
    "",                      // Actions HTML (deprecated)
    false,                   // Selection enabled?
    'id',                    // Primary key
    null,                    // Selection callback
    customRenderers,         // Custom column renderers
    null,                    // Selectable IDs whitelist
    getPaginationInfo        // Pagination info callback
);
```

---

## 📋 Parameters Reference

### Common Parameters:

| Parameter           | Type          | Required | Description                                             |
|---------------------|---------------|----------|---------------------------------------------------------|
| `headers`           | Array<string> | ✅ Yes    | Column labels (e.g., `["ID", "Name", "Status"]`)        |
| `rowKeys`           | Array<string> | ✅ Yes    | Object properties (e.g., `["id", "name", "is_active"]`) |
| `withSelection`     | Boolean       | No       | Enable checkbox selection (default: `false`)            |
| `primaryKey`        | String        | No       | Unique identifier key (default: `'id'`)                 |
| `onSelectionChange` | Function      | No       | Callback when selection changes: `(selectedIds) => {}`  |
| `customRenderers`   | Object        | No       | Custom rendering functions per column                   |
| `selectableIds`     | Set/Array     | No       | Whitelist of selectable IDs                             |
| `getPaginationInfo` | Function      | No       | Custom pagination info callback                         |

### `createTable()` Specific:

| Parameter | Type   | Required | Description                                        |
|-----------|--------|----------|----------------------------------------------------|
| `apiUrl`  | String | ✅ Yes    | API endpoint (e.g., `'users/query'`)               |
| `params`  | Object | ✅ Yes    | Request payload (e.g., `{page: 1, search: {...}}`) |

### `TableComponent()` Specific:

| Parameter        | Type   | Required | Description                                          |
|------------------|--------|----------|------------------------------------------------------|
| `data`           | Array  | ✅ Yes    | Array of data objects to display                     |
| `paginationData` | Object | ✅ Yes    | Pagination info: `{page, per_page, total, filtered}` |
| `actions`        | String | No       | Deprecated - use custom renderers instead            |

---

## 🎨 Custom Renderers

Transform column data into custom HTML.

### Basic Example:
```javascript
const customRenderers = {
    // Simple text transformation
    name: (value, row) => {
        return `<strong>${value}</strong>`;
    },
    
    // Status badge
    is_active: (value, row) => {
        const isActive = value === true || value === 1;
        const color = isActive ? 'green' : 'red';
        const text = isActive ? 'Active' : 'Inactive';
        return `<span class="text-${color}-600 font-bold">${text}</span>`;
    },
    
    // Action buttons
    actions: (value, row) => {
        return `
            <button onclick="editUser(${row.id})" class="btn-edit">
                Edit
            </button>
            <button onclick="deleteUser(${row.id})" class="btn-delete">
                Delete
            </button>
        `;
    }
};
```

### Using Reusable Components:
```javascript
// With AdminUIComponents library
const customRenderers = {
    is_active: (value, row) => {
        return AdminUIComponents.renderStatusBadge(value, {
            clickable: true,
            entityId: row.id
        });
    },
    
    code: (value, row) => {
        return AdminUIComponents.renderCodeBadge(value, {
            color: 'blue'
        });
    },
    
    actions: (value, row) => {
        return AdminUIComponents.buildActionButton({
            cssClass: 'edit-btn',
            icon: AdminUIComponents.SVGIcons.edit,
            text: 'Edit',
            color: 'blue',
            entityId: row.id
        });
    }
};
```

---

## 📄 Pagination

### Pagination Object Structure:
```javascript
{
    page: 1,           // Current page number
    per_page: 25,      // Items per page
    total: 100,        // Total items count
    filtered: 100      // Filtered items count (for search results)
}
```

### Custom Pagination Info:

By default, shows: `"1 to 25 of 100"`

Customize with `getPaginationInfo` callback:

```javascript
function getPaginationInfo(pagination, params) {
    const { page, per_page, total, filtered } = pagination;
    
    // Check if filtering
    const isFiltered = filtered !== total;
    const displayCount = isFiltered ? filtered : total;
    
    const startItem = (page - 1) * per_page + 1;
    const endItem = Math.min(page * per_page, displayCount);
    
    let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
    
    if (isFiltered) {
        infoText += ` <span class="text-gray-500">(filtered from ${total} total)</span>`;
    }
    
    return {
        total: displayCount,
        info: infoText
    };
}
```

**Important:** Must return object with `{total, info}` structure!

---

## 🔄 Page Changes & Global Functions

The table expects two global functions for pagination:

### 1. `window.changePage(page)`
```javascript
window.changePage = function(page) {
    console.log('Page changed to:', page);
    // Update your state
    currentPage = page;
    // Reload data
    loadData();
};
```

### 2. `window.changePerPage(perPage)`
```javascript
window.changePerPage = function(perPage) {
    console.log('Per page changed to:', perPage);
    // Update your state
    currentPerPage = perPage;
    currentPage = 1;  // Reset to first page
    // Reload data
    loadData();
};
```

**Important:** These functions MUST be defined globally for pagination to work!

---

## ✅ Selection System

### Enable Selection:
```javascript
TableComponent(
    data,
    headers,
    rowKeys,
    pagination,
    "",
    true,  // ← Enable selection
    'id',  // ← Primary key for selection
    onSelectionChange,  // ← Callback
    customRenderers
);
```

### Selection Callback:
```javascript
function onSelectionChange(selectedIds) {
    console.log('Selected IDs:', selectedIds);
    // selectedIds is a Set
    
    if (selectedIds.size === 0) {
        console.log('Nothing selected');
    } else {
        console.log(`${selectedIds.size} items selected`);
    }
}
```

### Whitelist Selectable IDs:
```javascript
// Only allow these IDs to be selected
const selectableIds = new Set([1, 2, 3, 5, 8]);

TableComponent(
    data,
    headers,
    rowKeys,
    pagination,
    "",
    true,
    'id',
    onSelectionChange,
    customRenderers,
    selectableIds  // ← Whitelist
);
```

---

## 📤 Export Features

Three export buttons are automatically rendered:

### 1. CSV Export
- Exports visible table data
- Filename: `table_data_YYYYMMDD_HHMMSS.csv`
- Uses commas as separator
- Handles special characters

### 2. Excel Export
- Exports as `.xls` format
- Preserves formatting
- Filename: `table_data_YYYYMMDD_HHMMSS.xls`

### 3. PDF Export
- Opens print-friendly view
- User can save as PDF from browser

**Note:** Exports only the visible data (current page), not all data!

---

## 🎯 Complete Usage Example

### Scenario: Languages Management Table

```javascript
// ========================================
// 1. Define Headers & Keys
// ========================================
const headers = ['ID', 'Name', 'Code', 'Direction', 'Status', 'Actions'];
const rowKeys = ['id', 'name', 'code', 'direction', 'is_active', 'actions'];

// ========================================
// 2. Custom Renderers
// ========================================
const customRenderers = {
    name: (value, row) => {
        const icon = row.icon || '🌍';
        return `<div class="flex items-center">
            <span class="mr-2">${icon}</span>
            <strong>${value}</strong>
        </div>`;
    },
    
    code: (value, row) => {
        return `<code class="bg-blue-100 px-2 py-1 rounded">${value}</code>`;
    },
    
    direction: (value, row) => {
        const arrow = value === 'rtl' ? '←' : '→';
        return `<span>${arrow} ${value.toUpperCase()}</span>`;
    },
    
    is_active: (value, row) => {
        return AdminUIComponents.renderStatusBadge(value, {
            clickable: true,
            entityId: row.id,
            dataAttribute: 'data-language-id'
        });
    },
    
    actions: (value, row) => {
        return AdminUIComponents.buildActionButton({
            cssClass: 'edit-btn',
            icon: AdminUIComponents.SVGIcons.edit,
            text: 'Edit',
            color: 'blue',
            entityId: row.id
        });
    }
};

// ========================================
// 3. Pagination Info Callback
// ========================================
function getPaginationInfo(pagination, params) {
    const { page, per_page, total, filtered } = pagination;
    
    const displayCount = filtered || total;
    const startItem = (page - 1) * per_page + 1;
    const endItem = Math.min(page * per_page, displayCount);
    
    let infoText = `${startItem} to ${endItem} of ${displayCount}`;
    
    if (filtered && filtered !== total) {
        infoText += ` (filtered from ${total} total)`;
    }
    
    return {
        total: displayCount,
        info: infoText
    };
}

// ========================================
// 4. Load Function (Method 1 - With API)
// ========================================
async function loadLanguages() {
    const params = {
        page: currentPage,
        per_page: currentPerPage,
        search: {
            columns: {
                is_active: "1"
            }
        }
    };
    
    await createTable(
        'languages/query',
        params,
        headers,
        rowKeys,
        false,  // No selection
        'id',
        null,
        customRenderers,
        null,
        getPaginationInfo
    );
}

// ========================================
// OR Method 2 - Static Data
// ========================================
async function loadLanguagesStatic() {
    // Fetch data yourself
    const result = await ApiHandler.call('languages/query', params, 'Query Languages');
    
    if (!result.success) {
        console.error('Failed to load');
        return;
    }
    
    const data = result.data.data || [];
    const pagination = result.data.pagination || {page: 1, per_page: 25, total: 0};
    
    // Render with TableComponent
    TableComponent(
        data,
        headers,
        rowKeys,
        pagination,
        "",
        false,
        'id',
        null,
        customRenderers,
        null,
        getPaginationInfo
    );
}

// ========================================
// 5. Global Pagination Functions
// ========================================
window.changePage = function(page) {
    currentPage = page;
    loadLanguages();
};

window.changePerPage = function(perPage) {
    currentPerPage = perPage;
    currentPage = 1;
    loadLanguages();
};

// ========================================
// 6. Initialize
// ========================================
loadLanguages();
```

---

## 🐛 Error Handling

### Automatic Error UI:

If `createTable()` API call fails, shows:

```
┌─────────────────────────────────────┐
│  ❌ Failed to Load Data             │
│                                     │
│  [Error message here]               │
│                                     │
│  [📄 Show Raw Response] (if any)    │
│                                     │
│  [🔄 Retry]                         │
└─────────────────────────────────────┘
```

### Custom Error Handling:

Use `TableComponent()` with your own error handling:

```javascript
const result = await ApiHandler.call('endpoint', params, 'Operation');

if (!result.success) {
    // Show custom error
    document.getElementById('table-container').innerHTML = `
        <div class="error-custom">
            <h3>Oops! ${result.error}</h3>
            <button onclick="retry()">Try Again</button>
        </div>
    `;
    return;
}

// Success - render table
TableComponent(result.data.data, ...);
```

---

## 🎨 Styling

### Container:
```html
<div id="table-container" class="w-full"></div>
```

### Required CSS Framework:
- **Tailwind CSS** - For utility classes
- Or provide equivalent CSS classes

### Key Classes Used:
- Layout: `flex`, `grid`, `w-full`, `p-4`, `gap-2`
- Colors: `bg-gray-100`, `text-blue-600`, `border-gray-300`
- States: `hover:bg-blue-50`, `focus:ring-2`
- Typography: `text-sm`, `font-medium`, `font-bold`

---

## ⚠️ Important Notes

### 1. Headers Must Be Strings
```javascript
// ❌ WRONG - Objects
const headers = [
    {key: 'id', label: 'ID'},
    {key: 'name', label: 'Name'}
];

// ✅ CORRECT - Strings
const headers = ['ID', 'Name'];
```

### 2. rowKeys Must Match Data Properties
```javascript
// If your data looks like:
const data = [
    {id: 1, full_name: 'John', status: 1}
];

// Then rowKeys must be:
const rowKeys = ['id', 'full_name', 'status'];
```

### 3. Pagination Must Have Correct Structure
```javascript
// ✅ CORRECT
const pagination = {
    page: 1,
    per_page: 25,
    total: 100,
    filtered: 100  // Optional
};

// ❌ WRONG - Missing fields
const pagination = {
    currentPage: 1,
    itemsPerPage: 25
};
```

### 4. Global Functions Are Required
```javascript
// These MUST be defined globally for pagination to work
window.changePage = function(page) { ... };
window.changePerPage = function(perPage) { ... };
```

---

## 🔍 Debugging

### Enable Debug Mode:
```javascript
// In console
localStorage.setItem('TABLE_DEBUG', 'true');

// Reload page - will log extra info
```

### Common Issues:

#### Issue 1: "Table not rendering"
**Solution:** Check console for errors, verify container exists:
```javascript
const container = document.getElementById('table-container');
if (!container) {
    console.error('Container not found!');
}
```

#### Issue 2: "Pagination not working"
**Solution:** Verify global functions exist:
```javascript
console.log(typeof window.changePage);  // Should be "function"
console.log(typeof window.changePerPage);  // Should be "function"
```

#### Issue 3: "Headers/rows mismatch"
**Solution:** Ensure arrays have same length:
```javascript
console.log(headers.length);  // e.g., 5
console.log(rowKeys.length);  // Must also be 5
```

#### Issue 4: "Custom renderer not working"
**Solution:** Check renderer function signature:
```javascript
// ✅ CORRECT
const renderers = {
    columnName: (value, row) => { return `<span>${value}</span>`; }
};

// ❌ WRONG - Missing parameters
const renderers = {
    columnName: () => { return 'text'; }
};
```

---

## 📊 Performance Tips

### 1. Use TableComponent for Frequent Updates
```javascript
// ❌ Slow - Fetches API every time
setInterval(() => {
    createTable(...);
}, 5000);

// ✅ Fast - Fetch once, update UI only
setInterval(async () => {
    const result = await ApiHandler.call(...);
    TableComponent(result.data.data, ...);
}, 5000);
```

### 2. Limit Custom Renderer Complexity
```javascript
// ❌ Slow - Complex DOM in every cell
const renderer = (value, row) => {
    return `<div class="complex">
        <img src="...loading from API..." />
        <script>doSomething();</script>
    </div>`;
};

// ✅ Fast - Simple HTML
const renderer = (value, row) => {
    return `<span class="simple">${value}</span>`;
};
```

---

## ✅ Testing Checklist

- [ ] Table renders with data
- [ ] Pagination controls work
- [ ] Page change updates table
- [ ] Per page change updates table
- [ ] Custom renderers display correctly
- [ ] Selection checkbox works (if enabled)
- [ ] Select all works (if enabled)
- [ ] Export CSV works
- [ ] Export Excel works
- [ ] Export PDF works
- [ ] Error UI shows on API failure
- [ ] Retry button works
- [ ] Empty state displays correctly
- [ ] Loading state shows during fetch

---

## 🔗 Related Documentation

- `API_HANDLER_DOCUMENTATION.md` - API call documentation
- `REUSABLE_COMPONENTS_GUIDE.md` - AdminUIComponents library
- `CONSOLE_LOGGING_GUIDE.md` - Debugging requests

---

**Data Table Component is production-ready and battle-tested! 📊**
