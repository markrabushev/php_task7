<?php
require_once __DIR__ . '/vendor/autoload.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;

$bot_token = 'BOT TOKEN'; // GitHub предупреждает, что это конфиденциальная информация, если нужно, могу отправить в личку
$bot_username = 'CountryEmojiBot';
$telegram = new Telegram($bot_token, $bot_username);
$telegram->useGetUpdatesWithoutDatabase();

function getCountryCodeAndName(string $countryName) {
    $url = 'https://restcountries.com/v3.1/translation/' . rawurlencode($countryName);
    $response = file_get_contents($url);
    if (!$response) {
        return null;
    } else {
        $data = json_decode($response, true);
        if(count($data) > 0){
            foreach ($data as $country) {
                if($country['name']['common'] == $countryName || $country['name']['official'] == $countryName) {
                    $countryCode = $country['cca2'];
                    $countryNameEn = $country['name']['common'];
                    $responce = ["name" => $countryNameEn, "code" => $countryCode];
                    return $responce;
                }
            }
        }
        $country = $data[0];
        $countryCode = $country['cca2'];
        $countryNameEn = $country['name']['common'];
        $responce = ["name" => $countryNameEn, "code" => $countryCode];
        return $responce;
    }
    return null;
}

function getFlagEmoji($code) {
    $code = strtoupper($code);
    $firstChar = 0x1F1E6 + ord($code[0]) - 65;  
    $secondChar = 0x1F1E6 + ord($code[1]) - 65;
    return mb_chr($firstChar) . mb_chr($secondChar);
}

function getWikipediaUrl(string $countryName) {
    $name = urlencode(str_replace(' ', '_', trim($countryName)));
    return "https://en.wikipedia.org/wiki/$name";
}

while (true) {
    try {
        $response = $telegram->handleGetUpdates();
        if ($response->isOk()) {
            $updates = $response->getResult();
            foreach ($updates as $update) {
                
                $message = $update->getMessage();
                $chat_id = $message->getChat()->getId();
                $text = $message->getText();

                if ($text === '/start') {
                    Request::sendMessage([
                        'chat_id' => $chat_id,
                        'text' => "Отправьте название страны, и я пришлю её флаг и ссылку на Википедию."
                    ]);
                    continue;
                }

                $APIResponce = getCountryCodeAndName($text);
                if (!$APIResponce) {
                    Request::sendMessage([
                        'chat_id' => $chat_id,
                        'text' => "Страна не найдена."
                    ]);
                    continue;
                }

                $flag = getFlagEmoji($APIResponce["code"]);
                $wikiUrl = getWikipediaUrl($APIResponce["name"]);

                Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "Флаг: $flag\nСсылка: $wikiUrl"
                ]);
            }
        }
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
    }
    sleep(1);
}