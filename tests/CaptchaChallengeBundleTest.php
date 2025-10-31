<?php

declare(strict_types=1);

namespace CaptchaChallengeBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\CaptchaChallengeBundle\CaptchaChallengeBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(CaptchaChallengeBundle::class)]
#[RunTestsInSeparateProcesses]
final class CaptchaChallengeBundleTest extends AbstractBundleTestCase
{
}
