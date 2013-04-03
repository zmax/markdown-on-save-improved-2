<?php
/*
Plugin Name: Markdown on Save Improved 2
Description: Allows you to compose content in Markdown on a per-item basis from WP admin or mobile/3rd-party apps. The markdown version is stored separately, so you can deactivate this plugin and your posts won't spew out Markdown. Based on <a href="http://wordpress.org/extend/plugins/markdown-osi/">Mark Jaquith's plugin</a>. It supports Github Fenced code blocks, and integrated with <a href="http://epiceditor.com/">EpicEditor</a>.
Version: 2.5.4

Author: Starck Lin
Author URI: http://blog.starcklin.com
License: GPL v2
*/

/*
 * Copyright 2012-2013 Starck Lin. GPL v2, of course.
 *
 * This software is forked from the original Markdown on Save improved plugin (c) Matt Wiebe
 * It uses the Markdown Extra and Markdownify libraries. Copyrights and licenses indicated in said libararies.
 *
 *
 * This software is forked from the original Markdown on Save plugin (c) Mark Jaquith
 * It uses the Markdown Extra and Markdownify libraries. Copyrights and licenses indicated in said libararies.
 *
 */

if(!function_exists('get_plugin_data')) {
 	require_once('wp-includes/plugin.php');
}

// Define the default path and URL for the WP Editor plugin
define('EPICEDITOR_PATH', plugin_dir_path( __FILE__ ) . '/epiceditor/' );
define('EPICEDITOR_URL', plugins_url() . '/' . basename(dirname(__FILE__)) . '/epiceditor/' );
define('EPICEDITOR_VERSION', '0.2.5-starck-modified');

class SD_Markdown {

	const PM = '_sd_disable_markdown';
	const MD = '_sd_is_markdown';
	const CONVERT = 'sd_convert_to_markdown';
	const VERSION = '2.5.4';
	const VERSION_OPT = 'mosi-version';

	protected $new_api_post = false;

