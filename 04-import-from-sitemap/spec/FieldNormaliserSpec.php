<?php

namespace spec;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FieldNormaliserSpec extends ObjectBehavior
{
    function it_takes_a_hash_map_of_fields_and_converts_to_config()
    {
        $hashMap = [
            'name'     => 'This is the page title',
            'imported' => 'This is the content'
        ];

        $this->normalise($hashMap, 123)->shouldReturn([
            'project_id' => 123,
            'name' => 'This is the page title',
            'config' => base64_encode(json_encode(json_decode('
                [{
                    "label": "Content",
                    "name": "tab1",
                    "hidden": false,
                    "elements": [{
                        "type": "text",
                        "name": "el1",
                        "required": false,
                        "label": "imported",
                        "value": "This is the content",
                        "microcopy": "",
                        "limit_type": "words",
                        "limit": 0,
                        "plain_text": false
                    }]
                }]'
            )))
        ]);
    }
}
