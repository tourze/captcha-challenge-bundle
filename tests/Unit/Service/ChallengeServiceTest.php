<?php

namespace Tourze\CaptchaChallengeBundle\Tests\Unit\Service;

use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tourze\CaptchaChallengeBundle\Service\ChallengeService;

class ChallengeServiceTest extends TestCase
{
    private ChallengeService $challengeService;
    private ArrayAdapter $cache;
    private UrlGeneratorInterface|MockObject $urlGenerator;
    private Encryptor|MockObject $encryptor;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
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
        $challengeKey = $this->challengeService->generateChallenge();

        $this->assertNotEmpty($challengeKey);
        // UUID v4 格式验证
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $challengeKey);
        
        // 验证缓存中存储了正确的值
        $cacheItem = $this->cache->getItem("challenge-{$challengeKey}");
        $this->assertTrue($cacheItem->isHit());
        $this->assertMatchesRegularExpression('/^\d{5}$/', (string) $cacheItem->get());
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

        // 手动设置缓存值
        $cacheItem = $this->cache->getItem($redisKey);
        $cacheItem->set($challengeVal);
        $this->cache->save($cacheItem);

        $result = $this->challengeService->getChallengeValFromChallengeKey($challengeKey);

        $this->assertEquals($challengeVal, $result);
    }

    public function testGetChallengeValFromChallengeKey_withNonExistingKey_returnsEmptyString(): void
    {
        $challengeKey = 'non-existing-key';

        $result = $this->challengeService->getChallengeValFromChallengeKey($challengeKey);

        $this->assertEquals('', $result);
    }

    public function testCheckAndConsume_withValidKeyAndValue_returnsTrue(): void
    {
        $challengeKey = 'test-challenge-key';
        $challengeVal = '12345';
        $redisKey = "challenge-{$challengeKey}";

        // 手动设置缓存值
        $cacheItem = $this->cache->getItem($redisKey);
        $cacheItem->set($challengeVal);
        $this->cache->save($cacheItem);

        $result = $this->challengeService->checkAndConsume($challengeKey, $challengeVal);

        $this->assertTrue($result);
        
        // 验证值已被删除
        $cacheItem = $this->cache->getItem($redisKey);
        $this->assertFalse($cacheItem->isHit());
    }

    public function testCheckAndConsume_withInvalidValue_returnsFalse(): void
    {
        // 先生成一个验证码
        $challengeKey = $this->challengeService->generateChallenge();
        $challengeVal = $this->challengeService->getChallengeValFromChallengeKey($challengeKey);
        
        // 使用错误的值进行验证
        $invalidValue = $challengeVal === '12345' ? '54321' : '12345';
        $result = $this->challengeService->checkAndConsume($challengeKey, $invalidValue);

        $this->assertFalse($result);
        
        // 验证验证码还存在（因为验证失败了）
        $redisKey = "challenge-{$challengeKey}";
        $cacheItem = $this->cache->getItem($redisKey);
        $this->assertTrue($cacheItem->isHit());
        $this->assertEquals($challengeVal, $cacheItem->get());
    }

    public function testCheckAndConsume_withEmptyKey_returnsFalse(): void
    {
        $result = $this->challengeService->checkAndConsume('', '12345');
        $this->assertFalse($result);
    }

    public function testCheckAndConsume_withEmptyValue_returnsFalse(): void
    {
        $challengeKey = 'test-challenge-key';
        $result = $this->challengeService->checkAndConsume($challengeKey, '');
        $this->assertFalse($result);
    }
}
