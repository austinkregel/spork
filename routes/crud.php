<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

if (! function_exists('developerRoute')) {
    function developerRoute($name, $model)
    {
        cache()->rememberForever($name, fn () => $model);

        Route::prefix('crud')->group(function () use ($name) {
            Route::get($name, [\App\Http\Controllers\Spork\LocalAdminController::class, 'index'])->name($name.'.index');

            Route::post($name.'', [\App\Http\Controllers\Spork\LocalAdminController::class, 'store'])->name($name.'.store');
            Route::get($name.'/fields', [\App\Http\Controllers\Spork\LocalAdminController::class, 'fields'])->name($name.'.fields');
            Route::get($name.'/{'.$name.'}', [\App\Http\Controllers\Spork\LocalAdminController::class, 'show'])->name($name.'.show');
            // Updating
            Route::put($name.'/{'.$name.'}', [\App\Http\Controllers\Spork\LocalAdminController::class, 'update'])->name($name.'.update');
            Route::patch($name.'/{'.$name.'}', [\App\Http\Controllers\Spork\LocalAdminController::class, 'update']);
            // Restoring
            Route::post($name.'/{'.$name.'}/restore', [\App\Http\Controllers\Spork\LocalAdminController::class, 'restore'])->name($name.'.restore');
            // Soft-deleting
            Route::delete($name.'/{'.$name.'}', [\App\Http\Controllers\Spork\LocalAdminController::class, 'destroy'])->name($name.'.destroy');
            // Force delete
            Route::delete($name.'/{'.$name.'}/force', [\App\Http\Controllers\Spork\LocalAdminController::class, 'forceDestroy'])->name($name.'.forceDestroy');
        });
    }
}

developerRoute('accounts', App\Models\Finance\Account::class);
developerRoute('articles', App\Models\Article::class);
developerRoute('budgets', App\Models\Finance\Budget::class);
developerRoute('conditions', App\Models\Condition::class);
developerRoute('credentials', App\Models\Credential::class);
developerRoute('domains', App\Models\Domain::class);
developerRoute('external_rss_feeds', App\Models\ExternalRssFeed::class);
developerRoute('messages', App\Models\Message::class);
developerRoute('navigations', App\Models\Navigation::class);
developerRoute('pages', App\Models\Page::class);
developerRoute('people', App\Models\Person::class);
developerRoute('projects', App\Models\Project::class);
developerRoute('research', App\Models\Research::class);
developerRoute('scripts', App\Models\Spork\Script::class);
developerRoute('servers', App\Models\Server::class);
developerRoute('tags', App\Models\Tag::class);
developerRoute('tasks', App\Models\Task::class);
developerRoute('threads', App\Models\Thread::class);
developerRoute('transactions', App\Models\Finance\Transaction::class);
developerRoute('users', App\Models\User::class);
