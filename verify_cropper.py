from playwright.sync_api import sync_playwright
import os

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    # Define the HTML content mocking the blade template logic
    html_content = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cropper Test</title>
        <!-- Bootstrap 5 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Cropper CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" crossorigin="anonymous" />
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
    </head>
    <body>
        <div class="container mt-5">
            <h1>Cropper Test Page</h1>

            <!-- Trigger Buttons -->
            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-trigger-file" onclick="document.getElementById('triggerFile').click();">Select File</button>
            <img id="employeePhotoPreview" src="" class="img-thumbnail mb-3" style="width: 150px; height: 180px; object-fit: cover;">

            <!-- Inputs -->
            <input type="file" class="d-none" id="triggerFile" accept="image/*">
            <input type="file" class="d-none" id="employeePhotoInput" name="employeePhoto">

            <!-- Modal -->
            <div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="cropperModalLabel">ครอบตัดรูปภาพ</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="img-container">
                                <img id="imageToCrop" src="" alt="Picture" style="display: block; max-width: 100%;">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="button" class="btn btn-primary" id="cropImageBtn">ครอบตัดและบันทึก</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Cropper JS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js" crossorigin="anonymous"></script>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cropperModal = new bootstrap.Modal(document.getElementById('cropperModal'));
            const imageToCrop = document.getElementById('imageToCrop');
            const cropImageBtn = document.getElementById('cropImageBtn');
            const employeePhotoPreview = document.getElementById('employeePhotoPreview');
            const actualInput = document.getElementById('employeePhotoInput');
            const triggerFileInput = document.getElementById('triggerFile');
            let cropper;
            let originalFile;

            function handleFileSelect(event) {
                if (event.target.files && event.target.files.length > 0) {
                    originalFile = event.target.files[0];
                } else {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    imageToCrop.src = e.target.result;
                    cropperModal.show();
                };
                reader.readAsDataURL(originalFile);
                event.target.value = '';
            }

            document.getElementById('cropperModal').addEventListener('shown.bs.modal', function () {
                if (imageToCrop.complete) {
                    initCropper();
                } else {
                    imageToCrop.onload = initCropper;
                }
            });

            function initCropper() {
                if (typeof Cropper === 'undefined') {
                    console.error('Cropper.js is not loaded.');
                    // For test purposes, let's create a fake cropper object if lib is missing but we want to test logic flow?
                    // No, we want to know if it fails.
                    document.body.setAttribute('data-cropper-loaded', 'false');
                    return;
                }
                document.body.setAttribute('data-cropper-loaded', 'true');

                if (cropper) {
                    cropper.destroy();
                }
                cropper = new Cropper(imageToCrop, {
                    aspectRatio: 150 / 180,
                    viewMode: 1,
                    dragMode: 'move',
                    background: false,
                    autoCropArea: 0.8,
                    movable: true,
                    zoomable: true,
                    rotatable: true,
                    scalable: true,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    ready: function() {
                        document.body.setAttribute('data-cropper-ready', 'true');
                    }
                });
            }

            document.getElementById('cropperModal').addEventListener('hidden.bs.modal', function () {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                imageToCrop.src = '';
            });

            cropImageBtn.addEventListener('click', function () {
                if (!cropper) return;

                const canvas = cropper.getCroppedCanvas({
                    width: 300,
                    height: 360,
                    imageSmoothingQuality: 'high',
                });

                canvas.toBlob(function (blob) {
                    if (!blob) return;

                    const croppedImageUrl = URL.createObjectURL(blob);
                    employeePhotoPreview.src = croppedImageUrl;

                    const croppedFile = new File([blob], originalFile.name, {
                        type: originalFile.type || 'image/jpeg',
                        lastModified: Date.now()
                    });

                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(croppedFile);

                    actualInput.files = dataTransfer.files;

                    document.body.setAttribute('data-file-saved', 'true');

                    cropperModal.hide();

                }, originalFile.type || 'image/jpeg');
            });

            if (triggerFileInput) triggerFileInput.addEventListener('change', handleFileSelect);
        });
        </script>
    </body>
    </html>
    """

    page.set_content(html_content)

    # 1. Trigger file upload
    with page.expect_file_chooser() as fc_info:
        page.click("#btn-trigger-file")
    file_chooser = fc_info.value
    file_chooser.set_files("test_image.jpg")

    # 2. Wait for modal to show
    page.wait_for_selector("#cropperModal.show")
    print("Modal shown.")

    # 3. Wait for cropper to be ready
    # We use a data attribute set in initCropper to verify execution
    try:
        page.wait_for_selector("body[data-cropper-loaded='true']", timeout=5000)
        print("Cropper library loaded and init called.")
    except:
        print("Cropper library failed to load or init.")
        page.screenshot(path="verification_failure_cropper_load.png")
        # Depending on environment, CDNs might be blocked.
        # But we proceed to see what happens.

    try:
        page.wait_for_selector("body[data-cropper-ready='true']", timeout=5000)
        print("Cropper is ready.")
    except:
        print("Cropper did not trigger ready event.")

    # Take a screenshot of the modal with the cropper
    page.screenshot(path="verification_cropper_modal.png")
    print("Screenshot of cropper modal taken.")

    # 4. Click Save
    page.click("#cropImageBtn")

    # 5. Wait for save logic
    try:
        page.wait_for_selector("body[data-file-saved='true']", timeout=2000)
        print("File saved logic executed.")
    except:
        print("File save logic failed or timed out.")

    # 6. Verify input has file
    file_count = page.evaluate("document.getElementById('employeePhotoInput').files.length")
    print(f"Files in input: {file_count}")

    if file_count == 1:
        print("SUCCESS: File input populated.")
    else:
        print("FAILURE: File input empty.")

    page.screenshot(path="verification_final.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
