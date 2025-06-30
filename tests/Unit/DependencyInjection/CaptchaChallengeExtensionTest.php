<?php

declare(strict_types=1);

namespace Tourze\CaptchaChallengeBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\CaptchaChallengeBundle\DependencyInjection\CaptchaChallengeExtension;
use Tourze\CaptchaChallengeBundle\Service\ChallengeService;
use Tourze\CaptchaChallengeBundle\Controller\ChallengeController;
use Tourze\CaptchaChallengeBundle\Service\AttributeControllerLoader;

class CaptchaChallengeExtensionTest extends TestCase
{
    private CaptchaChallengeExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new CaptchaChallengeExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoad(): void
    {
        $this->extension->load([], $this->container);

        // 验证服务是否正确注册
        $this->assertTrue($this->container->hasDefinition(ChallengeService::class));
        $this->assertTrue($this->container->hasDefinition(ChallengeController::class));
        $this->assertTrue($this->container->hasDefinition(AttributeControllerLoader::class));

        // 验证控制器被正确配置（控制器应该被自动配置）
        $controllerDefinition = $this->container->getDefinition(ChallengeController::class);
        $this->assertTrue($controllerDefinition->isAutoconfigured());
        
        // 验证服务被正确配置（服务应该被自动装配）
        $challengeServiceDefinition = $this->container->getDefinition(ChallengeService::class);
        $this->assertTrue($challengeServiceDefinition->isAutowired());
    }

    public function testGetAlias(): void
    {
        $this->assertEquals('captcha_challenge', $this->extension->getAlias());
    }
}