<?php

if ( !defined('ABSPATH') ) exit;

/**
 * Affiliate Tracking integration class
 *
 * @extends WC_Integration
 */
class WC_Affiliate_Tracking_Integration extends WC_Integration
{
    /**
     * Init and hook in the integration.
     *
     * @return void
     */
    public function __construct()
    {
        $this->id                 = 'affiliate_tracking';
        $this->method_title       = __('Affiliate Tracking', 'woocommerce-affiliate-tracking');
        $this->method_description = __('Integrates affiliate tracking with WooCommerce.', 'woocommerce-affiliate-tracking');

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Affiliate networks
        $this->avangate       = $this->get_option('at_avangate');
        $this->facebook       = $this->get_option('at_facebook');
        $this->impact_radius  = $this->get_option('at_impact_radius');
        $this->link_connector = $this->get_option('at_link_connector');
        $this->upsellit       = $this->get_option('at_upsellit');

        // Network data
        $this->link_connector_id = $this->get_option('at_link_connector_id');
        $this->upsellit_id       = $this->get_option('at_upsellit_id');
        $this->upsellit_name     = $this->get_option('at_upsellit_name');

        // Save settings
        add_action('woocommerce_update_options_integration_' . $this->id, [$this, 'process_admin_options']);

        // Tracking code
        add_action('wp_footer', [$this, 'display_tracking_code'], 999999);
    }

    /**
     * Init settings form fields
     *
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'at_link_connector_id' => [
                'title'         => __('Link Connector ID:', 'woocommerce-affiliate-tracking'),
                'description'   => __('Account ID for Link Connector. Example: 000000001234', 'woocommerce-affiliate-tracking'),
                'type'          => 'text'
            ],
            'at_upsellit_id' => [
                'title'         => __('UpSellit ID:', 'woocommerce-affiliate-tracking'),
                'description'   => __('Site ID for UpSellit. Example: 1234', 'woocommerce-affiliate-tracking'),
                'type'          => 'text'
            ],
            'at_upsellit_name' => [
                'title'         => __('UpSellit Script Name:', 'woocommerce-affiliate-tracking'),
                'description'   => __('Name from the UpSellit script. Example: <code>upsellit.com/launch/XXXX.jsp</code>', 'woocommerce-affiliate-tracking'),
                'type'          => 'text'
            ],
            'at_avangate' => [
                'title'         => __('Affiliate Networks:', 'woocommerce-affiliate-tracking'),
                'label'         => __('Avangate', 'woocommerce-affiliate-tracking'),
                'checkboxgroup' => 'start',
                'type'          => 'checkbox'
            ],
            'at_facebook' => [
                'label'         => __('Facebook', 'woocommerce-affiliate-tracking'),
                'checkboxgroup' => '',
                'type'          => 'checkbox'
            ],
            'at_impact_radius' => [
                'label'         => __('Impact Radius', 'woocommerce-affiliate-tracking'),
                'checkboxgroup' => '',
                'type'          => 'checkbox'
            ],
            'at_link_connector' => [
                'label'         => __('Link Connector', 'woocommerce-affiliate-tracking'),
                'checkboxgroup' => '',
                'type'          => 'checkbox'
            ],
            'at_upsellit' => [
                'label'         => __('Upsell It', 'woocommerce-affiliate-tracking'),
                'checkboxgroup' => 'end',
                'type'          => 'checkbox'
            ]
        ];
    }

    /**
     * Display tracking code globally and on the order received page
     *
     * @return string
     */
    public function display_tracking_code()
    {
        if ( is_admin() ) return;

        $this->output_global_tracking_code();

        if ( is_order_received_page() )
        {
            $order_id = isset($wp->query_vars['order-received']) ? $wp->query_vars['order-received'] : 0;

            if ( 0 < $order_id )
            {
                $this->output_order_tracking_code($order_id);
            }
        }
    }

