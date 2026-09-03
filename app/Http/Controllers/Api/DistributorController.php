<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReceiptCondition;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\ContractFormatter;
use App\Models\DistributorProfile;
use App\Models\CollectorShipment;
use App\Models\DistributorReceipt;
use App\Models\HarvestBatch;
use App\Models\User;
use App\Support\CodeGenerator;
use App\Support\Traceability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DistributorController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Distributor->value);

        return ApiResponse::success([
            'user' => $user,
            'profile' => DistributorProfile::firstOrCreate([
                'user_id' => $user->id,
            ], [
                'business_name' => $user->full_name,
            ]),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Distributor->value);

        $data = $request->validate([
            'business_name' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
            'contact' => ['nullable', 'string', 'max:50'],
            'avatar_path' => ['nullable', 'string', 'max:255'],
        ]);

        $profile = DistributorProfile::updateOrCreate([
            'user_id' => $user->id,
        ], $data + [
            'business_name' => $data['business_name'] ?? $user->full_name,
        ]);

        return ApiResponse::success($profile, 'Profil distributor diperbarui');
    }

    public function shipments(Request $request): JsonResponse
    {
        $this->ensureRole($request->user(), UserRole::Distributor->value);

        return ApiResponse::success(
            CollectorShipment::query()
                ->with('sources.batch')
                ->where('destination_type', 'distributor')
                ->latest()
                ->get()
                ->map(fn (CollectorShipment $shipment): array => ContractFormatter::shipment($shipment))
                ->values()
                ->all()
        );
    }

    public function showShipment(Request $request, string $code): JsonResponse
    {
        $this->ensureRole($request->user(), UserRole::Distributor->value);

        $shipment = CollectorShipment::query()
            ->with('sources.batch')
            ->where('destination_type', 'distributor')
            ->where('code', $code)
            ->firstOrFail();

        $receipt = DistributorReceipt::query()
            ->where('collector_shipment_id', $shipment->id)
            ->latest()
            ->first();

        return ApiResponse::success([
            'shipment' => ContractFormatter::shipment($shipment),
            'source_batches' => $shipment->sources->map(function ($source): array {
                return [
                    'code' => $source->source_code_snapshot,
                    'variety' => $source->source_variety_snapshot,
                    'status' => $source->batch?->status,
                ];
            })->values()->all(),
            'receipt' => $receipt ? ContractFormatter::receipt($receipt) : null,
        ]);
    }

    public function storeReceipt(Request $request, string $code): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Distributor->value);

        $shipment = CollectorShipment::query()
            ->with('sources.batch')
            ->where('destination_type', 'distributor')
            ->where('code', $code)
            ->firstOrFail();

        $data = $request->validate([
            'received_weight_kg' => ['required', 'numeric', 'min:0'],
            'received_fruit_count' => ['required', 'integer', 'min:0'],
            'condition' => ['required', Rule::in(ReceiptCondition::values())],
            'received_at' => ['nullable', 'date'],
            'discrepancy_note' => ['nullable', 'string'],
            'quality_note' => ['nullable', 'string'],
        ]);

        $expectedWeight = (float) $shipment->total_weight_kg;
        $expectedFruitCount = (int) $shipment->total_fruit_count;
        $hasMismatch = (float) $data['received_weight_kg'] !== $expectedWeight
            || (int) $data['received_fruit_count'] !== $expectedFruitCount;

        if ($hasMismatch && blank($data['discrepancy_note'] ?? null)) {
            return ApiResponse::validation([
                'discrepancy_note' => ['Catatan selisih wajib diisi jika jumlah tidak sesuai.'],
            ]);
        }

        $receipt = DB::transaction(function () use ($user, $shipment, $data, $expectedWeight, $expectedFruitCount): DistributorReceipt {
            $receipt = DistributorReceipt::create([
                'code' => CodeGenerator::receipt(),
                'distributor_user_id' => $user->id,
                'collector_shipment_id' => $shipment->id,
                'expected_weight_kg' => $expectedWeight,
                'expected_fruit_count' => $expectedFruitCount,
                'received_weight_kg' => $data['received_weight_kg'],
                'received_fruit_count' => $data['received_fruit_count'],
                'condition' => $data['condition'],
                'received_at' => $data['received_at'] ?? now(),
                'discrepancy_note' => $data['discrepancy_note'] ?? null,
                'quality_note' => $data['quality_note'] ?? null,
            ]);

            $receipt->forceFill([
                'code' => CodeGenerator::receipt($receipt->id),
            ])->save();

            $shipment->forceFill([
                'status' => ShipmentStatus::Completed->value,
                'completed_at' => now(),
            ])->save();

            foreach ($shipment->sources as $source) {
                if ($source->batch) {
                    Traceability::recordEvent($source->batch, 'Receipt Distributor Dibuat', $user, [
                        'status' => $source->batch->status,
                        'shipment_code' => $shipment->code,
                        'receipt_code' => $receipt->code,
                    ]);
                }
            }

            return $receipt;
        });

        return ApiResponse::created([
            'shipment_code' => $shipment->code,
            'condition' => $receipt->condition,
            'received_at' => $receipt->received_at?->toISOString(),
        ], 'Receipt berhasil disimpan');
    }

    private function ensureRole(?User $user, string $role): void
    {
        abort_unless($user && $user->role === $role, 403, 'Forbidden');
    }
}
