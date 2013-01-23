<div class="header page-header grid_21 prefix_3 alpha omega">
	<div class="header-logo">
		<h2 class="register">Order Review</h2>
	</div>
	<div class="config steps">
		<p class="step-1"><span class="step-bold">Step 1</span> Cart</p>
		<p class="step-2"><span class="step-bold">Step 2</span> Account & Payment</p>
		<p class="step-3 step-selected"><span class="step-bold">Step 3</span> Confirm</p>
	</div>
</div>
<form action="<?php echo $this->Html->url(array('controller' => 'configurator', 'action' => 'configure', 6));?>" method="POST" onsubmit="return check_required();">
<br clear="all" />
<?php echo SessionHelper::flash(); ?>
<div class="grid_22 prefix_1 alpha omega">
	<div class="grid_6 alpha">
		<p class="order_summary">Account Info</p>
		<p class="account-info"><?php echo htmlentities(@$client_info['full_name']); ?></p>
		<p class="account-info"><?php echo htmlentities(@$client_info['address']); ?></p>
		<p class="account-info"><?php echo htmlentities(@$client_info['city']); ?>, <?php echo htmlentities(@$client_info['state']); ?> <?php echo htmlentities(@$client_info['postal_code']); ?></p>
		<p class="account-info"><?php echo htmlentities(@$client_info['country']); ?></p>
		<?php if (!empty($client_info['company_name'])) : ?>
			<p class="account-info"><?php echo htmlentities(@$client_info['company_name']); ?></p>
		<?php endif; ?>
		<p class="account-info">Phone: <?php echo htmlentities(@$client_info['phone_number']); ?></p>
	</div>
	<div class="grid_9">
		<p class="order_summary">Billing Info</p>
		<div class="review-payment-method">
			<div class="card-or-bank-account-image-review card-or-bank-account-image-<?php echo $payment_method->payment_type; ?> <?php echo $payment_method->payment_type; ?>-<?php echo $payment_method->account_type; ?>"></div>
			<div style="float: left;">
				<?php if ($payment_method->payment_type == 'cc') : ?>
					<p><?php echo $payment_method->card_type_full; ?> <b>ending in <?php echo $payment_method->text; ?></b></p>
					<p><span>Name <b><?php echo htmlentities($payment_method->full_name); ?></b></span></p>
					<p><span>Expires <b><?php echo htmlentities($payment_method->expiration_month); ?>/<?php echo htmlentities($payment_method->expiration_year); ?></b></span></p>
				<?php elseif ($payment_method->payment_type == 'ach') : ?>
					<p><?php echo $payment_method->account_type_full . ' '; ?>Account <b>ending in <?php echo $payment_method->text; ?></b></p>
					<p><span>Name <b><?php echo htmlentities($payment_method->full_name); ?></b></span></p>
					<p><span>Bank <b><?php echo htmlentities($payment_method->bank); ?></b></span></p>
				<?php endif; ?>
			</div>
			<br clear="all" />
		</div>
	</div>
	<div class="grid_6 omega">
		<div class="cart-total">
			<p class="grand-total"><span class="total-value">Total USD due today:</span> <span class="grand-total-value" id="total_due_today_text">$<?php echo number_format($total_due_today, 2); ?></span></p>
		</div>
		<div class="continue-to-checkout place-order grid_1 alpha omega">
			<input type="submit" value="Place Order" class="proceed-to-checkout alpha omega" />
		</div>
	</div>
</div>
<br clear="all" />
<div class="agree-to-msa grid_21 alpha omega">
<?php if (empty($has_active_client_msa)) { ?>
	<p><b>Please sign and accept this Service Order and the MSA</b></p>
	<p>
		<input type="checkbox" name="agreed_to_msa" id="agree-to-msa" value="1" /> &nbsp; I, <input type="text" name="signing_name" id="signing_name" class="signing-name required" />, am authorized to, and do duly, sign this Service Order and the Master Services Agreement.
		&nbsp;
<?php if ($inline_msa_view) { ?>
		<a href="#" id="view-msa-inline" class="review-edit-link" onclick="show_msaoverlay_lightbox()"; target="_blank">View</a> &nbsp;
<?php } ?>
		<a href="/msa/download" class="review-edit-link" target="_blank">Download</a>
	</p>
<?php if ($inline_msa_view) { ?>
	<div id="msaoverlay_container">
		<div id="msaoverlay">
			<span class="msaoverlay_close" onclick="$('#msaoverlay_container').hide();"></span>
			<p>Master Services Agreement</p>
			<div id="msaoverlay_contents"></div>
		</div>
	</div>
	<div id="msaoverlay" style="display:none;">
		<h2>Master Services Agreement</h2>
		<p><a href="http://www.adobe.com/go/getflashplayer"><img src="https://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" border="0" /></a></p>
	</div>
<?php } ?>
	<?php } else { ?>
		<p><b>Please sign and accept this Service Order</b></p>
		<p>
			<input type="checkbox" name="agreed_to_msa" id="agree-to-msa" value="1" /> &nbsp; I, <input type="text" name="signing_name" id="signing_name" class="signing-name required" />, am authorized to, and do duly, bind this Service Order to the active Master Services Agreement on file.
		</p>	
	<?php } ?>
