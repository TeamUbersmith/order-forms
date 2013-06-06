<div class="header page-header grid_21 prefix_3 alpha omega">
	<div class="header-logo">
		<h2 class="register"><?php echo __('Order Review'); ?></h2>
	</div>
	<div class="config steps">
		<p class="step-1"><span class="step-bold"><?php echo __('Step %d', 1); ?></span> <?php echo __('Cart'); ?></p>
		<p class="step-2"><span class="step-bold"><?php echo __('Step %d', 2); ?></span> <?php echo __('Account & Payment'); ?></p>
		<p class="step-3 step-selected"><span class="step-bold"><?php echo __('Step %d', 3); ?></span> <?php echo __('Confirm'); ?></p>
	</div>
</div>
<form action="<?php echo $this->Html->url(array('controller' => 'configurator', 'action' => 'configure', 6));?>" method="POST" onsubmit="return check_required();">
<br clear="all" />
<?php echo $this->Session->flash(); ?>
<div class="grid_22 prefix_1 alpha omega">
	<div class="grid_6 alpha">
		<p class="order_summary"><?php echo __('Account Info'); ?></p>
		<p class="account-info"><?php echo htmlentities(@$client_info['full_name']); ?></p>
		<p class="account-info"><?php echo htmlentities(@$client_info['address']); ?></p>
		<p class="account-info"><?php echo htmlentities(@$client_info['city']); ?>, <?php echo htmlentities(@$client_info['state']); ?> <?php echo htmlentities(@$client_info['postal_code']); ?></p>
		<p class="account-info"><?php echo htmlentities(@$client_info['country']); ?></p>
		<?php if (!empty($client_info['company_name'])) : ?>
			<p class="account-info"><?php echo htmlentities(@$client_info['company_name']); ?></p>
		<?php endif; ?>
		<p class="account-info"><?php echo __('Phone'); ?>: <?php echo htmlentities(@$client_info['phone_number']); ?></p>
	</div>
	<div class="grid_9">
		<p class="order_summary"><?php echo __('Billing Info'); ?></p>
		<div class="review-payment-method">
			<div class="card-or-bank-account-image-review card-or-bank-account-image-<?php echo $payment_method->payment_type; ?> <?php echo $payment_method->payment_type; ?>-<?php echo $payment_method->account_type; ?>"></div>
			<div style="float: left;">
				<?php if ($payment_method->payment_type == 'cc') : ?>
					<p><?php echo $payment_method->card_type_full; ?> <b><?php echo __('ending in %s', $payment_method->text); ?></b></p>
					<p><span><?php echo __('Name'); ?> <b><?php echo htmlentities($payment_method->full_name); ?></b></span></p>
					<p><span><?php echo __('Expires'); ?> <b><?php echo htmlentities($payment_method->expiration_month); ?>/<?php echo htmlentities($payment_method->expiration_year); ?></b></span></p>
				<?php elseif ($payment_method->payment_type == 'ach') : ?>
					<p><?php echo $payment_method->account_type_full . ' '; ?><?php echo __('Account'); ?> <b><?php echo __('ending in %s', $payment_method->text); ?></b></p>
					<p><span><?php echo __('Name'); ?> <b><?php echo htmlentities($payment_method->full_name); ?></b></span></p>
					<p><span><?php echo __('Bank'); ?> <b><?php echo htmlentities($payment_method->bank); ?></b></span></p>
				<?php endif; ?>
			</div>
			<br clear="all" />
		</div>
	</div>
	<div class="grid_6 omega">
		<div class="cart-total">
			<p class="grand-total"><span class="total-value"><?php echo __('Total %s due today', 'USD'); ?>:</span> <span class="grand-total-value" id="total_due_today_text">$<?php echo number_format($total_due_today, 2); ?></span></p>
		</div>
		<div class="continue-to-checkout place-order grid_1 alpha omega">
			<input type="submit" value="<?php echo __('Place Order'); ?>" class="proceed-to-checkout alpha omega" />
		</div>
	</div>
