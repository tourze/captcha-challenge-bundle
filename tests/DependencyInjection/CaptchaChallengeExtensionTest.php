<?php

declare(strict_types=1);

namespace Tourze\CaptchaChallengeBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\CaptchaChallengeBundle\DependencyInjection\CaptchaChallengeExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(CaptchaChallengeExtension::class)]
final class CaptchaChallengeExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    /**
     * 测试扩展别名
     */
    public function testGetAlias(): void
    {
        $extension = new CaptchaChallengeExtension();
        $this->assertEquals('captcha_challenge', $extension->getAlias());
    }

    /**
     * 测试在测试环境中的prepend配置
     */
    public function testPrependInTestEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension = new CaptchaChallengeExtension();
        $extension->prepend($container);

        // 验证在测试环境中会添加加密器配置
        $config = $container->getExtensionConfig('nzo_encryptor');
        $this->assertNotEmpty($config);
        $this->assertEquals('test-secret-key-for-captcha-challenge-bundle-12345', $config[0]['secret_key']);
    }

    /**
     * 测试在生产环境中的prepend配置
     */
    public function testPrependInProdEnvironment(): void
    {
        // 创建一个模拟的容器
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new CaptchaChallengeExtension();
        $extension->prepend($container);

        // 验证在生产环境中不会添加测试配置
        $this->assertFalse($container->hasDefinition('nzo_encryptor'));
    }

    /**
     * 测试没有kernel环境时的prepend配置
     */
    public function testPrependWithoutKernelEnvironment(): void
    {
        // 创建一个没有kernel环境参数的容器
        $container = new ContainerBuilder();

        $extension = new CaptchaChallengeExtension();
        $extension->prepend($container);

        // 验证在没有环境参数时不会添加加密器服务
        $this->assertFalse($container->hasDefinition('nzo_encryptor'));
    }
}
