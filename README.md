# Captcha Challenge Bundle

这是一个Symfony Bundle，提供图片验证码功能，用于网站安全防护。

## 特性

- 生成随机验证码，并将挑战值存储在缓存中
- 提供图片验证码的图像生成和显示
- 提供验证码校验和一次性消费功能
- 支持JSON-RPC接口

## 安装

使用Composer安装:

```bash
composer require tourze/captcha-challenge-bundle
```

## 配置

在您的Symfony应用程序中注册Bundle:

```php
// config/bundles.php
return [
    // ...
    Tourze\CaptchaChallengeBundle\CaptchaChallengeBundle::class => ['all' => true],
];
```

确保配置了环境变量:

```
# .env
LOGIN_CHALLENGE_TYPE=captcha # 启用验证码功能，设置为'null'则禁用
```

## 使用方法

### 生成验证码和获取图片

```php
// 在控制器中
use Tourze\CaptchaChallengeBundle\Service\ChallengeService;

class YourController extends AbstractController
{
    public function generateCaptcha(ChallengeService $challengeService): Response
    {
        // 生成验证码
        $challengeKey = $challengeService->generateChallenge();
        
        // 获取验证码图片URL
        $imageUrl = $challengeService->generateChallengeCaptchaImageUrl($challengeKey);
        
        return $this->json([
            'challengeKey' => $challengeKey,
            'imageUrl' => $imageUrl,
        ]);
    }
}
```

### 验证用户输入的验证码

```php
// 在控制器中
use Tourze\CaptchaChallengeBundle\Service\ChallengeService;

class YourController extends AbstractController
{
    public function verifyCode(
        Request $request,
        ChallengeService $challengeService
    ): Response
    {
        $challengeKey = $request->request->get('challengeKey');
        $userInput = $request->request->get('captchaCode');
        
        // 验证并消费验证码
        $isValid = $challengeService->checkAndConsume($challengeKey, $userInput);
        
        if (!$isValid) {
            return $this->json(['success' => false, 'message' => '验证码错误']);
        }
        
        return $this->json(['success' => true]);
    }
}
```

## 技术细节

- 验证码使用5位随机数字生成
- 验证码在缓存中保存5分钟
- 验证成功后，会立即从缓存中删除，防止重复使用
- 使用 GD 库生成图片，支持反OCR功能

## 依赖

- PHP 8.1+
- GD 扩展
- Symfony 6.4 框架
- PSR-16 缓存实现
- Gregwar/Captcha 库

## 许可

本Bundle使用MIT许可证。详情请参阅 [LICENSE](LICENSE) 文件。
