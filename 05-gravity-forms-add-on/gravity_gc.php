<?php require __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Client;

/*
Plugin Name: Gravity GatherContent Form
Description: Send data from Gravity Form to GatherContent
Version: 1.0
*/

class GravityGC {

    private $auth = array('', '');
    private $account = 0;
    private $templates = array();

    public function __construct() {
        add_action('gform_addon_navigation', array(&$this, 'menu'));
        add_action('gform_after_submission', array(&$this, 'check_form'));
        add_action('admin_init', array(&$this, 'save_settings'));
        add_action('wp_ajax_gravity_gc_get_accounts', array(&$this, 'ajax_get_accounts') );
    }

    public function menu($menu_items) {
        $has_full_access = current_user_can("gform_full_access");
        $menu_items[] = array( 'name' => 'gf_gathercontent', 'label' => 'GatherContent', 'callback' => array(&$this, 'options'), "permission" => $has_full_access ? "gform_full_access" : "gravityforms_edit_forms" );
        return $menu_items;
    }


    /* Send data to GC */

    public function check_form($lead) {
        $api = get_option('gravity_gc_api');

        if($api){
            $this->auth = $api['auth'];

            $mappings = get_option('gravity_gc_' . $lead['form_id']);
            if($mappings) {
                try {

                    if($mappings['template_id'] > 0) {
                        $config = $this->api('get', "/templates/{$mappings['template_id']}")['data']['config'];

                        $fields = array_flip($mappings['fields']);

                        foreach($config as &$tab) {
                            foreach($tab['elements'] as &$element) {
                                if(isset($fields[$element['name']])) {
                                    $element['value'] = $lead[$fields[$element['name']]];
                                }
                            }
                        }

                        $fields = $mappings['title']['fields'];
                        $lengths = $mappings['title']['lengths'];

                        $title = '';
                        foreach($fields as $field) {
                            $field = (string) $field;
                            $title .= ' ';
                            if($lengths[$field] != 0) {
                                $title .= substr($lead[$field], 0, $lengths[$field]);
                            }
                            else {
                                $title .= $lead[$field];
                            }
                        }

                        $this->api('post', '/items', array(
                            'project_id' => $mappings['project_id'],
                            'template_id' => $mappings['template_id'],
                            'name' => $title,
                            'config' => base64_encode(json_encode($config))
                        ));
                    }
                } catch(Exception $e) {
                    $form = GFAPI::get_form($lead['form_id']);

                    $notification_id = key($form['notifications']);

                    $notification = $form['notifications'][$notification_id];

                    $notification['subject'] = 'Failed to send lead data to GatherContent.';
                    $notification['message'] = 'Failed to send lead data to GatherContent. ' . $e->getMessage() .'<br><br>{all_fields}';

                    GFCommon::send_notification($notification, $form, $lead);
                }
            }
        }
    }


    /* Save Form Options */

    public function save_settings() {
        if(isset($_POST['gf_gathercontent_nonce'])) {

            $current_tab = rgempty('view', $_GET) ? 'api' : rgget('view');

            if(!in_array($current_tab, array('api', 'mapping'))) {
                $current_tab = 'api';
            }


            check_admin_referer("gf_gathercontent", "gf_gathercontent_nonce");

            $gravity_gc = $_POST['gravity_gc'];

            if($current_tab == 'api') {

                $api = array(
                    'auth' => array(
                        $gravity_gc['email'],
                        $gravity_gc['apikey'],
                    ),
                    'account' => 0,
                );

                if(isset($gravity_gc['account'])) {
                    $api['account'] = $gravity_gc['account'];
                }

                update_option('gravity_gc_api', $api);
            }

            if($current_tab == 'mapping') {

                $form_id = rgempty('form_id', $_GET) ? -1 : rgget('form_id');
                $form_id = intval($form_id);

                $project_id = rgempty('project_id', $_GET) ? -1 : rgget('project_id');
                $project_id = intval($project_id);

                $template_id = rgempty('template_id', $_GET) ? -1 : rgget('template_id');
                $template_id = intval($template_id);

                if($form_id > -1) {

                    $mapping = array(
                        'project_id' => $project_id,
                        'template_id' => $template_id,
                        'title' => array(
                            'fields' => array(),
                            'lengths' => array()
                        ),
                        'fields' => array()
                    );

                    $title = $gravity_gc['title'];

                    if(isset($title['fields'])) {
                        $mapping['title']['fields'] = $title['fields'];

                        foreach($title['fields'] as $field) {
                            $mapping['title']['lengths'][$field] = $title['lengths'][$field];
                        }
                    }


                    $fields = $gravity_gc['fields'];

                    if(!empty($fields)) {
                        foreach($fields as $field_id => $gc_name) {
                            if(intval($gc_name) !== -1) {
                                $mapping['fields'][$field_id] = $gc_name;
                            }
                        }
                    }

                    update_option('gravity_gc_' . $form_id, $mapping);
                }
            }
        }

    }