	protected $parser = false;

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		register_activation_hook( __FILE__, array($this, 'activate') );
	}

	public function init() {

		load_plugin_textdomain( 'markdown-osi', NULL, basename( dirname( __FILE__ ) ) );

		$this->add_post_type_support();

		add_action( 'do_meta_boxes', array( $this, 'do_meta_boxes' ), 20, 2 );
		add_action( 'xmlrpc_call', array($this, 'xmlrpc_actions') );
		add_action( 'load-post.php', array( $this, 'load' ) );
		add_action( 'load-post-new.php', array( $this, 'load' ) );
		add_action( 'xmlrpc_call_success_mw_newPost', array( $this, 'xmlrpc_new_post' ), 10, 2 );

		remove_action( 'the_content', 'wpautop' );

		add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 10, 2 );
		add_filter( 'edit_post_content', array( $this, 'edit_post_content' ), 10, 2 );
		add_filter( 'edit_post_content_filtered', array( $this, 'edit_post_content_filtered' ), 10, 2 );
		add_filter( 'the_content', array($this, 'maybe_default_formatting' ) );

		// Markdown breaks autoembedding by wrapping URLs on their own line in paragraphs
		if ( get_option( 'embed_autourls' ) )
			add_filter( 'the_content', array($this, 'oembed_fixer' ), 8 );

		if ( defined( 'XMLRPC_REQUEST') && XMLRPC_REQUEST )
			$this->maybe_prime_post_data();
      
      	add_action( 'admin_init', array($this, 'epic_plugin_admin_init') );

      	wp_register_script('epic_editor', EPICEDITOR_URL . '/js/epiceditor.js', false, EPICEDITOR_VERSION);
        
	}

	public function epic_plugin_admin_init() {
		wp_enqueue_script( 'epic_editor' );
	}

	protected function add_post_type_support() {
		add_post_type_support( 'post', 'markdown-osi' );
		add_post_type_support( 'page', 'markdown-osi' );
	}

	public function maybe_default_formatting( $content ) {
		if ( ! post_type_supports( get_post_type(), 'markdown-osi' ) || ! $this->is_markdown( get_the_ID() ) )
			$content = wpautop( $content );

		return $content;
	}

	public function xmlrpc_new_post( $post_id, $args ) {
		$this->new_api_post = true;
		remove_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 10, 2 );
		$post = (array) get_post( $post_id );
		$post = $this->wp_insert_post_data( $post, $post );

		wp_update_post( $post );
	}

	public function xmlrpc_actions( $xmlrpc_method ) {
		$make_filterable = array( 'metaWeblog.getRecentPosts', 'wp.getPosts', 'wp.getPages' );

		if ( in_array( $xmlrpc_method, $make_filterable ) )
			add_action( 'parse_query', array($this, 'make_filterable'), 10, 1 );
	}

	// we have to do it early and ghetto like this since metaWeblog.getPost && wp.getPage
	// fire *after* get_post is called in their methods
	public function maybe_prime_post_data() {
		global $HTTP_RAW_POST_DATA;
		require_once( ABSPATH . WPINC . '/class-IXR.php' );
		$message = new IXR_Message( $HTTP_RAW_POST_DATA );
		if ( ! $message->parse() ) {
			unset( $message );
			return;
		}

		$methods_to_prime = array( 'metaWeblog.getPost', 'wp.getPost', 'wp.getPage' );
		if ( ! in_array( $message->methodName, $methods_to_prime ) ) {
			unset( $message );
			return;
		}

		// different ID arg for wp.getPage
		$post_id = ( 'wp.getPage' === $message->methodName ) ? $message->params[1] : array_shift( $message->params );
		$post_id = (int) $post_id;
		// prime the post cache
		if ( $this->is_markdown( $post_id ) ) {
			$post = get_post( $post_id );
			if ( ! empty( $post->post_content_filtered ) )
				$post->post_content = $post->post_content_filtered;
			wp_cache_delete( $post->ID, 'posts' );
			wp_cache_add( $post->ID, $post, 'posts' );
		}
		unset( $message );
	}

	public function make_filterable( $wp_query ) {
		$wp_query->set( 'suppress_filters', false );
		add_action( 'the_posts', array( $this, 'the_posts' ), 10, 2 );
	}

	public function the_posts($posts, $wp_query) {
		foreach ( $posts as $key => $post ) {
			if ( $this->is_markdown($post->ID) )
				$posts[ $key ]->post_content = $posts[ $key ]->post_content_filtered;
		}
		return $posts;
	}

	public function load() {
		if ( ! ( isset( $_GET['post'] ) && ! $this->is_markdown( $_GET['post'] ) ) )
			add_filter( 'user_can_richedit', '__return_false', 99 );
	}

	public function wp_insert_post_data( $data, $postarr ) {
		// run once
		remove_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 10, 2 );

		// checks
		$nonced = ( isset( $_POST['_sd_markdown_nonce'] ) && wp_verify_nonce( $_POST['_sd_markdown_nonce'], 'sd-markdown-save' ) );
		$disable_ticked = ( $nonced && isset( $_POST['sd_disable_markdown'] ) );
		$disable_comment_inserted = ( false !== stripos( $data['post_content'], '<!--no-markdown-->' ) );
		$do_html_to_markdown = ( $nonced && isset($_POST[self::CONVERT]) );
		$id = ( isset( $postarr['ID'] ) ) ? $postarr['ID'] : 0;

		$supports = post_type_supports( $postarr['post_type'], 'markdown-osi' );

		// double check in case this is a new xml-rpc post. Disable couldn't be checked.
		if ( $this->new_api_post )
			$disable_ticked = false;

		// Maybe do HTML --> Markdown
		if ( $do_html_to_markdown )
			$data['post_content'] = $this->html_to_markdown( $data['post_content'] );

		// Make sure markdown processing isn't disabled for this post
		if ( $supports && ! ( $disable_ticked || $disable_comment_inserted ) ) {
          
          	// 將原始內容存至 post_content_filtered
			$data['post_content_filtered'] = $data['post_content'];
          
          	// Do markdown processing
          	$data['post_content'] = $this->process( $data['post_content'], $id );
          
			if ( $id )
				update_post_meta( $id, self::MD, 1 );
		} else {
			$data['post_content_filtered'] = '';
			if ( $id )
				update_post_meta( $id, self::MD, false );
		}

		return $data;
	}

	/**
	 * Fixes oEmbed auto-embedding of single-line URLs
	 *
	 * WP's normal autoembed assumes that there's no <p>'s yet because it runs before wpautop
	 * But, when running Markdown, we have <p>'s already there, including around our single-line URLs
	 */
	public function oembed_fixer( $content ) {
		global $wp_embed;
		return preg_replace_callback( '|^\s*<p>(https?://[^\s"]+)</p>\s*$|im', array( $wp_embed, 'autoembed_callback' ), $content );
	}

	protected function html_to_markdown( $content ) {
		$content = stripslashes( $content );
		$content = wpautop( $content );
		require_once( dirname(__FILE__) . '/markdownify/markdownify_extra.php' );
		$md = new Markdownify_Extra( true );
		$content = $md->parseString( $content );
		return $content;
	}

	protected function process( $content, $id ) {
		$this->maybe_load_markdown();
		// Links with "quoted titles" bork without stripping slashes.
		// Stripslashes would lose desired backslashes. So, regex.
		$content = preg_replace( '/\\\"/', '"', $content );
      
      	// Added by Starck Lin - parsing Github Fenced code blocks
        $content = preg_replace("/\n```([A-z :]+)([^```]+?)\n```/", '<pre class="lang:$1">$2</pre>', $content );
        $content = preg_replace("/\n```([^```]+?)\n```/", '<pre>$1</pre>', $content );
      	// /^ *``` *(\w+)? *\n([^\0]+?)\s*``` *(?:\n+|$)/
        //$content = preg_replace("/\s*```\s*(\w+)?\s*\n([^\0]+?)\s*```\s*(?:\n+|$)/", '<pre class="lang:$1">$2</pre>', $content );
      	
		// convert to Markdown
      	$content = $this->parser->transform( $content );
      
		// reference the post_id to make footnote ids unique
		$content = preg_replace( '/fn(ref)?:/', "fn$1-$id:", $content );
      	
      	return $content;
	}

	protected function maybe_load_markdown() {
		// In case another plugin has included it - hopefully it's compatible
		if ( ! class_exists( 'MarkdownExtra_Parser' ) )
			require_once( dirname( __FILE__ ) . '/markdown-extra/markdown-extra.php' );
		if ( ! $this->parser )
			$this->parser = new MarkdownExtra_Parser;
	}

	public function do_meta_boxes( $type, $context ) {
		// allow disabling for folks who think markdown should always be on.
		if ( defined( 'SD_HIDE_MARKDOWN_BOX') && SD_HIDE_MARKDOWN_BOX )
			return;

		if ( 'side' == $context && in_array( $type, array_keys( get_post_types() ) ) )
			add_meta_box( 'sd-markdown', __( 'Markdown', 'markdown-osi' ), array( $this, 'meta_box' ), $type, 'side', 'high' );
	}

	public function meta_box() {
		global $post;
		$screen = get_current_screen();
		wp_nonce_field( 'sd-markdown-save', '_sd_markdown_nonce', false, true );
		echo '<p><input type="checkbox" name="sd_disable_markdown" id="sd_disable_markdown" value="1" ';
		// we get false positives on new post screens. Do not want.
		if ( 'add' !== $screen->action )
			checked( ! get_post_meta( $post->ID, self::MD, true ) );
		echo ' /> <label for="sd_disable_markdown">' . __( 'Disable Markdown formatting', 'markdown-osi' ) . '</label></p>';
		printf( '<p><label><input type="checkbox" name="%s" /> %s</label></p>', self::CONVERT, __('Convert HTML to Markdown (experimental)', 'markdown-osi') );

      	$url = EPICEDITOR_URL;

      	echo <<<EPIC_EDITOR

<script>

var epic_editor, temp_content;

function toogleEpicEditor() {
	if( epic_editor ) {
		if( epic_editor.is('loaded') ) {
			
			jQuery('#wp-content-media-buttons').show();
			jQuery('#ed_toolbar').show();
			jQuery('#content').show();
			
			epic_editor.unload( function() {
				jQuery('#epiceditor').css('height','0px');
				console.log('epiceditor unloaded.');
			} );

		}else{

			jQuery('#epiceditor').css('height', jQuery('#content').css('height') );
			jQuery('#wp-content-media-buttons').hide();
			jQuery('#ed_toolbar').hide();
			jQuery('#content').hide();
			
			temp_content = jQuery('#content').val();

			epic_editor.load( function(){
				
				console.log(jQuery('#content').val());
				epic_editor.getElement('editor').body.innerText = temp_content;
				temp_content = '';
				epic_editor.reflow();

			} );
		}
	}
}

var epic_container = jQuery('<div id="epiceditor"></div>');

epic_container.insertBefore('#content');

jQuery('document').ready(function(){
	var opts = {
	  container: 'epiceditor',
	  textarea: document.getElementById('content'),
	  textareaClearOnUnload: false,
	  basePath: '${url}',
	  clientSideStorage: true,
	  localStorageName: 'epiceditor',
	  useNativeFullsreen: true,
	  parser: marked,
	  file: {
	    name: 'epiceditor',
	    defaultContent: '',
	    autoSave: 100
	  },
	  theme: {
	    base:'/themes/base/epiceditor.css',
	    preview:'/themes/preview/github.css',
	    editor:'/themes/editor/epic-dark.css'
	  },
	  button: {
	    preview: true,
	    fullscreen: true
	  },
	  focusOnLoad: false,
	  shortcut: {
	    modifier: 18,
	    fullscreen: 70,
	    preview: 80
	  },
	  string: {
	    togglePreview: 'Toggle Preview Mode',
	    toggleEdit: 'Toggle Edit Mode',
	    toggleFullscreen: 'Enter Fullscreen'
	  }
	}

	epic_editor = new EpicEditor(opts);

});

</script>

EPIC_EDITOR;

		printf( '<p><lable><input type="button" value="%s" class="button button-large" onclick="toogleEpicEditor();return true;" /></label></p>', __('Toogle EpicEditor') );

	}

	private function is_markdown( $id ) {
		return (bool) get_post_meta( $id, self::MD, true );
	}

	public function edit_post_content( $content, $id ) {
		if ( $this->is_markdown( $id ) ) {
			$post = get_post( $id );
			if ( $post && ! empty( $post->post_content_filtered ) )
				$content = $post->post_content_filtered;
		}
		return $content;
	}

	public function edit_post_content_filtered( $content, $id ) {
		return $content;
	}

	public function activate() {
		$previous_version = get_option( self::VERSION_OPT, '2.1' );
		// upgrade to new determining of MD
		if ( version_compare( '2.1', $previous_version, '=' ) ) {
			$this->update_schema();
		}
		update_option( self::VERSION_OPT, self::VERSION );
	}

	/**
	* Previously, we only set a meta value for disabling. Now we'll set one regardless. Old options need updating.
	*/
	private function update_schema() {
		global $wpdb;
		// formerly MD-disabled posts get updated to new meta key with false value
		$query = $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_key = %s, meta_value = '' WHERE meta_key = %s ", self::MD, self::PM );
		$wpdb->query( $query );

		// posts with stuff in post_content_filtered get the new meta key with true value
		$ids = $wpdb->get_col( "SELECT ID from {$wpdb->posts} WHERE post_content_filtered != '' " );
		if ( $ids && ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				// reprocess markdown -> we used to strip <p> tags and rely on wpautop
				$post = get_post( $id );
				$post->post_content = $this->process( $post->post_content_filtered, $id );
				wp_update_post( $post );
				update_post_meta( $id, self::MD, 1 );
			}
		}
	}
}

new SD_Markdown;