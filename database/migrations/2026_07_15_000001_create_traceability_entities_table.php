<?php

use App\Enums\ConsumerTransactionStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\ReceiptCondition;
use App\Enums\ShipmentDestination;
use App\Enums\ShipmentStatus;
use App\Enums\HarvestBatchStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name', 150)->nullable();
            $table->string('role_label', 50)->nullable();
            $table->string('location', 191)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('contact', 50)->nullable();
            $table->string('avatar_path')->nullable();
            $table->timestamps();
        });

        Schema::create('farms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('farmer_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('province', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('village', 100)->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('harvest_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->foreignId('farmer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('farm_id')->nullable()->constrained('farms')->nullOnDelete();
            $table->string('farm_name_snapshot', 150);
            $table->string('variety', 100)->nullable();
            $table->string('grade', 50)->nullable();
            $table->decimal('quantity_kg', 12, 2)->default(0);
            $table->string('unit', 20)->default('kg');
            $table->unsignedInteger('fruit_count')->nullable();
            $table->date('harvest_date')->nullable();
            $table->string('status', 50)->default(HarvestBatchStatus::Created->value);
            $table->string('fertilizer', 150)->nullable();
            $table->string('harvest_method', 150)->nullable();
            $table->string('maturity_level', 150)->nullable();
            $table->string('shelf_life_estimate', 150)->nullable();
            $table->string('storage_suggestion', 191)->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable();
            $table->decimal('received_quantity_kg', 12, 2)->nullable();
            $table->unsignedInteger('received_fruit_count')->nullable();
            $table->string('verified_grade', 50)->nullable();
            $table->text('quality_notes')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('rejected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('harvest_batch_grade_breakdowns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('harvest_batch_id')->constrained()->cascadeOnDelete();
            $table->string('grade_label', 50)->nullable();
            $table->decimal('weight_kg', 12, 2)->default(0);
            $table->unsignedInteger('fruit_count')->nullable();
            $table->timestamps();
        });

        Schema::create('harvest_batch_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('harvest_batch_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('actor_label', 100)->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('event_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('collector_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('business_name', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('contact', 50)->nullable();
            $table->string('avatar_path')->nullable();
            $table->timestamps();
        });

        Schema::create('collector_shipments', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->foreignId('collector_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('destination_type', 50)->default(ShipmentDestination::Umkm->value);
            $table->decimal('total_weight_kg', 12, 2)->default(0);
            $table->unsignedInteger('total_fruit_count')->default(0);
            $table->timestamp('packaged_at')->nullable();
            $table->text('warehouse_note')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status', 50)->default(ShipmentStatus::ReadyToShip->value);
            $table->timestamps();
        });

        Schema::create('collector_shipment_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('collector_shipment_id')->constrained('collector_shipments')->cascadeOnDelete();
            $table->foreignId('harvest_batch_id')->nullable()->constrained('harvest_batches')->nullOnDelete();
            $table->string('source_code_snapshot', 50);
            $table->string('source_grade_snapshot', 50)->nullable();
            $table->decimal('source_weight_kg', 12, 2)->nullable();
            $table->unsignedInteger('source_fruit_count')->nullable();
            $table->string('source_variety_snapshot', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('distributor_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('business_name', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('contact', 50)->nullable();
            $table->string('avatar_path')->nullable();
            $table->timestamps();
        });

        Schema::create('distributor_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->foreignId('distributor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('collector_shipment_id')->nullable()->constrained('collector_shipments')->nullOnDelete();
            $table->decimal('expected_weight_kg', 12, 2)->nullable();
            $table->unsignedInteger('expected_fruit_count')->nullable();
            $table->decimal('received_weight_kg', 12, 2)->nullable();
            $table->unsignedInteger('received_fruit_count')->nullable();
            $table->string('condition', 50)->default(ReceiptCondition::Good->value);
            $table->timestamp('received_at')->nullable();
            $table->text('discrepancy_note')->nullable();
            $table->text('quality_note')->nullable();
            $table->timestamps();
        });

        Schema::create('umkm_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name', 150)->nullable();
            $table->string('owner_name', 150)->nullable();
            $table->text('about')->nullable();
            $table->text('address')->nullable();
            $table->string('contact', 50)->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });

        Schema::create('umkm_products', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->foreignId('umkm_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('category', 100)->nullable();
            $table->string('name', 150);
            $table->decimal('price', 14, 2)->default(0);
            $table->unsignedInteger('stock_qty')->default(0);
            $table->text('description')->nullable();
            $table->string('status', 50)->default(ProductStatus::Aktif->value);
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('umkm_product_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('umkm_product_id')->constrained('umkm_products')->cascadeOnDelete();
            $table->foreignId('harvest_batch_id')->nullable()->constrained('harvest_batches')->nullOnDelete();
            $table->string('source_code_snapshot', 50);
            $table->decimal('weight_kg', 12, 2)->nullable();
            $table->unsignedInteger('fruit_count')->nullable();
            $table->timestamps();
        });

        Schema::create('umkm_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->foreignId('umkm_product_id')->constrained('umkm_products')->cascadeOnDelete();
            $table->string('buyer_name', 150);
            $table->string('buyer_phone', 50)->nullable();
            $table->text('buyer_address')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->text('qr_code_data')->nullable();
            $table->text('note')->nullable();
            $table->string('status', 50)->default(OrderStatus::Diproses->value);
            $table->timestamps();
        });

        Schema::create('consumer_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('avatar_path')->nullable();
            $table->timestamps();
        });

        Schema::create('consumer_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->foreignId('consumer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('umkm_product_id')->nullable()->constrained('umkm_products')->nullOnDelete();
            $table->string('product_name_snapshot', 150)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('buyer_coordinates', 150)->nullable();
            $table->string('payment_status', 50)->default('unpaid');
            $table->string('status', 50)->default(ConsumerTransactionStatus::Processing->value);
            $table->text('qr_code_data')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumer_transactions');
        Schema::dropIfExists('consumer_profiles');
        Schema::dropIfExists('umkm_orders');
        Schema::dropIfExists('umkm_product_sources');
        Schema::dropIfExists('umkm_products');
        Schema::dropIfExists('umkm_profiles');
        Schema::dropIfExists('distributor_receipts');
        Schema::dropIfExists('distributor_profiles');
        Schema::dropIfExists('collector_shipment_sources');
        Schema::dropIfExists('collector_shipments');
        Schema::dropIfExists('collector_profiles');
        Schema::dropIfExists('harvest_batch_events');
        Schema::dropIfExists('harvest_batch_grade_breakdowns');
        Schema::dropIfExists('harvest_batches');
        Schema::dropIfExists('farms');
        Schema::dropIfExists('farmer_profiles');
    }
};
