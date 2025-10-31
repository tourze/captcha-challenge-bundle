<?php

declare(strict_types=1);

namespace Tourze\CaptchaChallengeBundle\Service;

use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

#[Autoconfigure(public: true)]
readonly class ChallengeService
{
    public function __construct(
        #[Autowire(service: 'cache.app')] private AdapterInterface $cache,
        private UrlGeneratorInterface $urlGenerator,
        private Encryptor $encryptor,
    ) {
    }

    public function generateChallenge(): string
    {
        $challengeKey = Uuid::v4()->toRfc4122();
        $challengeVal = (string) rand(10000, 99999);

        $cacheItem = $this->cache->getItem($this->getRedisKey($challengeKey));
        $cacheItem->set($challengeVal);
        $cacheItem->expiresAfter(60 * 5); // 只保存5分钟
        $this->cache->save($cacheItem);

        return $challengeKey;
    }

    public function generateChallengeCaptchaImageUrl(string $challengeKey): string
    {
        return $this->urlGenerator->generate('app_challenge_captcha_image', [
            'key' => $this->encryptor->encrypt($challengeKey),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function getChallengeKeyFromEncryptKey(string $key): string
    {
        if ('' === $key) {
            return '';
        }

        try {
            $challengeKey = $this->encryptor->decrypt($key);

            return trim($challengeKey);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * 检查并消费验证码挑战值
     *
     * 不考虑并发：验证码系统为单次使用，重复验证失败是合理行为
     */
    public function checkAndConsume(string $challengeKey, string $challengeVal): bool
    {
        if ('' === $challengeKey || '' === $challengeVal) {
            return false;
        }
        $dbVal = $this->getChallengeValFromChallengeKey($challengeKey);

        $res = $dbVal === $challengeVal;
        if ($res) {
            $this->cache->deleteItem($this->getRedisKey($challengeKey));
        }

        return $res;
    }

    public function getChallengeValFromChallengeKey(string $challengeKey): string
    {
        $cacheItem = $this->cache->getItem($this->getRedisKey($challengeKey));

        if (!$cacheItem->isHit()) {
            return '';
        }

        $value = $cacheItem->get();

        return is_string($value) ? $value : '';
    }

    private function getRedisKey(string $challengeKey): string
    {
        // 清理缓存键，移除非法字符
        $safeKey = preg_replace('/[{}()\/\\\@:]/', '_', $challengeKey);

        return "challenge-{$safeKey}";
    }
}