</div>
<br clear="all" />
<p class="order_summary prefix_1">Order Summary &nbsp; <a href="<?php echo $this->Html->url(array('controller' => 'configurator', 'action' => 'configure', 'cart'));?>" class="review-edit-link">Edit</a></p>
<div class="prefix_1 grid_22 box">
	<table class="prefix_1 cart-table cart-table-review" width="100%">
		<tr class="cart-table-header">
			<th width="63">Qty</th>
			<th width="275">Item</th>
			<th width="300">Hostname(s)</th>
			<th width="100" class="subtotal-column">Unit Price</th>
			<th class="subtotal-column">Subtotal</th>
		</tr>
		<tr class="cart-table-separator"></tr>
		<?php foreach ($items as $item) : ?>
		<script type="text/javascript">
			upgrades[<?php echo $item->id; ?>] = [];
		</script>
		<tr class="cart-item">
			<td valign="top">
				<p class="summary-quantity"><?php echo number_format($item->quantity, 0); ?></p>
			</td>
			<td valign="top">
				<p class="item-bold"><?php echo $item->name; ?></p>
				<?php foreach ($item->upgrades as $upgrade) : ?>
						<p class="item-small"><?php echo @$upgrade->value; ?></p>
				<?php endforeach; ?>
			</td>
			<td valign="top">
				<div id="quantity-error-<?php echo $item->id; ?>" class="error" style="display: none;">Invalid quantity</div>
				<div class="hostnames-div" id="hostnames-div-<?php echo $item->id; ?>">
					<?php foreach ($item->hostnames as $hostname) : ?>
						<p class="new-or-existing-list-items"><?php echo htmlentities($hostname); ?></p>
					<?php endforeach; ?>
				</div>
			</td>
			<td align="right" valign="top" class="subtotal-column">
				<?php if ($item->monthly_price_one_time_discount_amount > 0 && $item->monthly_price_one_time_discount_amount != $item->monthly_price_recurring_discount_amount) : ?>
					<p class="unit-column discount" id="item_<?php echo $item->id; ?>_unit_monthly_price_text_discount">$<?php echo number_format($item->monthly_price - $item->monthly_price_one_time_discount_amount, 2); ?></p>
					<p class="unit-description discount" id="item_<?php echo $item->id; ?>_unit_monthly_price_text_discount_monthly">first month</p>
					<p class="unit-column<?php echo ($item->monthly_price_recurring_discount_amount > 0) ? ' discount' : ''; ?>" id="item_<?php echo $item->id; ?>_unit_monthly_price_text">$<?php echo number_format($item->monthly_price - $item->monthly_price_recurring_discount_amount, 2); ?></p>
					<p class="unit-description<?php echo ($item->monthly_price_recurring_discount_amount > 0) ? ' discount' : ''; ?>">monthly</p>
				<?php elseif ($item->monthly_price > 0) : ?>
					<p class="unit-column discount" id="item_<?php echo $item->id; ?>_unit_monthly_price_text_discount" style="display: none;">$<?php echo number_format($item->monthly_price - $item->monthly_price_one_time_discount_amount, 2); ?></p>
					<p class="unit-description discount" id="item_<?php echo $item->id; ?>_unit_monthly_price_text_discount_monthly" style="display: none;">first month</p>
					<p class="unit-column<?php echo ($item->monthly_price_recurring_discount_amount > 0) ? ' discount' : ''; ?>" id="item_<?php echo $item->id; ?>_unit_monthly_price_text">$<?php echo number_format($item->monthly_price - $item->monthly_price_recurring_discount_amount, 2); ?></p>
					<p class="unit-description<?php echo ($item->monthly_price_recurring_discount_amount > 0) ? ' discount' : ''; ?>">monthly</p>
				<?php endif; ?>
				<?php if ($item->setup_fee > 0) : ?>
					<p class="unit-column">$<?php echo number_format($item->setup_fee, 2); ?></p>
					<p class="unit-description">setup</p>
				<?php endif; ?>
			</td>
			<td align="right" valign="top" class="subtotal-column">
				<?php if ($item->monthly_price_one_time_discount_subtotal > 0 && $item->monthly_price_one_time_discount_subtotal != $item->monthly_price_recurring_discount_subtotal) : ?>
					<p class="subtotal-column discount" id="item_<?php echo $item->id; ?>_subtotal_monthly_price_text_discount">$<?php echo number_format($item->monthly_price_subtotal - $item->monthly_price_one_time_discount_subtotal, 2); ?></p>
					<p class="unit-description discount">first month</p>
					<p class="subtotal-column<?php echo ($item->monthly_price_recurring_discount_subtotal > 0) ? ' discount' : ''; ?>" id="item_<?php echo $item->id; ?>_subtotal_monthly_price_text_discount">$<?php echo number_format($item->monthly_price_subtotal - $item->monthly_price_recurring_discount_subtotal, 2); ?></p>
					<p class="unit-description<?php echo ($item->monthly_price_recurring_discount_subtotal > 0) ? ' discount' : ''; ?>">monthly</p>
				<?php elseif ($item->monthly_price_subtotal > 0) : ?>
					<p class="subtotal-column discount" id="item_<?php echo $item->id; ?>_subtotal_monthly_price_text_discount" style="display: none;">$<?php echo number_format($item->monthly_price_subtotal - $item->monthly_price_one_time_discount_subtotal, 2); ?></p>
					<p class="unit-description discount" style="display: none;">first month</p>
					<p class="subtotal-column<?php echo ($item->monthly_price_recurring_discount_subtotal > 0) ? ' discount' : ''; ?>" id="item_<?php echo $item->id; ?>_subtotal_monthly_price_text">$<?php echo number_format($item->monthly_price_subtotal - $item->monthly_price_recurring_discount_subtotal, 2); ?></p>
					<p class="unit-description<?php echo ($item->monthly_price_recurring_discount_subtotal > 0) ? ' discount' : ''; ?>">monthly</p>
				<?php endif; ?>
				<?php if ($item->setup_fee_subtotal > 0) : ?>
					<p class="subtotal-column">$<?php echo number_format($item->setup_fee_subtotal, 2); ?></p>
					<p class="unit-description">setup</p>
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach ?>
	</table>
