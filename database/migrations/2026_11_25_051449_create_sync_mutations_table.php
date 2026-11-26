<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_mutations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('mutation_id', 128);
            $table->string('entity_type', 64);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->unique(['workspace_id', 'mutation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_mutations');
    }
};
