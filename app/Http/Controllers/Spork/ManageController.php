<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Services\Development\DescribeTableService;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ManageController
{
    public function index()
    {
        return Inertia::render('Manage/Index', [
            'title' => 'Dynamic CRUD',
            'description' => [
                'fillable' => [],
            ],
        ]);
    }

    public function show($model)
    {
        $description = (new DescribeTableService())->describe(new $model);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $model::query()
            ->with(
                array_filter($description['includes'], fn ($relation) => ! in_array($relation, [
                    'tagsTranslated',
                ]))
            )
            ->paginate(request('limit', 15), ['*'], 'manage_page', request('manage_page', 1));

        $data = $paginator->items();
        $paginator = $paginator->toArray();

        unset($paginator['data']);

        return Inertia::render('Manage/Index', [
            'title' => 'CRUD '.Str::ucfirst(str_replace('_', ' ', Str::ascii((new $model)->getTable(), 'en'))),
            'description' => $description,
            'singular' => Str::singular((new $model)->getTable()),
            'plural' => Str::plural((new $model)->getTable()),
            'link' => '/'.(new $model)->getTable(),
            'apiLink' => '/api/crud/'.(new $model)->getTable(),
            'data' => $data,
            'paginator' => $paginator,
        ]);
    }
}
