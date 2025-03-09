<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\CompileNewInstanceOfClass;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Nette\PhpGenerator\Parameter;
use PHPUnit\Framework\TestCase;

class CompileNewInstanceOfClassTest extends TestCase
{
    public function testHandle(): void
    {
        $command = new CompileNewInstanceOfClass();

        $this->assertInstanceOf(Command::class, $command);

        $sourceClass = 'App\\SourceClass';
        $destinationClass = 'App\\DestinationClass';

        $command->setLaravel(app());

        $command->expects($this->once())
            ->method('argument')
            ->withConsecutive(['sourceClass'], ['destination'])
            ->willReturnOnConsecutiveCalls($sourceClass, $destinationClass);

        $command->expects($this->once())
            ->method('option')
            ->with('blank-methods')
            ->willReturn(true);

        $command->handle();

        $newFileName = str_replace('\\', '/', lcfirst($destinationClass)).'.php';

        $this->assertFileExists(base_path($newFileName));
    }
}
