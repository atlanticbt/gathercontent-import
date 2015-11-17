<?php require 'vendor/autoload.php';

use GuzzleHttp\Client;
use League\Csv\Writer;

$username = 'your email address here';
$apikey = 'your api key here';
$projectId = 12345;

// Setup API client
$client = new Client([
    'base_uri' => 'https://api.gathercontent.com',
    'headers' => [
        'Accept' => 'application/vnd.gathercontent.v0.5+json'
    ],
    'auth' => [
        $username,
        $apikey
    ]
]);

// @link https://gathercontent.com/developers/items/get-items/
$itemListResponse = $client->get('/items', [
    'query' => [
        'project_id' => $projectId
    ]
]);

$items = json_decode($itemListResponse->getBody(), true)['data'];

fopen('content.csv', "w");

$csv = Writer::createFromPath('content.csv');

$headings = [];
$content = [];

foreach ($items as $item) {
    $itemResponse = $client->get('/items/' . $item['id']);

    $config = json_decode($itemResponse->getBody(), true)['data']['config'];

    $item = [];

    foreach ($config[0]['elements'] as $element) {
        $item[] = $element['value'];
    }

    $content[] = $item;

    if (empty($headings)) {
        foreach ($config[0]['elements'] as $element) {
            $headings[] = $element['label'];
        }

        $csv->insertOne($headings);
    }
}

foreach ($content as $item) {
    $csv->insertOne($item);
}
