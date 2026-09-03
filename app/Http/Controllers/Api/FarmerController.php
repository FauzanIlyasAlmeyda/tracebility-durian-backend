<?php

namespace App\Http\Controllers\Api;

use App\Enums\HarvestBatchStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\ContractFormatter;
use App\Models\Farm;
use App\Models\FarmerProfile;
use App\Models\HarvestBatch;
use App\Models\User;
use App\Support\CodeGenerator;
use App\Support\Traceability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FarmerController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $this->ensureRole($request->user(), UserRole::Petani->value);

        return ApiResponse::success([
            'user' => $request->user(),
            'profile' => $request->user()->farmerProfile ?? FarmerProfile::firstOrCreate([
                'user_id' => $request->user()->id,
            ], [
                'full_name' => $request->user()->full_name,
                'role_label' => 'Petani',
            ]),
        ], 'OK');
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Petani->value);

        $data = $request->validate([
            'full_name' => ['nullable', 'string', 'max:150'],
            'role_label' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:191'],
            'village' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'contact' => ['nullable', 'string', 'max:50'],
            'avatar_path' => ['nullable', 'string', 'max:255'],
        ]);

        $profile = FarmerProfile::updateOrCreate(
            ['user_id' => $user->id],
            $data + [
                'full_name' => $data['full_name'] ?? $user->full_name,
                'role_label' => $data['role_label'] ?? 'Petani',
            ],
        );

        return ApiResponse::success([
            'user' => $user->refresh(),
            'profile' => $profile,
        ], 'Profil petani diperbarui');
    }

    public function farms(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Petani->value);

        return ApiResponse::success(
            Farm::query()
                ->where('farmer_user_id', $user->id)
                ->latest()
                ->get()
                ->map(fn (Farm $farm): array => ContractFormatter::farm($farm))
                ->values()
                ->all()
        );
    }

    public function storeFarm(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Petani->value);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $farm = Farm::create($data + [
            'farmer_user_id' => $user->id,
        ]);

        return ApiResponse::created(
            ContractFormatter::farm($farm),
            'Kebun berhasil dibuat'
        );
    }

    public function batches(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Petani->value);

        return ApiResponse::success(
            HarvestBatch::query()
                ->with(['farm', 'events', 'gradeBreakdowns'])
                ->where('farmer_user_id', $user->id)
                ->latest()
                ->get()
                ->map(fn (HarvestBatch $batch): array => ContractFormatter::batch($batch))
                ->values()
                ->all()
        );
    }

    public function storeBatch(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Petani->value);

        $data = $request->validate([
            'farm_id' => ['nullable', 'integer', 'exists:farms,id'],
            'farm_name_snapshot' => ['nullable', 'string', 'max:150'],
            'variety' => ['nullable', 'string', 'max:100'],
            'grade' => ['nullable', 'string', 'max:50'],
            'quantity_kg' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:20'],
            'fruit_count' => ['nullable', 'integer', 'min:0'],
            'harvest_date' => ['nullable', 'date'],
            'fertilizer' => ['nullable', 'string', 'max:150'],
            'harvest_method' => ['nullable', 'string', 'max:150'],
            'maturity_level' => ['nullable', 'string', 'max:150'],
            'shelf_life_estimate' => ['nullable', 'string', 'max:150'],
            'storage_suggestion' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string'],
            'photo_path' => ['nullable', 'string', 'max:255'],
        ]);

        $farm = isset($data['farm_id']) ? Farm::query()->whereKey($data['farm_id'])->first() : null;

        $batch = DB::transaction(function () use ($data, $user, $farm): HarvestBatch {
            $batch = HarvestBatch::create($data + [
                'code' => CodeGenerator::batch(),
                'farmer_user_id' => $user->id,
                'farm_name_snapshot' => $data['farm_name_snapshot'] ?? $farm?->name ?? 'Tidak diketahui',
                'status' => HarvestBatchStatus::Created->value,
                'unit' => $data['unit'] ?? 'kg',
            ]);

            $batch->forceFill([
                'code' => CodeGenerator::batch($batch->id),
            ])->save();

            Traceability::recordEvent($batch, 'Batch Dibuat', $user, [
                'status' => $batch->status,
                'quantity_kg' => $batch->quantity_kg,
            ]);

            return $batch->load(['farm', 'events', 'gradeBreakdowns']);
        });

        return ApiResponse::created(
            [
                'code' => $batch->code,
                'status' => $batch->status,
                'harvest_date' => $batch->harvest_date?->toDateString(),
            ],
            'Batch berhasil dibuat'
        );
    }

    public function showBatch(Request $request, string $code): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Petani->value);

        $batch = HarvestBatch::query()
            ->with(['farm', 'events', 'gradeBreakdowns'])
            ->where('code', $code)
            ->where('farmer_user_id', $user->id)
            ->firstOrFail();

        return ApiResponse::success([
            'batch' => ContractFormatter::batch($batch),
            'events' => ContractFormatter::batchTimeline($batch),
        ]);
    }

    public function updateBatch(Request $request, string $code): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Petani->value);

        $batch = HarvestBatch::query()
            ->where('code', $code)
            ->where('farmer_user_id', $user->id)
            ->firstOrFail();

        if ($batch->status !== HarvestBatchStatus::Created->value) {
            return ApiResponse::error('Batch hanya bisa diubah saat status created', 422);
        }

        $data = $request->validate([
            'farm_id' => ['nullable', 'integer', 'exists:farms,id'],
            'farm_name_snapshot' => ['nullable', 'string', 'max:150'],
            'variety' => ['nullable', 'string', 'max:100'],
            'grade' => ['nullable', 'string', 'max:50'],
            'quantity_kg' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:20'],
            'fruit_count' => ['nullable', 'integer', 'min:0'],
            'harvest_date' => ['nullable', 'date'],
            'fertilizer' => ['nullable', 'string', 'max:150'],
            'harvest_method' => ['nullable', 'string', 'max:150'],
            'maturity_level' => ['nullable', 'string', 'max:150'],
            'shelf_life_estimate' => ['nullable', 'string', 'max:150'],
            'storage_suggestion' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string'],
            'photo_path' => ['nullable', 'string', 'max:255'],
        ]);

        if (isset($data['farm_id'])) {
            $farm = Farm::query()->whereKey($data['farm_id'])->where('farmer_user_id', $user->id)->firstOrFail();
            $data['farm_name_snapshot'] = $data['farm_name_snapshot'] ?? $farm->name;
        }

        $batch->fill($data);
        $batch->farm_name_snapshot = $data['farm_name_snapshot'] ?? $batch->farm_name_snapshot;
        $batch->save();

        Traceability::recordEvent($batch, 'Batch Diperbarui', $user, [
            'status' => $batch->status,
        ]);

        return ApiResponse::success([
            'code' => $batch->code,
            'status' => $batch->status,
        ], 'Batch berhasil diperbarui');
    }

    private function ensureRole(?User $user, string $role): void
    {
        abort_unless($user && $user->role === $role, 403, 'Forbidden');
    }
}
