@php
    use App\Services\ContractStatusService;
    $contract = \App\Models\ServiceContract::current();
    $snap = ContractStatusService::snapshot();
    $extensions = $contract ? $contract->extensions()->with('extender')->limit(10)->get() : collect();

    $modeBadge = match ($snap['mode']) {
        ContractStatusService::MODE_ACTIVE => ['bg-success', 'ใช้งานปกติ (Active)'],
        ContractStatusService::MODE_GRACE => ['bg-warning text-dark', 'เปิดใช้ชั่วคราว (Grace)'],
        ContractStatusService::MODE_READ_ONLY => ['bg-danger', 'ดูอย่างเดียว (Read-Only)'],
        default => ['bg-secondary', 'ยังไม่ตั้งค่าสัญญา'],
    };
@endphp

<div class="alert alert-info py-2 small mb-3">
    <i class="bi bi-info-circle me-1"></i>
    ตั้งค่าอายุสัญญาให้บริการ / สัญญาทดสอบ — เมื่อครบกำหนดโดยไม่มีการต่ออายุ ระบบจะเข้าโหมด <strong>ดูอย่างเดียว</strong>
    สำหรับทุก role ยกเว้น <strong>Super Admin</strong> ระหว่างเจรจา Super Admin สามารถเปิดใช้ชั่วคราวเป็นระยะๆ ได้
</div>

{{-- ============= Current status ============= --}}
<div class="card border-{{ $snap['mode'] === ContractStatusService::MODE_READ_ONLY ? 'danger' : ($snap['mode'] === ContractStatusService::MODE_GRACE ? 'warning' : 'success') }} mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-3 text-center border-end">
                <div class="text-muted small">สถานะปัจจุบัน</div>
                <span class="badge {{ $modeBadge[0] }} fs-6 mt-1">{{ $modeBadge[1] }}</span>
                @if($snap['days_remaining'] !== null && $snap['mode'] !== ContractStatusService::MODE_READ_ONLY)
                    <div class="mt-2 fw-bold" style="font-size: 1.5rem;">
                        {{ $snap['days_remaining'] }} <small class="text-muted fs-6">วัน</small>
                    </div>
                    <div class="small text-muted">คงเหลือถึง {{ \Carbon\Carbon::parse($snap['effective_end'])->format('d/m/Y') }}</div>
                @elseif($snap['mode'] === ContractStatusService::MODE_READ_ONLY)
                    <div class="mt-2 text-danger">
                        <i class="bi bi-x-circle-fill"></i> หมดอายุแล้ว
                    </div>
                @endif
            </div>
            <div class="col-md-3">
                <div class="text-muted small">ประเภทสัญญา</div>
                <div class="fw-bold">
                    @if($contract && $contract->contract_type === 'service')
                        <i class="bi bi-file-earmark-check-fill text-success"></i> สัญญาให้บริการ (Service)
                    @elseif($contract && $contract->contract_type === 'trial')
                        <i class="bi bi-file-earmark-text text-info"></i> สัญญาทดสอบ (Trial)
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </div>
                <div class="text-muted small mt-2">ลูกค้า</div>
                <div>{{ $contract->customer_name ?? '-' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">วันเริ่มสัญญา</div>
                <div class="fw-bold">{{ $contract && $contract->start_date ? $contract->start_date->format('d/m/Y') : '-' }}</div>
                <div class="text-muted small mt-2">วันสิ้นสุดสัญญา (จริง)</div>
                <div class="fw-bold">{{ $contract && $contract->end_date ? $contract->end_date->format('d/m/Y') : '-' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">โหมดชั่วคราว (Grace)</div>
                @if($contract && $contract->grace_end_date)
                    <div class="fw-bold text-warning">
                        <i class="bi bi-clock-history"></i> เปิดถึง {{ $contract->grace_end_date->format('d/m/Y') }}
                    </div>
                @else
                    <div class="text-muted">ปิด</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ============= Save Contract Info ============= --}}
<div class="card border-primary mb-3">
    <div class="card-header bg-primary bg-opacity-10 fw-bold">
        <i class="bi bi-pencil-square me-1"></i> ข้อมูลสัญญา (บันทึก / ต่ออายุ)
    </div>
    <div class="card-body">
        <form action="{{ route('super-admin.contract.save') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">ประเภทสัญญา <span class="text-danger">*</span></label>
                    <select name="contract_type" class="form-select" required>
                        <option value="trial" {{ ($contract->contract_type ?? 'trial') === 'trial' ? 'selected' : '' }}>Trial (ทดสอบ)</option>
                        <option value="service" {{ ($contract->contract_type ?? '') === 'service' ? 'selected' : '' }}>Service (ให้บริการ)</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label small fw-bold">ชื่อลูกค้า / บริษัท</label>
                    <input type="text" name="customer_name" class="form-control" value="{{ $contract->customer_name ?? '' }}" placeholder="บริษัท ABC จำกัด">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">วันเริ่มสัญญา</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $contract && $contract->start_date ? $contract->start_date->format('Y-m-d') : '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">วันสิ้นสุดสัญญา <span class="text-danger">*</span></label>
                    <input type="date" name="end_date" class="form-control" required
                           value="{{ $contract && $contract->end_date ? $contract->end_date->format('Y-m-d') : now()->addDays(30)->format('Y-m-d') }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save-fill me-1"></i> บันทึกสัญญา
                    </button>
                </div>
                <div class="col-md-12">
                    <label class="form-label small fw-bold">หมายเหตุ</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="รายละเอียดสัญญา (ถ้ามี)">{{ $contract->notes ?? '' }}</textarea>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ============= Grace Period ============= --}}
