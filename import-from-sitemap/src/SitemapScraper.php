<?php

class SitemapScraper
{
    public function listUrls($sitemapUrl)
    {
        $contents = file_get_contents($sitemapUrl);
        $urlset = new SimpleXMLElement($contents);
        $urls = [];
        foreach ($urlset as $url) {
            $urls[] = (trim((string)$url->loc));
        }
        return $urls;
    }
}
