<?php

declare(strict_types=1);

namespace Tourze\CaptchaChallengeBundle;

use Nzo\UrlEncryptorBundle\NzoUrlEncryptorBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\JsonRPCLockBundle\JsonRPCLockBundle;
use Tourze\RoutingAutoLoaderBundle\RoutingAutoLoaderBundle;

class CaptchaChallengeBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            NzoUrlEncryptorBundle::class => ['all' => true],
            JsonRPCLockBundle::class => ['all' => true],
            RoutingAutoLoaderBundle::class => ['all' => true],
        ];
    }
}
