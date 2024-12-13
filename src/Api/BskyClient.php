<?php
/**
 * Bluesky API Client
 *
 * @package AlfBsky\Api
 */

namespace AlfBsky\Api;

/**
 * BskyClient class for interacting with the Bluesky API
 */
class BskyClient {
	/**
	 * Base URL for the Bluesky API
	 *
	 * @var string
	 */
	private string $api_base_url = 'https://bsky.social/xrpc/';

	/**
	 * Authentication token
	 *
	 * @var string|null
	 */
	private ?string $jwt = null;

	/**
	 * Constructor
	 *
	 * @param string $identifier Bluesky identifier.
	 * @param string $password App password.
	 */
	public function __construct(
		private string $identifier,
		private string $password
	) {}

	/**
	 * Create a post on Bluesky
	 *
	 * @param string $text The text content to post.
	 * @return array Response from the API.
	 * @throws \Exception If authentication fails or post creation fails.
	 */
	public function create_post( string $text ): array {
		if ( ! $this->jwt ) {
			$this->authenticate();
		}

		$response = wp_remote_post(
			$this->api_base_url . 'com.atproto.repo.createRecord',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->jwt,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'collection' => 'app.bsky.feed.post',
						'repo'       => $this->did,
						'record'     => array(
							'text'      => $text,
							'createdAt' => gmdate( 'c' ),
							'$type'     => 'app.bsky.feed.post',
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new \Exception( 'Failed to create post: ' . $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			throw new \Exception( 'Failed to create post: ' . ( $body['message'] ?? 'Unknown error' ) );
		}

		return $body;
	}

	/**
	 * Authenticate with Bluesky
	 *
	 * @throws \Exception If authentication fails.
	 */
	private function authenticate(): void {
		$response = wp_remote_post(
			$this->api_base_url . 'com.atproto.server.createSession',
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'identifier' => $this->identifier,
						'password'   => $this->password,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new \Exception( 'Authentication failed: ' . $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			throw new \Exception( 'Authentication failed: ' . ( $body['message'] ?? 'Unknown error' ) );
		}

		$this->jwt = $body['accessJwt'];
		$this->did = $body['did'];
	}
}
