<?php

namespace Tests\Feature\Production;

use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\Employer;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function email_edit_fields_have_conditional_visibility()
    {
        $user = User::factory()->create();
        $employer = Employer::factory()->create();
        $employee = Employee::factory()->create(['employer_id' => $employer->id]);

        $order = ProductionOrder::create([
            'employer_id' => $employer->id,
            'status' => 'active',
            'created_by' => $user->id
        ]);

        $item = ProductionItem::create([
            'production_order_id' => $order->id,
            'employee_id' => $employee->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($user)->get(route('workflow.item.card', ['item' => $item->id]));

        $response->assertStatus(200);

        // Response is JSON with 'html' key
        $json = $response->json();
        $this->assertArrayHasKey('html', $json);
        $html = $json['html'];

        // 1. Verify Display Mode Div
        $this->assertStringContainsString('x-show="!isEditing"', $html);
        $this->assertStringContainsString(':class="{ \'d-flex\': !isEditing }"', $html);

        // Ensure the static class attribute does NOT contain d-flex
        // We verify that the string 'class="d-flex align-items-center gap-2"' is NOT present
        // And 'class="align-items-center gap-2"' IS present
        $this->assertStringNotContainsString('class="d-flex align-items-center gap-2"', $html);
        $this->assertStringContainsString('class="align-items-center gap-2"', $html);

        // 2. Verify Edit Mode Div
        $this->assertStringContainsString('x-show="isEditing"', $html);
        $this->assertStringContainsString(':class="{ \'d-flex\': isEditing }"', $html);

        // Ensure the static class attribute does NOT contain d-flex
        // The original was 'class="d-flex flex-column gap-1 p-2 bg-white border rounded shadow-sm"'
        // The new one is 'class="flex-column gap-1 p-2 bg-white border rounded shadow-sm"'
        $this->assertStringNotContainsString('class="d-flex flex-column gap-1 p-2 bg-white border rounded shadow-sm"', $html);
        $this->assertStringContainsString('class="flex-column gap-1 p-2 bg-white border rounded shadow-sm"', $html);
    }
}
