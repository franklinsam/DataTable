<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LeadsDataTable extends Component
{
    use WithPagination;

    // Search and Filtering
    public $search = '';
    public $columnSearch = [
        'id' => '',
        'name' => '',
        'email' => '',
        'status' => '',
        'source' => '',
    ];
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    // Bulk Actions
    public $selectedLeads = [];
    public $selectAll = false;

    // Delete confirmation properties
    public $deleteId = null;
    public $confirmingDeletion = false;
    public $confirmingBulkDeletion = false;

    // Bulk Edit Properties
    public $bulkEditMode = false;
    public $editableLeads = [];
    public $editingLeadId = null;
    public $editMode = 'cell'; // 'cell' or 'row'
    
    // Track which rows are being edited
    public $editingRows = [];
    
    // Properties to store edited values
    public $editedValues = [];

    // Filtering
    public $filters = [
        'status' => '',
        'source' => '',
        'date_from' => '',
        'date_to' => '',
    ];

    // Column Visibility
    public $visibleColumns = [
        'id' => true,
        'name' => true,
        'email' => true,
        'status' => true,
        'source' => true,
        'created_at' => true
    ];

    // Default column visibility
    private $defaultColumns = [
        'id' => true,
        'name' => true,
        'email' => true,
        'status' => true,
        'source' => true,
        'created_at' => true
    ];

    // Query String for Persistent Filtering
    protected $queryString = [
        // No query string parameters
    ];

    // Optional: If you want to keep state without URL parameters
    protected $options = [
        'persist' => [
            'search',
            'sortField',
            'sortDirection',
            'perPage',
            'filters',
            'columnSearch',
            'visibleColumns'
        ]
    ];

    protected $listeners = ['toggleColumn' => 'toggleColumnVisibility'];

    // Reset Pagination on Search
    public function updatingSearch()
    {
        $this->resetPage();
    }

    // Reset Pagination on Column Search
    public function updatingColumnSearch()
    {
        $this->resetPage();
    }

    // Sorting Method
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    // Bulk Select Toggle
    public function toggleSelectAll()
    {
        $this->selectAll = !$this->selectAll;
        $this->selectedLeads = $this->selectAll 
            ? $this->getCurrentPageLeadIds() 
            : [];
    }

    // Get Current Page Lead IDs
    private function getCurrentPageLeadIds()
    {
        return $this->leads->pluck('id')->toArray();
    }

    // Bulk Delete
    public function deleteSelected()
    {
        if (empty($this->selectedLeads)) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => "No leads selected for deletion"
            ]);
            return;
        }

        try {
            $count = Lead::whereIn('id', $this->selectedLeads)->delete();
            
            // Dispatch a success notification
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "{$count} lead(s) deleted successfully"
            ]);

            // Clear selected leads after deletion
            $this->selectedLeads = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            // Dispatch an error notification
            $this->dispatch('notify', [
                'type' => 'danger',
                'message' => "Failed to delete selected leads: " . $e->getMessage()
            ]);
            
            // Log the error
            Log::error("Delete selected leads error: " . $e->getMessage());
        }
    }

    // Confirm Delete Method
    public function confirmDelete($leadId)
    {
        $this->confirmingDeletion = true;
        $this->deleteId = $leadId;

        // Dispatch a confirmation notification
        $this->dispatch('notify', [
            'type' => 'warning', 
            'message' => "Confirm deletion of lead #$leadId?"
        ]);
    }

    // Delete Confirmed Method
    public function deleteConfirmed()
    {
        if (!$this->deleteId) {
            $this->dispatch('notify', [
                'type' => 'warning', 
                'message' => "No lead selected for deletion"
            ]);
            return;
        }

        try {
            $lead = Lead::findOrFail($this->deleteId);
            $lead->delete();

            // Dispatch success notification
            $this->dispatch('notify', [
                'type' => 'success', 
                'message' => "Lead #{$this->deleteId} deleted successfully"
            ]);

            // Reset deletion state
            $this->confirmingDeletion = false;
            $this->deleteId = null;
        } catch (\Exception $e) {
            // Dispatch error notification
            $this->dispatch('notify', [
                'type' => 'danger', 
                'message' => "Failed to delete lead: " . $e->getMessage()
            ]);
            
            // Log the error
            Log::error("Delete lead error: " . $e->getMessage());
            
            // Reset deletion state
            $this->confirmingDeletion = false;
            $this->deleteId = null;
        }
    }

    // Cancel Delete Method
    public function cancelDelete()
    {
        $this->confirmingDeletion = false;
        $this->deleteId = null;

        // Dispatch cancellation notification
        $this->dispatch('notify', [
            'type' => 'info', 
            'message' => "Deletion canceled"
        ]);
    }

    // Bulk Delete Confirmation
    public function confirmBulkDelete()
    {
        if (empty($this->selectedLeads)) {
            $this->dispatch('notify', [
                'type' => 'warning', 
                'message' => "No leads selected for deletion"
            ]);
            return;
        }

        // Dispatch confirmation notification
        $this->dispatch('notify', [
            'type' => 'warning', 
            'message' => "Confirm deletion of " . count($this->selectedLeads) . " leads?"
        ]);
    }

    // Cancel Bulk Delete
    public function cancelBulkDelete()
    {
        $this->confirmingBulkDeletion = false;
    }

    // Individual Lead Deletion
    public function confirmDeleteSingle($leadId)
    {
        $this->deleteId = $leadId;
        $this->confirmingDeletion = true;
    }

    // Export CSV
    public function exportCSV()
    {
        try {
            $filename = 'leads_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            $callback = function() {
                $file = fopen('php://output', 'w');
                
                // Add headers
                fputcsv($file, ['ID', 'Name', 'Email', 'Status', 'Source', 'Created At']);
                
                // Get filtered leads without pagination
                $leads = Lead::query()
                    ->when($this->search, function($query) {
                        $query->where(function($q) {
                            $q->where('name', 'like', '%' . $this->search . '%')
                              ->orWhere('email', 'like', '%' . $this->search . '%')
                              ->orWhere('status', 'like', '%' . $this->search . '%')
                              ->orWhere('source', 'like', '%' . $this->search . '%');
                        });
                    })
                    ->when($this->filters['status'], function($query) {
                        $query->where('status', $this->filters['status']);
                    })
                    ->when($this->filters['source'], function($query) {
                        $query->where('source', $this->filters['source']);
                    })
                    ->when($this->filters['date_from'], function($query) {
                        $query->whereDate('created_at', '>=', $this->filters['date_from']);
                    })
                    ->when($this->filters['date_to'], function($query) {
                        $query->whereDate('created_at', '<=', $this->filters['date_to']);
                    })
                    ->orderBy($this->sortField, $this->sortDirection)
                    ->get();
                
                // Add data rows
                foreach ($leads as $lead) {
                    fputcsv($file, [
                        $lead->id,
                        $lead->name,
                        $lead->email,
                        $lead->status,
                        $lead->source,
                        $lead->created_at->format('Y-m-d H:i:s')
                    ]);
                }
                
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            // Log the error
            Log::error("CSV export error: " . $e->getMessage());
            
            // Notify the user
            $this->dispatch('notify', [
                'type' => 'danger',
                'message' => "Failed to export CSV: " . $e->getMessage()
            ]);
        }
    }

    // Method to toggle edit mode
    public function toggleEditMode($mode = 'cell')
    {
        $this->editMode = $mode;
        $this->bulkEditMode = true;
        $this->editableLeads = [];
    }

    // Method to handle double-click editing
    public function startCellEdit($leadId, $field)
    {
        // If not already in bulk edit mode, enter it
        if (!$this->bulkEditMode) {
            $this->bulkEditMode = true;
        }

        // If edit mode is row-based, edit entire row
        if ($this->editMode === 'row') {
            $this->editingLeadId = $leadId;
            $lead = Lead::findOrFail($leadId);
            $this->editableLeads[$leadId] = [
                'name' => $lead->name,
                'email' => $lead->email,
                'status' => $lead->status,
                'source' => $lead->source,
            ];
        } else {
            // Cell-based editing
            if (!isset($this->editableLeads[$leadId])) {
                $lead = Lead::findOrFail($leadId);
                $this->editableLeads[$leadId] = [
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'status' => $lead->status,
                    'source' => $lead->source,
                ];
            }

            // Mark the specific field as being edited
            $this->editableLeads[$leadId]['editing_field'] = $field;
        }
    }

    // Method to save bulk edits
    public function saveBulkEdits()
    {
        $this->validate([
            'editableLeads.*.name' => 'sometimes|string|max:255',
            'editableLeads.*.email' => 'sometimes|email|max:255',
            'editableLeads.*.status' => 'sometimes|in:pending,active,inactive',
            'editableLeads.*.source' => 'sometimes|string|max:255',
        ]);

        try {
            DB::transaction(function () {
                foreach ($this->editableLeads as $leadId => $leadData) {
                    $lead = Lead::findOrFail($leadId);
                    
                    // Remove the editing_field before updating
                    unset($leadData['editing_field']);
                    
                    $lead->update($leadData);
                }
            });

            $this->dispatch('notify', [
                'type' => 'success', 
                'message' => count($this->editableLeads) . " leads updated successfully"
            ]);

            // Reset bulk edit state
            $this->bulkEditMode = false;
            $this->editableLeads = [];
            $this->editingLeadId = null;
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'danger', 
                'message' => "Failed to save bulk edits: " . $e->getMessage()
            ]);
            
            // Log the error
            Log::error("Bulk edit error: " . $e->getMessage());
        }
    }

    // Method to cancel bulk edits
    public function cancelBulkEdit()
    {
        $this->bulkEditMode = false;
        $this->editableLeads = [];
        $this->editingLeadId = null;
    }

    // Property to track which leads are being edited
    public $editingLeads = [];

    // Method to start cell editing
    public function startCellEditSingle($leadId, $field)
    {
        // If not already in edit mode for this lead, add it
        if (!isset($this->editingLeads[$leadId])) {
            $lead = Lead::findOrFail($leadId);
            $this->editingLeads[$leadId] = [
                'original' => [
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'status' => $lead->status,
                    'source' => $lead->source,
                ],
                'current' => [
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'status' => $lead->status,
                    'source' => $lead->source,
                ],
                'editing_field' => $field
            ];
        } else {
            // If already in edit mode, update the editing field
            $this->editingLeads[$leadId]['editing_field'] = $field;
        }
    }

    // Method to save edited leads
    public function saveEditedLeadsSingle()
    {
        $this->validate([
            'editingLeads.*.current.name' => 'sometimes|string|max:255',
            'editingLeads.*.current.email' => 'sometimes|email|max:255',
            'editingLeads.*.current.status' => 'sometimes|in:pending,active,inactive',
            'editingLeads.*.current.source' => 'sometimes|string|max:255',
        ]);

        try {
            DB::transaction(function () {
                foreach ($this->editingLeads as $leadId => $leadData) {
                    $lead = Lead::findOrFail($leadId);
                    
                    // Update only the changed fields
                    $changedFields = array_diff_assoc(
                        $leadData['current'], 
                        $leadData['original']
                    );
                    
                    if (!empty($changedFields)) {
                        $lead->update($changedFields);
                    }
                }
            });

            $this->dispatch('notify', [
                'type' => 'success', 
                'message' => count($this->editingLeads) . " lead(s) updated successfully"
            ]);

            // Reset editing state
            $this->editingLeads = [];
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'danger', 
                'message' => "Failed to save edited leads: " . $e->getMessage()
            ]);
            
            // Log the error
            Log::error("Error saving edited leads: " . $e->getMessage());
        }
    }

    // Method to cancel cell editing
    public function cancelCellEditingSingle()
    {
        $this->editingLeads = [];
    }

    // Edit Confirmation Method
    public function edit($leadId)
    {
        try {
            $lead = Lead::findOrFail($leadId);
            
            // Set up editing state
            $this->editingLeadId = $leadId;
            
            // Dispatch notification about editing
            $this->dispatch('notify', [
                'type' => 'info', 
                'message' => "Editing lead #$leadId"
            ]);
        } catch (\Exception $e) {
            // Dispatch error notification
            $this->dispatch('notify', [
                'type' => 'danger', 
                'message' => "Failed to edit lead: " . $e->getMessage()
            ]);
            
            // Log the error
            Log::error("Edit lead error: " . $e->getMessage());
        }
    }

    // Update Lead Method
    public function updateLead()
    {
        if (!$this->editingLeadId) {
            $this->dispatch('notify', [
                'type' => 'warning', 
                'message' => "No lead selected for editing"
            ]);
            return;
        }

        try {
            $lead = Lead::findOrFail($this->editingLeadId);
            
            // Validate input
            $validatedData = $this->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'status' => 'required|in:pending,active,inactive',
                'source' => 'required|string|max:255'
            ]);

            // Update lead
            $lead->update($validatedData);

            // Dispatch success notification
            $this->dispatch('notify', [
                'type' => 'success', 
                'message' => "Lead #{$this->editingLeadId} updated successfully"
            ]);

            // Reset editing state
            $this->editingLeadId = null;
        } catch (\Exception $e) {
            // Dispatch error notification
            $this->dispatch('notify', [
                'type' => 'danger', 
                'message' => "Failed to update lead: " . $e->getMessage()
            ]);
            
            // Log the error
            Log::error("Update lead error: " . $e->getMessage());
        }
    }

    // Cancel Edit Method
    public function cancelEdit()
    {
        // Reset editing state
        $this->editingLeadId = null;
        
        // Dispatch notification
        $this->dispatch('notify', [
            'type' => 'info', 
            'message' => "Edit canceled"
        ]);
    }

    // Lifecycle hook to persist column visibility
    public function mount()
    {
        // Initialize column visibility from session or use default values
        $this->visibleColumns = session()->get('leads_table_column_visibility', $this->defaultColumns);
    }

    // Toggle column visibility
    public function toggleColumnVisibility($column)
    {
        if (array_key_exists($column, $this->visibleColumns)) {
            $this->visibleColumns[$column] = !$this->visibleColumns[$column];
            
            // Save to session
            session(['leads_table_column_visibility' => $this->visibleColumns]);
            
            // Dispatch event to notify JS
            $this->dispatch('columnVisibilityChanged', [
                'column' => $column,
                'visible' => $this->visibleColumns[$column]
            ]);
        }
    }

    // Reset column visibility to default
    public function resetColumnVisibility()
    {
        $this->visibleColumns = $this->defaultColumns;
        
        // Save to session
        session(['leads_table_column_visibility' => $this->visibleColumns]);
        
        // Dispatch event to notify JS
        $this->dispatch('columnVisibilityChanged', ['reset' => true]);
    }

    // Filtering Query
    private function getFilteredLeads()
    {
        $query = Lead::query();

        // Apply global search
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('status', 'like', '%' . $this->search . '%')
                  ->orWhere('source', 'like', '%' . $this->search . '%');
            });
        }

        // Apply column-specific search
        foreach (['id', 'name', 'email', 'status', 'source'] as $column) {
            if (!empty($this->columnSearch[$column])) {
                $query->where($column, 'like', '%' . $this->columnSearch[$column] . '%');
            }
        }

        // Apply status filter
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        // Apply source filter
        if (!empty($this->filters['source'])) {
            $query->where('source', $this->filters['source']);
        }

        // Apply date range filter
        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    // Method to toggle row editing
    public function toggleRowEdit($leadId)
    {
        if (in_array($leadId, $this->editingRows)) {
            // Remove from editing rows if already being edited
            $this->editingRows = array_diff($this->editingRows, [$leadId]);
            unset($this->editedValues[$leadId]);
        } else {
            // Add to editing rows and initialize edited values with current values
            $this->editingRows[] = $leadId;
            $lead = Lead::findOrFail($leadId);
            $this->editedValues[$leadId] = [
                'name' => $lead->name,
                'email' => $lead->email,
                'status' => $lead->status,
                'source' => $lead->source,
            ];
        }
    }
    
    // Method to save all edited rows
    public function saveAllEdits()
    {
        try {
            DB::transaction(function () {
                // Save row-based edits
                foreach ($this->editingRows as $leadId) {
                    if (isset($this->editedValues[$leadId])) {
                        $lead = Lead::findOrFail($leadId);
                        $lead->update($this->editedValues[$leadId]);
                    }
                }
                
                // Save cell-based edits
                foreach ($this->editingCells as $cellId => $cellData) {
                    $leadId = $cellData['leadId'];
                    $field = $cellData['field'];
                    
                    if (isset($this->editedValues[$leadId][$field])) {
                        $lead = Lead::findOrFail($leadId);
                        $lead->update([
                            $field => $this->editedValues[$leadId][$field]
                        ]);
                    }
                }
            });
            
            $editCount = count($this->editingRows) + count($this->editingCells);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "$editCount edit(s) saved successfully"
            ]);
            
            // Reset editing state
            $this->editingRows = [];
            $this->editingCells = [];
            $this->editedValues = [];
            
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'danger',
                'message' => "Failed to save edits: " . $e->getMessage()
            ]);
            
            Log::error("Error saving edits: " . $e->getMessage());
        }
    }
    
    // Method to cancel all edits
    public function cancelAllEdits()
    {
        $this->editingRows = [];
        $this->editingCells = [];
        $this->editedValues = [];
        
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => "Editing canceled"
        ]);
    }

    // Track which cells are being edited
    public $editingCells = [];
    
    // Method to toggle cell edit on double click
    public function toggleCellEdit($leadId, $field)
    {
        // If the row is already in editing mode, don't do anything
        if (in_array($leadId, $this->editingRows)) {
            return;
        }
        
        // Create a unique identifier for this cell
        $cellId = "{$leadId}_{$field}";
        
        // Check if this cell is already being edited
        if (isset($this->editingCells[$cellId])) {
            // Remove from editing cells
            unset($this->editingCells[$cellId]);
        } else {
            // Add to editing cells and initialize with current value
            $lead = Lead::findOrFail($leadId);
            
            // Only allow editing of editable fields
            if (in_array($field, ['name', 'email', 'status', 'source'])) {
                $this->editingCells[$cellId] = [
                    'leadId' => $leadId,
                    'field' => $field,
                    'value' => $lead->$field
                ];
                
                // Initialize edited values if not already set
                if (!isset($this->editedValues[$leadId])) {
                    $this->editedValues[$leadId] = [
                        'name' => $lead->name,
                        'email' => $lead->email,
                        'status' => $lead->status,
                        'source' => $lead->source,
                    ];
                }
            }
        }
    }
    
    // Method to save a single cell edit
    public function saveCellEdit($leadId, $field)
    {
        $cellId = $leadId . '_' . $field;
        
        try {
            $lead = Lead::findOrFail($leadId);
            
            // Only update if the field is valid
            if (in_array($field, ['name', 'email', 'status', 'source']) && 
                isset($this->editedValues[$leadId][$field])) {
                
                // Update just this field
                $lead->update([
                    $field => $this->editedValues[$leadId][$field]
                ]);
                
                // Remove from editing cells
                unset($this->editingCells[$cellId]);
                
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => "Updated " . ucfirst($field) . " for lead #$leadId"
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'danger',
                'message' => "Failed to save edit: " . $e->getMessage()
            ]);
            
            Log::error("Cell edit error: " . $e->getMessage());
        }
    }
    
    // Method to cancel a single cell edit
    public function cancelCellEdit($leadId, $field)
    {
        $cellId = $leadId . '_' . $field;
        
        // Reset the value to original
        if (isset($this->editingCells[$cellId])) {
            $lead = Lead::findOrFail($leadId);
            $this->editedValues[$leadId][$field] = $lead->$field;
            
            // Remove from editing cells
            unset($this->editingCells[$cellId]);
        }
    }

    // Render Method
    public function render()
    {
        $query = $this->getFilteredLeads();

        $this->leads = $query;

        return view('livewire.leads-data-table', [
            'leads' => $this->leads,
            'sources' => Lead::distinct('source')->pluck('source'),
        ]);
    }
}