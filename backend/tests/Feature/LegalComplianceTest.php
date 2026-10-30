<?php

namespace Tests\Feature;

use App\Enums\ConsentType;
use App\Enums\StaffRole;
use App\Models\CarModification;
use App\Models\Consent;
use App\Models\Customer;
use App\Models\LegalDocument;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegalComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoCatalogSeeder::class);
    }

    public function test_shop_details_and_documents_are_public(): void
    {
        Setting::saveShop(['legal_name' => 'ИП Петров Пётр Петрович', 'inn' => '771234567890']);

        $this->getJson('/api/v1/shop')
            ->assertOk()
            ->assertJsonPath('data.details.legal_name', 'ИП Петров Пётр Петрович')
            ->assertJsonCount(6, 'data.documents');

        $html = $this->getJson('/api/v1/legal/privacy')->assertOk()->json('data.html');
        $this->assertStringContainsString('ИП Петров Пётр Петрович', $html);
        $this->assertStringContainsString('ИНН 771234567890', $html);
        // Незаполненный реквизит виден сразу, а не пропадает молча.
        $this->assertStringContainsString('[не заполнено: ОГРН / ОГРНИП]', $html);
        $this->assertStringContainsString('<table>', $html);
    }

    public function test_html_from_settings_is_escaped(): void
    {
        Setting::saveShop(['legal_name' => '<script>alert(1)</script>']);

        $html = $this->getJson('/api/v1/legal/offer')->json('data.html');

        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_editing_document_creates_new_version(): void
    {
        $document = LegalDocument::where('slug', 'personal-data-consent')->firstOrFail();

        $document->update(['title' => 'Новый заголовок']);
        $this->assertSame(1, $document->fresh()->version);

        $document->update(['body' => $document->body."\n\nНовый пункт."]);
        $this->assertSame(2, $document->fresh()->version);
    }

    public function test_registration_requires_personal_data_consent(): void
    {
        $payload = ['name' => 'Анна', 'email' => 'anna@example.com', 'password' => 'secret-pass'];

        $this->postJson('/api/v1/auth/register', $payload)->assertJsonValidationErrors('consent_personal_data');
        $this->postJson('/api/v1/auth/register', [...$payload, 'consent_personal_data' => false])->assertJsonValidationErrors('consent_personal_data');

        $this->postJson('/api/v1/auth/register', [...$payload, 'consent_personal_data' => true])
            ->assertCreated()
            ->assertJsonPath('data.customer.marketing_consent', false);

        $consent = Consent::sole();
        $this->assertSame(ConsentType::PersonalData, $consent->type);
        $this->assertTrue($consent->granted);
        $this->assertSame(1, $consent->document_version);
        $this->assertNotNull($consent->ip);
    }

    public function test_marketing_consent_is_optional_and_revocable(): void
    {
        $token = $this->postJson('/api/v1/auth/register', [
            'name' => 'Анна', 'email' => 'anna@example.com', 'password' => 'secret-pass',
            'consent_personal_data' => true, 'consent_marketing' => true,
        ])->json('data.token');

        $this->withToken($token)->patchJson('/api/v1/auth/me', ['marketing_consent' => false])
            ->assertJsonPath('data.marketing_consent', false);

        $marketing = Consent::where('type', ConsentType::Marketing)->orderBy('id')->pluck('granted')->all();
        $this->assertSame([true, false], $marketing);
    }

    public function test_guest_order_requires_consent(): void
    {
        $this->postJson('/api/v1/orders', $this->orderPayload())->assertJsonValidationErrors('consent_personal_data');

        $number = $this->postJson('/api/v1/orders', [...$this->orderPayload(), 'consent_personal_data' => true])
            ->assertCreated()
            ->json('data.number');
        $this->assertSame(Order::where('number', $number)->value('id'), Consent::sole()->order_id);
    }

    public function test_customer_order_needs_no_repeated_consent(): void
    {
        // Покупатель с аккаунтом дал согласие при регистрации.
        Sanctum::actingAs(Customer::firstOrFail());

        $this->postJson('/api/v1/orders', $this->orderPayload())->assertCreated();
    }

    public function test_account_deletion_keeps_orders_and_logs_withdrawal(): void
    {
        $customer = Customer::create(['name' => 'Анна', 'email' => 'anna@example.com', 'password' => 'secret-pass']);
        $customer->vehicles()->create(['car_modification_id' => CarModification::firstOrFail()->id]);
        Sanctum::actingAs($customer);
        $order = Order::where('number', $this->postJson('/api/v1/orders', $this->orderPayload())->json('data.number'))->firstOrFail();
        $this->assertSame($customer->id, $order->customer_id);

        $this->deleteJson('/api/v1/auth/me', ['password' => 'wrong'])->assertJsonValidationErrors('password');
        $this->deleteJson('/api/v1/auth/me', ['password' => 'secret-pass'])->assertOk();

        $this->assertModelMissing($customer);
        $this->assertDatabaseCount('customer_vehicles', 0);
        $this->assertNull($order->fresh()->customer_id);

        $withdrawal = Consent::sole();
        $this->assertFalse($withdrawal->granted);
        $this->assertNull($withdrawal->customer_id);
    }

    private function orderPayload(): array
    {
        return [
            'items' => [['offer_id' => Offer::where('quantity', '>', 0)->firstOrFail()->id, 'quantity' => 1]],
            'contact_name' => 'Гость',
            'contact_phone' => '+79990000001',
            'delivery_method' => 'pickup',
            'payment_method' => 'on_receipt',
        ];
    }

    public function test_only_admin_edits_shop_details_and_reads_consent_log(): void
    {
        $manager = User::factory()->role(StaffRole::Manager)->create();
        $admin = User::factory()->role(StaffRole::Admin)->create();

        $this->actingAs($manager)->get('/admin/shop-details')->assertForbidden();
        $this->actingAs($manager)->get('/admin/consents')->assertForbidden();
        $this->actingAs($manager)->get('/admin/legal-documents')->assertOk();

        $this->actingAs($admin)->get('/admin/shop-details')->assertOk();
        $this->actingAs($admin)->get('/admin/consents')->assertOk();
    }
}
