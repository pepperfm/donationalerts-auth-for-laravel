<?php

declare(strict_types=1);

namespace Pepperfm\DonationalertsAuth\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Pepperfm\DonationalertsAuth\Providers\DonationalertsAuthServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFaker();
    }

    /**
     * @inheritdoc
     */
    protected function getPackageProviders($app): array
    {
        return [
            DonationalertsAuthServiceProvider::class,
        ];
    }
}
