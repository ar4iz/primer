<form id="tables-receipt-filter" method="get">
	<!-- For plugins, we also need to ensure that the form posts back to our current page -->
	<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
	<!-- Now we can render the completed list table -->
	<div id="primer_receipt_table">
		<?php $this->display(); ?>
	</div>
	<div class="submit convert_orders convert_receipts">
		<a href="" class="button download-btn hide" download><?php _e('Download selected receipts', 'export-wp-page-to-static-html'); ?></a>

		<input type="submit" class="button" id="zip_load" value="<?php _e('Download selected receipts', 'primer'); ?>" disabled>
	</div>
</form>
<?php


