<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('domain_records', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Domain::class);
            // cloudflare using alphanumeric ids...
            $table->string('record_id')->index()->nullable();

            $table->string('name', 255);
            $table->string('type');
            $table->integer('ttl');
            $table->string('comment')->nullable();
            $table->json('tags')->nullable();
            $table->string('value', 2048)->nullable();

            $table->integer('timeout')->nullable();
            $table->boolean('proxied_through_cloudflare')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('domain_records');
    }
};
