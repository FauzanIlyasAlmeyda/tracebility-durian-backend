<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('first_name', 100);
            $table->string('last_name', 100);

            $table->string('phone', 20)->unique();

            $table->string('email', 191)->unique();

            $table->string('username', 50)->nullable()->unique();

            $table->string('password');

            $table->enum('role', [
                'petani',
                'pengepul',
                'distributor',
                'umkm',
                'konsumen'
            ]);

            $table->timestamp('email_verified_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamp('last_login_at')->nullable();

            $table->rememberToken();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
