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
        Schema::create('domain_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Domain::class);
            $table->string('query_name');
            $table->string('response_code');
            $table->string('origin');
            $table->unsignedBigInteger('query_count');
            $table->unsignedBigInteger('uncached_count');
            $table->unsignedBigInteger('stale_count');
            $table->dateTime('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_analytics');
    }
};
