<?php

/**
 * All the Cron related code.
 *
 * @package   Pte_Bot
 * @author  Mte90 <mte90net@gmail.com>
 * @license   GPL-2.0+
 * @copyright 2014-2015
 * @since    1.0.0
 */
class Pb_Cron {

  /**
   * Initialize CMB2.
   *
   * @since     1.0.0
   */
  public function __construct() {
    $plugin = Pte_Bot::get_instance();
    $this->plugin_slug = $plugin->get_plugin_slug();
    /*
     * Load CronPlus 
     * 
     */
    if ( !class_exists( 'CronPlus' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'CronPlus/cronplus.php' );
    }
    $args = array(
	  'recurrence' => 'daily',
	  'schedule' => 'schedule',
	  'name' => 'ptebot_cron',
	  'cb' => array( $this, 'ptebot_sending' )
    );

    $cronplus = new CronPlus( $args );
    $cronplus->schedule_event();

    //add_action( 'wp_head', array( $this, 'ptebot_sending' ) );
  }

  public function ptebot_sending() {
    $waiting = array();
    $countries = get_terms( 'pte-country', array(
	  'hide_empty' => false,
		) );
    if ( !empty( $countries ) && !is_wp_error( $countries ) ) {
	foreach ( $countries as $term ) {
	  $waiting[ $term->slug ] = $this->download_plugins( $term->slug );
	}
    }

    foreach ( $waiting as $lang => $projects ) {
	$args = array(
	    'post_type' => 'pte',
	    'update_post_meta_cache' => false,
	    'posts_per_page' => -1,
	    'no_found_rows' => true,
	    'tax_query' => array(
		  array(
			'taxonomy' => 'pte-country',
			'field' => 'slug',
			'terms' => $lang,
		  ),
	    )
	);
	$timestamp = time();
	$query = new WP_Query( $args );
	if ( $query->have_posts() ) :
	  while ( $query->have_posts() ): $query->the_post();
	    $frequency = wp_get_post_terms( get_the_ID(), 'pte-frequency' );
	    switch ( $frequency[ 0 ]->slug ) {
		case 1:
		  if ( date( 'D', $timestamp ) === 'Mon' ) {
		    $this->prepare_email( get_the_title(), $projects[ mt_rand( 0, count( $projects ) - 1 ) ] );
		  }
		  break;
		case 2:
		  if ( date( 'D', $timestamp ) === 'Tue' || date( 'D', $timestamp ) === 'Thu' ) {
		    $this->prepare_email( get_the_title(), $projects[ mt_rand( 0, count( $projects ) - 1 ) ] );
		  }
		  break;
		case 3:
		  if ( date( 'D', $timestamp ) === 'Mon' || date( 'D', $timestamp ) === 'Wed' || date( 'D', $timestamp ) === 'Fri' ) {
		    $this->prepare_email( get_the_title(), $projects[ mt_rand( 0, count( $projects ) - 1 ) ] );
		  }
		  break;
	    }
	  endwhile;
	endif;
    }
  }

  public function download_plugins( $slug ) {
    $waiting = wp_remote_get( 'https://translate.wordpress.org/locale/' . $slug . '/default/wp-plugins?filter=strings-waiting-and-fuzzy', array( 'timeout' => 120, 'httpversion' => '1.1' ) );
    if ( is_array( $waiting ) ) {
	$waiting = $waiting[ 'body' ];
	require_once( plugin_dir_path( __FILE__ ) . 'php-selector.php' );
	return select_elements( '.project-name', $waiting );
    }
  }

  public function prepare_email( $email, $random_project ) {
    $link = 'https://translate.wordpress.org' . $random_project[ 'children' ][ 0 ][ 'children' ][ 0 ][ 'attributes' ][ 'href' ];
    $text = __( 'Hi', $this->plugin_slug );
    $text .= ' ' . $email;
    $text .= __( ', there is a plugin that require your attention to review the strings:' . "\n\n", $this->plugin_slug );
    $text .= str_replace( "\n", '', str_replace( "\t", '', $random_project[ 'text' ] ) ) . ' - ' . $link;
    $text .= __( "\n\n" . 'Cheers from your loveable PTE Bot' . "\n", $this->plugin_slug );
    $nonce = $this->non_logged_nonce( 'ptebot-unsub-' . $email );
    $url = get_site_url() . '/?id=' . get_the_ID() . '&email=' . $email . '&_wpnonce=' . $nonce;
    $text .= __( "\n" . 'To unsubscribe ' . $url . "\n", $this->plugin_slug );
    $headers = array('From: PTE Bot <no-reply@mte90.net>');
    wp_mail( get_the_title(), __( 'PTE Bot for you', $this->plugin_slug ), $text, $headers );
  }

  public static function non_logged_nonce( $action = -1 ) {
    $i = wp_nonce_tick();

    return substr( wp_hash( $i . '|' . $action . '|0|', 'nonce' ), -12, 10 );
  }

}

new Pb_Cron();
