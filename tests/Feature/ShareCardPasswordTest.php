<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Share card (copy text / copy image / save image) — 2026-10-30: the
 * employee's e-mail password goes out only when the user picks "show" in the
 * popup; the page is told whether there is one to ask about, and the log says
 * when it was included.
 */
class ShareCardPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function login(): void
    {
        Role::findOrCreate('super-admin');
        $u = User::factory()->create();
        $u->assignRole('super-admin');
        $this->actingAs($u);
    }

    public function test_password_only_when_asked_for(): void
    {
        $this->login();
        $e = Employee::factory()->create(['employer_id' => Employer::factory()->create()->id, 'password' => 'MailPw#1']);

        $hidden = $this->getJson(route('share-card.show', ['employee', $e->id]))->assertOk();
        $hidden->assertJsonPath('has_password', true);
        $this->assertStringNotContainsString('MailPw#1', $hidden->getContent());

        $shown = $this->getJson(route('share-card.show', ['employee', $e->id]) . '?password=1')->assertOk();
        $this->assertStringContainsString('MailPw#1', $shown->json('text'));
        $this->assertSame('email_password', collect($shown->json('lines'))->last()['key']);
    }

    public function test_no_password_means_nothing_to_ask(): void
    {
        $this->login();
        $e = Employee::factory()->create(['employer_id' => Employer::factory()->create()->id, 'password' => null]);

        $this->getJson(route('share-card.show', ['employee', $e->id]) . '?password=1')
            ->assertOk()->assertJsonPath('has_password', false)
            ->assertJsonMissing(['key' => 'email_password']);
    }

    public function test_the_log_says_when_the_password_went_out(): void
    {
        $this->login();
        $e = Employee::factory()->create(['employer_id' => Employer::factory()->create()->id, 'password' => 'MailPw#1']);

        $this->postJson(route('share-card.log'), ['type' => 'employee', 'id' => $e->id, 'method' => 'copy_text', 'password' => true])->assertOk();
        $this->postJson(route('share-card.log'), ['type' => 'employee', 'id' => $e->id, 'method' => 'copy_text', 'password' => false])->assertOk();

        $logs = ActivityLog::where('action', 'share')->orderBy('id')->get();
        $this->assertStringContainsString('รวมรหัสอีเมล', $logs[0]->description);
        $this->assertTrue($logs[0]->properties['email_password_included']);
        $this->assertStringNotContainsString('รวมรหัสอีเมล', $logs[1]->description);
    }
}
