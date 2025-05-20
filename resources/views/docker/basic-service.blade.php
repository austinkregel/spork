services:
@php
foreach ($projects as $index => $project) {
    extract($project);
    $volumes = [
        '${DOCKER_DIRECTORY}/appdata/' . $path . '/.env:/var/www/html/.env',
    ];

    if ($queue ?? false) {
        $actualProjects[] = [
            'path' => $path.'.queue',
            'image' => $image,
            'command' => 'bash /var/www/html/queue.sh',
            'volumes' => array_merge($volumes, [
                '${DOCKER_DIRECTORY}/appdata/'.$path.'/queue.sh:/var/www/html/queue.sh',
                '${DOCKER_DIRECTORY}/appdata/'.$path.'/queue.log:/var/www/html/storage/logs/laravel.log'
            ]),
            'dependencies' => $dependencies,
        ];
    }
    if ($cron ?? false) {
        $actualProjects[] = [
            'path' => $path.'.cron',
            'image' => $image,
            'command' => 'bash /var/www/html/cron.sh',
            'volumes' => array_merge($volumes, [
                '${DOCKER_DIRECTORY}/appdata/'.$path.'/cron.sh:/var/www/html/cron.sh',
                '${DOCKER_DIRECTORY}/appdata/'.$path.'/cron.log:/var/www/html/storage/logs/laravel.log'
            ]),
            'dependencies' => $dependencies,
        ];
    }
    if ($web ?? false) {
        $actualProjects[] = [
            'path' => $path.'.web',
            'image' => $image,
            'command' => 'bash /var/www/html/start.sh',
            'volumes' => array_merge($volumes, [
                '${DOCKER_DIRECTORY}/appdata/' . $path . '/start.sh:/var/www/html/start.sh',
                '${DOCKER_DIRECTORY}/appdata/'.$path.'/web-start.log:/var/www/html/storage/logs/laravel.log'
            ]),
            'dependencies' => $dependencies,
        ];
    }
    if ($websocket ?? false) {
        $actualProjects[] = [
            'path' => $path.'.websocket',
            'image' => $image,
            'command' => 'bash /var/www/html/websocket.sh',
            'volumes' => array_merge($volumes, [
                '${DOCKER_DIRECTORY}/appdata/' . $path . '/start.sh:/var/www/html/start.sh',
                '${DOCKER_DIRECTORY}/appdata/'.$path.'/websocket.log:/var/www/html/storage/logs/laravel.log'
            ]),
            'dependencies' => $dependencies,
        ];
    }

}

@endphp
@foreach ($actualProjects as $project)
@php(extract($project))
  {{ $path }}:
    extra_hosts:
      - host.docker.internal:host-gateway
    volumes:
@foreach($volumes as $volume)
      - {{ $volume }}
@endforeach
    networks:
      internal:
    depends_on:
@foreach($dependencies as $dependency)
      - {{ $dependency }}
@endforeach
    container_name: {{$path }}
    image: {{ $image }}
    command: {{ $command }}

@endforeach