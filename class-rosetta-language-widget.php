<?php

/*
 * Rosetta_Language_Widget
 * output language menu
 */
if( ! class_exists('Rosetta_Language_Widget') ){
	class Rosetta_Language_Widget extends WP_Widget{
	private $lang_menu; 
	function __construct() {
		parent::__construct(
	 		'lang_widget', // Base ID
			__( 'Rosetta language menu', 'rosetta-plugin' ), // Name
			array( 'description' => __( 'Shows the language selection menu', 'rosetta-plugin' ), ) // Args
		);
		$this->load_lang_options();
	}

	function load_lang_options() {

		global $localization;
		if(isset($localization)) 
			$this->lang_menu = $localization->get_lang_menu();
	}
	function get_language_list_html() {
		global $post;
		if(isset($post)){
			$lang_list_html = '<ul>';
			if(isset($this->lang_menu)){
				foreach ($this->lang_menu as $prefix=>$item){
					$href = is_ssl() ? 'https://'.$item['href'] : 'http://'.$item['href'];
					$lang_list_html.= '<li><a href="'.$href.'">'.$item['title'].'</a></li>';
				}
			}		
			$lang_list_html .= '</ul>';
			return $lang_list_html;
		}	
		return '';
	}
	function get_permalink( $id, $lang_prefix){

		if( $this->use_hosts && is_permalinks_enabled() ) {
			$new_host = $_SERVER['HTTP_HOST'];
			if( $lang_prefix == $this->langs['default']['prefix'] ){
				$new_host = $this->langs['default']['host'];
			}else{
				if(isset($this->langs['others'][$lang_prefix])){
					$new_host = $this->langs['others'][$lang_prefix]['host'];
				}else{
					return get_permalink($id);
				}
			}
			if (is_ssl())
				return 'https://' . $new_host.$_SERVER['REQUEST_URI'];
			else
				return 'http://' . $new_host.$_SERVER['REQUEST_URI'];
			

		} elseif ( ! $this->use_hosts && ! is_permalinks_enabled() ) { // no permalinks
			
			$lang_tmp = '';
			$uri = $_SERVER['REQUEST_URI'];
			$uri = str_replace ( '?lang=' . $_REQUEST['lang'], '', $uri );
			$uri = str_replace ( '&amp;lang=' . $_REQUEST['lang'], '', $uri );
			$uri = str_replace ( '&lang=' . $_REQUEST['lang'], '', $uri );

			if ( DEFAULT_LANG != $lang_prefix  ) {

				if ( false === strpos( $uri, '?' ) )
					$lang_tmp = '?lang=' . $lang_prefix;
				else
					$lang_tmp = '&amp;lang=' . $lang_prefix;

			}

			if (is_ssl())
				return 'https://' . $_SERVER['HTTP_HOST'] .  $uri . $lang_tmp;
			else
				return 'http://' . $_SERVER['HTTP_HOST'] . $uri . $lang_tmp;

		} else { // permalinks with no hosts
			
			if ( DEFAULT_LANG != $lang_prefix  ){
				if (is_ssl())		
					return 'https://' . $_SERVER['HTTP_HOST']. '/' . $lang_prefix . $_SERVER['REQUEST_URI'];
				else
					return 'http://' . $_SERVER['HTTP_HOST']. '/' . $lang_prefix . $_SERVER['REQUEST_URI'];
			}else{
				if (is_ssl())		
					return 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				else
					return 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			}
		}				
		return get_permalink( $id );
	}
	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;
		echo '<h3 class="widget-title">'.__( 'Language', 'rosetta-plugin' ). '</h3>';
		echo $this->get_language_list_html();
		echo $after_widget;
	}
	
}
}
?>
