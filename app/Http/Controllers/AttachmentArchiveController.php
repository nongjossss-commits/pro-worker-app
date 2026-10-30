<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Support\ActivityLogPresenter;
use App\Support\AttachmentArchive;
use Illuminate\Support\Facades\Storage;

/**
 * "Replaced files" of one employee / employer — the old attachments kept for
 * 30 days (App\Support\AttachmentArchive). Only for users with
 * 'view-attachment-archive': Super Admin always, others when a Super Admin
 * ticks it on their user page (routes: middleware can:view-attachment-archive).
 */
class AttachmentArchiveController extends Controller
{
    public function index(string $owner, int $id)
    {
        $model = $this->record($owner, $id);
        $modelName = class_basename(AttachmentArchive::OWNERS[$owner]);

        $files = array_map(function ($f) use ($modelName) {
            $f['label'] = $f['field'] ? ActivityLogPresenter::fieldLabel($modelName, $f['field']) : '-';
            return $f;
        }, AttachmentArchive::files($owner, $id));

        return view('attachment_archive.index', [
            'owner' => $owner,
            'model' => $model,
            'title' => $owner === 'employees'
                ? ($model->getRawOriginal('employeeNameEn') ?: $model->getRawOriginal('employeeNameTh'))
                : ($model->getRawOriginal('employerNameTh') ?: $model->getRawOriginal('employerNameEn')),
            'files' => $files,
        ]);
    }

    public function show(string $owner, int $id, string $file)
    {
        $this->record($owner, $id);
        $path = "attachment_archive/{$owner}/{$id}/" . basename($file);
        abort_unless(AttachmentArchive::belongsTo($path, $owner, $id), 404);

        ActivityLogHelper::logAction('download', 'เปิดดูไฟล์เดิมที่เก็บสำรองไว้ (' . basename($path) . ')',
            AttachmentArchive::OWNERS[$owner], $id, ['archived_file' => $path]);

        return response()->file(Storage::disk('private')->path($path));
    }

    /** The employee / employer (trashed too) — and the user may see it at all (tenancy scopes apply). */
    protected function record(string $owner, int $id)
    {
        abort_unless(isset(AttachmentArchive::OWNERS[$owner]), 404);

        return AttachmentArchive::OWNERS[$owner]::withTrashed()->findOrFail($id);
    }
}
