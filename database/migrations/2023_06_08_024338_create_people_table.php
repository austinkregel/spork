<?php

declare(strict_types=1);

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
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->string('primary_number')->nullable();
            $table->string('primary_address')->nullable();
            $table->string('primary_email')->nullable();

            $table->string('pronouns')->nullable();
            $table->date('birthdate')->nullable();

            $table->json('phone_numbers')->nullable();
            $table->json('addresses')->nullable();
            $table->json('emails')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
