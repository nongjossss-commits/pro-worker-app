<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RbacStructureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_the_correct_rbac_tables_and_columns()
    {
        $this->assertFalse(Schema::hasTable('roles'));

        $this->artisan('migrate');

        $this->assertTrue(Schema::hasTable('roles'), 'The "roles" table is missing.');
        $this->assertTrue(Schema::hasTable('permissions'), 'The "permissions" table is missing.');
        $this->assertTrue(Schema::hasTable('role_user'), 'The "role_user" pivot table is missing.');
        $this->assertTrue(Schema::hasTable('permission_role'), 'The "permission_role" pivot table is missing.');

        $this->assertTrue(Schema::hasColumns('roles', [
            'id', 'name', 'description', 'created_at', 'updated_at'
        ]), 'The "roles" table is missing required columns.');
    }
}