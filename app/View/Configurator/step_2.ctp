<div class="header page-header grid_21 prefix_3 alpha omega">
	<div class="header-logo">
		<h2 class="register"><?php echo __('Cart', 'Shopping Cart'); ?></h2>
	</div>
	<div class="config steps">
		<p class="step-1 step-selected"><span class="step-bold"><?php echo __('Step %d', 1); ?></span> <?php echo __('Cart'); ?></p>
		<p class="step-2"><span class="step-bold"><?php echo __('Step %d', 2); ?></span> <?php echo __('Account & Payment'); ?></p>
		<p class="step-3"><span class="step-bold"><?php echo __('Step %d', 3); ?></span> <?php echo __('Confirm', 'Confirm Order'); ?></p>
	</div>
</div>
<br clear="all" />
<?php echo $this->Session->flash(); ?>
<form id="virtual_rack_form" action="<?php echo $this->Html->url(array('controller' => 'configurator', 'action' => 'configure', 3));?>" method="POST" onsubmit="return check_required();">
<div class="prefix_1 grid_22 box">
	<table class="prefix_1 cart-table" width="100%">
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
				<input class="required quantity<?php echo (in_array($item->id, $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" id="quantity-<?php echo $item->id; ?>" data-old-quantity="<?php echo $quantities[$item->id]; ?>" data-item-id="<?php echo $item->id; ?>" type="text" name="quantity[<?php echo $item->id; ?>]" onkeyup="update_hostname_list(<?php echo $item->id; ?>); update_subtotals(<?php echo $item->id; ?>);" maxlength="2" value="<?php echo $quantities[$item->id]; ?>" />
			</td>
			<td valign="top">
				<p class="item-bold"><?php echo $item->name; ?></p>
				<p class="edit-and-delete-item"><a href="<?php echo $this->Html->url(array('controller' => 'configurator', 'action' => 'configure', 1, $item->service_plan_id, $item->id));?>"><?php echo __('Edit'); ?></a> / <a href="javascript:void(0);" onclick="if(confirm('<?php echo __('Are you sure you want to remove this item from your cart'); ?>?')) { location.href='<?php echo $this->Html->url(array('controller' => 'configurator', 'action' => 'remove_from_cart', $item->id));?>'; } "><?php echo __('Remove'); ?></a></p>
				<?php foreach ($item->upgrades as $upgrade) :
					$upgrade_id = @$upgrade->id;
					?>
					<?php if (empty($upgrade->callout_upgrades)) : ?>
						<p class="item-small"><?php echo @$upgrade->value; ?></p>
					<?php else: ?>
						<script type="text/javascript">
							upgrades[<?php echo $item->id; ?>][<?php echo $upgrade_id; ?>] = [];
							upgrades[<?php echo $item->id; ?>][<?php echo $upgrade_id; ?>]['default_callout_upgrade_option_id'] = '<?php echo $upgrade->default_callout_upgrade_option_id; ?>';
						</script>
						<p id="p_upgrade_option_<?php echo $item->id; ?>_<?php echo $upgrade_id; ?>" class="item-small upgrade-option-callout"><?php echo $upgrade->category; ?><span> - <?php echo $upgrade->value; ?></span> &nbsp; <a href="javascript:void(0);" onclick="toggle_upsell_div('<?php echo $item->id; ?>_<?php echo $upgrade_id; ?>');$('#upsell_div_<?php echo $item->id; ?>_<?php echo $upgrade_id; ?>').css('margin-top', '-' + (parseInt($('#p_upgrade_option_<?php echo $item->id; ?>_<?php echo $upgrade_id; ?>').height()) + 8) + 'px');" class="upgrade-option-callout-add-now"><?php echo (!empty($upgrade->callout_text)) ? $upgrade->callout_text : __('Add Now'); ?></a></p>
						<div class="upsell_div" id="upsell_div_<?php echo $item->id; ?>_<?php echo $upgrade_id; ?>">
							<p class="upgrade-option-callout callout-header float-left"><?php echo $upgrade->category; ?></p>
							<div class="close-modal" onclick="cancel_upsell(<?php echo $item->id; ?>, <?php echo $upgrade_id; ?>, <?php echo $upgrade->default_callout_upgrade_option_id; ?>);"></div>
							<br clear="all" />
							<?php foreach ($upgrade->callout_upgrades as $upgrade_option_id => $upgrade_option) : ?>
								<div id="item_<?php echo $item->id; ?>_<?php echo $upgrade_id; ?>_<?php echo $upgrade_option_id; ?>" class="item callout_item" data-upgrade-text="<?php echo $upgrade_option->name ?>" data-monthly-price="<?php echo $upgrade_option->monthly_price; ?>">
									<script type="text/javascript">
									<?php if (!empty($upgrade_option->is_default_upgrade_option)) : ?>
										upgrades[<?php echo $item->id; ?>][<?php echo $upgrade_id; ?>]['selected'] = '<?php echo $upgrade_option_id; ?>';
									<?php endif; ?>
									upgrades[<?php echo $item->id; ?>][<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>] = [];
									upgrades[<?php echo $item->id; ?>][<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]['monthly_price'] = '<?php echo $upgrade_option->monthly_price; ?>';
									upgrades[<?php echo $item->id; ?>][<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]['monthly_price_discount_amount'] = '<?php echo empty($upgrade_option->monthly_price_discount_amount) ? '0' : $upgrade_option->monthly_price_discount_amount; ?>';
									upgrades[<?php echo $item->id; ?>][<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]['monthly_price_one_time_discount_amount'] = '<?php echo empty($upgrade_option->monthly_price_one_time_discount_amount) ? '0' : $upgrade_option->monthly_price_one_time_discount_amount; ?>';
									upgrades[<?php echo $item->id; ?>][<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]['monthly_price_recurring_discount_amount'] = '<?php echo empty($upgrade_option->monthly_price_recurring_discount_amount) ? '0' : $upgrade_option->monthly_price_recurring_discount_amount; ?>';
									upgrades[<?php echo $item->id; ?>][<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]['setup_fee'] = '<?php echo $upgrade_option->setup_fee; ?>';
									upgrades[<?php echo $item->id; ?>][<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]['setup_fee_discount_amount'] = '<?php echo empty($upgrade_option->setup_fee_discount_amount) ? '0' : $upgrade_option->setup_fee_discount_amount; ?>';
									</script>
									<label for="upgrades[<?php echo $item->id; ?>][<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]">
										<input type="radio" id="upgrades[<?php echo $item->id; ?>][<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]" onchange="old_upgrade_option_id='upgrades[<?php echo $item->id; ?>][<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]'; update_upsell_price(<?php echo $item->id; ?>);" name="upgrades[<?php echo $item->id; ?>][<?php echo $upgrade_id; ?>]" value="upgrades[<?php echo $item->id; ?>][<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]" <?php echo (!empty($upgrade_option->is_default_upgrade_option)) ? 'checked' : '' ?> />
										<span class="callout-item-name item-name"><?php echo $upgrade_option->name ?></span>
										<?php if (!empty($upgrade_option->description)) : ?>
											(<?php echo $upgrade_option->description; ?>)
										<?php endif ?>
										<?php if (empty($upgrade_option->is_default_upgrade_option)) : ?>
										<?php
											// not using inline php+html here because of the spaces that it creates
											echo '<span class="upgrade_option_language" id="upgrade_option_language_' . $item->id . '_' . $upgrade_id . '_' . $upgrade_option_id . '">(';
											if ($upgrade_option->monthly_price >= $upgrade->callout_upgrades[$upgrade_option_id]->monthly_price) {
												$has_monthly_price = true;
													echo '$' . number_format($upgrade_option->monthly_price - ($upgrade->callout_upgrades[$upgrade->default_callout_upgrade_option_id]->monthly_price - $upgrade->callout_upgrades[$upgrade->default_callout_upgrade_option_id]->monthly_price_discount_amount), 2) . '/' . __('mo');
													if ($upgrade_option->monthly_price_discount_amount > 0) {
														if ($upgrade_option->monthly_price_discount_amount == $upgrade_option->monthly_price_recurring_discount_amount) {
															echo ' - $' . number_format($upgrade_option->monthly_price_discount_amount, 2) . '/' . __('mo discount') . ' = <span class="discount">$' . number_format($upgrade_option->monthly_price - ($upgrade->callout_upgrades[$upgrade->default_callout_upgrade_option_id]->monthly_price - $upgrade->callout_upgrades[$upgrade->default_callout_upgrade_option_id]->monthly_price_discount_amount) - $upgrade_option->monthly_price_discount_amount, 2) . '/' . __('mo') . '</span>';
														}
														else {
															if ($upgrade_option->monthly_price_one_time_discount_amount > 0) {
																echo ' - $' . number_format($upgrade_option->monthly_price_one_time_discount_amount, 2);
															}
															if ($upgrade_option->monthly_price_recurring_discount_amount > 0) {
																echo ' - $' . number_format($upgrade_option->monthly_price_recurring_discount_amount, 2) . '/' . __('mo discount');
															}
															echo ' = <span class="discount">';
															if ($upgrade_option->monthly_price_one_time_discount_amount > 0) {
																echo '$' . number_format($upgrade_option->monthly_price - ($upgrade->callout_upgrades[$upgrade->default_callout_upgrade_option_id]->monthly_price - $upgrade->callout_upgrades[$upgrade->default_callout_upgrade_option_id]->monthly_price_one_time_discount_amount) - $upgrade_option->monthly_price_one_time_discount_amount, 2) . ' ' . __('first month') . '; ';
															}
															echo ' $' . number_format($upgrade_option->monthly_price - ($upgrade->callout_upgrades[$upgrade->default_callout_upgrade_option_id]->monthly_price - $upgrade->callout_upgrades[$upgrade->default_callout_upgrade_option_id]->monthly_price_recurring_discount_amount) - $upgrade_option->monthly_price_recurring_discount_amount, 2) . '/' . __('mo') . '</span>';
														}
													}
												//echo '$' . $upgrade_option->monthly_price . '/mo';
											}
											elseif ($upgrade_option->monthly_price < $upgrade->callout_upgrades[$upgrade_option_id]->monthly_price) {
												$has_monthly_price = true;
												echo ' - $' . ($upgrade->callout_upgrades[$upgrade_option_id]->monthly_price - $upgrade_option->monthly_price) . '/' . __('mo');
											}
											if ($upgrade_option->setup_fee > $upgrade->callout_upgrades[$upgrade_option_id]->setup_fee) {
												$has_setup_fee = true;
												if (!empty($has_monthly_price)) {
													echo ' + ';
												}
												echo '$' . $upgrade_option->setup_fee . ' ' . __('setup');
											}
											elseif ($upgrade_option->setup_fee < $upgrade->callout_upgrades[$upgrade_option_id]->setup_fee) {
												$has_setup_fee = true;
												echo ' - $' . ($upgrade->callout_upgrades[$upgrade_option_id]->setup_fee - $upgrade_option->setup_fee) .' ' . __('setup');
											}
											echo ')</span>';
										?>
										<?php else: ?>
											<span class="upgrade_option_language" id="upgrade_option_language_<?php echo $item->id; ?>_<?php echo $upgrade_id; ?>_<?php echo $upgrade_option_id; ?>">(<?php echo ('Included in price'); ?>)</span>
										<?php endif ?>
									</label>
								</div>
							<?php endforeach; // callout_upgrades ?>
							<input type="button" class="update-upsell-button" value="<?php echo __('Update'); ?>" onclick="save_upsell_option(<?php echo $item->service_plan_id; ?>, <?php echo $item->id; ?>, <?php echo $upgrade_id; ?>);" />
							<input type="button" class="update-upsell-button" value="<?php echo __('Cancel'); ?>" onclick="cancel_upsell(<?php echo $item->id; ?>, <?php echo $upgrade_id; ?>, <?php echo $upgrade->default_callout_upgrade_option_id; ?>);" />
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</td>
			<td valign="top" id="hostnames-tr">
				<div id="quantity-error-<?php echo $item->id; ?>" class="error" style="display: none;"><?php echo __('Invalid quantity'); ?></div>
				<div class="hostnames-div" id="hostnames-div-<?php echo $item->id; ?>">
					<?php
					$i = 1;
					foreach ($hostnames[$item->id] as $hostname) :
						$hostname_div_id = 'hostnames-' . $item->id . '-' . $i;
						?>
						<p class="hostnames-<?php echo $item->id; ?>-<?php echo $i; ?>-p" id="hostnames-<?php echo $item->id; ?>-<?php echo $i; ?>-p"><input class="required hostname hostnames-<?php echo $item->id; ?><?php echo (in_array($hostname_div_id, $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" placeholder="server1.yourdomain.com" id="<?php echo $hostname_div_id; ?>" type="text" name="hostnames[<?php echo $item->id; ?>][]" value="<?php echo htmlentities($hostname); ?>" data-item-id="<?php echo $item->id; ?>" data-hostname-id="<?php echo $i; ?>" /> <sup>*</sup></p>
						<?php
						$i++;
					endforeach; ?>
				</div>
			</td>
			<td align="right" valign="top" class="subtotal-column">
				<input type="hidden" class="calc-unit-monthly-price items_unit_monthly_price" data-item-id="<?php echo $item->id; ?>" id="item_<?php echo $item->id; ?>_unit_monthly_price" value="<?php echo $item->monthly_price; ?>" />
				<input type="hidden" class="calc-unit-monthly-price-one-time-discount-amount items_unit_monthly_price_one_time_discount_amount" data-item-id="<?php echo $item->id; ?>" id="item_<?php echo $item->id; ?>_unit_monthly_price_one_time_discount_amount" value="<?php echo $item->monthly_price_one_time_discount_amount; ?>" />
				<input type="hidden" class="calc-unit-monthly-price-recurring-discount-amount items_unit_monthly_price_recurring_discount_amount" data-item-id="<?php echo $item->id; ?>" id="item_<?php echo $item->id; ?>_unit_monthly_price_recurring_discount_amount" value="<?php echo $item->monthly_price_recurring_discount_amount; ?>" />
				<input type="hidden" class="calc-unit-setup-fee items_unit_setup_fee" data-item-id="<?php echo $item->id; ?>" id="item_<?php echo $item->id; ?>_unit_setup_fee" value="<?php echo $item->setup_fee; ?>" />
				<input type="hidden" class="calc-unit-setup-fee-discount-amount items_unit_setup_fee_discount_amount" data-item-id="<?php echo $item->id; ?>" id="item_<?php echo $item->id; ?>_unit_setup_fee_discount_amount" value="<?php echo $item->setup_fee_discount_amount; ?>" />
				<?php if ($item->monthly_price_one_time_discount_amount > 0 && $item->monthly_price_one_time_discount_amount != $item->monthly_price_recurring_discount_amount) : ?>
					<p class="unit-column discount" id="item_<?php echo $item->id; ?>_unit_monthly_price_text_discount">$<?php echo number_format($item->monthly_price - $item->monthly_price_one_time_discount_amount, 2); ?></p>
					<p class="unit-description discount" id="item_<?php echo $item->id; ?>_unit_monthly_price_text_discount_monthly">first month</p>
					<p class="unit-column<?php echo ($item->monthly_price_recurring_discount_amount > 0) ? ' ' . __('discount') : ''; ?>" id="item_<?php echo $item->id; ?>_unit_monthly_price_text">$<?php echo number_format($item->monthly_price - $item->monthly_price_recurring_discount_amount, 2); ?></p>
					<p class="unit-description<?php echo ($item->monthly_price_recurring_discount_amount > 0) ? ' discount' : ''; ?>">monthly</p>
				<?php elseif ($item->monthly_price > 0) : ?>
					<p class="unit-column discount" id="item_<?php echo $item->id; ?>_unit_monthly_price_text_discount" style="display: none;">$<?php echo number_format($item->monthly_price - $item->monthly_price_one_time_discount_amount, 2); ?></p>
					<p class="unit-description discount" id="item_<?php echo $item->id; ?>_unit_monthly_price_text_discount_monthly" style="display: none;">first month</p>
					<p class="unit-column<?php echo ($item->monthly_price_recurring_discount_amount > 0) ? ' ' . __('discount') : ''; ?>" id="item_<?php echo $item->id; ?>_unit_monthly_price_text">$<?php echo number_format($item->monthly_price - $item->monthly_price_recurring_discount_amount, 2); ?></p>
					<p class="unit-description<?php echo ($item->monthly_price_recurring_discount_amount > 0) ? ' ' . __('discount') : ''; ?>">monthly</p>
				<?php endif; ?>
				<?php if ($item->setup_fee > 0) : ?>
					<p class="unit-column<?php echo ($item->setup_fee_discount_amount > 0) ? ' ' . __('discount') : ''; ?>" id="item_<?php echo $item->id; ?>_unit_setup_fee_text">$<?php echo number_format($item->setup_fee - $item->setup_fee_discount_amount, 2); ?></p>
					<p class="unit-description<?php echo ($item->setup_fee_discount_amount > 0) ? ' ' . __('discount') : ''; ?>" id="item_<?php echo $item->id; ?>_unit_setup_fee_text_setup">setup</p>
				<?php endif; ?>
			</td>
			<td align="right" valign="top" class="subtotal-column">
				<?php if ($item->monthly_price_one_time_discount_subtotal > 0 && $item->monthly_price_one_time_discount_subtotal != $item->monthly_price_recurring_discount_subtotal) : ?>
					<p class="subtotal-column discount" id="item_<?php echo $item->id; ?>_subtotal_monthly_price_text_discount">$<?php echo number_format($item->monthly_price_subtotal - $item->monthly_price_one_time_discount_subtotal, 2); ?></p>
					<p class="unit-description discount">first month</p>
					<p class="subtotal-column<?php echo ($item->monthly_price_recurring_discount_subtotal > 0) ? ' ' . __('discount') : ''; ?>" id="item_<?php echo $item->id; ?>_subtotal_monthly_price_text_discount">$<?php echo number_format($item->monthly_price_subtotal - $item->monthly_price_recurring_discount_subtotal, 2); ?></p>
					<p class="unit-description<?php echo ($item->monthly_price_recurring_discount_subtotal > 0) ? ' ' . __('discount') : ''; ?>"><?php echo __('monthly'); ?></p>
				<?php elseif ($item->monthly_price_subtotal > 0) : ?>
					<p class="subtotal-column discount" id="item_<?php echo $item->id; ?>_subtotal_monthly_price_text_discount" style="display: none;">$<?php echo number_format($item->monthly_price_subtotal - $item->monthly_price_one_time_discount_subtotal, 2); ?></p>
					<p class="unit-description discount" style="display: none;">first month</p>
					<p class="subtotal-column<?php echo ($item->monthly_price_recurring_discount_subtotal > 0) ? ' ' . __('discount') : ''; ?>" id="item_<?php echo $item->id; ?>_subtotal_monthly_price_text">$<?php echo number_format($item->monthly_price_subtotal - $item->monthly_price_recurring_discount_subtotal, 2); ?></p>
					<p class="unit-description<?php echo ($item->monthly_price_recurring_discount_subtotal > 0) ? ' ' . __('discount') : ''; ?>"><?php echo __('monthly'); ?></p>
				<?php endif; ?>
				<?php if ($item->setup_fee_subtotal > 0) : ?>
					<p class="subtotal-column<?php echo ($item->setup_fee_discount_subtotal > 0) ? ' ' . __('discount') : ''; ?>" id="item_<?php echo $item->id; ?>_subtotal_setup_fee_text">$<?php echo number_format($item->setup_fee_subtotal - $item->setup_fee_discount_subtotal, 2); ?></p>
					<p class="unit-description<?php echo ($item->setup_fee_discount_subtotal > 0) ? ' ' . __('discount') : ''; ?>"><?php echo __('setup'); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach ?>
	</table>
