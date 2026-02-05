<div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cropperModalLabel">
                    <i class="bi bi-crop"></i> จัดการรูปภาพพนักงาน
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body position-relative">
                {{-- Loading Overlay --}}
                <div id="cropperLoading" class="position-absolute top-0 start-0 w-100 h-100 d-none flex-column align-items-center justify-content-center bg-white bg-opacity-75" style="z-index: 10;">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                    <h5 class="text-dark" id="cropperLoadingText">กำลังประมวลผล AI...</h5>
                    <small class="text-muted">กรุณารอสักครู่ (อาจใช้เวลา 5-10 วินาที)</small>
                </div>

                <style>
                    .img-container {
                        max-height: 500px;
                        min-height: 300px;
                        display: block;
                        background-color: #f8f9fa;
                    }
                    .img-container img {
                        max-width: 100%;
                        display: block;
                    }
                </style>
                <div class="img-container">
                    <img id="imageToCrop" src="" alt="Picture" style="display: block; max-width: 100%;">
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                {{-- Left Side: AI Tools --}}
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group dropup">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="btnRemoveBg">
                            <i class="bi bi-magic"></i> ลบพื้นหลัง (AI)
                        </button>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header">เลือกสีพื้นหลัง</h6></li>
                            <li><button class="dropdown-item" type="button" onclick="window.cropperManager.removeBackground('transparent')"><i class="bi bi-grid-3x3"></i> โปร่งใส (Transparent)</button></li>
                            <li><button class="dropdown-item" type="button" onclick="window.cropperManager.removeBackground('white')"><i class="bi bi-square-fill text-white border"></i> สีขาว (White)</button></li>
                            <li><button class="dropdown-item" type="button" onclick="window.cropperManager.removeBackground('blue')"><i class="bi bi-square-fill text-info"></i> สีฟ้าอ่อน (Light Blue)</button></li>
                        </ul>
                    </div>
                </div>

                {{-- Right Side: Actions --}}
                <div>
                    <button type="button" class="btn btn-secondary me-1" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="cropImageBtn">
                        <i class="bi bi-check-lg"></i> บันทึกรูปภาพ
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