</div>
<br clear="all" />
<div class="cart-total grid_21 prefix_1">
	<?php if ($monthly_one_time_total != $monthly_recurring_total) : ?>
		<p class="cart-total-summary" id="monthly_one_time_total_p"><span class="total-text">Total first month:</span> <span class="total-value" id="monthly_one_time_total_text">$<?php echo number_format($monthly_one_time_total, 2); ?></span></p>
		<p class="cart-total-summary"><span class="total-text">Total USD monthly:</span> <span class="total-value" id="monthly_recurring_total_text">$<?php echo number_format($monthly_recurring_total, 2); ?></span></p>
	<?php else : ?>
		<p class="cart-total-summary" id="monthly_one_time_total_p" style="display: none;"><span class="total-text">Total first month:</span> <span class="total-value" id="monthly_one_time_total_text">$<?php echo number_format($monthly_one_time_total, 2); ?></span></p>
		<p class="cart-total-summary"><span class="total-text">Total USD monthly:</span> <span class="total-value" id="monthly_recurring_total_text">$<?php echo number_format($monthly_recurring_total, 2); ?></span></p>
	<?php endif; ?>
	<?php if ($setup_fee_total > 0) : ?>
		<p class="cart-total-summary"><span class="total-text">Total USD setup:</span> <span class="total-value" id="setup_fee_total_text">$<?php echo number_format($setup_fee_total, 2); ?></span></p>
	<?php endif; ?>
	<p class="grand-total"><span class="total-value">Total USD due today:</span> <span class="grand-total-value" id="total_due_today_text">$<?php echo number_format($total_due_today, 2); ?></span></p>
</div>
<div class="continue-to-checkout">
    <div class="continue-to-checkout place-order grid_1 alpha omega">
        <input type="submit" value="Place Order" class="proceed-to-checkout alpha omega" />
    </div>
</div>
</form>
<script type="text/javascript">
	$('input.highlight-error').effect('highlight', { color: '#f7f493' }, 1250);
	
	$('#signing_name').click(function() {
		$('#agree-to-msa')[0].checked = true;
	});
</script>
