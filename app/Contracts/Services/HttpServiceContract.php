<?php

declare(strict_types=1);

namespace App\Contracts\Services;

interface HttpServiceContract
{
    public function get($path, $data = null);

    public function post($path, $data = null);

    public function put(string $path, $data = null);

    public function patch($path, $data = null);

    public function delete(string $path, $data = null);

    public function toArray();
}

