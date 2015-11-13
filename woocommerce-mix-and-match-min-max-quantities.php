<?php
/**
 * Plugin Name: WooCommerce Mix and Match: Min/Max Quantities
 * Plugin URI: http://www.woothemes.com/products/woocommerce-mix-and-match-products/
 * Description: Set minimum/maximum quantities for unlimited mix and match containers
 * Version: 1.0.0
 * Author: Kathy Darling
 * Author URI: http://kathyisawesome.com/
 * Developer: Kathy Darling, Manos Psychogyiopoulos
 * Developer URI: http://kathyisawesome.com/
 * Text Domain: wc-mnm-min-max
 * Domain Path: /languages
 *
 * Copyright: © 2015 Kathy Darling and Manos Psychogyiopoulos
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */



/**
 * The Main WC_MNM_Min_Max_Quantities class
 **/
if ( ! class_exists( 'WC_MNM_Min_Max_Quantities' ) ) :

class WC_MNM_Min_Max_Quantities {

	/**
	 * @var WC_MNM_Min_Max_Quantities - the single instance of the class
	 */
	protected static $_instance = null;

	/**
	 * variables
	 */
	public $version = '1.0.0';
	public $required_woo = '2.3';

	/**
	 * Main WC_MNM_Min_Max_Quantities instance.
	 *
	 * Ensures only one instance of WC_MNM_Min_Max_Quantities is loaded or can be loaded
	 *
	 * @static
	 * @return WC_MNM_Min_Max_Quantities - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}


	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wc-mnm-min-max' ) );
	}


	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wc-mnm-min-max' ) );
	}


	/**
	 * WC_MNM_Min_Max_Quantities Constructor
	 *
	 * @access 	public
     * @return 	WC_MNM_Min_Max_Quantities
	 */
	public function __construct() {

		// Load translation files
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		// Remove and replace product meta options
		remove_action( 'woocommerce_mnm_product_options', array( 'WC_Mix_and_Match_Admin', 'container_size_options' ), 10 );
		add_action( 'woocommerce_mnm_product_options', array( $this, 'container_size_options' ) );

		// save the new field
		add_action( 'woocommerce_process_product_meta_mix-and-match', array( $this, 'process_meta' ), 20 );

		// add the attribute to front end display
		add_filter( 'woocommerce_mix_and_match_data_attributes', array( $this, 'data_attributes' ), 10, 2 );

		// modify the max container size properties on sync
		add_filter( 'woocommerce_mnm_max_container_size', array( $this, 'max_container_size' ), 10, 2 );

		// display the quantity message info when no JS
		add_filter( 'woocommerce_mnm_container_quantity_message', array( $this, 'quantity_message' ), 10, 2 );

		// validate the container
		add_filter( 'woocommerce_mnm_add_to_cart_validation', array( $this, 'validate_container' ), 10, 3 );

		// Modify order items to include bundle meta
		add_action( 'woocommerce_add_order_item_meta', array( $this, 'add_order_item_meta' ), 10, 3 );

		// Hide bundle configuration metadata in order line items
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hidden_order_item_meta' ) );

		// register script
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );

		// load scripts
		add_action( 'woocommerce_mix-and-match_add_to_cart', array( $this, 'load_scripts' ), 20 );

