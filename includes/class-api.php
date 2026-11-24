<?php
/**
 * Server-side proxy to the kontoros.lt search API.
 *
 * The browser calls /wp-json/kontoros/v1/search on the shop itself, so the API key stays
 * private, results are cached in transients, and visitors are throttled per IP.
 */

defined( 'ABSPATH' ) || exit;

class Kontoros_CL_Api {

	const ENDPOINT  = 'https://www.kontoros.lt/api/v1/companies/search';
	const CACHE_TTL = 600;   // seconds
	const PER_IP    = 60;    // lookups per visitor per 5 minutes

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		register_rest_route(
			'kontoros/v1',
			'/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'search' ),
				'permission_callback' => '__return_true', // public, read-only lookup used on checkout
				'args'                => array(
					'q' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	public static function search( WP_REST_Request $request ) {
		if ( ! Kontoros_CL_Settings::enabled( 'enabled' ) ) {
			return new WP_REST_Response( array( 'data' => array() ), 200 );
		}
		$q = trim( mb_substr( (string) $request->get_param( 'q' ), 0, 100 ) );
		if ( mb_strlen( $q ) < 2 ) {
			return new WP_REST_Response( array( 'data' => array() ), 200 );
		}

		$active = Kontoros_CL_Settings::enabled( 'active_only' );
		$key    = 'kontoros_cl_' . md5( mb_strtolower( $q ) . '|' . (int) $active );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return self::respond( $cached );
		}

		if ( self::throttled() ) {
			return new WP_REST_Response( array( 'data' => array(), 'error' => 'rate_limited' ), 429 );
		}

		$args = array(
			'timeout' => 5,
			'headers' => array( 'Accept' => 'application/json' ),
			'user-agent' => 'KontorosCompanyLookup/' . KONTOROS_CL_VERSION . '; ' . home_url( '/' ),
		);
		$api_key = trim( (string) Kontoros_CL_Settings::get( 'api_key' ) );
		if ( '' !== $api_key ) {
			$args['headers']['X-Api-Key'] = $api_key;
		}
		$url      = add_query_arg(
			array(
				'q'      => rawurlencode( $q ),
				'limit'  => 8,
				'active' => $active ? 1 : 0,
			),
			self::ENDPOINT
		);
		$response = wp_remote_get( $url, $args );
		$code     = wp_remote_retrieve_response_code( $response );

		if ( is_wp_error( $response ) || 200 !== $code ) {
			// Fail quietly: the customer can always type the details by hand.
			return new WP_REST_Response( array( 'data' => array(), 'error' => 429 === $code ? 'rate_limited' : 'unavailable' ), 200 );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$data = array();
		foreach ( isset( $body['data'] ) && is_array( $body['data'] ) ? $body['data'] : array() as $c ) {
			$data[] = self::map( $c );
		}
		set_transient( $key, $data, self::CACHE_TTL );

		return self::respond( $data );
	}

	/** Only the fields the checkout needs, already in the shop's preferred format. */
	private static function map( array $c ) {
		$addr = isset( $c['address'] ) && is_array( $c['address'] ) ? $c['address'] : array();
		$name = 'official' === Kontoros_CL_Settings::get( 'name_format' ) ? $c['name'] : ( isset( $c['display_name'] ) ? $c['display_name'] : $c['name'] );

		return array(
			'code'      => (string) $c['code'],
			'name'      => (string) $name,
			'vat_code'  => isset( $c['vat_code'] ) ? (string) $c['vat_code'] : '',
			'active'    => ! empty( $c['active'] ),
			'address'   => isset( $addr['full'] ) ? (string) $addr['full'] : '',
			'address_1' => trim( ( isset( $addr['street'] ) ? $addr['street'] : '' ) . ' ' . ( isset( $addr['house'] ) ? $addr['house'] : '' ) ),
			'city'      => isset( $addr['city'] ) ? (string) $addr['city'] : '',
			'state'     => isset( $addr['county'] ) ? (string) $addr['county'] : '', // WooCommerce "Apskritis" (free text for LT)
			// Digits only: WooCommerce saves "LT-48426" as "LT48426", which the block checkout then rejects.
			'postcode'  => isset( $addr['postcode'] ) ? preg_replace( '/\D/', '', (string) $addr['postcode'] ) : '',
		);
	}

	private static function respond( array $data ) {
		$res = new WP_REST_Response( array( 'data' => $data ), 200 );
		$res->header( 'Cache-Control', 'private, max-age=300' );
		return $res;
	}

	/** Simple per-IP counter so one visitor can't use up the shop's API quota. */
	private static function throttled() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'kontoros_cl_ip_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= self::PER_IP ) {
			return true;
		}
		set_transient( $key, $n + 1, 5 * MINUTE_IN_SECONDS );
		return false;
	}
}
