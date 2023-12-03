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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Person::class, 'from_person');

            $table->foreignIdFor(\App\Models\Thread::class)->nullable();

            $table->string('type');
            $table->string('event_id')->unique();

            $table->timestamp('originated_at');

            // Maybe it's an image
            $table->string('thumbnail_url', 2048)->nullable();

            $table->boolean('is_decrypted');

            $table->text('message')->nullable();
            $table->text('html_message')->nullable();

            // Encryption information to decrypt later?
            // Email settings? What if every email had a thread by the sender, and all messages were subsiqent emails?
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
