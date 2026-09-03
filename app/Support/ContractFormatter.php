<?php

namespace App\Support;

use App\Models\CollectorShipment;
use App\Models\DistributorReceipt;
use App\Models\Farm;
use App\Models\HarvestBatch;
use App\Models\HarvestBatchEvent;
use App\Models\UmkmOrder;
use App\Models\UmkmProduct;
use App\Models\ConsumerTransaction;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ContractFormatter
{
    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'email' => $user->email,
            'username' => $user->username,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    public static function farm(Farm $farm): array
    {
        $payload = [
            'id' => $farm->id,
            'farmer_id' => $farm->farmer_user_id,
            'farmerId' => $farm->farmer_user_id,
            'name' => $farm->name,
            'province' => $farm->province,
            'city' => $farm->city,
            'district' => $farm->district,
            'village' => $farm->village,
            'address' => $farm->address,
            'latitude' => $farm->latitude === null ? null : (float) $farm->latitude,
            'longitude' => $farm->longitude === null ? null : (float) $farm->longitude,
        ];

        return $payload;
    }

    public static function batch(HarvestBatch $batch): array
    {
        $blockchain = DummyBlockchainLedger::proof($batch);

        $gradeBreakdowns = $batch->relationLoaded('gradeBreakdowns')
            ? $batch->gradeBreakdowns
                ->map(fn ($breakdown): array => [
                    'grade' => $breakdown->grade_label,
                    'gradeLabel' => $breakdown->grade_label,
                    'weight_kg' => (float) $breakdown->weight_kg,
                    'weightKg' => (float) $breakdown->weight_kg,
                    'fruit_count' => $breakdown->fruit_count,
                    'fruitCount' => $breakdown->fruit_count,
                ])
                ->values()
                ->all()
            : [];

        return [
            'code' => $batch->code,
            'farmer_id' => $batch->farmer_user_id,
            'farmerId' => $batch->farmer_user_id,
            'farm_id' => $batch->farm_id,
            'farmId' => $batch->farm_id,
            'farm_name_snapshot' => $batch->farm_name_snapshot,
            'farmName' => $batch->farm_name_snapshot,
            'variety' => $batch->variety,
            'grade' => $batch->grade,
            'quantity_kg' => $batch->quantity_kg === null ? null : (float) $batch->quantity_kg,
            'quantityKg' => $batch->quantity_kg === null ? null : (float) $batch->quantity_kg,
            'unit' => $batch->unit,
            'fruit_count' => $batch->fruit_count,
            'fruitCount' => $batch->fruit_count,
            'harvest_date' => $batch->harvest_date?->toDateString(),
            'harvestDate' => $batch->harvest_date?->toDateString(),
            'status' => $batch->status,
            'received_quantity_kg' => $batch->received_quantity_kg === null
                ? null
                : (float) $batch->received_quantity_kg,
            'receivedQuantity' => $batch->received_quantity_kg === null
                ? null
                : (float) $batch->received_quantity_kg,
            'received_fruit_count' => $batch->received_fruit_count,
            'receivedFruitCount' => $batch->received_fruit_count,
            'verified_grade' => $batch->verified_grade,
            'verifiedGrade' => $batch->verified_grade,
            'quality_notes' => $batch->quality_notes,
            'qualityNotes' => $batch->quality_notes,
            'verified_at' => $batch->verified_at?->toISOString(),
            'verifiedAt' => $batch->verified_at?->toISOString(),
            'rejection_reason' => $batch->rejection_reason,
            'rejectionReason' => $batch->rejection_reason,
            'rejected_at' => $batch->rejected_at?->toISOString(),
            'rejectedAt' => $batch->rejected_at?->toISOString(),
            'grade_breakdowns' => $gradeBreakdowns,
            'gradeBreakdown' => $gradeBreakdowns,
            'blockchain' => $blockchain,
        ];
    }

    public static function batchEvent(HarvestBatchEvent $event): array
    {
        return [
            'status' => self::eventStatus($event),
            'title' => $event->title,
            'actor_label' => $event->actor_label,
            'event_at' => $event->event_at?->toISOString() ?? $event->event_at,
            'ledger_hash' => $event->ledger_hash,
            'previous_ledger_hash' => $event->previous_ledger_hash,
            'ledger_height' => $event->ledger_height,
        ];
    }

    public static function batchTimeline(HarvestBatch $batch): array
    {
        return $batch->events
            ->sortBy('event_at')
            ->values()
            ->map(fn (HarvestBatchEvent $event): array => self::batchEvent($event))
            ->all();
    }

    public static function shipment(CollectorShipment $shipment): array
    {
        $sourceBatchCodes = $shipment->relationLoaded('sources')
            ? $shipment->sources->map(fn ($source) => $source->source_code_snapshot)->values()->all()
            : [];

        return [
            'code' => $shipment->code,
            'collector_id' => $shipment->collector_user_id,
            'collectorId' => $shipment->collector_user_id,
            'source_batch_codes' => $sourceBatchCodes,
            'sourceBatchCodes' => $sourceBatchCodes,
            'total_weight_kg' => $shipment->total_weight_kg === null ? null : (float) $shipment->total_weight_kg,
            'totalWeightKg' => $shipment->total_weight_kg === null ? null : (float) $shipment->total_weight_kg,
            'total_fruit_count' => $shipment->total_fruit_count,
            'totalFruitCount' => $shipment->total_fruit_count,
            'status' => $shipment->status,
            'destination_type' => $shipment->destination_type,
            'destinationType' => $shipment->destination_type,
            'packaged_at' => $shipment->packaged_at?->toISOString(),
            'packagedAt' => $shipment->packaged_at?->toISOString(),
            'sent_at' => $shipment->sent_at?->toISOString(),
            'sentAt' => $shipment->sent_at?->toISOString(),
            'completed_at' => $shipment->completed_at?->toISOString(),
            'completedAt' => $shipment->completed_at?->toISOString(),
        ];
    }

    public static function receipt(DistributorReceipt $receipt): array
    {
        return [
            'code' => $receipt->code,
            'shipment_code' => $receipt->shipment?->code,
            'shipmentCode' => $receipt->shipment?->code,
            'condition' => $receipt->condition,
            'received_at' => $receipt->received_at?->toISOString(),
            'receivedAt' => $receipt->received_at?->toISOString(),
            'expected_weight_kg' => $receipt->expected_weight_kg === null ? null : (float) $receipt->expected_weight_kg,
            'expectedWeightKg' => $receipt->expected_weight_kg === null ? null : (float) $receipt->expected_weight_kg,
            'expected_fruit_count' => $receipt->expected_fruit_count,
            'expectedFruitCount' => $receipt->expected_fruit_count,
            'received_weight_kg' => $receipt->received_weight_kg === null ? null : (float) $receipt->received_weight_kg,
            'receivedWeightKg' => $receipt->received_weight_kg === null ? null : (float) $receipt->received_weight_kg,
            'received_fruit_count' => $receipt->received_fruit_count,
            'receivedFruitCount' => $receipt->received_fruit_count,
            'discrepancy_note' => $receipt->discrepancy_note,
            'discrepancyNote' => $receipt->discrepancy_note,
            'quality_note' => $receipt->quality_note,
            'qualityNote' => $receipt->quality_note,
        ];
    }

    public static function product(UmkmProduct $product): array
    {
        $sourceMaterials = $product->relationLoaded('sources')
            ? $product->sources->map(function ($source) use ($product): array {
                return [
                    'purchaseId' => $source->source_code_snapshot,
                    'traceCode' => $source->source_code_snapshot,
                    'supplierName' => $source->batch?->farmer?->full_name ?? $source->batch?->farm_name_snapshot ?? 'Durian',
                    'productName' => $product->name,
                    'quantityKg' => $source->weight_kg === null ? 0.0 : (float) $source->weight_kg,
                ];
            })->values()->all()
            : [];

        return [
            'code' => $product->code,
            'id' => $product->code,
            'name' => $product->name,
            'category' => $product->category,
            'status_label' => $product->status,
            'status' => $product->status,
            'price_label' => self::moneyLabel($product->price),
            'priceLabel' => self::moneyLabel($product->price),
            'stock_label' => 'Stok ' . (int) $product->stock_qty . ' paket',
            'stockLabel' => 'Stok ' . (int) $product->stock_qty . ' paket',
            'short_description' => $product->description ? str($product->description)->limit(80)->toString() : null,
            'shortDescription' => $product->description ? str($product->description)->limit(80)->toString() : null,
            'description' => $product->description,
            'qr_code_data' => $product->code,
            'qrCodeData' => $product->code,
            'sourceMaterials' => $sourceMaterials,
        ];
    }

    public static function order(UmkmOrder $order): array
    {
        return [
            'id' => $order->code,
            'product_name' => $order->product?->name ?? $order->product_name_snapshot ?? null,
            'productName' => $order->product?->name ?? $order->product_name_snapshot ?? null,
            'buyer_name' => $order->buyer_name,
            'buyerName' => $order->buyer_name,
            'quantity' => $order->quantity,
            'total_label' => self::moneyLabel($order->total_amount),
            'totalLabel' => self::moneyLabel($order->total_amount),
            'status' => $order->status,
            'created_at' => $order->created_at?->toISOString(),
            'createdAt' => $order->created_at?->toISOString(),
            'qr_code_data' => $order->qr_code_data ?? $order->code,
            'qrCodeData' => $order->qr_code_data ?? $order->code,
            'product_code' => $order->product?->code,
            'productCode' => $order->product?->code,
            'completed_at' => $order->updated_at?->toISOString(),
            'completedAt' => $order->updated_at?->toISOString(),
            'note' => $order->note,
        ];
    }

    public static function transaction(ConsumerTransaction $transaction): array
    {
        return [
            'id' => $transaction->code,
            'status' => $transaction->status,
            'payment_status' => $transaction->payment_status,
            'paymentStatus' => $transaction->payment_status,
            'created_at' => $transaction->created_at?->toISOString(),
            'createdAt' => $transaction->created_at?->toISOString(),
            'qr_code_data' => $transaction->qr_code_data ?? $transaction->code,
            'qrCodeData' => $transaction->qr_code_data ?? $transaction->code,
            'quantity' => $transaction->quantity,
            'total_label' => self::moneyLabel($transaction->total_amount),
            'totalLabel' => self::moneyLabel($transaction->total_amount),
            'buyer_address' => $transaction->buyer_address ?? null,
            'buyerAddress' => $transaction->buyer_address ?? null,
            'buyer_coordinates' => $transaction->buyer_coordinates,
            'buyerCoordinates' => $transaction->buyer_coordinates,
            'payment_method' => $transaction->payment_method ?? null,
            'paymentMethod' => $transaction->payment_method ?? null,
            'bank_name' => $transaction->bank_name ?? null,
            'bankName' => $transaction->bank_name ?? null,
            'account_number' => $transaction->account_number ?? null,
            'accountNumber' => $transaction->account_number ?? null,
            'note' => $transaction->note,
            'product' => $transaction->relationLoaded('product') && $transaction->product
                ? self::product($transaction->product)
                : null,
        ];
    }

    public static function trace(HarvestBatch $batch, ?string $publicUrl = null): array
    {
        $blockchain = DummyBlockchainLedger::proof($batch);

        return [
            'batch' => [
                'code' => $batch->code,
                'variety' => $batch->variety,
                'grade' => $batch->grade,
                'status' => $batch->status,
                'farm_name_snapshot' => $batch->farm_name_snapshot,
                'farm_location' => trim(implode(', ', array_filter([
                    $batch->farm?->city,
                    $batch->farm?->province,
                ]))),
                'farmName' => $batch->farm_name_snapshot,
                'farmLocation' => trim(implode(', ', array_filter([
                    $batch->farm?->city,
                    $batch->farm?->province,
                ]))),
                'received_quantity_kg' => $batch->received_quantity_kg === null
                    ? null
                    : (float) $batch->received_quantity_kg,
                'receivedQuantity' => $batch->received_quantity_kg === null
                    ? null
                    : (float) $batch->received_quantity_kg,
                'received_fruit_count' => $batch->received_fruit_count,
                'receivedFruitCount' => $batch->received_fruit_count,
                'verified_grade' => $batch->verified_grade,
                'verifiedGrade' => $batch->verified_grade,
                'quality_notes' => $batch->quality_notes,
                'qualityNotes' => $batch->quality_notes,
                'verified_at' => $batch->verified_at?->toISOString(),
                'verifiedAt' => $batch->verified_at?->toISOString(),
                'rejection_reason' => $batch->rejection_reason,
                'rejectionReason' => $batch->rejection_reason,
                'rejected_at' => $batch->rejected_at?->toISOString(),
                'rejectedAt' => $batch->rejected_at?->toISOString(),
                'blockchain' => $blockchain,
            ],
            'events' => self::batchTimeline($batch),
            'source_batches' => [],
            'sourceBatches' => [],
            'downstream_products' => [],
            'downstreamProducts' => [],
            'shipment_history' => [],
            'shipmentHistory' => [],
            'public_url' => $publicUrl,
            'publicUrl' => $publicUrl,
            'blockchain' => $blockchain,
        ];
    }

    public static function moneyLabel(mixed $value): string
    {
        $amount = (float) $value;

        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    private static function eventStatus(HarvestBatchEvent $event): string
    {
        $metadataStatus = Arr::get($event->metadata, 'status');

        if (is_string($metadataStatus) && $metadataStatus !== '') {
            return $metadataStatus;
        }

        return Str::of($event->title)
            ->lower()
            ->replace([' ', '-'], '_')
            ->toString();
    }
}
