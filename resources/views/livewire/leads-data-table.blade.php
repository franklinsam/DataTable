<div class="container-fluid">
    <style>
        .sortable {
            cursor: pointer;
        }
        .sortable:hover {
            background-color: transparent;
        }
        tr.editing-row {
            background-color: rgba(255, 243, 205, 0.3) !important;
        }
        tr.editing-row td {
            vertical-align: middle;
        }
        .form-control-sm, .form-select-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .filter-dropdown {
            position: relative;
            margin-top: 12px;
            z-index: 10;
            border-top: 1px solid #dee2e6;
            padding-top: 8px;
        }
        th {
            position: relative;
            padding: 16px 12px !important;
            font-weight: 600;
        }
        th.sortable {
            cursor: pointer;
        }
        .editable-cell {
            cursor: pointer;
        }
        .editable-cell:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }
        .cell-actions {
            display: flex;
            margin-top: 5px;
        }
        .cell-actions button {
            padding: 0.1rem 0.3rem;
            font-size: 0.7rem;
            margin-right: 3px;
        }
        .table-header-custom {
            background-color: #6c757d !important;
            color: white;
        }
        .table > :not(caption) > * > * {
            padding: 12px 10px;
        }
        .card-body {
            padding-top: 1.5rem;
        }
        .table {
            margin-bottom: 0;
        }
        .table-responsive {
            border-radius: 4px;
            overflow: hidden;
        }
        .column-title {
            display: block;
            margin-bottom: 8px;
        }
        .sort-icon {
            margin-left: 5px;
        }
    </style>
    
    <div class="row">
        <div class="col-12">
            <div id="notification-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1100;">
                <!-- Notifications will be dynamically added here -->
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input 
                            wire:model.live.debounce.300ms="search" 
                            type="text" 
                            class="form-control" 
                            placeholder="Search leads..."
                        >
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="d-flex justify-content-end align-items-center">
                        <div class="btn-group me-3">
                            <div class="dropdown" id="columnVisibilityContainer">
                                <button 
                                    class="btn btn-outline-secondary dropdown-toggle" 
                                    type="button" 
                                    id="columnVisibilityDropdown" 
                                    data-bs-toggle="dropdown" 
                                    aria-expanded="false"
                                >
                                    <i class="fas fa-columns me-2"></i>Columns
                                </button>
                                <div class="dropdown-menu p-2" id="columnVisibilityMenu">
                                    @php
                                        $columnLabels = [
                                            'id' => 'ID',
                                            'name' => 'Name',
                                            'email' => 'Email',
                                            'status' => 'Status',
                                            'source' => 'Source',
                                            'created_at' => 'Created At'
                                        ];
                                    @endphp
                                    @foreach($columnLabels as $column => $label)
                                        <div class="form-check mb-2">
                                            <input 
                                                type="checkbox" 
                                                class="form-check-input column-visibility-checkbox" 
                                                id="{{ $column }}_visibility"
                                                data-column="{{ $column }}"
                                                {{ $visibleColumns[$column] ? 'checked' : '' }}
                                            >
                                            <label 
                                                class="form-check-label" 
                                                for="{{ $column }}_visibility"
                                            >
                                                {{ $label }}
                                            </label>
                                        </div>
                                    @endforeach
                                    <hr class="dropdown-divider">
                                    <button 
                                        class="btn btn-sm btn-secondary w-100" 
                                        id="resetColumnVisibility"
                                    >
                                        Reset to Default
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="btn-group me-2">
                            <div class="dropdown">
                                <button 
                                    class="btn btn-outline-secondary dropdown-toggle" 
                                    type="button" 
                                    id="filtersDropdown" 
                                    data-bs-toggle="dropdown" 
                                    aria-expanded="false"
                                >
                                    <i class="fas fa-filter me-2"></i>Filters
                                </button>
                                <div class="dropdown-menu p-3" style="width: 300px;">
                                    <div class="mb-2">
                                        <label class="form-label small">Status</label>
                                        <select wire:model.live="filters.status" class="form-select form-select-sm">
                                            <option value="">All Statuses</option>
                                            @foreach(['pending', 'active', 'inactive'] as $status)
                                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">Source</label>
                                        <select wire:model.live="filters.source" class="form-select form-select-sm">
                                            <option value="">All Sources</option>
                                            @foreach($sources as $source)
                                                <option value="{{ $source }}">{{ ucfirst($source) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-6 mb-2">
                                            <label class="form-label small">From Date</label>
                                            <input 
                                                type="date" 
                                                wire:model.live="filters.date_from" 
                                                class="form-control form-control-sm"
                                            >
                                        </div>
                                        <div class="col-6 mb-2">
                                            <label class="form-label small">To Date</label>
                                            <input 
                                                type="date" 
                                                wire:model.live="filters.date_to" 
                                                class="form-control form-control-sm"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="btn-group me-auto">
                            <button 
                                wire:click="exportCSV" 
                                class="btn btn-outline-success me-2"
                            >
                                <i class="fas fa-file-csv me-2"></i>Export CSV
                            </button>
                            @if(count($selectedLeads) > 0)
                                <button 
                                    wire:click="deleteSelected" 
                                    class="btn btn-outline-danger"
                                >
                                    <i class="fas fa-trash me-2"></i>Delete Selected
                                </button>
                            @endif
                        </div>
                        
                        @if(count($editingRows) > 0 || count($editingCells) > 0)
                            <div class="btn-group">
                                <button 
                                    wire:click="saveAllEdits" 
                                    class="btn btn-success me-2"
                                >
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                                <button 
                                    wire:click="cancelAllEdits" 
                                    class="btn btn-secondary"
                                >
                                    <i class="fas fa-times me-2"></i>Cancel
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-header-custom">
                        <tr>
                            <th>
                                <div class="form-check">
                                    <input 
                                        type="checkbox" 
                                        class="form-check-input" 
                                        wire:model.live="selectAll"
                                    >
                                </div>
                            </th>
                            @if($visibleColumns['id'])
                                <th wire:click="sortBy('id')" class="sortable position-relative">
                                    <span class="column-title">ID
                                    @if($sortField === 'id')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} sort-icon"></i>
                                    @else
                                        <i class="fas fa-sort sort-icon"></i>
                                    @endif
                                    </span>
                                    <div class="filter-dropdown">
                                        <input 
                                            type="text" 
                                            wire:model.live.debounce.300ms="columnSearch.id" 
                                            class="form-control form-control-sm" 
                                            placeholder="Search ID"
                                            @click.stop
                                        >
                                    </div>
                                </th>
                            @endif
                            @if($visibleColumns['name'])
                                <th wire:click="sortBy('name')" class="sortable position-relative">
                                    <span class="column-title">Name
                                    @if($sortField === 'name')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} sort-icon"></i>
                                    @else
                                        <i class="fas fa-sort sort-icon"></i>
                                    @endif
                                    </span>
                                    <div class="filter-dropdown">
                                        <input 
                                            type="text" 
                                            wire:model.live.debounce.300ms="columnSearch.name" 
                                            class="form-control form-control-sm" 
                                            placeholder="Search Name"
                                            @click.stop
                                        >
                                    </div>
                                </th>
                            @endif
                            @if($visibleColumns['email'])
                                <th wire:click="sortBy('email')" class="sortable position-relative">
                                    <span class="column-title">Email
                                    @if($sortField === 'email')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} sort-icon"></i>
                                    @else
                                        <i class="fas fa-sort sort-icon"></i>
                                    @endif
                                    </span>
                                    <div class="filter-dropdown">
                                        <input 
                                            type="text" 
                                            wire:model.live.debounce.300ms="columnSearch.email" 
                                            class="form-control form-control-sm" 
                                            placeholder="Search Email"
                                            @click.stop
                                        >
                                    </div>
                                </th>
                            @endif
                            @if($visibleColumns['status'])
                                <th wire:click="sortBy('status')" class="sortable position-relative">
                                    <span class="column-title">Status
                                    @if($sortField === 'status')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} sort-icon"></i>
                                    @else
                                        <i class="fas fa-sort sort-icon"></i>
                                    @endif
                                    </span>
                                    <div class="filter-dropdown">
                                        <select 
                                            wire:model.live="columnSearch.status" 
                                            class="form-control form-control-sm"
                                            @click.stop
                                        >
                                            <option value="">All Statuses</option>
                                            @foreach(['pending', 'active', 'inactive'] as $status)
                                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </th>
                            @endif
                            @if($visibleColumns['source'])
                                <th wire:click="sortBy('source')" class="sortable position-relative">
                                    <span class="column-title">Source
                                    @if($sortField === 'source')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} sort-icon"></i>
                                    @else
                                        <i class="fas fa-sort sort-icon"></i>
                                    @endif
                                    </span>
                                    <div class="filter-dropdown">
                                        <select 
                                            wire:model.live="columnSearch.source" 
                                            class="form-control form-control-sm"
                                            @click.stop
                                        >
                                            <option value="">All Sources</option>
                                            @foreach($sources as $source)
                                                <option value="{{ $source }}">{{ ucfirst($source) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </th>
                            @endif
                            @if($visibleColumns['created_at'])
                                <th wire:click="sortBy('created_at')" class="sortable position-relative">
                                    <span class="column-title">Created At
                                    @if($sortField === 'created_at')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} sort-icon"></i>
                                    @else
                                        <i class="fas fa-sort sort-icon"></i>
                                    @endif
                                    </span>
                                    <div class="filter-dropdown">
                                        <div class="d-flex justify-content-center">
                                            <small class="text-muted">Date format</small>
                                        </div>
                                    </div>
                                </th>
                            @endif
                            <th class="position-relative">
                                <span class="column-title">Actions</span>
                                <div class="filter-dropdown">
                                    <div class="d-flex justify-content-center">
                                        <small class="text-muted">Edit/Delete</small>
                                    </div>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($leads as $lead)
                        <tr class="{{ in_array($lead->id, $editingRows) ? 'editing-row' : '' }}">
                            <td>
                                <div class="form-check">
                                    <input 
                                        type="checkbox" 
                                        class="form-check-input" 
                                        wire:model.live="selectedLeads" 
                                        value="{{ $lead->id }}"
                                    >
                                </div>
                            </td>
                            @if($visibleColumns['id'])
                                <td>{{ $lead->id }}</td>
                            @endif
                            @if($visibleColumns['name'])
                                <td>
                                    @if(in_array($lead->id, $editingRows))
                                        <input 
                                            type="text" 
                                            class="form-control form-control-sm" 
                                            wire:model="editedValues.{{ $lead->id }}.name" 
                                            value="{{ $lead->name }}"
                                        >
                                    @elseif(isset($editingCells[$lead->id . '_name']))
                                        <div>
                                            <input 
                                                type="text" 
                                                class="form-control form-control-sm" 
                                                wire:model="editedValues.{{ $lead->id }}.name" 
                                                value="{{ $lead->name }}"
                                                wire:keydown.enter="saveCellEdit({{ $lead->id }}, 'name')"
                                                wire:keydown.escape="cancelCellEdit({{ $lead->id }}, 'name')"
                                                autofocus
                                            >
                                            <div class="cell-actions">
                                                <button 
                                                    class="btn btn-sm btn-success" 
                                                    wire:click="saveCellEdit({{ $lead->id }}, 'name')"
                                                >
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button 
                                                    class="btn btn-sm btn-secondary" 
                                                    wire:click="cancelCellEdit({{ $lead->id }}, 'name')"
                                                >
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <div 
                                            class="editable-cell" 
                                            wire:dblclick="toggleCellEdit({{ $lead->id }}, 'name')"
                                        >
                                            {{ $lead->name }}
                                        </div>
                                    @endif
                                </td>
                            @endif
                            @if($visibleColumns['email'])
                                <td>
                                    @if(in_array($lead->id, $editingRows))
                                        <input 
                                            type="email" 
                                            class="form-control form-control-sm" 
                                            wire:model="editedValues.{{ $lead->id }}.email" 
                                            value="{{ $lead->email }}"
                                        >
                                    @elseif(isset($editingCells[$lead->id . '_email']))
                                        <div>
                                            <input 
                                                type="email" 
                                                class="form-control form-control-sm" 
                                                wire:model="editedValues.{{ $lead->id }}.email" 
                                                value="{{ $lead->email }}"
                                                wire:keydown.enter="saveCellEdit({{ $lead->id }}, 'email')"
                                                wire:keydown.escape="cancelCellEdit({{ $lead->id }}, 'email')"
                                                autofocus
                                            >
                                            <div class="cell-actions">
                                                <button 
                                                    class="btn btn-sm btn-success" 
                                                    wire:click="saveCellEdit({{ $lead->id }}, 'email')"
                                                >
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button 
                                                    class="btn btn-sm btn-secondary" 
                                                    wire:click="cancelCellEdit({{ $lead->id }}, 'email')"
                                                >
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <div 
                                            class="editable-cell" 
                                            wire:dblclick="toggleCellEdit({{ $lead->id }}, 'email')"
                                        >
                                            {{ $lead->email }}
                                        </div>
                                    @endif
                                </td>
                            @endif
                            @if($visibleColumns['status'])
                                <td>
                                    @if(in_array($lead->id, $editingRows))
                                        <select 
                                            class="form-select form-select-sm" 
                                            wire:model="editedValues.{{ $lead->id }}.status"
                                        >
                                            @foreach(['pending', 'active', 'inactive'] as $status)
                                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                            @endforeach
                                        </select>
                                    @elseif(isset($editingCells[$lead->id . '_status']))
                                        <div>
                                            <select 
                                                class="form-select form-select-sm" 
                                                wire:model="editedValues.{{ $lead->id }}.status"
                                                wire:keydown.enter="saveCellEdit({{ $lead->id }}, 'status')"
                                                wire:keydown.escape="cancelCellEdit({{ $lead->id }}, 'status')"
                                                autofocus
                                            >
                                                @foreach(['pending', 'active', 'inactive'] as $status)
                                                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                            <div class="cell-actions">
                                                <button 
                                                    class="btn btn-sm btn-success" 
                                                    wire:click="saveCellEdit({{ $lead->id }}, 'status')"
                                                >
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button 
                                                    class="btn btn-sm btn-secondary" 
                                                    wire:click="cancelCellEdit({{ $lead->id }}, 'status')"
                                                >
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <div 
                                            class="editable-cell" 
                                            wire:dblclick="toggleCellEdit({{ $lead->id }}, 'status')"
                                        >
                                            <span class="badge bg-{{ $lead->status === 'active' ? 'success' : ($lead->status === 'pending' ? 'warning' : 'secondary') }}">
                                                {{ ucfirst($lead->status) }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                            @endif
                            @if($visibleColumns['source'])
                                <td>
                                    @if(in_array($lead->id, $editingRows))
                                        <input 
                                            type="text" 
                                            class="form-control form-control-sm" 
                                            wire:model="editedValues.{{ $lead->id }}.source" 
                                            value="{{ $lead->source }}"
                                        >
                                    @elseif(isset($editingCells[$lead->id . '_source']))
                                        <div>
                                            <input 
                                                type="text" 
                                                class="form-control form-control-sm" 
                                                wire:model="editedValues.{{ $lead->id }}.source" 
                                                value="{{ $lead->source }}"
                                                wire:keydown.enter="saveCellEdit({{ $lead->id }}, 'source')"
                                                wire:keydown.escape="cancelCellEdit({{ $lead->id }}, 'source')"
                                                autofocus
                                            >
                                            <div class="cell-actions">
                                                <button 
                                                    class="btn btn-sm btn-success" 
                                                    wire:click="saveCellEdit({{ $lead->id }}, 'source')"
                                                >
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button 
                                                    class="btn btn-sm btn-secondary" 
                                                    wire:click="cancelCellEdit({{ $lead->id }}, 'source')"
                                                >
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <div 
                                            class="editable-cell" 
                                            wire:dblclick="toggleCellEdit({{ $lead->id }}, 'source')"
                                        >
                                            {{ $lead->source }}
                                        </div>
                                    @endif
                                </td>
                            @endif
                            @if($visibleColumns['created_at'])
                                <td>{{ $lead->created_at->format('Y-m-d H:i') }}</td>
                            @endif
                            <td>
                                <div class="btn-group">
                                    <button 
                                        wire:click="toggleRowEdit({{ $lead->id }})" 
                                        class="btn btn-sm {{ in_array($lead->id, $editingRows) ? 'btn-warning' : 'btn-primary' }}"
                                    >
                                        <i class="fas {{ in_array($lead->id, $editingRows) ? 'fa-times' : 'fa-edit' }}"></i>
                                    </button>
                                    <button 
                                        wire:click="confirmDeleteSingle({{ $lead->id }})" 
                                        class="btn btn-sm btn-danger ms-1"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <select wire:model.live="perPage" class="form-control w-auto d-inline-block">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="ml-2">records per page</span>
                </div>
                <div>
                    {{ $leads->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    @if($confirmingDeletion)
        <div class="modal" tabindex="-1" style="display: block;" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Deletion</h5>
                        <button 
                            type="button" 
                            class="btn-close" 
                            wire:click="cancelDelete"
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete lead #{{ $deleteId }}?</p>
                        <p class="text-danger">
                            <strong>Warning:</strong> This action cannot be undone.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button 
                            type="button" 
                            class="btn btn-secondary" 
                            wire:click="cancelDelete"
                        >
                            Cancel
                        </button>
                        <button 
                            type="button" 
                            class="btn btn-danger" 
                            wire:click="deleteConfirmed"
                        >
                            Confirm Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Bulk Delete Confirmation Modal -->
    @if($confirmingBulkDeletion)
        <div class="modal" tabindex="-1" style="display: block;" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Bulk Deletion</h5>
                        <button 
                            type="button" 
                            class="btn-close" 
                            wire:click="cancelBulkDelete"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete {{ count($selectedLeads) }} selected leads?</p>
                    </div>
                    <div class="modal-footer">
                        <button 
                            type="button" 
                            class="btn btn-secondary" 
                            wire:click="cancelBulkDelete"
                        >Cancel</button>
                        <button 
                            type="button" 
                            class="btn btn-danger" 
                            wire:click="bulkDeleteConfirmed"
                        >Delete</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get DOM elements
            const columnVisibilityContainer = document.getElementById('columnVisibilityContainer');
            const columnVisibilityDropdown = document.getElementById('columnVisibilityDropdown');
            const columnVisibilityMenu = document.getElementById('columnVisibilityMenu');
            const resetButton = document.getElementById('resetColumnVisibility');
            const checkboxes = document.querySelectorAll('.column-visibility-checkbox');
            
            // Initialize Bootstrap dropdown manually
            const dropdownInstance = new bootstrap.Dropdown(columnVisibilityDropdown);
            
            // Prevent dropdown from closing when clicking inside
            columnVisibilityMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            // Handle checkbox changes
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const column = this.dataset.column;
                    const isChecked = this.checked;
                    
                    // Call Livewire method
                    @this.call('toggleColumnVisibility', column);
                });
            });
            
            // Handle reset button
            resetButton.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Call Livewire method
                @this.call('resetColumnVisibility');
                
                // Update checkbox states after reset
                setTimeout(() => {
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = true;
                    });
                }, 100);
            });
            
            // Listen for Livewire events
            Livewire.on('columnVisibilityChanged', () => {
                // Keep dropdown open
                dropdownInstance.show();
            });
            
            // Update checkboxes when Livewire updates
            Livewire.hook('message.processed', (message, component) => {
                if (component.fingerprint.name === 'leads-data-table') {
                    const visibleColumns = component.serverMemo.data.visibleColumns;
                    
                    // Update checkbox states
                    checkboxes.forEach(checkbox => {
                        const column = checkbox.dataset.column;
                        if (visibleColumns && visibleColumns[column] !== undefined) {
                            checkbox.checked = visibleColumns[column];
                        }
                    });
                    
                    // Focus on any newly opened cell editors
                    const cellInputs = document.querySelectorAll('.editable-cell input, .editable-cell select');
                    if (cellInputs.length > 0) {
                        setTimeout(() => {
                            cellInputs[0].focus();
                            if (cellInputs[0].tagName === 'INPUT') {
                                cellInputs[0].select();
                            }
                        }, 100);
                    }
                }
            });
            
            // Add keyboard shortcuts for cell editing
            document.addEventListener('keydown', function(e) {
                // If Escape key is pressed while editing a cell
                if (e.key === 'Escape') {
                    const activeCell = document.querySelector('.editable-cell input:focus, .editable-cell select:focus');
                    if (activeCell) {
                        const cancelButton = activeCell.closest('div').querySelector('.btn-secondary');
                        if (cancelButton) {
                            cancelButton.click();
                        }
                    }
                }
                
                // If Enter key is pressed while editing a cell
                if (e.key === 'Enter') {
                    const activeCell = document.querySelector('.editable-cell input:focus, .editable-cell select:focus');
                    if (activeCell) {
                        const saveButton = activeCell.closest('div').querySelector('.btn-success');
                        if (saveButton) {
                            saveButton.click();
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</div>