<?php defined('ABSPATH') || die(-1);

/**
 * @package Pinned Pages
 * @copyright BC Libraries Coop 2013
 *
 **/
/**
 * Plugin Name: Coop Pinned Pages
 * Description: Mark existing pages as "pinned" - prohibits some behaviours, locks item into the menu. Install as MUST USE.
 * Author: Erik Stainsby, Roaring Sky Software
 * Author URI: http://wp.roaringsky.ca/plugins/coop-pinned-pages
 * Version: 0.2.0
 **/


if ( ! class_exists( 'CoopPinnedPages' )) :
	
class CoopPinnedPages {

	var $slug = 'pinned_page';

	public function __construct() {
	
		add_action( 'init', array( &$this, '_init' ));
	}

	public function _init() {
				
		if( is_admin()) {
		
			add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_styles_scripts' ));
			
			add_action( 'add_meta_boxes_page', array(&$this, 'add_pinned_page_meta_box'));
			add_action( 'add_meta_boxes_page', array(&$this, 'modify_post_editor'));
			
			add_action( 'quick_edit_custom_box', array(&$this,'display_pinned_page_quickedit'), 10, 2 );
			add_action( 'save_post', array( &$this, 'save_post_pinned_page_metadata' ));
			add_action( 'save_post', array( &$this, 'save_metabox_postdata' ));
			add_filter( 'manage_pages_columns', array(&$this,'add_pinned_column_definition'),10, 1 );
			add_action( 'manage_pages_custom_column', array(&$this,'add_pinned_column_data'), 10, 2 );
			
			// support for quick_edit behaviour
			add_action( 'admin_footer-edit.php', array(&$this,'admin_load_footer_script'),11);
			add_action( 'admin_footer-post.php', array(&$this,'admin_load_footer_script'),11);
			
		//	add_action( 'wp_ajax_coop-save-pp-change', array( &$this, 'pp_admin_save_changes'));
		}
	}
	
		
	public function admin_enqueue_styles_scripts($hook) {
	
		self::coop_usermeta_script();
	
		wp_register_script( 'coop-pp-admin-footer', plugins_url('js/pinned-pages-admin-foot.js', __FILE__),array('jquery'));
		wp_register_script( 'coop-pp-admin-js', plugins_url( '/js/pinned-pages-admin.js',__FILE__), array('jquery'));
		wp_register_style( 'coop-pp-admin', plugins_url( '/css/pinned-pages-admin.css', __FILE__ ), false );
		
		wp_enqueue_style( 'coop-pp-admin' );
		wp_enqueue_script( 'coop-pp-admin-js' );
		wp_enqueue_script( 'coop-pp-admin-footer' );
	}
	
	public function admin_load_footer_script($hook) {
	
		error_log( __FUNCTION__ );
		
		echo '<script id="coop-pinned-footer" type="text/javascript">';
		echo '    jQuery().ready(function() { window.coop_pinned_page_lockup = jQuery().cooppplockup()}); ';
		echo '</script>';		 
	}
	
	public function coop_usermeta_script() {
		$role = self::current_role();
		echo '<script id="coop-usermeta" type="text/javascript">window.user_role = "'. $role .'";</script>';
	}
	
	public function current_role() {
		global $wp_roles;
		$user = wp_get_current_user();	
		return $user->roles[0];
	}
	
		
	public function add_pinned_page_meta_box( $post ) {
		
		// Super Admin can modify the pinned-state of a page
		if( current_user_can( 'manage_network' )) {
			add_meta_box( 'coop-pp-metabox','Pin Page',array(&$this,'pinned_page_inner_custom_box'),'page' );
		}
		// other users cannot modify, but get informed on pinned-ness
		else {
			$m = get_post_meta($post->ID,$this->slug,true);
			if( !empty($m)) {
				// is pinned
				add_meta_box( 'coop-pp-infobox','Pinned Page',array(&$this,'pinned_page_inner_infobox'),'page' );
			}
		}
	}
	
	
	public function pinned_page_inner_infobox( $post ) {
		/**
		*	This information box applies for non-Super Admin users
		*	to tell them when a page is locked (pinned).
		**/
		/**
		*	Do we have a pinned flag set on the current page ?
		**/	
		
		printf('<label for="%s">%s</label> ',$this->slug,'This page is locked in position');
		printf('<p>%s</p>', "This page is a required part of the website architecture. ");
		
		$value = get_post_meta( $post->ID, $this->slug, true );
		// add a hidden input with the checked attribute set or not, indicating if the current post is pinned or not
		printf( '<input type="hidden" id="%s-checkbox" name="%s" value="%s">',$this->slug,$this->slug,(($value>0)?'checked':''));
		
		// Site Manager may change the content, but not title or parent
		if( current_user_can('manage_local_site')) {				
			echo "<p>You may edit the content of the page as you wish. ";
			echo "You cannot change the title or change the parent page of a pinned page.</p>";
		}
	}
	
