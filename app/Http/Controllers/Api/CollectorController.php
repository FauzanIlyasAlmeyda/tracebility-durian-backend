<?php

namespace App\Http\Controllers\Api;

use App\Enums\HarvestBatchStatus;
use App\Enums\ShipmentDestination;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\ContractFormatter;
use App\Models\CollectorProfile;
use App\Models\CollectorShipment;
use App\Models\CollectorShipmentSource;
use App\Models\HarvestBatch;
use App\Models\HarvestBatchGradeBreakdown;
use App\Models\User;
use App\Support\CodeGenerator;
use App\Support\Traceability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CollectorController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Pengepul->value);

        return ApiResponse::success([
            'user' => $user,
            'profile' => CollectorProfile::firstOrCreate([
                'user_id' => $user->id,
            ], [
                'business_name' => $user->full_name,
            ]),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Pengepul->value);

        $data = $request->validate([
            'business_name' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
            'contact' => ['nullable', 'string', 'max:50'],
            'avatar_path' => ['nullable', 'string', 'max:255'],
        ]);

        $profile = CollectorProfile::updateOrCreate([
            'user_id' => $user->id,
        ], $data + [
            'business_name' => $data['business_name'] ?? $user->full_name,
        ]);

        return ApiResponse::success([
            'user' => $user->refresh(),
            'profile' => $profile,
        ], 'Profil pengepul diperbarui');
    }

    public function stock(Request $request): JsonResponse
    {
        $this->ensureRole($request->user(), UserRole::Pengepul->value);

        $batches = HarvestBatch::query()
            ->with(['farm', 'events'])
            ->whereIn('status', [
                HarvestBatchStatus::Created->value,
                HarvestBatchStatus::VerifiedByCollector->value,
                HarvestBatchStatus::InDistribution->value,
            ])
            ->latest()
            ->get();

        return ApiResponse::success([
            'active_batch_count' => $batches->count(),
            'total_weight_kg' => (float) $batches->sum('quantity_kg'),
            'total_fruit_count' => (int) $batches->sum('fruit_count'),
            'grade_breakdown' => $batches
                ->groupBy('grade')
                ->map(function ($group, $grade): array {
                    return [
                        'key' => $grade,
                        'label' => 'Grade ' . $grade,
                        'total_weight_kg' => (float) $group->sum('quantity_kg'),
                        'total_fruit_count' => (int) $group->sum('fruit_count'),
                        'batch_count' => $group->count(),
                    ];
                })
                ->values()
                ->all(),
            'variety_breakdown' => $batches
                ->groupBy(fn (HarvestBatch $batch): string => strtolower((string) $batch->variety))
                ->map(function ($group, $variety): array {
                    return [
                        'key' => $variety,
                        'label' => $group->first()?->variety ? 'Durian ' . $group->first()->variety : $variety,
                        'total_weight_kg' => (float) $group->sum('quantity_kg'),
                        'total_fruit_count' => (int) $group->sum('fruit_count'),
                        'batch_count' => $group->count(),
                    ];
                })
                ->values()
                ->all(),
        ]);
    }

    public function shipmentBatches(Request $request): JsonResponse
    {
        $this->ensureRole($request->user(), UserRole::Pengepul->value);

        return ApiResponse::success(
            CollectorShipment::query()
                ->with('sources.batch')
                ->where('collector_user_id', $request->user()->id)
                ->latest()
                ->get()
                ->map(fn (CollectorShipment $shipment): array => ContractFormatter::shipment($shipment))
                ->values()
                ->all()
        );
    }

    public function storeShipmentBatches(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Pengepul->value);

        $data = $request->validate([
            'destination_type' => ['required', Rule::in(ShipmentDestination::values())],
            'source_batch_codes' => ['nullable', 'array', 'min:1'],
            'source_batch_codes.*' => ['distinct'],
            'source_batch_codes.*' => ['string', 'exists:harvest_batches,code'],
            'batch_codes' => ['nullable', 'array', 'min:1'],
            'batch_codes.*' => ['distinct'],
            'batch_codes.*' => ['string', 'exists:harvest_batches,code'],
            'packaged_at' => ['nullable', 'date'],
            'warehouse_note' => ['nullable', 'string'],
        ]);

        $sourceCodes = array_values(array_unique($data['source_batch_codes'] ?? $data['batch_codes'] ?? []));

        $batches = HarvestBatch::query()
            ->whereIn('code', $sourceCodes)
            ->get()
            ->keyBy('code');

        if ($batches->isEmpty()) {
            return ApiResponse::validation([
                'source_batch_codes' => ['Minimal satu batch harus dipilih.'],
            ]);
        }

        $invalidSourceCodes = $batches
            ->filter(fn (HarvestBatch $batch): bool => $batch->status !== HarvestBatchStatus::VerifiedByCollector->value)
            ->keys()
            ->values()
            ->all();

        if ($invalidSourceCodes !== []) {
            return ApiResponse::validation([
                'source_batch_codes' => [
                    'Batch hanya bisa masuk shipment jika sudah diverifikasi pengepul.',
                ],
            ]);
        }

        $shipment = DB::transaction(function () use ($user, $data, $batches) {
            $shipment = CollectorShipment::create([
                'code' => CodeGenerator::shipment(),
                'collector_user_id' => $user->id,
                'destination_type' => $data['destination_type'],
                'total_weight_kg' => $batches->sum('quantity_kg'),
                'total_fruit_count' => $batches->sum('fruit_count') ?: 0,
                'packaged_at' => $data['packaged_at'] ?? now(),
                'warehouse_note' => $data['warehouse_note'] ?? null,
                'status' => ShipmentStatus::ReadyToShip->value,
            ]);

            $shipment->forceFill([
                'code' => CodeGenerator::shipment($shipment->id),
            ])->save();

            foreach ($batches as $batch) {
                CollectorShipmentSource::create([
                    'collector_shipment_id' => $shipment->id,
                    'harvest_batch_id' => $batch->id,
                    'source_code_snapshot' => $batch->code,
                    'source_grade_snapshot' => $batch->grade,
                    'source_weight_kg' => $batch->quantity_kg,
                    'source_fruit_count' => $batch->fruit_count,
                    'source_variety_snapshot' => $batch->variety,
                ]);
            }

            return $shipment;
        });

        foreach ($batches as $batch) {
            $batch->forceFill([
                'status' => HarvestBatchStatus::InDistribution->value,
            ])->save();

            Traceability::recordEvent($batch, 'Batch Masuk Shipment', $user, [
                'status' => HarvestBatchStatus::InDistribution->value,
                'shipment_code' => $shipment->code,
                'destination_type' => $shipment->destination_type,
            ]);
        }

        return ApiResponse::created([
            'code' => $shipment->code,
            'status' => $shipment->status,
            'shipment' => ContractFormatter::shipment($shipment->load('sources')),
        ], 'Shipment berhasil dibuat');
    }

    public function sendShipment(Request $request, string $code): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Pengepul->value);

        $shipment = CollectorShipment::query()
            ->with('sources.batch')
            ->where('collector_user_id', $user->id)
            ->where('code', $code)
            ->firstOrFail();

        if ($shipment->status !== ShipmentStatus::ReadyToShip->value) {
            return ApiResponse::error('Shipment hanya bisa dikirim saat status readyToShip', 422);
        }

        $shipment->forceFill([
            'status' => ShipmentStatus::Sent->value,
            'sent_at' => now(),
        ])->save();

        foreach ($shipment->sources as $source) {
            if ($source->batch) {
                Traceability::recordEvent($source->batch, 'Shipment Dikirim', $user, [
                    'status' => HarvestBatchStatus::InDistribution->value,
                    'shipment_code' => $shipment->code,
                ]);
            }
        }

        return ApiResponse::success([
            'code' => $shipment->code,
            'status' => $shipment->status,
            'sent_at' => $shipment->sent_at?->toISOString(),
            'shipment' => ContractFormatter::shipment($shipment->load('sources')),
        ], 'Shipment terkirim');
    }

    public function completeShipment(Request $request, string $code): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Pengepul->value);

        $shipment = CollectorShipment::query()
            ->with('sources.batch')
            ->where('collector_user_id', $user->id)
            ->where('code', $code)
            ->firstOrFail();

        if ($shipment->status !== ShipmentStatus::Sent->value) {
            return ApiResponse::error('Shipment hanya bisa diselesaikan saat status sent', 422);
        }

        $shipment->forceFill([
            'status' => ShipmentStatus::Completed->value,
            'completed_at' => now(),
        ])->save();

        foreach ($shipment->sources as $source) {
            if ($source->batch) {
                $nextStatus = $shipment->destination_type === ShipmentDestination::Umkm->value
                    ? HarvestBatchStatus::ReceivedByUmkm->value
                    : HarvestBatchStatus::InDistribution->value;

                $source->batch->forceFill([
                    'status' => $nextStatus,
                ])->save();

                Traceability::recordEvent($source->batch, 'Shipment Selesai', $user, [
                    'status' => $nextStatus,
                    'shipment_code' => $shipment->code,
                ]);
            }
        }

        return ApiResponse::success([
            'code' => $shipment->code,
            'status' => $shipment->status,
            'completed_at' => $shipment->completed_at?->toISOString(),
            'shipment' => ContractFormatter::shipment($shipment->load('sources')),
        ], 'Shipment selesai');
    }

    public function verifyBatch(Request $request, string $code): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Pengepul->value);

        $batch = HarvestBatch::query()
            ->where('code', $code)
            ->firstOrFail();

        if ($batch->status !== HarvestBatchStatus::Created->value) {
            return ApiResponse::error('Batch hanya bisa diverifikasi saat status created', 422);
        }

        $data = $request->validate([
            'received_quantity_kg' => ['required', 'numeric', 'min:0'],
            'received_fruit_count' => ['required', 'integer', 'min:0'],
            'grade_breakdown' => ['nullable', 'array', 'min:1'],
            'grade_breakdown.*.grade' => ['required_with:grade_breakdown', 'string', 'max:50'],
            'grade_breakdown.*.weight_kg' => ['required_with:grade_breakdown', 'numeric', 'min:0'],
            'grade_breakdown.*.fruit_count' => ['required_with:grade_breakdown', 'integer', 'min:0'],
            'quality_notes' => ['nullable', 'string'],
            'verified_by' => ['nullable', 'string', 'max:150'],
        ]);

        $breakdowns = collect($data['grade_breakdown'] ?? []);
        $weightTotal = (float) $breakdowns->sum('weight_kg');
        $fruitTotal = (int) $breakdowns->sum('fruit_count');
        $receivedWeight = (float) $data['received_quantity_kg'];
        $receivedFruit = (int) $data['received_fruit_count'];

        if ($breakdowns->isNotEmpty() && abs($weightTotal - $receivedWeight) > 0.01) {
            return ApiResponse::validation([
                'grade_breakdown' => ['Total weight grade breakdown harus sama dengan received_quantity_kg.'],
            ]);
        }

        if ($breakdowns->isNotEmpty() && $fruitTotal !== $receivedFruit) {
            return ApiResponse::validation([
                'grade_breakdown' => ['Total fruit count grade breakdown harus sama dengan received_fruit_count.'],
            ]);
        }

        DB::transaction(function () use ($batch, $data, $user): void {
            $batch->forceFill([
                'status' => HarvestBatchStatus::VerifiedByCollector->value,
                'received_quantity_kg' => $data['received_quantity_kg'],
                'received_fruit_count' => $data['received_fruit_count'],
                'quality_notes' => $data['quality_notes'] ?? null,
                'verified_by_user_id' => $user->id,
                'verified_at' => now(),
            ])->save();

            $batch->gradeBreakdowns()->delete();

            foreach ($data['grade_breakdown'] ?? [] as $breakdown) {
                HarvestBatchGradeBreakdown::create([
                    'harvest_batch_id' => $batch->id,
                    'grade_label' => $breakdown['grade'],
                    'weight_kg' => $breakdown['weight_kg'],
                    'fruit_count' => $breakdown['fruit_count'],
                ]);
            }

            Traceability::recordEvent($batch, 'Batch Diverifikasi Pengepul', $user, [
                'status' => HarvestBatchStatus::VerifiedByCollector->value,
                'verified_by' => $data['verified_by'] ?? $user->full_name,
            ]);
        });

        return ApiResponse::success([
            'code' => $batch->code,
            'status' => HarvestBatchStatus::VerifiedByCollector->value,
            'batch' => ContractFormatter::batch($batch->load('gradeBreakdowns')),
        ], 'Batch terverifikasi');
    }

    public function rejectBatch(Request $request, string $code): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Pengepul->value);

        $batch = HarvestBatch::query()
            ->where('code', $code)
            ->firstOrFail();

        if ($batch->status !== HarvestBatchStatus::Created->value) {
            return ApiResponse::error('Batch hanya bisa ditolak saat status created', 422);
        }

        $data = $request->validate([
            'reason' => ['required', 'string'],
            'rejected_by' => ['nullable', 'string', 'max:150'],
        ]);

        $batch->forceFill([
            'status' => HarvestBatchStatus::Rejected->value,
            'rejection_reason' => $data['reason'],
            'rejected_by_user_id' => $user->id,
            'rejected_at' => now(),
        ])->save();

        Traceability::recordEvent($batch, 'Batch Ditolak Pengepul', $user, [
            'status' => HarvestBatchStatus::Rejected->value,
            'reason' => $data['reason'],
        ]);

        return ApiResponse::success([
            'code' => $batch->code,
            'status' => $batch->status,
            'batch' => ContractFormatter::batch($batch),
        ], 'Batch ditolak');
    }

    private function ensureRole(?User $user, string $role): void
    {
        abort_unless($user && $user->role === $role, 403, 'Forbidden');
    }
}
