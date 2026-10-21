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
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('converted_from_license_id')->nullable()->constrained('licenses')->nullOnDelete();
            $table->string('key')->unique();
            $table->string('type');
            $table->string('status')->default('active');
            $table->unsignedSmallInteger('max_activations');
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('updates_until')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
