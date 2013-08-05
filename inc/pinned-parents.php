<?php defined('ABSPATH') || die(-1);

/**
 * @package PinnedPages
 * @component PinnedParents
 * @copyright BC Libraries Coop 2013
 *
 **/
/**
 * Extension Name: PinnedParents
 * Description: Child of the PinnedPages plugin. Provide hierarchy view and selection in the Parent selctor on Edit.php sidebar.
 * Author: Erik Stainsby, Roaring Sky Software
 * Version: 0.1.0
 **/
 
if ( ! class_exists( 'PinnedParents' )) :
	
class PinnedParents {

	public function __construct() {
		add_action( 'init', array( &$this, '_init' ));
	}

	public function _init() {
		
		error_log(__FUNCTION__);
		if( is_admin()) {
		
			add_action( 'add_meta_boxes', array(&$this,'modify_post_editor'));
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
		$nodes = PinnedParents::walk_menu_nodes();
	//	error_log( count($nodes) );				// reflects only top-level parent-nodes
		foreach( $nodes as $n ) {
			$out[] = PinnedParents::expand_options($n);
		}
	
		$out[] = '</select>';
		$out[] = '<p><strong>Template</strong></p>';
		$out[] = '<label for="page_template" class="screen-reader-text">Page Template</label><select id="page_template" name="page_template">';
		
		$templates = wp_get_theme()->get_page_templates();
	    foreach ( $templates as $template_name => $template_filename ) {
	        echo '<option value="'.$template_filename.'">'.$template_name.'</option>';
	    }
	    
		$out[] = '</select>';
		$out[] = '<p><strong>Order</strong></p>';
		$out[] = '<p><label for="menu_order" class="screen-reader-text">Order</label><input type="text" value="" id="menu_order" size="4" name="menu_order"></p>';
		$out[] = '<p>Need help? Use the Help tab in the upper right of your screen.</p>';
			
		echo implode("\n",$out);
	}
	

	public function expand_options( $node ) {
		
		/**
		*	when this node (option) has the same value (post_ID) as the current $post object's _parent_ node,
		*	set this option as the selected option in the select control.
		**/
		
		global $post;
		
		$out = array();
		$indent = (($node['depth']>0) ? "\t":'');
		$out[] = sprintf( '<option class="level-%d %s" value="%d"%s>%s</option>',$node['depth'],$node['post_type'],$node['ID'],(($node['ID']==$post->post_parent)?' selected="selected"':''),$node['post_title']);
		
		if(count($node['children'])>0) {
			foreach( $node['children'] as $n ) {
				$out[] = PinnedParents::expand_options( $n );
			}
		}
		
		return implode("\n",$out);
	}
	
	/**
	*	walk the menu hierarchy from the fixutres at the base 
	*	to the last pages under each branch.
	*
	**/

	public function walk_menu_nodes( $node_id=0, $depth=0 ) {
			
		global $wpdb;
		$sql = "SELECT ID, post_type, post_title, post_parent FROM $wpdb->posts WHERE post_parent=$node_id AND post_type IN('page','fixture') AND post_status='publish' ORDER BY post_parent,menu_order";

		$res = $wpdb->get_results($sql);
		
		$ret = array();
		if( $wpdb->num_rows === 0 ) {
			return $ret;
		}
		
		foreach( $res as $r ) {
			$node = array( 'ID'=>$r->ID, 'post_type'=>$r->post_type, 'post_title'=>$r->post_title, 'depth' => $depth );
			$node['children'] = PinnedParents::walk_menu_nodes($r->ID, $depth+1);
			$ret[] = $node;
			unset($node);
		}
	
		return $ret;	
	}
	
}
if ( ! isset( $pinnedparents ) ) {
	global $pinnedparents;
	$pinnedparents = new PinnedParents();
}
	
endif; /* ! class_exists */

	