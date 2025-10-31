<?php

declare(strict_types=1);

namespace Tourze\CaptchaChallengeBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class CaptchaChallengeExtension extends AutoExtension implements PrependExtensionInterface
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }

    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasParameter('kernel.environment')) {
            $env = $container->getParameter('kernel.environment');
            if ('test' === $env) {
                $container->prependExtensionConfig('nzo_encryptor', [
                    'secret_key' => 'test-secret-key-for-captcha-challenge-bundle-12345',
                ]);
            }
        }
    }
}
