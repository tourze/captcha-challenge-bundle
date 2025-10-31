<?php

declare(strict_types=1);

namespace Tourze\CaptchaChallengeBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tourze\CaptchaChallengeBundle\Procedure\SendChallengeRequest;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Model\JsonRpcParams;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(SendChallengeRequest::class)]
#[RunTestsInSeparateProcesses]
final class SendChallengeRequestTest extends AbstractProcedureTestCase
{
    protected function onSetUp(): void
    {
        // 不需要特殊的设置，使用真实的服务进行集成测试
    }

    public function testProcedureClassName(): void
    {
        // 测试类名是否符合预期
        $this->assertEquals('Tourze_CaptchaChallengeBundle_Procedure_SendChallengeRequest', SendChallengeRequest::getProcedureName());
    }

    public function testExecuteThrowsExceptionWhenChallengeTypeNotSet(): void
    {
        // 确保环境变量未设置
        unset($_ENV['LOGIN_CHALLENGE_TYPE']);

        // 从容器获取真实的服务实例
        $procedure = self::getService(SendChallengeRequest::class);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('接口未启用');

        $procedure->execute();
    }

    public function testExecuteThrowsExceptionWhenChallengeTypeIsNull(): void
    {
        // 设置环境变量为 'null'
        $_ENV['LOGIN_CHALLENGE_TYPE'] = 'null';

        // 从容器获取真实的服务实例
        $procedure = self::getService(SendChallengeRequest::class);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('接口未启用');

        $procedure->execute();

        // 清理环境变量
        unset($_ENV['LOGIN_CHALLENGE_TYPE']);
    }

    public function testExecuteReturnsValidResponseWhenChallengeTypeIsEnabled(): void
    {
        // 设置环境变量为启用状态
        $_ENV['LOGIN_CHALLENGE_TYPE'] = 'captcha';

        // 从容器获取真实的服务实例
        $procedure = self::getService(SendChallengeRequest::class);

        $result = $procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('challengeKey', $result);
        $this->assertArrayHasKey('challengeImage', $result);
        $this->assertIsString($result['challengeKey']);
        $this->assertIsString($result['challengeImage']);
        $this->assertNotEmpty($result['challengeKey']);
        $this->assertNotEmpty($result['challengeImage']);

        // 清理环境变量
        unset($_ENV['LOGIN_CHALLENGE_TYPE']);
    }

    public function testGetLockResourceReturnsCorrectResource(): void
    {
        // 从容器获取真实的服务实例
        $procedure = self::getService(SendChallengeRequest::class);

        // 创建一个带有特定IP的请求堆栈
        $requestStack = self::getService(RequestStack::class);

        // 清理现有的请求（如果有的话）
        while (null !== $requestStack->getMainRequest()) {
            $requestStack->pop();
        }

        // 创建一个测试请求
        $testRequest = new Request();
        $testRequest->server->set('REMOTE_ADDR', '192.168.1.1');
        $requestStack->push($testRequest);

        // 创建匿名JsonRpcParams类
        $params = new class extends JsonRpcParams {
            public function __construct()
            {
                parent::__construct();
            }
        };

        $result = $procedure->getLockResource($params);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertStringStartsWith('SendChallengeRequest_', $result[0]);
    }

    public function testGetLockResourceHandlesNullRequest(): void
    {
        // 从容器获取真实的服务实例
        $procedure = self::getService(SendChallengeRequest::class);

        // 创建一个空的请求堆栈
        $requestStack = self::getService(RequestStack::class);

        // 清理现有的请求（如果有的话）
        while (null !== $requestStack->getMainRequest()) {
            $requestStack->pop();
        }

        // 创建匿名JsonRpcParams类
        $params = new class extends JsonRpcParams {
            public function __construct()
            {
                parent::__construct();
            }
        };

        $result = $procedure->getLockResource($params);

        $this->assertIsArray($result);
        $this->assertEquals(['SendChallengeRequest_'], $result);
    }
}