</div>
<br clear="all" />
<div class="agree-to-msa grid_21 alpha omega">
<?php if (empty($has_active_client_msa)) { ?>
	<p><b><?php echo __('Please sign and accept this Service Order and the MSA'); ?></b></p>
	<p>
		<input type="checkbox" name="agreed_to_msa" id="agree-to-msa" value="1" /> &nbsp; <?php echo __('I %s am authorized to, and do duly, sign this Service Order and the Master Services Agreement', ', <input type="text" name="signing_name" id="signing_name" class="signing-name required" />, '); ?>.
		&nbsp;
<?php if ($inline_msa_view) { ?>
		<a href="#" id="view-msa-inline" class="review-edit-link" onclick="show_msaoverlay_lightbox()"; target="_blank"><?php echo __('View'); ?></a> &nbsp;
<?php } ?>
		<a href="/msa/download" class="review-edit-link" target="_blank"><?php echo __('Download'); ?></a>
	</p>
<?php if ($inline_msa_view) { ?>
	<div id="msaoverlay_container">
		<div id="msaoverlay">
			<span class="msaoverlay_close" onclick="$('#msaoverlay_container').hide();"></span>
			<p><?php echo __('Master Services Agreement'); ?></p>
			<div id="msaoverlay_contents"></div>
		</div>
	</div>
	<div id="msaoverlay" style="display:none;">
		<h2><?php echo __('Master Services Agreement'); ?></h2>
		<p><a href="http://www.adobe.com/go/getflashplayer"><img src="https://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" border="0" /></a></p>
	</div>
<?php } ?>
	<?php } else { ?>
		<p><b><?php echo __('Please sign and accept this Service Order'); ?></b></p>
		<p>
			<input type="checkbox" name="agreed_to_msa" id="agree-to-msa" value="1" /> &nbsp; <?php echo __('I %s am authorized to, and do duly,  bind this Service Order to the active Master Services Agreement on file', ', <input type="text" name="signing_name" id="signing_name" class="signing-name required" />, '); ?>.
		</p>	
	<?php } ?>
</div>
<br clear="all" />
<p class="order_summary prefix_1"><?php echo __('Order Summary'); ?> &nbsp; <a href="<?php echo $this->Html->url(array('controller' => 'configurator', 'action' => 'configure', 'cart'));?>" class="review-edit-link">Edit</a></p>
<div class="prefix_1 grid_22 box">
	<table class="prefix_1 cart-table cart-table-review" width="100%">
		<tr class="cart-table-header">
			<th width="63"><?php echo __('Qty'); ?></th>
			<th width="275"><?php echo __('Item'); ?></th>
			<th width="300"><?php echo __('Hostnames'); ?></th>
			<th width="100" class="subtotal-column"><?php echo __('Unit Price'); ?></th>
			<th class="subtotal-column"><?php echo __('Subtotal'); ?></th>
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
				<div class="hostnames-div" id="hostnames-div-<?php echo $item->id; ?>">
					<?php foreach ($item->hostnames as $hostname) : ?>
						<p class="new-or-existing-list-items"><?php echo htmlentities($hostname); ?></p>
					<?php endforeach; ?>
				</div>
			</td>
			<td align="right" valign="top" class="subtotal-column">
				<?php if ($item->monthly_price_one_time_discount_amount > 0 && $item->monthly_price_one_time_discount_amount != $item->monthly_price_recurring_discount_amount) : ?>
					<p class="unit-column discount" id="item_<?php echo $item->id; ?>_unit_monthly_price_text_discount">$<?php echo number_format($item->monthly_price - $item->monthly_price_one_time_discount_amount, 2); ?></p>
					<p class="unit-description discount" id="item_<?php echo $item->id; ?>_unit_monthly_price_text_discount_monthly"><?php echo __('first month'); ?></p>
					<p class="unit-column<?php echo ($item->monthly_price_recurring_discount_amount > 0) ? ' ' . __('discount') : ''; ?>" id="item_<?php echo $item->id; ?>_unit_monthly_price_text">$<?php echo number_format($item->monthly_price - $item->monthly_price_recurring_discount_amount, 2); ?></p>
					<p class="unit-description<?php echo ($item->monthly_price_recurring_discount_amount > 0) ? ' ' . __('discount') : ''; ?>"><?php echo __('monthly'); ?></p>
				<?php elseif ($item->monthly_price > 0) : ?>
					<p class="unit-column discount" id="item_<?php echo $item->id; ?>_unit_monthly_price_text_discount" style="display: none;">$<?php echo number_format($item->monthly_price - $item->monthly_price_one_time_discount_amount, 2); ?></p>
					<p class="unit-description discount" id="item_<?php echo $item->id; ?>_unit_monthly_price_text_discount_monthly" style="display: none;"><?php echo __('first month'); ?></p>
					<p class="unit-column<?php echo ($item->monthly_price_recurring_discount_amount > 0) ? ' ' . __('discount') : ''; ?>" id="item_<?php echo $item->id; ?>_unit_monthly_price_text">$<?php echo number_format($item->monthly_price - $item->monthly_price_recurring_discount_amount, 2); ?></p>
					<p class="unit-description<?php echo ($item->monthly_price_recurring_discount_amount > 0) ? ' ' . __('discount') : ''; ?>"><?php echo __('monthly'); ?></p>
				<?php endif; ?>
				<?php if ($item->setup_fee > 0) : ?>
					<p class="unit-column">$<?php echo number_format($item->setup_fee, 2); ?></p>
					<p class="unit-description"><?php echo __('setup'); ?></p>
				<?php endif; ?>
			</td>
			<td align="right" valign="top" class="subtotal-column">
				<?php if ($item->monthly_price_one_time_discount_subtotal > 0 && $item->monthly_price_one_time_discount_subtotal != $item->monthly_price_recurring_discount_subtotal) : ?>
					<p class="subtotal-column discount" id="item_<?php echo $item->id; ?>_subtotal_monthly_price_text_discount">$<?php echo number_format($item->monthly_price_subtotal - $item->monthly_price_one_time_discount_subtotal, 2); ?></p>
					<p class="unit-description discount"><?php echo __('first month'); ?></p>
					<p class="subtotal-column<?php echo ($item->monthly_price_recurring_discount_subtotal > 0) ? ' ' . __('discount') : ''; ?>" id="item_<?php echo $item->id; ?>_subtotal_monthly_price_text_discount">$<?php echo number_format($item->monthly_price_subtotal - $item->monthly_price_recurring_discount_subtotal, 2); ?></p>
					<p class="unit-description<?php echo ($item->monthly_price_recurring_discount_subtotal > 0) ? ' ' . __('discount') : ''; ?>"><?php echo __('monthly'); ?></p>
				<?php elseif ($item->monthly_price_subtotal > 0) : ?>
					<p class="subtotal-column discount" id="item_<?php echo $item->id; ?>_subtotal_monthly_price_text_discount" style="display: none;">$<?php echo number_format($item->monthly_price_subtotal - $item->monthly_price_one_time_discount_subtotal, 2); ?></p>
					<p class="unit-description discount" style="display: none;"><?php echo __('first month'); ?></p>
					<p class="subtotal-column<?php echo ($item->monthly_price_recurring_discount_subtotal > 0) ? ' ' . __('discount') : ''; ?>" id="item_<?php echo $item->id; ?>_subtotal_monthly_price_text">$<?php echo number_format($item->monthly_price_subtotal - $item->monthly_price_recurring_discount_subtotal, 2); ?></p>
					<p class="unit-description<?php echo ($item->monthly_price_recurring_discount_subtotal > 0) ? ' ' . __('discount') : ''; ?>"><?php echo __('monthly'); ?></p>
				<?php endif; ?>
				<?php if ($item->setup_fee_subtotal > 0) : ?>
					<p class="subtotal-column">$<?php echo number_format($item->setup_fee_subtotal, 2); ?></p>
					<p class="unit-description"><?php echo __('setup'); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach ?>
	</table>
