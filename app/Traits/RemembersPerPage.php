<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * "Items per page" that sticks: a ?per_page= pick is saved in a long-lived
 * cookie for that list, so this browser keeps showing that many items after
 * refreshes and new visits until the user picks another number.
 * Per browser on purpose — a big screen and a slow laptop can differ.
 */
trait RemembersPerPage
{
    protected function rememberedPerPage(Request $request, string $list, array $options, int $default): int
    {
        $cookie = 'pw_per_page_' . $list;
        $picked = (int) $request->input('per_page');

        if (in_array($picked, $options, true)) {
            Cookie::queue($cookie, (string) $picked, 60 * 24 * 365 * 5);   // 5 years

            return $picked;
        }

        $saved = (int) $request->cookie($cookie);
        if (in_array($saved, $options, true)) {
            // Browsers cap cookie lifetime (Chrome: 400 days) — renew it on every visit.
            Cookie::queue($cookie, (string) $saved, 60 * 24 * 365 * 5);

            return $saved;
        }

        return $default;
    }
}
