<?php
/**
 * Plugin Name:       Region Redirect Manager (Stable)
 * Description:       Redirects visitors from selected US states or countries based on Cloudflare headers. Based on a proven, stable foundation.
 * Version:           2.2 (Stable)
 * Author:            James Schweda
 */

if (!defined('ABSPATH')) {
    exit;
}

//======================================================================
// 1. CORE REDIRECTION LOGIC (Based on your working code)
//======================================================================

/**
 * The main redirection function.
 */
function region_redirect_from_cloudflare() {
    // Abort for backend users and processes. This is from your original code.
    if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
        return;
    }

    // Retrieve geo-location headers from Cloudflare.
    $region_code  = strtoupper(trim($_SERVER['HTTP_CF_REGION_CODE'] ?? ''));
    $country_code = strtoupper(trim($_SERVER['HTTP_CF_IPCOUNTRY'] ?? ''));

    // Retrieve the saved settings, falling back to the default-generating functions
    // exactly as you implemented. This is a key part of the working solution.
    $states    = get_option('region_redirect_states', region_redirect_get_default_states());
    $countries = get_option('region_redirect_countries', region_redirect_get_default_countries());

    // --- State Redirect Check ---
    // The logic is kept in the exact order you provided, as it is proven to work.
    if (array_key_exists($region_code, $states) && !empty($states[$region_code]['enabled'])) {
        // Use wp_redirect, as in your original code. Added esc_url_raw for security on the output.
        wp_redirect(esc_url_raw($states[$region_code]['url']), 302);
        exit;
    }

    // --- Country Redirect Check ---
    if (array_key_exists($country_code, $countries) && !empty($countries[$country_code]['enabled'])) {
        wp_redirect(esc_url_raw($countries[$country_code]['url']), 302);
        exit;
    }
}
// Use the 'init' hook, which you have confirmed works in your environment.
add_action('init', 'region_redirect_from_cloudflare');


//======================================================================
// 2. ADMIN SETTINGS PAGE (With security and usability enhancements)
//======================================================================

/**
 * Adds the settings page link to the WordPress admin menu.
 */
function region_redirect_settings_menu() {
    add_options_page('Region Redirect Settings', 'Region Redirect', 'manage_options', 'region-redirect-settings', 'region_redirect_settings_page');
}
add_action('admin_menu', 'region_redirect_settings_menu');

/**
 * Registers our two settings options with WordPress.
 * The most important change is adding the sanitization callbacks to secure the saved data.
 */
function region_redirect_register_settings() {
    register_setting('region_redirect_settings_group', 'region_redirect_states', 'region_redirect_sanitize_states');
    register_setting('region_redirect_settings_group', 'region_redirect_countries', 'region_redirect_sanitize_countries');
}
add_action('admin_init', 'region_redirect_register_settings');

/**
 * Renders the complete HTML for the admin settings page.
 */
