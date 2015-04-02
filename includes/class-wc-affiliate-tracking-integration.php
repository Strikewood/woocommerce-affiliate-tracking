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
        $this->avangate_id       = $this->get_option('at_avangate_id');
        $this->facebook_id       = $this->get_option('at_facebook_id');
        $this->impact_radius_src = $this->get_option('at_impact_radius_src');
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
            'at_avangate_id' => [
                'title'         => __('Avangate ID:', 'woocommerce-affiliate-tracking'),
                'description'   => __('Merchant ID for Avangate.', 'woocommerce-affiliate-tracking'),
                'type'          => 'text'
            ],
            'at_facebook_id' => [
                'title'         => __('Facebook ID:', 'woocommerce-affiliate-tracking'),
                'description'   => __('Pixel ID for Facebook.', 'woocommerce-affiliate-tracking'),
                'type'          => 'text'
            ],
            'at_impact_radius_src' => [
                'title'         => __('Impact Radius URL:', 'woocommerce-affiliate-tracking'),
                'description'   => __('URL to Impact Radius script. Example: <code>XXXXXXXXXXX.cloudfront.net/js/XXXX/XXXXX/irv3.js</code>', 'woocommerce-affiliate-tracking'),
                'type'          => 'text'
            ],
            'at_link_connector_id' => [
                'title'         => __('Link Connector ID:', 'woocommerce-affiliate-tracking'),
                'description'   => __('Account ID for Link Connector.', 'woocommerce-affiliate-tracking'),
                'type'          => 'text'
            ],
            'at_upsellit_id' => [
                'title'         => __('UpSellit ID:', 'woocommerce-affiliate-tracking'),
                'description'   => __('Site ID for UpSellit.', 'woocommerce-affiliate-tracking'),
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
		global $wp;

        if ( is_admin() || current_user_can('manage_options') ) return;

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
        if ('yes' == $this->upsellit)
        {
            $this->output_upsellit_global_code();
        }
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
        USI_installID.src = \'http\'+ (document.location.protocol==\'https:\'?\'s://www\':\'://www\')+ \'.upsellit.com/launch/' . esc_js($this->upsellit_name) . '.jsp\';
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
     * Output the order tracking code for enabled networks
     *
     * @param int $order_id
     *
     * @return string
     */
    public function output_order_tracking_code($order_id)
    {
        $order = new WC_Order($order_id);

        if ('yes' == $this->avangate)
        {
            $this->output_avangate_order_code($order);
        }

        if ('yes' == $this->impact_radius)
        {
            $this->output_impact_radius_order_code($order);
        }

        if ('yes' == $this->facebook)
        {
            $this->output_facebook_order_code($order);
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
     * Output the order tracking code for avangate
     *
     * @param WC_Order $order
     *
     * @return string
     */
    public function output_avangate_order_code($order)
    {
        if ( !$this->avangate_id ) return;

        $code  = '<script src="https://affiliates.avangate.com/js/track.js"></script>';
        $code .= '<script>/* <![CDATA[ */
    window.onload = function () {
        tracking = new AVGTracking(\'https://affiliates.avangate.com/track_click.php\');
        tracking.merch = \'' . esc_js($this->avangate_id) . '\';
        tracking.ref = \'' . esc_js( $order->get_order_number() ) . '\';
        tracking.currency = \'' . esc_js( $order->get_order_currency() ) . '\';
        tracking.totalPrice = ' . esc_js( $order->get_total() ) . ';';

        if ( $order->get_items() )
        {
            foreach ( $order->get_items() as $item )
            {
                $_product = $order->get_product_from_item($item);
                $sku      = $_product->get_sku() ? $_product->get_sku() : $_product->id;
                $price    = round( $order->get_item_total($item) / $item['qty'], 2 );

                $code .= '
    tracking.addProduct(\'' . esc_js( $item['name'] ) . '\', ' . esc_js( $price ) . ', ' . esc_js($item['qty']) . ', \'' . esc_js($sku) . '\');';
            }
        }

        $code .= '
        tracking.init();
    }
/* ]]> */</script>';

        echo $code;
    }

    /**
     * Output the order tracking code for facebook
     *
     * @param WC_Order $order
     *
     * @return string
     */
    public function output_facebook_order_code($order)
    {
        if ( !$this->facebook_id ) return;

        $code  = '<script>/* <![CDATA[ */
    (function() {
        var _fbq = window._fbq || (window._fbq = []);
        if (!_fbq.loaded) {
            var fbds = document.createElement(\'script\');
            fbds.async = true;
            fbds.src = \'//connect.facebook.net/en_US/fbds.js\';
            var s = document.getElementsByTagName(\'script\')[0];
                s.parentNode.insertBefore(fbds, s);
            _fbq.loaded = true;
        }
    })();
    window._fbq = window._fbq || [];
    window._fbq.push([\'track\', \'' . esc_js($this->facebook_id) . '\', {\'value\':\'' . esc_js( $order->get_total() ) . '\',\'currency\':\'' . esc_js( $order->get_order_currency() ) . '\'}]);
/* ]]> */</script>';
        $code .= '<noscript><img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/tr?ev=' . urlencode($this->facebook_id) . '&amp;cd[value]=' . urlencode( $order->get_total() ) . '&amp;cd[currency]=' . urlencode( $order->get_order_currency() ) . '&amp;noscript=1" /></noscript>';

        echo $code;
    }

    /**
     * Output the order tracking code for link connector
     *
     * @param WC_Order $order
     *
     * @return string
     */
    public function output_impact_radius_order_code($order)
    {
        if ( !$this->impact_radius_src ) return;

        $code  = '<script src="https://' . $this->impact_radius_src . '"></script>';
        $code .= '<script>/* <![CDATA[ */
    irEvent.setOrderId("' . esc_js( $order->get_order_number() ) . '");';

        if ( $order->get_items() )
        {
            foreach ( $order->get_items() as $item )
            {
                $_product   = $order->get_product_from_item($item);
                $categories = get_the_terms($_product->id, 'product_cat');

                $category = $categories ? $category[0]->name : '';

                $sku = $_product->get_sku() ? $_product->get_sku() : $_product->id;

                $code .= '
    irEvent.addItem("' . esc_js($category) . '", "' . esc_js($sku) . '", "' . esc_js( $order->get_item_total($item) ) . '", "' . esc_js($item['qty']) . '");';
            }
        }

        $coupons = $order->get_used_coupons();

        $coupon = $coupons ? $coupons[0] : '';

        $code .= '
    irEvent.setPromoCode("' . esc_js($coupon) . '");
    irEvent.fire();
/* ]]> */</script>';

        echo $code;
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

        $src_params = '?lc=' . urlencode($this->link_connector_id) . '&amp;oid=' . urlencode( $order->get_order_number() ) . '&amp;amt=' . urlencode( $order->get_total() );

        $script_src = 'https://linkconnector.com/tmjs.php' . $src_params;
        $img_src    = 'https://linkconnector.com/tm.php' . $src_params;

        $code = '<script src="' . $script_src . '"></script><noscript><img border="0" src="" alt=""></noscript>';

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
