<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employer;
use App\Models\Agent;
use App\Models\Importer;
use App\Models\Delegate;
use App\Models\Address;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TrashController extends Controller
{
    // Protect the entire controller, only users with 'view-trash' permission can access it.
    public function __construct()
    {
        $this->middleware('permission:view-trash');
    }

    /**
     * Display a listing of all soft-deleted items.
     */
    public function index()
    {
        $trashedEmployers = Employer::onlyTrashed()->get();
        $trashedAgents = Agent::onlyTrashed()->get();
        $trashedImporters = Importer::onlyTrashed()->get();
        $trashedDelegates = Delegate::onlyTrashed()->get();
        $trashedAddresses = Address::onlyTrashed()->get();

        return view('admin.trash', compact(
            'trashedEmployers',
            'trashedAgents',
            'trashedImporters',
            'trashedDelegates',
            'trashedAddresses'
        ));
    }

    /**
     * Restore a specific soft-deleted item.
     */
    public function restore($model, $id)
    {
        $modelClass = $this->getModelClass($model);
        $permission = 'restore-' . Str::plural(strtolower($model));

        // Check if the user has the specific permission (e.g., 'restore-employers')
        if (Gate::denies($permission)) {
            abort(403, 'You do not have permission to restore this item.');
        }

        $item = $modelClass::onlyTrashed()->findOrFail($id);
        $item->restore();

        return redirect()->route('admin.trash.index')->with('success', ucfirst($model) . ' restored successfully.');
    }

    /**
     * Permanently delete a specific soft-deleted item.
     */
    public function forceDelete($model, $id)
    {
        $modelClass = $this->getModelClass($model);
        $permission = 'force-delete-' . Str::plural(strtolower($model));

        // Check if the user has the specific permission (e.g., 'force-delete-employers')
        if (Gate::denies($permission)) {
            abort(403, 'You do not have permission to permanently delete this item.');
        }

        $item = $modelClass::onlyTrashed()->findOrFail($id);

        // Manually delete associated files before force deleting (mirroring Controller logic)
        try {
            if ($model === 'employer' && $item->document_company_registration) {
                Storage::disk('public')->delete($item->document_company_registration);
            }
            if ($model === 'employer' && $item->document_vat_registration) {
                Storage::disk('public')->delete($item->document_vat_registration);
            }
            if ($model === 'employer' && $item->document_map) {
                Storage::disk('public')->delete($item->document_map);
            }
            if ($model === 'delegate' && $item->delegatePhoto) {
                Storage::disk('public')->delete($item->delegatePhoto);
            }
        } catch (\Exception $e) {
            // Log error but continue with deletion
            \Log::error("Could not delete associated file for $model $id: " . $e->getMessage());
        }

        $item->forceDelete();

        return redirect()->route('admin.trash.index')->with('success', ucfirst($model) . ' permanently deleted.');
    }

    /**
     * Helper to get model class from string.
     */
    private function getModelClass($model)
    {
        $map = [
            'employer' => Employer::class,
            'agent' => Agent::class,
            'importer' => Importer::class,
            'delegate' => Delegate::class,
            'address' => Address::class,
        ];

        if (!array_key_exists($model, $map)) {
            abort(404, 'Model not found.');
        }

        return $map[$model];
    }
}