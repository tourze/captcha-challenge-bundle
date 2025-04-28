<?php

namespace Tourze\CaptchaChallengeBundle\Service;

use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

class ChallengeService
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Encryptor $encryptor,
    ) {
    }

    public function generateChallenge(): string
    {
        $challengeKey = Uuid::v4()->toRfc4122();
        $challengeVal = (string) rand(10000, 99999);
        $this->cache->set($this->getRedisKey($challengeKey), $challengeVal, 60 * 5); // 只保存5分钟

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
        $challengeKey = $this->encryptor->decrypt($key);

        return trim($challengeKey);
    }

    public function checkAndConsume(string $challengeKey, string $challengeVal): bool
    {
        if (empty($challengeKey) || empty($challengeVal)) {
            return false;
        }
        $dbVal = $this->getChallengeValFromChallengeKey($challengeKey);

        $res = $dbVal === $challengeVal;
        if ($res) {
            $this->cache->delete($this->getRedisKey($challengeKey));
        }

        return $res;
    }

    public function getChallengeValFromChallengeKey(string $challengeKey): string
    {
        return (string) $this->cache->get($this->getRedisKey($challengeKey));
    }

    private function getRedisKey(string $challengeKey): string
    {
        return "challenge-{$challengeKey}";
    }
}
