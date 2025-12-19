<?php

declare(strict_types=1);

namespace Tourze\CaptchaChallengeBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\CaptchaChallengeBundle\Param\SendChallengeRequestParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

/**
 * SendChallengeRequestParam 测试
 *
 * 测试发送挑战验证请求的参数对象
 * @internal
 */
#[CoversClass(SendChallengeRequestParam::class)]
final class SendChallengeRequestParamTest extends TestCase
{
    public function testParameterCanBeInstantiated(): void
    {
        $param = new SendChallengeRequestParam();

        self::assertInstanceOf(SendChallengeRequestParam::class, $param);
    }

    public function testParameterImplementsRpcParamInterface(): void
    {
        $param = new SendChallengeRequestParam();

        self::assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testParameterIsReadonly(): void
    {
        $param = new SendChallengeRequestParam();

        // readonly 对象检查 - 确保类是readonly的
        $reflection = new \ReflectionClass($param);
        self::assertTrue($reflection->isReadOnly());
    }
}
