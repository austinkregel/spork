<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_transaction_matches', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('privacy_transaction_id')->constrained('privacy_transactions')->cascadeOnDelete();

            $table->string('match_method')->nullable();
            $table->unsignedTinyInteger('confidence')->nullable();

            $table->timestamps();

            $table->unique(['privacy_transaction_id'], 'privacy_match_privacy_tx_unique');
            $table->unique(['transaction_id', 'privacy_transaction_id'], 'privacy_match_tx_privacy_unique');
            $table->index(['transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_transaction_matches');
    }
};
