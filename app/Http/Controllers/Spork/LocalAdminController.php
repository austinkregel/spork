<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Contracts\ModelQuery;
use App\Http\Requests\Dynamic\CreateRequest;
use App\Http\Requests\Dynamic\DeleteRequest;
use App\Http\Requests\Dynamic\ForceDeleteRequest;
use App\Http\Requests\Dynamic\IndexRequest;
use App\Http\Requests\Dynamic\RestoreRequest;
use App\Http\Requests\Dynamic\UpdateRequest;
use App\Http\Requests\Dynamic\ViewRequest;
use App\Services\ActionFilter;
use App\Services\Development\DescribeTableService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\QueryBuilder\AllowedFilter as Filter;
use Spatie\QueryBuilder\QueryBuilder;

class LocalAdminController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function fields(IndexRequest $request)
    {
        return response()->json((new DescribeTableService)->describe($this->getModel($request)));
    }

    protected function getModel(Request $request)
    {
        $parts = $request->path();
        $split = explode('/', $parts);

        return cache()->get(end($split));
    }

    /**
     * @throws Exception
     */
    public function index(IndexRequest $request)
    {
        $class = $this->getModel($request);
        $model = new $class;
        $action = new ActionFilter(request()->get('action', 'paginate:14'));

        $description = (new DescribeTableService)->describe($model);

        $query = QueryBuilder::for($class)
            ->allowedFields($description['fields'])
            ->allowedFilters(array_merge([
                Filter::scope('q'),
            ], $description['filters']))
            ->allowedIncludes($description['includes'])
            ->allowedSorts($description['sorts']);

        return $action->execute($query);
    }

    public function store(CreateRequest $request)
    {
        $model = $this->getModel($request);
        $description = (new DescribeTableService)->describe(new $model);

        $request->validate(array_reduce($description['required'], fn ($all, $field) => array_merge(
            $all,
            [$field => 'required']
        ), []));
        /** @var ModelQuery $resource */
        $resource = new $model;
        $resource->fill($request->all());
        $resource->save();

        return $resource->refresh();
    }

    public function show(ViewRequest $request, ModelQuery $model, $abstractEloquentModel = null)
    {
        $query = QueryBuilder::for($model = $this->getModel($request));

        return $query->find($abstractEloquentModel) ?? response([
            'message' => 'No resource found by that id.',
        ], 404);
    }

    public function update(UpdateRequest $request, $abstractEloquentModel = null)
    {
        $abstractEloquentModel->update($request->all());

        return $abstractEloquentModel->refresh();
    }

    public function destroy(DeleteRequest $request, ModelQuery $abstractEloquentModel)
    {
        $abstractEloquentModel->delete();

        return response('', 204);
    }

    public function forceDestroy(ForceDeleteRequest $request, ModelQuery $abstractEloquentModel)
    {
        $model = new $this->getModel($request);
        if (! $model->usesSoftdeletes()) {
            abort(404, 'You cannot force delete an item of this type.');

            return;
        }

        $abstractEloquentModel->forceDelete();

        return response('', 204);
    }

    public function restore(RestoreRequest $request, ModelQuery $model, ModelQuery $abstractEloquentModel)
    {
        $model = new $this->getModel($request);
        if (! $model->usesSoftdeletes()) {
            abort(404, 'You cannot restore an item of this type.');

            return;
        }

        $abstractEloquentModel->restore();

        return $abstractEloquentModel->refresh();
    }
}
