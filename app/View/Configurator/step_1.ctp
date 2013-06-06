<div class="header page-header grid_21 prefix_3 alpha omega">
	<div class="header-logo">
		<h2 class="register"><?php echo __('Configure'); ?></h2>
	</div>
</div>
<br clear="all" />
<?php echo $this->Session->flash(); ?>
<form id="configure_form" action="/configure/2/<?php echo $service_plan_id; ?><?php echo (!empty($cart_item_id)) ? '/' . $cart_item_id : ''; ?>" method="POST">
<input type="hidden" name="service_plan_id" value="<?php echo $service_plan_id; ?>" />
<script type="text/javascript">
	monthly_price = '<?php echo $monthly_price; ?>';
	setup_fee = '<?php echo $setup_fee; ?>';
	service_plan_id = '<?php echo $service_plan_id; ?>';
</script>
<div id="tabs" class="grid_24">
	<ul class="clearfix">
	<?php
	$i = 0;
	foreach ($upgrade_groups as $upgrade_group_id => $upgrade_group) :
		if (!isset($first_upgrade_group_id)) {
			$first_upgrade_group_id = $i;
		}
		?>
		<li><a href="#tab-<?php echo $i; ?>" class="tab-link" onmouseup="update_tab_history(this);" id="tab-<?php echo $i; ?>-link"><?php echo $upgrade_group->name; ?></a></li>
		<?php
		$i++;
	endforeach; ?>
	</ul>
	<div class="grid_16 alpha tab-container">
		<?php
		$i =  0;
		foreach ($upgrade_groups as $upgrade_group_id => $upgrade_group) :
			?>
			<div id="tab-<?php echo $i; ?>" class="pane">
				<?php foreach ($upgrade_group->upgrades as $upgrade_id => $upgrade) :
					$item_class = 'item';
					?>
					<script type="text/javascript">
						upgrades[<?php echo $upgrade_id; ?>] = [];
					</script>
					<div class="box upgrade-group">
						<div class="upgrade-group-header">
							<h3><?php echo $upgrade->name; ?></h3>
						</div>
						<?php if (!empty($upgrade->description)) : ?>
							<p class="description"><?php echo $upgrade->description; ?></p>
						<?php endif; ?>
						<div class="upgrade-group-items">
							<?php if (count($upgrade->options)) : ?>
								<?php foreach ($upgrade->options as $upgrade_option_id => $upgrade_option) :
									if ((!isset($option_group) || $upgrade_option->option_group->id != $option_group->id) && $upgrade_option->option_group->id != 0) {
										$option_group = $upgrades[$upgrade_id]->option_groups[$upgrade_option->option_group->id];
										if (!empty($option_group->image_url)) : ?>
											<br clear="all" />
											<div class="option_group_icon alpha clearfix" style="float: left;">
												<img src="<?php echo $option_group->image_url; ?>" />
											</div>
											<?php
											$item_class = 'item item-move-left';
										else: ?>
											<p class="option-group"><?php echo $option_group->name; ?></p>
											<?php
										endif;
									}
									?>
									<div
										id="div-upgrade-option-<?php echo $upgrade_option_id; ?>"
										class="<?php echo $item_class; ?>"
										data-sidebar-id="sidebar-<?php echo $upgrade_id; ?>"
										data-monthly-price="<?php echo $upgrade_option->monthly_price; ?>"
										data-monthly-price-discount-type="<?php echo empty($upgrade_option->monthly_price_discount_type) ? '0' : $upgrade_option->monthly_price_discount_type; ?>"
										data-monthly-price-discount-amount="<?php echo empty($upgrade_option->monthly_price_discount_amount) ? '0' : $upgrade_option->monthly_price_discount_amount; ?>"
										data-monthly-price-one-time-discount-amount="<?php echo empty($upgrade_option->monthly_price_one_time_discount_amount) ? '0' : $upgrade_option->monthly_price_one_time_discount_amount; ?>"
										data-monthly-price-recurring-discount-amount="<?php echo empty($upgrade_option->monthly_price_recurring_discount_amount) ? '0' : $upgrade_option->monthly_price_recurring_discount_amount; ?>"
										data-coupon-id="<?php echo $upgrade_option->coupon_id; ?>"
										>
										<script type="text/javascript">
											<?php if (!empty($upgrade->default_upgrade_option_id) && $upgrade->default_upgrade_option_id == $upgrade_option_id) : ?>
												upgrades[<?php echo $upgrade_id; ?>]['selected'] = '<?php echo $upgrade_option_id; ?>';
											<?php endif; ?>
											upgrades[<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>] = [];
											upgrades[<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]['monthly_price'] = '<?php echo $upgrade_option->monthly_price; ?>';
											upgrades[<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]['monthly_price_discount_amount'] = '<?php echo empty($upgrade_option->monthly_price_discount_amount) ? '0' : $upgrade_option->monthly_price_discount_amount; ?>';
											upgrades[<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]['monthly_price_one_time_discount_amount'] = '<?php echo empty($upgrade_option->monthly_price_one_time_discount_amount) ? '0' : $upgrade_option->monthly_price_one_time_discount_amount; ?>';
											upgrades[<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]['monthly_price_recurring_discount_amount'] = '<?php echo empty($upgrade_option->monthly_price_recurring_discount_amount) ? '0' : $upgrade_option->monthly_price_recurring_discount_amount; ?>';
											upgrades[<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]['setup_fee'] = '<?php echo $upgrade_option->setup_fee; ?>';
											upgrades[<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]['setup_fee_discount_amount'] = '<?php echo empty($upgrade_option->setup_fee_discount_amount) ? '0' : $upgrade_option->setup_fee_discount_amount; ?>';
											<?php if (!empty($upgrade_option->coupons)) : ?>
												upgrades[<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]['coupons'] = [];
												<?php foreach ($upgrade_option->coupons as $coupon_id => $coupon) : ?>
													upgrades[<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]['coupons']['<?php echo $coupon_id; ?>'] = [];
													upgrades[<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]['coupons']['<?php echo $coupon_id; ?>']['one_time'] = '<?php echo $coupon->one_time; ?>';
													upgrades[<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]['coupons']['<?php echo $coupon_id; ?>']['recurring'] = '<?php echo $coupon->recurring; ?>';
												<?php endforeach; ?>
											<?php endif; ?>
										</script>
										<label for="upgrades[<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]">
											<input type="radio" id="upgrades[<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]" name="upgrades[<?php echo $upgrade_id; ?>]" onchange="old_upgrade_option_id='upgrades[<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]'; update_price();" value="upgrades[<?php echo $upgrade_id; ?>][<?php echo $upgrade_option_id; ?>]" <?php echo (!empty($upgrade->default_upgrade_option_id) && $upgrade->default_upgrade_option_id == $upgrade_option_id) ? 'checked' : '' ?> />
											<span class="item-name"><?php echo $upgrade_option->name ?></span>
											<?php if (!empty($upgrade_option->description)) : ?>
												(<?php echo $upgrade_option->description; ?>)
											<?php endif ?>
											<?php if (empty($upgrade->default_upgrade_option_id) || $upgrade->default_upgrade_option_id != $upgrade_option_id) : ?>
											<?php
												// not using inline php+html here because of the spaces that it creates
												echo '<span class="upgrade_option_language" id="upgrade_option_language_' . $upgrade_id . '_' . $upgrade_option_id . '">(';
												// monthly price
												if (($upgrade_option->monthly_price - $upgrade_option->monthly_price_discount_amount) >= ($upgrade->options[$upgrade->default_upgrade_option_id]->monthly_price - $upgrade->options[$upgrade->default_upgrade_option_id]->monthly_price_discount_amount)) {
													$has_monthly_price = true;
													echo '$' . number_format($upgrade_option->monthly_price - ($upgrade->options[$upgrade->default_upgrade_option_id]->monthly_price - $upgrade->options[$upgrade->default_upgrade_option_id]->monthly_price_discount_amount), 2) . '/' . __('mo');
													if ($upgrade_option->monthly_price_discount_amount > 0) {
														if ($upgrade_option->monthly_price_discount_amount == $upgrade_option->monthly_price_recurring_discount_amount) {
															echo ' - $' . number_format($upgrade_option->monthly_price_discount_amount, 2) . '/' . __('mo discount','month discount') . ' = <span class="discount">$' . number_format($upgrade_option->monthly_price - ($upgrade->options[$upgrade->default_upgrade_option_id]->monthly_price - $upgrade->options[$upgrade->default_upgrade_option_id]->monthly_price_discount_amount) - $upgrade_option->monthly_price_discount_amount, 2) . '/' . __('mo') . '</span>';
														}
														else {
															if ($upgrade_option->monthly_price_one_time_discount_amount > 0) {
																echo ' - $' . number_format($upgrade_option->monthly_price_one_time_discount_amount, 2);
															}
															if ($upgrade_option->monthly_price_recurring_discount_amount > 0) {
																echo ' - $' . number_format($upgrade_option->monthly_price_recurring_discount_amount, 2) . '/' . __('mo discount','month discount');
															}
															echo ' = <span class="discount">';
															if ($upgrade_option->monthly_price_one_time_discount_amount > 0) {
																echo '$' . number_format($upgrade_option->monthly_price - ($upgrade->options[$upgrade->default_upgrade_option_id]->monthly_price - $upgrade->options[$upgrade->default_upgrade_option_id]->monthly_price_one_time_discount_amount) - $upgrade_option->monthly_price_one_time_discount_amount, 2) . ' ' . __('first month') . '; ';
															}
															echo ' $' . number_format($upgrade_option->monthly_price - ($upgrade->options[$upgrade->default_upgrade_option_id]->monthly_price - $upgrade->options[$upgrade->default_upgrade_option_id]->monthly_price_recurring_discount_amount) - $upgrade_option->monthly_price_recurring_discount_amount, 2) . '/' . __('mo') . '</span>';
														}
													}
												}
												elseif (($upgrade_option->monthly_price - $upgrade_option->monthly_price_discount_amount) < ($upgrade->options[$upgrade->default_upgrade_option_id]->monthly_price - $upgrade->options[$upgrade->default_upgrade_option_id]->monthly_price_discount_amount)) {
													$has_monthly_price = true;
													echo ' - $' . number_format(($upgrade->options[$upgrade->default_upgrade_option_id]->monthly_price - $upgrade->options[$upgrade->default_upgrade_option_id]->monthly_price_discount_amount) - ($upgrade_option->monthly_price - $upgrade_option->monthly_price_discount_amount), 2) . '/' . __('mo');
												}
												// setup fee
												if (($upgrade_option->setup_fee - $upgrade_option->setup_fee_discount_amount) > ($upgrade->options[$upgrade->default_upgrade_option_id]->setup_fee - $upgrade->options[$upgrade->default_upgrade_option_id]->setup_fee_discount_amount)) {
													$has_setup_fee = true;
													if (!empty($has_monthly_price)) {
														echo ' + ';
													}
													if ($upgrade_option->setup_fee_discount_amount > 0) {
														echo '<span class="discount">$' . number_format(($upgrade_option->setup_fee - $upgrade_option->setup_fee_discount_amount) - ($upgrade->options[$upgrade->default_upgrade_option_id]->setup_fee - $upgrade->options[$upgrade->default_upgrade_option_id]->setup_fee_discount_amount), 2) . ' ' . __('setup') . '</span>';
													}
													else {
														echo '$' . number_format(($upgrade_option->setup_fee - $upgrade_option->setup_fee_discount_amount) - ($upgrade->options[$upgrade->default_upgrade_option_id]->setup_fee - $upgrade->options[$upgrade->default_upgrade_option_id]->setup_fee_discount_amount), 2) . ' ' . __('setup');
													}
												}
												elseif (($upgrade_option->setup_fee - $upgrade_option->setup_fee_discount_amount) < ($upgrade->options[$upgrade->default_upgrade_option_id]->setup_fee - $upgrade->options[$upgrade->default_upgrade_option_id]->setup_fee_discount_amount)) {
													$has_setup_fee = true;
													if (!empty($upgrade_option->setup_fee_discount_amount)) {
														echo ' - $' . number_format(($upgrade->options[$upgrade->default_upgrade_option_id]->setup_fee - $upgrade->options[$upgrade->default_upgrade_option_id]->setup_fee_discount_amount) - ($upgrade_option->setup_fee - $upgrade_option->setup_fee_discount_amount), 2) . ' ' . __('setup');
													}
													else {
														echo ' - $' . number_format(($upgrade->options[$upgrade->default_upgrade_option_id]->setup_fee - $upgrade->options[$upgrade->default_upgrade_option_id]->setup_fee_discount_amount) - ($upgrade_option->setup_fee - $upgrade_option->setup_fee_discount_amount), 2) . ' ' . __('setup');
													}
												}
												echo ')</span>';
												
											?>
											<?php else: ?>
												<span class="upgrade_option_language" id="upgrade_option_language_<?php echo $upgrade_id; ?>_<?php echo $upgrade_option_id; ?>">(<?php echo __('Included in price'); ?>)</span>
											<?php endif ?>
										</label>
									</div>
								<?php
								endforeach; // upgrade options
								?>
							<?php endif; ?>
						</div>
					</div>
					<?php
					unset($option_group);
				endforeach; // upgrades
				?>
				<?php if (($i + 1) == count($upgrade_groups)) : ?>
					<div class="tab-next"><button class="tab-next button_add" onmouseup="$('#configure_form').submit();"><?php echo __('Next'); ?> &raquo;</button></div>
				<?php else : ?>
					<div class="tab-next"><button class="tab-next button_add" onmouseup="go_to_tab(<?php echo ($i + 1); ?>);"><?php echo __('Next'); ?> &raquo;</button></div>
				<?php endif; ?>
			</div>
			<!-- end box -->
		<?php
		$i++;
		endforeach; // upgrade groups (tabs)
		?>
	</div>
	<div class="next-step grid_5 prefix_1 omega">
		<div class="continue summary">
			<div class="summary-header">
				<h3><?php echo __('Your Summary'); ?></h3>
			</div>
			<input type="hidden" id="monthly_recurring_subtotal_value" value="<?php echo empty($monthly_price_recurring_discount_amount) ? $monthly_price : $monthly_price - $monthly_price_recurring_discount_amount; ?>" />
			<input type="hidden" id="monthly_one_time_subtotal_value" value="<?php echo empty($monthly_price_one_time_discount_amount) ? $monthly_price : $monthly_price - $monthly_price_one_time_discount_amount; ?>" />
			<input type="hidden" id="setup_fee_subtotal_value" value="<?php echo number_format(empty($setup_fee_discount_amount) ? $setup_fee : $setup_fee - $setup_fee_discount_amount, 2); ?>" />
			<div class="summary-price">
				<div id="monthly-price">
					<?php if ($monthly_price_recurring_discount_amount == $monthly_price_one_time_discount_amount) : ?>
						<span id="first_month_discount" class="discount" style="display: none;">$<span id="monthly_one_time_subtotal"><?php echo number_format(empty($monthly_price_one_time_discount_amount) ? $monthly_price : $monthly_price - $monthly_price_one_time_discount_amount, 2); ?></span></span>
						<span id="first_month_monthly" class="monthly" style="display: none;"><?php echo __('%s first Month', 'USD'); ?></span>
						<span class="<?php echo ($monthly_price_recurring_discount_amount > 0) ? 'discount ' : ''; ?>">$<span id="monthly_recurring_subtotal"><?php echo number_format(empty($monthly_price_one_time_discount_amount) ? $monthly_price : $monthly_price - $monthly_price_one_time_discount_amount, 2); ?></span></span>
						<span class="monthly monthly_recurring"><?php echo __('%s per Month', 'USD'); ?></span>
					<?php else: ?>
						<span id="first_month_discount" class="discount">$<span id="monthly_one_time_subtotal"><?php echo number_format(empty($monthly_price_one_time_discount_amount) ? $monthly_price : $monthly_price - $monthly_price_one_time_discount_amount, 2); ?></span></span>
						<span id="first_month_monthly" class="monthly"><?php echo __('%s first Month', 'USD'); ?></span>
						<span class="<?php echo ($monthly_price_recurring_discount_amount > 0) ? 'discount ' : ''; ?>monthly_recurring_header">$<span id="monthly_recurring_subtotal"><?php echo number_format(empty($monthly_price_recurring_discount_amount) ? $monthly_price : $monthly_price - $monthly_price_recurring_discount_amount, 2); ?></span></span>
						<span class="monthly monthly_recurring"><?php echo __('%s per Month', 'USD'); ?></span>
					<?php endif; ?>
				</div>
				<div id="setup-fee">
					<?php if (empty($setup_fee_discount_amount)) : ?>
						$<span id="setup_fee_subtotal"><?php echo number_format($setup_fee, 2); ?></span> USD <?php echo __('Setup'); ?>
					<?php else: ?>
						<span class="discount"><?php echo __('Discounted'); ?><br />$<span id="setup_fee_subtotal"><?php echo number_format($setup_fee - $setup_fee_discount_amount, 2); ?></span> USD <?php echo __('Setup'); ?></span>
					<?php endif; ?>
				</div>
				<input type="submit" class="default_button" value="<?php echo (!empty($cart_item_id)) ? __('Update Item') : __('Add to Cart'); ?>" />
			</div>
			<div class="summary-config">
				<h4><?php echo __('Your Configuration'); ?></h4>
				<div class="config-item clearfix"><span class="name"><?php echo __('Base Price'); ?></span><span class="price">$<?php echo number_format($monthly_price_base, 2); ?></span></div>
				<?php if (!empty($monthly_base_price_one_time_discount)) : ?>
					<div class="config-item clearfix"><span class="name"></span><span class="price discount">-$<?php echo number_format($monthly_base_price_one_time_discount, 2); ?></span></div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
<script>
$("#tabs").tabs();
if (typeof($.bbq.getState('tab')) == 'undefined') {
	$.bbq.pushState({ 'tab' : <?php echo $first_upgrade_group_id; ?> });
}
$('input.highlight-error').effect('highlight', { color: '#f7f493' }, 1250);
</script>
</form>
<script type="text/javascript">
$('button.tab-next').on('click', function () {
	return false
})
</script>
