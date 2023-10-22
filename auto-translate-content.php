<?php
/**
 * Plugin Name: Auto Translate Content
 * Description: Automatically translates the content to English using Google Cloud Translation API.
 * Version: 3.0
 * Author: BuyReadySite.com
 * Author URI: https://buyreadysite.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add a settings link to the plugins page
function auto_translate_content_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=auto-translate-content-settings">' . __('Settings', 'auto-translate-content') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'auto_translate_content_settings_link');

// Register the settings page
function auto_translate_content_admin_menu()
{
    add_options_page(
        __('Auto Translate Content', 'auto-translate-content'),
        __('Auto Translate Content', 'auto-translate-content'),
        'manage_options',
        'auto-translate-content-settings',
        'auto_translate_content_settings_page'
    );
}

add_action('admin_menu', 'auto_translate_content_admin_menu');

// Display the settings page
function auto_translate_content_settings_page()
{
    ?>
    <div class="wrap auto-translate-content-settings-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php
            $args = array(
                'public'   => true,
                '_builtin' => false
            );

            $post_types = get_post_types($args, 'objects');
            $post_types['post'] = get_post_type_object('post');
            $post_types['page'] = get_post_type_object('page');

// Сортировка типов записей по алфавиту
uasort($post_types, function ($a, $b) {
    return strcmp($a->labels->name, $b->labels->name);
});


            $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

            echo '<h2 class="nav-tab-wrapper">';
            echo '<a href="?page=auto-translate-content-settings&tab=general" class="nav-tab ' . ($active_tab == 'general' ? 'nav-tab-active' : '') . '">' . __('General', 'auto-translate-content') . '</a>';
            foreach ($post_types as $post_type) {
                $tab_id = $post_type->name;
                echo '<a href="?page=auto-translate-content-settings&tab=' . $tab_id . '" class="nav-tab ' . ($active_tab == $tab_id ? 'nav-tab-active' : '') . '">' . $post_type->labels->name . '</a>';
            }
            echo '</h2>';

            echo '<form action="options.php" method="post">';
            settings_fields('auto_translate_content_settings');
            if ($active_tab == 'general') {
                do_settings_sections('auto-translate-content-settings');
            } else {
                foreach ($post_types as $post_type) {
                    if ($active_tab == $post_type->name) {
                        $custom_fields = get_custom_fields_by_post_type($post_type->name);

                        echo '<div class="tab-pane" id="' . $post_type->name . '">';
						
						
// Выводим чекбокс для автоматического перевода и выбора всех полей
        auto_translate_content_auto_translate_checkbox($post_type->name);
        auto_translate_content_select_all_custom_fields_checkbox();
        echo '<br><br>';
/*

// Выводим чекбокс для выбора всех полей
auto_translate_content_select_all_custom_fields_checkbox();
echo '<br><br>';
*/



// Выводим поля в две колонки
$half = ceil(count($custom_fields) / 2);
echo '<div class="row">';
echo '<div class="col-md-6">';
for ($i = 0; $i < $half; $i++) {
    if (isset($custom_fields[$i])) {
        // Выводим поле для перевода в левой колонке
        auto_translate_content_custom_field_input(array('field' => $custom_fields[$i]));
        echo '<label for="auto_translate_content_custom_field_' . $custom_fields[$i] . '">' . $custom_fields[$i] . '</label><br>';
    }
}
echo '</div>';
echo '<div class="col-md-6">';
for ($i = $half; $i < count($custom_fields); $i++) {
    if (isset($custom_fields[$i])) {
        // Выводим поле для перевода в правой колонке
        auto_translate_content_custom_field_input(array('field' => $custom_fields[$i]));
        echo '<label for="auto_translate_content_custom_field_' . $custom_fields[$i] . '">' . $custom_fields[$i] . '</label><br>';
    }
}
echo '</div>';
echo '</div>';

echo '</div>';

                    }
                }
            }
            submit_button();
            echo '</form>';
        ?>
    </div>
    <?php
}




