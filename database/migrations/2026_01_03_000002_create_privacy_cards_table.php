<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_cards', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('credential_id')->constrained('credentials')->cascadeOnDelete();

            $table->string('card_token');

            $table->string('state')->nullable();
            $table->string('type')->nullable();

            $table->string('memo')->nullable();
            $table->string('descriptor')->nullable();

            $table->integer('spend_limit_cents')->nullable();
            $table->string('spend_limit_duration')->nullable();

            $table->json('data')->nullable();

            $table->timestamps();

            $table->unique(['credential_id', 'card_token'], 'privacy_cards_credential_token_unique');
            $table->index(['credential_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_cards');
    }
};





