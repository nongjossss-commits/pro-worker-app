<?php

namespace App\Http\Controllers\Labor;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\CompanyDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * "เอกสารบริษัท" — any Labor-access user (with a team assigned, same gate
 * as contract issuance) can browse/download; upload/remove is restricted
 * to Super Admin at the route level (see routes/labor.php), matching the
 * tier charge-types/expense-categories/users use. Downloads are logged via
 * the existing ActivityLog (action='download') — deliberately NOT counted
 * as statistics, per the user's explicit distinction from the
 * contract-issuance running-number system.
 */
class LaborCompanyDocumentController extends Controller
{
    public function index()
    {
        $documents = CompanyDocument::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('labor.company_documents.index', compact('documents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'document_type' => 'nullable|string|max:255',
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        $path = $file->store('company_documents', 'public');

        CompanyDocument::create([
            'title' => $request->title,
            'document_type' => $request->document_type,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'uploaded_by' => Auth::id(),
            'is_active' => true,
        ]);

        return back()->with('success', __('Document uploaded successfully.'));
    }

    public function destroy(CompanyDocument $document)
    {
        $document->delete();

        return back()->with('success', __('Document removed.'));
    }

    public function download(CompanyDocument $document)
    {
        $user = Auth::user();
        abort_unless($user->labor_team_id, 403, __('You have not been assigned to a Pro Walker Labor team yet. Please contact a Super Admin.'));

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        ActivityLogHelper::logAction(
            'download',
            'ดาวน์โหลดเอกสารบริษัท: ' . $document->title,
            CompanyDocument::class,
            $document->id
        );

        return response()->download(
            Storage::disk('public')->path($document->file_path),
            $document->original_filename ?: $document->title
        );
    }
}
