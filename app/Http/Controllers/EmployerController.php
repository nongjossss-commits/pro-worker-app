<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Models\Employer;
use App\Models\JobOwner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\Employee;
use App\Services\SignatureGeneratorService; // Import Service
use Illuminate\Support\Facades\Hash;
use App\Traits\AddressFilterTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EmployerController extends Controller
{
    use AddressFilterTrait;

    /**
     * Apply permission middleware to the controller.
     */
    public function __construct()
    {
        $this->middleware('permission:view-employers', ['only' => ['index', 'show']]);
        $this->middleware('permission:create-employers', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit-employers', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete-employers', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $query = Employer::with(['jobOwner', 'caretakers', 'addresses'])->latest();

        if ($request->filled('search')) {
            $rawSearch = trim($request->input('search'));

            // Support ID:123 format for direct employer ID lookup
            if (preg_match('/^ID:\s*(\d+)$/i', $rawSearch, $matches)) {
                $query->where('id', (int) $matches[1]);
            } else {
                $searchTerm = '%' . $rawSearch . '%';

                $query->where(function($q) use ($searchTerm) {
                    $q->where('employerNameTh', 'like', $searchTerm)
                      ->orWhere('employerNameEn', 'like', $searchTerm)
                      ->orWhere('name_suffix', 'like', $searchTerm)
                      ->orWhere('businessType', 'like', $searchTerm)
                      ->orWhere('employerId', 'like', $searchTerm)
                      ->orWhere('employerTaxId', 'like', $searchTerm)
                      ->orWhereHas('jobOwner', function($subQ) use ($searchTerm) {
                          $subQ->where('name', 'like', $searchTerm);
                      })
                      ->orWhereHas('caretakers', function($subQ) use ($searchTerm) {
                          $subQ->where('name', 'like', $searchTerm);
                      })
                      ->orWhere(function($subQ) use ($searchTerm) {
                          $subQ->filterByAddress($searchTerm);
                      });
                });
            }
        }

        // Address options (before address filtering)
        $addressOptions = $this->getAddressOptions($query);

        // Apply address filters
        $query = $this->applyAddressFilters($query, $request);

        $perPage = $request->input('per_page', 10);
        $employers = $query->paginate($perPage)->withQueryString();

        return view('employers.index', compact('employers', 'addressOptions'));
    }

    public function create()
    {
        $jobOwners = JobOwner::orderBy('name')->get();
        $staffUsers = User::role(['admin', 'staff', 'caretaker'])->orderBy('name')->get();

        return view('employers.create', compact('jobOwners', 'staffUsers'));
    }

    public function store(Request $request)
    {
        // Validation with new fields
        $validated = $request->validate([
            'employerNameTh' => 'required|string|max:255',
            'employerNameEn' => 'nullable|string|max:255',
            'name_suffix' => 'nullable|string|max:255',
            'employerTaxId' => 'nullable|string|max:255',
            'employerEmail' => 'nullable|string|max:255|unique:employers,employerEmail',
            'employerPassword' => 'nullable|string|max:255',
            'employerPhone' => 'nullable|string|max:255',
            'outsource_re_code' => 'nullable|string|max:255',
            'outsource_password' => 'nullable|string|max:255',
            'socialSecurityHospital' => 'nullable|string|max:255',
            'businessType' => 'required|string|max:255',
            'signerNameTh' => 'nullable|string|max:255',
            'signerNameEn' => 'nullable|string|max:255',
            'signer_2_name_th' => 'nullable|string|max:255',
            'signer_2_name_en' => 'nullable|string|max:255',
            'businessTypeEn' => 'nullable|string|max:255',
            'regCapital' => 'nullable|numeric',
            'regDate' => 'nullable|date',
            'minimum_wage' => 'nullable|numeric',
            'employer_doc_company' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employer_doc_company_expiry' => 'nullable|date',
            'employer_doc_lease' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employer_doc_construction' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employer_doc_other_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employer_doc_other_1_desc' => 'nullable|string|max:255',
            'employer_doc_other_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employer_doc_other_2_desc' => 'nullable|string|max:255',
            'employer_doc_other_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employer_doc_other_3_desc' => 'nullable|string|max:255',
            'job_owner_id' => 'required|exists:job_owners,id',
            'assigned_staff_ids' => 'nullable|array',
            'assigned_staff_ids.*' => 'exists:users,id',
            // Signatures & Stamp
            'signature_1_file' => 'nullable|image|max:2048',
            'signature_2_file' => 'nullable|image|max:2048',
            'employer_stamp_file' => 'nullable|image|max:2048',
        ]);

        // Generate ID
        do {
            $randomId = 'EMP-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Employer::where('employerId', $randomId)->exists());

        $validated['employerId'] = $randomId;

        // Handle docs
        $docFields = ['employer_doc_company', 'employer_doc_lease', 'employer_doc_construction', 'employer_doc_other_1', 'employer_doc_other_2', 'employer_doc_other_3'];
        foreach ($docFields as $field) {
            if ($request->hasFile($field)) {
                $validated[$field] = $request->file($field)->store('employer_documents', 'public');
            }
        }

        // Handle Signatures Uploads
        if ($request->hasFile('signature_1_file')) {
            $validated['signature_1_path'] = $request->file('signature_1_file')->store('signatures/employers', 'public');
        }
        if ($request->hasFile('signature_2_file')) {
            $validated['signature_2_path'] = $request->file('signature_2_file')->store('signatures/employers', 'public');
        }
        if ($request->hasFile('employer_stamp_file')) {
            $validated['employer_stamp_path'] = $request->file('employer_stamp_file')->store('signatures/employers', 'public');
        }

        // Remove file inputs from array before create
        unset($validated['signature_1_file']);
        unset($validated['signature_2_file']);
        unset($validated['employer_stamp_file']);

        // Apply SuperAdmin defaults for Employer Other Document Descriptions if not provided
        $defaults = \App\Models\SuperAdminSetting::where('key', 'like', 'employer_other_%_desc')->pluck('value', 'key');
        for ($i = 1; $i <= 3; $i++) {
            $descField = "employer_doc_other_{$i}_desc";
            $settingKey = "employer_other_{$i}_desc";
            if (!isset($validated[$descField]) || trim($validated[$descField]) === '') {
                if (isset($defaults[$settingKey])) {
                    $validated[$descField] = $defaults[$settingKey];
                }
            }
        }

        $staffIds = $validated['assigned_staff_ids'] ?? [];
        unset($validated['assigned_staff_ids']);

        $employer = Employer::create($validated);
        $employer->caretakers()->sync($staffIds);

        return redirect()->route('employers.index')->with('success', 'Employer created successfully.');
    }

    public function edit(Request $request, Employer $employer)
    {
        $employer->load('caretakers');
        $jobOwners = JobOwner::orderBy('name')->get();
        $staffUsers = User::role(['admin', 'staff', 'caretaker'])->orderBy('name')->get();

        $employeeQuery = $employer->employees()
            ->whereNull('terminated_at')
            ->where(function($q) {
                $q->whereNotIn('status', ['registration_cancelled'])
                  ->orWhereNull('status');
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        // Filter Logic
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $employeeQuery->where(function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', $searchTerm)
                  ->orWhere('employeeNameEn', 'like', $searchTerm)
                  ->orWhere('name_suffix', 'like', $searchTerm)
                  ->orWhere('employeePassport', 'like', $searchTerm)
                  ->orWhere('employeeWorkPermit', 'like', $searchTerm)
                  ->orWhere('employee_id_number', 'like', $searchTerm)
                  ->orWhere('name_list_number', 'like', $searchTerm)
                  ->orWhere('request_number', 'like', $searchTerm)
                  ->orWhere('employer_employee_id', 'like', $searchTerm)
                  ->orWhere('pinkCardNo', 'like', $searchTerm);
            });
        }

        if ($request->filled('nationality')) {
            $employeeQuery->where('employeeNationality', $request->input('nationality'));
        }

        if ($request->filled('mou_group')) {
            $employeeQuery->where('workPermitMOUGroup', $request->input('mou_group'));
        }

        if ($request->filled('insurance_type')) {
            $insuranceType = $request->input('insurance_type');
            if ($insuranceType === 'none') {
                $employeeQuery->where(function ($q) {
                    $q->whereNull('insurance_type')->orWhere('insurance_type', '=', '');
                });
            } else {
                $employeeQuery->where('insurance_type', $insuranceType);
            }
        }

        if ($request->filled('pink_card')) {
            if ($request->input('pink_card') === 'yes') {
                $employeeQuery->where(function ($q) {
                    $q->whereNotNull('pinkCardNo')->where('pinkCardNo', '!=', '');
                });
            } elseif ($request->input('pink_card') === 'no') {
                $employeeQuery->where(function ($q) {
                    $q->whereNull('pinkCardNo')->orWhere('pinkCardNo', '=', '');
                });
            }
        }

        if ($request->filled('passport_status')) {
            if ($request->input('passport_status') === 'has_passport') {
                $employeeQuery->where(function ($q) {
                    $q->whereNotNull('employeePassport')->where('employeePassport', '!=', '');
                });
            } elseif ($request->input('passport_status') === 'no_passport') {
                $employeeQuery->where(function ($q) {
                    $q->whereNull('employeePassport')->orWhere('employeePassport', '=', '');
                });
            }
        }

        if ($request->filled('visa_status')) {
            if ($request->input('visa_status') === 'has_visa') {
                $employeeQuery->where(function ($q) {
                    $q->whereNotNull('visaExpiryDate')->where('visaExpiryDate', '!=', '');
                });
            } elseif ($request->input('visa_status') === 'no_visa') {
                $employeeQuery->where(function ($q) {
                    $q->whereNull('visaExpiryDate')->orWhere('visaExpiryDate', '=', '');
                });
            }
        }

        if ($request->filled('work_permit_expiry_date')) {
            $employeeQuery->whereDate('workPermitExpiryDate', $request->input('work_permit_expiry_date'));
        }

        if ($request->filled('passport_type_myanmar')) {
            $employeeQuery->where('passportType', $request->input('passport_type_myanmar'));
        }

        if ($request->filled('passport_type_cambodia')) {
            $employeeQuery->where('passport_type_cambodia', $request->input('passport_type_cambodia'));
        }

        $perPageOptions = [10, 25, 50];
        $currentPerPage = $request->input('per_page', 10);
        $employees = $employeeQuery->paginate($currentPerPage)->withQueryString();
        $currentView = $request->input('view', 'card');
        $terminatedEmployees = $employer->employees()->whereNotNull('terminated_at')->get();

        return view('employers.edit', compact(
            'employer',
            'jobOwners',
            'staffUsers',
            'employees',
            'terminatedEmployees',
            'perPageOptions',
            'currentView',
            'currentPerPage'
        ));
    }

    public function update(Request $request, Employer $employer)
    {
        $validated = $request->validate([
            'employerNameTh' => 'required|string|max:255',
            'employerNameEn' => 'nullable|string|max:255',
            'name_suffix' => 'nullable|string|max:255',
            'employerId' => ['required', 'string', Rule::unique('employers')->ignore($employer->id), 'max:255'],
            'employerTaxId' => 'nullable|string|max:255',
            'employerEmail' => ['nullable', 'string', 'max:255', Rule::unique('employers', 'employerEmail')->ignore($employer->id)],
            'employerPassword' => 'nullable|string|max:255',
            'employerPhone' => 'nullable|string|max:255',
            'outsource_re_code' => 'nullable|string|max:255',
            'outsource_password' => 'nullable|string|max:255',
            'socialSecurityHospital' => 'nullable|string|max:255',
            'businessType' => 'required|string|max:255',
            'signerNameTh' => 'nullable|string|max:255',
            'signerNameEn' => 'nullable|string|max:255',
            'signer_2_name_th' => 'nullable|string|max:255',
            'signer_2_name_en' => 'nullable|string|max:255',
            'businessTypeEn' => 'nullable|string|max:255',
            'regCapital' => 'nullable|numeric',
            'regDate' => 'nullable|date',
            'minimum_wage' => 'nullable|numeric',
            'employer_doc_company' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employer_doc_company_expiry' => 'nullable|date',
            'employer_doc_lease' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employer_doc_construction' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employer_doc_other_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employer_doc_other_1_desc' => 'nullable|string|max:255',
            'employer_doc_other_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employer_doc_other_2_desc' => 'nullable|string|max:255',
            'employer_doc_other_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'employer_doc_other_3_desc' => 'nullable|string|max:255',
            'job_owner_id' => 'required|exists:job_owners,id',
            'assigned_staff_ids' => 'nullable|array',
            'assigned_staff_ids.*' => 'exists:users,id',
            // Signatures & Stamp
            'signature_1_action' => 'nullable|in:keep,generate,upload,draw',
            'signature_1_file' => 'nullable|required_if:signature_1_action,upload|image|max:2048',
            'signature_1_base64' => 'nullable|string',
            'signature_2_action' => 'nullable|in:keep,generate,upload,draw',
            'signature_2_file' => 'nullable|required_if:signature_2_action,upload|image|max:2048',
            'signature_2_base64' => 'nullable|string',
            'employer_stamp_action' => 'nullable|in:keep,upload,draw',
            'employer_stamp_file' => 'nullable|required_if:employer_stamp_action,upload|image|max:2048',
            'employer_stamp_base64' => 'nullable|string',
        ]);

        // Employers shouldn't be updating signatures or stamps. Clear them if present just in case.
        if (auth()->user()->hasRole('employer')) {
            unset($validated['signature_1_action'], $validated['signature_1_file'], $validated['signature_1_base64']);
            unset($validated['signature_2_action'], $validated['signature_2_file'], $validated['signature_2_base64']);
            unset($validated['employer_stamp_action'], $validated['employer_stamp_file'], $validated['employer_stamp_base64']);
        }

        // Handle docs
        $docFields = ['employer_doc_company', 'employer_doc_lease', 'employer_doc_construction', 'employer_doc_other_1', 'employer_doc_other_2', 'employer_doc_other_3'];
        foreach ($docFields as $field) {
            if ($request->hasFile($field)) {
                if ($employer->{$field}) {
                    Storage::disk('public')->delete($employer->{$field});
                }
                $validated[$field] = $request->file($field)->store('employer_documents', 'public');
            }
        }

        // Handle Signatures
        $sigService = app(SignatureGeneratorService::class);

        // Signer 1
        $sig1Action = $request->input('signature_1_action', 'keep');
        if ($sig1Action === 'upload' && $request->hasFile('signature_1_file')) {
             if ($employer->signature_1_path) Storage::disk('public')->delete($employer->signature_1_path);
             $validated['signature_1_path'] = $request->file('signature_1_file')->store('signatures/employers', 'public');
        } elseif ($sig1Action === 'generate') {
             if ($employer->signature_1_path) Storage::disk('public')->delete($employer->signature_1_path);
             $seed = 'EMPR-' . $employer->id . '-1-' . time();
             $content = $sigService->generate($seed);
             $path = 'signatures/employers/emp_' . $employer->id . '_sig1_' . time() . '.png';
             Storage::disk('public')->put($path, $content);
             $validated['signature_1_path'] = $path;
        } elseif ($sig1Action === 'draw' && $request->filled('signature_1_base64')) {
             $base64Image = $request->input('signature_1_base64');
             if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                 if ($employer->signature_1_path) Storage::disk('public')->delete($employer->signature_1_path);
                 $data = substr($base64Image, strpos($base64Image, ',') + 1);
                 $data = base64_decode($data);
                 $path = 'signatures/employers/emp_' . $employer->id . '_sig1_' . time() . '.' . strtolower($type[1]);
                 Storage::disk('public')->put($path, $data);
                 $validated['signature_1_path'] = $path;
             }
        }

        // Signer 2
        $sig2Action = $request->input('signature_2_action', 'keep');
        if ($sig2Action === 'upload' && $request->hasFile('signature_2_file')) {
             if ($employer->signature_2_path) Storage::disk('public')->delete($employer->signature_2_path);
             $validated['signature_2_path'] = $request->file('signature_2_file')->store('signatures/employers', 'public');
        } elseif ($sig2Action === 'generate') {
             if ($employer->signature_2_path) Storage::disk('public')->delete($employer->signature_2_path);
             $seed = 'EMPR-' . $employer->id . '-2-' . time();
             $content = $sigService->generate($seed);
             $path = 'signatures/employers/emp_' . $employer->id . '_sig2_' . time() . '.png';
             Storage::disk('public')->put($path, $content);
             $validated['signature_2_path'] = $path;
        } elseif ($sig2Action === 'draw' && $request->filled('signature_2_base64')) {
             $base64Image = $request->input('signature_2_base64');
             if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                 if ($employer->signature_2_path) Storage::disk('public')->delete($employer->signature_2_path);
                 $data = substr($base64Image, strpos($base64Image, ',') + 1);
                 $data = base64_decode($data);
                 $path = 'signatures/employers/emp_' . $employer->id . '_sig2_' . time() . '.' . strtolower($type[1]);
                 Storage::disk('public')->put($path, $data);
                 $validated['signature_2_path'] = $path;
             }
        }

        // Employer Stamp
        if (!auth()->user()->hasRole('employer')) {
            $stampAction = $request->input('employer_stamp_action', 'keep');
            if ($stampAction === 'upload' && $request->hasFile('employer_stamp_file')) {
                if ($employer->employer_stamp_path) Storage::disk('public')->delete($employer->employer_stamp_path);
                $validated['employer_stamp_path'] = $request->file('employer_stamp_file')->store('signatures/employers', 'public');
            } elseif ($stampAction === 'draw' && $request->filled('employer_stamp_base64')) {
                $base64Image = $request->input('employer_stamp_base64');
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                    if ($employer->employer_stamp_path) Storage::disk('public')->delete($employer->employer_stamp_path);
                    $data = substr($base64Image, strpos($base64Image, ',') + 1);
                    $data = base64_decode($data);
                    $path = 'signatures/employers/emp_' . $employer->id . '_stamp_' . time() . '.' . strtolower($type[1]);
                    Storage::disk('public')->put($path, $data);
                    $validated['employer_stamp_path'] = $path;
                }
            }
        }

        // Cleanup fields not in DB
        unset($validated['signature_1_action']);
        unset($validated['signature_1_file']);
        unset($validated['signature_1_base64']);
        unset($validated['signature_2_action']);
        unset($validated['signature_2_file']);
        unset($validated['signature_2_base64']);
        unset($validated['employer_stamp_action']);
        unset($validated['employer_stamp_file']);
        unset($validated['employer_stamp_base64']);

        $staffIds = $validated['assigned_staff_ids'] ?? [];
        unset($validated['assigned_staff_ids']);

        $employer->update($validated);
        $employer->caretakers()->sync($staffIds);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Employer updated successfully.'
            ]);
        }

        return redirect()->route('employers.index')->with('success', 'Employer updated successfully.');
    }

    public function destroy(Employer $employer)
    {
        if ($employer->document_company_registration) {
            Storage::disk('public')->delete($employer->document_company_registration);
        }
        if ($employer->document_vat_registration) {
            Storage::disk('public')->delete($employer->document_vat_registration);
        }
        if ($employer->document_map) {
            Storage::disk('public')->delete($employer->document_map);
        }

        $employer->delete();
        return redirect()->route('employers.index')->with('success', 'Employer deleted successfully.');
    }

    public function filterHistory(Request $request, Employer $employer)
    {
        $query = $employer->employees()->whereNotNull('terminated_at');

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', $searchTerm)
                  ->orWhere('employeeNameEn', 'like', $searchTerm)
                  ->orWhere('name_suffix', 'like', $searchTerm)
                  ->orWhere('employeePassport', 'like', $searchTerm)
                  ->orWhere('employeeWorkPermit', 'like', $searchTerm)
                  ->orWhere('employee_id_number', 'like', $searchTerm)
                  ->orWhere('name_list_number', 'like', $searchTerm)
                  ->orWhere('request_number', 'like', $searchTerm)
                  ->orWhere('employer_employee_id', 'like', $searchTerm)
                  ->orWhere('pinkCardNo', 'like', $searchTerm);
            });
        }

        $terminatedEmployees = $query->paginate(10)->withQueryString();

        $terminatedEmployees->getCollection()->transform(function ($employee) {
            $employee->can_restore = auth()->user()->can('restore-employees');
            $employee->can_force_delete = auth()->user()->can('force-delete-employees');
            $employee->photo_url = $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : asset('images/default-avatar.png');
            $employee->days_since_termination = floor($employee->terminated_at ? \Carbon\Carbon::parse($employee->terminated_at)->diffInDays(\Carbon\Carbon::now()) : 0);
            return $employee;
        });

        return response()->json($terminatedEmployees);
    }

    public function exportHistory(Request $request, Employer $employer)
    {
        $request->merge(['history' => true]);
        return $this->exportEmployees($request, $employer);
    }

    public function exportEmployees(Request $request, Employer $employer)
    {
        $this->authorize('view-employees');

        $isHistoryExport = $request->has('history');

        ActivityLogHelper::logAction('export', 'ส่งออกข้อมูลลูกจ้าง (' . ($isHistoryExport ? 'ประวัติ' : 'ปัจจุบัน') . ') ของ ' . $employer->employerNameTh, Employer::class, $employer->id, [
            'type' => $isHistoryExport ? 'history' : 'active',
            'employer' => $employer->employerNameTh,
        ]);

        if ($isHistoryExport) {
            $query = $employer->employees()->whereNotNull('terminated_at');
        } else {
            $query = $employer->employees()
                ->whereNull('terminated_at')
                ->where(function($q) {
                    $q->whereNotIn('status', ['registration_cancelled'])
                      ->orWhereNull('status');
                });
        }

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', $searchTerm)
                  ->orWhere('employeeNameEn', 'like', $searchTerm)
                  ->orWhere('name_suffix', 'like', $searchTerm)
                  ->orWhere('employeePassport', 'like', $searchTerm)
                  ->orWhere('employeeWorkPermit', 'like', $searchTerm)
                  ->orWhere('employee_id_number', 'like', $searchTerm)
                  ->orWhere('name_list_number', 'like', $searchTerm)
                  ->orWhere('request_number', 'like', $searchTerm)
                  ->orWhere('employer_employee_id', 'like', $searchTerm)
                  ->orWhere('pinkCardNo', 'like', $searchTerm);
            });
        }

        if (!$isHistoryExport) {
            if ($request->filled('nationality')) {
                $query->where('employeeNationality', $request->input('nationality'));
            }
            if ($request->filled('mou_group')) {
                $query->where('workPermitMOUGroup', $request->input('mou_group'));
            }
            if ($request->filled('insurance_type')) {
                $insuranceType = $request->input('insurance_type');
                if ($insuranceType === 'none') {
                    $query->where(function ($q) {
                        $q->whereNull('insurance_type')->orWhere('insurance_type', '=', '');
                    });
                } else {
                    $query->where('insurance_type', $insuranceType);
                }
            }
            if ($request->filled('pink_card')) {
                if ($request->input('pink_card') === 'yes') {
                    $query->where(fn($q) => $q->whereNotNull('pinkCardNo')->where('pinkCardNo', '!=', ''));
                } elseif ($request->input('pink_card') === 'no') {
                    $query->where(fn($q) => $q->whereNull('pinkCardNo')->orWhere('pinkCardNo', '=', ''));
                }
            }

            if ($request->filled('passport_status')) {
                if ($request->input('passport_status') === 'has_passport') {
                    $query->where(function ($q) {
                        $q->whereNotNull('employeePassport')->where('employeePassport', '!=', '');
                    });
                } elseif ($request->input('passport_status') === 'no_passport') {
                    $query->where(function ($q) {
                        $q->whereNull('employeePassport')->orWhere('employeePassport', '=', '');
                    });
                }
            }

            if ($request->filled('passport_type_myanmar')) {
                $query->where('passportType', $request->input('passport_type_myanmar'));
            }

            if ($request->filled('passport_type_cambodia')) {
                $query->where('passport_type_cambodia', $request->input('passport_type_cambodia'));
            }
        }

        $employees = $query->get();

        $safeEmployerName = preg_replace('/[^A-Za-z0-9\-]/', '_', $employer->employerNameTh);
        $exportType = $isHistoryExport ? 'history' : 'active';
        $fileName = "{$safeEmployerName}_{$exportType}_employees_" . date('Y-m-d') . '.xlsx';

        $columns = [
            'Employee Name (TH)', 'Employee Name (EN)', 'Nationality',
            'Passport No', 'Passport Expiry', 'Work Permit No',
            'Work Permit Expiry', 'Visa Expiry', '90 Day Report', 'Pink Card No'
        ];

        if ($isHistoryExport) {
            $columns[] = 'Terminated At';
            $columns[] = 'Termination Reason';
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Write Header
        $columnIndex = 1;
        foreach ($columns as $col) {
            $cell = $sheet->getCell([$columnIndex, 1]);
            $cell->setValue($col);
            $sheet->getStyle([$columnIndex, 1])->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF2F2F2'], // Light gray
                ],
            ]);
            $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
            $columnIndex++;
        }

        // Write Data Rows
        $currentRow = 2;
        $textColumns = ['Passport No', 'Work Permit No', 'Pink Card No'];

        foreach ($employees as $employee) {
            $row = [
                'Employee Name (TH)'   => $employee->employeeNameTh,
                'Employee Name (EN)'   => $employee->getRawOriginal('employeeNameEn'),
                'Nationality'          => $employee->employeeNationality,
                'Passport No'          => $employee->employeePassport,
                'Passport Expiry'      => $employee->passportExpiryDate ? \Carbon\Carbon::parse($employee->passportExpiryDate)->format('d/m/Y') : '-',
                'Work Permit No'       => $employee->employeeWorkPermit,
                'Work Permit Expiry'   => $employee->workPermitExpiryDate ? \Carbon\Carbon::parse($employee->workPermitExpiryDate)->format('d/m/Y') : '-',
                'Visa Expiry'          => $employee->visaExpiryDate ? \Carbon\Carbon::parse($employee->visaExpiryDate)->format('d/m/Y') : '-',
                '90 Day Report'        => $employee->ninetyDayReportDate ? \Carbon\Carbon::parse($employee->ninetyDayReportDate)->format('d/m/Y') : '-',
                'Pink Card No'         => $employee->pinkCardNo,
            ];

            if ($isHistoryExport) {
                $row['Terminated At'] = $employee->terminated_at ? \Carbon\Carbon::parse($employee->terminated_at)->format('d/m/Y H:i:s') : '-';
                $row['Termination Reason'] = $employee->termination_reason;
            }

            $columnIndex = 1;
            foreach ($columns as $col) {
                $val = $row[$col] ?? '-';
                $cell = $sheet->getCell([$columnIndex, $currentRow]);

                if (in_array($col, $textColumns) && $val !== '' && $val !== '-') {
                    $cell->setValueExplicit($val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $cell->setValue($val);
                }

                $sheet->getStyle([$columnIndex, $currentRow])->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ]
                ]);
                $columnIndex++;
            }
            $currentRow++;
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('view-employers');

        ActivityLogHelper::logAction('export', 'ส่งออกรายชื่อนายจ้างทั้งหมด (Excel)', null, null, [
            'search' => $request->input('search'),
        ]);

        $query = Employer::with('jobOwner')->latest();

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('employerNameTh', 'like', $searchTerm)
                  ->orWhere('employerNameEn', 'like', $searchTerm)
                  ->orWhere('name_suffix', 'like', $searchTerm)
                  ->orWhere('employerId', 'like', $searchTerm)
                  ->orWhere('employerTaxId', 'like', $searchTerm)
                  ->orWhereHas('jobOwner', function($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', $searchTerm);
                  })
                  ->orWhere(function($subQ) use ($searchTerm) {
                      $subQ->filterByAddress($searchTerm);
                  });
            });
        }

        $employers = $query->get();
        $fileName = 'employers_export_' . date('Y-m-d') . '.xlsx';

        $columns = [
            'Employer ID', 'Employer Name (TH)', 'Employer Name (EN)',
            'Job Owner', 'Business Type', 'Tax ID', 'Email', 'Phone'
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Write Header
        $columnIndex = 1;
        foreach ($columns as $col) {
            $cell = $sheet->getCell([$columnIndex, 1]);
            $cell->setValue($col);
            $sheet->getStyle([$columnIndex, 1])->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF2F2F2'], // Light gray
                ],
            ]);
            $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
            $columnIndex++;
        }

        // Write Data Rows
        $currentRow = 2;
        $textColumns = ['Employer ID', 'Tax ID', 'Phone'];

        foreach ($employers as $employer) {
            $row = [
                'Employer ID'        => $employer->employerId,
                'Employer Name (TH)' => $employer->getRawOriginal('employerNameTh'),
                'Employer Name (EN)' => $employer->employerNameEn,
                'Job Owner'          => $employer->jobOwner->name ?? 'N/A',
                'Business Type'      => $employer->businessType,
                'Tax ID'             => $employer->employerTaxId,
                'Email'              => $employer->employerEmail,
                'Phone'              => $employer->employerPhone,
            ];

            $columnIndex = 1;
            foreach ($columns as $col) {
                $val = $row[$col] ?? '-';
                $cell = $sheet->getCell([$columnIndex, $currentRow]);

                if (in_array($col, $textColumns) && $val !== '' && $val !== '-') {
                    $cell->setValueExplicit($val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $cell->setValue($val);
                }

                $sheet->getStyle([$columnIndex, $currentRow])->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ]
                ]);
                $columnIndex++;
            }
            $currentRow++;
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function listApi(Request $request)
    {
        $this->authorize('view-employers');
        $query = Employer::query();

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('employerNameTh', 'like', $searchTerm)
                  ->orWhere('employerNameEn', 'like', $searchTerm)
                  ->orWhere('name_suffix', 'like', $searchTerm)
                  ->orWhere('employerId', 'like', $searchTerm)
                  ->orWhere('employerTaxId', 'like', $searchTerm)
                  ->orWhere(function($subQ) use ($searchTerm) {
                      $subQ->filterByAddress($searchTerm);
                  });
            });
        }

        $employers = $query->select(['id', 'employerNameTh', 'employerNameEn', 'name_suffix', 'employerId'])->take(10)->get();
        return response()->json($employers);
    }

    public function locate(Employer $employer)
    {
        return redirect()->route('employers.index')
                         ->with('highlight_employer', $employer->id);
    }

    public function downloadDocumentAsPdf(Request $request, Employer $employer, $field)
    {
        $allowedFields = [
            'employer_doc_company',
            'employer_doc_lease',
            'employer_doc_construction',
            'employer_doc_other_1',
            'employer_doc_other_2',
            'employer_doc_other_3'
        ];

        if (!in_array($field, $allowedFields)) {
            abort(404, 'Document type not found.');
        }

        $this->authorize('view-employers');
        $filePath = $employer->{$field};
        $disk = 'public';

        if (!$filePath || !Storage::disk($disk)->exists($filePath)) {
            abort(404, 'File not found.');
        }

        // Map field to readable name
        $fieldMapping = [
            'employer_doc_company' => 'Company_Registration',
            'employer_doc_lease' => 'Lease_Agreement',
            'employer_doc_construction' => 'Construction_Contract',
            'employer_doc_other_1' => 'Other_1',
            'employer_doc_other_2' => 'Other_2',
            'employer_doc_other_3' => 'Other_3',
        ];

        $docName = $fieldMapping[$field] ?? $field;
        // Use English name for filename compatibility
        $employerName = $employer->employerNameEn ?: 'Employer_' . $employer->employerId;
        // Sanitize name to be safe
        $employerName = preg_replace('/[^A-Za-z0-9\-\_]/', '_', $employerName);

        $employerId = preg_replace('/[^A-Za-z0-9\-\_]/', '_', (string)$employer->employerId);

        // Construct filename: [DocType]_[EmployerName]_[EmployerID].pdf
        $filename = "{$docName}_{$employerName}_{$employerId}.pdf";

        $disposition = $request->input('disposition', 'inline');

        return \App\Helpers\PdfHelper::streamFile($disk, $filePath, $disposition, $filename);
    }
}
