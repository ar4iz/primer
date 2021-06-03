<form id="tables-receipt-log-filter" method="get">
	<!-- For plugins, we also need to ensure that the form posts back to our current page -->
	<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
	<!-- Now we can render the completed list table -->
	<div id="primer_receipt_log_table">
		<?php $this->display(); ?>
	</div>
	<div class="submit convert_orders convert_receipts convert_receipts_logs">
		<!--<a href="" class="button"><?php /*_e('Save error log', 'primer'); */?></a>-->
	</div>
</form>
<?php