</div>
<br clear="all" />
<div class="cart-total grid_21 prefix_1">
	<?php if ($monthly_one_time_total != $monthly_recurring_total) : ?>
		<p class="cart-total-summary" id="monthly_one_time_total_p"><span class="total-text"><?php echo __('Total first month'); ?>:</span> <span class="total-value" id="monthly_one_time_total_text">$<?php echo number_format($monthly_one_time_total, 2); ?></span></p>
		<p class="cart-total-summary"><span class="total-text"><?php echo __('Total %s monthly', 'USD'); ?>:</span> <span class="total-value" id="monthly_recurring_total_text">$<?php echo number_format($monthly_recurring_total, 2); ?></span></p>
	<?php else : ?>
		<p class="cart-total-summary" id="monthly_one_time_total_p" style="display: none;"><span class="total-text">Total first month'); ?>:</span> <span class="total-value" id="monthly_one_time_total_text">$<?php echo number_format($monthly_one_time_total, 2); ?></span></p>
		<p class="cart-total-summary"><span class="total-text"><?php echo __('Total %s monthly', 'USD'); ?>:</span> <span class="total-value" id="monthly_recurring_total_text">$<?php echo number_format($monthly_recurring_total, 2); ?></span></p>
	<?php endif; ?>
	<?php if ($setup_fee_total > 0) : ?>
		<p class="cart-total-summary"><span class="total-text"><?php echo __('Total %s setup', 'USD'); ?>:</span> <span class="total-value" id="setup_fee_total_text">$<?php echo number_format($setup_fee_total, 2); ?></span></p>
	<?php endif; ?>
	<p class="grand-total"><span class="total-value"><?php echo __('Total %s due today', 'USD'); ?>:</span> <span class="grand-total-value" id="total_due_today_text">$<?php echo number_format($total_due_today, 2); ?></span></p>
</div>
<div class="continue-to-checkout">
    <div class="continue-to-checkout place-order grid_1 alpha omega">
        <input type="submit" value="<?php echo __('Place Order'); ?>" class="proceed-to-checkout alpha omega" />
    </div>
</div>
</form>
<script type="text/javascript">
	$('input.highlight-error').effect('highlight', { color: '#f7f493' }, 1250);
	
	$('#signing_name').click(function() {
		$('#agree-to-msa')[0].checked = true;
	});
</script>
