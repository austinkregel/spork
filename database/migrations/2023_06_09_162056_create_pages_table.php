<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            // Columns
            $table->increments('id');
            $table->foreignIdFor(\App\Models\Domain::class)->nullable();
            $table->string('title');
            $table->string('uri');
            $table->string('slug')->nullable();
            $table->string('route');
            $table->json('middleware')->nullable();
            // 1 sentence
            $table->string('subtitle')->nullable();
            $table->text('excerpt')->nullable();
            $table->text('content')->nullable();
            $table->string('view')->nullable();
            $table->string('redirect', 2048)->nullable();
            $table->boolean('is_active')->default(true);
            $table->mediumInteger('sort_order')->unsigned()->default(0);
            $table->dateTime('published_at');;
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['domain_id', 'uri']);
            $table->unique(['domain_id', 'slug']);
            $table->unique(['domain_id', 'route']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
