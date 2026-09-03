<?php

namespace App\Http\Controllers\Api;

use App\Enums\ConsumerTransactionStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\ContractFormatter;
use App\Models\ConsumerProfile;
use App\Models\ConsumerTransaction;
use App\Models\UmkmProduct;
use App\Models\User;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConsumerController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Konsumen->value);

        return ApiResponse::success([
            'user' => $user,
            'profile' => ConsumerProfile::firstOrCreate([
                'user_id' => $user->id,
            ], [
                'display_name' => $user->full_name,
            ]),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Konsumen->value);

        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'avatar_path' => ['nullable', 'string', 'max:255'],
        ]);

        $profile = ConsumerProfile::updateOrCreate([
            'user_id' => $user->id,
        ], $data + [
            'display_name' => $data['display_name'] ?? $user->full_name,
        ]);

        return ApiResponse::success($profile, 'Profil konsumen diperbarui');
    }

    public function products(Request $request): JsonResponse
    {
        $this->ensureRole($request->user(), UserRole::Konsumen->value);

        return ApiResponse::success(
            UmkmProduct::query()
                ->with(['umkm.umkmProfile'])
                ->where('status', ProductStatus::Aktif->value)
                ->latest()
                ->get()
                ->map(fn (UmkmProduct $product): array => $this->consumerProduct($product))
                ->values()
                ->all()
        );
    }

    public function showProduct(Request $request, string $code): JsonResponse
    {
        $this->ensureRole($request->user(), UserRole::Konsumen->value);

        $product = UmkmProduct::query()
            ->with('sources.batch')
            ->where('code', $code)
            ->firstOrFail();

        $sourceBatch = $product->sources->first();

        return ApiResponse::success([
            'product' => array_merge(
                ContractFormatter::product($product),
                [
                    'source_batch_code' => $sourceBatch?->source_code_snapshot,
                    'source_variety' => $sourceBatch?->batch?->variety,
                    'source_grade' => $sourceBatch?->batch?->grade,
                    'source_origin_farm' => $sourceBatch?->batch?->farm_name_snapshot,
                    'source_harvest_date' => $sourceBatch?->batch?->harvest_date?->toDateString(),
                    'source_harvest_method' => $sourceBatch?->batch?->harvest_method,
                    'source_maturity_level' => $sourceBatch?->batch?->maturity_level,
                    'source_shelf_life_estimate' => $sourceBatch?->batch?->shelf_life_estimate,
                    'source_verified_at' => $sourceBatch?->batch?->verified_at?->toISOString(),
                    'source_quality_notes' => $sourceBatch?->batch?->quality_notes,
                ],
            ),
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $this->ensureRole($request->user(), UserRole::Konsumen->value);

        return ApiResponse::success(
            ConsumerTransaction::query()
                ->with('product')
                ->where('consumer_user_id', $request->user()->id)
                ->latest()
                ->get()
                ->map(fn (ConsumerTransaction $transaction): array => ContractFormatter::transaction($transaction))
                ->values()
                ->all()
        );
    }

    public function storeTransaction(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Konsumen->value);

        $data = $request->validate([
            'umkm_product_id' => ['nullable', 'integer', 'exists:umkm_products,id'],
            'product_id' => ['nullable', 'integer', 'exists:umkm_products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'total_label' => ['nullable', 'string', 'max:50'],
            'buyer_address' => ['nullable', 'string'],
            'buyer_coordinates' => ['nullable', 'string', 'max:150'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_status' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'qr_code_data' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        $productId = $data['umkm_product_id'] ?? $data['product_id'] ?? null;
        if (! $productId) {
            return ApiResponse::validation([
                'product_id' => ['Produk wajib dipilih.'],
            ]);
        }

        $product = UmkmProduct::query()->whereKey($productId)->firstOrFail();

        if ($product->status !== ProductStatus::Aktif->value) {
            return ApiResponse::validation([
                'product_id' => ['Produk harus berstatus aktif untuk dibeli.'],
            ]);
        }

        if (($product->stock_qty ?? 0) < ($data['quantity'] ?? 0)) {
            return ApiResponse::validation([
                'quantity' => ['Stok produk tidak mencukupi.'],
            ]);
        }

        $transaction = DB::transaction(function () use ($user, $data, $product) {
            $totalAmount = $data['total_amount'] ?? $this->resolveMoney($data['total_label'] ?? null);

            $transaction = ConsumerTransaction::create([
                'code' => CodeGenerator::transaction(),
                'consumer_user_id' => $user->id,
                'umkm_product_id' => $product->id,
                'product_name_snapshot' => $product->name,
                'quantity' => $data['quantity'],
                'total_amount' => $totalAmount,
                'buyer_coordinates' => $data['buyer_coordinates'] ?? null,
                'payment_status' => $data['payment_status'] ?? 'unpaid',
                'status' => ConsumerTransactionStatus::Processing->value,
                'qr_code_data' => $data['qr_code_data'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            $transaction->forceFill([
                'code' => CodeGenerator::transaction($transaction->id),
            ])->save();

            $product->forceFill([
                'stock_qty' => $product->stock_qty - $data['quantity'],
                'status' => $product->stock_qty - $data['quantity'] <= 0
                    ? ProductStatus::Habis->value
                    : $product->status,
            ])->save();

            return $transaction;
        });

        return ApiResponse::created([
            'id' => $transaction->code,
            'status' => $transaction->status,
            'payment_status' => $transaction->payment_status,
            'qr_code_data' => $transaction->qr_code_data ?? $transaction->code,
        ], 'Transaksi berhasil dibuat');
    }

    private function ensureRole(?User $user, string $role): void
    {
        abort_unless($user && $user->role === $role, 403, 'Forbidden');
    }

    private function consumerProduct(UmkmProduct $product): array
    {
        $profile = $product->umkm?->umkmProfile;

        return [
            'code' => $product->code,
            'name' => $product->name,
            'category' => $product->category ?? 'Paket',
            'status' => 'readyToSell',
            'price_label' => ContractFormatter::moneyLabel($product->price),
            'short_description' => $product->description ? str($product->description)->limit(80)->toString() : null,
            'umkm_name' => $profile?->name ?? $product->umkm?->full_name,
            'location' => $profile?->address,
            'rating' => 4.9,
            'stock_label' => 'Stok ' . (int) $product->stock_qty . ' paket',
        ];
    }

    private function resolveMoney(?string $label): float
    {
        if ($label === null) {
            return 0.0;
        }

        $normalized = preg_replace('/[^\d,\.]/', '', $label);
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return (float) $normalized;
    }
}
