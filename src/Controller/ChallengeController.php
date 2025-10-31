<?php

declare(strict_types=1);

namespace Tourze\CaptchaChallengeBundle\Controller;

use Gregwar\Captcha\CaptchaBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\CaptchaChallengeBundle\Service\ChallengeService;

#[Autoconfigure(public: true)]
final class ChallengeController extends AbstractController
{
    #[Route(path: '/challenge/captcha-image', name: 'app_challenge_captcha_image', methods: ['GET', 'HEAD'])]
    public function __invoke(Request $request, ChallengeService $challengeService, KernelInterface $kernel): Response
    {
        $challengeVal = $request->query->get('challengeVal');
        if (null === $challengeVal || '' === $challengeVal || false === $challengeVal) {
            $key = (string) $request->query->get('key', '');
            $challengeKey = $challengeService->getChallengeKeyFromEncryptKey($key);
            if ('' === $challengeKey) {
                return new Response('no key');
            }
            $challengeVal = $challengeService->getChallengeValFromChallengeKey($challengeKey);
        }
        if ('' === $challengeVal) {
            return new Response('no challenge');
        }

        $builder = new CaptchaBuilder();
        $builder->tempDir = "{$kernel->getCacheDir()}/captcha";
        $builder->setPhrase($challengeVal);

        $this->buildCaptcha($builder);

        $img = $builder->getContents();
        assert($img instanceof \GdImage);

        $imageData = $this->generateImageData($img);

        $response = new Response(false !== $imageData ? $imageData : '');
        $this->setCacheHeaders($response);

        return $response;
    }

    private function buildCaptcha(CaptchaBuilder $builder): void
    {
        try {
            if ($this->canUseOcrad()) {
                $builder->buildAgainstOCR();
            } else {
                $builder->build();
            }
        } catch (\Throwable) {
            $builder->build();
        }
    }

    private function canUseOcrad(): bool
    {
        $disabledFunctions = ini_get('disable_functions');
        if (!function_exists('shell_exec') || (is_string($disabledFunctions) && in_array('shell_exec', explode(',', $disabledFunctions), true))) {
            return false;
        }

        $ocradCheck = @shell_exec('which ocrad 2>/dev/null');

        return null !== $ocradCheck && false !== $ocradCheck && '' !== $ocradCheck;
    }

    private function generateImageData(\GdImage $img): string|false
    {
        ob_start();
        \imagejpeg($img);

        return ob_get_clean();
    }

    private function setCacheHeaders(Response $response): void
    {
        $response->headers->set('Content-type', 'image/jpeg');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Cache-Control', 'no-cache');
    }
}
