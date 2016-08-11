<?php

namespace spec;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ImportCommandSpec extends ObjectBehavior
{
    function it_implements_symfony_console_command()
    {
        $this->shouldImplement(Command::class);
    }
}