function auto_translate_content_admin_init()
{
    register_setting(
        'auto_translate_content_settings',
        'auto_translate_content_settings',
        'auto_translate_content_sanitize_settings'
    );

    add_settings_section(
        'auto_translate_content_main',
        __('Google Cloud Translation API Settings', 'auto-translate-content'),
        'auto_translate_content_section_text',
        'auto-translate-content-settings'
    );

    add_settings_field(
        'auto_translate_content_api_key',
        __('API Key', 'auto-translate-content'),
        'auto_translate_content_api_key_input',
        'auto-translate-content-settings',
        'auto_translate_content_main'
    );

    add_settings_field(
        'auto_translate_content_target_lang',
        __('Target language', 'auto-translate-content'),
        'auto_translate_content_target_lang_input',
        'auto-translate-content-settings',
        'auto_translate_content_main'
    );
	


    $args = array(
        'public'   => true,
        '_builtin' => false
    );

    $post_types = get_post_types($args, 'objects');
    $post_types['post'] = get_post_type_object('post');
    $post_types['page'] = get_post_type_object('page');

    foreach ($post_types as $post_type) {
        $section_id = 'auto_translate_content_custom_fields_' . $post_type->name;

        add_settings_section(
            $section_id . '_column_1',
            '',
            null,
            'auto-translate-content-settings-' . $post_type->name . '-column-1'
        );

        add_settings_section(
            $section_id . '_column_2',
            '',
            null,
            'auto-translate-content-settings-' . $post_type->name . '-column-2'
        );

        $all_custom_fields = get_all_custom_fields();
        $translatable_custom_fields = array_filter($all_custom_fields, 'is_field_translatable');

        // Добавляем поля package_name[] и package_description[] в список переводимых полей
        $translatable_custom_fields[] = 'package_name[]';
        $translatable_custom_fields[] = 'package_description[]';

        $count = count($translatable_custom_fields);
        $i = 0;

        foreach ($translatable_custom_fields as $field) {
            $field_id = 'auto_translate_content_custom_field_' . $field;
            $column_id = ($i < $count / 2) ? 'column-1' : 'column-2';

            add_settings_field(
                $field_id,
                $field,
                'auto_translate_content_custom_field_input',
                'auto-translate-content-settings-' . $post_type->name . '-' . $column_id,
                $section_id . '_' . $column_id,
                array('field' => $field)
            );

            $i++;
        }
    }

    // Вставьте код из пункта 3 сюда
    add_action('save_post', 'auto_translate_content_auto_translate_on_save', 10, 1);
    add_action('publish_post', 'auto_translate_content_auto_translate_on_save', 10, 1);
}


function is_field_translatable($field_key)
{
    $untranslatable_fields = array('_edit_lock', '_edit_last');
    $translatable_field_prefixes = array('package_name[]', 'package_description[]');

    if (in_array($field_key, $untranslatable_fields)) {
        return false;
    }

    foreach ($translatable_field_prefixes as $prefix) {
        if (strpos($field_key, $prefix) === 0) {
            return true;
        }
    }

    return true;
}


add_action('admin_init', 'auto_translate_content_admin_init');




// Display the target language input field
function auto_translate_content_target_lang_input() {
    $options = get_option('auto_translate_content_settings');
    $site_lang = substr(get_locale(), 0, 2); // Get the first two characters of the site's locale

    echo '<select id="auto_translate_content_target_lang" name="auto_translate_content_settings[target_lang]">';

    $languages = ['en', 'ru', 'es', 'fr', 'de', 'uk', 'it']; // Add more languages here
    foreach ($languages as $lang) {
        $selected = ($options['target_lang'] == $lang || (empty($options['target_lang']) && $lang == $site_lang)) ? 'selected' : '';
        echo '<option value="' . $lang . '" ' . $selected . '>' . $lang . '</option>';
    }

    echo '</select>';
}








// Display the settings section text
function auto_translate_content_section_text()
{
    _e('Enter your Google Cloud Translation API key:', 'auto-translate-content');
}

