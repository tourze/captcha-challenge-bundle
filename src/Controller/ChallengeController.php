<?php

namespace Tourze\CaptchaChallengeBundle\Controller;

use Gregwar\Captcha\CaptchaBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\CaptchaChallengeBundle\Service\ChallengeService;

class ChallengeController extends AbstractController
{
    #[Route(path: '/challenge/captcha-image', name: 'app_challenge_captcha_image')]
    public function captchaImage(Request $request, ChallengeService $challengeService, KernelInterface $kernel): Response
    {
        $challengeVal = $request->query->get('challengeVal');
        if (!$challengeVal) {
            $key = $request->query->get('key', '');
            $challengeKey = $challengeService->getChallengeKeyFromEncryptKey($key);
            if (empty($challengeKey)) {
                return new Response('no key');
            }
            $challengeVal = $challengeService->getChallengeValFromChallengeKey($challengeKey);
        }
        if (empty($challengeVal)) {
            return new Response('no challenge');
        }

        $builder = new CaptchaBuilder();
        $builder->tempDir = "{$kernel->getCacheDir()}/captcha";
        $builder->setPhrase($challengeVal);

        // 尝试反OCR生成，失败的话就正常处理了
        try {
            $builder->buildAgainstOCR();
        } catch (\Throwable) {
            $builder->build();
        }

        /** @var \GdImage $img */
        $img = $builder->getContents();

        // 创建一个空白的输出缓冲区
        ob_start();
        // 将 Gd 图像对象 $img 写入输出缓冲区
        \imagejpeg($img);
        // 从输出缓冲区获取图片数据
        $imageData = ob_get_clean();

        $response = new Response($imageData);
        $response->headers->set('Content-type', 'image/jpeg');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
