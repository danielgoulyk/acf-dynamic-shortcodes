<?php
/**
 * Plugin Name: ACF Dynamic Shortcodes
 * Description: Dynamically register shortcodes for ACF fields from a selected page using a clean admin interface.
 * Version: 2.0
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

// Register plugin settings
function acfds_register_settings() {
    if (!acfds_acf_check()) return;

    add_option('acfds_page_id', '');
    add_option('acfds_shortcode_custom', []);
    register_setting('acfds_settings_group', 'acfds_page_id');
    register_setting('acfds_settings_group', 'acfds_shortcode_custom');
}
add_action('admin_init', 'acfds_register_settings');

// Admin settings page
function acfds_settings_page() {
    if (!acfds_acf_check()) return;

    $selected_page = get_option('acfds_page_id');
    $shortcode_map = get_option('acfds_shortcode_custom');
    ?>
    <div class="wrap">
        <h1>ACF Dynamic Shortcodes</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('acfds_settings_group');
            do_settings_sections('acfds_settings_group');
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
                        <p class="description"><strong>Note:</strong> This is the page where your ACF fields live. Values from this page will be used to populate shortcodes across your entire site.</p>
                    </td>
                </tr>
            </table>

            <?php if ($selected_page): ?>
                <h2>Shortcode Mapping</h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>ACF Field Name</th>
                            <th>Shortcode Name</th>
                            <th>Copy</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $fields = get_fields($selected_page);
                        if ($fields) {
                            foreach ($fields as $field_name => $val) {
                                $shortcode = $shortcode_map[$field_name] ?? '';
                                echo "<tr>
                                    <td><code>{$field_name}</code></td>
                                    <td><input type='text' name='acfds_shortcode_custom[{$field_name}]' value='{$shortcode}' /></td>
                                    <td>";
                                if ($shortcode) {
                                    echo "<button type='button' class='button copy-button' data-copy='[{$shortcode}]'>Copy</button>";
                                }
                                echo "</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3'><em>No ACF fields found on this page.</em></td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php submit_button(); ?>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const buttons = document.querySelectorAll('.copy-button');
            buttons.forEach(button => {
                button.addEventListener('click', () => {
                    const shortcode = button.dataset.copy;
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
function acfds_register_settings_page() {
    add_options_page('ACF Dynamic Shortcodes', 'ACF Shortcodes', 'manage_options', 'acfds-settings', 'acfds_settings_page');
}
add_action('admin_menu', 'acfds_register_settings_page');

// Register dynamic shortcodes if ACF is present
function acfds_register_dynamic_shortcodes() {
    if (!acfds_acf_check()) return;

    $page_id = get_option('acfds_page_id');
    $custom_map = get_option('acfds_shortcode_custom');

    if (!$page_id || !is_array($custom_map)) return;

    foreach ($custom_map as $field_name => $shortcode) {
        $shortcode = trim($shortcode);
        if (!$shortcode) continue;

        add_shortcode($shortcode, function() use ($field_name, $page_id) {
            $value = get_field($field_name, $page_id);
            if (!$value) return 'Enquire for pricing';
            if (strpos($value, '$') === 0) return $value;
            return '$' . $value;
        });
    }
}
add_action('init', 'acfds_register_dynamic_shortcodes');