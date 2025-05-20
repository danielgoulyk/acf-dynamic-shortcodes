<?php
/**
 * Plugin Name: ACF Dynamic Shortcodes
 * Description: Dynamically register shortcodes for ACF fields from a selected page using a clean admin interface.
 * Version: 3.0
 * Author: Daniel Goulyk (danielgoulyk.com)
 */

 function acfds_acf_check() {
    if (!function_exists('get_field')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>ACF Dynamic Shortcodes:</strong> ACF is not active. Please activate it for this plugin to work.</p></div>';
        });
        return false;
    }
    return true;
}

function acfds_register_settings() {
    if (!acfds_acf_check()) return;
    register_setting('acfds_settings_group', 'acfds_page_id');
    register_setting('acfds_settings_group', 'acfds_shortcode_custom');
}
add_action('admin_init', 'acfds_register_settings');

function acfds_save_acf_field_values() {
    if (!acfds_acf_check()) return;
    if (!current_user_can('manage_options')) return;

    if (
        isset($_POST['acfds_field_values']) &&
        isset($_POST['acfds_page_id']) &&
        is_array($_POST['acfds_field_values'])
    ) {
        $page_id = intval($_POST['acfds_page_id']);
        foreach ($_POST['acfds_field_values'] as $field_key => $field_value) {
            update_field($field_key, sanitize_text_field($field_value), $page_id);
        }
    }
}
add_action('admin_init', 'acfds_save_acf_field_values');

function acfds_settings_page() {
    if (!acfds_acf_check()) return;

    $selected_page = isset($_GET['acfds_page']) ? intval($_GET['acfds_page']) : get_option('acfds_page_id');
    $shortcode_map = get_option('acfds_shortcode_custom', []);

    echo '<div class="wrap">';
    echo '<h1>ACF Dynamic Shortcodes</h1>';
    echo '<p>This plugin allows you to dynamically create shortcodes using values from your ACF fields on a specific page. Select a page where you’ve defined ACF fields, assign shortcode names to each, and instantly display those values anywhere using simple shortcodes.</p>';

    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="acfds-settings" />';
    echo '<label for="acfds_page">Select ACF Source Page:</label><br>';
    echo '<select name="acfds_page" id="acfds_page" onchange="this.form.submit()">';
    echo '<option value="">-- Select a Page --</option>';

    foreach (get_pages() as $page) {
        $selected = ($selected_page == $page->ID) ? 'selected' : '';
        echo "<option value='{$page->ID}' {$selected}>{$page->post_title}</option>";
    }

    echo '</select>';
    echo '<p class="description">This is the page where your ACF fields live. Values from this page will be used to populate shortcodes across your entire site.</p>';
    echo '</form>';

    if ($selected_page) {
        update_option('acfds_page_id', $selected_page);
        $fields = get_fields($selected_page);

        if (!$fields) {
            echo '<p><em>Sorry, looks like you haven’t set any custom ACF fields for this page. Please go to the <a href="' . esc_url(admin_url('edit.php?post_type=acf-field-group')) . '" target="_blank">ACF plugin settings</a> and add them.</em></p>';
        } else {
            echo '<form method="post" action="options.php">';
            settings_fields('acfds_settings_group');
            echo '<input type="hidden" name="acfds_page_id" value="' . esc_attr($selected_page) . '">';

            echo '<h2>Shortcode Mapping</h2>';
            echo '<table class="widefat">';
            echo '<thead>
                <tr>
                    <th>ACF Field Name<br><small>This is the ACF field you’ve defined in the ACF plugin.</small></th>
                    <th>Shortcode Name<br><small>This is the shortcode which will define the “variable”.</small></th>
                    <th>Copy<br><small>Copy shortcode name to clipboard</small></th>
                    <th>Value<br><small>The ACF field value (editable override).</small></th>
                </tr>
            </thead><tbody>';

            foreach ($fields as $field_name => $value) {
                $shortcode = $shortcode_map[$field_name] ?? '';
                $copy_text = $shortcode ? "[{$shortcode}]" : '';
                $copy_disabled = $shortcode ? '' : 'disabled style="opacity:0.5;"';
                $value_escaped = esc_attr($value);
                $shortcode_escaped = esc_attr($shortcode);

                echo "<tr>
                    <td><code>{$field_name}</code></td>
                    <td><input type='text' name='acfds_shortcode_custom[{$field_name}]' value='{$shortcode_escaped}' /></td>
                    <td><button type='button' class='button copy-button' data-copy='{$copy_text}' {$copy_disabled}>Copy</button></td>
                    <td><input type='text' name='acfds_field_values[{$field_name}]' value='{$value_escaped}' /></td>
                </tr>";
            }

            echo '</tbody></table>';
            submit_button('Save Changes');

            // ⚠️ Manual cache notice
            echo '<div class="notice notice-warning inline" style="margin-top: 20px;"><p><strong>Note:</strong> If you\'re using a caching plugin (e.g. LiteSpeed, WP Rocket, W3 Total Cache, etc.), make sure to <strong>clear the cache</strong> after saving changes to see the updates reflected on the front end.</p></div>';

            echo '</form>';
        }
    }

    echo '</div>';
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const buttons = document.querySelectorAll('.copy-button');
            buttons.forEach(button => {
                button.addEventListener('click', () => {
                    const shortcode = button.dataset.copy;
                    if (!shortcode || shortcode === '[]') return;
                    navigator.clipboard.writeText(shortcode).then(() => {
                        button.innerText = 'Copied!';
                        setTimeout(() => button.innerText = 'Copy', 1500);
                    });
                });
            });
        });
    </script>
    <?php
}

add_action('admin_menu', function () {
    add_options_page('ACF Dynamic Shortcodes', 'ACF Shortcodes', 'manage_options', 'acfds-settings', 'acfds_settings_page');
});

function acfds_register_dynamic_shortcodes() {
    if (!acfds_acf_check()) return;

    $page_id = get_option('acfds_page_id');
    $custom_map = get_option('acfds_shortcode_custom');

    if (!$page_id || !is_array($custom_map)) return;

    foreach ($custom_map as $field_name => $shortcode) {
        $shortcode = trim($shortcode, '[] ');
        if (!$shortcode) continue;

        add_shortcode($shortcode, function () use ($field_name, $page_id) {
            $value = get_field($field_name, $page_id);
            return $value ?: 'ACF Error (Undefined)';
        });
    }
}
add_action('init', 'acfds_register_dynamic_shortcodes');