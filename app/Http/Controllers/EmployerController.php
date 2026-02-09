<?php

namespace App\Http\Controllers;

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
        $query = Employer::with(['jobOwner', 'assignedStaff', 'addresses'])->latest();

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';

            $query->where(function($q) use ($searchTerm) {
                $q->where('employerNameTh', 'like', $searchTerm)
                  ->orWhere('employerNameEn', 'like', $searchTerm)
                  ->orWhere('businessType', 'like', $searchTerm)
                  ->orWhereHas('jobOwner', function($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', $searchTerm);
                  })
                  ->orWhereHas('assignedStaff', function($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', $searchTerm);
                  })
                  ->orWhere(function($subQ) use ($searchTerm) {
                      $subQ->filterByAddress($searchTerm);
                  });
            });
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
            'employer_doc_company' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_company_expiry' => 'nullable|date',
            'employer_doc_lease' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_construction' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_other_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_other_1_desc' => 'nullable|string|max:255',
            'employer_doc_other_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_other_2_desc' => 'nullable|string|max:255',
            'employer_doc_other_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_other_3_desc' => 'nullable|string|max:255',
            'job_owner_id' => 'required|exists:job_owners,id',
            'assigned_staff_id' => 'nullable|exists:users,id',
            // Signatures
            'signature_1_file' => 'nullable|image|max:2048',
            'signature_2_file' => 'nullable|image|max:2048',
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

        // Remove file inputs from array before create
        unset($validated['signature_1_file']);
        unset($validated['signature_2_file']);

        Employer::create($validated);
        return redirect()->route('employers.index')->with('success', 'Employer created successfully.');
    }

    public function edit(Request $request, Employer $employer)
    {
        $jobOwners = JobOwner::orderBy('name')->get();
        $staffUsers = User::role(['admin', 'staff', 'caretaker'])->orderBy('name')->get();

        $employeeQuery = $employer->employees()
            ->whereNull('terminated_at')
            ->where(function($q) {
                $q->whereNotIn('status', ['registration_pending', 'registration_cancelled'])
                  ->orWhereNull('status');
            });

        // Filter Logic
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $employeeQuery->where(function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', $searchTerm)
                  ->orWhere('employeeNameEn', 'like', $searchTerm)
                  ->orWhere('employeePassport', 'like', $searchTerm)
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
            'employer_doc_company' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_company_expiry' => 'nullable|date',
            'employer_doc_lease' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_construction' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_other_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_other_1_desc' => 'nullable|string|max:255',
            'employer_doc_other_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_other_2_desc' => 'nullable|string|max:255',
            'employer_doc_other_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'employer_doc_other_3_desc' => 'nullable|string|max:255',
            'job_owner_id' => 'required|exists:job_owners,id',
            'assigned_staff_id' => 'nullable|exists:users,id',
            // Signatures
            'signature_1_action' => 'nullable|in:keep,generate,upload,draw',
            'signature_1_file' => 'nullable|required_if:signature_1_action,upload|image|max:2048',
            'signature_1_base64' => 'nullable|string',
            'signature_2_action' => 'nullable|in:keep,generate,upload,draw',
            'signature_2_file' => 'nullable|required_if:signature_2_action,upload|image|max:2048',
            'signature_2_base64' => 'nullable|string',
        ]);

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

        // Cleanup fields not in DB
        unset($validated['signature_1_action']);
        unset($validated['signature_1_file']);
        unset($validated['signature_1_base64']);
        unset($validated['signature_2_action']);
        unset($validated['signature_2_file']);
        unset($validated['signature_2_base64']);

        $employer->update($validated);

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
                  ->orWhere('employeePassport', 'like', $searchTerm);
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

        if ($isHistoryExport) {
            $query = $employer->employees()->whereNotNull('terminated_at');
        } else {
            $query = $employer->employees()
                ->whereNull('terminated_at')
                ->where(function($q) {
                    $q->whereNotIn('status', ['registration_pending', 'registration_cancelled'])
                      ->orWhereNull('status');
                });
        }

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('employeeNameTh', 'like', $searchTerm)
                  ->orWhere('employeeNameEn', 'like', $searchTerm)
                  ->orWhere('employeePassport', 'like', $searchTerm)
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
        $fileName = "{$safeEmployerName}_{$exportType}_employees_" . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Encoding"    => "UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Employee Name (TH)', 'Employee Name (EN)', 'Nationality',
            'Passport No', 'Passport Expiry', 'Work Permit No',
            'Work Permit Expiry', 'Visa Expiry', '90 Day Report', 'Pink Card No'
        ];

        if ($isHistoryExport) {
            $columns[] = 'Terminated At';
            $columns[] = 'Termination Reason';
        }

        $callback = function() use($employees, $columns, $isHistoryExport) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // Add BOM for UTF-8
            fputcsv($file, $columns);

            foreach ($employees as $employee) {
                $row = [
                    'Employee Name (TH)'   => $employee->employeeNameTh,
                    'Employee Name (EN)'   => $employee->employeeNameEn,
                    'Nationality'          => $employee->employeeNationality,
                    'Passport No'          => $employee->employeePassport,
                    'Passport Expiry'      => $employee->passportExpiryDate,
                    'Work Permit No'       => $employee->employeeWorkPermit,
                    'Work Permit Expiry'   => $employee->workPermitExpiryDate,
                    'Visa Expiry'          => $employee->visaExpiryDate,
                    '90 Day Report'        => $employee->ninetyDayReportDate,
                    'Pink Card No'         => $employee->pinkCardNo,
                ];

                if ($isHistoryExport) {
                    $row['Terminated At'] = $employee->terminated_at;
                    $row['Termination Reason'] = $employee->termination_reason;
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function export(Request $request)
    {
        $this->authorize('view-employers');
        $query = Employer::with('jobOwner')->latest();

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('employerNameTh', 'like', $searchTerm)
                  ->orWhere('employerNameEn', 'like', $searchTerm)
                  ->orWhereHas('jobOwner', function($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', $searchTerm);
                  })
                  ->orWhere(function($subQ) use ($searchTerm) {
                      $subQ->filterByAddress($searchTerm);
                  });
            });
        }

        $employers = $query->get();
        $fileName = 'employers_export_' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Encoding"    => "UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Employer ID', 'Employer Name (TH)', 'Employer Name (EN)',
            'Job Owner', 'Business Type', 'Tax ID', 'Email', 'Phone'
        ];

        $callback = function() use($employers, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns);

            foreach ($employers as $employer) {
                $row['Employer ID']        = $employer->employerId;
                $row['Employer Name (TH)'] = $employer->employerNameTh;
                $row['Employer Name (EN)'] = $employer->employerNameEn;
                $row['Job Owner']          = $employer->jobOwner->name ?? 'N/A';
                $row['Business Type']      = $employer->businessType;
                $row['Tax ID']             = $employer->employerTaxId;
                $row['Email']              = $employer->employerEmail;
                $row['Phone']              = $employer->employerPhone;

                fputcsv($file, array_values($row));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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
                  ->orWhere('employerId', 'like', $searchTerm)
                  ->orWhere(function($subQ) use ($searchTerm) {
                      $subQ->filterByAddress($searchTerm);
                  });
            });
        }

        $employers = $query->select(['id', 'employerNameTh', 'employerNameEn', 'employerId'])->take(10)->get();
        return response()->json($employers);
    }

    public function locate(Employer $employer)
    {
        return redirect()->route('employers.index')
                         ->with('highlight_employer', $employer->id);
    }

    public function downloadDocumentAsPdf(Employer $employer, $field)
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

        $mimeType = Storage::disk($disk)->mimeType($filePath);

        if ($mimeType === 'application/pdf') {
            return Storage::disk($disk)->download($filePath);
        }

        return \App\Helpers\PdfHelper::streamFile($disk, $filePath);
    }
}
