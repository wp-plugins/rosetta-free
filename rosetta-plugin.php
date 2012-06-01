<?php
/*
	Plugin Name: Rosetta plugin free
	Plugin URI:
	Description: Add language versions to all type of posts. It's free version with some limitations.
	Version: 1.0
	Author: Frumatic
	Author URI:
 */

if( !class_exists('Rosetta_Plugin') ){
	
	include_once 'class-utilities.php';
	include_once 'class-url-changer.php';
	include_once 'class-admin-menus.php';
	include_once 'class-post-localizer.php';
	include_once 'class-bloginfo-localizer.php';
	include_once 'class-theme-menus-localizer.php';
	include_once 'class-rosetta-language-widget.php';
	include_once 'class-force-secondary-hosts-login.php';

	/*
	 * Localization class
	 * 
	 * manages plugin options
	 * controls other classes 
	 */	

	class Rosetta_Plugin {
		private $langs;
		private $use_hosts;
		private $use_default_lang_values;

		private $custom_localizer;
		private $thumbnail_localizer;
		function __construct() {
			load_plugin_textdomain('rosetta-plugin',false, dirname( plugin_basename( __FILE__ ) ) .'/lang/');

			if( ! is_multisite() ){
				$this->register_options();
				$this->load_options();

				if ( ! defined('DEFAULT_LANG') ) define('DEFAULT_LANG', $this->langs['default']['prefix']);

				new Url_Changer();
				new Post_Localizer();
				new Admin_Menus();
				new Bloginfo_Localizer();

				if ( $this->use_hosts || is_permalinks_enabled() )
					add_action( 'widgets_init', create_function( '', 'register_widget( "Rosetta_Language_Widget" );' ));

				do_action('rosetta_parameters', $this->langs, $this->use_hosts, false);
				do_action('rosetta_lang_menu', $this->get_lang_menu());
			}else{
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			}
		}
		function register_options() {
			$default_options = array(
				'langs' => array(
					'default' => array('prefix' => 'en', 'name' => 'English', 'host' => $_SERVER['HTTP_HOST']),
					'others' => array(),
				),
				'use_hosts' => false,
				'use_default_lang_values' => false,
			);

			if( is_multisite() )
				add_blog_option(get_current_blog_id(), 'rosetta-free-options', $default_options, '', 'yes');
			else
				add_option('rosetta-free-options', $default_options, '', 'yes');
		}
		function admin_notices(){
			echo '<div id="message" class="error"><p><strong>'.__( 'Rosetta-free is not avalable on multisite. Learn more about full version on ', 'rosetta-plugin' ).'<a href="http://store.theme.fm/plugins/rosetta/">theme.fm</a></strong></p></div>';
		}
		function load_options() {

			$options = array();
			if( is_multisite() ) {
				if( false == strpos($_SERVER['REQUEST_URI'],"site-settings.php") )
					$blog_id = get_current_blog_id();
				else
					$blog_id = $_GET['id'];

				$options = get_blog_option($blog_id, 'rosetta-free-options');
			}else{

				$options = get_option('rosetta-free-options');
			}

			$this->langs = $options['langs'];
			$this->use_hosts = $options['use_hosts'];
			$this->use_default_lang_values = $options['use_default_lang_values'];
			if (!isset($this->langs))
				$this->langs = array(
					'default' => array('prefix' => 'en', 'name' => 'English', 'host' => $_SERVER['HTTP_HOST']),
					'others' => array(),
				);
			if (!isset($this->use_hosts))
				$this->use_hosts = false;
			if(!isset($this->use_default_lang_values))
				$this->use_default_lang_values = false;
		}

		function get_language_list() {
			$lang_list = array();
			foreach ($this->langs['others'] as $prefix => $lang) {
				$lang_list[$prefix] = $lang['host'];
			}
			$lang_list[$this->langs['default']['prefix']] = $this->langs['default']['host'];
			return $lang_list;
		}
		function get_permalink( $host, $lang_prefix ) {

			$uri = $_SERVER['REQUEST_URI'];

			$path = '';
			// If multisite then remove blogname from $uri
			if ( is_multisite() && ! is_subdomain_install() ) {
				$site = rosetta_get_current_blog();

				$path = $site->path;
				if ( substr( $uri, 0, strlen( $site->path ) ) == $site->path )
					$uri = '/' . substr( $uri, strlen( $site->path ), strlen( $uri ) - strlen( $site->path ) );
			}

			// Remove lang prefix
			$lang_list = $this->get_language_list();
			$code = '/' . substr( $uri, 0, 4 ) . '/';
			$code = preg_replace('/\/{2,}/','/', $code);
			foreach ( $lang_list as $prefix => $lang ) {
				if ( '/' . $prefix . '/' == $code )
					$uri = substr( $uri, 4, strlen( $uri) - 4);
			}

			// If lang is in request uri ( like ?lang ), remove it
			$uri = str_replace ( '?lang=' . $lang_prefix, '', $uri );
			$uri = str_replace ( '&amp;lang=' . $lang_prefix, '', $uri );
			$uri = str_replace ( '&lang=' . $lang_prefix , '', $uri );

			// Add ?lang if need.
			$lang_tmp = '';
			if ( false && ! is_permalinks_enabled() && ! $this->use_hosts ) {
				if ( false === strpos( $uri, '?' ) )
					$lang_tmp = '?lang=' . $lang_prefix;
				else
					$lang_tmp = '&amp;lang=' . $lang_prefix;
			}

			if ( is_permalinks_enabled() && ! $this->use_hosts )
				$lang_code = $lang_prefix;
			else
				$lang_code = '';

			if ( DEFAULT_LANG != $lang_prefix )
				$href = $host . '/' . $path . '/' . $lang_code  . '/' . $uri . $lang_tmp;
			else
				$href = $host . '/' . $path . '/' . $uri;

			$href = preg_replace('/\/{2,}/','/', $href);

			return apply_filters( 'rosetta_get_permalink', $href, $lang_prefix );
		}
		function get_lang_menu(){
			$lang_menu = array();

			// Set menu item for default language.
			$href = $this->get_permalink( ( $this->use_hosts ) ? $this->langs['default']['host'] : $_SERVER['HTTP_HOST'], $this->langs['default']['prefix'] );
			$lang_menu[ $this->langs['default']['prefix'] ] = array(
					'title' => $this->langs['default']['name'],
					'href' => $href,
			);

			// Set menu items for additional languages
			foreach ($this->langs['others'] as $prefix => $lang) {

				$href = $this->get_permalink( ( $this->use_hosts ) ? $lang['host'] : $_SERVER['HTTP_HOST'], $lang['prefix'] );

				$lang_menu[ $lang['prefix'] ] = array(
					'title' => $lang['name'],
					'href' => $href,
				);
			}
			return $lang_menu; 
		}
		function is_enabled() {
			return true;
		}
	}

	add_action('after_setup_theme', create_function('', 'global $localization; $localization = new Rosetta_Plugin();'));
}


