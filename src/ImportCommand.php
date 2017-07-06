<?php

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

class ImportCommand extends Command
{
    /**
     * @var Client
     */
    private $httpClient;
    private $config;

    public function configure()
    {
        $this
            ->addOption('configfile', null, InputOption::VALUE_OPTIONAL, 'The configuration file.')
            ->addOption('username', null, InputOption::VALUE_OPTIONAL, 'The username.')
            ->addOption('api_key', null, InputOption::VALUE_OPTIONAL, 'The configuration file.')
            ->addOption('template_id', null, InputOption::VALUE_OPTIONAL, 'The template id.');;

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

        if ($importFile = $input->getOption('configfile')) {
            $this->config = Yaml::parse(file_get_contents($importFile));
        }


        $io = new SymfonyStyle($input, $output);

        $username = $input->getOption('username') ? $input->getOption('username') : $this->config['username'];
        $apiKey   = $input->getOption('api_key') ? $input->getOption('api_key') : $this->config['api_key'];
        $templateId = $input->getOption('template_id') ? $input->getOption('template_id') : $this->config['template_id'];
        if (!$this->authenticate($username, $apiKey)) {

            exit;
        }

        $io->success('Connected to Gather Content.');

        $template = $this->lookupTemplate($username, $apiKey, $templateId);

        // which account would you like to use?
        if (!$accountId = $this->config['account_id']) {
            $accounts = $this->lookupAccounts($username, $apiKey);
            if (count($accounts) == 1) {
                $accountId = reset(array_keys($accounts));
            }
            else {
                $io->error('Please add your account id to the config file.');
            }
        }

        // what project would you like to import to
        $projects = $this->lookupProjects($username, $apiKey, $accountId);

        $projectId = $this->config['project_id'];
        $projectName = $projects[$projectId];
        if (!isset($projectName) || $projectName=='') {
            $projectName = $io->choice(
              'What project would you like to import to? (default is a new project)',
              array_merge(['Create a new project'], $projects),
              'Create a new project'
            );
            $projectId = array_search($projectName, $projects);
        }

        if ($projectId === false) {
            // create a new project
            /*$projectName = $io->ask('What would you like to call the project?');
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
            $projectName = current($newProject);*/
        }

        $io->text('Importing to project: ' . $projectName);

        if (!$sitemap = $this->config['sitemap_url']) {
            $sitemap = $io->ask('Please provide a link to your sitemap.xml (remember the leading http://)');
        }
        $io->text('Searching for items in ' . $sitemap);

        $urls = (new SitemapScraper())->listUrls($sitemap);
        $urlCount = count($urls);
        if (!$urlCount) {
            $io->error('No pages found at ' . $sitemap);
            exit;
        }

        $io->text('We found ' . $urlCount . ' pages to import.');

        if (!$mappings = $this->config['mappings']) {
            $mappings['imported']['field_type'] = 'css_selector';
            $mappings['imported']['css_selector'] = $io->ask('What CSS selector would you like to import from? (e.g. body, .content, #main)  If you would like more options use the --config option.', 'article');
        }

        $io->progressStart($urlCount);

        $contentToFieldMapper = new ContentToFieldMapper();
        $normaliser           = new FieldNormaliser();
        $mapped = array(
          'name' => array('type'=> 'name', 'selector' => 'title', 'html_flag' => false),
        );
        //$mapped = array();
        $mappedValues = array();
        foreach ($mappings as $key => $value) {
            $html_flag = (isset($value['html'])) ? $value['html'] : null;
            if ($value['field_type'] == 'value') {
              $mappedValues[$value['mapped_field']] = $value['value'];
            } elseif ($value['field_type'] == 'css_selector') {
              $mapped[$value['mapped_field']] = array('type' => 'css_selector', 'selector' => $value['css_selector'], 'html_flag' => $html_flag);
            } elseif ($value['field_type'] == 'xpath_selector') {
              $mapped[$value['mapped_field']] = array('type' => 'xpath_selector', 'selector' => $value['xpath_selector'], 'html_flag' => $html_flag);
            }
        }
        foreach ($urls as $url) {

            $hashMap     = $contentToFieldMapper->mapContentToFields(
                file_get_contents($url),
                $mapped
            );
            $hashMap = array_merge($hashMap, $mappedValues);
            //var_dump($hashMap);
            $fields = $normaliser->normalise($hashMap, $projectId, $templateId, $template);
            //var_dump($fields); exit;
            //print_r($fields); exit;
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

    private function lookupTemplate($username, $apiKey, $templateId)
    {
        $options      = ['auth' => [$username, $apiKey]];
        $jsonResponse = $this->httpClient->get('templates/' . $templateId, $options)->getBody();
        $response     = json_decode($jsonResponse);

        return $response;
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
        $response = $this->httpClient->post('items', $options);

        return $response->getStatusCode() === 202;
    }
}
