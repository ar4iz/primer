<form id="tables-filter" method="get">
	<!-- For plugins, we also need to ensure that the form posts back to our current page -->
	<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
	<!-- Now we can render the completed list table -->
	<div id="primer_order_table">
		<?php
//		wp_nonce_field( 'ajax-order-list-nonce', '_ajax_order_list_nonce' );
		$this->display();
		?>
	</div>
	<div class="submit convert_orders">
		<input type="hidden" name="action" value="convert_select_orders">
		<input type="submit" class="submit_convert_orders" value="<?php _e('Issue Receipts for selected orders', 'primer'); ?>" disabled>
	</div>
</form>
<?php

