<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->timestamp('checked_in_at');
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'child_id', 'checked_out_at']);
            $table->index(['tenant_id', 'room_id', 'checked_out_at']);
            $table->unique(['tenant_id', 'child_id', 'checked_out_at'], 'tenant_child_active_check_in_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
