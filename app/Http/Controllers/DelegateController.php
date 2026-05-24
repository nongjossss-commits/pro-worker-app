<?php

namespace App\Http\Controllers;

use App\Models\Delegate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DelegateController extends Controller
{
    /**
     * Validation rules ของ Delegate — ใช้ทั้ง store และ update
     */
    private const DELEGATE_RULES = [
        'delegateNameTh'              => 'required|string|max:255',
        'delegateNameEn'              => 'nullable|string|max:255',
        'delegateId'                  => 'nullable|string|max:255',
        'delegateEmployeeId'          => 'nullable|string|max:255',
        'delegateIssueDate'           => 'nullable|date',
        'delegateExpiryDate'          => 'nullable|date',
        'delegatePhone'               => 'nullable|string|max:255',
        'delegateEmail'               => 'nullable|email|max:255',
        'outsource_password'          => 'nullable|string|max:255',
        'delegatePhoto'               => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        'delegate_doc_other_1'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        'delegate_doc_other_1_desc'   => 'nullable|string|max:255',
        'delegate_doc_other_2'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        'delegate_doc_other_2_desc'   => 'nullable|string|max:255',
        'delegate_doc_other_3'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        'delegate_doc_other_3_desc'   => 'nullable|string|max:255',
        'delegate_signature'          => 'nullable|image|max:5120',
    ];

    /** Authorization: ต้องมี permission ตามแต่ละ action */
    public function __construct()
    {
        $this->middleware('permission:view-delegates')->only(['index', 'show']);
        $this->middleware('permission:create-delegates')->only(['create', 'store']);
        $this->middleware('permission:edit-delegates')->only(['edit', 'update']);
        $this->middleware('permission:delete-delegates')->only(['destroy']);
    }

    public function index()
    {
        $delegates = Delegate::all();
        return view('delegates.index', compact('delegates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('delegates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate(self::DELEGATE_RULES);

        if ($request->hasFile('delegate_signature')) {
            $data['signature_path'] = $request->file('delegate_signature')->store('signatures/delegates', 'public');
        }

        if ($request->hasFile('delegatePhoto')) {
            $path = $request->file('delegatePhoto')->store('delegate_photos', 'public');
            $data['delegatePhoto'] = $path;
        }

        $docFields = ['delegate_doc_other_1', 'delegate_doc_other_2', 'delegate_doc_other_3'];
        foreach ($docFields as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store('delegate_documents', 'public');
            }
        }

        $delegate = Delegate::create($data);

        // Process temporary addresses from request
        if ($request->filled('registered_addresses')) {
            $addrs = json_decode($request->input('registered_addresses'), true);
            if (is_array($addrs)) {
                foreach ($addrs as $addr) {
                    $addr['type'] = 'registered';
                    $delegate->addresses()->create($addr);
                }
            }
        }
        if ($request->filled('workplace_addresses')) {
            $addrs = json_decode($request->input('workplace_addresses'), true);
            if (is_array($addrs)) {
                foreach ($addrs as $addr) {
                    $addr['type'] = 'workplace';
                    $delegate->addresses()->create($addr);
                }
            }
        }

        return redirect()->route('delegates.index')
                        ->with('success','Delegate created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Delegate $delegate)
    {
        return view('delegates.show',compact('delegate'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Delegate $delegate)
    {
        return view('delegates.edit',compact('delegate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Delegate $delegate)
    {
        $data = $request->validate(self::DELEGATE_RULES);

        if ($request->hasFile('delegate_signature')) {
            if ($delegate->signature_path) {
                Storage::disk('public')->delete($delegate->signature_path);
            }
            $data['signature_path'] = $request->file('delegate_signature')->store('signatures/delegates', 'public');
        }

        if ($request->hasFile('delegatePhoto')) {
            // Delete old photo
            if ($delegate->delegatePhoto) {
                Storage::disk('public')->delete($delegate->delegatePhoto);
            }
            $path = $request->file('delegatePhoto')->store('delegate_photos', 'public');
            $data['delegatePhoto'] = $path;
        }

        $docFields = ['delegate_doc_other_1', 'delegate_doc_other_2', 'delegate_doc_other_3'];
        foreach ($docFields as $field) {
            if ($request->hasFile($field)) {
                if ($delegate->{$field}) {
                    Storage::disk('public')->delete($delegate->{$field});
                }
                $data[$field] = $request->file($field)->store('delegate_documents', 'public');
            }
        }

        $delegate->update($data);

        return redirect()->route('delegates.index')
                        ->with('success','Delegate updated successfully');
    }

    public function downloadDocumentAsPdf(Request $request, Delegate $delegate, $field)
    {
        $allowedFields = [
            'delegate_doc_other_1',
            'delegate_doc_other_2',
            'delegate_doc_other_3'
        ];

        if (!in_array($field, $allowedFields)) {
            abort(404, 'Document type not found.');
        }

        $filePath = $delegate->{$field};
        $disk = 'public';

        if (!$filePath || !Storage::disk($disk)->exists($filePath)) {
            abort(404, 'File not found.');
        }

        $fieldMapping = [
            'delegate_doc_other_1' => 'Other_1',
            'delegate_doc_other_2' => 'Other_2',
            'delegate_doc_other_3' => 'Other_3',
        ];

        $docName = $fieldMapping[$field] ?? $field;
        $name = $delegate->delegateNameEn ?: 'Delegate_' . $delegate->id;
        $name = preg_replace('/[^A-Za-z0-9\-\_]/', '_', $name);

        $filename = "{$docName}_{$name}.pdf";
        $disposition = $request->input('disposition', 'inline');

        return \App\Helpers\PdfHelper::streamFile($disk, $filePath, $disposition, $filename);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Delegate $delegate)
    {
        try {
            if ($delegate->delegatePhoto) {
                Storage::disk('public')->delete($delegate->delegatePhoto);
            }
            $delegate->delete();
            return response()->json(['success' => 'Delegate deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not delete delegate.'], 500);
        }
    }
}
