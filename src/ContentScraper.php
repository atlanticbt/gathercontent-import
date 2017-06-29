<?php

use Symfony\Component\DomCrawler\Crawler;

class ContentScraper
{
    public function scrapeContent($content, $xpathSelector = 'body')
    {
        $crawler = new Crawler($content);

        $filtered = $crawler->filter($xpathSelector[0]);

        if ($filtered->count() > 0) {
          if ($xpathSelector[1] === true) {
            return $filtered->html();
          }
          return $filtered->text();
        }

        return '';
    }
}
