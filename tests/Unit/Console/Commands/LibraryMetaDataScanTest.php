<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\LibraryMetaDataScan;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Mockery;
use PHPUnit\Framework\TestCase;

class LibraryMetaDataScanTest extends TestCase
{
    public function testHandle()
    {
        $filesystemMock = Mockery::mock(Filesystem::class);
        $commandMock = Mockery::mock(LibraryMetaDataScan::class)->makePartial();

        $commandMock->shouldReceive('recursivelyFindFiles')
            ->once()
            ->with('/media/Shows', Mockery::type('array'), Mockery::type('array'), Mockery::type('Closure'));

        $commandMock->handle();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
