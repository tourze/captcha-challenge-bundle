<?php

namespace Tourze\CaptchaChallengeBundle\Procedure;

use Symfony\Component\HttpFoundation\RequestStack;
use Tourze\CaptchaChallengeBundle\Service\ChallengeService;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Model\JsonRpcParams;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;

#[MethodTag(name: '用户模块')]
#[MethodExpose(method: 'SendChallengeRequest')]
#[MethodDoc(summary: '发送挑战验证请求')]
#[Log]
class SendChallengeRequest extends LockableProcedure
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ChallengeService $challengeService,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function execute(): array
    {
        // 如果没开启登录验证，那么不需要这个
        if (!isset($_ENV['LOGIN_CHALLENGE_TYPE']) || 'null' === $_ENV['LOGIN_CHALLENGE_TYPE']) {
            throw new ApiException('接口未启用');
        }

        $challengeKey = $this->challengeService->generateChallenge();

        return [
            'challengeKey' => $challengeKey,
            'challengeImage' => $this->challengeService->generateChallengeCaptchaImageUrl($challengeKey),
        ];
    }

    /**
     * @return array<string>|null
     */
    public function getLockResource(JsonRpcParams $params): ?array
    {
        return [
            'SendChallengeRequest_' . $this->requestStack->getMainRequest()?->getClientIp(),
        ];
    }
}
