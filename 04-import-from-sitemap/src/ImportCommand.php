<?php

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportCommand extends Command
{
    /**
     * @var Client
     */
    private $httpClient;

    public function configure()
    {
        $this->setName('scrape')->setDescription('Scrape content and import from an existing website');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->httpClient = new Client([
            'base_uri' => 'https://api.gathercontent.com/',
            'headers' => [
                'Accept' => 'application/vnd.gathercontent.v0.5+json'
            ]
        ]);

        $io = new SymfonyStyle($input, $output);

        $username = $io->ask('What is your username?');
        $apiKey   = $io->ask('What is your API key?');

        $io->text('Validating username and API key...');

        if (!$this->authenticate($username, $apiKey)) {
            $io->error('Hm, your username and API key are not working right now.');
            exit;
        }

        $io->success('Looks good.');

        // which account would you like to use?
        $accounts = $this->lookupAccounts($username, $apiKey);

        if (count($accounts) == 1) {
            $accountId = reset(array_keys($accounts));
        } else {
            $accountName = $io->choice('Which account would you like to use?', array_values($accounts));
            $accountId   = array_search($accountName, $accounts);
        }

        // what project would you like to import to
        $projects = $this->lookupProjects($username, $apiKey, $accountId);

        $projectName = $io->choice(
            'What project would you like to import to? (default is a new project)',
            array_merge(['Create a new project'], $projects),
            'Create a new project'
        );
        $projectId = array_search($projectName, $projects);

        if ($projectId === false) {
            // create a new project
            $projectName = $io->ask('What would you like to call the project?');
            // select a type
            $projectType = $io->choice(
                'Select a type for the project',
                [
                    'website-build',
                    'ongoing-website-content',
                    'marketing-editorial-content',
                    'email-marketing-content',
                    'other'
                ]
            );

            if (!$this->createProject($username, $apiKey, $accountId, $projectName, $projectType)) {
                $io->error('Unable to create project!');
                exit;
            }
            $newProjects = $this->lookupProjects($username, $apiKey, $accountId);
            $newProject  = array_diff($newProjects, $projects);
            $projectId   = key($newProject);
            $projectName = current($newProject);
        }

        $io->text('Importing to project: ' . $projectName);

        $sitemap = $io->ask('Please provide a link to your sitemap.xml (remember the leading http://)');

        $io->text('Searching for items in ' . $sitemap);

        $urls = (new SitemapScraper())->listUrls($sitemap);
        $urlCount = count($urls);
        if (!$urlCount) {
            $io->error('No pages found at ' . $sitemap);
            exit;
        }

        $io->text('We found ' . $urlCount . ' pages to import.');

        $cssSelector = $io->ask('What CSS selector would you like to import from? (e.g. body, .content, #main)', 'article');

        if (!$io->confirm('Ok to import? (this may take a while)')) {
            exit;
        }

        $io->progressStart($urlCount);

        $contentToFieldMapper = new ContentToFieldMapper();
        $normaliser           = new FieldNormaliser();

        foreach ($urls as $url) {
            $hashMap     = $contentToFieldMapper->mapContentToFields(
                file_get_contents($url),
                ['name' => 'title', 'imported' => $cssSelector]
            );
            $fields      = $normaliser->normalise($hashMap, $projectId);
            $this->createItem($fields, $username, $apiKey);
            $io->progressAdvance();
        }

        $io->progressFinish();

        $io->success("We're all done!");
    }

    private function authenticate($username, $apiKey)
    {
        return $this->httpClient->get('me', ['auth' => [$username, $apiKey]])->getStatusCode() === 200;
    }

    private function lookupAccounts($username, $apiKey)
    {
        $jsonResponse = $this->httpClient->get('accounts', ['auth' => [$username, $apiKey]])->getBody();
        $response = json_decode($jsonResponse);

        return array_reduce($response->data, function ($accounts, $account) {
            $accounts[$account->id] = $account->name;

            return $accounts;
        }, []);
    }

    private function lookupProjects($username, $apiKey, $accountId)
    {
        $options      = ['auth' => [$username, $apiKey], 'query' => ['account_id' => $accountId]];
        $jsonResponse = $this->httpClient->get('projects', $options)->getBody();
        $response     = json_decode($jsonResponse);

        return array_reduce($response->data, function ($projects, $project) {
            $projects[$project->id] = $project->name;

            return $projects;
        }, []);
    }

    private function createProject($username, $apiKey, $accountId, $projectName, $projectType)
    {
        $options = [
            'auth'        => [$username, $apiKey],
            'form_params' => [
                'account_id' => $accountId,
                'name'       => $projectName,
                'type'       => $projectType
            ]
        ];

        return $this->httpClient->post('projects', $options)->getStatusCode() === 202;
    }

    private function createItem($data, $username, $apiKey)
    {
        $options = [
            'auth'        => [$username, $apiKey],
            'form_params' => $data
        ];

        return $this->httpClient->post('items', $options)->getStatusCode() === 202;
    }
}
