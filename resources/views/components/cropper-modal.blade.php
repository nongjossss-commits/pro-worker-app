<div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cropperModalLabel">{{ __('Crop Image') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body position-relative" style="min-height: 500px;">
                <!-- Loading Overlay -->
                <div id="cropperLoadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex flex-column justify-content-center align-items-center d-none" style="z-index: 20;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div id="cropperLoadingText" class="mt-2 text-primary fw-bold">{{ __('Processing...') }}</div>
                    <button type="button" id="cancelProcessingBtn" class="btn btn-danger btn-sm mt-3">
                        <i class="bi bi-x-circle"></i> {{ __('Cancel') }}
                    </button>
                </div>

                <!-- Refine Editor Overlay -->
                <div id="refineEditorContainer" class="position-absolute top-0 start-0 w-100 h-100 bg-white d-flex flex-column d-none" style="z-index: 15;">
                    <!-- Toolbar -->
                    <div class="p-2 border-bottom bg-light d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-2 align-items-center">
                            <span class="fw-bold text-primary"><i class="bi bi-brush"></i> {{ __('Refine Mask') }}</span>
                            <div class="vr mx-2"></div>

                            <!-- Tools -->
                            <div class="btn-group" role="group" aria-label="Refine Tools">
                                <button type="button" class="btn btn-outline-danger btn-sm active" id="refineToolEraser" title="{{ __('Eraser (Manual)') }}">
                                    <i class="bi bi-eraser-fill"></i> {{ __('Erase') }}
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm" id="refineToolRestore" title="{{ __('Restore (Manual)') }}">
                                    <i class="bi bi-pencil-fill"></i> {{ __('Restore') }}
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="refineToolSmart" title="{{ __('Smart Eraser (AI)') }}">
                                    <i class="bi bi-magic"></i> {{ __('Smart Erase') }}
                                </button>
                            </div>

                            <div class="vr mx-2"></div>

                            <!-- Brush Size -->
                            <div class="d-flex align-items-center gap-2" style="width: 150px;">
                                <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                                <input type="range" class="form-range" id="refineBrushSize" min="5" max="100" value="20">
                                <i class="bi bi-circle-fill" style="font-size: 1.2rem;"></i>
                            </div>
                        </div>
                        <div>
                            <button type="button" class="btn btn-secondary btn-sm" id="refineBtnUndo" disabled>
                                <i class="bi bi-arrow-counterclockwise"></i> {{ __('Undo') }}
                            </button>
                        </div>
                    </div>

                    <!-- Canvas Area -->
                    <div class="flex-grow-1 position-relative bg-secondary bg-opacity-10 overflow-hidden d-flex justify-content-center align-items-center" id="refineCanvasWrapper">
                         <!-- Checkerboard Background for Transparency -->
                        <div class="position-absolute w-100 h-100" style="background-image: linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%); background-size: 20px 20px; background-position: 0 0, 0 10px, 10px -10px, -10px 0px; opacity: 0.5; z-index: 0;"></div>

                        <canvas id="refineCanvas" style="z-index: 1; max-width: 100%; max-height: 100%; box-shadow: 0 0 10px rgba(0,0,0,0.1); cursor: crosshair;"></canvas>
                    </div>

                    <!-- Footer -->
                    <div class="p-2 border-top bg-white d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" id="refineBtnCancel">{{ __('Cancel') }}</button>
                        <button type="button" class="btn btn-primary" id="refineBtnSave">{{ __('Apply Changes') }}</button>
                    </div>
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
                    <div class="d-flex gap-2 flex-wrap align-items-center" id="bgToolbar">
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

                        <div class="vr mx-1"></div>

                        <!-- Refine Button -->
                        <button type="button" class="btn btn-warning btn-sm text-dark" id="btnStartRefine">
                            <i class="bi bi-sliders"></i> {{ __('Refine / Edit Mask') }}
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

<!-- Load OpenCV for Smart Eraser -->
<script async src="https://docs.opencv.org/4.x/opencv.js" onload="document.dispatchEvent(new Event('opencv-loaded-cropper'))"></script>
