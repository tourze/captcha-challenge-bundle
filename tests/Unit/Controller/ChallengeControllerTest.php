<?php

namespace Tourze\CaptchaChallengeBundle\Tests\Unit\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\CaptchaChallengeBundle\Controller\ChallengeController;
use Tourze\CaptchaChallengeBundle\Service\ChallengeService;

/**
 * 由于环境中可能没有 GD 扩展和 imagettfbbox 函数，我们仅测试错误情况
 */
class ChallengeControllerTest extends TestCase
{
    private ChallengeController $controller;
    private ChallengeService|MockObject $challengeService;
    private KernelInterface|MockObject $kernel;

    protected function setUp(): void
    {
        $this->challengeService = $this->createMock(ChallengeService::class);
        $this->kernel = $this->createMock(KernelInterface::class);

        $this->controller = new ChallengeController();
    }

    public function testCaptchaImage_withNoKeyOrChallengeVal_returnsErrorMessage(): void
    {
        $request = new Request();
        $request->query->set('key', ''); // 使用空字符串而不是null

        $this->challengeService->expects($this->once())
            ->method('getChallengeKeyFromEncryptKey')
            ->with($this->equalTo(''))
            ->willReturn('');

        $response = $this->controller->captchaImage($request, $this->challengeService, $this->kernel);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('no key', $response->getContent());
    }

    public function testCaptchaImage_withInvalidKey_returnsErrorMessage(): void
    {
        $encryptedKey = 'invalid-key';

        $request = new Request();
        $request->query->set('key', $encryptedKey);

        $this->challengeService->expects($this->once())
            ->method('getChallengeKeyFromEncryptKey')
            ->with($this->equalTo($encryptedKey))
            ->willReturn('');

        $response = $this->controller->captchaImage($request, $this->challengeService, $this->kernel);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('no key', $response->getContent());
    }

    public function testCaptchaImage_withNoChallenge_returnsErrorMessage(): void
    {
        $encryptedKey = 'encrypted-key';
        $challengeKey = 'challenge-key';

        $request = new Request();
        $request->query->set('key', $encryptedKey);

        $this->challengeService->expects($this->once())
            ->method('getChallengeKeyFromEncryptKey')
            ->with($this->equalTo($encryptedKey))
            ->willReturn($challengeKey);

        $this->challengeService->expects($this->once())
            ->method('getChallengeValFromChallengeKey')
            ->with($this->equalTo($challengeKey))
            ->willReturn('');

        $response = $this->controller->captchaImage($request, $this->challengeService, $this->kernel);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('no challenge', $response->getContent());
    }
}
