<?php

declare(strict_types=1);

namespace Tourze\CaptchaChallengeBundle\Param;

use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

/**
 * SendChallengeRequest Procedure 的参数对象
 *
 * 用于发送挑战验证请求的参数（无输入参数）
 */
final readonly class SendChallengeRequestParam implements RpcParamInterface
{
    public function __construct()
    {
    }
}
