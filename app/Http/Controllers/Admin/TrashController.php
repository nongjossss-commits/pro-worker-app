<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

// We must import all the models we plan to use
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Agent;
use App\Models\Importer;
use App\Models\Delegate;
use App\Models\Address;
use App\Models\JobTicket;
use App\Models\SystemConfig;

class TrashController extends Controller
{
    // Protect the entire controller with the permission we added
    public function __construct()
    {
        $this->middleware('permission:view-trash');
    }

    /**
     * Display the list of all soft-deleted items, with search and view state.
     */
    public function index(Request $request)
    {
        $trashedData = [];
        $models = $this->getPrunableModels();
        $searchTerm = $request->input('search');
        $retentionSettings = [];

        // Validate per_page, default to 25 if invalid or missing
        $perPage = $request->input('per_page', 25);
        if (!in_array($perPage, [25, 50, 100])) {
            $perPage = 25;
        }

        foreach ($models as $modelName => $modelClass) {
            $query = $modelClass::onlyTrashed();

            // V2.5.5 Fix: Ensure Admin sees all trashed items by ignoring global scopes (tenancy)
            if ($modelName === 'employees' || $modelName === 'employers') {
                $query->withoutGlobalScopes();
            }

            // --- Eager Loading ---
            // Eager load relationships to prevent N+1 query problems in the view.
            if ($modelName === 'employees') {
                $query->with('employer'); // For displaying employer name
            }

            // --- Search Logic ---
            if ($searchTerm) {
                // We apply model-specific search logic based on common fields.
                $query->where(function ($q) use ($searchTerm, $modelName) {
                    switch ($modelName) {
                        case 'employees':
                            $q->where('employeeNameTh', 'like', "%{$searchTerm}%")
                                ->orWhere('employeeNameEn', 'like', "%{$searchTerm}%")
                                ->orWhere('employeePassport', 'like', "%{$searchTerm}%");
                            break;
                        case 'employers':
                            $q->where('employerNameTh', 'like', "%{$searchTerm}%")
                                ->orWhere('employerNameEn', 'like', "%{$searchTerm}%");
                            break;
                        case 'agents':
                            $q->where('agentNameEn', 'like', "%{$searchTerm}%");
                            break;
                        case 'importers':
                            $q->where('importerNameTh', 'like', "%{$searchTerm}%")
                                ->orWhere('importerNameEn', 'like', "%{$searchTerm}%");
                            break;
                        case 'delegates':
                            $q->where('delegateNameTh', 'like', "%{$searchTerm}%")
                                ->orWhere('delegateNameEn', 'like', "%{$searchTerm}%");
                            break;
                        case 'tickets':
                            $q->where('subject', 'like', "%{$searchTerm}%");
                            break;
                        // No default case: if a model has no specific search, we don't apply a search filter.
                        // This prevents errors on models like 'Address' that don't have a standard searchable name field.
                    }
                });
            }

            $trashedData[$modelName] = $query->paginate($perPage, ['*'], $modelName . '_page')
                ->withQueryString()
                ->appends(['tab' => $modelName, 'per_page' => $perPage]);

            // Fetch retention settings for the view
            $retentionSettings[$modelName] = SystemConfig::where('key', "trash_retention_days_{$modelName}")->value('value');
        }

        return view('admin.trash.index', [
            'trashedData' => $trashedData,
            'search' => $searchTerm,
            'perPage' => $perPage,
            'currentView' => $request->input('view', 'table'), // Pass the view state
            'retentionSettings' => $retentionSettings,
        ]);
    }

    /**
     * Update the retention settings for trash.
     */
    public function updateSettings(Request $request)
    {
        if (Gate::denies('view-trash')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'retention_days' => 'required|array',
            'retention_days.*' => 'nullable|string', // Can be number string or 'forever' or null
        ]);

        foreach ($data['retention_days'] as $modelName => $value) {
            // Ensure the model name is valid
            if (!array_key_exists($modelName, $this->getPrunableModels())) {
                continue;
            }

            // If value is 'forever' or empty, save it as 'forever' or null.
            // If it is a number, save it.
            // We'll sanitize: if empty/null -> 'forever'.
            if (empty($value) || $value === 'forever') {
                $valueToSave = 'forever';
            } else {
                $valueToSave = (int)$value;
                if ($valueToSave <= 0) {
                    $valueToSave = 'forever';
                }
            }

            SystemConfig::updateOrCreate(
                ['key' => "trash_retention_days_{$modelName}"],
                ['value' => $valueToSave]
            );
        }

