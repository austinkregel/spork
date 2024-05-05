<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\BuildMetaDataFile;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class LibraryMetaDataScan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:library-meta-data-scan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // ...$filesystem->directories('/media/Downloads'),
        $supportedTypes = [
            // Audio
            'mp3',
            'flac',
            'm3u',
            'm4a',
            'ogg',
            // ebooks
            'pdf',
            'epub',
            //video
            'mp4',
            'mkv',
            'm4v',
            'm2ts',
            'part',
            'mkv.part',
            // sub titles
            'ts',
            'srt',
            // metadata
            'cue',
            'xml',
            // playlist
            'm3u8',
            // db
            'json',
            'sqlite',
            'sqlite3',
            'ini',

            // checksums
            'sfv',
            'checksum',

            // audiotracks
            'mka',
        ];
        $blacklistedTypes = [
            'gif',
            'png',
            'jpg',
            'jpeg',
            'nfo',
            'gif',
            'mobileconfig',
            'xci',
            'html',
            'log',
            'svg',
            'txt',
            'url',
            'exe',
            'db',
            'metathumb',
            'iso',
            'lnk',
            'md',
            'About_album',
            'pls',
            'accurip',
            'mid',
            'auCDtect',
            'opus',
            'hosts',
            'last_time',
            'sbk',
            'yml',
            'db-shm',
            'db-wal',
            'zip',
            'yaml',
            'pem',
            'txz',
            '1',
            'mbp',
            'env',
            'config',
            'key',
            'js',
            'INFO',
            'css',
            'psd',
            'pspimage',
            'sqlite3-shm',
            'sqlite3-wal',
            'gz',
            'phar',
            'LICENSE',
            'lock',
            'dist',
            'web',
            'php',
            'composer',
            'console',
            'sh',
            'jsonlint',
            'php-cs-fixer',
            'php-parse',
            'phpstan',
            'phpunit',
            'satis',
            'validate-json',
            'var-dump-server',
            'Dockerfile',
            'conf',
            'ico',
            'map',
            'eot',
            'ttf',
            'woff',
            'woff2',
            'twig',
            'meta',
            'PORTING_INFO',
            'compile',
            'bat',
            'stub',
            'scss',
            'rst',
            'diff',
            'phpt',
            'template',
            'y',
            'asc',
            'neon',
            'xsd',
            'tpl',
            'base64',
            'xlf',
            'CHANGELOG',
            'secret',
            'public',
            'planet',
            'port',
            'peer',
        ];

        $this->recursivelyFindFiles('/media/Shows', $supportedTypes, $blacklistedTypes, function (\SplFileInfo $file, $err, $out) {
        });
    }

    protected function recursivelyFindFiles(string $directory, array &$supportedTypes, array &$blacklistedTypes, Closure $callback)
    {
        $this->warn('     Found: '.$directory);
        $files = (new Filesystem)->files($directory);

        // Build a list of supported file types.
        // Interactively when
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            $name = $file->getBasename();
            $split = explode('.', $name);
            $extension = end($split);
            $path = $file->getPathname();

            if (in_array($extension, $blacklistedTypes)) {
                continue;
            }

            dispatch(new BuildMetaDataFile($path));
        }

        $directories = (new Filesystem)->directories($directory);

        foreach ($directories as $directory) {
            $this->recursivelyFindFiles($directory, $supportedTypes, $blacklistedTypes, $callback);
        }
    }
}