// Display the API key input field
function auto_translate_content_api_key_input()
{
    $options = get_option('auto_translate_content_settings');
    $nonce = wp_create_nonce('check_api_key_nonce');

    echo '<input id="auto_translate_content_api_key" name="auto_translate_content_settings[api_key]" type="text" value="' . esc_attr($options['api_key']) . '" />';
    echo '<input id="auto_translate_content_api_key_nonce" type="hidden" value="' . $nonce . '" />';

    // Add a button for checking API key validity
    echo '<button id="check_api_key_button" type="button">' . __('Check API Key', 'auto-translate-content') . '</button>';

    // Add a place for displaying the check status
    echo '<span id="api_key_check_status"></span>';

    // Add a script for handling the button click
    echo '
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $("#check_api_key_button").click(function() {
            var data = {
                action: "check_api_key_validity",
                api_key: $("#auto_translate_content_api_key").val(),
                nonce: $("#auto_translate_content_api_key_nonce").val()
            };

            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    $("#api_key_check_status").text("API Key is valid").css("color", "green");
                } else {
                    $("#api_key_check_status").text("API Key is invalid").css("color", "red");
                }
            });
        });
    });
    </script>
    ';
}

function check_api_key() {
    // Verify nonce
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'check_api_key_nonce')) {
        wp_send_json_error();
        exit;
    }

    $api_key = $_REQUEST['api_key'];
    $is_valid = check_api_key_validity($api_key); // функция, которую нужно реализовать

    if ($is_valid) {
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}
add_action('wp_ajax_check_api_key_validity', 'check_api_key');

function check_api_key_validity($api_key) {
    // URL для проверки ключа API
    $url = "https://translation.googleapis.com/language/translate/v2/languages?key=" . $api_key;

    // Инициализация cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, get_site_url());
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);

    // Разбор ответа
    $response = json_decode($output, true);

    // Если в ответе есть поле 'data', то ключ API валидный
    if (isset($response['data'])) {
        return true;
    } else {
        // Если нет, то ключ API невалидный
        return false;
    }
}




function auto_translate_content_sanitize_settings($input)
{
    $new_input = array();
    $options = get_option('auto_translate_content_settings');

    if (isset($input['api_key'])) {
        $new_input['api_key'] = sanitize_text_field($input['api_key']);
    } else {
        $new_input['api_key'] = $options['api_key'];
    }

    if (isset($input['target_lang'])) {
        $new_input['target_lang'] = sanitize_text_field($input['target_lang']);
    } else {
        $new_input['target_lang'] = $options['target_lang'];
    }

    if (isset($input['custom_fields']) && is_array($input['custom_fields'])) {
        foreach ($input['custom_fields'] as $field => $value) {
            $new_input['custom_fields'][$field] = $value ? true : false;
        }
    } else {
        $new_input['custom_fields'] = $options['custom_fields'];
    }

    if (isset($input['auto_translate']) && is_array($input['auto_translate'])) {
        foreach ($input['auto_translate'] as $post_type => $value) {
            $new_input['auto_translate'][$post_type] = isset($value) ? true : false;
        }
    } else {
        $new_input['auto_translate'] = $options['auto_translate'];
    }

    return $new_input;
}





// Translate the content using Google Cloud Translation API
function auto_translate_content($content)
{
    $options = get_option('auto_translate_content_settings');
    $api_key = $options['api_key'];
    $target_lang = isset($options['target_lang']) ? $options['target_lang'] : 'en';

    if (empty($api_key)) {
        return $content;
    }

    $source_lang = 'auto'; // Detect source language automatically

    $url = 'https://translation.googleapis.com/language/translate/v2?key=' . $api_key . '&q=' . urlencode($content) . '&target=' . $target_lang;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, get_site_url());
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response_body = curl_exec($ch);
    curl_close($ch);

    if (!empty($response_body)) {
        $translated_data = json_decode($response_body, true);
        if (isset($translated_data['data']['translations'][0]['translatedText'])) {
            return html_entity_decode($translated_data['data']['translations'][0]['translatedText'], ENT_QUOTES, 'UTF-8');
        }
    }

    return $content;
}


add_filter('the_content', 'auto_translate_content');

