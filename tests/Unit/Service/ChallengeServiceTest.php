<?php

namespace Tourze\CaptchaChallengeBundle\Tests\Unit\Service;

use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tourze\CaptchaChallengeBundle\Service\ChallengeService;

class ChallengeServiceTest extends TestCase
{
    private ChallengeService $challengeService;
    private CacheInterface|MockObject $cache;
    private UrlGeneratorInterface|MockObject $urlGenerator;
    private Encryptor|MockObject $encryptor;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->encryptor = $this->createMock(Encryptor::class);

        $this->challengeService = new ChallengeService(
            $this->cache,
            $this->urlGenerator,
            $this->encryptor
        );
    }

    public function testGenerateChallenge_returnsValidChallengeKey(): void
    {
        $this->cache->expects($this->once())
            ->method('set')
            ->with(
                $this->matchesRegularExpression('/^challenge-[\w\-]+$/'),
                $this->matchesRegularExpression('/^\d{5}$/'),
                $this->equalTo(60 * 5)
            );

        $challengeKey = $this->challengeService->generateChallenge();

        $this->assertNotEmpty($challengeKey);
        $this->assertIsString($challengeKey);
    }

    public function testGenerateChallengeCaptchaImageUrl_returnsValidUrl(): void
    {
        $challengeKey = 'test-challenge-key';
        $encryptedKey = 'encrypted-key';
        $expectedUrl = 'https://example.com/captcha?key=encrypted-key';

        $this->encryptor->expects($this->once())
            ->method('encrypt')
            ->with($this->equalTo($challengeKey))
            ->willReturn($encryptedKey);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with(
                $this->equalTo('app_challenge_captcha_image'),
                $this->equalTo(['key' => $encryptedKey]),
                $this->equalTo(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->willReturn($expectedUrl);

        $url = $this->challengeService->generateChallengeCaptchaImageUrl($challengeKey);

        $this->assertEquals($expectedUrl, $url);
    }

    public function testGetChallengeKeyFromEncryptKey_withValidKey_returnsDecryptedKey(): void
    {
        $encryptedKey = 'encrypted-key';
        $decryptedKey = 'test-challenge-key';

        $this->encryptor->expects($this->once())
            ->method('decrypt')
            ->with($this->equalTo($encryptedKey))
            ->willReturn($decryptedKey);

        $result = $this->challengeService->getChallengeKeyFromEncryptKey($encryptedKey);

        $this->assertEquals($decryptedKey, $result);
    }

    public function testGetChallengeValFromChallengeKey_withExistingKey_returnsValue(): void
    {
        $challengeKey = 'test-challenge-key';
        $challengeVal = '12345';
        $redisKey = "challenge-{$challengeKey}";

        $this->cache->expects($this->once())
            ->method('get')
            ->with($this->equalTo($redisKey))
            ->willReturn($challengeVal);

        $result = $this->challengeService->getChallengeValFromChallengeKey($challengeKey);

        $this->assertEquals($challengeVal, $result);
    }

    public function testGetChallengeValFromChallengeKey_withNonExistingKey_returnsEmptyString(): void
    {
        $challengeKey = 'non-existing-key';
        $redisKey = "challenge-{$challengeKey}";

        $this->cache->expects($this->once())
            ->method('get')
            ->with($this->equalTo($redisKey))
            ->willReturn(null);

        $result = $this->challengeService->getChallengeValFromChallengeKey($challengeKey);

        $this->assertEquals('', $result);
    }

    public function testCheckAndConsume_withValidKeyAndValue_returnsTrue(): void
    {
        $challengeKey = 'test-challenge-key';
        $challengeVal = '12345';
        $redisKey = "challenge-{$challengeKey}";

        $this->cache->expects($this->once())
            ->method('get')
            ->with($this->equalTo($redisKey))
            ->willReturn($challengeVal);

        $this->cache->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($redisKey));

        $result = $this->challengeService->checkAndConsume($challengeKey, $challengeVal);

        $this->assertTrue($result);
    }

    public function testCheckAndConsume_withInvalidValue_returnsFalse(): void
    {
        $challengeKey = 'test-challenge-key';
        $challengeVal = '12345';
        $invalidValue = '54321';
        $redisKey = "challenge-{$challengeKey}";

        $this->cache->expects($this->once())
            ->method('get')
            ->with($this->equalTo($redisKey))
            ->willReturn($challengeVal);

        $this->cache->expects($this->never())
            ->method('delete');

        $result = $this->challengeService->checkAndConsume($challengeKey, $invalidValue);

        $this->assertFalse($result);
    }

    public function testCheckAndConsume_withEmptyKey_returnsFalse(): void
    {
        $this->cache->expects($this->never())
            ->method('get');

        $this->cache->expects($this->never())
            ->method('delete');

        $result = $this->challengeService->checkAndConsume('', '12345');

        $this->assertFalse($result);
    }

    public function testCheckAndConsume_withEmptyValue_returnsFalse(): void
    {
        $challengeKey = 'test-challenge-key';

        $this->cache->expects($this->never())
            ->method('get');

        $this->cache->expects($this->never())
            ->method('delete');

        $result = $this->challengeService->checkAndConsume($challengeKey, '');

        $this->assertFalse($result);
    }
}
