<?php

namespace spec;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ContentScraperSpec extends ObjectBehavior
{
    function it_scrapes_the_text_from_a_given_xpath_selector_from_a_html_page()
    {
        $this->scrapeContent(file_get_contents(__DIR__ . '/../fixtures/sample-page.html'), 'title')
            ->shouldReturn('This is the title');

        $this->scrapeContent(file_get_contents(__DIR__ . '/../fixtures/sample-page.html'), 'article')
            ->shouldReturn('This is the article contents');
    }

    function it_returns_an_empty_string_if_the_xpath_selector_cannot_be_found_in_the_content()
    {
        $this->scrapeContent('<div><p>hello</p></div>', 'foo')->shouldReturn('');
    }
}
