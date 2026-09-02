<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Self-service editing of "my own" LaborTeamMember.name — the name used on
 * generated reports/documents, deliberately independent of the login's own
 * User.name (which can stay a nickname/pseudonym for sign-in purposes; see
 * LaborTeamMemberController for the Super-Admin-side matching/editing
 * screen this complements). Scoped entirely by auth()->user()->laborTeamMember
 * — no {id} in the route, so there is no way to reach anyone else's record
 * from here even by tampering with the URL.
 */
class LaborMyNameController extends Controller
{
    public function edit(Request $request)
    {
        $member = $request->user()->laborTeamMember;

        abort_unless($member, 403, 'บัญชีนี้ยังไม่ได้ถูกจับคู่กับข้อมูลลูกทีมใด กรุณาติดต่อ Super Admin');

        return view('labor.my-name.edit', compact('member'));
    }

    public function update(Request $request)
    {
        $member = $request->user()->laborTeamMember;

        abort_unless($member, 403, 'บัญชีนี้ยังไม่ได้ถูกจับคู่กับข้อมูลลูกทีมใด กรุณาติดต่อ Super Admin');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $member->update($validated);

        return back()->with('success', 'แก้ไขชื่อเรียบร้อยแล้ว');
    }
}
