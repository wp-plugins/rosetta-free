<?php
/*
 * Url_Changer class
 * Changes homeurl,siteurl
 * Registrates constants - CURRENT_LANG, DEFAULT_LANG
 */
if( ! class_exists('Url_Changer') ){
	class Url_Changer {

	private $old_url;
	private $use_hosts;
	private $langs;

	function __construct() {
		if ( ! is_admin() )
			add_action( 'rosetta_parameters', array( $this, 'start' ), 1, 2 );
	}

	function start( $langs, $use_hosts ) {
		$this->langs = $langs;
		$this->use_hosts = $use_hosts;

		if ( is_multisite() && defined( 'SUNRISE' ) && defined( 'ROSETTA_MULTISITE_DOMAIN' )  )
			$_SERVER['HTTP_HOST'] = ROSETTA_MULTISITE_DOMAIN;

		$this->look_up_language();
			
		// Fix host name
		if ( $this->use_hosts ) {
			add_filter( 'option_siteurl', array( $this, 'change_siteurl' ) );
			add_filter( 'option_home', array( $this, 'change_siteurl' ) );
			add_filter( 'content_url', array( $this, 'change_contenturl' ) );
			return;
		}

		// Fix links for /lang/ prefix
		if ( ! is_admin() && is_permalinks_enabled() ) {

			add_filter( 'option_home', array( $this, 'add_language_prefix_to_url' ) );
			add_filter( 'content_url', array( $this, 'add_language_prefix_to_url' ) );
            return;
		}

		// Fix content links for ?lang
		if ( false && ! is_admin() && ! is_permalinks_enabled() ) {
			add_filter( 'option_home', array( $this, 'add_language_postfix_to_url' ) );
			add_filter( 'content_url', array( $this, 'add_language_postfix_to_url' ));
			add_filter( 'author_feed_link', array( $this, 'add_language_postfix_to_url'));
			add_filter( 'author_link',	array( $this, 'add_language_postfix_to_url'));
			add_filter( 'author_feed_link', array( $this, 'add_language_postfix_to_url'));
			add_filter( 'day_link',  array( $this, 'add_language_postfix_to_url'));
			add_filter( 'get_comment_author_url_link', array( $this, 'add_language_postfix_to_url'));
			add_filter( 'month_link', array( $this, 'add_language_postfix_to_url'));
			add_filter( 'page_link',	array( $this, 'add_language_postfix_to_url'));
			add_filter( 'post_link',	array( $this, 'add_language_postfix_to_url'));
			add_filter( 'year_link',	array( $this,  'add_language_postfix_to_url'));
			add_filter( 'category_feed_link', array( $this, 'add_language_postfix_to_url'));
			add_filter( 'category_link',	array( $this, 'add_language_postfix_to_url'));
			add_filter( 'tag_link',	array( $this, 'add_language_postfix_to_url'));
			add_filter( 'term_link',	array( $this, 'add_language_postfix_to_url'));
			add_filter( 'the_permalink',	array( $this, 'add_language_postfix_to_url'));
			add_filter( 'feed_link',	array( $this, 'add_language_postfix_to_url'));
			add_filter( 'post_comments_feed_link', array( $this, 'add_language_postfix_to_url'));
			add_filter( 'tag_feed_link',	array( $this, 'add_language_postfix_to_url'));
			add_filter( 'get_pagenum_link', array( $this, 'add_language_postfix_to_url'));

			return;
		}
	}
	function look_up_language() {


		// Language recognition (CURRENT_LANG) for multisite should be in sunrise.php
		if ( is_multisite() )
			return;

		$use_lang = $this->langs['default']['prefix'];

		if ( $this->use_hosts  ):
			foreach ( $this->langs['others'] as $prefix => $lang ) {
				if ( $_SERVER['HTTP_HOST'] == $lang['host'] || $_SERVER['HTTP_HOST'] == 'www.' . $lang['host'] ) {
					$use_lang = $lang['prefix'];
					break;
				}
			}
		elseif ( ! is_permalinks_enabled() ) :
			// Non-permalinks variant.
			if ( isset($_GET['lang']) ) :
				foreach ( $this->langs['others'] as $prefix => $lang ) {
					if ( $_GET['lang'] == $lang['prefix'] ) {	
						$use_lang = $lang['prefix'];
						break;
					}
				}
			endif;
		else: // Permalinks no-hosts variant.
			$uri = $_SERVER['REQUEST_URI'];

			$code = substr( $uri, 0, 4 );
			if ( '/' != substr( $code, -1 ) )
				$code = $code . '/';

			foreach ( $this->langs['others'] as $prefix => $lang ) {
				if ( ! ( false === strpos( $code, '/' . $lang['prefix'] . '/' ) ) ) {
					$use_lang = $lang['prefix'];
					break;
				}
			}

		endif;
		
		define('CURRENT_LANG', $use_lang);	
	}

	/**
	 * Filter for content_url
	 * NO HOSTS
	 * NO PERMALINKS
	 */
	function add_language_postfix_to_url( $value ) {

		$content = strpos($value, '/wp-content/');
		$admin = strpos($value, '/wp-includes/');

		if ( false == $content && false === $admin && DEFAULT_LANG != CURRENT_LANG) {

			if( ! $this->use_hosts && ! is_permalinks_enabled() ) {

				// If lang is in request uri ( like ?lang ), remove it
				$value = str_replace ( '?lang=' . $lang_prefix, '', $value );
				$value = str_replace ( '&amp;lang=' . $lang_prefix, '', $value );
				$value = str_replace ( '&lang=' . $lang_prefix , '', $value );

				// Add ?lang if need.
				$lang_tmp = '';
					if ( false === strpos( $value, '?' ) )
						$lang_tmp = '?lang=' . CURRENT_LANG;
					else
						$lang_tmp = '&amp;lang=' . CURRENT_LANG;

				$value .= $lang_tmp;
			}
		}
		return $value;
	}

	/**
	 * Filter for option_home, content_url
	 * NO HOSTS
	 * PERMALINKS
	 * TODO ensure it works perfect
	 */
	function add_language_prefix_to_url( $value ) {

		$pos = strpos($value, '/wp-content/');
		$admin = strpos($value, '/wp-includes/');

		if ( $pos === false && $admin === false && DEFAULT_LANG != CURRENT_LANG ) {

			if( is_multisite() && ! is_subdomain_install() ) {

				$site = rosetta_get_current_blog();
				if ( $site ) {
					$from =  preg_replace('/\/{2,}/','/',  trim( $_SERVER['HTTP_HOST'] . $site->path, '/' ) );
					$to = preg_replace('/\/{2,}/','/',  $_SERVER['HTTP_HOST'] . $site->path . '/' . CURRENT_LANG );
					$value = str_replace( $from, $to, $value);
				}

			} else  {

				$value = str_replace($_SERVER['HTTP_HOST'], $_SERVER['HTTP_HOST'] . '/' . CURRENT_LANG, $value);
			}
		}
		return $value;
	}

	/**
	 * When use hosts change the siteurl, permalinks or not.
	 */
	function change_siteurl($value) {

		if ($this->langs['default']['prefix'] != CURRENT_LANG){
			$new_url = $this->langs['others'][CURRENT_LANG]['host'];
		}	
		else{
			if( $this->use_hosts ) $new_url = $this->langs['default']['host'];
			else return $value;
		}

		$this->old_url = $value;

		if ( is_ssl() )
			$new_url = 'https://' . $new_url;
		else
			$new_url = 'http://' . $new_url;
		
		return $new_url;
	}

	/**
	 * When use hosts change the contenturl (posts, pages urls), permalinks or not.
	 */
	function change_contenturl( $value ) {

		if ($this->langs['default']['prefix'] != CURRENT_LANG)
			$new_url = $this->langs['others'][CURRENT_LANG]['host'];
		else
			$new_url = $this->langs['default']['host'];

		$value = str_replace($this->old_url, '', $value);
		$value = str_replace('http://', '', $value);
		$value = str_replace('https://', '', $value);

		if (is_ssl())
			$value = 'https://' . $new_url . $value;
		else
			$value = 'http://' . $new_url . $value;
		return $value;
	}

}
}
?>
