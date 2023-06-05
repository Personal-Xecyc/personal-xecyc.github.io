<?php
session_start();

require_once 'vendor/autoload.php';

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;

$credentials = __DIR__ .'/credentials.json';
$client = new Google\Client();
$client->setAuthConfig($credentials);
$client->addScope(Google_Service_Calendar::CALENDAR);

// Перевірка, чи передано параметр code в URL
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    try {
        // Обмінати код на access_token
        $accessToken = $client->fetchAccessTokenWithAuthCode($code);

        // Зберегти access_token в сесії або базі даних для подальшого використання
        $_SESSION['access_token'] = $accessToken;

        if (isset($_SESSION['access_token'])) {
            $accessToken = $_SESSION['access_token'];

            $client->setAccessToken($accessToken);

            // Отримати дані з API (приклад з використанням Guzzle)
            $httpClient = new GuzzleHttp\Client();
            $response = $httpClient->get('https://64523bfba2860c9ed405ab30.mockapi.io/eventDate');
            $data = json_decode($response->getBody(), true);

            if ($data === null || !isset($data[0]['events'])) {
                die("Не вдалося отримати дані з API");
            }

            $events = $data[0]['events'];
            $currentDate = $data[0]['currentDate'];
            $utc = $data[0]['utc'];

            $service = new Google\Service\Calendar($client);
            $calendarId = 'primary';

            foreach ($events as $event) {
                $title = $event['title'];
                $description = $event['description'];
                $addDays = $event['add_days'];
                $startTime = $event['start_time'];
                $endTime = $event['end_time'];

                $eventDate = date('Y-m-d', strtotime($currentDate . " + " . ($addDays - 1) . " days"));
                $startDateTime = date('Y-m-d\TH:i:s', strtotime("$eventDate $startTime + $utc hours"));
                $endDateTime = date('Y-m-d\TH:i:s', strtotime("$eventDate $endTime + $utc hours"));

                $eventObject = new Event();
                $eventObject->setSummary($title);
                $eventObject->setDescription($description);

                $start = new EventDateTime();
                $start->setDateTime($startDateTime);
                $eventObject->setStart($start);

                $end = new EventDateTime();
                $end->setDateTime($endDateTime);
                $eventObject->setEnd($end);

                $service->events->insert($calendarId, $eventObject);
            }

            // Зберегти повідомлення в сесії для відображення після редіректу
            $_SESSION['message'] = 'Дані успішно додано в календар';
        } else {
            echo 'Помилка';
            exit;
        }
        exit;
    } catch (Exception $e) {
        echo 'Помилка отримання access_token: ' . $e->getMessage();
        exit;
    }
} else {
    echo 'Помилка: відсутній параметр code в URL';
    exit;
}
?>
