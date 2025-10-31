<?php

declare(strict_types=1);

namespace Tourze\CaptchaChallengeBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\CaptchaChallengeBundle\Controller\ChallengeController;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ChallengeController::class)]
#[RunTestsInSeparateProcesses]
final class ChallengeControllerTest extends AbstractWebTestCase
{
    public function testChallengeImageRouteWithoutKeyReturnsNoKeyMessage(): void
    {
        $client = self::createClientWithDatabase();
        $client->request('GET', '/challenge/captcha-image');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertSame('no key', $client->getResponse()->getContent());
    }

    public function testChallengeImageRouteWithInvalidKeyReturnsNoKeyMessage(): void
    {
        $client = self::createClientWithDatabase();
        // 使用无效格式的密钥，确保解密失败返回空字符串
        $client->request('GET', '/challenge/captcha-image?key=not-a-valid-encrypted-key');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertSame('no key', $client->getResponse()->getContent());
    }

    public function testChallengeImageRouteAccessibleWithoutAuthentication(): void
    {
        $client = self::createClientWithDatabase();
        $client->request('GET', '/challenge/captcha-image');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    public function testHeadMethodAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $client->request('HEAD', '/challenge/captcha-image');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/challenge/captcha-image');
    }
}
