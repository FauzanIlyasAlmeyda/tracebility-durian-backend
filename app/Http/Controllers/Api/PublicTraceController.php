<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CollectorShipmentSource;
use App\Models\HarvestBatch;
use App\Models\UmkmProductSource;
use App\Support\ApiResponse;
use App\Support\ContractFormatter;
use Illuminate\Http\JsonResponse;

class PublicTraceController extends Controller
{
    public function show(string $batchCode): JsonResponse
    {
        $batch = HarvestBatch::query()
            ->with(['farm', 'farmer', 'events', 'gradeBreakdowns'])
            ->where('code', $batchCode)
            ->firstOrFail();

        $shipmentHistory = CollectorShipmentSource::query()
            ->with('shipment')
            ->where('harvest_batch_id', $batch->id)
            ->get()
            ->map(function (CollectorShipmentSource $source): array {
                return [
                    'code' => $source->shipment?->code,
                    'status' => $source->shipment?->status,
                    'destination_type' => $source->shipment?->destination_type,
                    'packaged_at' => $source->shipment?->packaged_at?->toISOString(),
                    'sent_at' => $source->shipment?->sent_at?->toISOString(),
                    'completed_at' => $source->shipment?->completed_at?->toISOString(),
                ];
            })
            ->values()
            ->all();

        $publicUrl = url('/api/trace/' . $batch->code);

        $trace = ContractFormatter::trace($batch, $publicUrl);

        $trace['batch'] = ContractFormatter::batch($batch);
        $trace['events'] = ContractFormatter::batchTimeline($batch);
        $trace['source_batches'] = UmkmProductSource::query()
            ->where('harvest_batch_id', $batch->id)
            ->pluck('source_code_snapshot')
            ->values()
            ->all();
        $trace['downstream_products'] = UmkmProductSource::query()
            ->with('product')
            ->where('harvest_batch_id', $batch->id)
            ->latest()
            ->get()
            ->map(function (UmkmProductSource $source): array {
                return [
                    'code' => $source->product?->code,
                    'name' => $source->product?->name,
                    'status' => $source->product?->status,
                ];
            })
            ->values()
            ->all();
        $trace['shipment_history'] = $shipmentHistory;

        return ApiResponse::success($trace);
    }
}
