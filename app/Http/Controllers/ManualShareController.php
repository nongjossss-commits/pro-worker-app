<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Services\ManualShareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Public share links for the user manuals — see ManualShareService.
 * generate()/revoke() are Super Admin only (routes/web.php super-admin group);
 * show() is public and renders nothing but the manual page itself.
 */
class ManualShareController extends Controller
{
    public function generate(Request $request, string $bundle)
    {
        abort_unless(ManualShareService::isBundle($bundle), 404);

        $days = (int) $request->input('expires_in', 30);
        if (! in_array($days, ManualShareService::EXPIRY_OPTIONS, true)) {
            $days = 30;
        }

        ManualShareService::generate($bundle, $days);
        $life = $days > 0 ? "อายุ {$days} วัน" : 'ไม่มีวันหมดอายุ';
        ActivityLogHelper::logAction('update', "สร้างลิงก์แชร์คู่มือ ({$bundle}, {$life}) — ลิงก์เดิม (ถ้ามี) ใช้ไม่ได้แล้ว");

        return redirect()->route('super-admin.settings.index', ['tab' => 'branding'])
            ->with('success', __('Share link created. Anyone with the link can view and print this manual.'));
    }

    public function revoke(string $bundle)
    {
        abort_unless(ManualShareService::isBundle($bundle), 404);

        ManualShareService::revoke($bundle);
        ActivityLogHelper::logAction('update', "ปิดลิงก์แชร์คู่มือ ({$bundle})");

        return redirect()->route('super-admin.settings.index', ['tab' => 'branding'])
            ->with('success', __('Share link turned off. The old link no longer works.'));
    }

    public function show(Request $request, string $token)
    {
        $found = ManualShareService::lookup($token);
        abort_if($found === null, 404);

        // Thai unless the link asks for another language (a visitor has no
        // session locale, and APP_LOCALE may be 'en').
        ManualShareService::applyLang($request->query('lang') ?: 'th');

        if ($found['expired']) {
            return response()
                ->view('manuals.share_expired', [], 410)
                ->header('X-Robots-Tag', 'noindex, nofollow')
                ->header('Cache-Control', 'private, no-store');
        }

        // Lets the bundle views hide anything meant only for the Super Admin
        // (and build language links that stay on this public URL).
        View::share('manualPublic', true);

        return response()
            ->view(ManualShareService::BUNDLES[$found['bundle']])
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('Cache-Control', 'private, no-store');
    }

    /**
     * Super Admin: the manual as ONE self-contained .html file — screenshots
     * and logo embedded as data URIs — to email or hand to a customer. It
     * opens by double-click in any browser, works without the program, and
     * keeps the table of contents, zoomable images and printing.
     */
    public function download(Request $request, string $bundle)
    {
        abort_unless(ManualShareService::isBundle($bundle), 404);

        $lang = array_key_exists($request->query('lang'), ManualShareService::LANGS) ? $request->query('lang') : 'th';
        ManualShareService::applyLang($lang);
        View::share('manualExport', true); // no language links — they would point back at the server

        $html = view(ManualShareService::BUNDLES[$bundle])->render();
        $html = $this->embedImages($html);

        ActivityLogHelper::logAction('export', "ดาวน์โหลดคู่มือเป็นไฟล์ HTML ({$bundle}, {$lang})");

        $filename = "manual-{$bundle}-{$lang}-" . now()->format('Ymd') . '.html';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Replace src="…/images/manuals/…" and src="…/storage/…" with data URIs. */
    protected function embedImages(string $html): string
    {
        $roots = [
            '/images/manuals/' => public_path('images/manuals'),
            '/storage/' => storage_path('app/public'),
        ];

        return preg_replace_callback('/\bsrc="([^"]+)"/i', function ($m) use ($roots) {
            $path = rawurldecode((string) parse_url(html_entity_decode($m[1]), PHP_URL_PATH));
            foreach ($roots as $prefix => $dir) {
                $pos = strpos($path, $prefix);
                if ($pos === false) {
                    continue;
                }
                $base = realpath($dir);
                $file = realpath($dir . DIRECTORY_SEPARATOR . substr($path, $pos + strlen($prefix)));
                // Only files inside the allowed folder, and only images.
                if (! $base || ! $file || ! str_starts_with($file, $base . DIRECTORY_SEPARATOR) || ! is_file($file)) {
                    return $m[0];
                }
                $mime = mime_content_type($file) ?: '';
                if (! str_starts_with($mime, 'image/')) {
                    return $m[0];
                }

                return 'src="data:' . $mime . ';base64,' . base64_encode(file_get_contents($file)) . '"';
            }

            return $m[0];
        }, $html);
    }
}
