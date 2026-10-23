<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('evento', 40);
            $table->string('auditavel_type', 40);
            $table->unsignedBigInteger('auditavel_id');
            $table->json('antes')->nullable();
            $table->json('depois')->nullable();
            $table->text('motivo')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['auditavel_type', 'auditavel_id']);
            $table->index('evento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
