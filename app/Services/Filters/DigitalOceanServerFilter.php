<?php

declare(strict_types=1);

namespace App\Services\Filters;

use DigitalOceanV2\Entity\Droplet;
use DigitalOceanV2\Entity\Network;

class DigitalOceanServerFilter extends Filter
{
    /**
     * @param  Droplet  $server
     */
    public function filter($server): array
    {
        return [
            'id' => $server->id,
            'name' => $server->name,
            'tags' => $server->tags,
            'cpu' => $server->size->vcpus,
            'memory' => $server->size->memory,
            'cost' => $server->size->priceHourly,
            'disk' => $server->size->disk,
            'region' => $server->region->slug,
            'features' => $server->features,
            'image' => sprintf('%s %s', $server->image->distribution, $server->image->name),
            'status' => $server->status,
            'volumes' => $server->volumeIds,
            'backups' => $server->backupsEnabled,
            'networks' => array_reduce($server->networks, fn (array $networks, Network $network) => array_merge($networks, [
                $network->type.'_v'.$network->version => $network->ipAddress,
            ]), []),
        ];
    }
}
