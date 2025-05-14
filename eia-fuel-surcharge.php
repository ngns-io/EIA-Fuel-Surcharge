<?php
/**
 * EIA Fuel Surcharge Display
 *
 * @package           EIAFuelSurcharge
 * @author            Doug Evenhouse
 * @copyright         2025 Evenhouse Consulting, Inc.
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       EIA Fuel Surcharge Display
 * Plugin URI:        https://ngns.io/plugins/eia-fuel-surcharge
 * Description:       A WordPress plugin that retrieves diesel fuel prices from the U.S. Energy Information Administration (EIA) API and calculates fuel surcharge rates for display on your website through flexible shortcodes.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Doug Evenhouse
 * Author URI:        https://ngns.io
 * Text Domain:       eia-fuel-surcharge
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://ngns.io/plugins/eia-fuel-surcharge/
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define('EIA_FUEL_SURCHARGE_VERSION', '1.0.0');
define('EIA_FUEL_SURCHARGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EIA_FUEL_SURCHARGE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EIA_FUEL_SURCHARGE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Autoload classes
 */
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'EIAFuelSurcharge\\';

    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/includes/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

/**
 * The code that runs during plugin activation.
 */
function activate_eia_fuel_surcharge() {
    require_once EIA_FUEL_SURCHARGE_PLUGIN_DIR . 'includes/Core/Activator.php';
    \EIAFuelSurcharge\Core\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_eia_fuel_surcharge() {
    require_once EIA_FUEL_SURCHARGE_PLUGIN_DIR . 'includes/Core/Activator.php';
    \EIAFuelSurcharge\Core\Activator::deactivate();
}

register_activation_hook(__FILE__, 'activate_eia_fuel_surcharge');
register_deactivation_hook(__FILE__, 'deactivate_eia_fuel_surcharge');

/**
 * Begins execution of the plugin.
 */
function run_eia_fuel_surcharge() {
    $plugin = new \EIAFuelSurcharge\Core\Plugin();
    $plugin->run();
}

run_eia_fuel_surcharge();