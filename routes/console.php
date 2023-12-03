<?php

use League\Flysystem\UnableToRetrieveMetadata;
use Symfony\Component\Finder\SplFileInfo;

Artisan::command('scan-for-files', function () {
    /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
    $storage = \Illuminate\Support\Facades\Storage::disk('ftp');

    $storage::macro('information', function ($path) {
        /** @var \League\Flysystem\Filesystem $driver */
        $driver = $this->getDriver();
        /** @var \League\Flysystem\Ftp\FtpAdapter $adapter */
        $adapter = $this->getAdapter();
        $adapter->customMethod = function ($path) {
            $location = $this->prefixer()->prefixPath($path);

            if ($this->isPureFtpdServer) {
                $location = $this->escapePath($location);
            }

            $object = ftp_raw($this->connection(), 'STAT ' . Str::ascii($location));
            if (empty($object) || count($object) < 3 || substr($object[1], 0, 5) === "ftpd:") {
                $object = ftp_raw($this->connection(), 'STAT ' . $this->escapePath($location));
            }

            if (empty($object) || count($object) < 3 || substr($object[1], 0, 5) === "ftpd:") {
                dd($object, error_get_last());
                throw UnableToRetrieveMetadata::create($path, '', error_get_last()['message'] ?? '');
            }

            $item = $object[1];
            $item = preg_replace('#\s+#', ' ', trim($item), 7);
            $parts = explode(' ', $item, 9);
            [$permissions, $number, $owner, $group, $size, $month, $day, $timeOrYear, $path] = $parts;
            $permissions = $this->normalizePermissions($permissions);
            $metadata = compact(
                'permissions',
                'number',
                'owner',
                'group',
                'size',
                'month',
                'day',
                'timeOrYear',
                'path'
            );

            $metadata['last_modified'] = $this->normalizeUnixTimestamp($month, $day, $timeOrYear);

            return $metadata;
        };
       return $adapter->customMethod->bindTo($adapter)->call($adapter, $path);
    });

    $folders = [
        '/Shows',
        '/Music',
        '/Movies',
        '/Downloads',
    ];

    $folderCount = count($folders);
    foreach ($folders as $i => $folder) {
        $this->info("Processing folder: $i/$folderCount - $folder");
        $files = $storage->allFiles($folder);
        $this->info("Found files: ".$count = count($files));
        /** @var \Symfony\Component\Console\Helper\ProgressBar $bar */
        $bar = $this->output->createProgressBar($count);
        $bar->clear();
        $bar->display();
        foreach ($files as $file) {
                [
                    'size' => $size,
                    'last_modified' => $last_modified,
                ] = $storage->information($file);

            try {
                $file = \App\Models\File::firstOrCreate([
                    'pretty_name' => Str::ascii(basename($file)),
                    'name' => Str::ascii(basename($file)),
                    'path' => trim($file),
                ], [
                    'size' => $size,
                    'path' => trim($file),
                    'ascii_path' => Str::ascii(trim($file)),
                    'last_modified' => \Carbon\Carbon::parse($last_modified),
                ]);

                if (\Carbon\Carbon::parse($last_modified)->format('Y-m-d H:i:s') !== \Carbon\Carbon::parse($file->last_modified)->format('Y-m-d H:i:s')) {
                    $file->last_modified = \Carbon\Carbon::parse($last_modified);
                }

                if ($size !== $file->size) {
                    $file->size = $size;
                }

                if ($file->isDirty()) {
                    $file->save();
                }
            } catch (\Throwable $e) {
                dd($file, $e,);
            }

            $bar->advance();
        }
        $bar->finish();
    }
});

