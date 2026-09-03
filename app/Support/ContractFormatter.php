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
        return [
            'id' => $farm->id,
            'farmer_id' => $farm->farmer_user_id,
            'name' => $farm->name,
            'province' => $farm->province,
            'city' => $farm->city,
            'district' => $farm->district,
            'village' => $farm->village,
            'address' => $farm->address,
            'latitude' => $farm->latitude === null ? null : (float) $farm->latitude,
            'longitude' => $farm->longitude === null ? null : (float) $farm->longitude,
        ];
    }

    public static function batch(HarvestBatch $batch): array
    {
        $blockchain = DummyBlockchainLedger::proof($batch);

        return [
            'code' => $batch->code,
            'farmer_id' => $batch->farmer_user_id,
            'farm_id' => $batch->farm_id,
            'farm_name_snapshot' => $batch->farm_name_snapshot,
            'variety' => $batch->variety,
            'grade' => $batch->grade,
            'quantity_kg' => $batch->quantity_kg === null ? null : (float) $batch->quantity_kg,
            'unit' => $batch->unit,
            'fruit_count' => $batch->fruit_count,
            'harvest_date' => $batch->harvest_date?->toDateString(),
            'status' => $batch->status,
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
        return [
            'code' => $shipment->code,
            'collector_id' => $shipment->collector_user_id,
            'source_batch_codes' => $shipment->relationLoaded('sources')
                ? $shipment->sources->map(fn ($source) => $source->source_code_snapshot)->values()->all()
                : [],
            'total_weight_kg' => $shipment->total_weight_kg === null ? null : (float) $shipment->total_weight_kg,
            'total_fruit_count' => $shipment->total_fruit_count,
            'status' => $shipment->status,
            'destination_type' => $shipment->destination_type,
            'packaged_at' => $shipment->packaged_at?->toISOString(),
            'sent_at' => $shipment->sent_at?->toISOString(),
            'completed_at' => $shipment->completed_at?->toISOString(),
        ];
    }

    public static function receipt(DistributorReceipt $receipt): array
    {
        return [
            'code' => $receipt->code,
            'shipment_code' => $receipt->shipment?->code,
            'condition' => $receipt->condition,
            'received_at' => $receipt->received_at?->toISOString(),
        ];
    }

    public static function product(UmkmProduct $product): array
    {
        return [
            'code' => $product->code,
            'name' => $product->name,
            'category' => $product->category,
            'status' => $product->status,
            'price_label' => self::moneyLabel($product->price),
            'stock_label' => 'Stok ' . (int) $product->stock_qty . ' paket',
            'description' => $product->description,
            'qr_code_data' => $product->code,
        ];
    }

    public static function order(UmkmOrder $order): array
    {
        return [
            'id' => $order->code,
            'product_name' => $order->product?->name ?? $order->product_name_snapshot ?? null,
            'buyer_name' => $order->buyer_name,
            'quantity' => $order->quantity,
            'total_label' => self::moneyLabel($order->total_amount),
            'status' => $order->status,
            'created_at' => $order->created_at?->toISOString(),
            'qr_code_data' => $order->qr_code_data ?? $order->code,
        ];
    }

    public static function transaction(ConsumerTransaction $transaction): array
    {
        return [
            'id' => $transaction->code,
            'status' => $transaction->status,
            'payment_status' => $transaction->payment_status,
            'created_at' => $transaction->created_at?->toISOString(),
            'qr_code_data' => $transaction->qr_code_data ?? $transaction->code,
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
                'blockchain' => $blockchain,
            ],
            'events' => self::batchTimeline($batch),
            'source_batches' => [],
            'shipment_history' => [],
            'public_url' => $publicUrl,
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
