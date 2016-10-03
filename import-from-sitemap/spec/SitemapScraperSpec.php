<?php

namespace spec;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SitemapScraperSpec extends ObjectBehavior
{
    function it_takes_a_sitemap_url_and_returns_a_list_of_urls()
    {
        $this->listUrls(__DIR__ . '/../fixtures/sample-sitemap.xml')->shouldReturn([
            'http://gathercontent.com/page-sitemap.xml',
            'http://gathercontent.com/case-studies-sitemap.xml',
            'http://gathercontent.com/articles-sitemap.xml',
            'http://gathercontent.com/resources-sitemap.xml',
            'http://gathercontent.com/thanks-sitemap.xml',
            'http://gathercontent.com/integrations-sitemap.xml',
            'http://gathercontent.com/careers-sitemap.xml',
            'http://gathercontent.com/resource_category-sitemap.xml'
        ]);
    }
}
