<?php
/**
 * The core plugin class.
 *
 * @package    EIAFuelSurcharge
 * @subpackage EIAFuelSurcharge\Core
 */

namespace EIAFuelSurcharge\Core;

use EIAFuelSurcharge\Admin\Admin;
use EIAFuelSurcharge\Frontend\Shortcodes;

class Plugin {

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('EIA_FUEL_SURCHARGE_VERSION')) {
            $this->version = EIA_FUEL_SURCHARGE_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'eia-fuel-surcharge';
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Admin($this->get_plugin_name(), $this->get_version());
        
        add_action('admin_enqueue_scripts', [$plugin_admin, 'enqueue_styles']);
        add_action('admin_enqueue_scripts', [$plugin_admin, 'enqueue_scripts']);
        add_action('admin_menu', [$plugin_admin, 'add_plugin_admin_menu']);
        add_action('admin_init', [$plugin_admin, 'register_settings']);
        add_filter('plugin_action_links_' . EIA_FUEL_SURCHARGE_PLUGIN_BASENAME, [$plugin_admin, 'add_action_links']);
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $shortcodes = new Shortcodes($this->get_plugin_name(), $this->get_version());
        
        add_action('wp_enqueue_scripts', [$shortcodes, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$shortcodes, 'enqueue_scripts']);
        add_action('init', [$shortcodes, 'register_shortcodes']);
    }
    
    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        add_action('plugins_loaded', [$this, 'load_plugin_textdomain']);
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'eia-fuel-surcharge',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        
        // Set up scheduled events
        $scheduler = new Scheduler();
        add_action('eia_fuel_surcharge_update_event', [$scheduler, 'run_scheduled_update']);
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}