    /* Options Pages */

    public function _options_api() {

        if(!empty($this->auth[0])) {
            $html = $this->_get_accounts_dropdown();
        }
        else {
            $html = '<input type="button" name="test" value="Get Accounts" class="button button-secondary ajax-get-gc-accounts" />';
        }
        ?>
        <form method="post" enctype="multipart/form-data" style="margin-top:10px;">
            <?php wp_nonce_field("gf_gathercontent", "gf_gathercontent_nonce"); ?>
            <div class="gc_row">
                <label class="gfield_label" for="gravity_gc_email">Email Address:</label>
                <div class="ginput_container">
                    <input name="gravity_gc[email]" type="email" id="gravity_gc_email" class="input" value="<?php esc_attr_e($this->auth[0]) ?>" />
                </div>
            </div>
            <div class="gc_row">
                <label class="gfield_label" for="gravity_gc_apikey">API Key:</label>
                <div class="ginput_container">
                    <input name="gravity_gc[apikey]" type="text" id="gravity_gc_apikey" class="input" value="<?php esc_attr_e($this->auth[1]) ?>" />
                </div>
            </div>
            <div class="gc_row">
                <?php echo $html ?>
            </div>
            <input type="submit" value="Submit" name="submit" class="button button-large button-primary" />
        </form>
        <?php
    }

    public function _options_mapping() {

        $form_id = rgempty('form_id', $_GET) ? -1 : rgget('form_id');
        $form_id = intval($form_id);

        $project_id = rgempty('project_id', $_GET) ? -1 : rgget('project_id');
        $project_id = intval($project_id);

        $template_id = rgempty('template_id', $_GET) ? -1 : rgget('template_id');
        $template_id = intval($template_id);

        $forms = GFAPI::get_forms();

        $query = array(
            'view' => 'mapping',
        );

        $select = '
        <select id="gravity_gc_form" name="gravity_gc[form_id]">
            <option value="-1">Select Form</option>';
        foreach($forms as $form) {
            $query['form_id'] = $form['id'];

            $query['project_id'] = null;
            $query['template_id'] = null;

            $mappings = get_option('gravity_gc_' . $form['id']);
            if($mappings) {
                $query['project_id'] = $mappings['project_id'];
                $query['template_id'] = $mappings['template_id'];
            }


            $select .= '
            <option value="' . add_query_arg($query) . '"' . ($form['id'] === $form_id ? ' selected="selected"': '') .'>' . $form['title'] . '</option>';
        }
        $select .= '
        </select>';



        $mappings = get_option('gravity_gc_' . $form_id);
        if(!$mappings) {
            $mappings = array(
                'project_id' => $project_id,
                'template_id' => $template_id,
                'title' => array(),
                'fields' => array()
            );
        }

        ?>
        <form method="post" enctype="multipart/form-data" style="margin-top:10px;">
            <?php wp_nonce_field("gf_gathercontent", "gf_gathercontent_nonce"); ?>
            <div class="gc_row">
                <label class="gfield_label" for="gravity_gc_form">Form:</label>
                <?php echo $select ?>
            </div>
            <?php
            if($form_id > 0) {
                ?>
            <div class="gc_row">
                <label class="gfield_label" for="gravity_gc_project">Project:</label>
                <?php echo $this->_get_projects_dropdown($project_id) ?>
            </div>
            <?php
            if($project_id > 0) {
                ?>
            <div class="gc_row">
                <label class="gfield_label" for="gravity_gc_template">Template:</label>
                <?php echo $this->_get_templates_dropdown($project_id, $template_id) ?>
            </div>
            <?php
                if($template_id > 0) {
                    $form = GFAPI::get_form($form_id);

                    $config = $this->_get_config_array($template_id);

                    $fields = array_flip(rgempty('fields', $mappings['title']) ? array() : $mappings['title']['fields']);
                    $lengths = rgempty('lengths', $mappings['title']) ? array() : $mappings['title']['lengths'];
                ?>
            <div class="gc_row">
                <h2 class="gfield_label">Title:</h2>
                <div class="ginput_container">
                    <select name="gravity_gc[title][fields][]" id="gravity_gc_title_fields" multiple>
                    <?php
                    $length_html = '';

                    foreach($form['fields'] as $field) {
                        if($field['type'] == 'page' || (isset($field['isHidden']) && $field['isHidden'] === true)) {
                            continue;
                        }
                        if(isset($field['inputs']) && !empty($field['inputs'])) {

                            foreach($field['inputs'] as $input) {
                                if(isset($input['isHidden']) && $input['isHidden'] === true) {
                                    continue;
                                }

                                $id = (string) $input['id'];
                                $length = isset($lengths[$id]) ? $lengths[$id] : 0;
                                $length_html .= '
                        <div class="gc_row hidden">
                            <label class="gfield_label">' . $field['label'] . ' - ' . $input['label'] . ':</label>
                            <input name="gravity_gc[title][lengths][' . $input['id'] . ']" value="'. esc_attr($length) . '" />
                        </div>';
                                echo '
                        <option value="' . $input['id'] . '"' . (isset($fields[$id]) ? ' selected="selected"' : '') . '>' . $field['label'] . ' - ' . $input['label'] . '</option>';
                            }
                        }
                        else {
                            $id = (string) $field['id'];
                            $length = isset($lengths[$id]) ? $lengths[$id] : 0;
                            $length_html .= '
                        <div class="gc_row hidden">
                            <label class="gfield_label">' . $field['label'] . ':</label>
                            <input name="gravity_gc[title][lengths][' . $field['id'] . ']" value="'. esc_attr($length) . '" />
                        </div>';

                            echo '
                        <option value="' . $field['id'] . '"' . (isset($fields[$id]) ? ' selected="selected"' : '') . '>' . $field['label'] . '</option>';
                        }

                    }
                    ?>
                    </select>
                </div>
                <div class="ginput_container">
                    <h2 class="gfield_label">Title Field Lengths:</h2>
                    <?php echo $length_html ?>
                </div>
            </div>
            <div class="gc_row">
                <h2 class="gfield_label">Fields:</h2>
            </div>
            <?php
            foreach($form['fields'] as $field) {
                if($field['type'] == 'page' || (isset($field['isHidden']) && $field['isHidden'] === true)) {
                    continue;
                }

                if(isset($field['inputs']) && !empty($field['inputs'])) {
                    foreach($field['inputs'] as $input) {
                        if(isset($input['isHidden']) && $input['isHidden'] === true) {
                            continue;
                        }
                    ?>
            <div class="gc_row">
                <label class="gfield_label"><?php echo $field['label'] . ' - ' . $input['label'] ?>:</label>
                <div class="ginput_container">
                    <?php echo $this->_config_dropdown($input['id'], $config, $mappings['fields']) ?>
                </div>
            </div>
                    <?php
                    }
                }
                else {
                    ?>
            <div class="gc_row">
                <label class="gfield_label"><?php echo $field['label'] ?>:</label>
                <div class="ginput_container">
                    <?php echo $this->_config_dropdown($field['id'], $config, $mappings['fields']) ?>
                </div>
            </div>
                    <?php
                }
            }
            ?>
            <input type="submit" value="Submit" name="submit" class="button button-large button-primary" />
                <?php
                }
            }
            }
            ?>
        </form>
        <?php
    }

