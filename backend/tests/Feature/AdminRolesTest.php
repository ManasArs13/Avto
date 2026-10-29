<?php

namespace Tests\Feature;

use App\Enums\StaffRole;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoCatalogSeeder::class);
    }

    public function test_viewer_can_browse_but_not_edit(): void
    {
        $viewer = User::factory()->role(StaffRole::Viewer)->create();
        $product = Product::firstOrFail();

        $this->actingAs($viewer)->get('/admin/products')->assertOk();
        $this->actingAs($viewer)->get("/admin/products/{$product->id}")->assertOk();
        $this->actingAs($viewer)->get("/admin/products/{$product->id}/edit")->assertForbidden();
        $this->actingAs($viewer)->get('/admin/products/create')->assertForbidden();
        $this->actingAs($viewer)->get('/admin/users')->assertForbidden();
    }

    public function test_manager_edits_catalog_but_not_staff(): void
    {
        $manager = User::factory()->role(StaffRole::Manager)->create();
        $product = Product::firstOrFail();

        $this->actingAs($manager)->get("/admin/products/{$product->id}/edit")->assertOk();
        $this->actingAs($manager)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_manages_staff(): void
    {
        $admin = User::factory()->role(StaffRole::Admin)->create();

        $this->actingAs($admin)->get('/admin/users')->assertOk();
    }

    public function test_staff_role_command(): void
    {
        $user = User::factory()->create();

        $this->artisan('staff:role', ['email' => $user->email, 'role' => 'manager'])->assertSuccessful();
        $this->assertSame(StaffRole::Manager, $user->fresh()->role);

        $this->artisan('staff:role', ['email' => $user->email, 'role' => 'boss'])->assertFailed();
    }
}
