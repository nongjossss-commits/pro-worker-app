<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttachmentSizeScanner;
use Illuminate\Http\Request;

/**
 * Menu "ขนาดไฟล์แนบ" — lists uploaded files at or above a size limit
 * (default 10 MB) and which record / field each belongs to. Read-only:
 * the user opens the record and re-uploads a smaller copy.
 */
class AttachmentSizeController extends Controller
{
    public const THRESHOLDS_MB = [5, 10, 20, 50];

    public function index(Request $request, AttachmentSizeScanner $scanner)
    {
        $mb = (int) $request->query('min', 10);
        if (!in_array($mb, self::THRESHOLDS_MB, true)) {
            $mb = 10;
        }

        $result = $scanner->scan($mb * 1024 * 1024, $request->boolean('refresh'));

        return view('admin.attachment_sizes.index', [
            'result' => $result,
            'minMb' => $mb,
            'thresholds' => self::THRESHOLDS_MB,
        ]);
    }
}
