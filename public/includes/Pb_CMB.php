<?php

/**
 * All the CMB related code.
 *
 * @package   Pte_Bot
 * @author  Mte90 <mte90net@gmail.com>
 * @license   GPL-2.0+
 * @copyright 2014-2015
 * @since    1.0.0
 */
class Pb_CMB {

  /**
   * Initialize CMB2.
   *
   * @since     1.0.0
   */
  public function __construct() {
    $plugin = Pte_Bot::get_instance();
    $this->plugin_slug = $plugin->get_plugin_slug();

    /*
     * CMB 2 for metabox and many other cool things!
     * https://github.com/WebDevStudios/CMB2
     */
    require_once( plugin_dir_path( __FILE__ ) . '/CMB2/init.php' );

    add_action( 'cmb2_init', array( $this, 'ptebot_signup_fields' ) );
    add_action( 'cmb2_after_init', array( $this, 'ptebot_submission' ) );
  }

  /**
   * Register the form and fields for our front-end submission form
   */
  public function ptebot_signup_fields() {
    $cmb = new_cmb2_box( array(
	  'id' => 'ptebot-form',
	  'object_types' => array( 'pte' ),
	  'hookup' => false,
	  'save_fields' => false,
		) );

    $cmb->add_field( array(
	  'name' => __( 'Email', $this->plugin_slug ),
	  'id' => 'user_email',
	  'type' => 'text_email'
    ) );

    $cmb->add_field( array(
	  'name' => 'Country',
	  'id' => 'country',
	  'taxonomy' => 'pte-country',
	  'type' => 'taxonomy_select',
    ) );

    $cmb->add_field( array(
	  'name' => 'Frequency for week',
	  'id' => 'frequency',
	  'taxonomy' => 'pte-frequency',
	  'type' => 'taxonomy_select',
    ) );

    $cmb->add_field( array(
	  'name' => '',
	  'id' => 'author_email',
	  'type' => 'text_email',
    ) );
  }

  /**
   * Gets the front-end-post-form cmb instance
   *
   * @return CMB2 object
   */
  public static function get_cmb2_form() {
    // Use ID of metabox in wds_frontend_form_register
    $metabox_id = 'ptebot-form';

    // Post/object ID is not applicable since we're using this form for submission
    $object_id = 'fake-oject-id';

    // Get CMB2 metabox object
    return cmb2_get_metabox( $metabox_id, $object_id );
  }

  /**
   * Handles form submission on save. Redirects if save is successful, otherwise sets an error message as a cmb property
   *
   * @return void
   */
  public function ptebot_submission() {

    // If no form submission, bail
    if ( empty( $_POST ) || !isset( $_POST[ 'submit-cmb' ], $_POST[ 'object_id' ] ) ) {
	return false;
    }

    // Get CMB2 metabox object
    $cmb = $this->get_cmb2_form();

    $post_data = array();

    // Get our shortcode attributes and set them as our initial post_data args
    if ( isset( $_POST[ 'atts' ] ) ) {
	foreach ( ( array ) $_POST[ 'atts' ] as $key => $value ) {
	  $post_data[ $key ] = sanitize_text_field( $value );
	}
	unset( $_POST[ 'atts' ] );
    }

    // Check security nonce
    if ( !isset( $_POST[ $cmb->nonce() ] ) || !wp_verify_nonce( $_POST[ $cmb->nonce() ], $cmb->nonce() ) ) {
	return $cmb->prop( 'submission_error', new WP_Error( 'security_fail', __( 'Security check failed.' ) ) );
    }

    // Check email2 for spam
    if ( !empty( $_POST[ 'author_email' ] ) ) {
	return $cmb->prop( 'submission_error', new WP_Error( 'post_data_missing', __( 'Please enter your email address.' ) ) );
    }

    // Check title submitted
    if ( empty( $_POST[ 'user_email' ] ) ) {
	return $cmb->prop( 'submission_error', new WP_Error( 'post_data_missing', __( 'The email is mandatory!' ) ) );
    }

    // And that the title is not the default title
    if ( $cmb->get_field( 'user_email' )->default() === $_POST[ 'user_email' ] ) {
	return $cmb->prop( 'submission_error', new WP_Error( 'post_data_missing', __( 'This email already exist, contact the admin.' ) ) );
    }

    /**
     * Fetch sanitized values
     */
    $sanitized_values = $cmb->get_sanitized_values( $_POST );

    // Set our post data arguments
    $post_data[ 'post_title' ] = $sanitized_values[ 'user_email' ];
    unset( $sanitized_values[ 'user_email' ] );

    // Create the new post
    $new_submission_id = wp_insert_post( $post_data, true );

    // If we hit a snag, update the user
    if ( is_wp_error( $new_submission_id ) ) {
	return $cmb->prop( 'submission_error', $new_submission_id );
    }
    // Set the taxonomy
    wp_set_object_terms( $new_submission_id, esc_html( $_POST[ 'frequency' ] ), 'pte-frequency' );
    wp_set_object_terms( $new_submission_id, esc_html( $_POST[ 'country' ] ), 'pte-country' );

    /*
     * Redirect back to the form page with a query variable with the new post ID.
     * This will help double-submissions with browser refreshes
     */
    wp_redirect( esc_url_raw( add_query_arg( 'new_user', $new_submission_id ) ) );
    exit;
  }

}

new Pb_CMB();
