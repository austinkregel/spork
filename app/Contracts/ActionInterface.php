<?php

namespace App\Contracts;

interface ActionInterface
{
    // We really only care about automated discovery, the UI is handled seperately.
    // To help with auto discovery, we should return a friendly name, route, and use X for validation rules. If validatino fails it'll return a 422 response.
    // Empty validation arrays will not be processed.
}
