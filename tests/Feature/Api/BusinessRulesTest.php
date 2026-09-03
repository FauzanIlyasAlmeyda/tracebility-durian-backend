<?php

namespace Tests\Feature\Api;

use App\Enums\HarvestBatchStatus;
use App\Enums\ShipmentDestination;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Models\CollectorShipment;
use App\Models\CollectorShipmentSource;
use App\Models\Farm;
use App\Models\HarvestBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_collector_cannot_package_unverified_batch(): void
    {
        $collector = User::factory()->create([
            'first_name' => 'Andi',
            'last_name' => 'Pengepul',
            'email' => 'collector@example.com',
            'phone' => '081200000010',
            'role' => UserRole::Pengepul->value,
            'password' => 'password123',
        ]);

        $farmer = User::factory()->create([
            'first_name' => 'Budi',
            'last_name' => 'Petani',
            'email' => 'farmer@example.com',
            'phone' => '081200000011',
            'role' => UserRole::Petani->value,
            'password' => 'password123',
        ]);

        $farm = Farm::create([
            'farmer_user_id' => $farmer->id,
            'name' => 'Kebun Durian',
            'city' => 'Jember',
            'province' => 'Jawa Timur',
        ]);

        $batch = HarvestBatch::create([
            'code' => 'DRN-2026-100001',
            'farmer_user_id' => $farmer->id,
            'farm_id' => $farm->id,
            'farm_name_snapshot' => $farm->name,
            'variety' => 'Montong',
            'grade' => 'A',
            'quantity_kg' => 100,
            'unit' => 'kg',
            'fruit_count' => 40,
            'status' => HarvestBatchStatus::Created->value,
        ]);

        $response = $this->withToken($collector->createToken('testing')->plainTextToken)
            ->postJson('/api/collector/shipment-batches', [
                'destination_type' => ShipmentDestination::Distributor->value,
                'source_batch_codes' => [$batch->code],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_umkm_product_creation_marks_source_batch_as_processed(): void
    {
        $umkm = User::factory()->create([
            'first_name' => 'Rina',
            'last_name' => 'UMKM',
            'email' => 'umkm@example.com',
            'phone' => '081200000012',
            'role' => UserRole::Umkm->value,
            'password' => 'password123',
        ]);

        $farmer = User::factory()->create([
            'first_name' => 'Budi',
            'last_name' => 'Petani',
            'email' => 'farmer2@example.com',
            'phone' => '081200000013',
            'role' => UserRole::Petani->value,
            'password' => 'password123',
        ]);

        $farm = Farm::create([
            'farmer_user_id' => $farmer->id,
            'name' => 'Kebun Rasa',
            'city' => 'Jember',
            'province' => 'Jawa Timur',
        ]);

        $batch = HarvestBatch::create([
            'code' => 'DRN-2026-100002',
            'farmer_user_id' => $farmer->id,
            'farm_id' => $farm->id,
            'farm_name_snapshot' => $farm->name,
            'variety' => 'Montong',
            'grade' => 'A',
            'quantity_kg' => 50,
            'unit' => 'kg',
            'fruit_count' => 20,
            'status' => HarvestBatchStatus::ReceivedByUmkm->value,
        ]);

        $response = $this->withToken($umkm->createToken('testing')->plainTextToken)
            ->postJson('/api/umkm/products', [
                'name' => 'Paket Durian Premium',
                'category' => 'Paket',
                'price' => 150000,
                'stock_qty' => 10,
                'description' => 'Produk olahan durian.',
                'source_codes' => [$batch->code],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Paket Durian Premium');

        $this->assertDatabaseHas('harvest_batches', [
            'code' => $batch->code,
            'status' => HarvestBatchStatus::Processed->value,
        ]);
    }

    public function test_distributor_receipt_requires_sent_shipment(): void
    {
        $collector = User::factory()->create([
            'first_name' => 'Andi',
            'last_name' => 'Pengepul',
            'email' => 'collector2@example.com',
            'phone' => '081200000014',
            'role' => UserRole::Pengepul->value,
            'password' => 'password123',
        ]);

        $distributor = User::factory()->create([
            'first_name' => 'Siti',
            'last_name' => 'Distributor',
            'email' => 'distributor@example.com',
            'phone' => '081200000015',
            'role' => UserRole::Distributor->value,
            'password' => 'password123',
        ]);

        $farmer = User::factory()->create([
            'first_name' => 'Budi',
            'last_name' => 'Petani',
            'email' => 'farmer3@example.com',
            'phone' => '081200000016',
            'role' => UserRole::Petani->value,
            'password' => 'password123',
        ]);

        $farm = Farm::create([
            'farmer_user_id' => $farmer->id,
            'name' => 'Kebun Pagi',
            'city' => 'Jember',
            'province' => 'Jawa Timur',
        ]);

        $batch = HarvestBatch::create([
            'code' => 'DRN-2026-100003',
            'farmer_user_id' => $farmer->id,
            'farm_id' => $farm->id,
            'farm_name_snapshot' => $farm->name,
            'variety' => 'Montong',
            'grade' => 'A',
            'quantity_kg' => 20,
            'unit' => 'kg',
            'fruit_count' => 8,
            'status' => HarvestBatchStatus::InDistribution->value,
        ]);

        $shipment = CollectorShipment::create([
            'code' => 'PGL-2026-100001',
            'collector_user_id' => $collector->id,
            'destination_type' => ShipmentDestination::Distributor->value,
            'total_weight_kg' => 20,
            'total_fruit_count' => 8,
            'packaged_at' => now(),
            'status' => ShipmentStatus::ReadyToShip->value,
        ]);

        CollectorShipmentSource::create([
            'collector_shipment_id' => $shipment->id,
            'harvest_batch_id' => $batch->id,
            'source_code_snapshot' => $batch->code,
            'source_grade_snapshot' => $batch->grade,
            'source_weight_kg' => $batch->quantity_kg,
            'source_fruit_count' => $batch->fruit_count,
            'source_variety_snapshot' => $batch->variety,
        ]);

        $response = $this->withToken($distributor->createToken('testing')->plainTextToken)
            ->postJson('/api/distributor/shipments/' . $shipment->code . '/receipt', [
                'received_weight_kg' => 20,
                'received_fruit_count' => 8,
                'condition' => 'good',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
