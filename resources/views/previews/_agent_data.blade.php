{{--
    Loaded via AJAX into the universal preview modal.
    No @extends — outputs body content only.
--}}
<div class="content-section">
    <h5>ข้อมูลเอเจนซี่</h5>
    <hr>
    <div class="row mb-3">
        <div class="col-md-12">
            <label class="form-label fw-bold">ชื่อเอเจนซี่ (Agent Name)</label>
            <p class="form-control-plaintext">{{ $agent->agentNameEn ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">License</label>
            <p class="form-control-plaintext">{{ $agent->agentLicense ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">เบอร์โทรศัพท์</label>
            <p class="form-control-plaintext">{{ $agent->agentPhone ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">อีเมล</label>
            <p class="form-control-plaintext">{{ $agent->agentEmail ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label fw-bold">ที่อยู่</label>
        <p class="form-control-plaintext" style="white-space: pre-wrap;">{{ $agent->agentAddress ?? 'N/A' }}</p>
    </div>
</div>
