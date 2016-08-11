<?php

namespace spec;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ContentToFieldMapperSpec extends ObjectBehavior
{
    function it_takes_a_hash_map_of_fields_to_xpath_selectors_and_returns_a_hash_map_with_the_matching_content()
    {
        $mapped = $this->mapContentToFields(
            file_get_contents(__DIR__ . '/../fixtures/sample-page.html'),
            ['name' => 'title', 'imported' => 'article']
        );

        $mapped['name']->shouldBe('This is the title');
        $mapped['imported']->shouldContain('This is the article content');
    }
}
