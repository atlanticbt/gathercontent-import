<?php

use Symfony\Component\DomCrawler\Crawler;

class ContentScraper
{
    public function scrapeContent($content, $xpathSelector = 'body')
    {
        $crawler = new Crawler($content);

        switch ($xpathSelector['type']){
          case 'css_selector':
          case 'name':
            $filtered = $crawler->filter($xpathSelector['selector']);
            break;
          case 'xpath_selector':
            $filtered = $crawler->filterXPath($xpathSelector['selector']);
            break;
        }

        if (isset($filtered) && $filtered->count() > 0) {
          if ($xpathSelector['html_flag'] === true) {
            return $filtered->html();
          }
          return $filtered->text();
        }

        return '';
    }
}
