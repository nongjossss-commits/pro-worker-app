<div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalLabel">เพิ่ม/แก้ไขที่อยู่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Add novalidate to prevent browser's default validation --}}
                <form id="addressForm" novalidate>
                    {{-- The form's action and method will be set dynamically via JS --}}
                    @csrf
                    {{-- Hidden input for method spoofing (for PUT requests) --}}
                    <input type="hidden" name="_method" id="addressFormMethod">
                    <input type="hidden" id="address_id" name="id">
                    <input type="hidden" id="addressable_id" name="addressable_id">
                    <input type="hidden" id="addressable_type" name="addressable_type" value="App\Models\Employer">
                    <input type="hidden" id="address_type" name="type">

                    {{-- Row 1: No --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrNo" class="form-label">บ้านเลขที่ (ไทย)</label>
                            <input type="text" class="form-control" id="addrNo" name="addrNo">
                            <div class="invalid-feedback" id="addrNoError"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="addrNoEn" class="form-label">Address No. (EN)</label>
                            <input type="text" class="form-control" id="addrNoEn" name="addrNoEn">
                             <div class="invalid-feedback" id="addrNoEnError"></div>
                        </div>
                    </div>
                    {{-- Row 2: Moo --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrMoo" class="form-label">หมู่ (ไทย)</label>
                            <input type="text" class="form-control" id="addrMoo" name="addrMoo">
                             <div class="invalid-feedback" id="addrMooError"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="addrMooEn" class="form-label">Moo (EN)</label>
                            <input type="text" class="form-control" id="addrMooEn" name="addrMooEn">
                             <div class="invalid-feedback" id="addrMooEnError"></div>
                        </div>
                    </div>
                    {{-- Row 3: Soi --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrSoi" class="form-label">ซอย (ไทย)</label>
                            <input type="text" class="form-control" id="addrSoi" name="addrSoi">
                             <div class="invalid-feedback" id="addrSoiError"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="addrSoiEn" class="form-label">Soi (EN)</label>
                            <input type="text" class="form-control" id="addrSoiEn" name="addrSoiEn">
                             <div class="invalid-feedback" id="addrSoiEnError"></div>
                        </div>
                    </div>
                    {{-- Row 4: Road --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrRoad" class="form-label">ถนน (ไทย)</label>
                            <input type="text" class="form-control" id="addrRoad" name="addrRoad">
                             <div class="invalid-feedback" id="addrRoadError"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="addrRoadEn" class="form-label">Road (EN)</label>
                            <input type="text" class="form-control" id="addrRoadEn" name="addrRoadEn">
                             <div class="invalid-feedback" id="addrRoadEnError"></div>
                        </div>
                    </div>
                    {{-- Row 5: Province --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrProvince" class="form-label">จังหวัด (Thai)</label>
                            <select class="form-select" name="addrProvince" id="addrProvince">
                                <option selected disabled value="">-- เลือกจังหวัด --</option>
                            </select>
                            <div class="invalid-feedback" id="addrProvinceError"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="addrProvinceEn" class="form-label">Province (EN)</label>
                            <input type="text" class="form-control" id="addrProvinceEn" name="addrProvinceEn" readonly>
                        </div>
                    </div>
                    {{-- Row 6: District --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrDistrict" class="form-label">อำเภอ/เขต (Thai)</label>
                            <select class="form-select" name="addrDistrict" id="addrDistrict" disabled>
                                <option selected disabled value="">-- เลือกอำเภอ/เขต --</option>
                            </select>
                             <div class="invalid-feedback" id="addrDistrictError"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="addrDistrictEn" class="form-label">District (EN)</label>
                            <input type="text" class="form-control" id="addrDistrictEn" name="addrDistrictEn" readonly>
                        </div>
                    </div>
                    {{-- Row 7: SubDistrict --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrSubDistrict" class="form-label">ตำบล/แขวง (Thai)</label>
                            <select class="form-select" name="addrSubDistrict" id="addrSubDistrict" disabled>
                                <option selected disabled value="">-- เลือกตำบล/แขวง --</option>
                            </select>
                             <div class="invalid-feedback" id="addrSubDistrictError"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="addrSubDistrictEn" class="form-label">Sub-district (EN)</label>
                            <input type="text" class="form-control" id="addrSubDistrictEn" name="addrSubDistrictEn" readonly>
                        </div>
                    </div>
                     <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrZipCode" class="form-label">รหัสไปรษณีย์</label>
                            <input type="text" class="form-control" id="addrZipCode" name="addrZipCode" readonly>
                             <div class="invalid-feedback" id="addrZipCodeError"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                {{-- Changed to type="submit" and linked to the form --}}
                <button type="submit" class="btn btn-primary" id="saveAddressBtn" form="addressForm">บันทึก</button>
            </div>
        </div>
    </div>
</div>