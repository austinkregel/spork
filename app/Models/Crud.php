<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Marker for models exposed via the auto-generated JSON CRUD API and /-/manage UI.
 *
 * Optional: add `public static function pillar(): ?Pillar` on a concrete model to surface that
 * table under the matching pillar’s glass sub-navigation (CrudPillarNavigationCollector).
 */
interface Crud {}
