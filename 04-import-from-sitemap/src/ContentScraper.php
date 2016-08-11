<?php

use Symfony\Component\DomCrawler\Crawler;

class ContentScraper
{
    public function scrapeContent($content, $xpathSelector = 'body')
    {
        $crawler = new Crawler($content);

        $filtered = $crawler->filter($xpathSelector);

        if ($filtered->count() > 0) {
            return $filtered->text();
        }

        return '';
    }
}