		// QV support
		add_action( 'wc_quick_view_enqueue_scripts', array( $this, 'quickview_support' ) );

    }


	/*-----------------------------------------------------------------------------------*/
	/* Localization */
	/*-----------------------------------------------------------------------------------*/


	/**
	 * Make the plugin translation ready
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'wc-mnm-min-max' , false , dirname( plugin_basename( __FILE__ ) ) .  '/languages/' );
	}

	/*-----------------------------------------------------------------------------------*/
	/* Admin */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Show new options in metabox
	 * @param  int 	$post_id
	 * @return void
	 */
	public static function container_size_options( $post_id ) {

		// min value is the same as the regular container size
		$min_qty = intval( get_post_meta( $post_id, '_mnm_container_size', true ) );

		// don't eval with inval yet as we need to test whether a max meta field even exists
		$max_qty = get_post_meta( $post_id, '_mnm_max_container_size', true );

		// existing "unlimited" containers have a 0 quantity
		if( $min_qty === 0 ){
			$min_qty = 1;
			$max_qty = 0;
		// if no max value, set it to minimum (essentially a fixed container size)
		} elseif( ! $max_qty ){
			$max_qty = $min_qty;
		}

		woocommerce_wp_text_input( 
					array( 
						'id' => 'mnm_container_size',
						'value' => $min_qty,
						'label' => __( 'Minimum Container Size', 'wc-mnm-min-max' ), 
						'description' => __( 'Minimum quantity for Mix and Match containers', 'wc-mnm-min-max' ), 
						'type' => 'number', 
						'min' => 1,
						'desc_tip' => true ) );

		woocommerce_wp_text_input( 
					array( 
						'id' => 'mnm_max_container_size',
						'value' => $max_qty,
						'label' => __( 'Maximum Container Size', 'wc-mnm-min-max' ), 
						'description' => __( 'Maximum quantity for Mix and Match containers. Use 0 to not enforce an upper size limit.', 'wc-mnm-min-max' ), 
						'type' => 'number', 
						'desc_tip' => true ) );

				?>

		<?php
	}


	/**
	 * Process, verify and save product data
	 * @param  int 	$post_id
	 * @return void
	 */
	public static function process_meta( $post_id ) {  delete_post_meta( $post_id, '_mnm_max_container_size' );

		// Min container size
		$min_qty = intval( get_post_meta( $post_id, '_mnm_container_size', true ) );

		// Max container size
		$max_qty = ( isset( $_POST[ 'mnm_max_container_size'] ) ) ? intval( wc_clean( $_POST['mnm_max_container_size' ] ) ) : $min_qty;

		if ( $max_qty > 0 && $min_qty > $max_qty ) {
			$max_qty = $min_qty;
			WC_Admin_Meta_Boxes::add_error( __( 'The maximum Mix & Match container size cannot be smaller than the minimum container size.', 'wc-mnm-min-max' ) );
		}

		update_post_meta( $post_id, '_mnm_max_container_size', $max_qty );

		return $post_id;

	}

	/*-----------------------------------------------------------------------------------*/
	/* Front End Display */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Add the min/max attribute
	 *
	 * @param array $attributes - added as data-something="value" in mnm cart div
	 * @param obj $product
	 * @return array
	 */
	public function data_attributes( $attributes, $product ) {
		$min_qty = intval( get_post_meta( $product->id, '_mnm_container_size', true ) );
		$max_qty = get_post_meta( $product->id, '_mnm_max_container_size', true );
		$max_qty = $max_qty !== false ? intval( $max_qty ) : $min_qty;

		$attributes['min_container_size'] = $min_qty;
		$attributes['max_container_size'] = $max_qty;
		return $attributes;
	}


	/**
	 * Filter the maximum container size.
	 *
	 * @param int $size
	 * @param obj $product
	 * @return inte
	 */
	function max_container_size( $size, $product ){
		$max_qty = get_post_meta( $product->id, '_mnm_max_container_size', true );
		if( $max_qty !== false ){
			$size = intval( $max_qty );
		}
		return $size;
	}


	/**
	 * Quantity message when no JS
	 *
	 * @param string $message
	 * @param obj $product
	 * @return void
	 */
	function quantity_message( $message, $product ){
		$min_qty = get_post_meta( $product->id, '_mnm_container_size', true );
		$max_qty = get_post_meta( $product->id, '_mnm_max_container_size', true );

		// if a max quantity exists and is not equal to the min quantity we have a non-fixed container size
		if( $max_qty && intval( $min_qty ) != intval( $max_qty ) ){

			$min_qty = intval( $min_qty );
			$max_qty = intval( $max_qty );

			if( $max_qty > 0 ){
				// if not set, min_container_size is always 1, because the container can't be empty
				$min_qty = $min_qty > 0 ? $min_qty : 1;
				$message = sprintf( __( 'Please choose between %d and %d items to continue...', 'wc-mnm-min-max' ), $min_qty, $max_qty );
			} else if( $min_qty > 0 ){
				$message = sprintf( _n( 'Please choose at least %d item to continue...', 'Please choose at least %d items to continue...', $min_qty, 'wc-mnm-min-max' ), $min_qty );
			} 

		}

		return $message;
	}

	/*-----------------------------------------------------------------------------------*/
	/* Cart Validation */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Validate container against our minimum quantity requirement
	 *
	 * @param bool $passed
	 * @param obj $mnm_stock
	 * @param obj $product
	 * @return void
	 */
	function validate_container( $passed, $mnm_stock, $product ){
		$total_items_in_container = $mnm_stock->get_total_quantity();

		$min_qty = intval( get_post_meta( $product->id, '_mnm_container_size', true ) );

		$max_qty = get_post_meta( $product->id, '_mnm_max_container_size', true );

		// if a max quantity exists and is not equal to the min quantity we have a non-fixed container size
		if( $max_qty && $min_qty != intval( $max_qty ) ){
			$min_qty = $min_qty > 0 ? $min_qty : 1;
			$max_qty = intval( $max_qty );

			// validate that an unlimited container is in min/max range & build a specific error message
			if( $max_qty > 0 && $min_qty > 0 && ( $total_items_in_container > $max_qty || $total_items_in_container < $min_qty ) ){
				$message = $total_items_in_container > $max_qty ? __( 'You have selected too many items.', 'wc-mnm-min-max' ) : __( 'You have selected too few items.', 'wc-mnm-min-max' );
				$message .= '  ' . 	sprintf( __( 'Please choose between %d and %d items for &quot;%s&quot;', 'wc-mnm-min-max' ), $min_qty, $max_qty, $product->get_title() );
				wc_add_notice( $message, 'error' );
				$passed = false;
			} 
			// validate that an unlimited container has minimum number of items
			else if( $min_qty > 0 && $total_items_in_container < $min_qty ){
				wc_add_notice( sprintf( __( 'You have selected too few items. Please choose at least %d items for &quot;%s&quot;', 'wc-mnm-min-max' ), $min_qty, $product->get_title() ), 'error' );
				$passed = false;
			} 

		}

		return $passed;
	}

	/*-----------------------------------------------------------------------------------*/
	/* Scripts and Styles */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Load scripts
	 *
	 * @return void
	 */
	public function frontend_scripts() {

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_script( 'wc-add-to-cart-mnm-min-max', plugins_url( 'js/add-to-cart-mnm-min-max' . $suffix . '.js', __FILE__ ), array( 'wc-add-to-cart-mnm' ), WC_MNM_Min_Max_Quantities()->version, true );

		$params = array(
			'i18n_min_max_qty_error'               => __( '%vPlease choose between %min and %max items to continue&hellip;', 'wc-mnm-min-max' ),
			'i18n_min_qty_error'               => __( '%vPlease choose at least %min items to continue&hellip;', 'wc-mnm-min-max' )
		);

		wp_localize_script( 'wc-add-to-cart-mnm-min-max', 'wc_mnm_min_max_params', $params );

	}

	/**
	 * QuickView scripts init
	 * @return void
	 */
	public function quickview_support() {

		if ( ! is_product() ) {
			$this->frontend_scripts();
			wp_enqueue_script( 'wc-add-to-cart-mnm-min-max' );
		}
	}

	/**
	 * Load the script anywhere the MNN add to cart button is displayed
	 * @return void
	 */
	public function load_scripts(){
		wp_enqueue_script( 'wc-add-to-cart-mnm-min-max' );
	}


} //end class: do not remove or there will be no more guacamole for you

endif; // end class_exists check


/**
 * Returns the main instance of WC_MNM_Min_Max_Quantities to prevent the need to use globals.
 *
 * @return WooCommerce
 */
function WC_MNM_Min_Max_Quantities() {
	return WC_MNM_Min_Max_Quantities::instance();
}

// Launch the whole plugin
WC_MNM_Min_Max_Quantities();