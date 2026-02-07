<div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cropperModalLabel">{{ __('Crop Image') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body position-relative">
                <!-- Loading Overlay -->
                <div id="cropperLoadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex flex-column justify-content-center align-items-center d-none" style="z-index: 10;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div id="cropperLoadingText" class="mt-2 text-primary fw-bold">{{ __('Processing...') }}</div>
                </div>

                <style>
                    .img-container {
                        max-height: 500px;
                        display: block;
                    }
                    .img-container img {
                        max-width: 100%;
                        display: block;
                    }
                </style>
                <div class="img-container">
                    <img id="imageToCrop" src="" alt="Picture" style="display: block; max-width: 100%;">
                </div>

                <!-- Background Removal Toolbar -->
                <div class="mt-3 border-top pt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold mb-0 text-primary"><i class="bi bi-magic"></i> {{ __('Smart Background Tools') }}</label>
                        <small class="text-muted" style="font-size: 0.8em;">Powered by AI</small>
                    </div>
                    <div class="d-flex gap-2 flex-wrap" id="bgToolbar">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bg-action="original">
                            <i class="bi bi-arrow-counterclockwise"></i> {{ __('Original') }}
                        </button>
                        <button type="button" class="btn btn-outline-dark btn-sm" data-bg-action="transparent">
                            <i class="bi bi-grid-3x3"></i> {{ __('Remove BG') }}
                        </button>
                        <button type="button" class="btn btn-outline-dark btn-sm" data-bg-action="white">
                            <i class="bi bi-square-fill text-white border"></i> {{ __('White BG') }}
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bg-action="blue">
                            <i class="bi bi-square-fill" style="color: #65a5ff;"></i> {{ __('Light Blue BG') }}
                        </button>
                    </div>
                    <div class="text-muted small mt-1">
                        <i class="bi bi-info-circle"></i> {{ __('Select an option to automatically remove and replace the background.') }}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="cropImageBtn">{{ __('Crop & Save') }}</button>
            </div>
        </div>
    </div>
</div>
