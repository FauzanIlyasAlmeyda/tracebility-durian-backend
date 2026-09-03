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
            ->groupBy(fn (CollectorShipmentSource $source): string => (string) ($source->shipment?->code ?? $source->collector_shipment_id))
            ->map(function ($sources): array {
                /** @var CollectorShipmentSource $firstSource */
                $firstSource = $sources->first();
                $shipment = $firstSource?->shipment;

                return [
                    'code' => $shipment?->code,
                    'status' => $shipment?->status,
                    'destination_type' => $shipment?->destination_type,
                    'source_batch_codes' => $sources
                        ->pluck('source_code_snapshot')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                    'packaged_at' => $shipment?->packaged_at?->toISOString(),
                    'sent_at' => $shipment?->sent_at?->toISOString(),
                    'completed_at' => $shipment?->completed_at?->toISOString(),
                ];
            })
            ->values()
            ->all();

        $publicUrl = url('/api/trace/' . $batch->code);

        $trace = ContractFormatter::trace($batch, $publicUrl);

        $trace['batch'] = ContractFormatter::batch($batch);
        $trace['events'] = ContractFormatter::batchTimeline($batch);
        $trace['source_batches'] = [];
        $trace['downstream_products'] = $this->downstreamProducts($batch->id);
        $trace['shipment_history'] = $shipmentHistory;

        return ApiResponse::success($trace);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function downstreamProducts(int $batchId): array
    {
        return UmkmProductSource::query()
            ->with('product')
            ->where('harvest_batch_id', $batchId)
            ->latest()
            ->get()
            ->groupBy('umkm_product_id')
            ->map(function ($sources): array {
                /** @var UmkmProductSource $firstSource */
                $firstSource = $sources->first();
                $product = $firstSource?->product;

                return [
                    'code' => $product?->code,
                    'name' => $product?->name,
                    'status' => $product?->status,
                    'category' => $product?->category,
                    'price_label' => ContractFormatter::moneyLabel($product?->price ?? 0),
                    'stock_label' => 'Stok ' . (int) ($product?->stock_qty ?? 0) . ' paket',
                    'source_batch_codes' => $sources
                        ->pluck('source_code_snapshot')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }
}
