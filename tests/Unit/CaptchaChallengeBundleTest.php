<?php

namespace Tourze\CaptchaChallengeBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tourze\CaptchaChallengeBundle\CaptchaChallengeBundle;

class CaptchaChallengeBundleTest extends TestCase
{
    public function testBundleInstantiation(): void
    {
        $bundle = new CaptchaChallengeBundle();
        
        $this->assertInstanceOf(CaptchaChallengeBundle::class, $bundle);
    }
}