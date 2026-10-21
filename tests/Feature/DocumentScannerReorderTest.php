<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Document scanner review screen ("ตรวจสอบเอกสาร"):
 *  - pages can be dragged to a new position right there (mouse / press-and-hold);
 *  - while the numbered sort mode is open, Save is locked until ✓ Confirm.
 * The interaction itself is checked in the browser (see DEVELOPMENT_LOG);
 * this guards the wiring.
 */
class DocumentScannerReorderTest extends TestCase
{
    public function test_drag_to_move_and_save_lock_are_wired(): void
    {
        $view = file_get_contents(resource_path('views/components/document-scanner.blade.php'));
        $js = file_get_contents(public_path('js/document-scanner.js'));

        $this->assertTrue(str_contains($view, ':data-scan-idx="index"'), 'cards carry their position');
        $this->assertTrue(str_contains($view, '@pointerdown="dragPointerDown($event, index)"'), 'cards start a drag');
        $this->assertTrue(str_contains($view, ':disabled="!canFinish() || isSorting"'), 'save locked in sort mode');

        $this->assertTrue(str_contains($js, 'dragPointerDown(e, index)'), 'drag handler');
        $this->assertTrue(str_contains($js, 'movePage(from, to)'), 'move keeps the others in order');
        $this->assertTrue(str_contains($js, "if (this.isSorting) {\n                    if (window.appToast)"), 'finalize refuses while sorting');
        $this->assertTrue(str_contains($js, "ghost.setAttribute('x-ignore', '')"), 'drag picture is not evaluated by Alpine');
    }

    public function test_scanner_pdf_keeps_full_quality_and_oversized_uploads_are_stopped(): void
    {
        // Pages go into the PDF at full quality (owner's decision: QR codes / small text must stay sharp).
        $js = file_get_contents(public_path('js/document-scanner.js'));
        $this->assertTrue(str_contains($js, 'const imgData = this.capturedImages[i].cropped;'), 'full-quality pages');
        $this->assertFalse(str_contains($js, 'shrinkForPdf'), 'no down-scaling');

        $guard = file_get_contents(public_path('js/upload-size-guard.js'));
        $this->assertTrue(str_contains($guard, 'e.preventDefault();'), 'guard stops the submit');
        foreach (['views/layouts/app.blade.php', 'views/labor/layout.blade.php'] as $layout) {
            $src = file_get_contents(resource_path($layout));
            $this->assertTrue(str_contains($src, "ini_get('upload_max_filesize')") && str_contains($src, 'js/upload-size-guard.js'), "$layout passes the limits and loads the guard");
        }
    }
}
