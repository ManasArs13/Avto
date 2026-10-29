<?php

namespace Tests\Feature;

use App\Models\CarModification;
use App\Models\Customer;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoCatalogSeeder::class);
        $this->customer = Customer::create(['name' => 'Тест', 'email' => 'test@example.com', 'password' => 'secret-pass']);
    }

    public function test_garage_requires_token(): void
    {
        $this->getJson('/api/v1/garage')->assertUnauthorized();
    }

    public function test_add_list_and_remove_vehicle(): void
    {
        Sanctum::actingAs($this->customer);
        $vesta = CarModification::where('engine_code', '21129')->firstOrFail();

        $id = $this->postJson('/api/v1/garage', ['car_modification_id' => $vesta->id, 'vin' => 'xta21180091234567'])
            ->assertCreated()
            ->assertJsonPath('data.modification.model.slug', 'vesta')
            ->assertJsonPath('data.vin', 'XTA21180091234567')
            ->json('data.id');

        // Повторное добавление той же машины не создаёт дубль.
        $this->postJson('/api/v1/garage', ['car_modification_id' => $vesta->id, 'name' => 'Семейная'])->assertOk();
        $this->getJson('/api/v1/garage')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Семейная');

        $this->deleteJson("/api/v1/garage/{$id}")->assertOk();
        $this->getJson('/api/v1/garage')->assertJsonCount(0, 'data');
    }

    public function test_cannot_delete_someone_elses_vehicle(): void
    {
        $other = Customer::create(['name' => 'Другой', 'email' => 'other@example.com', 'password' => 'secret-pass']);
        $vehicle = $other->vehicles()->create(['car_modification_id' => CarModification::firstOrFail()->id]);

        Sanctum::actingAs($this->customer);
        $this->deleteJson("/api/v1/garage/{$vehicle->id}")->assertNotFound();
        $this->assertModelExists($vehicle);
    }

    public function test_invalid_vin_rejected(): void
    {
        Sanctum::actingAs($this->customer);

        $this->postJson('/api/v1/garage', ['car_modification_id' => CarModification::firstOrFail()->id, 'vin' => 'XTA2118009123456O'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('vin');
    }

    public function test_update_profile_and_password(): void
    {
        Sanctum::actingAs($this->customer);

        $this->patchJson('/api/v1/auth/me', ['name' => 'Новое имя', 'phone' => '+79001112233'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Новое имя');

        $this->patchJson('/api/v1/auth/me', ['current_password' => 'wrong', 'password' => 'new-password'])
            ->assertJsonValidationErrors('current_password');

        $this->patchJson('/api/v1/auth/me', ['current_password' => 'secret-pass', 'password' => 'new-password'])->assertOk();
        $this->postJson('/api/v1/auth/login', ['email' => 'test@example.com', 'password' => 'new-password'])->assertOk();
    }
}