    public function options() {

        // register admin styles
        wp_register_style('gform_admin', GFCommon::get_base_url() . '/css/admin.css');
        wp_register_style('gravity_gc_admin', plugins_url( '', __FILE__ ) . '/css/admin.css');
        wp_print_styles(array('jquery-ui-styles', 'gform_admin', 'gravity_gc_admin'));

        wp_enqueue_script('gravity_gc_admin', plugins_url( '', __FILE__ ) . '/js/admin.js', array('jquery'), '1.0', true);

        $current_tab = rgempty('view', $_GET) ? 'api' : rgget('view');

        if(!in_array($current_tab, array('api', 'mapping'))) {
            $current_tab = 'api';
        }

        $setting_tabs = $this->get_tabs();

        // kind of boring having to pass the title, optionally get it from the settings tab
        if(!$title) {
            foreach($setting_tabs as $tab) {
                if($tab['name'] == $current_tab)
                    $title = $tab['label'];
            }
        }
        ?>
        <div class="wrap">
            <h2><?php echo $title ?></h2>

            <div id="gform_tab_group" class="gform_tab_group vertical_tabs">
                <ul id="gform_tabs" class="gform_tabs">
                    <?php
                    foreach($setting_tabs as $tab) {

                        $query = array(
                            'view' => $tab['name'],
                            'form_id' => null,
                            'project_id' => null,
                        );
                        ?>
                        <li<?php echo $current_tab == $tab['name'] ? ' class="active"' : ''?>>
                            <a href="<?php echo add_query_arg($query); ?>"><?php echo $tab['label'] ?></a>
                        </li>
                        <?php
                    }
                    ?>
                </ul>

                <div id="gform_tab_container" class="gform_tab_container">
                    <div class="gform_tab_content" id="tab_<?php echo $current_tab ?>">
                        <?php
                        $function = '_options_' . $current_tab;
                        $this->{$function}();
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }



