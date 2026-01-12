<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('social_feeds')) {
            return;
        }

        Schema::create('social_feeds', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();

            $table->boolean('is_public')->default(false)->index();

            // Matches Conditionable interface expectations and ConditionService semantics.
            // When true, all conditions must pass (AND). When false, any passing condition is sufficient (OR).
            $table->boolean('must_all_conditions_pass')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_feeds');
    }
};
