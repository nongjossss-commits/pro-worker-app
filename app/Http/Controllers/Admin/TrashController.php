<?php //

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
     * Display a list of all soft-deleted items.
     */
    public function index(Request $request)
    {
        $trashedData = [];
        $models = $this->getPrunableModels();
        $searchTerm = $request->input('search');

        foreach ($models as $modelName => $modelClass) {
            $query = $modelClass::onlyTrashed();

            // --- Add Eager Loading ---
            if ($modelName === 'employees') {
                $query->with('employer');
            }

            // --- Add Search Logic ---
            if ($searchTerm) {
                // This requires a bit of model-specific logic.
                // We'll create a simple search for common fields.
                $query->where(function ($q) use ($searchTerm, $modelName) {
                    if ($modelName === 'employees') {
                        $q->where('employeeNameTh', 'like', "%{$searchTerm}%")
                          ->orWhere('employeeNameEn', 'like', "%{$searchTerm}%")
                          ->orWhere('employeePassport', 'like', "%{$searchTerm}%");
                    } elseif ($modelName === 'employers') {
                        $q->where('employerNameTh', 'like', "%{$searchTerm}%")
                          ->orWhere('employerNameEn', 'like', "%{$searchTerm}%");
                    } else {
                        // A generic fallback for other models
                        $q->where('name', 'like', "%{$searchTerm}%");
                    }
                });
            }

            $trashedData[$modelName] = $query->get();
        }

        return view('admin.trash.index', [
            'trashedData' => $trashedData,
            'search' => $searchTerm,
            'currentView' => $request->input('view', 'table') // Default to table view
        ]);
    }

    /**
     * Restore a specific soft-deleted item.
     */
    public function restore(Request $request, $model, $id)
    {
        $modelClass = $this->getModelClass($model);
        $permission = 'restore-' . Str::plural(strtolower($model)); // e.g., 'restore-employers'

        if (Gate::denies($permission)) {
            return response()->json(['error' => 'You do not have permission to restore this item.'], 403);
        }

        $item = $modelClass::onlyTrashed()->findOrFail($id);
        $item->restore();

        return response()->json(['success' => ucfirst($model) . ' restored successfully.']);
    }

    /**
     * Permanently delete a specific soft-deleted item.
     */
    public function forceDelete(Request $request, $model, $id)
    {
        $modelClass = $this->getModelClass($model);
        $permission = 'force-delete-' . Str::plural(strtolower($model)); // e.g., 'force-delete-employers'

        if (Gate::denies($permission)) {
            return response()->json(['error' => 'You do not have permission to permanently delete this item.'], 403);
        }

        $item = $modelClass::onlyTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['success' => ucfirst($model) . ' permanently deleted successfully.']);
    }

    /**
     * Helper function to get all models that use SoftDeletes (based on Seeder).
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
     * Helper function to safely get a model class from a string.
     */
    private function getModelClass($modelName): string
    {
        $map = $this->getPrunableModels();
        $modelNameLower = strtolower($modelName); // Use the plural form from the URL

        if (!array_key_exists($modelNameLower, $map)) {
            abort(404, 'Model not found.');
        }

        return $map[$modelNameLower];
    }
}
