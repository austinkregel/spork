<?php

namespace App\Http\Controllers\Api\Actions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RunActionController extends Controller
{
    public function __construct()
    {

    }

    public function __invoke(Request $request, $action)
    {
        return call_user_func($action, $request);
    }
}