    /**
     * Output the global tracking code for enabled networks
     *
     * @return string
     */
    public function output_global_tracking_code()
    {
        if ('yes' == $this->facebook)
        {

        }

        if ('yes' == $this->upsellit)
        {
            $this->output_upsellit_global_code();
        }
    }

    /**
     * Output the order tracking code for enabled networks
     *
     * @param int $order_id
     *
     * @return string
     */
    public function output_order_tracking_code($order_id)
    {
        $order = new WC_Order($order_id);

        if ('yes' == $this->impact_radius)
        {

        }

        if ('yes' == $this->facebook)
        {

        }

        if ('yes' == $this->link_connector)
        {
            $this->output_link_connector_order_code($order);
        }

        if ('yes' == $this->upsellit)
        {
            $this->output_upsellit_order_code($order);
        }
    }

    /**
     * Output the order tracking code for link connector
     *
     * @param WC_Order $order
     *
     * @return string
     */
    public function output_link_connector_order_code($order)
    {
        if ( !$this->link_connector_id ) return;

        $src_params = '?lc=' . esc_js($this->link_connector_id) . '&amp;oid=' . esc_js( $order->get_order_number() ) . '&amp;amt=' . esc_js( $order->get_total() );

        $script_src = 'https://linkconnector.com/tmjs.php' . $src_params;
        $img_src    = 'https://linkconnector.com/tm.php' . $src_params;

        $code = '<script src="' . $script_src . '"></script><noscript><img border="0" src="" alt=""></noscript>';

        echo $code;
    }

    /**
     * Output the global tracking code for upSellit
     *
     * @return string
     */
    public function output_upsellit_global_code()
    {
        if ( !$this->upsellit_id || !$this->upsellit_name ) return;

        $code = '<script>/* <![CDATA[ */
    function USI_installCode() {
        var USI_headID = document.getElementsByTagName("head")[0];
        var USI_installID = document.createElement(\'script\');
        USI_installID.type = \'text/javascript\';
        USI_installID.src = \'http\'+ (document.location.protocol==\'https:\'?\'s://www\':\'://www\')+ \'.upsellit.com/launch/' . esc_js($this->upsellit_name) . '\';
        USI_headID.appendChild(USI_installID);
    }
    if (window.addEventListener){
        window.addEventListener(\'load\', USI_installCode, true);
    } else if (window.attachEvent) {
        window.attachEvent(\'onload\', USI_installCode);
    } else {
        USI_installCode();
    }
/* ]]> */</script>';

        echo $code;
    }

    /**
     * Output the order tracking code for upSellit
     *
     * @param WC_Order $order
     *
     * @return string
     */
    public function output_upsellit_order_code($order)
    {
        if ( !$this->upsellit_id || !$this->upsellit_name ) return;

        $code = '<script>/* <![CDATA[ */
    var USI_orderID = \'' . esc_js( $order->get_order_number() ) . '\';
    var USI_orderAmt = \'' . esc_js( $order->get_total() ) . '\';
    var USI_headID = document.getElementsByTagName("head")[0];
    var USI_dynScript = document.createElement("script");
    USI_dynScript.setAttribute(\'type\',\'text/javascript\');
    USI_dynScript.src = \'http\'+ (document.location.protocol==\'https:\'?\'s://www\':\'://www\')+ \'.upsellit.com/upsellitReporting.jsp?command=REPORT&siteID=' . esc_js($this->upsellit_id) . '&productID=77&position=1&orderID=\'+escape(USI_orderID)+\'&orderAmt=\'+escape(USI_orderAmt);
    USI_headID.appendChild(USI_dynScript);
    var USI_dynScript2 = document.createElement("script");
    USI_dynScript2.setAttribute(\'type\',\'text/javascript\');
    USI_dynScript2.src = \'http\'+ (document.location.protocol==\'https:\'?\'s://www\':\'://www\')+\'.upsellit.com/hound/sale.jsp?orderID=\'+escape(USI_orderID)+\'&orderAmt=\'+escape(USI_orderAmt);
    USI_headID.appendChild(USI_dynScript2);
/* ]]> */</script>';

        echo $code;
    }
}
