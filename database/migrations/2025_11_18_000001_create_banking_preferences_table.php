<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banking_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\User::class)->unique()->constrained()->cascadeOnDelete();
            $table->json('pinned_accounts')->default(json_encode([]));
            $table->json('pinned_budgets')->default(json_encode([]));
            $table->json('settings')->default(json_encode([]));
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banking_preferences');
    }
};