    private function _config_dropdown($field_id, $config, $mappings){
        $field_id = (string) $field_id;
        $map = isset($mappings[$field_id]) ? $mappings[$field_id] : -1;

        $html = '
        <option value="-1">(Do not send)</option>';
        $optgroup_open = false;
        foreach($config as $field) {
            if(!isset($field['name'])) {
                if($optgroup_open) {
                    $html .= '</optgroup>';
                }
                $html .= '<optgroup label="' . esc_attr($field['label']) .'">';
            }
            else {
                $html .= '<option value="' . esc_attr($field['name']) . '"' . ($map == $field['name'] ? ' selected="selected"':'') . '>' . $field['label'] . '</option>';
            }
        }
        if($optgroup_open) {
            $html .= '</optgroup>';
        }

        return '
        <select name="gravity_gc[fields][' . $field_id .']">' . $html . '</select>';
    }

    private function _get_config_array($template_id) {
        $options = array();

        $template = $this->templates[$template_id];

        foreach($template as $tab) {
            $options[] = array('label' => $tab['label']);

            foreach($tab['elements'] as $element) {
                $options[] = array('label' => $element['label'], 'name' => $element['name']);
            }
        }

        return $options;
    }

    private function _get_accounts_dropdown() {
        try {
            $accounts = $this->api('get', '/accounts')['data'];

            $html = '
        <label class="gfield_label" for="gravity_gc_account">Account:</label>
        <div class="ginput_container">
            <select name="gravity_gc[account]" id="gravity_gc_account" class="input">';
            foreach($accounts as $account) {
                $html .= '
            <option value="' . $account['id'] . '"' . ($this->account == $account['id'] ? ' selected="selected"' : '') . '>' . $account['name'] . '</option>';
            }
            $html .= '
            </select>
        </div>';

            return $html;

        } catch(Exception $e) {
        }

        return '';
    }

    private function _get_projects_dropdown($project_id) {
        try {
            $projects = $this->api('get', '/projects', array(
                'account_id' => $this->account
            ))['data'];

            $html = '
        <select name="gravity_gc[project]" id="gravity_gc_project">
            <option value="-1">Select Project</option>';
        foreach($projects as $project) {
            $query = array(
                'project_id' => $project['id'],
                'template_id' => null
            );

            $html .= '
        <option value="' . add_query_arg($query) . '"' . ($project_id == $project['id'] ? ' selected="selected"' : '') . '>' . $project['name'] . '</option>';
        }
        $html .= '
        </select>';

            return $html;

        } catch(Exception $e) {
        }

        return '';
    }

    private function _get_templates_dropdown($project_id, $template_id) {
        try {
            $templates = $this->api('get', '/templates', array(
                'project_id' => $project_id
            ))['data'];

            $html = '
        <select name="gravity_gc[template]" id="gravity_gc_template">
            <option value="-1">Select Template</option>';
        foreach($templates as $template) {
            $this->templates[$template['id']] = $template['config'];
            $query = array(
                'template_id' => $template['id']
            );

            $html .= '
        <option value="' . add_query_arg($query) . '"' . ($template_id == $template['id'] ? ' selected="selected"' : '') . '>' . $template['name'] . '</option>';
        }
        $html .= '
        </select>';

            return $html;

        } catch(Exception $e) {
        }

        return '';
    }

    public function get_tabs() {

        $setting_tabs = array();
        $setting_tabs[] = array('name' => 'api', 'label' => 'API');

        $api = get_option('gravity_gc_api');

        if($api){
            $this->auth = $api['auth'];
            $this->account = $api['account'];

            $setting_tabs[] = array('name' => 'mapping' , 'label' => 'Mappings');
        }

        return $setting_tabs;
    }

    private function api($method, $url, $params=array()) {

        $client = new Client(
            array(
                'base_url' => 'https://api.gathercontent.com',
                'defaults' => array(
                    'auth' => $this->auth,
                    'headers' => array(
                        'Accept' => 'application/vnd.gathercontent.v0.5+json',
                    ),
                ),
            )
        );

        if(!empty($params)) {
            $key = 'body';
            if($method == 'get') {
                $key = 'query';
            }

            $params = array(
                $key => $params
            );
        }

        return json_decode($client->{$method}($url, $params)->getBody(), true);
    }


    public function ajax_get_accounts() {
        $out = array('error' => 'Verification failed, please refreshing the item and try again .');
        if ( isset($_POST['_wpnonce']) ) {
            if ( wp_verify_nonce( $_POST['_wpnonce'], 'gf_gathercontent' ) ) {
                $this->auth = array(
                    $_POST['email'],
                    $_POST['apikey']
                );

                $success = true;
                $html = $this->_get_accounts_dropdown();

                if(empty($html)) {
                    $success = false;
                    $html = '<div class="gfield_error">Invalid credentials</div>';
                }

                echo json_encode(array(
                    'success' => $success,
                    'html' => $html
                ));
                exit;
            }
        }
        echo json_encode( $out );
        exit;
    }

}

new GravityGC;
