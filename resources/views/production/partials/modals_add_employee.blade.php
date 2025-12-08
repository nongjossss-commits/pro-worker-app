<!-- Modal: Add Existing -->
<div class="modal fade" id="addExistingModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('production.add_employee', $production->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Existing Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Employee</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">-- Choose --</option>
                            @php
                                $query = \App\Models\Employee::query();
                                if ($production->type === 'employer' && $production->employer_id) {
                                    $query->where('employer_id', $production->employer_id);
                                }
                                $employees = $query->limit(200)->get();
                            @endphp
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">
                                    {{ $emp->fullname_th }} ({{ $emp->employeePassport }}) - {{ $emp->employer->name_th ?? 'Unknown' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Create New -->
<div class="modal fade" id="addNewModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('production.add_new_employee', $production->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Employee (External/Import)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <select name="title" class="form-select">
                            <option value="นาย">Mr. (นาย)</option>
                            <option value="นาง">Mrs. (นาง)</option>
                            <option value="นางสาว">Miss (นางสาว)</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Name (TH)</label>
                            <input type="text" name="name_th" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Surname (TH)</label>
                            <input type="text" name="surname_th" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Passport No.</label>
                        <input type="text" name="passport_no" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nationality</label>
                        <select name="nationality" class="form-select">
                            <option value="Myanmar">Myanmar</option>
                            <option value="Cambodia">Cambodia</option>
                            <option value="Laos">Laos</option>
                            <option value="Vietnam">Vietnam</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create & Add</button>
                </div>
            </div>
        </form>
    </div>
</div>
