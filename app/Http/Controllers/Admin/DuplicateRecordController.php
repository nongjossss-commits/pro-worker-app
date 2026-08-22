<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\DuplicateDataHelper;
use App\Http\Controllers\Controller;

/**
 * Review page for identity-field duplicates already sitting in the
 * database — see DuplicateDataHelper for the live query behind it. Gated
 * the same way as ActivityLogController (role:admin|super-admin +
 * menu:<key>, no dedicated permission), since it's the closest existing
 * precedent for a data-quality admin review page.
 */
class DuplicateRecordController extends Controller
{
    public function index()
    {
        return view('admin.duplicate_records.index', [
            'groups' => DuplicateDataHelper::getGroups(),
        ]);
    }
}