	public function pinned_page_inner_custom_box( $post ) {
	
		wp_nonce_field( plugin_basename( __FILE__ ), $this->slug.'-nonce' );
		
		$value = get_post_meta( $post->ID, $this->slug, true );
		
		printf( '<input type="checkbox" id="%s-checkbox" name="%s" %s/>&nbsp;&nbsp;',$this->slug,$this->slug,(($value>0)?' checked="checked"':''));
		printf('<label for="%s">%s</label> ',$this->slug,'Pin this page');
		
		printf('<p>%s</p>', "Checking this box will lock this item in the menu, and restrict changing the Title to the roles of Admin/Super Admin");
		
	}
		
	
	public function add_pinned_column_definition( $columns ) {
		
		$my_custom_cols = array( $this->slug=> 'Pinned' );
	
		$columns = array_merge( $columns, $my_custom_cols );
		
		/** How to remove a Comments column **/
			unset(
		//		$columns['author'],
				$columns['comments']
			);
		
		return $columns;
	}


	public function add_pinned_column_data( $column, $post_id ) {
	
		if( $this->slug == $column ) {	
			$m = get_post_meta($post_id,$this->slug,true);
			if( !empty($m) ) {
				echo 'page is pinned';
			}
			else {
				$m = 0;
			}
			printf('<input type="hidden" class="%s_preset" value="%d">',$this->slug, $m );
		}
	}	
	
