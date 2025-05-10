<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Contracts\ModelQuery;
use App\Services\Code;
use App\Services\Development\DescribeTableService;
use Inertia\Inertia;

class ApiQueryController
{
    public function __invoke(DescribeTableService $descriptionService)
    {
        return Inertia::render('API/QueryBuilderPage', [
            'models' => collect(Code::instancesOf(ModelQuery::class)
                ->getClasses())
                ->map(fn ($model) => $descriptionService->describe(new $model)),
        ]);
    }
}
