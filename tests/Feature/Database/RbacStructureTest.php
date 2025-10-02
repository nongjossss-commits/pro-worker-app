<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RbacStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_the_correct_spatie_permission_tables()
    {
        // Assert that the tables from the Spatie package migrations exist.
        $this->assertTrue(Schema::hasTable('roles'), 'The "roles" table is missing.');
        $this->assertTrue(Schema::hasTable('permissions'), 'The "permissions" table is missing.');
        $this->assertTrue(Schema::hasTable('model_has_permissions'), 'The "model_has_permissions" table is missing.');
        $this->assertTrue(Schema::hasTable('model_has_roles'), 'The "model_has_roles" table is missing.');
        $this->assertTrue(Schema::hasTable('role_has_permissions'), 'The "role_has_permissions" table is missing.');

        // Assert that the 'roles' table has the columns defined by the Spatie package.
        $this->assertTrue(Schema::hasColumns('roles', [
            'id', 'name', 'guard_name', 'created_at', 'updated_at'
        ]), 'The "roles" table is missing required columns.');
    }
}