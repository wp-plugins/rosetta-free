<?php
// Correct plugin path for symlinks case
if( ! class_exists('Utilities') ){
	class Utilities {
		public static function plugins_url() {	
			return trailingslashit( plugins_url( basename( dirname( __FILE__ ) ) ) );
		}
		public static function is_permalinks_enabled() {

			$permalink_page = parse_url( admin_url('/') . 'options-permalink.php' );
			if ( isset( $_POST['_wp_http_referer'] ) &&  $permalink_page['path'] == $_POST['_wp_http_referer'] && isset( $_POST['permalink_structure'] ) ) {
				if ( '' !=  $_POST['permalink_structure'] )
					return true;
				else
					return false;
			}

			if( is_multisite() )
				return '' != get_blog_option( get_current_blog_id(), 'permalink_structure' );
			else
				return '' !=  get_option( 'permalink_structure' );
		}

	}
	if ( ! function_exists( 'is_permalinks_enabled' ) ) {
		function is_permalinks_enabled() {
			return Utilities::is_permalinks_enabled();
		}
	}
}
?>
