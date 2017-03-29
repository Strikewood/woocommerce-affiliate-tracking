<?php
/*
 * Plugin Name: WooCommerce Affiliate Tracking
 * Plugin URI:  https://github.com/Strikewood/woocommerce-affiliate-tracking
 * Description: Affiliate tracking extension for WooCommerce.
 * Version:     0.2.1
 * Author:      Strikewood Studios
 * Author URI:  http://strikewood.com/
 */

if ( !defined('ABSPATH') ) exit;

/**
 * WC_Affiliate_Tracking class
 */
class WC_Affiliate_Tracking
{
    /**
     * @var WC_Affiliate_Tracking Single instance of this class
     */
    protected static $instance;

    /**
     * Bootstraps the class and hooks required actions & filters.
     *
     * @return void
     */
    public function __construct()
    {
        if ( class_exists('WC_Integration') && defined('WOOCOMMERCE_VERSION') )
        {
            require_once('includes/class-wc-affiliate-tracking-integration.php');

            // Register the integration
            add_filter('woocommerce_integrations', [$this, 'add_integration']);
        }
    }

    /**
     * Main WC_Affiliate_Tracking instance, ensures only one instance is/can be loaded.
     *
     * @return WC_Affiliate_Tracking
     */
    public static function get_instance()
    {
        if ( is_null(self::$instance) )
        {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Add a new integration to WooCommerce.
     *
     * @param array $integrations WooCommerce integrations
     *
     * @return array WC_Facebook_Remarketing_Integration
     */
    public function add_integration($integrations)
    {
        $integrations[] = 'WC_Affiliate_Tracking_Integration';

        return $integrations;
    }
}

add_action('plugins_loaded', ['WC_Affiliate_Tracking', 'get_instance'], 0);