function add_translate_button_to_editor()
{
    global $pagenow;

    if ($pagenow == 'post.php' || $pagenow == 'post-new.php') {
        wp_enqueue_script('translate-button', plugins_url('translate-button.js', __FILE__), array('jquery'), '1.0', true);
        wp_enqueue_script('translate-custom-fields', plugins_url('translate-custom-fields.js', __FILE__), array('jquery'), '1.0', true);
        wp_localize_script('translate-button', 'translate_object', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('translate_content_nonce')
        ));
        $options = get_option('auto_translate_content_settings');
        wp_localize_script('translate-custom-fields', 'translate_custom_fields_object', array(
            'custom_fields' => $options['custom_fields']
        ));
    }
}


add_action('admin_enqueue_scripts', 'add_translate_button_to_editor');


function translate_content()
{
    if (!wp_verify_nonce($_REQUEST['nonce'], 'translate_content_nonce') || !isset($_REQUEST['nonce'])) {
        exit("No naughty business please");
    }

    $post_id = intval($_REQUEST['post_id']);
    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(array('message' => 'Недостаточно прав для выполнения данной операции.'));
    }

    $post = get_post($post_id);

    if (!$post) {
        wp_send_json_error(array('message' => 'Запись не найдена.'));
    }

    $translated_content = auto_translate_content($post->post_content);
    $translated_title = auto_translate_content($post->post_title);

    $update_result = wp_update_post(array(
        'ID' => $post_id,
        'post_content' => $translated_content,
        'post_title' => $translated_title
    ), true);

    // Translate custom fields if selected by the user
    $options = get_option('auto_translate_content_settings');
    $selected_custom_fields = isset($options['custom_fields']) ? $options['custom_fields'] : array();

    foreach ($selected_custom_fields as $field => $should_translate) {
        if ($should_translate) {
            $field_value = get_post_meta($post_id, $field, true);
            $translated_field_value = auto_translate_content($field_value);
            update_post_meta($post_id, $field, $translated_field_value);
        }
    }

    if (is_wp_error($update_result)) {
        wp_send_json_error(array('message' => $update_result->get_error_message()));
    } else {
        wp_send_json_success();
    }
}

add_action('wp_ajax_translate_content', 'translate_content');

function my_translate_plugin_enqueue_assets() {
  wp_enqueue_script(
    'gutenberg-translate-button',
    plugin_dir_url(__FILE__) . 'gutenberg-translate-button.js',
    array('wp-plugins', 'wp-edit-post'    , 'wp-element', 'wp-components', 'jquery'),
    filemtime(plugin_dir_path(__FILE__) . 'gutenberg-translate-button.js'),
    true
  );
}
add_action('enqueue_block_editor_assets', 'my_translate_plugin_enqueue_assets');

function get_all_custom_fields()
{
    global $wpdb;
    $custom_fields = $wpdb->get_col("SELECT DISTINCT meta_key FROM $wpdb->postmeta");
    $custom_fields[] = 'package_name[]';
    $custom_fields[] = 'package_description[]';
    return $custom_fields;
}





// Display the custom field checkboxes
function auto_translate_content_custom_field_input($args)
{
    $options = get_option('auto_translate_content_settings');
    $field = $args['field'];
    $checked = isset($options['custom_fields'][$field]) ? 'checked' : '';
    echo '<input id="auto_translate_content_custom_field_' . $field . '" name="auto_translate_content_settings[custom_fields][' . $field . ']" type="checkbox" ' . $checked . ' />';
}

function auto_translate_content_custom_css() {
    echo '<style>
        .auto-translate-content-settings-wrap .nav-tab-wrapper {
            margin-bottom: 1em;
        }
        .auto-translate-content-settings-wrap .form-table {
            display: inline-block;
            vertical-align: top;
            margin-right: 3%;
        }
        .auto-translate-content-settings-wrap .form-table:last-child {
            margin-right: 0;
        }
    </style>';
}
add_action('admin_head-settings_page_auto-translate-content-settings', 'auto_translate_content_custom_css');


