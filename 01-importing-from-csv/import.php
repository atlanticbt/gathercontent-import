<?php require 'vendor/autoload.php';

use Ramsey\Uuid\Uuid;
use GuzzleHttp\Client;
use League\Csv\Reader;

$username = 'your email address here';
$apikey = 'your api key here';

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

// @link https://gathercontent.com/developers/projects/post-projects/
$createProjectResponse = $client->post('/projects', [
    'form_params' => [
        'account_id' => 12975,
        'name' => 'Importing Example',
    ]
]);

// Get the location of the new project
$projectLocation = $createProjectResponse->getHeader('Location')[0];

// @link https://gathercontent.com/developers/projects/get-projects/
$project = json_decode($client->get($projectLocation)->getBody(), true)['data'];

// Read CSV file
$csv = Reader::createFromPath('content.csv');

// Grab the first row as headings
$headings = $csv->fetchOne();

// Grab all other rows as items
$items = $csv->setOffset(1)->fetchAll();

foreach ($items as $item) {

    // Each Item must have a tab
    $tab = [
        "label" => "Content",
        "name" => Uuid::uuid4()->toString(),
        "hidden" => false,
        "elements" => []
    ];

    // Add text elements to the tab
    foreach ($item as $index => $element) {
        $tab['elements'][] = [
            "type" => "text",
            "name" => Uuid::uuid4()->toString(),
            "required" => false,
            "label" => $headings[$index],
            "value" => "<p>{$element}</p>",
            "microcopy" => "",
            "limit_type" => "words",
            "limit" => 0,
            "plain_text" => false
        ];
    }

    // Use the first name and last name for the Item name
    $itemName = $item[0] . ' ' . $item[1];

    // Create the new Item
    $client->post('/items', [
        'form_params' => [
            'project_id' => $project['id'],
            'name' => $itemName,
            'config' => base64_encode(json_encode([$tab]))
        ]
    ]);

    echo "{$itemName}: Created.\n";
}
