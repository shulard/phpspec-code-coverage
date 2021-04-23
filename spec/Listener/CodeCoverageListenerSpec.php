<?php

declare(strict_types=1);

namespace spec\FriendsOfPhpSpec\PhpSpec\CodeCoverage\Listener;

use FriendsOfPhpSpec\PhpSpec\CodeCoverage\Exception\ConfigurationException;
use FriendsOfPhpSpec\PhpSpec\CodeCoverage\Listener\CodeCoverageListener;
use PhpSpec\Console\ConsoleIO;
use PhpSpec\Event\SuiteEvent;
use PhpSpec\ObjectBehavior;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;
use stdClass;

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
    public function it_can_process_all_directory_filtering_options(SuiteEvent $event)
    {
        $this->setOptions([
            'blacklist' => [
                'src',
                ['directory' => 'src', 'suffix' => 'Spec.php', 'prefix' => 'Get'],
                ['directory' => 'src', 'suffix' => 'Test.php'],
                ['directory' => 'src'],
            ],
        ]);

        $this
            ->shouldNotThrow(ConfigurationException::class)
            ->during('beforeSuite', [$event]);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CodeCoverageListener::class);
    }

    public function it_will_ignore_unknown_directory_filtering_options(SuiteEvent $event)
    {
        $this->setOptions([
            'whitelist' => [
                ['directory' => 'test', 'foobar' => 'baz'],
            ],
        ]);

        $this
            ->shouldNotThrow(ConfigurationException::class)
            ->during('beforeSuite', [$event]);
    }

    public function it_will_throw_if_the_directory_filter_option_type_is_not_supported(SuiteEvent $event)
    {
        $this->setOptions([
            'whitelist' => [
                new stdClass(),
            ],
        ]);

        $this
            ->shouldThrow(ConfigurationException::class)
            ->during('beforeSuite', [$event]);
    }

    public function it_will_throw_if_the_directory_parameter_is_missing(SuiteEvent $event)
    {
        $this->setOptions([
            'whitelist' => [
                ['foobar' => 'baz', 'suffix' => 'Spec.php', 'prefix' => 'Get'],
            ],
        ]);

        $this
            ->shouldThrow(ConfigurationException::class)
            ->during('beforeSuite', [$event]);
    }

    public function let(ConsoleIO $io)
    {
        $codeCoverage = new CodeCoverage(new DriverStub(), new Filter());

        $this->beConstructedWith($io, $codeCoverage, null, []);
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