</div>
<br clear="all" />
<div class="cart-total grid_21 prefix_1">
		<div class="coupon_container">
		<?php if (empty($coupons)) { ?>
			<input type="text" class="coupon_code" id="coupon_code" placeholder="Coupon Code" value="<?php echo @$coupon_code; ?>" /> <input type="button" id="apply_coupon" class="default_button" onclick="return false;" value="Apply" />
		<?php } else { ?>
			<div class="applied_coupons">
				<p><?php echo __n('Applied Coupon', 'Applied Coupons', count($coupons)); ?>: 
<?php
			$i = 0;
			foreach ($coupons as $coupon) {
				if ($i > 1) {
					echo ', ';
				}
				echo $coupon->coupon->coupon_code; ?>
					(<a href="#" class="remove_coupon" data-coupon-code="<?php echo $coupon->coupon->coupon_code; ?>"><?php echo __('Remove'); ?></a>)
<?php
				$i++;
			} ?>
				</p>
			</div>
	<?php } ?>
		</div>
	<input type="hidden" id="monthly_one_time_total_value" value="<?php echo $monthly_one_time_total; ?>" />
	<input type="hidden" id="monthly_recurring_total_value" value="<?php echo $monthly_recurring_total; ?>" />
	<input type="hidden" id="setup_fee_total_value" value="<?php echo $setup_fee_total; ?>" />
	<?php if ($monthly_one_time_total != $monthly_recurring_total) : ?>
		<p class="cart-total-summary" id="monthly_one_time_total_p"><span class="total-text"><?php echo __('Total first month'); ?>:</span> <span class="total-value" id="monthly_one_time_total_text">$<?php echo number_format($monthly_one_time_total, 2); ?></span></p>
		<p class="cart-total-summary"><span class="total-text"><?php echo __('Total monthly'); ?>:</span> <span class="total-value" id="monthly_recurring_total_text">$<?php echo number_format($monthly_recurring_total, 2); ?></span></p>
	<?php else : ?>
		<p class="cart-total-summary" id="monthly_one_time_total_p" style="display: none;"><span class="total-text"><?php echo __('Total first month'); ?>:</span> <span class="total-value" id="monthly_one_time_total_text">$<?php echo number_format($monthly_one_time_total, 2); ?></span></p>
		<p class="cart-total-summary"><span class="total-text"><?php echo __('Total monthly'); ?>:</span> <span class="total-value" id="monthly_recurring_total_text">$<?php echo number_format($monthly_recurring_total, 2); ?></span></p>
	<?php endif; ?>
	<?php if ($setup_fee_total > 0) : ?>
		<p class="cart-total-summary"><span class="total-text"><?php echo __('Total setup'); ?>:</span> <span class="total-value" id="setup_fee_total_text">$<?php echo number_format($setup_fee_total, 2); ?></span></p>
	<?php endif; ?>
	<p class="grand-total"><span class="total-value"><?php echo __('Total %s due today', 'USD'); ?>:</span> <span class="grand-total-value" id="total_due_today_text">$<?php echo number_format($total_due_today, 2); ?></span></p>
</div>
<div class="continue-to-checkout">
	<p class="proceed-to-checkout">
		<input type="button" onclick="window.location.href='/';" id="add-another-server" class="button_add" value="<?php echo __('Add Another Server'); ?>" />
		&nbsp; &nbsp;
		<input type="submit" id="proceed-to-checkout" class="button_proceed proceed-to-checkout" value="<?php echo __('Proceed to Checkout'); ?>" />
	</p>
</div>
</form>

<div id="modal_placeholder"></div>
