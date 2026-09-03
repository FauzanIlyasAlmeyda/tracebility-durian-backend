<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Enums\HarvestBatchStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\ContractFormatter;
use App\Models\UmkmProfile;
use App\Models\HarvestBatch;
use App\Models\UmkmOrder;
use App\Models\UmkmProduct;
use App\Models\UmkmProductSource;
use App\Models\User;
use App\Support\CodeGenerator;
use App\Support\Traceability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UmkmController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Umkm->value);

        return ApiResponse::success([
            'user' => $user,
            'profile' => UmkmProfile::firstOrCreate([
                'user_id' => $user->id,
            ], [
                'name' => $user->full_name,
                'owner_name' => $user->full_name,
            ]),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Umkm->value);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:150'],
            'owner_name' => ['nullable', 'string', 'max:150'],
            'about' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'contact' => ['nullable', 'string', 'max:50'],
            'image_path' => ['nullable', 'string', 'max:255'],
        ]);

        $profile = UmkmProfile::updateOrCreate([
            'user_id' => $user->id,
        ], $data + [
            'name' => $data['name'] ?? $user->full_name,
            'owner_name' => $data['owner_name'] ?? $user->full_name,
        ]);

        return ApiResponse::success($profile, 'Profil UMKM diperbarui');
    }

    public function products(Request $request): JsonResponse
    {
        $this->ensureRole($request->user(), UserRole::Umkm->value);

        return ApiResponse::success(
            UmkmProduct::query()
                ->with('sources.batch')
                ->where('umkm_user_id', $request->user()->id)
                ->latest()
                ->get()
                ->map(fn (UmkmProduct $product): array => ContractFormatter::product($product))
                ->values()
                ->all()
        );
    }

    public function storeProduct(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Umkm->value);

        $data = $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:150'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_label' => ['nullable', 'string', 'max:50'],
            'stock_qty' => ['nullable', 'integer', 'min:0'],
            'stock_label' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(ProductStatus::values())],
            'photo_path' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'qr_code_data' => ['nullable', 'string', 'max:150'],
            'source_codes' => ['nullable', 'array'],
            'source_codes.*' => ['string', 'exists:harvest_batches,code'],
        ]);

        $sourceBatches = HarvestBatch::query()
            ->with('farmer')
            ->whereIn('code', $data['source_codes'] ?? [])
            ->get()
            ->keyBy('code');

        if ($sourceBatches->isNotEmpty()) {
            $invalidSourceCodes = $sourceBatches
                ->filter(fn (HarvestBatch $batch): bool => $batch->status !== HarvestBatchStatus::ReceivedByUmkm->value)
                ->keys()
                ->values()
                ->all();

            if ($invalidSourceCodes !== []) {
                return ApiResponse::validation([
                    'source_codes' => ['Source batch harus sudah berstatus receivedByUmkm sebelum dipakai membuat produk.'],
                ]);
            }
        }

        $product = DB::transaction(function () use ($user, $data) {
            $price = $this->resolvePrice($data);
            $stockQty = $this->resolveStock($data);
            $photoPath = $data['photo_path'] ?? $data['image_path'] ?? null;

            $product = UmkmProduct::create([
                'code' => CodeGenerator::product(),
                'umkm_user_id' => $user->id,
                'category' => $data['category'] ?? null,
                'name' => $data['name'],
                'price' => $price,
                'stock_qty' => $stockQty,
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? ProductStatus::Aktif->value,
                'photo_path' => $photoPath,
            ]);

            $product->forceFill([
                'code' => CodeGenerator::product($product->id),
            ])->save();

            foreach (($data['source_codes'] ?? []) as $sourceCode) {
                $batch = HarvestBatch::query()->where('code', $sourceCode)->first();
                if (! $batch) {
                    continue;
                }

                UmkmProductSource::create([
                    'umkm_product_id' => $product->id,
                    'harvest_batch_id' => $batch->id,
                    'source_code_snapshot' => $batch->code,
                    'weight_kg' => $batch->quantity_kg,
                    'fruit_count' => $batch->fruit_count,
                ]);

                $batch->forceFill([
                    'status' => HarvestBatchStatus::Processed->value,
                ])->save();

                Traceability::recordEvent($batch, 'Source batch dipakai untuk produk UMKM', $user, [
                    'status' => HarvestBatchStatus::Processed->value,
                    'product_code' => $product->code,
                ]);
            }

            return $product;
        });

        return ApiResponse::created(ContractFormatter::product($product), 'Produk berhasil dibuat');
    }

    public function orders(Request $request): JsonResponse
    {
        $this->ensureRole($request->user(), UserRole::Umkm->value);

        return ApiResponse::success(
            UmkmOrder::query()
                ->with('product')
                ->whereHas('product', fn ($query) => $query->where('umkm_user_id', $request->user()->id))
                ->latest()
                ->get()
                ->map(fn (UmkmOrder $order): array => ContractFormatter::order($order))
                ->values()
                ->all()
        );
    }

    public function storeOrder(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureRole($user, UserRole::Umkm->value);

        $data = $request->validate([
            'umkm_product_id' => ['nullable', 'integer', 'exists:umkm_products,id'],
            'product_id' => ['nullable', 'integer', 'exists:umkm_products,id'],
            'buyer_name' => ['required', 'string', 'max:150'],
            'buyer_phone' => ['nullable', 'string', 'max:50'],
            'buyer_address' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'total_label' => ['nullable', 'string', 'max:50'],
            'qr_code_data' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(OrderStatus::values())],
            'note' => ['nullable', 'string'],
        ]);

        $productId = $data['umkm_product_id'] ?? $data['product_id'] ?? null;
        if (! $productId) {
            return ApiResponse::validation([
                'product_id' => ['Produk wajib dipilih.'],
            ]);
        }

        $product = UmkmProduct::query()
            ->where('umkm_user_id', $user->id)
            ->whereKey($productId)
            ->firstOrFail();

        if ($product->status !== ProductStatus::Aktif->value) {
            return ApiResponse::validation([
                'product_id' => ['Produk harus berstatus aktif untuk dibuatkan order.'],
            ]);
        }

        if (($product->stock_qty ?? 0) < ($data['quantity'] ?? 0)) {
            return ApiResponse::validation([
                'quantity' => ['Stok produk tidak mencukupi.'],
            ]);
        }

        $totalAmount = $data['total_amount'] ?? $this->resolveMoney($data['total_label'] ?? null);

        $order = UmkmOrder::create([
            'code' => CodeGenerator::order(),
            'umkm_product_id' => $product->id,
            'buyer_name' => $data['buyer_name'],
            'buyer_phone' => $data['buyer_phone'] ?? null,
            'buyer_address' => $data['buyer_address'],
            'quantity' => $data['quantity'],
            'total_amount' => $totalAmount,
            'qr_code_data' => $data['qr_code_data'] ?? null,
            'note' => $data['note'] ?? null,
            'status' => $data['status'] ?? OrderStatus::Diproses->value,
        ]);

        $order->forceFill([
            'code' => CodeGenerator::order($order->id),
        ])->save();

        $product->forceFill([
            'stock_qty' => $product->stock_qty - $data['quantity'],
            'status' => $product->stock_qty - $data['quantity'] <= 0 ? ProductStatus::Habis->value : $product->status,
        ])->save();

        return ApiResponse::created(ContractFormatter::order($order->load('product')), 'Order berhasil dibuat');
    }

    private function ensureRole(?User $user, string $role): void
    {
        abort_unless($user && $user->role === $role, 403, 'Forbidden');
    }

    private function resolvePrice(array $data): float
    {
        if (isset($data['price']) && $data['price'] !== null) {
            return (float) $data['price'];
        }

        return $this->resolveMoney($data['price_label'] ?? null);
    }

    private function resolveStock(array $data): int
    {
        if (isset($data['stock_qty']) && $data['stock_qty'] !== null) {
            return (int) $data['stock_qty'];
        }

        if (empty($data['stock_label'])) {
            return 0;
        }

        preg_match('/(\d+)/', (string) $data['stock_label'], $matches);

        return isset($matches[1]) ? (int) $matches[1] : 0;
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
