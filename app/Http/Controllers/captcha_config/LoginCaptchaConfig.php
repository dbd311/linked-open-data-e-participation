<?php

// BotDetect PHP Captcha configuration options
// more details here: http://captcha.com/doc/php/howto/captcha-configuration.html
// ---------------------------------------------------------------------------

$LBD_CaptchaConfig = CaptchaConfiguration::GetSettings();

$LBD_CaptchaConfig->CodeLength = 3;

$imageStyles = array(
  ImageStyle::Chipped, 
  ImageStyle::Negative, 
);
$LBD_CaptchaConfig->ImageStyle = CaptchaRandomization::GetRandomImageStyle($imageStyles);
