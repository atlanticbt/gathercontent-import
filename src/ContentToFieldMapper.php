<?php

class ContentToFieldMapper
{
    public function mapContentToFields($content, $map)
    {
        $contentScraper = new ContentScraper();

        return array_map(function ($xpathSelector) use ($content, $contentScraper) {
            return $contentScraper->scrapeContent($content, $xpathSelector);
        }, $map);
    }
}
