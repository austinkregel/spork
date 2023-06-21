<?php

declare(strict_types=1);

namespace App\Listeners\Pages;

use App\Events\Pages\PageCreated;
use App\Events\Pages\PageUpdated;
use App\Models\Page;

class GenerateNewRoutesFileFromDatabase
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PageCreated|PageUpdated $event): void
    {
        $pagesByDomain = Page::all()->groupBy('domain');

        $route = "<?php\n";

        foreach ($pagesByDomain as $domain => $pages) {
            $route .= "Route::domain('$domain')->group(function () {\n";
            foreach ($pages as $page) {
                $context = collect(compact('page'));
                $route .= "    Route::get('$page->uri', fn () => view('$page->view', json_decode('$context', true)))->middleware($page->middleware);\n";
            }

            $route .= "});\n";
        }

        file_put_contents(base_path('routes/generate-pages.php'), $route);
    }
}
