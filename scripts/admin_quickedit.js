(function($) {
	var $wp_inline_edit = inlineEditPost.edit;

	inlineEditPost.edit = function( id ) {
		$wp_inline_edit.apply( this, arguments );

		var pid = $('#ret_calc_inputs').parents('tr').attr('id').replace('edit-','');
		$('#ret_calc_inputs').html('<img src="images/loading.gif" />').load(ajaxurl + '?action=render_wc_product_margin_quickedit',{post_ID:pid});
	}
})(jQuery);