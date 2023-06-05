<?php

require_once 'vendor/autoload.php';

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;

// Початок сесії
session_start();

$credentials = __DIR__ .'/credentials.json';
$client = new Google\Client();
$client->setAuthConfig($credentials);
$client->setRedirectUri('http://localhost:63342/index.php/redirectPage.php');
$client->addScope(Google_Service_Calendar::CALENDAR);

if (!isset($_GET['code'])) {
    $authUrl = $client->createAuthUrl();
    $_SESSION['authUrl'] = $authUrl;  // Зберегти URL авторизації в сесії
    echo '<script>window.location.href = "' . $authUrl . '";</script>';
    exit;
} else {
    $client->authenticate($_GET['code']);
    $_SESSION['access_token'] = $client->getAccessToken();

    // Отримання коду авторизації
    $authorizationCode = $_GET['code'];

    // URL-адреса перенаправлення з кодом авторизації
    $redirectUrl = 'http://localhost:63342/index.php/redirectPage.php?code=' . urlencode($authorizationCode);
    // Перенаправлення на іншу сторінку з кодом авторизації
    echo '<script>window.location.href = "' . $redirectUrl . '";</script>';
    exit;
}
?>