	public function display_pinned_page_quickedit( $column_name, $post_type ) {

		if( ! current_user_can( 'manage_network' )) {
			return;
		}

		if( $column_name === $this->slug ) { 
					
			$out = array();	
	   		$out[] = sprintf('<fieldset class="inline-edit-col-left inline-edit-%s">',$this->slug);
		    $out[] = sprintf('<div class="inline-edit-col column-%s">',$column_name);
		    $out[] = '<label class="inline-edit-group">';
		    $out[] = sprintf('<span class="title">Pin page</span><input name="%s" class="quickedit_%s" type="checkbox" /> <span class="inline-edit-comment">Keeps page fixed in the menu system, requires Admin to edit title</span>',$this->slug,$this->slug);
		    $out[] = '</label>';
		    $out[] = '</div>';
		    $out[] = '</fieldset>';
		
			echo implode("\n",$out);
		}
	}
	
	
	public function save_metabox_postdata( $post_id ) {
	
		if ( 'page' !== $_POST['post_type'] ) {
			return;
		} 
		if ( 'page' == $_POST['post_type'] && ! current_user_can( 'manage_local_site', $post_id ) ) {
		    return;
		}
		if ( ! isset( $_POST[$this->slug.'-nonce'] ) 
		|| ! wp_verify_nonce( $_POST[$this->slug.'-nonce'],plugin_basename( __FILE__ ))) {
			return;
		}

		if ( !wp_is_post_revision( $post_id ) ) {
			# checkboxes are submitted if checked, absent if not
			if ( isset( $_POST[$this->slug] ) ) {
			    update_post_meta($post_id, $this->slug, 1);
			} else {
			    update_post_meta($post_id, $this->slug, 0);
			}
		}
	}
	
	
	public function save_post_pinned_page_metadata( $post_id ) {
		
		//  only Super Admins can save changes to the pinned state 
		if ( !current_user_can( 'manage_network' ) ) {
	        return;
	    }
		
		if ( !wp_is_post_revision( $post_id ) ) {
			# checkboxes are submitted if checked, absent if not
			if ( isset( $_POST[$this->slug] ) ) {
			    update_post_meta($post_id, $this->slug, 1);
			} else {
			    update_post_meta($post_id, $this->slug, 0);
			}
		}
	}
	
		
	public function modify_post_editor() {
			
		// despite it's name this function modifies the pages editor
			
		// take out the borked default one...
		remove_meta_box('pageparentdiv','page','side');
		
		// and replace with our fixed up version...
		add_meta_box( 'pageparentdiv'
			, 'Page Parent'
			, array( &$this, 'menu_parent_meta_box' )
			, 'page' , 'side' );
	}
	
	
	public function menu_parent_meta_box() {
	
		$out = array();
	
		$out[] = '<p><strong>Parent</strong></p>';
		$out[] = '<label for="parent_id" class="screen-reader-text">Parent</label>';
		$out[] = '<select id="parent_id" name="parent_id">';
		
		if( current_user_can('manage_network' ) ) {
			$out[] = '	<option value="">(no parent)</option>';		// not permitted to regular users by business rule 
		}
		$nodes = self::walk_menu_nodes();
	//	error_log( count($nodes) );				// reflects only top-level parent-nodes
		foreach( $nodes as $n ) {
			$out[] = self::expand_options($n);
		}
	
		$out[] = '</select>';
		$out[] = '<p><strong>Template</strong></p>';
		$out[] = '<label for="page_template" class="screen-reader-text">Page Template</label>';
		$out[] = '<select id="page_template" name="page_template">';
		
		$templates = wp_get_theme()->get_page_templates();
	    foreach ( $templates as $template_filename => $template_name ) {
	        $out[] = '<option value="'.$template_filename.'">'.$template_name.'</option>';
	    }
		
		$out[] = '</select>';
		$out[] = '<p><strong>Order</strong></p>';
		$out[] = '<p><label for="menu_order" class="screen-reader-text">Order</label>';
		
		global $post;
				
		$out[] = sprintf('<input type="text" value="%d" id="menu_order" size="4" name="menu_order"></p>',$post->menu_order);
		$out[] = '<p></p>';
			
		echo implode("\n",$out);
	}
	

	public function expand_options( $node ) {
		
		/**
		*	when the current node (option) has the same value (post_ID) as the current $post object's _parent_ node,
		*	set this option as the selected option in the select control.
		**/
		
		global $post;
		
		$out = array();
		$indent = (($node['depth']>0) ? "\t":'');
		$out[] = sprintf( '<option class="level-%d %s" value="%d"%s>%s</option>',$node['depth'],($node['pinned']?'pinned':''),$node['ID'],(($node['ID']==$post->post_parent)?' selected="selected"':''),$node['post_title']);
		
		if(count($node['children'])>0) {
			foreach( $node['children'] as $n ) {
				$out[] = self::expand_options( $n );
			}
		}
		
		return implode("\n",$out);
	}
	
	
	/**
	*	walk the menu hierarchy from the pinned pages at the base 
	*	to the last pages under each branch.
	**/

	public function walk_menu_nodes( $node_id=0, $depth=0 ) {
			
		global $wpdb;
		$sql = "SELECT ID, post_type, post_title, post_parent, menu_order FROM $wpdb->posts WHERE post_parent=$node_id AND post_type IN('page') AND post_status='publish' ORDER BY post_parent, menu_order";

		$res = $wpdb->get_results($sql);
				
		$ret = array();
		if( $wpdb->num_rows === 0 ) {
			return $ret;
		}
		
		foreach( $res as $r ) {
			$m = get_post_meta($r->ID, $this->slug, true );
			$node = array( 
					'ID'=>$r->ID,
					'post_type'=>$r->post_type, 
					'post_title'=>$r->post_title, 
					'depth' => $depth,
					'menu_order' => $r->menu_order,
					'pinned' => (($m>0)? true:false)
				 );
			$node['children'] = self::walk_menu_nodes($r->ID, $depth+1);
			$ret[] = $node;
			unset($node);
		}
	
		return $ret;	
	}
	
	

}

if ( ! isset( $cooppinnedpages ) ) {
	global $cooppinnedpages;
	$cooppinnedpages = new CoopPinnedPages();
}
endif; /* ! class_exists */
