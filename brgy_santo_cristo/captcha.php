<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$code = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 5);
$_SESSION['captcha'] = $code;

header("Content-type: image/png");

$image = imagecreate(100, 40);
if (!$image) {
    die("Failed to create image.");
}
$bg = imagecolorallocate($image, 255, 255, 255);
$text = imagecolorallocate($image, 0, 0, 0);
imagestring($image, 5, 20, 10, $code, $text);
imagepng($image);
imagedestroy($image);
