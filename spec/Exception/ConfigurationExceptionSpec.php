<?php

declare(strict_types=1);

namespace spec\FriendsOfPhpSpec\PhpSpec\CodeCoverage\Exception;

use FriendsOfPhpSpec\PhpSpec\CodeCoverage\Exception\ConfigurationException;
use InvalidArgumentException;
use PhpSpec\ObjectBehavior;

/**
 * @author Ignace Nyamagana Butera
 */
class ConfigurationExceptionSpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(InvalidArgumentException::class);
        $this->shouldHaveType(ConfigurationException::class);
    }
}
