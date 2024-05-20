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
    public function up(): void
    {
        Schema::create('server_actions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignIdFor(\App\Models\Server::class);
            $table->foreignIdFor(\App\Models\Spork\Script::class);
            $table->foreignIdFor(\App\Models\Credential::class);
            $table->foreignIdFor(\App\Models\User::class);

            $table->longText('output')->nullable();
            $table->longText('error')->nullable();

            $table->timestamp('should_run_at');
            $table->timestamp('started_run_at')->nullable();
            $table->timestamp('finished_run_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('server_actions');
    }
};
