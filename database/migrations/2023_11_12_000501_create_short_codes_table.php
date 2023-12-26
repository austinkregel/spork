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
        Schema::create('short_codes', function (Blueprint $table) {
            $table->id()->startingValue(58101822);
            $table->foreignIdFor(\App\Models\User::class);
            $table->string('short_code', 255)->unique()->index();
            $table->string('long_url', 4096);
            $table->boolean('is_enabled');
            // like http status
            $table->integer('status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('short_codes');
    }
};