function get_custom_fields_by_post_type($post_type)
{
    global $wpdb;

    $results = $wpdb->get_results($wpdb->prepare("
        SELECT DISTINCT meta_key
        FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE p.post_type = %s
        AND pm.meta_key NOT LIKE '\\_%'
        ORDER BY meta_key
    ", $post_type));

    $custom_fields = array();
    foreach ($results as $result) {
        // Убираем проверку is_field_translatable
        $custom_fields[] = $result->meta_key;
    }

    return $custom_fields;
}


function auto_translate_content_select_all_custom_fields_checkbox()
{
    echo '<input id="auto_translate_content_select_all" type="checkbox" />';
    echo '<label for="auto_translate_content_select_all">' . __('Выбрать все', 'auto-translate-content') . '</label>';
}


function auto_translate_content_auto_translate_on_save($post_id)
{
    $options = get_option('auto_translate_content_settings');

    // Получите тип записи
    $post_type = get_post_type($post_id);

    if (isset($_POST['translatable_keys'])) {
        $translatable_keys = json_decode(stripslashes($_POST['translatable_keys']), true);
        if (is_array($translatable_keys)) {
            foreach ($translatable_keys as $key) {
                if (!in_array($key, $translatable_fields)) {
                    $translatable_fields[] = $key;
                }
            }
        }
    }

    // Проверьте состояние чекбокса автоматического перевода для данного типа записи
    if (isset($options['auto_translate_' . $post_type]) && $options['auto_translate_' . $post_type] == 1) {
        // Вызовите функцию перевода для этой записи
        auto_translate_content_translate_post($post_id);
    }

    // Добавьте этот код перед вызовом auto_translate_content_translate_post
    if (isset($_POST['package_name[]']) && isset($_POST['package_description[]'])) {
        $package_names = $_POST['package_name[]'];
        $package_descriptions = $_POST['package_description[]'];

        if (is_array($package_names) && is_array($package_descriptions)) {
            for ($i = 0; $i < count($package_names); $i++) {
                if (!empty($package_names[$i]) && !empty($package_descriptions[$i])) {
                    $translated_package_name = auto_translate_content_translate_text($package_names[$i], $to_lang);
                    $translated_package_description = auto_translate_content_translate_text($package_descriptions[$i], $to_lang);

                    // Сохраните переведенные значения в произвольных полях или в другом месте, где это необходимо.
                }
            }
        }
    }
}



function auto_translate_content_translate_post($post_id, $target_language) {
    // Получаем данные о записи
    $post = get_post($post_id);

    // Получаем настройки плагина
    $options = get_option('auto_translate_content_settings');

    // Получаем ключ API
    $api_key = $options['api_key'];

    // Создаем экземпляр Google Translate API
    $translate = new TranslateClient();
    $translate->setApiKey($api_key);

    // Получаем заголовок и контент записи
    $title = $post->post_title;
    $content = $post->post_content;

    // Переводим заголовок и контент
    $translated_title = $translate->translate($title, [
        'target' => $target_language,
    ]);

    $translated_content = $translate->translate($content, [
        'target' => $target_language,
    ]);

    // Обновляем заголовок и контент записи
    $updated_post = array(
        'ID' => $post_id,
        'post_title' => $translated_title,
        'post_content' => $translated_content,
    );

    wp_update_post($updated_post);

    // Получаем все произвольные поля записи
    $custom_fields = get_post_custom($post_id);

    // Перебираем все произвольные поля
    foreach ($custom_fields as $field_name => $field_value) {
        // Если поле выбрано для перевода
        if (isset($options['custom_fields'][$field_name]) && $options['custom_fields'][$field_name]) {
            // Получаем значение поля
            $field_value = $field_value[0];

            // Переводим значение поля
            $translated_value = $translate->translate($field_value, [
                'target' => $target_language,
            ]);

            // Обновляем значение поля
            update_post_meta($post_id, $field_name, $translated_value);
        }
    }
}

function auto_translate_content_auto_translate_checkbox($post_type)
{
    $options = get_option('auto_translate_content_settings');

    $checked = isset($options['auto_translate'][$post_type]) ? checked($options['auto_translate'][$post_type], true, false) : '';

    echo '<input type="checkbox" id="auto_translate_' . $post_type . '" name="auto_translate_content_settings[auto_translate][' . $post_type . ']" value="1" ' . $checked . ' />';
    echo '<label for="auto_translate_' . $post_type . '">' . __('Enable automatic translation for this post type', 'auto-translate-content') . '</label>';
}