Artisan::command('file-check', function () {
    $page = 1;
    $qualities = [
        0 => 'Unknown',
1 => 'SDTV',
2 => 'DVD',
3 => 'WEBDL-1080p',
4 => 'HDTV-720p',
5 => 'WEBDL-720p',
6 => 'Bluray-720p',
7 => 'Bluray-1080p',
8 => 'WEBDL-480p',
9 => 'HDTV-1080p',
10 => 'Raw-HD',
12 => 'WEBRip-480p',
13 => 'Bluray-480p',
14 => 'WEBRip-720p',
15 => 'WEBRip-1080p',
16 => 'HDTV-2160p',
17 => 'WEBRip-2160p',
18 => 'WEBDL-2160p',
19 => 'Bluray-2160p',
20 => 'Bluray-1080p Remux',
21 => 'Bluray-2160p Remux',

];

    do {
        $series = App\Models\Sqlite\Episode::query()->with('file', 'series')->paginate(100, ['*'], 'page', $page++);

        foreach ($series as $show) {
            //        $path = str_replace('/volume1', '', $show->Path);
            foreach ([$show->file] as $file) {
                if (empty($file)) {
                    continue;
                }


                $parts = explode('.', $file->RelativePath);
                $extension = end($parts);

                $season = $show->SeasonNumber ? 'Season ' . $show->SeasonNumber : null;

                $seasonNumber = $show->SeasonNumber < 10 ? '0' . $show->SeasonNumber : $show->SeasonNumber;
                $episodeNumber = $show->EpisodeNumber < 10 ? '0' . $show->EpisodeNumber : $show->EpisodeNumber;

                $quality = $qualities[json_decode($file->Quality, true)['quality']];

                $episodeName = sprintf('%s - S%sE%s - %s %s.%s', $show->series->Title, $seasonNumber, $episodeNumber, $show->Title, $quality, $extension);

                $standardPath = '/Shows/' . implode('/', array_filter([
                    $show->series->Title,
                    $season,
                    $episodeName,
                ]));

                $isAnime = json_decode($file->MediaInfo)->videoFps <= 25;
                $audioLanguages = json_decode($file->MediaInfo)->audioLanguages;
                $subtitledLanguages = json_decode($file->MediaInfo)->subtitles;

                if ($isAnime && empty($audioLanguages)) {
                    $audioLanguages = 'Japanese';
                } elseif (!$isAnime && empty($audioLanguages)) {
                    $audioLanguages = 'English';
                }


                $actualPath = str_replace('/volume1', '', $show->series->Path) . '/' . $file->RelativePath;

                if ($standardPath == $actualPath) {
                    continue;
                }

                dd($show, $audioLanguages, $subtitledLanguages, $standardPath, $actualPath);

                Storage::disk('ftp')->move();
                dd($show, Storage::disk('ftp')->exists());
            }
        }
    } while ($series->hasMorePages());
});

Artisan::command('socialite:discover', function () {
    // discover installed socialite providers.
//    $autoload = require './vendor/autoload.php';
//
//    $providers = [];
//
//    foreach ($autoload->getClassMap() as $class => $location) {
//        if (str_contains($location, 'symfony')) {
//            continue;
//        }
//
//        if (str_contains($class, 'Provider')) {
//            $implementations = class_implements_recursive($class);
//            if (in_array(\Laravel\Socialite\Two\ProviderInterface::class, $implementations)) {
//                $providers = array_merge($providers, $implementations);
//            }
//        }
//    }
    $files = collect(json_decode(file_get_contents(base_path('composer.lock')))->packages)
        ->filter(function ($jsonFile) {
            if (isset($jsonFile->name)) {
                if ($jsonFile->name === 'socialiteproviders/manager') {
                    return true;
                }
            }

            if (isset($jsonFile->require)) {
                return isset($jsonFile->require->{'socialiteproviders/manager'});
            }
            if (isset($jsonFile->{'require-dev'})) {
                return isset($jsonFile->{'require-dev'}->{'socialiteproviders/manager'});
            }

            return false;
        });

    $allFiles = \App\Services\Code::composerMappedClasses();
    $installed = $files->map(function ($contents) use ($allFiles) {
        $namespaces = $contents->autoload->{'psr-4'};


        return [
            'name' => $contents->name,
            'description' => $contents->description,
            'version' => $contents->version,
            'time' => \Carbon\Carbon::parse($contents->time)->format('F j, Y H:i:s'),
            'installed' => true,
            'drivers' => collect($allFiles)
                ->filter(function($value, $class) use ($namespaces) {
                    foreach ($namespaces as $psr4Namespace => $sourceFolder) {
                        if (str_starts_with($class, $psr4Namespace)) {
                            return true;
                        }
                    }

                    return false;
                })
                ->map(function ($filePath, $class) {
                    $contentsOfFile = (file_get_contents($filePath));
                    preg_match('/extendSocialite..([\w]+)\'./i', $contentsOfFile , $matches);
                    if (!isset($matches[1])) {
                        return null;
                    }

                    return $matches[1];
                })->filter(),
        ];
    })->values();
    $page = 1;
    $installedNames = $installed->map->name;

    $uninstalled = [];
    do {
        $response = \Illuminate\Support\Facades\Http::get('https://packagist.org/search.json?q=socialiteproviders&per_page=100&page='.$page++)->json();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $response['results'],
            $response['total'],
            100,
            $page - 1,
        );

        foreach ($paginator->items() as $provider) {
            if ($installedNames->contains($provider['name'])) {
                continue;
            }

            $uninstalled[] = [
                'name' => $provider['name'],
                'description' => $provider['description'],
                'downloads' => $provider['downloads'],
                'installed' => false,
            ];
        }
    } while ($paginator->hasMorePages());

    // We need a way to add the handle method to the event service provider.
    // Also, we might want to change how we identify enabled/disabled values.
    file_put_contents(storage_path('provider-information.json'),  json_encode([
        'installed' => $installed,
        'notInstalled' => collect($uninstalled)->sortByDesc('downloads')->values(),
    ]));
});

