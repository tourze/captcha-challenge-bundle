<?php

declare(strict_types=1);

namespace Tourze\CaptchaChallengeBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\CaptchaChallengeBundle\Service\ChallengeService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ChallengeService::class)]
#[RunTestsInSeparateProcesses]
final class ChallengeServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外设置
    }

    public function testGenerateChallengeReturnsValidKey(): void
    {
        $challengeService = self::getService(ChallengeService::class);
        $challengeKey = $challengeService->generateChallenge();

        $this->assertNotEmpty($challengeKey);
        $this->assertIsString($challengeKey);
    }

    public function testCheckAndConsumeWithEmptyDataReturnsFalse(): void
    {
        $challengeService = self::getService(ChallengeService::class);
        $result = $challengeService->checkAndConsume('', '');

        $this->assertFalse($result);
    }

    public function testGetChallengeKeyFromEncryptKeyWithInvalidKeyReturnsEmpty(): void
    {
        $challengeService = self::getService(ChallengeService::class);
        // 使用无效格式的密钥，确保解密失败返回空字符串
        $result = $challengeService->getChallengeKeyFromEncryptKey('not-a-valid-encrypted-key');

        $this->assertEmpty($result);
    }

    public function testGetChallengeKeyFromEncryptKeyWithEmptyKeyReturnsEmpty(): void
    {
        $challengeService = self::getService(ChallengeService::class);
        $result = $challengeService->getChallengeKeyFromEncryptKey('');

        $this->assertEmpty($result);
    }

    public function testGenerateChallengeCaptchaImageUrl(): void
    {
        $challengeKey = 'test-challenge-key';
        $challengeService = self::getService(ChallengeService::class);
        $result = $challengeService->generateChallengeCaptchaImageUrl($challengeKey);

        $this->assertNotEmpty($result);
        $this->assertIsString($result);
        $this->assertStringContainsString('challenge/captcha-image', $result);
        $this->assertStringContainsString('key=', $result);
    }
}
