<?php
// Mock Request
$request = Illuminate\Http\Request::create('/production/1/transactions', 'POST', [
    'type' => 'installment',
    'amount' => 1000,
    'due_date' => '2025-12-31',
    'notes' => 'Test Installment'
]);

// Mock User (Admin)
$user = App\Models\User::where('role', 'admin')->first() ?? App\Models\User::factory()->create(['role' => 'admin']);
auth()->login($user);

// Create Mock Production Order if needed
$prod = App\Models\ProductionOrder::firstOrCreate(['id' => 1], [
    'project_name' => 'Test Project',
    'employer_id' => 1,
    'status' => 'new'
]);

// Call Controller
$controller = new App\Http\Controllers\FinancialController();
$response = $controller->storeTransaction($request, 1);

echo $response->getContent();