//Artisan::command('test', function () {
//    $client = new \App\Services\Matrix\MatrixClient('beeper@kregel.email', 'beeper.com', );
//
//
//    dd($client->discover());
//    $request = $client->requestCodeForBeeper('beeper@kregel.email');
//    $request = $client->loginWithBeeperCode('beeper@kregel.email', '741408');
//    $request = $client->loginWithJwt("");
//    dd($request,); //$client->discover());
//    $filesystem = new \Illuminate\Filesystem\Filesystem();
//    $entities = collect($filesystem->allFiles(storage_path('entities')))
//    ->map(function (SplFileInfo $e) {
//        return $e->getFilename();
//    })->sort();
//    $intents = collect($filesystem->allFiles(storage_path('intents')))
//    ->map(function (SplFileInfo $e) {
//         return $e->getFilename();
//    })->filter(fn ($fileName) => !\Illuminate\Support\Str::contains($fileName, [' ', '_', '-']));
//
//    dd($entities, $intents);
//});

Artisan::command('yet-another', function () {

    $models = \App\Services\Code::instancesOf(App\Models\Crud::class);

    $crudClasses = $models->getClasses();

    dd(array_map(fn ($class) => \Illuminate\Support\Str::slug(\Illuminate\Support\Str::headline((new $class)->getTable())), $crudClasses));
});

Artisan::command('more-test', function () {
    dd(
        route(((new \App\Models\Page())->getTable().'.store'))
    );
});

Artisan::command('inbox', function () {
    $mailbox = new PhpImap\Mailbox(
        sprintf('{'.env('IMAP_HOST').':'.env('IMAP_PORT').'/imap/'.env('IMAP_ENCRYPTION', 'notls').'}INBOX'), // IMAP server and mailbox folder
        env('IMAP_USERNAME'), // Username for the before configured mailbox
        env('IMAP_PASSWORD'), // Password for the before configured username
        storage_path(), // Directory, where attachments will be saved (optional)
        'UTF-8', // Server encoding (optional)
        true, // Trim leading/ending whitespaces of IMAP path (optional)
        false // Attachment filename mode (optional; false = random filename; true = original filename)
    );

    try {
        // Get all emails (messages)
        // PHP.net imap_search criteria: http://php.net/manual/en/function.imap-search.php

        dd($mailsIds);
    } catch(PhpImap\Exceptions\ConnectionException $ex) {
        echo "IMAP connection failed: " . implode(",", $ex->getErrors('all'));
        die();
    }

    dd($mailboxes);
});
