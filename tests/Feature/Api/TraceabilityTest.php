<?php

namespace Tests\Feature\Api;

use App\Enums\HarvestBatchStatus;
use App\Enums\UserRole;
use App\Models\Farm;
use App\Models\HarvestBatch;
use App\Models\User;
use App\Support\Traceability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraceabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_trace_includes_dummy_blockchain_proof(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Budi',
            'last_name' => 'Petani',
            'email' => 'budi.trace@example.com',
            'phone' => '081200000001',
            'role' => UserRole::Petani->value,
            'password' => 'password123',
        ]);

        $farm = Farm::create([
            'farmer_user_id' => $user->id,
            'name' => 'Kebun Durian Maju',
            'city' => 'Kediri',
            'province' => 'Jawa Timur',
        ]);

        $batch = HarvestBatch::create([
            'code' => 'DRN-2026-000001',
            'farmer_user_id' => $user->id,
            'farm_id' => $farm->id,
            'farm_name_snapshot' => $farm->name,
            'variety' => 'Montong',
            'grade' => 'A',
            'quantity_kg' => 120,
            'unit' => 'kg',
            'status' => HarvestBatchStatus::Created->value,
        ]);

        Traceability::recordEvent($batch, 'Batch Dibuat', $user, [
            'status' => HarvestBatchStatus::Created->value,
            'quantity_kg' => 120,
        ]);

        $response = $this->getJson('/api/trace/' . $batch->code);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.blockchain.network', 'dummy-ledger')
            ->assertJsonPath('data.blockchain.status', 'simulated')
            ->assertJsonPath('data.blockchain.event_count', 1)
            ->assertJsonPath('data.batch.code', 'DRN-2026-000001')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'batch',
                    'events',
                    'source_batches',
                    'downstream_products',
                    'shipment_history',
                    'public_url',
                    'blockchain',
                ],
            ]);
    }
}
