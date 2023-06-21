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
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            //Service server id
            $table->string('server_id')->index();
            $table->foreignIdFor(\App\Models\Credential::class)->nullable();
            $table->string('name');

            $table->integer('vcpu')->nullable();
            $table->integer('memory')->nullable();
            $table->integer('disk')->nullable();
            $table->unsignedFloat('cost_per_hour', 10, 8)->nullable();

            $table->string('ip_address')->nullable();
            $table->string('ip_address_v6')->nullable();
            $table->string('internal_ip_address')->nullable();
            $table->string('internal_ip_address_v6')->nullable();
            // This might be afead31ade.stoned.host, some way to resolve this specific server using internal DNS.
            $table->string('internal_url', 2048)->nullable();

            $table->dateTime('last_ping_at')->nullable();
            $table->dateTime('booted_at')->nullable();
            $table->dateTime('turned_off_at')->nullable();
            $table->string('os')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
