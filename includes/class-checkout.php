<?php
/**
 * Checkout integration: extra fields (classic and block checkout), script, order display.
 */

defined( 'ABSPATH' ) || exit;

class Kontoros_CL_Checkout {

	// Block checkout field ids (WooCommerce Additional Checkout Fields API).
	const BLOCK_CODE = 'kontoros/company-code';
	const BLOCK_VAT  = 'kontoros/vat-code';

	public static function init() {
		if ( Kontoros_CL_Settings::enabled( 'add_fields' ) ) {
			add_filter( 'woocommerce_billing_fields', array( __CLASS__, 'classic_fields' ), 20 );
			add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'classic_save' ), 20 );
			add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'admin_display' ) );
			add_action( 'woocommerce_order_details_after_customer_details', array( __CLASS__, 'customer_display' ) );
			add_action( 'woocommerce_email_customer_details', array( __CLASS__, 'email_display' ), 25, 3 );
		}
		if ( Kontoros_CL_Settings::enabled( 'enabled' ) ) {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		}
		if ( Kontoros_CL_Settings::enabled( 'show_company' ) ) {
			// Read by both the classic and the block checkout. Only "hidden" is changed, so the
			// shop's own "required" choice is kept, and turning our setting off restores "hidden".
			add_filter( 'option_woocommerce_checkout_company_field', array( __CLASS__, 'show_company' ) );
			add_filter( 'default_option_woocommerce_checkout_company_field', array( __CLASS__, 'show_company' ) );
		}
	}

	public static function show_company( $value ) {
		return ( 'hidden' === $value || false === $value ) ? 'optional' : $value;
	}

	/** Classic (shortcode) checkout: insert fields right after Company. */
	public static function classic_fields( $fields ) {
		$priority = isset( $fields['billing_company']['priority'] ) ? (int) $fields['billing_company']['priority'] : 30;
		$fields['billing_company_code'] = array(
			'label'    => __( 'Company code', 'kontoros-company-lookup' ),
			'required' => false,
			'class'    => array( 'form-row-first' ),
			'priority' => $priority + 1,
			'autocomplete' => 'off',
		);
		$fields['billing_vat_code'] = array(
			'label'    => __( 'VAT code', 'kontoros-company-lookup' ),
			'required' => false,
			'class'    => array( 'form-row-last' ),
			'priority' => $priority + 2,
			'autocomplete' => 'off',
		);
		return $fields;
	}

	/** Block checkout: register the same fields in the address section. */
	public static function register_block_fields() {
		if ( ! Kontoros_CL_Settings::enabled( 'add_fields' ) || ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => self::BLOCK_CODE,
				'label'    => __( 'Company code', 'kontoros-company-lookup' ),
				'optionalLabel' => __( 'Company code (optional)', 'kontoros-company-lookup' ),
				'location' => 'address',
				'type'     => 'text',
				'required' => false,
				'attributes' => array( 'autocomplete' => 'off' ),
			)
		);
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => self::BLOCK_VAT,
				'label'    => __( 'VAT code', 'kontoros-company-lookup' ),
				'optionalLabel' => __( 'VAT code (optional)', 'kontoros-company-lookup' ),
				'location' => 'address',
				'type'     => 'text',
				'required' => false,
				'attributes' => array( 'autocomplete' => 'off' ),
			)
		);
	}

	public static function enqueue() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}
		wp_enqueue_style( 'kontoros-cl', KONTOROS_CL_URL . 'assets/checkout.css', array(), KONTOROS_CL_VERSION );
		wp_enqueue_script( 'kontoros-cl', KONTOROS_CL_URL . 'assets/checkout.js', array(), KONTOROS_CL_VERSION, true );
		wp_localize_script(
			'kontoros-cl',
			'kontorosCL',
			array(
				'endpoint'    => esc_url_raw( rest_url( 'kontoros/v1/search' ) ),
				'fillAddress' => Kontoros_CL_Settings::enabled( 'fill_address' ),
				'fillFields'  => Kontoros_CL_Settings::enabled( 'add_fields' ),
				'attribution' => Kontoros_CL_Settings::enabled( 'attribution' ),
				'blockCode'   => self::BLOCK_CODE,
				'blockVat'    => self::BLOCK_VAT,
				'i18n'        => array(
					'code'     => __( 'Code', 'kontoros-company-lookup' ),
					'vat'      => __( 'VAT', 'kontoros-company-lookup' ),
					'inactive' => __( 'not active', 'kontoros-company-lookup' ),
					'source'   => __( 'Data: kontoros.lt', 'kontoros-company-lookup' ),
				),
			)
		);
	}

	/** Block checkout additional fields are stored in order meta under this prefix. */
	const BLOCK_META = '_wc_billing/';

	/** True when WooCommerce itself shows our block fields (order page, admin, emails). */
	private static function block_fields_active() {
		return function_exists( 'woocommerce_register_additional_checkout_field' );
	}

	/**
	 * Classic checkout: also store the codes the way the block checkout does, so WooCommerce
	 * shows them in one place for orders from either checkout (incl. block theme order pages).
	 * The classic _billing_company_code / _billing_vat_code meta stays for invoicing plugins.
	 */
	public static function classic_save( $order ) {
		if ( ! self::block_fields_active() ) {
			return;
		}
		foreach ( array( '_billing_company_code' => self::BLOCK_CODE, '_billing_vat_code' => self::BLOCK_VAT ) as $classic => $block ) {
			$v = (string) $order->get_meta( $classic );
			if ( '' !== $v ) {
				$order->update_meta_data( self::BLOCK_META . $block, $v );
			}
		}
	}

	/** Company code and VAT code of an order, from classic or block checkout meta. */
	public static function order_values( $order ) {
		$code = $order->get_meta( '_billing_company_code' );
		$vat  = $order->get_meta( '_billing_vat_code' );
		return array(
			'code' => (string) $code,
			'vat'  => (string) $vat,
		);
	}

	public static function admin_display( $order ) {
		$v = self::order_values( $order );
		if ( self::block_fields_active() || ( '' === $v['code'] && '' === $v['vat'] ) ) {
			return; // block checkout orders show their own additional fields
		}
		echo '<p>';
		if ( '' !== $v['code'] ) {
			echo '<strong>' . esc_html__( 'Company code', 'kontoros-company-lookup' ) . ':</strong> <a href="' . esc_url( 'https://www.kontoros.lt/paieska?q=' . rawurlencode( $v['code'] ) ) . '" target="_blank" rel="noopener">' . esc_html( $v['code'] ) . '</a><br>';
		}
		if ( '' !== $v['vat'] ) {
			echo '<strong>' . esc_html__( 'VAT code', 'kontoros-company-lookup' ) . ':</strong> ' . esc_html( $v['vat'] );
		}
		echo '</p>';
	}

	public static function customer_display( $order ) {
		$v = self::order_values( $order );
		if ( self::block_fields_active() || ( '' === $v['code'] && '' === $v['vat'] ) ) {
			return;
		}
		echo '<p class="kontoros-cl-order">';
		if ( '' !== $v['code'] ) {
			echo esc_html__( 'Company code', 'kontoros-company-lookup' ) . ': ' . esc_html( $v['code'] ) . '<br>';
		}
		if ( '' !== $v['vat'] ) {
			echo esc_html__( 'VAT code', 'kontoros-company-lookup' ) . ': ' . esc_html( $v['vat'] );
		}
		echo '</p>';
	}

	public static function email_display( $order, $sent_to_admin = false, $plain_text = false ) {
		$v = self::order_values( $order );
		if ( self::block_fields_active() || ( '' === $v['code'] && '' === $v['vat'] ) ) {
			return;
		}
		$lines = array();
		if ( '' !== $v['code'] ) {
			$lines[] = __( 'Company code', 'kontoros-company-lookup' ) . ': ' . $v['code'];
		}
		if ( '' !== $v['vat'] ) {
			$lines[] = __( 'VAT code', 'kontoros-company-lookup' ) . ': ' . $v['vat'];
		}
		if ( $plain_text ) {
			echo "\n" . esc_html( implode( "\n", $lines ) ) . "\n";
			return;
		}
		echo '<p>';
		foreach ( $lines as $i => $line ) {
			echo ( $i ? '<br>' : '' ) . esc_html( $line );
		}
		echo '</p>';
	}
}
