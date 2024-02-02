<?php
declare(strict_types=1);
namespace App\Contracts\Services;

interface GeocodingServiceContract
{
    public function geocode(string $address): array;
}
