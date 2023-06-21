<?php

namespace App\Http\Controllers;

use App\Contracts\ModelQuery;
use App\Http\Requests\Dynamic\CreateRequest;
use App\Http\Requests\Dynamic\DeleteRequest;
use App\Http\Requests\Dynamic\ForceDeleteRequest;
use App\Http\Requests\Dynamic\IndexRequest;
use App\Http\Requests\Dynamic\RestoreRequest;
use App\Http\Requests\Dynamic\UpdateRequest;
use App\Http\Requests\Dynamic\ViewRequest;
use App\Services\ActionFilter;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter as Filter;
use Spatie\QueryBuilder\QueryBuilder;

class LocalAdminController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function fields(IndexRequest $request, Model $model)
    {
        $fields = array_map(fn ($query) => $query->Field, DB::select('describe '. (new $model)->getTable()));

        $returnTypes = array_reduce(get_class_methods($model), function ($allClassMethods, $method) use ($model) {
            $ref = new \ReflectionMethod($model, $method);

            $type = $ref->getReturnType();
            if (empty($type)) {
                return $allClassMethods;
            }

            return array_merge($allClassMethods,
                [
                    $method => $type,
                ]);
        }, []);

        $methodsThatReturnAClass = array_filter($returnTypes, fn (\ReflectionNamedType $type) => class_exists($type->getName()));
        $relations = array_filter($methodsThatReturnAClass, function ($type) {
            $c = new \ReflectionClass($type->getName());

            if (!empty($parentClass = $c->getParentClass())) {
                if (!empty($parentParentClass = $parentClass->getParentClass())) {
                    if ($parentParentClass->getName() === \Illuminate\Database\Eloquent\Relations\Relation::class) {
                        return true;
                    }
                }
                if ($parentClass->getName() === \Illuminate\Database\Eloquent\Relations\Relation::class) {
                    return true;
                }
            }

            return false;
        });

        return response()->json([
            'fields' => $fields,
            'includes' => array_keys($relations),
            'sorts' => [],
            'filters' => [],
            'actions' => [
                'get',
                'paginate:14',
                'first'
            ],
        ]);
    }

    /**
     * @throws Exception
     */
    public function index(IndexRequest $request,  ModelQuery $model)
    {
        $action = new ActionFilter(request()->get('action', 'paginate:14'));

        $returnTypes = array_reduce(get_class_methods($model), function ($allClassMethods, $method) use ($model) {
            $ref = new \ReflectionMethod($model, $method);

            $type = $ref->getReturnType();
            if (empty($type)) {
                return $allClassMethods;
            }

            return array_merge($allClassMethods, [
                $method => $type,
            ]);
        }, []);

        $methodsThatReturnAClass = array_filter($returnTypes, fn (\ReflectionNamedType $type) => class_exists($type->getName()));
        $relations = array_filter($methodsThatReturnAClass, function ($type) {
            $c = new \ReflectionClass($type->getName());

            if (!empty($parentClass = $c->getParentClass())) {
                if (!empty($parentParentClass = $parentClass->getParentClass())) {
                    if ($parentParentClass->getName() === \Illuminate\Database\Eloquent\Relations\Relation::class) {
                        return true;
                    }
                }
                if ($parentClass->getName() === \Illuminate\Database\Eloquent\Relations\Relation::class) {
                    return true;
                }

                if ($parentClass->getName() === \Illuminate\Database\Eloquent\Relations\MorphOneOrMany::class) {
                    return true;
                }
            }

            return false;
        });

        $query = QueryBuilder::for(get_class($model))
            ->allowedFields(array_map(fn ($query) => $query->Field, DB::select('describe '. (new $model)->getTable())))
            ->allowedFilters(array_merge([
                Filter::scope('q')
            ]))
            ->allowedIncludes(array_keys($relations))
            ->allowedSorts([
                'id', 'updated_at', 'created_at',
                'name'
            ]);

        return $action->execute($query);
    }

    public function store(CreateRequest $request, ModelQuery $model)
    {
        /** @var ModelQuery $resource */
        $resource = new $model;
        $resource->fill($request->all());
        $resource->save();
        return $resource->refresh();
    }

    public function show(ViewRequest $request, ModelQuery $model, $abstractEloquentModel = null)
    {
        $query = QueryBuilder::for(get_class($model));

        return $query->find($abstractEloquentModel) ?? response([
            'message' => 'No resource found by that id.'
        ], 404);
    }

    public function update(UpdateRequest $request, ModelQuery $model, $abstractEloquentModel= null)
    {
        $abstractEloquentModel->update($request->all());

        return $abstractEloquentModel->refresh();
    }

    public function destroy(DeleteRequest $request, ModelQuery $model, ModelQuery $abstractEloquentModel)
    {
        $abstractEloquentModel->delete();

        return response('', 204);
    }

    public function forceDestroy(ForceDeleteRequest $request, ModelQuery $model, ModelQuery $abstractEloquentModel)
    {
        if (!$model->usesSoftdeletes()) {
            abort(404, "You cannot force delete an item of this type.");
            return;
        }

        $abstractEloquentModel->forceDelete();

        return response('', 204);
    }

    public function restore(RestoreRequest $request, ModelQuery $model, ModelQuery $abstractEloquentModel)
    {
        if (!$model->usesSoftdeletes()) {
            abort(404, "You cannot restore an item of this type.");
            return;
        }

        $abstractEloquentModel->restore();

        return $abstractEloquentModel->refresh();
    }
}
