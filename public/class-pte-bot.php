<?php

/**
 * PTE bot
 *
 * @package   Pte_Bot
 * @author    Daniele Mte90 Scasciafratte <mte90net@gmail.com>
 * @license   GPL-2.0+
 * @link      http://mte90.net
 * @copyright 2016 GPL 3
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-pte-bot-admin.php`
 *
 * @package Pte_Bot
 * @author  Daniele Mte90 Scasciafratte <mte90net@gmail.com>
 */
class Pte_Bot {

  /**
   * Plugin version, used for cache-busting of style and script file references.
   *
   * @since   1.0.0
   *
   * @var     string
   */
  const VERSION = '1.0.0';

  /**
   * Unique identifier for your plugin.
   *
   *
   * The variable name is used as the text domain when internationalizing strings
   * of text. Its value should match the Text Domain file header in the main
   * plugin file.
   *
   * @var      string
   *
   * @since    1.0.0
   */
  protected static $plugin_slug = 'pte-bot';

  /**
   * Instance of this class.
   *
   * @var      object
   *
   * @since    1.0.0
   */
  protected static $instance = null;

  /**
   * Initialize the plugin by setting localization and loading public scripts
   * and styles.
   *
   * @since     1.0.0
   */
  private function __construct() {

    register_via_cpt_core(
		array( __( 'PTE', $this->get_plugin_slug() ), __( 'PTEs', $this->get_plugin_slug() ), 'pte' ), array(
	  'taxonomies' => array( 'pte-country' ),
	  'supports' => array( 'title' )
		)
    );

    register_via_taxonomy_core(
		array( __( 'Country', $this->get_plugin_slug() ), __( 'Countries', $this->get_plugin_slug() ), 'pte-country' ), array(
	  'public' => false
		), array( 'pte' )
    );
    register_via_taxonomy_core(
		array( __( 'Frequency', $this->get_plugin_slug() ), __( 'Frequency', $this->get_plugin_slug() ), 'pte-frequency' ), array(
	  'public' => false
		), array( 'pte' )
    );

    /*
     * Load CMB
     */
    require_once( plugin_dir_path( __FILE__ ) . 'includes/Pb_CMB.php' );
    require_once( plugin_dir_path( __FILE__ ) . 'includes/Pb_Cron.php' );

    add_shortcode( 'ptebot-signup', array( $this, 'wds_do_frontend_form_submission_shortcode' ) );
  }

  /**
   * Return the plugin slug.
   *
   * @since    1.0.0
   *
   * @return    Plugin slug variable.
   */
  public function get_plugin_slug() {
    return self::$plugin_slug;
  }

  /**
   * Return an instance of this class.
   *
   * @since     1.0.0
   *
   * @return    object    A single instance of this class.
   */
  public static function get_instance() {
    // If the single instance hasn't been set, set it now.
    if ( null == self::$instance ) {
	self::$instance = new self;
    }

    return self::$instance;
  }

  /**
   * Handle the cmb-frontend-form shortcode
   *
   * @param  array  $atts Array of shortcode attributes
   * @return string       Form html
   */
  public function wds_do_frontend_form_submission_shortcode( $atts = array() ) {

    // Get CMB2 metabox object
    $cmb = Pb_CMB::get_cmb2_form();

    // Get $cmb object_types
    $post_types = $cmb->prop( 'object_types' );

    // Current user
    $user_id = get_current_user_id();

    // Parse attributes
    $atts = shortcode_atts( array(
	  'post_author' => $user_id ? $user_id : 1, // Current user, or admin
	  'post_status' => 'publish',
	  'post_type' => reset( $post_types ), // Only use first object_type in array
		), $atts, 'ptebot-form' );

    /*
     * Let's add these attributes as hidden fields to our cmb form
     * so that they will be passed through to our form submission
     */
    foreach ( $atts as $key => $value ) {
	$cmb->add_hidden_field( array(
	    'field_args' => array(
		  'id' => "atts[$key]",
		  'type' => 'hidden',
		  'default' => $value,
	    ),
	) );
    }

    // Initiate our output variable
    $output = '';

    // Get any submission errors
    if ( ( $error = $cmb->prop( 'submission_error' ) ) && is_wp_error( $error ) ) {
	// If there was an error with the submission, add it to our ouput.
	$output .= '<h3>' . sprintf( __( 'There was an error in the submission: %s', $this->get_plugin_slug() ), '<strong>' . $error->get_error_message() . '</strong>' ) . '</h3>';
    }

    // If the post was submitted successfully, notify the user.
    if ( isset( $_GET[ 'new_user' ] ) && ( $post = get_post( absint( $_GET[ 'new_user' ] ) ) ) ) {
	// Add notice of submission to our output
	$output .= '<h3>' . __( 'Thank you! You are in the PTE Bot system now!' ) . '</h3>';
    }

    // Get our form
    $output .= cmb2_get_metabox_form( $cmb, 'fake-oject-id', array( 'save_button' => __( 'Submit Submission', $this->get_plugin_slug() ) ) );

    return $output;
  }

}
