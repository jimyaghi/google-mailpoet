<?php
/**
 * Plugin Name: Google Mailpoet
 * Plugin URI:  https://github.com/jimyaghi/google-mailpoet
 * Description: This plugin needs to reside in the mu-plugins directory of Wordpress and enables you to add leads to mailpoet
 * when they've been created by Google Lead Form extensions
 *
 * Author:      YaghiLabs
 * Author URI:  https://jimyaghi.com
 * Version:     2.5.14
 */

namespace YL {

	/**
	 * بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيم
	 *
	 * Created by Jim Yaghi
	 * Date: 2021-10-31
	 * Time: 11:16
	 *
	 */


	// Basic security, prevents file from being loaded directly.
	defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );
	require_once( __DIR__ . '/google-mailpoet/GoogleMailpoetPlugin.php' );
	$GLOBALS['google_mailpoet_plugin'] = GoogleMailpoetPlugin::getInstance();
}


