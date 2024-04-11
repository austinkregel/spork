<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

class ApiQueryController
{
    public function __invoke(DescribeTableService $descriptionService)
    {
        return Inertia::render('API/QueryBuilderPage', [
            'models' => collect(\App\Services\Code::instancesOf(ModelQuery::class)
                ->getClasses())
                ->map(fn ($model) => $descriptionService->describe(new $model)),
        ]);
    }
}
