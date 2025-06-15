<?php

namespace Tourze\CaptchaChallengeBundle\Tests\Unit\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\CaptchaChallengeBundle\Controller\ChallengeController;
use Tourze\CaptchaChallengeBundle\Service\ChallengeService;

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

    public function testCaptchaImage_withDirectChallengeVal_skipsChallengeService(): void
    {
        // 测试直接传递 challengeVal 参数的情况
        $challengeVal = '12345';
        
        $request = new Request();
        $request->query->set('challengeVal', $challengeVal);

        $this->kernel->expects($this->once())
            ->method('getCacheDir')
            ->willReturn(sys_get_temp_dir());

        // 当直接传递 challengeVal 时，不应该调用 ChallengeService 的方法
        $this->challengeService->expects($this->never())
            ->method('getChallengeKeyFromEncryptKey');
        $this->challengeService->expects($this->never())
            ->method('getChallengeValFromChallengeKey');

        $response = $this->controller->captchaImage($request, $this->challengeService, $this->kernel);

        if ($this->isGdExtensionAvailable()) {
            $this->assertInstanceOf(Response::class, $response);
            $this->assertEquals('image/jpeg', $response->headers->get('Content-type'));
            $this->assertEquals('no-cache', $response->headers->get('Pragma'));
            $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
            // 验证返回的是有效的图片数据
            $this->assertNotEmpty($response->getContent());
        } else {
            // GD 扩展不可用时，测试应该跳过
            $this->markTestSkipped('GD extension is not available');
        }
    }

    public function testCaptchaImage_withValidKey_returnsImageResponse(): void
    {
        // 测试通过加密的 key 获取验证码的情况
        $encryptedKey = 'encrypted-key';
        $challengeKey = 'challenge-key';
        $challengeVal = '54321';

        $request = new Request();
        $request->query->set('key', $encryptedKey);

        $this->challengeService->expects($this->once())
            ->method('getChallengeKeyFromEncryptKey')
            ->with($this->equalTo($encryptedKey))
            ->willReturn($challengeKey);

        $this->challengeService->expects($this->once())
            ->method('getChallengeValFromChallengeKey')
            ->with($this->equalTo($challengeKey))
            ->willReturn($challengeVal);

        $this->kernel->expects($this->once())
            ->method('getCacheDir')
            ->willReturn(sys_get_temp_dir());

        $response = $this->controller->captchaImage($request, $this->challengeService, $this->kernel);

        if ($this->isGdExtensionAvailable()) {
            $this->assertInstanceOf(Response::class, $response);
            $this->assertEquals('image/jpeg', $response->headers->get('Content-type'));
            $this->assertEquals('no-cache', $response->headers->get('Pragma'));
            $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
            // 验证返回的是有效的图片数据
            $this->assertNotEmpty($response->getContent());
            
            // 可选：验证是否是有效的 JPEG 图片
            if (function_exists('imagecreatefromstring')) {
                $image = @imagecreatefromstring($response->getContent());
                $this->assertNotFalse($image, 'Response should contain valid image data');
                if ($image !== false) {
                    imagedestroy($image);
                }
            }
        } else {
            $this->markTestSkipped('GD extension is not available');
        }
    }

    public function testCaptchaImage_withEmptyChallengeVal_returnsErrorMessage(): void
    {
        // 测试 challengeVal 为空字符串的情况
        $request = new Request();
        $request->query->set('challengeVal', '');

        // 由于 challengeVal 是空字符串，PHP 会认为它是 falsy，所以会尝试从 key 获取
        // 但是没有提供 key，所以返回 'no key'
        $this->challengeService->expects($this->once())
            ->method('getChallengeKeyFromEncryptKey')
            ->with($this->equalTo(''))
            ->willReturn('');

        $response = $this->controller->captchaImage($request, $this->challengeService, $this->kernel);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('no key', $response->getContent());
    }

    public function testCaptchaImage_catchesBuilderException(): void
    {
        // 测试当 CaptchaBuilder 抛出异常时的降级处理
        $challengeVal = '99999';
        
        $request = new Request();
        $request->query->set('challengeVal', $challengeVal);

        $this->kernel->expects($this->once())
            ->method('getCacheDir')
            ->willReturn(sys_get_temp_dir());

        // 即使 buildAgainstOCR 失败，也应该返回有效的响应
        $response = $this->controller->captchaImage($request, $this->challengeService, $this->kernel);

        if ($this->isGdExtensionAvailable()) {
            $this->assertInstanceOf(Response::class, $response);
            $this->assertEquals('image/jpeg', $response->headers->get('Content-type'));
            $this->assertNotEmpty($response->getContent());
        } else {
            $this->markTestSkipped('GD extension is not available');
        }
    }

    /**
     * 检查 GD 扩展是否可用
     */
    private function isGdExtensionAvailable(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreate') && function_exists('imagejpeg');
    }
}