<div class="card border-warning mb-3">
    <div class="card-header bg-warning bg-opacity-25 fw-bold">
        <i class="bi bi-clock-history me-1"></i> เปิดใช้งานชั่วคราว (Grace Period)
    </div>
    <div class="card-body">
        <div class="alert alert-warning py-2 small mb-3">
            ใช้เมื่ออยู่ระหว่างเจรจาต่อสัญญา — ระบบจะให้ใช้งานปกติจนถึงวันที่ระบุ แล้วกลับเข้าโหมดดูอย่างเดียว
            @if($contract && $contract->grace_end_date)
                <br><strong>ปัจจุบัน:</strong> เปิดใช้งานถึง {{ $contract->grace_end_date->format('d/m/Y') }}
            @endif
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <form action="{{ route('super-admin.contract.grace.enable') }}" method="POST">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">จำนวนวัน <span class="text-danger">*</span></label>
                            <input type="number" name="days" class="form-control" required min="1" max="365" value="7" placeholder="7">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">เหตุผล (ถ้ามี)</label>
                            <input type="text" name="reason" class="form-control" placeholder="เช่น รอลูกค้าเซ็นสัญญา">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-warning w-100 fw-bold">
                                <i class="bi bi-play-fill me-1"></i>
                                {{ $contract && $contract->grace_end_date ? 'ต่อขยาย' : 'เปิดใช้' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            @if($contract && $contract->grace_end_date)
                <div class="col-md-4 d-flex align-items-end">
                    <form action="{{ route('super-admin.contract.grace.stop') }}" method="POST" class="w-100"
                          onsubmit="return confirm('ปิดโหมดชั่วคราวและกลับไปยึดตามวันสิ้นสุดสัญญาจริงหรือไม่?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-stop-fill me-1"></i> ปิดโหมดชั่วคราว
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- History --}}
        @if($extensions->count())
            <hr class="my-3">
            <h6 class="fw-bold small">
                <i class="bi bi-clock-history"></i> ประวัติการต่ออายุ / เปิดใช้ชั่วคราว
            </h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>วันที่</th>
                            <th>โดย</th>
                            <th>การกระทำ</th>
                            <th>วันเดิม</th>
                            <th>วันใหม่</th>
                            <th>จำนวนวัน</th>
                            <th>เหตุผล</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($extensions as $ext)
                            <tr>
                                <td class="small">{{ $ext->created_at->format('d/m/Y H:i') }}</td>
                                <td class="small">{{ $ext->extender?->name ?? '-' }}</td>
                                <td class="small">
                                    @switch($ext->action)
                                        @case('grace_enabled') <span class="badge bg-warning text-dark">เปิดใช้ชั่วคราว</span> @break
                                        @case('grace_extended') <span class="badge bg-info">ต่อขยายชั่วคราว</span> @break
                                        @case('grace_stopped') <span class="badge bg-secondary">ปิดโหมดชั่วคราว</span> @break
                                        @case('contract_renewed') <span class="badge bg-success">ต่อสัญญา</span> @break
                                        @default <span class="badge bg-light text-dark">{{ $ext->action }}</span>
                                    @endswitch
                                </td>
                                <td class="small">{{ $ext->previous_end ? $ext->previous_end->format('d/m/Y') : '-' }}</td>
                                <td class="small">{{ $ext->new_end ? $ext->new_end->format('d/m/Y') : '-' }}</td>
                                <td class="small">{{ $ext->days_added !== null ? $ext->days_added . ' วัน' : '-' }}</td>
                                <td class="small">{{ $ext->reason ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- ============= Attachments ============= --}}
<div class="card border-secondary">
    <div class="card-header bg-secondary bg-opacity-10 fw-bold">
        <i class="bi bi-paperclip me-1"></i> ไฟล์แนบสัญญา (3 ช่อง) — PDF, JPG, PNG ไม่เกิน 10 MB
    </div>
    <div class="card-body">
        <div class="row g-3">
            @for($slot = 1; $slot <= 3; $slot++)
                @php
                    $pathAttr = "attachment_{$slot}_path";
                    $origAttr = "attachment_{$slot}_original";
                    $upAttr = "attachment_{$slot}_uploaded_at";
                    $hasFile = $contract && $contract->{$pathAttr};
                @endphp
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="fw-bold mb-2">ไฟล์แนบช่องที่ {{ $slot }}</div>
                        @if($hasFile)
                            <div class="small mb-2">
                                <i class="bi bi-file-earmark-fill text-primary"></i>
                                <a href="{{ route('super-admin.contract.attachment.download', $slot) }}" class="text-decoration-none">
                                    {{ $contract->{$origAttr} }}
                                </a>
                            </div>
                            <div class="text-muted small mb-3">
                                อัพโหลด: {{ $contract->{$upAttr}?->format('d/m/Y H:i') ?? '-' }}
                            </div>
                        @else
                            <div class="text-muted small mb-3">ยังไม่มีไฟล์</div>
                        @endif
                        <form action="{{ route('super-admin.contract.attachment.upload', $slot) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".pdf,.jpg,.jpeg,.png" required>
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="bi bi-upload me-1"></i> {{ $hasFile ? 'เปลี่ยนไฟล์' : 'อัพโหลด' }}
                            </button>
                        </form>
                        @if($hasFile)
                            <form action="{{ route('super-admin.contract.attachment.delete', $slot) }}" method="POST"
                                  class="mt-2"
                                  onsubmit="return confirm('ลบไฟล์แนบช่องที่ {{ $slot }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                    <i class="bi bi-trash"></i> ลบไฟล์
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endfor
        </div>
    </div>
</div>
