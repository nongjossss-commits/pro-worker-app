<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RbacStructureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Remove the deprecated "@test" annotation and use the "test" keyword in the method name
     * or use the #[Test] attribute for PHPUnit 10+. For now, we'll rename the method.
     */
    public function test_it_has_the_correct_rbac_tables_and_columns()
    {
        // Since we use RefreshDatabase, the migrations are already run before this test executes.
        // We no longer need to check if the table doesn't exist, or run migrate manually.
        // We just need to assert that the final state is correct.

        $this->assertTrue(Schema::hasTable('roles'), 'The "roles" table is missing.');
        $this->assertTrue(Schema::hasTable('permissions'), 'The "permissions" table is missing.');
        $this->assertTrue(Schema::hasTable('role_user'), 'The "role_user" pivot table is missing.');
        $this->assertTrue(Schema::hasTable('permission_role'), 'The "permission_role" pivot table is missing.');

        $this->assertTrue(Schema::hasColumns('roles', [
            'id', 'name', 'description', 'created_at', 'updated_at'
        ]), 'The "roles" table is missing required columns.');
    }
}