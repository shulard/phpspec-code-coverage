<?php

declare(strict_types=1);

namespace spec\FriendsOfPhpSpec\PhpSpec\CodeCoverage\Listener;

use FriendsOfPhpSpec\PhpSpec\CodeCoverage\Listener\CodeCoverageListener;
use PhpSpec\Console\ConsoleIO;
use PhpSpec\ObjectBehavior;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;

/**
 * Disabled due to tests breaking as php-code-coverage marked their classes
 * final and we cannot mock them. The tests should be converted into proper
 * functional (integration) tests instead. This file is left for reference.
 *
 * @see https://github.com/leanphp/phpspec-code-coverage/issues/19
 *
 * @author Henrik Bjornskov
 */
class CodeCoverageListenerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(CodeCoverageListener::class);
    }

    public function let(ConsoleIO $io)
    {
        $codeCoverage = new CodeCoverage(new DriverStub(), new Filter());

        $this->beConstructedWith($io, $codeCoverage, []);
    }
}

class DriverStub extends Driver
{
    public function nameAndVersion(): string
    {
        return 'DriverStub';
    }

    public function start(bool $determineUnusedAndDead = true): void
    {
    }

    public function stop(): RawCodeCoverageData
    {
        return RawCodeCoverageData::fromXdebugWithoutPathCoverage([]);
    }
}
