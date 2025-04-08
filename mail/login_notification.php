<?php
defined('C5_EXECUTE') or die('Access denied');

use Concrete\Core\User\User;

/** @var string $fingerprint */
/** @var string $ip */
/** @var string $userAgent */
/** @var User $user */

$subject = t("Login Notification");

$body = t("Dear User,") . "\n";
$body .= "\n";
$body .= t("We wanted to inform you that a login to your account was detected.") . "\n";
$body .= "\n";
$body .= t("Details of the login are as follows:") . "\n";
$body .= t("Fingerprint: %s", $fingerprint) . "\n";
$body .= t("IP Address: %s", $ip) . "\n";
$body .= t("User Agent: %s", $userAgent) . "\n";
$body .= "\n";
$body .= t("If this login was not made by you, please contact support immediately.") . "\n";

$bodyHTML = nl2br($body);
