/**
 * @package PinnedPages
 * @copyright BC Libraries Coop 2013
 *
 **/
 
; (function($, window) {
 
 	var self;
 	
 	var CoopPPLockUp = function() {
	 	self = this;
	 	return self.init();
 	}
 
 	CoopPPLockUp.prototype = {
	 	
	 	init: function() {
 
		 	if( typenow == 'page' ) {
			 	$('input[name="comment_status"]').parent().parent().remove();
			}
		 	//$('input[name="post_password"]').parent().parent().parent().remove();
		 
		 	self.wp_inline_edit = window.inlineEditPost.edit;
		 	
		 	// and then we overwrite the function with our own code
		 	window.inlineEditPost.edit = self.inlineEditPostEdit;
		 
		 	return self;
		},
 
		inlineEditPostEdit: function( id ) {
		
			// we don't want to leave WordPress hanging
			self.wp_inline_edit.apply( this, arguments );
	
			// now we take care of our business
			// get the post ID
			var post_id = 0;
			if ( typeof( id ) == 'object' ) { post_id = parseInt( this.getId( id ) ); }
				
			if ( post_id > 0 ) {
				// define the edit row
				var edit_row = $( '#edit-' + post_id );
				var post_row = $( '#post-' + post_id );
	
				// get the data
				var checked=false;
				var pinned = $('.pinned_page_preset', post_row).val();
				
				if( pinned == 1 ) {
					checked="checked";
				}	
				// populate the data
				$('.quickedit_pinned_page', edit_row ).attr('checked', checked );
				
			//	console.log( window.user_role +' && ' + pinned );
				
				if ( window.user_role !== 'administrator' && pinned == 1 ) {
					$('.ptitle').attr('disabled','disabled');
					$('input[name="post_name"]').attr('disabled','disabled');
					$('input[name="menu_order"]').attr('disabled','disabled');
					$('#post_parent').attr('disabled','disabled');
					$('select[name="_status"]').attr('disabled','disabled');
				}
				else {
					$('.ptitle').removeAttr('disabled');
					$('input[name="post_name"]').removeAttr('disabled');
					$('input[name="menu_order"]').removeAttr('disabled');
					$('#post_parent').removeAttr('disabled');
					$('select[name="_status"]').removeAttr('disabled');
				}
			}
		}
	};	/* prototype */
	
	$.fn.cooppplockup = function() {
		return new CoopPPLockUp();
	}
	
 }(jQuery, window));
