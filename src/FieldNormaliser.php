<?php

class FieldNormaliser
{
    public function normalise($hashMap = [], $projectId, $templateId)
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
        $elements = array();
        $default = new stdClass();
        $default->type = "text";
        $default->name = '';
        $default->required = false;
        $default->label = "";
        $default->value = '';
        $default->microcopy = "";
        $default->limit_type = "words";
        $default->limit = 0;
        $default->plain_text = false;
//var_dump($hashMap); exit;
        foreach($hashMap as $id => $value) {
            $field = clone $default;
            $field->name = $id;
            $field->value = $value;
            ///$field->label =  ;
            $elements[] = $field;
        }
        //var_dump($elements); exit;

        $tab = new stdClass();
        $tab->label = "Content";
        $tab->name = "test";
        $tab->hidden = false;
        $tab->elements = $elements;

        $config = [$tab];

        return [
          'project_id' => $projectId,
          'name' => $hashMap['name'],
//            'parent_id' (optional)	Parent Item ID
          'template_id' => $templateId,
          'config' => base64_encode(json_encode($config))
        ];
    }

    public function create($hashMap = [], $projectId)
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
        $field->name = "el1474287414831";
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
          'template_id' => 421362,
          'config' => base64_encode(json_encode($config))
        ];
    }
}
