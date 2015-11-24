<?php require __DIR__ . '/vendor/autoload.php';

use Ramsey\Uuid\Uuid;
use GuzzleHttp\Client;

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

// Item Structure
$item = [
    (object) [
        'label' => "Content",
        'name' => Uuid::uuid4()->toString(),
        'hidden' => false,
        'elements' => [
            (object) [
                'type' => "text",
                'name' => Uuid::uuid4()->toString(),
                'required' => true,
                'label' => "Title",
                'value' => "",
                'microcopy' => "",
                'limit_type' => "chars",
                'limit' => 0,
                'plain_text' => false
            ],
            (object) [
                'type' => "text",
                'name' => Uuid::uuid4()->toString(),
                'required' => true,
                'label' => "Main content",
                'value' => "",
                'microcopy' => "",
                'limit_type' => "words",
                'limit' => 0,
                'plain_text' => true
            ],
        ]
    ]
];

// Make API call
$client->post('/items', [
    'form_params' => [
        'project_id' => $projectId,
        'name' => 'New Item',
        'config' => base64_encode(json_encode($item))
    ]
]);
