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
        Schema::create('threads', function (Blueprint $table) {
            $table->id();
            $table->string('thread_id')->unique();

            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->string('rules')->nullable();
            $table->string('topic')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('origin_server_ts');
            $table->timestamps();
        });

        Schema::create('thread_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Person::class);
            $table->foreignIdFor(\App\Models\Thread::class);
            $table->dateTime('joined_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('threads');
        Schema::dropIfExists('thread_participants');
    }
};
