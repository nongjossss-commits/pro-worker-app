<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttachmentSizeScanner;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Menu "ขนาดไฟล์แนบ" — lists uploaded files at or above a size limit
 * (default 10 MB) and which record / field each belongs to. Read-only:
 * the user opens the record and re-uploads a smaller copy.
 *
 * index() returns the page frame at once; the list (scan + one page of
 * results) is loaded by the page from list(), so a slow first scan shows a
 * loading state instead of the whole page timing out.
 */
class AttachmentSizeController extends Controller
{
    public const THRESHOLDS_MB = [5, 10, 20, 50];
    public const PER_PAGE = [20, 30, 50, 100];
    public const VIEWS = ['owned', 'orphans'];

    public function index(Request $request)
    {
        return view('admin.attachment_sizes.index', $this->options($request) + [
            'thresholds' => self::THRESHOLDS_MB,
            'perPageOptions' => self::PER_PAGE,
        ]);
    }

    public function list(Request $request, AttachmentSizeScanner $scanner)
    {
        // First scan of a large storage folder can take a while.
        @set_time_limit(300);

        $o = $this->options($request);
        $result = $scanner->scan($o['minMb'] * 1024 * 1024, $request->boolean('refresh'));

        $owned = array_values(array_filter($result['files'], fn ($f) => !empty($f['owners'])));
        $orphans = array_values(array_filter($result['files'], fn ($f) => empty($f['owners'])));
        $view = $o['view'];
        // Nothing attached to records → open the other tab instead of an empty one.
        if ($view === 'owned' && !$owned && $orphans && !$request->filled('view')) {
            $view = 'orphans';
        }
        $all = $view === 'owned' ? $owned : $orphans;

        $page = max(1, (int) $request->query('page', 1));
        $lastPage = max(1, (int) ceil(count($all) / $o['perPage']));
        $page = min($page, $lastPage);
        $items = $scanner->withDimensions(array_slice($all, ($page - 1) * $o['perPage'], $o['perPage']));

        $paginator = new LengthAwarePaginator($items, count($all), $o['perPage'], $page, [
            'path' => route('admin.attachment-sizes.index'),
            'query' => ['min' => $o['minMb'], 'per_page' => $o['perPage'], 'view' => $view],
        ]);

        return view('admin.attachment_sizes._list', [
            'result' => $result,
            'minMb' => $o['minMb'],
            'perPage' => $o['perPage'],
            'view' => $view,
            'ownedCount' => count($owned),
            'orphanCount' => count($orphans),
            'files' => $paginator,
        ]);
    }

    protected function options(Request $request): array
    {
        $mb = (int) $request->query('min', 10);
        $perPage = (int) $request->query('per_page', 20);
        $view = (string) $request->query('view', 'owned');

        return [
            'minMb' => in_array($mb, self::THRESHOLDS_MB, true) ? $mb : 10,
            'perPage' => in_array($perPage, self::PER_PAGE, true) ? $perPage : 20,
            'view' => in_array($view, self::VIEWS, true) ? $view : 'owned',
        ];
    }
}
