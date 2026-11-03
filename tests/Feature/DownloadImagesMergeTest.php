<?php

namespace Tests\Feature;

use App\Jobs\ProcessDownload;
use App\Models\DownloadTask;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-03: attachments saved as IMAGES merge into the Advanced Download
 * PDF correctly — every common type (JPG, PNG with transparency, GIF, WEBP,
 * BMP, a big phone photo), one page each, nothing reported missing; and a
 * phone photo that is stored sideways with an EXIF "rotate" tag comes out
 * upright, as the preview shows it.
 */
class DownloadImagesMergeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private array $made = [];

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
        $this->actingAs($this->admin);
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        foreach ($this->made as $path) {
            @unlink($path);
        }
        parent::tearDown();
    }

    private function image(string $type, int $w = 60, int $h = 40, bool $alpha = false): string
    {
        $img = imagecreatetruecolor($w, $h);
        if ($alpha) {
            imagesavealpha($img, true);
            imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
            imagefilledellipse($img, (int) ($w / 2), (int) ($h / 2), (int) ($w / 2), (int) ($h / 2), imagecolorallocate($img, 20, 120, 220));
        } else {
            imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, 220, 60, 60));
        }
        ob_start();
        match ($type) {
            'jpg' => imagejpeg($img, null, 85),
            'png' => imagepng($img),
            'gif' => imagegif($img),
            'webp' => imagewebp($img),
            'bmp' => imagebmp($img),
        };
        imagedestroy($img);

        return ob_get_clean();
    }

    /** A JPEG with an EXIF APP1 segment holding Orientation = $orientation. */
    public static function jpegWithOrientation(string $jpeg, int $orientation): string
    {
        // TIFF header (little endian) + IFD0 with 1 entry: 0x0112 SHORT 1 value
        $tiff = 'II' . pack('v', 42) . pack('V', 8)
            . pack('v', 1)
            . pack('v', 0x0112) . pack('v', 3) . pack('V', 1) . pack('v', $orientation) . pack('v', 0)
            . pack('V', 0);
        $app1 = "Exif\0\0" . $tiff;
        $segment = "\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1;

        return substr($jpeg, 0, 2) . $segment . substr($jpeg, 2); // right after SOI
    }

    private function employeeWith(Employer $er, string $name, string $file, string $bytes): Employee
    {
        $path = 'employee_files/test-img/' . $file;
        Storage::disk('public')->put($path, $bytes);

        return Employee::factory()->create(['employer_id' => $er->id, 'employeeNameEn' => $name, 'employee_doc_1' => $path]);
    }

    public function test_every_common_image_type_merges_as_one_page_each(): void
    {
        $er = Employer::factory()->create();
        $ids = [];
        foreach (['jpg', 'png', 'gif', 'webp', 'bmp'] as $type) {
            $ids[] = $this->employeeWith($er, 'Img ' . strtoupper($type), 'p.' . $type, $this->image($type))->id;
        }
        $ids[] = $this->employeeWith($er, 'Img Alpha', 'alpha.png', $this->image('png', 80, 80, true))->id;
        $ids[] = $this->employeeWith($er, 'Img Big', 'big.jpg', $this->image('jpg', 4000, 3000))->id;
        // a JPG photo saved with the wrong extension still merges
        $ids[] = $this->employeeWith($er, 'Img Wrong Ext', 'wrong.pdf', $this->image('jpg'))->id;

        $task = DownloadTask::create(['user_id' => $this->admin->id, 'type' => 'pdf', 'status' => 'pending']);
        (new ProcessDownload($task->id, $ids, ['passport'], []))->handle();
        $task->refresh();

        $this->assertSame('completed', $task->status, (string) $task->error_message);
        $this->assertNull($task->error_message, 'nothing reported missing');
        $this->assertStringEndsWith('.pdf', $task->file_path);
        $full = storage_path('app/private/' . $task->file_path);
        $this->made[] = $full;
        $this->assertSame(count($ids), (new \setasign\Fpdi\Fpdi())->setSourceFile($full), 'one page per image');
    }

    public function test_phone_photo_with_exif_rotation_is_turned_upright(): void
    {
        $job = new ProcessDownload(0, [], [], []);
        $normalize = (new \ReflectionClass($job))->getMethod('normalizeImage');
        $normalize->setAccessible(true);

        // stored 400×200 (landscape) but tagged "rotate 90° clockwise" → shown 200×400
        $file = tempnam(sys_get_temp_dir(), 'exif_') . '.jpg';
        file_put_contents($file, self::jpegWithOrientation($this->image('jpg', 400, 200), 6));
        $this->made[] = $file;

        $out = $normalize->invoke($job, $file);
        $this->assertNotNull($out);
        $this->made[] = $out;
        [$w, $h] = getimagesize($out);
        $this->assertSame([200, 400], [$w, $h]);

        // no tag → unchanged
        $plain = tempnam(sys_get_temp_dir(), 'plain_') . '.jpg';
        file_put_contents($plain, $this->image('jpg', 400, 200));
        $this->made[] = $plain;
        $out2 = $normalize->invoke($job, $plain);
        $this->made[] = $out2;
        $this->assertSame([400, 200], array_slice(getimagesize($out2), 0, 2));
    }
}
