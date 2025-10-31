# Captcha Challenge Bundle

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)](#)
[![Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](#)

[English](README.md) | [中文](README.zh-CN.md)

A Symfony Bundle that provides image captcha functionality for website security protection.

## Features

- Generate random captcha codes and store challenge values in cache
- Provide image captcha generation and display
- Provide captcha verification and one-time consumption functionality
- Support JSON-RPC interface

## Installation

Install via Composer:

```bash
composer require tourze/captcha-challenge-bundle
```

## Configuration

Register the Bundle in your Symfony application:

```php
// config/bundles.php
return [
    // ...
    Tourze\CaptchaChallengeBundle\CaptchaChallengeBundle::class => ['all' => true],
];
```

Ensure environment variables are configured:

```env
# .env
LOGIN_CHALLENGE_TYPE=captcha # Enable captcha functionality, set to 'null' to disable
```

## Usage

### Generating Captcha and Getting Image

```php
// In your controller
use Tourze\CaptchaChallengeBundle\Service\ChallengeService;

class YourController extends AbstractController
{
    public function generateCaptcha(ChallengeService $challengeService): Response
    {
        // Generate captcha
        $challengeKey = $challengeService->generateChallenge();
        
        // Get captcha image URL
        $imageUrl = $challengeService->generateChallengeCaptchaImageUrl($challengeKey);
        
        return $this->json([
            'challengeKey' => $challengeKey,
            'imageUrl' => $imageUrl,
        ]);
    }
}
```

### Verifying User Input Captcha

```php
// In your controller
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
        
        // Verify and consume captcha
        $isValid = $challengeService->checkAndConsume($challengeKey, $userInput);
        
        if (!$isValid) {
            return $this->json(['success' => false, 'message' => 'Invalid captcha code']);
        }
        
        return $this->json(['success' => true]);
    }
}
```

## Advanced Usage

### Custom Captcha Configuration

```php
// Custom captcha generation with specific settings
$challengeKey = $challengeService->generateChallenge();

// Generate image with custom parameters
$imageUrl = $challengeService->generateChallengeCaptchaImageUrl($challengeKey);
```

### Integration with Forms

```php
// In your form type
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

$builder
    ->add('challengeKey', HiddenType::class)
    ->add('captchaCode', TextType::class, [
        'label' => 'Enter captcha code',
        'required' => true,
    ]);
```

## Technical Details

- Captcha uses 5-digit random numbers
- Captcha is stored in cache for 5 minutes
- After successful verification, it is immediately deleted from cache to prevent reuse
- Uses GD library to generate images, supports anti-OCR functionality

## Requirements

- PHP 8.1+
- GD extension
- Symfony 6.4 framework
- PSR-16 cache implementation
- Gregwar/Captcha library

## License

This Bundle is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
