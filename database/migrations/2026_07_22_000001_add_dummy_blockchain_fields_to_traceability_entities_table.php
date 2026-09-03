<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('harvest_batches', function (Blueprint $table): void {
            $table->string('blockchain_network', 50)->default('dummy-ledger')->after('rejected_at');
            $table->string('blockchain_status', 50)->default('simulated')->after('blockchain_network');
            $table->string('blockchain_anchor_ref', 100)->nullable()->after('blockchain_status');
            $table->string('blockchain_tx_hash', 128)->nullable()->after('blockchain_anchor_ref');
            $table->unsignedBigInteger('blockchain_block_number')->nullable()->after('blockchain_tx_hash');
            $table->json('blockchain_payload')->nullable()->after('blockchain_block_number');
            $table->timestamp('blockchain_synced_at')->nullable()->after('blockchain_payload');
        });

        Schema::table('harvest_batch_events', function (Blueprint $table): void {
            $table->string('previous_ledger_hash', 64)->nullable()->after('metadata');
            $table->string('ledger_hash', 64)->nullable()->after('previous_ledger_hash');
            $table->unsignedInteger('ledger_height')->nullable()->after('ledger_hash');
        });
    }

    public function down(): void
    {
        Schema::table('harvest_batch_events', function (Blueprint $table): void {
            $table->dropColumn([
                'previous_ledger_hash',
                'ledger_hash',
                'ledger_height',
            ]);
        });

        Schema::table('harvest_batches', function (Blueprint $table): void {
            $table->dropColumn([
                'blockchain_network',
                'blockchain_status',
                'blockchain_anchor_ref',
                'blockchain_tx_hash',
                'blockchain_block_number',
                'blockchain_payload',
                'blockchain_synced_at',
            ]);
        });
    }
};
