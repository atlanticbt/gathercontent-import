<?php

class SitemapScraper
{
    public function listUrls($sitemapUrl)
    {
        $pathinfo = pathinfo($sitemapUrl);
        $contents = file_get_contents($sitemapUrl);
        switch ($pathinfo['extension']) {
          case 'csv':
            $urls = str_getcsv($contents, "\n");
            break;
          case 'xml':
            $urls = [];
            $urlset = new SimpleXMLElement($contents);
            foreach ($urlset as $url) {
              $urls[] = (trim((string)$url->loc));
            }
            break;
        }
        return $urls;
    }
}