function region_redirect_settings_page() {
    // Get the saved options, falling back to the defaults if they don't exist yet.
    $states    = get_option('region_redirect_states', region_redirect_get_default_states());
    $countries = get_option('region_redirect_countries', region_redirect_get_default_countries());
    ?>
    <div class="wrap">
        <h1>Region Redirect Settings</h1>
        <div class="notice notice-warning inline"><p><strong>Important:</strong> This plugin requires Cloudflare and its geographic HTTP headers to function correctly.</p></div>

        <form method="post" action="options.php">
            <?php 
            settings_fields('region_redirect_settings_group'); 
            ?>

            <h2>US States</h2>
            <table class="form-table">
                <?php foreach (region_redirect_get_state_list() as $code => $name) : // Using named list for usability ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($name); ?> (<?php echo esc_html($code); ?>)</th>
                        <td>
                            <label><input type="checkbox" name="region_redirect_states[<?php echo esc_attr($code); ?>][enabled]" value="1" <?php checked(!empty($states[$code]['enabled'])); ?>> Enable</label>
                            <br>
                            <input type="url" name="region_redirect_states[<?php echo esc_attr($code); ?>][url]" value="<?php echo esc_attr($states[$code]['url'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <hr>

            <h2>Countries</h2>
            <table class="form-table">
                <?php foreach (region_redirect_get_country_list() as $code => $name) : // Using named list for usability ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($name); ?> (<?php echo esc_html($code); ?>)</th>
                        <td>
                            <label><input type="checkbox" name="region_redirect_countries[<?php echo esc_attr($code); ?>][enabled]" value="1" <?php checked(!empty($countries[$code]['enabled'])); ?>> Enable</label>
                            <br>
                            <input type="url" name="region_redirect_countries[<?php echo esc_attr($code); ?>][url]" value="<?php echo esc_attr($countries[$code]['url'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

//======================================================================
// 3. SANITIZATION AND DATA HELPERS
//======================================================================

/**
 * CRITICAL: Sanitization callback for the state settings.
 * This function cleans the user input before it is saved to the database.
 */
function region_redirect_sanitize_states($input) {
    if (!is_array($input)) return [];
    $sanitized = [];
    foreach (region_redirect_get_state_list() as $code => $name) {
        $sanitized[$code]['enabled'] = !empty($input[$code]['enabled']);
        $sanitized[$code]['url'] = !empty($input[$code]['url']) ? esc_url_raw(trim($input[$code]['url'])) : region_redirect_get_default_url('state', $code);
    }
    return $sanitized;
}

/**
 * CRITICAL: Sanitization callback for the country settings.
 */
function region_redirect_sanitize_countries($input) {
    if (!is_array($input)) return [];
    $sanitized = [];
    foreach (region_redirect_get_country_list() as $code => $name) {
        $sanitized[$code]['enabled'] = !empty($input[$code]['enabled']);
        $sanitized[$code]['url'] = !empty($input[$code]['url']) ? esc_url_raw(trim($input[$code]['url'])) : region_redirect_get_default_url('country');
    }
    return $sanitized;
}

/**
 * Defines the default settings for states.
 */
function region_redirect_get_default_states() {
    $defaults = [];
    $default_enabled_states = ['TX', 'KS', 'IN'];
    foreach (array_keys(region_redirect_get_state_list()) as $state) {
        $defaults[$state] = [
            'enabled' => in_array($state, $default_enabled_states),
            'url'     => region_redirect_get_default_url('state', $state)
        ];
    }
    return $defaults;
}

/**
 * Defines the default settings for countries.
 */
function region_redirect_get_default_countries() {
    $defaults = [];
    foreach (array_keys(region_redirect_get_country_list()) as $country) {
        $defaults[$country] = [
            'enabled' => false,
            'url'     => region_redirect_get_default_url('country')
        ];
    }
    return $defaults;
}

/**
 * Helper function to generate default URLs.
 */
function region_redirect_get_default_url($type, $code = '') {
    if ($type === 'state') {
        return "https://www.defendonlineprivacy.com/" . strtolower($code) . "/";
    }
    return 'https://eff.org';
}

/**
 * Helper function to provide a canonical, named list of US states for the UI.
 */
function region_redirect_get_state_list() {
    return ['AL'=>'Alabama', 'AK'=>'Alaska', 'AZ'=>'Arizona', 'AR'=>'Arkansas', 'CA'=>'California', 'CO'=>'Colorado', 'CT'=>'Connecticut', 'DE'=>'Delaware', 'FL'=>'Florida', 'GA'=>'Georgia', 'HI'=>'Hawaii', 'ID'=>'Idaho', 'IL'=>'Illinois', 'IN'=>'Indiana', 'IA'=>'Iowa', 'KS'=>'Kansas', 'KY'=>'Kentucky', 'LA'=>'Louisiana', 'ME'=>'Maine', 'MD'=>'Maryland', 'MA'=>'Massachusetts', 'MI'=>'Michigan', 'MN'=>'Minnesota', 'MS'=>'Mississippi', 'MO'=>'Missouri', 'MT'=>'Montana', 'NE'=>'Nebraska', 'NV'=>'Nevada', 'NH'=>'New Hampshire', 'NJ'=>'New Jersey', 'NM'=>'New Mexico', 'NY'=>'New York', 'NC'=>'North Carolina', 'ND'=>'North Dakota', 'OH'=>'Ohio', 'OK'=>'Oklahoma', 'OR'=>'Oregon', 'PA'=>'Pennsylvania', 'RI'=>'Rhode Island', 'SC'=>'South Carolina', 'SD'=>'South Dakota', 'TN'=>'Tennessee', 'TX'=>'Texas', 'UT'=>'Utah', 'VT'=>'Vermont', 'VA'=>'Virginia', 'WA'=>'Washington', 'WV'=>'West Virginia', 'WI'=>'Wisconsin', 'WY'=>'Wyoming'];
}

/**
 * Helper function to provide a canonical, named list of target countries for the UI.
 */
function region_redirect_get_country_list() {
    return ['AT'=>'Austria', 'BE'=>'Belgium', 'BG'=>'Bulgaria', 'HR'=>'Croatia', 'CY'=>'Cyprus', 'CZ'=>'Czech Republic', 'DK'=>'Denmark', 'EE'=>'Estonia', 'FI'=>'Finland', 'FR'=>'France', 'DE'=>'Germany', 'GR'=>'Greece', 'HU'=>'Hungary', 'IE'=>'Ireland', 'IT'=>'Italy', 'LV'=>'Latvia', 'LT'=>'Lithuania', 'LU'=>'Luxembourg', 'MT'=>'Malta', 'NL'=>'Netherlands', 'PL'=>'Poland', 'PT'=>'Portugal', 'RO'=>'Romania', 'SK'=>'Slovakia', 'SI'=>'Slovenia', 'ES'=>'Spain', 'SE'=>'Sweden', 'GB'=>'United Kingdom', 'NO'=>'Norway', 'IS'=>'Iceland', 'LI'=>'Liechtenstein', 'CN'=>'China', 'RU'=>'Russia', 'IR'=>'Iran', 'KP'=>'North Korea', 'IN'=>'India', 'BR'=>'Brazil', 'VN'=>'Vietnam', 'TR'=>'Turkey', 'UA'=>'Ukraine'];
}

/**
 * Runs once on plugin activation to populate the default settings.
 */
function region_redirect_activate() {
    add_option('region_redirect_states', region_redirect_get_default_states());
    add_option('region_redirect_countries', region_redirect_get_default_countries());
}
register_activation_hook(__FILE__, 'region_redirect_activate');