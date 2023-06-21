<?php

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
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();

            $table->string('verification_key')->unique();

            // Cloudflare uses hashes, namecheap uses IDs.
            $table->string('cloudflare_id')->nullable();
            $table->foreignIdFor(\App\Models\Credential::class)->nullable();
            $table->string('domain_id')->nullable();

            $table->dateTime('registered_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
