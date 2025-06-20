<?php

namespace Tourze\CaptchaChallengeBundle\Tests\Unit\Procedure;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tourze\CaptchaChallengeBundle\Procedure\SendChallengeRequest;
use Tourze\CaptchaChallengeBundle\Service\ChallengeService;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Model\JsonRpcParams;

class SendChallengeRequestTest extends TestCase
{
    private SendChallengeRequest $procedure;
    private ChallengeService|MockObject $challengeService;
    private RequestStack|MockObject $requestStack;
    private string $clientIp = '127.0.0.1';

    protected function setUp(): void
    {
        $this->challengeService = $this->createMock(ChallengeService::class);

        $request = new Request();
        $request->server->set('REMOTE_ADDR', $this->clientIp);

        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack->method('getMainRequest')
            ->willReturn($request);

        $this->procedure = new SendChallengeRequest(
            $this->requestStack,
            $this->challengeService
        );
    }

    public function testExecute_whenChallengeTypeIsEnabled_returnsChallengeKeyAndImage(): void
    {
        $_ENV['LOGIN_CHALLENGE_TYPE'] = 'captcha';

        $challengeKey = 'test-challenge-key';
        $challengeImageUrl = 'https://example.com/captcha?key=encrypted-key';

        $this->challengeService->expects($this->once())
            ->method('generateChallenge')
            ->willReturn($challengeKey);

        $this->challengeService->expects($this->once())
            ->method('generateChallengeCaptchaImageUrl')
            ->with($this->equalTo($challengeKey))
            ->willReturn($challengeImageUrl);

        $result = $this->procedure->execute();

        $this->assertArrayHasKey('challengeKey', $result);
        $this->assertArrayHasKey('challengeImage', $result);
        $this->assertEquals($challengeKey, $result['challengeKey']);
        $this->assertEquals($challengeImageUrl, $result['challengeImage']);
    }

    public function testExecute_whenChallengeTypeIsNull_throwsApiException(): void
    {
        $_ENV['LOGIN_CHALLENGE_TYPE'] = 'null';

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('接口未启用');

        $this->procedure->execute();
    }

    public function testGetLockResource_returnsArrayWithClientIp(): void
    {
        $params = new JsonRpcParams([]);

        // 使用反射调用受保护的方法
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('getLockResource');
        $method->setAccessible(true);

        $result = $method->invoke($this->procedure, $params);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('SendChallengeRequest_' . $this->clientIp, $result[0]);
    }

    public function testExecute_whenGenerateChallengeThrowsException_propagatesException(): void
    {
        $_ENV['LOGIN_CHALLENGE_TYPE'] = 'captcha';

        $this->challengeService->expects($this->once())
            ->method('generateChallenge')
            ->willThrowException(new \Exception('Failed to generate challenge'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to generate challenge');

        $this->procedure->execute();
    }

    public function testExecute_whenGenerateImageUrlThrowsException_propagatesException(): void
    {
        $_ENV['LOGIN_CHALLENGE_TYPE'] = 'captcha';

        $challengeKey = 'test-challenge-key';

        $this->challengeService->expects($this->once())
            ->method('generateChallenge')
            ->willReturn($challengeKey);

        $this->challengeService->expects($this->once())
            ->method('generateChallengeCaptchaImageUrl')
            ->willThrowException(new \Exception('Failed to generate image URL'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to generate image URL');

        $this->procedure->execute();
    }

    public function testExecute_whenNoMainRequest_stillWorks(): void
    {
        $_ENV['LOGIN_CHALLENGE_TYPE'] = 'captcha';

        // 模拟没有主请求的情况
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getMainRequest')
            ->willReturn(null);

        $procedure = new SendChallengeRequest(
            $requestStack,
            $this->challengeService
        );

        $challengeKey = 'test-challenge-key';
        $challengeImageUrl = 'https://example.com/captcha?key=encrypted-key';

        $this->challengeService->expects($this->once())
            ->method('generateChallenge')
            ->willReturn($challengeKey);

        $this->challengeService->expects($this->once())
            ->method('generateChallengeCaptchaImageUrl')
            ->with($this->equalTo($challengeKey))
            ->willReturn($challengeImageUrl);

        $result = $procedure->execute();

        $this->assertEquals($challengeKey, $result['challengeKey']);
        $this->assertEquals($challengeImageUrl, $result['challengeImage']);
    }

    public function testExecute_withEmptyChallengeType_throwsApiException(): void
    {
        $_ENV['LOGIN_CHALLENGE_TYPE'] = 'null';

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('接口未启用');

        $this->procedure->execute();
    }

    public function testExecute_withUndefinedChallengeType_throwsApiException(): void
    {
        // 确保环境变量未设置
        unset($_ENV['LOGIN_CHALLENGE_TYPE']);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('接口未启用');

        $this->procedure->execute();
    }

    protected function tearDown(): void
    {
        // 清理环境变量
        if (isset($_ENV['LOGIN_CHALLENGE_TYPE'])) {
            $_ENV['LOGIN_CHALLENGE_TYPE'] = null;
        }

        parent::tearDown();
    }
}
