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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();
            $table->string('external_guid')->unique()->nullable();
            $table->morphs('author');
            $table->datetime('last_modified')->nullable();
            $table->string('etag')->nullable();

            $table->string('headline')->nullable();
            $table->text('content')->nullable();

            $table->string('attachment')->nullable();
            $table->string('url', 2048);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
