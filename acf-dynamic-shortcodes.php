<?php
/**
 * Plugin Name: ACF Dynamic Shortcodes
 * Description: Dynamically register shortcodes for ACF fields from a selected page.
 * Version: 1.1
 * Author: Daniel Goulyk (danielgoulyk.com)
 */

// Check if ACF is active
function acfds_acf_check() {
    if (!function_exists('get_field')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>ACF Dynamic Shortcodes:</strong> Advanced Custom Fields (ACF) is not active. Please install and activate ACF for this plugin to work.</p></div>';
        });
        return false;
    }
    return true;
}

// 1. Register plugin settings
function acfds_register_settings() {
    if (!acfds_acf_check()) return;

    add_option('acfds_page_id', '');
    add_option('acfds_shortcode_map', '');
    register_setting('acfds_settings_group', 'acfds_page_id');
    register_setting('acfds_settings_group', 'acfds_shortcode_map');
}
add_action('admin_init', 'acfds_register_settings');

// 2. Admin settings page
function acfds_settings_page() {
    if (!acfds_acf_check()) return;

    ?>
    <div class="wrap">
        <h1>ACF Dynamic Shortcodes</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('acfds_settings_group');
            do_settings_sections('acfds_settings_group');
            $selected_page = get_option('acfds_page_id');
            $shortcode_map = get_option('acfds_shortcode_map');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Select ACF Source Page</th>
                    <td>
                        <select name="acfds_page_id">
                            <option value="">-- Select a Page --</option>
                            <?php
                            $pages = get_pages();
                            foreach ($pages as $page) {
                                $selected = ($selected_page == $page->ID) ? 'selected' : '';
                                echo "<option value='{$page->ID}' {$selected}>{$page->post_title}</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Shortcode to ACF Field Mapping</th>
                    <td>
                        <textarea name="acfds_shortcode_map" rows="10" cols="50" placeholder="Example:&#10;price_basic = starting_price_basic&#10;price_pro = starting_price_pro"><?php echo esc_textarea($shortcode_map); ?></textarea>
                        <p class="description">Format: <code>shortcode_name = acf_field_name</code>, one per line.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
function acfds_register_settings_page() {
    add_options_page('ACF Dynamic Shortcodes', 'ACF Shortcodes', 'manage_options', 'acfds-settings', 'acfds_settings_page');
}
add_action('admin_menu', 'acfds_register_settings_page');

// 3. Register dynamic shortcodes if ACF is present
function acfds_register_dynamic_shortcodes() {
    if (!acfds_acf_check()) return;

    $page_id = get_option('acfds_page_id');
    $mapping = get_option('acfds_shortcode_map');

    if (!$page_id || !$mapping) return;

    $lines = explode("\n", $mapping);

    foreach ($lines as $line) {
        if (strpos($line, '=') === false) continue;

        [$shortcode, $field] = array_map('trim', explode('=', $line, 2));

        add_shortcode($shortcode, function() use ($field, $page_id) {
            $value = get_field($field, $page_id);
            if (!$value) return 'Enquire for pricing';
            if (strpos($value, '$') === 0) return $value;
            return '$' . $value;
        });
    }
}
add_action('init', 'acfds_register_dynamic_shortcodes');