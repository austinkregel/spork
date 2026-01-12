<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('automations')) {
            Schema::create('automations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug')->index();
                $table->boolean('enabled')->default(true)->index();
                $table->string('cron_expression')->nullable();
                $table->string('timezone')->nullable();
                $table->unsignedInteger('pacing_per_host_ms')->nullable();
                $table->unsignedInteger('max_concurrency')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('automation_steps')) {
            Schema::create('automation_steps', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('automation_id')->constrained('automations')->cascadeOnDelete();
                $table->unsignedInteger('order')->default(0)->index();
                $table->string('type')->index(); // MVP: 'dusk'
                $table->json('config')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('automation_operations')) {
            Schema::create('automation_operations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('automation_id')->constrained('automations')->cascadeOnDelete();
                $table->timestamp('should_run_at')->index();
                $table->timestamp('started_run_at')->nullable()->index();
                $table->timestamp('finished_run_at')->nullable()->index();
                $table->string('queue')->nullable();
                $table->string('queueConnection')->nullable();
                $table->longText('output')->nullable();
                $table->text('error')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_operations');
        Schema::dropIfExists('automation_steps');
        Schema::dropIfExists('automations');
    }
};
