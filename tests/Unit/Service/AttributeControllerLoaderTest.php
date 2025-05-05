<?php

namespace Tourze\CaptchaChallengeBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouteCollection;
use Tourze\CaptchaChallengeBundle\Service\AttributeControllerLoader;

class AttributeControllerLoaderTest extends TestCase
{
    private AttributeControllerLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new AttributeControllerLoader();
    }

    public function testAutoload_returnsRouteCollection(): void
    {
        $routes = $this->loader->autoload();

        $this->assertInstanceOf(RouteCollection::class, $routes);
        $this->assertGreaterThan(0, count($routes));
        $this->assertNotNull($routes->get('app_challenge_captcha_image'));
    }

    public function testSupports_alwaysReturnsFalse(): void
    {
        $result = $this->loader->supports('any_resource');
        $this->assertFalse($result);

        $result = $this->loader->supports('any_resource', 'type');
        $this->assertFalse($result);
    }

    public function testLoad_callsAutoload(): void
    {
        $routes = $this->loader->load('any_resource');

        $this->assertInstanceOf(RouteCollection::class, $routes);
        $this->assertGreaterThan(0, count($routes));
        $this->assertNotNull($routes->get('app_challenge_captcha_image'));
    }
}