        return redirect()->route('admin.trash.index')->with('success', 'Trash retention settings updated successfully.');
    }

    /**
     * Exports the filtered soft-deleted items to a CSV file.
     */
    public function exportTrash(Request $request)
    {
        $modelName = $request->input('model');
        if (!$modelName) {
            abort(400, 'A model type must be specified for export.');
        }

        $models = $this->getPrunableModels();
        if (!isset($models[$modelName])) {
            abort(404, 'The specified model type is not valid for export.');
        }

        $modelClass = $models[$modelName];
        $searchTerm = $request->input('search');

        // 1. Fetch the specific model's data, applying the same filters as the index page
        $query = $modelClass::onlyTrashed();

        // V2.5.5 Fix: Ensure Admin sees all trashed items by ignoring global scopes (tenancy)
        if ($modelName === 'employees' || $modelName === 'employers') {
            $query->withoutGlobalScopes();
        }

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm, $modelName) {
                // This switch MUST be kept in sync with the index method's search logic
                switch ($modelName) {
                    case 'employees':
                        $q->where('employeeNameTh', 'like', "%{$searchTerm}%")
                            ->orWhere('employeeNameEn', 'like', "%{$searchTerm}%")
                            ->orWhere('employeePassport', 'like', "%{$searchTerm}%");
                        break;
                    case 'employers':
                        $q->where('employerNameTh', 'like', "%{$searchTerm}%")
                            ->orWhere('employerNameEn', 'like', "%{$searchTerm}%");
                        break;
                    case 'agents':
                        // This is the corrected logic from the index method
                        $q->where('agentNameEn', 'like', "%{$searchTerm}%");
                        break;
                    case 'importers':
                        $q->where('importerNameTh', 'like', "%{$searchTerm}%")
                            ->orWhere('importerNameEn', 'like', "%{$searchTerm}%");
                        break;
                    case 'delegates':
                        $q->where('delegateNameTh', 'like', "%{$searchTerm}%")
                            ->orWhere('delegateNameEn', 'like', "%{$searchTerm}%");
                        break;
                    case 'tickets':
                        $q->where('subject', 'like', "%{$searchTerm}%");
                        break;
                }
            });
        }

        $recordsToExport = $query->get();

        // 2. Generate and stream a CSV file
        return response()->streamDownload(function () use ($recordsToExport, $modelName) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM to ensure Excel opens the file with correct encoding
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Add header row
            fputcsv($handle, ['Type', 'Identifier (TH)', 'Identifier (EN)', 'Date Deleted']);

            foreach ($recordsToExport as $item) {
                $identifierTh = '';
                $identifierEn = '';

                // Determine the identifier based on model type
                switch ($modelName) {
                    case 'employees':
                        $identifierTh = $item->employeeNameTh;
                        $identifierEn = $item->employeeNameEn;
                        break;
                    case 'employers':
                        $identifierTh = $item->employerNameTh;
                        $identifierEn = $item->employerNameEn;
                        break;
                    case 'agents':
                        $identifierTh = 'N/A'; // agentNameTh does not exist
                        $identifierEn = $item->agentNameEn;
                        break;
                    case 'importers':
                        $identifierTh = $item->importerNameTh;
                        $identifierEn = $item->importerNameEn;
                        break;
                    case 'delegates':
                        $identifierTh = $item->delegateNameTh;
                        $identifierEn = $item->delegateNameEn;
                        break;
                    case 'addresses':
                        $identifierTh = "Address ID: " . $item->id;
                        $identifierEn = $item->addressLine1;
                        break;
                    case 'tickets':
                        $identifierTh = "Ticket ID: " . $item->id;
                        $identifierEn = $item->subject;
                        break;
                }

                fputcsv($handle, [
                    Str::ucfirst($modelName),
                    $identifierTh,
                    $identifierEn,
                    $item->deleted_at->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'trash_export_' . $modelName . '_' . date('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Restore a specific soft-deleted item.
     */
    public function restore(Request $request, $model, $id)
    {
        $modelClass = $this->getModelClass($model);
        // Correctly generate permission name, e.g., 'restore-employees'
        $permission = 'restore-' . Str::plural(strtolower(class_basename($modelClass)));

        // V2.5.4: Fallback for Tickets - Allow 'manage-tickets' permission
        $authorized = Gate::allows($permission);
        if ($model === 'tickets' && Gate::allows('manage-tickets')) {
            $authorized = true;
        }

        if (!$authorized) {
            return response()->json(['error' => 'You do not have permission to restore this item.'], 403);
        }

        // V2.5.5 Fix: Ensure we can find the item even if out of scope
        $query = $modelClass::onlyTrashed();
        if ($model === 'employees' || $model === 'employers') {
            $query->withoutGlobalScopes();
        }
        $item = $query->findOrFail($id);

        $item->restore();

        return response()->json(['success' => class_basename($modelClass) . ' restored successfully.']);
    }

    /**
     * Permanently delete a specific soft-deleted item.
     */
    public function forceDelete(Request $request, $model, $id)
    {
        $modelClass = $this->getModelClass($model);
        // Correctly generate permission name, e.g., 'force-delete-employees'
        $permission = 'force-delete-' . Str::plural(strtolower(class_basename($modelClass)));

        // V2.5.4: Fallback for Tickets - Allow 'manage-tickets' permission
        $authorized = Gate::allows($permission);
        if ($model === 'tickets' && Gate::allows('manage-tickets')) {
            $authorized = true;
        }

        if (!$authorized) {
            return response()->json(['error' => 'You do not have permission to permanently delete this item.'], 403);
        }

        // V2.5.5 Fix: Ensure we can find the item even if out of scope
        $query = $modelClass::onlyTrashed();
        if ($model === 'employees' || $model === 'employers') {
            $query->withoutGlobalScopes();
        }
        $item = $query->findOrFail($id);
        // Note: Logic for deleting associated files (like photos) should be in an observer or the model's forceDeleting event.
        $item->forceDelete();

        return response()->json(['success' => class_basename($modelClass) . ' permanently deleted successfully.']);
    }

    /**
     * Helper function to get all models that use SoftDeletes.
     */
    private function getPrunableModels(): array
    {
        return [
            'employees' => Employee::class,
            'employers' => Employer::class,
            'agents' => Agent::class,
            'importers' => Importer::class,
            'delegates' => Delegate::class,
            'addresses' => Address::class,
            'tickets'   => JobTicket::class,
        ];
    }

    /**
     * Helper function to safely get a model class from its kebab-case string name.
     */
    private function getModelClass($modelName): string
    {
        $map = $this->getPrunableModels();
        // The key in the map is the plural kebab-case name from the route
        $modelKey = strtolower($modelName);

        if (!array_key_exists($modelKey, $map)) {
            abort(404, 'Model type not found.');
        }

        return $map[$modelKey];
    }
}
