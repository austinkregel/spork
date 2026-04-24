<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sender_identifier')->nullable()->index();
            // Sometimes its actual emoji, sometimes custom emoji
            $table->string('emoji', 255);
            $table->string('matrix_event_id')->unique();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'person_id', 'emoji'], 'message_reactions_unique_person');
            $table->index(['message_id', 'emoji']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reactions');
    }
};
