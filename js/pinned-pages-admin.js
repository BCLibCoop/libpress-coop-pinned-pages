/*
	Pinned-Pages - pinned-pages-admin.js 
*/
;(function($,window) {
	
	var Utils = function() {
		this.init();
	}
	
	Utils.prototype = {
	
		init: function(){
		//	alert( 'there' );
		},

		lockdown_editor: function() {	
			
			if( typenow == 'page' && adminpage == 'post-php' && $('#pinned_page-checkbox').attr("checked") == "checked" ) 
			{
				/*	prevent non-administrator from changing the title of a pinnned item */
				$('#title').attr('disabled','disabled');
				
				/* prevent changing parent */ 
				$('#parent_id').attr('disabled','disabled');
				
				/* prevent changing menu_order */
				$('#menu_order').attr('disabled','disabled');
				
				/* prevent changing pinned_state */
				$('#pinned_page-checkbox').attr('disabled','disabled');
			
				/* prevent unpublishing or hiding */
				$('#misc-publishing-actions a').hide();
				
				/* prevent deletion */
				$('#delete-action').hide();
				
				/* prevent editing slug */
				$('A[href="#post_name"]').hide();
						
			}
			else {
				$('#title').removeAttr('disabled');
				$('#parent_id').removeAttr('disabled');
				$('#menu_order').removeAttr('disabled');
				
				/* prevent changing pinned_state */
				$('#pinned_page-checkbox').attr('disabled','disabled');
				
				$('#misc-publishing-actions a').show();
				$('#delete-action').show();
				$('A[href="#post_name"]').show();
				
			}
			
			$('#coop-pp-metabox').hide();
			$('#pageparentdiv').hide();
						
		},
		
		
		/**
		*	Observe clicks in the custom select element, to prevent mismatched selections.
		*	A pinned page may only have another pinned page as a parent, or may have no parent at all.
		**/
		
		monitor_pinned_parent: function() {
			
			var type = $('#pageparentdiv H3.hndle SPAN').text();
			if( type == 'Page Parent' ) {
				var sel = $('#parent_id option').filter(':selected');
				if( ! $(sel).hasClass('pinned') ) {
					alert( "A pinned page cannot be the child of a regular page.\nPlease select another pinned page (in bold) from the list.");
					return false;
				}
			}
		}
	};
	
	$.fn.utils = function(){
		return new Utils();
	}
	
})(jQuery,window);



jQuery().ready(function($){

	window.utils = jQuery().utils();

	if ( window.user_role !== 'administrator' ) {
		window.utils.lockdown_editor();
	}
		
	jQuery('#parent_id option').click( utils.monitor_pinned_parent );
	
});