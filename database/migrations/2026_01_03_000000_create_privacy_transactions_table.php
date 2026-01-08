<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_transactions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('credential_id')->constrained('credentials')->cascadeOnDelete();

            $table->string('privacy_transaction_id');

            $table->string('result')->nullable();
            $table->string('status')->nullable();

            $table->integer('amount_cents')->nullable();
            $table->string('currency_code', 8)->nullable();

            $table->dateTime('date_authorized')->nullable();
            $table->dateTime('date_settled')->nullable();

            $table->string('descriptor')->nullable();
            $table->string('memo')->nullable();
            $table->string('mcc')->nullable();

            $table->string('card_uuid')->nullable();
            $table->unsignedBigInteger('card_id')->nullable();

            $table->json('data')->nullable();

            $table->timestamps();

            $table->unique(['credential_id', 'privacy_transaction_id'], 'privacy_tx_credential_txid_unique');
            $table->index(['credential_id', 'date_authorized']);
            $table->index(['credential_id', 'date_settled']);
            $table->index(['result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_transactions');
    }
};





