<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_id')->unique();
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->enum('role', ['admin', 'operator', 'viewer'])->default('viewer');
            $table->boolean('is_active')->default(true);
            $table->string('phone_number')->nullable();
            $table->string('session_state')->nullable()->comment('State untuk multi-step conversation');
            $table->json('session_data')->nullable()->comment('Data sementara dalam sesi');
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_users');
    }
};
