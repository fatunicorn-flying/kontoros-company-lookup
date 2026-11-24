<?php
/**
 * Settings tab: WooCommerce → Settings → Kontoros.
 */

defined( 'ABSPATH' ) || exit;

class Kontoros_CL_Settings {

	const TAB = 'kontoros';

	public static function init() {
		add_filter( 'woocommerce_settings_tabs_array', array( __CLASS__, 'add_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_' . self::TAB, array( __CLASS__, 'output' ) );
		add_action( 'woocommerce_update_options_' . self::TAB, array( __CLASS__, 'save' ) );
	}

	/** Option value with default. */
	public static function get( $key ) {
		$defaults = array(
			'enabled'      => 'yes',
			'api_key'      => '',
			'active_only'  => 'yes',
			'add_fields'   => 'yes',
			'name_format'  => 'display',
			'fill_address' => 'yes',
			'attribution'  => 'yes',
			'show_company' => 'yes',
		);
		return get_option( 'kontoros_cl_' . $key, isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' );
	}

	public static function enabled( $key ) {
		return 'yes' === self::get( $key );
	}

	public static function add_tab( $tabs ) {
		$tabs[ self::TAB ] = __( 'Kontoros', 'kontoros-company-lookup' );
		return $tabs;
	}

	public static function fields() {
		return array(
			array(
				'title' => __( 'Company lookup', 'kontoros-company-lookup' ),
				'type'  => 'title',
				/* translators: %s: link to kontoros.lt API documentation */
				'desc'  => sprintf( __( 'Customers start typing a company name or code in the Company field on checkout and pick it from the list; the company code, VAT code and address are filled in automatically. Data comes from the free %s.', 'kontoros-company-lookup' ), '<a href="https://www.kontoros.lt/api" target="_blank" rel="noopener">kontoros.lt API</a>' ),
				'id'    => 'kontoros_cl_section',
			),
			array(
				'title'   => __( 'Enable', 'kontoros-company-lookup' ),
				'type'    => 'checkbox',
				'desc'    => __( 'Enable company autocomplete on checkout', 'kontoros-company-lookup' ),
				'id'      => 'kontoros_cl_enabled',
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Company code and VAT fields', 'kontoros-company-lookup' ),
				'type'    => 'checkbox',
				'desc'    => __( 'Add "Company code" and "VAT code" fields to the billing address', 'kontoros-company-lookup' ),
				'desc_tip' => __( 'Turn off if another plugin already adds these fields. They are saved to the order and shown in the admin and in emails.', 'kontoros-company-lookup' ),
				'id'      => 'kontoros_cl_add_fields',
				'default' => 'yes',
			),
			array(
				'title'    => __( 'Company field', 'kontoros-company-lookup' ),
				'type'     => 'checkbox',
				'desc'     => __( 'Show the Company field on checkout if it is hidden', 'kontoros-company-lookup' ),
				'desc_tip' => __( 'WooCommerce hides the Company field by default and then discards the company name. When on, a hidden Company field is shown as optional. A field you set to Required stays required.', 'kontoros-company-lookup' ),
				'id'       => 'kontoros_cl_show_company',
				'default'  => 'yes',
			),
			array(
				'title'   => __( 'Company name', 'kontoros-company-lookup' ),
				'type'    => 'select',
				'id'      => 'kontoros_cl_name_format',
				'default' => 'display',
				'options' => array(
					'display'  => __( 'Short form (UAB "Jungtis")', 'kontoros-company-lookup' ),
					'official' => __( 'Official register name (Uždaroji akcinė bendrovė "Jungtis")', 'kontoros-company-lookup' ),
				),
			),
			array(
				'title'   => __( 'Address', 'kontoros-company-lookup' ),
				'type'    => 'checkbox',
				'desc'    => __( 'Also fill street, city, county, postcode and country with the registered address', 'kontoros-company-lookup' ),
				'id'      => 'kontoros_cl_fill_address',
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Active companies only', 'kontoros-company-lookup' ),
				'type'    => 'checkbox',
				'desc'    => __( 'Hide deregistered and liquidated companies', 'kontoros-company-lookup' ),
				'id'      => 'kontoros_cl_active_only',
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Data source credit', 'kontoros-company-lookup' ),
				'type'    => 'checkbox',
				'desc'    => __( 'Show a small "Data: kontoros.lt" line under the suggestions', 'kontoros-company-lookup' ),
				'id'      => 'kontoros_cl_attribution',
				'default' => 'yes',
			),
			array(
				'title'    => __( 'API key (optional)', 'kontoros-company-lookup' ),
				'type'     => 'text',
				'id'       => 'kontoros_cl_api_key',
				'default'  => '',
				'desc_tip' => __( 'Not required. Without a key the shop may make 100 lookups per 5 minutes and 1000 per day; results are cached for 10 minutes. Contact kontoros.lt for a key with higher limits.', 'kontoros-company-lookup' ),
				'placeholder' => 'kt_…',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'kontoros_cl_section',
			),
		);
	}

	public static function output() {
		woocommerce_admin_fields( self::fields() );
	}

	public static function save() {
		woocommerce_update_options( self::fields() );
	}
}
