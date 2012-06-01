<?php
/*
 * Post_Localizer
 * Manages metaboxes to store language versions of posts
 * Substitude post title,body
 */
if( ! class_exists('Post_Localizer') ){
	class Post_Localizer {

	private $langs;
	private $use_default_lang_values;
	function __construct() {
		add_action('rosetta_parameters', array($this, 'start'), 10, 3);
	}

	function start($langs, $use_hosts, $use_default_lang_values) {
		$this->use_default_lang_values = $use_default_lang_values;
		$this->langs = $langs;
		if (!is_admin()){
			// changes post title and body
			add_filter('the_title', array($this, 'localize_title'), 1, 2);
			add_filter('the_content', array($this, 'localize_body'), 1, 1);
			// don't show posts without title and body

		}
		// metaboxes
		add_action('add_meta_boxes', array($this, 'add_metaboxes'), 100);
		add_action('save_post', array($this, 'save_metaboxes'), 1, 2);

		// adding styles and scripts
		add_action('admin_init', array($this, 'add_styles'));
		add_action('admin_enqueue_scripts', array($this, 'add_scripts'));
		
		//notice messages
		add_action('admin_notices',array($this, 'fields_checker'));
		
	}

	function fields_checker(){
		global $post;
		// is edit post page 
		if( false != strpos($_SERVER['REQUEST_URI'],"post.php") ){ 
			// default lang field
			$notices = array();
			$default_notices = array();
			
			if( '' == $post->post_title )
				$default_notices[] = __('Title');
			
			if( '' == $post->post_content )
				$default_notices[] = __('Content');

			if( 0 != count($default_notices) ) $notices[ $this->langs['default']['name'] ] = $default_notices;
			
			// other languages fields
			foreach($this->langs['others'] as $prefix=>$lang){
				$others_notices = array();
				if( '' == get_post_meta($post->ID, 'title-' . $lang['prefix'], true) )
					$others_notices[] = __('Title');				
				if( '' == get_post_meta($post->ID, 'body-' . $lang['prefix'], true) )
					$others_notices[] = __('Content');
				
				if( 0 != count($others_notices) ) $notices[ $lang['name'] ] = $others_notices;
			}
			// generate message
			if( 0 != count($notices) ){
				foreach( $notices as $lang_name=>$note ){
					$notices_string .= '<p><b>'.$lang_name.'</b>: <i>';
					for( $i = 0; $i < count($note); $i++){
						if( count($note) - 1 == $i) $notices_string .= $note[$i].'</i>';
						else $notices_string .= $note[$i].', ';
					}
					$notices_string .= '</p>';
				}
				echo '<div class="updated"><p>'.__('Some fields are empty:','rosetta-plugin').'</p>'.$notices_string.'</div>';
			}

		}
	}
	
	function localize_title($title, $id) {

		if ( CURRENT_LANG != DEFAULT_LANG ){
			$title_new = get_post_meta( $id, 'title-' . CURRENT_LANG, true );

			if ( '' != $title_new || !$this->use_default_lang_values )
				return $title_new;
		}
		return $title;
	}
	function localize_body($body) {
		global $post;

		if ( CURRENT_LANG != DEFAULT_LANG ){
			$body_new = get_post_meta($post->ID, 'body-' . CURRENT_LANG, true);

			if ('' != $body_new || !$this->use_default_lang_values)
				return $body_new;
		}
		return $body;
	}

	function add_metaboxes() {
		// Searching for custom post types.
		$post_types = get_post_types('', 'names');
		
		// Adding metaboxes for every post type.
		foreach ($this->langs['others'] as $prefix => $lang) {
			foreach ($post_types as $post_type) {
				add_meta_box('post-in-' . $lang['prefix'], $lang['prefix'], array($this, 'show_metabox'), $post_type, 'normal', 'high', array('prefix' => $lang['prefix']));
			}
		}
	}

	function show_metabox($post, $metabox) {
		// getting meta data
		
		$prefix = $metabox['args']['prefix'];
		$body = get_post_meta($post->ID, 'body-' . $prefix, true);
		$title = get_post_meta($post->ID, 'title-' . $prefix, true);
		$post_type = get_post_type($post);
		
		if (get_post_status($post->ID) == 'auto-draft')
			$permalink_html = '<div id="edit-slug-box"><strong></strong><span id="sample-permalink"></span></div>';
		else
			$permalink_html = '<div id="edit-slug-box"><strong>'.__('Permalink:').'</strong><span id="sample-permalink"> ' . get_permalink($post->ID) . '</span></div>';
		if (!post_type_supports($post_type, 'editor'))
			$permalink_html = "";
		$title_html = <<<title_html
		<div class="titlediv">
			<input class="like-default-title" type="text" name="title-$prefix" value="$title"/>	
			$permalink_html
			$post_only_for_this_lang_html
		</div>
title_html;
		echo $title_html;

		if (post_type_supports($post_type, 'editor'))
			wp_editor($body, 'tinymce' . $prefix, array('textarea_name' => 'body-' . $prefix, 'media_buttons' => true, 'tinemce' => true));
	}

	function save_metaboxes($post_id, $post) {

		$post_meta = array();
		// saving post metaboxes - title, body 
		foreach ($this->langs['others'] as $prefix => $lang) {
			$post_meta['title-' . $lang['prefix']] = $_POST['title-' . $lang['prefix']];
			$post_meta['body-' . $lang['prefix']] = $_POST['body-' . $lang['prefix']];
		}
		foreach ($post_meta as $key => $value) {
			//if ('' == $value)
			//	continue;

			if ($post->post_type == 'revision')
				return;
			if (get_post_meta($post->ID, $key, FALSE)) {
				update_post_meta($post->ID, $key, $value);
			} else {
				add_post_meta($post->ID, $key, $value);
			}
			if (!$value)
				delete_post_meta($post->ID, $key);
		}
	}

	function get_indexed_language_list() {

		$result['0'] = array('0' => $this->langs['default']['prefix'], '1' => $this->langs['default']['name']);
		$i = 1;
		foreach ($this->langs['others'] as $prefix => $lang) {
			$result[$i] = array('0' => $lang['prefix'], '1' => $lang['name']);
			$i++;
		}
		return $result;
	}

	function add_styles() {
		wp_enqueue_style('rosetta_styles', Utilities::plugins_url() . 'css/tabs.css');
	}

	function add_scripts() {
		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_script('rosetta_post_tabs', Utilities::plugins_url() . 'js/post-tabs.js');
		wp_localize_script('rosetta_post_tabs', 'post_langs', json_encode(array('langs' => $this->get_indexed_language_list(), 'default_str' => __('Default','rosetta-plugin'))));
	}

}
}
?>
