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
        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignIdFor(\App\Models\User::class);
            // dns, servers, registrar (controls ns records), cloudflare
            $table->string('type')->index();
            // namecheap, cloudflare, digital-ocean, vultr, ovhcloud, google, other random APIs
            $table->string('service')->index();
            $table->string('api_key', 2048)->nullable();
            $table->string('secret_key', 2048)->nullable();
            $table->string('access_token', 2048)->nullable();
            $table->string('refresh_token')->nullable();
            $table->json('settings')->nullable();

            $table->dateTime('enabled_on')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
