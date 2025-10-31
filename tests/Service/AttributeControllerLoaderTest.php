<?php

declare(strict_types=1);

namespace Tourze\CaptchaChallengeBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Routing\RouteCollection;
use Tourze\CaptchaChallengeBundle\Service\AttributeControllerLoader;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外设置
    }

    public function testAutoloadCreatesRouteCollection(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $routes = $loader->autoload();

        $this->assertInstanceOf(RouteCollection::class, $routes);
        $this->assertGreaterThan(0, $routes->count());
    }

    public function testLoadCallsAutoload(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $routes = $loader->load('resource');

        $this->assertInstanceOf(RouteCollection::class, $routes);
        $this->assertGreaterThan(0, $routes->count());
    }

    public function testSupportsReturnsFalse(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);

        $this->assertFalse($loader->supports('resource'));
    }
}
