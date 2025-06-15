<?php

namespace Tourze\CaptchaChallengeBundle\Tests\Integration;

use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tourze\CaptchaChallengeBundle\Controller\ChallengeController;
use Tourze\CaptchaChallengeBundle\Service\ChallengeService;

/**
 * 端到端的集成测试，测试完整的验证码生成和验证流程
 */
class CaptchaIntegrationTest extends TestCase
{
    private ChallengeService $challengeService;
    private ChallengeController $controller;
    private ArrayAdapter $cache;
    private Encryptor $encryptor;
    private UrlGeneratorInterface $urlGenerator;
    private KernelInterface $kernel;

    public function testFullCaptchaFlow_generateAndValidate(): void
    {
        // 步骤1：生成验证码
        $challengeKey = $this->challengeService->generateChallenge();
        $challengeVal = $this->challengeService->getChallengeValFromChallengeKey($challengeKey);

        $this->assertNotEmpty($challengeKey);
        $this->assertNotEmpty($challengeVal);
        $this->assertMatchesRegularExpression('/^\d{5}$/', $challengeVal);

        // 步骤2：获取验证码图片URL
        $imageUrl = $this->challengeService->generateChallengeCaptchaImageUrl($challengeKey);
        $this->assertStringContainsString('challenge/captcha-image?key=', $imageUrl);

        // 步骤3：从URL中提取加密的key
        $parsedUrl = parse_url($imageUrl);
        parse_str($parsedUrl['query'] ?? '', $queryParams);
        $encryptedKey = $queryParams['key'] ?? '';

        $this->assertNotEmpty($encryptedKey);

        // 步骤4：模拟通过控制器获取验证码图片
        $request = new Request();
        $request->query->set('key', $encryptedKey);

        $response = $this->controller->captchaImage($request, $this->challengeService, $this->kernel);

        if (extension_loaded('gd') && function_exists('imagejpeg')) {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals('image/jpeg', $response->headers->get('Content-type'));
            $this->assertNotEmpty($response->getContent());
        }

        // 步骤5：验证正确的验证码
        $isValid = $this->challengeService->checkAndConsume($challengeKey, $challengeVal);
        $this->assertTrue($isValid);

        // 步骤6：尝试重复使用验证码（应该失败）
        $isValidAgain = $this->challengeService->checkAndConsume($challengeKey, $challengeVal);
        $this->assertFalse($isValidAgain);
    }

    public function testFullCaptchaFlow_invalidCode(): void
    {
        // 生成验证码
        $challengeKey = $this->challengeService->generateChallenge();
        $challengeVal = $this->challengeService->getChallengeValFromChallengeKey($challengeKey);

        // 验证错误的验证码
        $wrongVal = $challengeVal === '12345' ? '54321' : '12345';
        $isValid = $this->challengeService->checkAndConsume($challengeKey, $wrongVal);
        $this->assertFalse($isValid);

        // 验证码应该仍然存在，可以重试
        $isValidRetry = $this->challengeService->checkAndConsume($challengeKey, $challengeVal);
        $this->assertTrue($isValidRetry);
    }

    public function testDirectChallengeValFlow(): void
    {
        // 直接使用 challengeVal 参数获取验证码图片
        $challengeVal = '88888';

        $request = new Request();
        $request->query->set('challengeVal', $challengeVal);

        $response = $this->controller->captchaImage($request, $this->challengeService, $this->kernel);

        if (extension_loaded('gd') && function_exists('imagejpeg')) {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals('image/jpeg', $response->headers->get('Content-type'));
            $this->assertNotEmpty($response->getContent());
        }
    }

    public function testCaptchaExpiration(): void
    {
        // 注意：由于我们使用的是 ArrayAdapter，无法真正测试 TTL
        // 在实际环境中，应该使用支持 TTL 的缓存适配器

        $challengeKey = $this->challengeService->generateChallenge();
        $challengeVal = $this->challengeService->getChallengeValFromChallengeKey($challengeKey);

        // 立即验证应该成功
        $isValid = $this->challengeService->checkAndConsume($challengeKey, $challengeVal);
        $this->assertTrue($isValid);

        // 生成新的验证码，测试多个验证码可以共存
        $challengeKey2 = $this->challengeService->generateChallenge();
        $challengeVal2 = $this->challengeService->getChallengeValFromChallengeKey($challengeKey2);
        $this->assertNotEquals($challengeKey, $challengeKey2);

        $isValid2 = $this->challengeService->checkAndConsume($challengeKey2, $challengeVal2);
        $this->assertTrue($isValid2);
    }

    public function testInvalidEncryptedKey(): void
    {
        // 测试无效的加密key
        $request = new Request();
        $request->query->set('key', 'invalid-encrypted-key');

        $response = $this->controller->captchaImage($request, $this->challengeService, $this->kernel);

        // 无效的 key 会被解密成垃圾数据，但仍会尝试查找缓存，找不到则返回 'no challenge'
        $this->assertEquals('no challenge', $response->getContent());
    }

    public function testEmptyParameters(): void
    {
        // 测试没有任何参数的情况
        $request = new Request();

        $response = $this->controller->captchaImage($request, $this->challengeService, $this->kernel);

        $this->assertEquals('no key', $response->getContent());
    }

    protected function setUp(): void
    {
        // 设置缓存
        $this->cache = new ArrayAdapter();

        // 设置加密器
        $this->encryptor = $this->createMock(Encryptor::class);
        $this->encryptor->method('encrypt')
            ->willReturnCallback(function ($value) {
                return base64_encode($value);
            });
        $this->encryptor->method('decrypt')
            ->willReturnCallback(function ($value) {
                return base64_decode($value);
            });

        // 设置 URL 生成器
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->urlGenerator->method('generate')
            ->willReturnCallback(function ($route, $params) {
                $key = $params['key'] ?? '';
                return "http://localhost/challenge/captcha-image?key={$key}";
            });

        // 设置 Kernel
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->kernel->method('getCacheDir')
            ->willReturn(sys_get_temp_dir());

        // 创建服务
        $this->challengeService = new ChallengeService($this->cache, $this->urlGenerator, $this->encryptor);

        // 创建控制器
        $this->controller = new ChallengeController();
    }
}