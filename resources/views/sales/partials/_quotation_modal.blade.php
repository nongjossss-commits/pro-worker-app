{{-- resources/views/sales/partials/_quotation_modal.blade.php --}}
<div class="modal fade" id="quotationModal-{{ $lead->id }}" tabindex="-1" aria-labelledby="quotationModalLabel-{{ $lead->id }}" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="quotationModalLabel-{{ $lead->id }}">
                    <i class="bi bi-file-earmark-text me-2"></i> สร้างใบเสนอราคา (Quotation) - {{ $lead->employerNameTh }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i> เมื่อลูกค้าคอนเฟิร์ม ใบเสนอราคานี้จะถูกแปลงเป็นรายการบิลในเมนู "การเงิน (Finance)" หลักโดยอัตโนมัติ
                </div>

                {{-- Mock Finance UI for Sales Lead --}}
                <form id="financeForm-{{ $lead->id }}" action="{{ route('sales.quotation.store', $lead->id) }}" method="POST">
                    @csrf
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">โปรไฟล์การเงิน <span class="text-danger">*</span></label>
                            <select class="form-select" name="financial_profile_id" required>
                                <option value="">-- เลือกโปรไฟล์/บริษัทผู้ออกบิล --</option>
                                @foreach($profiles as $profile)
                                    <option value="{{ $profile->id }}" {{ ($lead->quotation && $lead->quotation->financial_profile_id == $profile->id) ? 'selected' : '' }}>{{ $profile->company_name_th }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">วันที่เสนอราคา</label>
                            <input type="date" class="form-control" name="quotation_date" value="{{ $lead->quotation ? $lead->quotation->quotation_date->format('Y-m-d') : date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">เงื่อนไขการชำระเงิน</label>
                            <input type="text" class="form-control" name="payment_terms" value="{{ $lead->quotation->payment_terms ?? '' }}" placeholder="เช่น เงินสด 100%, แบ่งจ่าย">
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i>รายการเสนอราคา</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addQuoteItem({{ $lead->id }}, {{ $lead->employees->count() ?: 1 }})">
                                <i class="bi bi-plus-circle"></i> เพิ่มรายการ
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover align-middle mb-0" id="quoteItemsTable-{{ $lead->id }}">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40%">รายละเอียดงาน</th>
                                        <th style="width: 15%">จำนวน</th>
                                        <th style="width: 20%">ราคาต่อหน่วย (บาท)</th>
                                        <th style="width: 20%">รวม (บาท)</th>
                                        <th style="width: 5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($lead->quotation && $lead->quotation->items_data)
                                        @foreach($lead->quotation->items_data as $index => $item)
                                        <tr>
                                            <td><input type="text" class="form-control form-control-sm" name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" required></td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" class="form-control text-end quote-qty" name="items[{{ $index }}][qty]" value="{{ $item['qty'] ?? 1 }}" min="1" oninput="calcQuoteRow(this)">
                                                    <span class="input-group-text">คน</span>
                                                </div>
                                            </td>
                                            <td><input type="number" class="form-control form-control-sm text-end quote-price" name="items[{{ $index }}][price]" value="{{ $item['price'] ?? 0 }}" min="0" oninput="calcQuoteRow(this)"></td>
                                            <td><input type="text" class="form-control form-control-sm text-end quote-total" name="items[{{ $index }}][total]" value="{{ number_format(($item['qty'] ?? 1) * ($item['price'] ?? 0), 2) }}" readonly></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove(); calcQuoteGrandTotal({{ $lead->id }});"><i class="bi bi-trash"></i></button></td>
                                        </tr>
                                        @endforeach
                                    @else
                                        {{-- Default row --}}
                                        <tr>
                                            <td><input type="text" class="form-control form-control-sm" name="items[0][description]" placeholder="เช่น ค่าดำเนินการนำเข้า" required></td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" class="form-control text-end quote-qty" name="items[0][qty]" value="{{ $lead->employees->count() ?: 1 }}" min="1" oninput="calcQuoteRow(this)">
                                                    <span class="input-group-text">คน</span>
                                                </div>
                                            </td>
                                            <td><input type="number" class="form-control form-control-sm text-end quote-price" name="items[0][price]" value="0" min="0" oninput="calcQuoteRow(this)"></td>
                                            <td><input type="text" class="form-control form-control-sm text-end quote-total" name="items[0][total]" value="0.00" readonly></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove(); calcQuoteGrandTotal({{ $lead->id }});"><i class="bi bi-trash"></i></button></td>
                                        </tr>
                                    @endif
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">ยอดรวมสุทธิ:</td>
                                        <td class="fw-bold fs-5 text-end text-primary" id="quoteGrandTotal-{{ $lead->id }}">{{ number_format($lead->quotation->grand_total ?? 0, 2) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> บันทึกใบเสนอราคา
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

@push('scripts')
@once
<script>
    let quoteItemCounter = 1;

    function addQuoteItem(leadId, numEmployees) {
        const tbody = document.querySelector(`#quoteItemsTable-${leadId} tbody`);
        const tr = document.createElement('tr');

        // numEmployees passed as arg instead of relying on blade directive that only captures the last lead in the loop
        numEmployees = numEmployees || 1;

        tr.innerHTML = `
            <td><input type="text" class="form-control form-control-sm" name="items[${quoteItemCounter}][description]" placeholder="ระบุรายละเอียดงาน" required></td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" class="form-control text-end quote-qty" name="items[${quoteItemCounter}][qty]" value="${numEmployees}" min="1" oninput="calcQuoteRow(this)">
                    <span class="input-group-text">คน</span>
                </div>
            </td>
            <td><input type="number" class="form-control form-control-sm text-end quote-price" name="items[${quoteItemCounter}][price]" value="0" min="0" oninput="calcQuoteRow(this)"></td>
            <td><input type="text" class="form-control form-control-sm text-end quote-total" name="items[${quoteItemCounter}][total]" value="0.00" readonly></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove(); calcQuoteGrandTotal(${leadId});"><i class="bi bi-trash"></i></button></td>
        `;
        tbody.appendChild(tr);
        quoteItemCounter++;
    }

    function calcQuoteRow(input) {
        const tr = input.closest('tr');
        const qty = parseFloat(tr.querySelector('.quote-qty').value) || 0;
        const price = parseFloat(tr.querySelector('.quote-price').value) || 0;
        const totalInput = tr.querySelector('.quote-total');

        const total = qty * price;
        totalInput.value = total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        const leadId = input.closest('table').id.replace('quoteItemsTable-', '');
        calcQuoteGrandTotal(leadId);
    }

    function calcQuoteGrandTotal(leadId) {
        const table = document.getElementById(`quoteItemsTable-${leadId}`);
        const totalInputs = table.querySelectorAll('.quote-total');
        let grandTotal = 0;

        totalInputs.forEach(input => {
            const val = parseFloat(input.value.replace(/,/g, '')) || 0;
            grandTotal += val;
        });

        document.getElementById(`quoteGrandTotal-${leadId}`).innerText = grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
</script>
@endonce
@endpush