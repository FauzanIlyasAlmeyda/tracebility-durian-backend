<?php

namespace Database\Seeders;

use App\Enums\HarvestBatchStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\ReceiptCondition;
use App\Enums\ShipmentDestination;
use App\Enums\ShipmentStatus;
use App\Enums\ConsumerTransactionStatus;
use App\Enums\UserRole;
use App\Models\CollectorProfile;
use App\Models\CollectorShipment;
use App\Models\CollectorShipmentSource;
use App\Models\ConsumerProfile;
use App\Models\ConsumerTransaction;
use App\Models\DistributorProfile;
use App\Models\DistributorReceipt;
use App\Models\Farm;
use App\Models\FarmerProfile;
use App\Models\HarvestBatch;
use App\Models\HarvestBatchEvent;
use App\Models\HarvestBatchGradeBreakdown;
use App\Models\UmkmOrder;
use App\Models\UmkmProduct;
use App\Models\UmkmProductSource;
use App\Models\UmkmProfile;
use App\Models\User;
use App\Support\CodeGenerator;
use App\Support\Traceability;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $farmer = User::query()->where('role', UserRole::Petani->value)->first();
            $collector = User::query()->where('role', UserRole::Pengepul->value)->first();
            $distributor = User::query()->where('role', UserRole::Distributor->value)->first();
            $umkm = User::query()->where('role', UserRole::Umkm->value)->first();
            $consumer = User::query()->where('role', UserRole::Konsumen->value)->first();

            if (! $farmer || ! $collector || ! $distributor || ! $umkm || ! $consumer) {
                return;
            }

            $this->seedProfiles($farmer, $collector, $distributor, $umkm, $consumer);

            $farmOne = Farm::firstOrCreate(
                ['farmer_user_id' => $farmer->id, 'name' => 'Kebun Pakis 1'],
                [
                    'province' => 'Jawa Timur',
                    'city' => 'Kabupaten Jember',
                    'district' => 'Pakis',
                    'village' => 'Pakis',
                    'address' => 'Jl. Raya Pakis No. 1',
                    'latitude' => -8.1234,
                    'longitude' => 113.5678,
                    'notes' => 'Demo farm utama.',
                ]
            );

            $farmTwo = Farm::firstOrCreate(
                ['farmer_user_id' => $farmer->id, 'name' => 'Kebun Pakis 2'],
                [
                    'province' => 'Jawa Timur',
                    'city' => 'Kabupaten Jember',
                    'district' => 'Pakis',
                    'village' => 'Pakis',
                    'address' => 'Jl. Raya Pakis No. 2',
                    'latitude' => -8.1244,
                    'longitude' => 113.5688,
                    'notes' => 'Demo farm cadangan.',
                ]
            );

            $batchOne = HarvestBatch::firstOrCreate(
                ['code' => 'DRN-2026-000128'],
                [
                    'farmer_user_id' => $farmer->id,
                    'farm_id' => $farmOne->id,
                    'farm_name_snapshot' => $farmOne->name,
                    'variety' => 'Montong',
                    'grade' => 'A',
                    'quantity_kg' => 52,
                    'unit' => 'kg',
                    'fruit_count' => 18,
                    'harvest_date' => '2026-07-20',
                    'status' => HarvestBatchStatus::VerifiedByCollector->value,
                    'fertilizer' => 'Organik Kompos',
                    'harvest_method' => 'Jatuh Alami',
                    'maturity_level' => 'Matang Pohon',
                    'shelf_life_estimate' => '2-3 hari',
                    'storage_suggestion' => 'Simpan di tempat sejuk.',
                    'notes' => 'Batch demo utama.',
                ]
            );

            $batchTwo = HarvestBatch::firstOrCreate(
                ['code' => 'DRN-2026-000110'],
                [
                    'farmer_user_id' => $farmer->id,
                    'farm_id' => $farmTwo->id,
                    'farm_name_snapshot' => $farmTwo->name,
                    'variety' => 'Musang King',
                    'grade' => 'B',
                    'quantity_kg' => 34,
                    'unit' => 'kg',
                    'fruit_count' => 12,
                    'harvest_date' => '2026-07-19',
                    'status' => HarvestBatchStatus::Created->value,
                    'fertilizer' => 'Pupuk kandang',
                    'harvest_method' => 'Petik manual',
                    'maturity_level' => 'Matang Panen',
                    'shelf_life_estimate' => '2 hari',
                    'storage_suggestion' => 'Jauhkan dari sinar matahari.',
                    'notes' => 'Batch demo kedua.',
                ]
            );

            $this->seedBatchTimeline($batchOne, $farmer, $collector);
            $this->seedBatchBreakdowns($batchOne);

            $shipment = CollectorShipment::firstOrCreate(
                ['code' => 'PGL-2026-000001'],
                [
                    'collector_user_id' => $collector->id,
                    'destination_type' => ShipmentDestination::Distributor->value,
                    'total_weight_kg' => $batchOne->quantity_kg,
                    'total_fruit_count' => $batchOne->fruit_count,
                    'packaged_at' => '2026-07-20 10:00:00',
                    'warehouse_note' => 'Pengiriman demo ke distributor.',
                    'sent_at' => '2026-07-20 11:00:00',
                    'completed_at' => '2026-07-20 13:00:00',
                    'status' => ShipmentStatus::Completed->value,
                ]
            );

            CollectorShipmentSource::firstOrCreate(
                ['collector_shipment_id' => $shipment->id, 'harvest_batch_id' => $batchOne->id],
                [
                    'source_code_snapshot' => $batchOne->code,
                    'source_grade_snapshot' => $batchOne->grade,
                    'source_weight_kg' => $batchOne->quantity_kg,
                    'source_fruit_count' => $batchOne->fruit_count,
                    'source_variety_snapshot' => $batchOne->variety,
                ]
            );

            $receipt = DistributorReceipt::firstOrCreate(
                ['code' => 'RCP-2026-000001'],
                [
                    'distributor_user_id' => $distributor->id,
                    'collector_shipment_id' => $shipment->id,
                    'expected_weight_kg' => $shipment->total_weight_kg,
                    'expected_fruit_count' => $shipment->total_fruit_count,
                    'received_weight_kg' => 51.5,
                    'received_fruit_count' => 18,
                    'condition' => ReceiptCondition::MinorDamage->value,
                    'received_at' => '2026-07-20 13:30:00',
                    'discrepancy_note' => 'Selisih kecil karena sortasi ulang.',
                    'quality_note' => 'Barang masih layak jual.',
                ]
            );

            $product = UmkmProduct::firstOrCreate(
                ['code' => 'UMKM-P-001'],
                [
                    'umkm_user_id' => $umkm->id,
                    'category' => 'Olahan',
                    'name' => 'Pancake Durian Premium',
                    'price' => 68000,
                    'stock_qty' => 24,
                    'description' => 'Pancake durian lembut dan siap jual.',
                    'status' => ProductStatus::Aktif->value,
                    'photo_path' => null,
                ]
            );

            UmkmProductSource::firstOrCreate(
                ['umkm_product_id' => $product->id, 'harvest_batch_id' => $batchOne->id],
                [
                    'source_code_snapshot' => $batchOne->code,
                    'weight_kg' => $batchOne->quantity_kg,
                    'fruit_count' => $batchOne->fruit_count,
                ]
            );

            $order = UmkmOrder::firstOrCreate(
                ['code' => 'ORD-2026-0001'],
                [
                    'umkm_product_id' => $product->id,
                    'buyer_name' => 'Rina Saputri',
                    'buyer_phone' => '081299998888',
                    'buyer_address' => 'Kabupaten Jember, Jawa Timur',
                    'quantity' => 2,
                    'total_amount' => 136000,
                    'qr_code_data' => 'ORD-2026-0001',
                    'note' => 'Bayar di tempat.',
                    'status' => OrderStatus::Diproses->value,
                ]
            );

            $transaction = ConsumerTransaction::firstOrCreate(
                ['code' => 'TRX-2026-0001'],
                [
                    'consumer_user_id' => $consumer->id,
                    'umkm_product_id' => $product->id,
                    'product_name_snapshot' => $product->name,
                    'quantity' => 2,
                    'total_amount' => 136000,
                    'buyer_coordinates' => '-8.2285, 113.6204',
                    'payment_status' => 'unpaid',
                    'status' => ConsumerTransactionStatus::Processing->value,
                    'qr_code_data' => 'TRX-2026-0001',
                    'note' => 'Menunggu pembayaran.',
                ]
            );

            if ($batchOne->events()->count() === 0) {
                Traceability::recordEvent($batchOne, 'Batch Dibuat', $farmer, [
                    'status' => HarvestBatchStatus::Created->value,
                    'quantity_kg' => $batchOne->quantity_kg,
                ]);

                Traceability::recordEvent($batchOne, 'Batch Diverifikasi Pengepul', $collector, [
                    'status' => HarvestBatchStatus::VerifiedByCollector->value,
                    'verified_by' => $collector->full_name,
                ]);

                Traceability::recordEvent($batchOne, 'Shipment Dikirim', $collector, [
                    'status' => HarvestBatchStatus::InDistribution->value,
                    'shipment_code' => $shipment->code,
                ]);

                Traceability::recordEvent($batchOne, 'Shipment Selesai', $collector, [
                    'status' => HarvestBatchStatus::InDistribution->value,
                    'shipment_code' => $shipment->code,
                ]);

                Traceability::recordEvent($batchOne, 'Receipt Distributor Dibuat', $distributor, [
                    'status' => HarvestBatchStatus::InDistribution->value,
                    'shipment_code' => $shipment->code,
                    'receipt_code' => $receipt->code,
                ]);
            }
        });
    }

    private function seedProfiles(
        User $farmer,
        User $collector,
        User $distributor,
        User $umkm,
        User $consumer
    ): void {
        FarmerProfile::updateOrCreate(
            ['user_id' => $farmer->id],
            [
                'full_name' => $farmer->full_name,
                'role_label' => 'Petani',
                'location' => 'Kabupaten Jember',
                'village' => 'Pakis',
                'district' => 'Pakis',
                'city' => 'Jember',
                'contact' => $farmer->phone,
            ]
        );

        CollectorProfile::updateOrCreate(
            ['user_id' => $collector->id],
            [
                'business_name' => 'Pengepul Jember',
                'address' => 'Kabupaten Jember',
                'contact' => $collector->phone,
            ]
        );

        DistributorProfile::updateOrCreate(
            ['user_id' => $distributor->id],
            [
                'business_name' => 'Distributor Jember',
                'address' => 'Kabupaten Jember',
                'contact' => $distributor->phone,
            ]
        );

        UmkmProfile::updateOrCreate(
            ['user_id' => $umkm->id],
            [
                'name' => 'UMKM Sari Durian Jember',
                'owner_name' => $umkm->full_name,
                'about' => 'UMKM olahan durian untuk demo aplikasi.',
                'address' => 'Kabupaten Jember, Jawa Timur',
                'contact' => $umkm->phone,
            ]
        );

        ConsumerProfile::updateOrCreate(
            ['user_id' => $consumer->id],
            [
                'display_name' => $consumer->full_name,
                'address' => 'Kabupaten Jember, Jawa Timur',
                'phone' => $consumer->phone,
            ]
        );
    }

    private function seedBatchTimeline(HarvestBatch $batch, User $farmer, User $collector): void
    {
        if ($batch->events()->count() > 0) {
            return;
        }

        Traceability::recordEvent($batch, 'Batch Dibuat', $farmer, [
            'status' => HarvestBatchStatus::Created->value,
            'quantity_kg' => $batch->quantity_kg,
        ]);

        Traceability::recordEvent($batch, 'Batch Diverifikasi Pengepul', $collector, [
            'status' => HarvestBatchStatus::VerifiedByCollector->value,
            'verified_by' => $collector->full_name,
        ]);
    }

    private function seedBatchBreakdowns(HarvestBatch $batch): void
    {
        HarvestBatchGradeBreakdown::firstOrCreate(
            ['harvest_batch_id' => $batch->id, 'grade_label' => 'A'],
            [
                'weight_kg' => 35,
                'fruit_count' => 12,
            ]
        );

        HarvestBatchGradeBreakdown::firstOrCreate(
            ['harvest_batch_id' => $batch->id, 'grade_label' => 'B'],
            [
                'weight_kg' => 17,
                'fruit_count' => 6,
            ]
        );
    }
}
