<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\SuperAdminSetting;
use App\Models\User;
use App\Services\ManualShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManualShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_link_with_expiry_opens_until_it_expires(): void
    {
        $token = ManualShareService::generate('main', 7);

        $this->get(route('manuals.public', $token))
            ->assertOk()
            ->assertSee('mvTocBtn', false); // interactive viewer included

        $this->travel(8)->days();

        $this->get(route('manuals.public', $token))
            ->assertStatus(410)
            ->assertSee(__('This manual link has expired'));
        $this->assertNull(ManualShareService::bundleForToken($token));
    }

    public function test_link_without_expiry_and_legacy_bare_token_keep_working(): void
    {
        $token = ManualShareService::generate('finance', 0);
        $this->travel(400)->days();
        $this->get(route('manuals.public', $token))->assertOk();

        // Links created before expiry existed were stored as a bare token.
        $legacy = str_repeat('a', 40);
        SuperAdminSetting::updateOrCreate(['key' => 'manual.share.training'], ['value' => $legacy]);
        $this->assertSame('training', ManualShareService::bundleForToken($legacy));
        $this->assertNull(ManualShareService::link('training')['expires_at']);
    }

    public function test_unknown_or_revoked_token_is_404(): void
    {
        $token = ManualShareService::generate('main', 30);
        ManualShareService::revoke('main');

        $this->get(route('manuals.public', $token))->assertNotFound();
        $this->get(route('manuals.public', str_repeat('b', 40)))->assertNotFound();
    }

    public function test_generate_stores_the_chosen_expiry(): void
    {
        Role::create(['name' => 'super-admin']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->withSession(['menu_unlocked' => true])
            ->post(route('super-admin.manuals.share.generate', 'main'), ['expires_in' => 90])
            ->assertRedirect();

        $link = ManualShareService::link('main');
        $this->assertNotNull($link);
        $this->assertEqualsWithDelta(90, now()->diffInDays($link['expires_at']), 1);

        // Anything outside the offered options falls back to 30 days.
        $this->actingAs($user)->post(route('super-admin.manuals.share.generate', 'main'), ['expires_in' => 5000]);
        $this->assertEqualsWithDelta(30, now()->diffInDays(ManualShareService::link('main')['expires_at']), 1);

        // The share panel shows how long the link lasts, then that it expired.
        $panel = fn () => view('super-admin.partials._manual_share', ['bundle' => 'main'])->render();
        $this->assertStringContainsString(ManualShareService::link('main')['expires_at']->format('d/m/Y'), $panel());
        $this->travel(31)->days();
        $this->assertStringContainsString(__('Expired'), $panel());
    }

    public function test_download_is_a_standalone_html_file_with_embedded_images(): void
    {
        Role::create(['name' => 'super-admin']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)
            ->get(route('super-admin.manuals.download', ['bundle' => 'training', 'lang' => 'en']));

        $response->assertOk();
        $this->assertStringContainsString('attachment; filename="manual-training-en-', $response->headers->get('Content-Disposition'));
        $html = $response->getContent();
        $this->assertStringContainsString('src="data:image/png;base64,', $html);
        $this->assertStringNotContainsString('/images/manuals/', $html);
        $this->assertStringNotContainsString('manual-lang-switch"', $html);
    }

    public function test_download_requires_super_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('super-admin.manuals.download', ['bundle' => 'main']))
            ->assertStatus(403);
    }
}
