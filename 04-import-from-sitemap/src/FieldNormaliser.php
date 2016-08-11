<?php

class FieldNormaliser
{
    public function normalise($hashMap = [], $projectId)
    {
        /*
         * config should be json_encoded, then base64_encoded, and should contain the following array
                [{
                    "label": "Content",
                    "name": "tab1",
                    "hidden": false,
                    "elements": [{
                        "type": "text",
                        "name": "el1",
                        "required": false,
                        "label": "description",
                        "value": "Hopefully you can see now that we just need to concat the above array to return",
                        "microcopy": "",
                        "limit_type": "words",
                        "limit": 0,
                        "plain_text": false
                    }]
                }]
         */
        $field = new stdClass();
        $field->type = "text";
        $field->name = "el1";
        $field->required = false;
        $field->label = "imported";
        $field->value = $hashMap['imported'];
        $field->microcopy = "";
        $field->limit_type = "words";
        $field->limit = 0;
        $field->plain_text = false;

        $elements = [$field];

        $tab = new stdClass();
        $tab->label = "Content";
        $tab->name = "tab1";
        $tab->hidden = false;
        $tab->elements = $elements;

        $config = [$tab];
        return [
            'project_id' => $projectId,
            'name' => $hashMap['name'],
//            'parent_id' (optional)	Parent Item ID
//            'template_id' (optional)	Template ID to apply
            'config' => base64_encode(json_encode($config))
        ];
    }
}
