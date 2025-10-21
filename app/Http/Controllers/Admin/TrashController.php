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

class TrashController extends Controller
{
    // Protect the entire controller with the permission we added
    public function __construct()
    {
        $this->middleware('permission:view-trash');
    }

    /**
     * Display a list of all soft-deleted items, with search and view state.
     */
    public function index(Request $request)
    {
        $trashedData = [];
        $models = $this->getPrunableModels();
        $searchTerm = $request->input('search');

        foreach ($models as $modelName => $modelClass) {
            $query = $modelClass::onlyTrashed();

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
                        case 'importers':
                        case 'delegates':
                            // Generic fallback for models with a 'name' column
                            $q->where('name', 'like', "%{$searchTerm}%");
                            break;
                    }
                });
            }

            $trashedData[$modelName] = $query->paginate(10, ['*'], $modelName . '_page')->withQueryString();
        }

        return view('admin.trash.index', [
            'trashedData' => $trashedData,
            'search' => $searchTerm,
            'currentView' => $request->input('view', 'table') // Pass the view state
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

        if (Gate::denies($permission)) {
            return response()->json(['error' => 'You do not have permission to restore this item.'], 403);
        }

        $item = $modelClass::onlyTrashed()->findOrFail($id);
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

        if (Gate::denies($permission)) {
            return response()->json(['error' => 'You do not have permission to permanently delete this item.'], 403);
        }

        $item = $modelClass::onlyTrashed()->findOrFail($id);
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
