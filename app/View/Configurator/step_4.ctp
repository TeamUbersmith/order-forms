<div class="header page-header grid_21 prefix_3 alpha omega">
	<div class="header-logo">
		<h2 class="register"><?php echo __('Account & Payment'); ?></h2>
	</div>
	<div class="config steps">
		<p class="step-1"><a href="<?php echo $this->Html->url(array('controller' => 'configurator', 'action' => 'configure', 'cart'));?>"><span class="step-bold"><?php echo __('Step %d', 1); ?></span> <?php echo __('Cart'); ?></a></p>
		<p class="step-2 step-selected"><span class="step-bold"><?php echo __('Step %d', 2); ?></span> <?php echo __('Account & Payment'); ?></p>
		<p class="step-3"><span class="step-bold"><?php echo __('Step %d', 3); ?></span> <?php echo __('Confirm'); ?></p>
	</div>
</div>
<br clear="all" />
<?php echo $this->Session->flash(); ?>
<form action="<?php echo $this->Html->url(array('controller' => 'configurator', 'action' => 'configure', 5));?>" method="POST">
<p class="order_summary prefix_1" style="float: left;"><?php echo __('Account Info'); ?></p>
<div class="continue billing-info-continue billing-info-continue-top">
	<input class="button_proceed" type="submit" value="<?php echo __('Continue'); ?>" />
</div>
<br clear="all" />
<div class="grid_24 alpha omega">
	<div class="prefix_1 grid_8 alpha">
		<p class="account-info"><?php echo htmlentities(@$client_info['full_name']); ?></p>
		<p class="account-info"><?php echo htmlentities(@$client_info['address']); ?></p>
		<p class="account-info"><?php echo htmlentities(@$client_info['city']); ?>, <?php echo htmlentities(@$client_info['state']); ?> <?php echo htmlentities(@$client_info['postal_code']); ?></p>
		<p class="account-info"><?php echo htmlentities(@$client_info['country']); ?></p>
		<p class="account-info"><?php echo __('Phone'); ?>: <?php echo htmlentities(@$client_info['phone_number']); ?></p>
		<?php if (!empty($new_client_info)) : ?>
			<p class="edit-new-client-info"><a href="<?php echo $this->Html->url(array('controller' => 'configurator', 'action' => 'configure', 'account-info'));?>"><?php echo __('Edit'); ?></a></p>
		<?php endif; ?>
	</div>
	<div class="grid_15 omega">
		<div class="box new-credit-card">
			<p class="order_summary credit-cards-header"><?php echo __('Credit Cards'); ?></p>
			<?php if (!empty($payment_methods)) : ?>
				<?php foreach ($payment_methods as $payment_method) : ?>
					<div class="select-payment-method">
						<div class="card-or-bank-account-image card-or-bank-account-image-<?php echo $payment_method->payment_type; ?> <?php echo $payment_method->payment_type; ?>-<?php echo $payment_method->account_type; ?>"></div>
						<div id="div_payment_method_<?php echo $payment_method->id; ?>" class="card-or-bank-details">
							<label for="payment_method_<?php echo $payment_method->id; ?>">
								<?php if ($payment_method->payment_type == 'cc') : ?>
									<p><input id="payment_method_<?php echo $payment_method->id; ?>" type="radio" name="payment_method" onchange="select_payment_method(<?php echo $payment_method->id; ?>);" value="<?php echo $payment_method->id; ?>" /> <span class="small-margin"><?php echo $payment_method->card_type_full; ?> <b><?php echo __('ending in %s', $payment_method->text); ?></b></span></p>
									<p><span><?php echo __('Name'); ?> <b><?php echo htmlentities($payment_method->full_name); ?></b></span></p>
									<p><span><?php echo __('Expires'); ?> <b><?php echo htmlentities($payment_method->expiration_month); ?>/<?php echo htmlentities($payment_method->expiration_year); ?></b></span></p>
								<?php elseif ($payment_method->payment_type == 'ach') : ?>
									<p><input id="payment_method_<?php echo $payment_method->id; ?>" type="radio" name="payment_method" onchange="select_payment_method(<?php echo $payment_method->id; ?>);" value="<?php echo $payment_method->id; ?>" /> <span class="small-margin"><?php echo $payment_method->account_type_full; ?> <?php echo __('Account'); ?> <b><?php echo __('ending in %s', $payment_method->text); ?></b></span></p>
									<p><span><?php echo __('Name'); ?> <b><?php echo htmlentities($payment_method->full_name); ?></b></span></p>
									<p><span><?php echo __('Bank'); ?> <b><?php echo htmlentities($payment_method->bank); ?></b></span></p>
								<?php endif; ?>
							</label>
						</div>
						<br clear="all" />
					</div>
				<?php endforeach; ?>
				<div class="new-card-info-break"></div>
			<?php endif; ?>
			<div>
				<div class="select-payment-method">
					<div id="div_payment_method_new" class="card-or-bank-details card-or-bank-details-new<?php echo (empty($payment_methods)) ? ' card-or-bank-details-only' : ''; ?><?php echo (!empty($new_card_info) || empty($payment_methods)) ? ' selected' : ''; ?>">
						<label for="new_credit_card">
							<p><input id="new_credit_card" type="radio" name="payment_method" onchange="select_payment_method('new');" value="new_credit_card" <?php echo (empty($payment_methods) || !empty($new_card_info)) ? 'checked' : ''; ?> /> <span class="small-margin"><?php echo __('New credit card'); ?></span></p>
						</label>
					</div>
				</div>
				<br clear="all" />
				<div class="new-card-info-grid">
					<div class="grid_20 alpha">
						<div class="grid_14 clearfix alpha omega">
							<p class="user-pass grid_14 alpha omega"><?php echo __('Card Number'); ?> <sup>*</sup></p>
							<div class="user-pass-input grid_14 alpha"><input type="text" name="cc_num" id="cc_num" class="new-user-info user-pass grid_7 alpha<?php echo (@in_array('cc_num', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" value="" /></div>
							<p class="user-pass grid_5 alpha"><?php echo __('Expiration Date'); ?> <sup>*</sup></p>
							<p class="user-pass grid_9 omega"><?php echo __('CVV2'); ?> <sup>*</sup></p>
							<div class="user-pass-input grid_5 alpha">
								<select name="cc_exp_mo" id="cc_exp_mo" class="<?php echo (@in_array('cc_exp_mo', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>">
									<?php for ($i = 1; $i <= 12; $i++): ?>
										<option><?php echo $i; ?></option>
									<?php endfor; ?>
								</select>
								<select name="cc_exp_yr" id="cc_exp_yr" class="<?php echo (@in_array('cc_exp_yr', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>">
									<?php for ($i = date('Y'); $i <= date('Y')+20; $i++): ?>
										<option><?php echo $i; ?></option>
									<?php endfor; ?>
								</select>
							</div>
							<div class="user-pass-input grid_9 omega"><input type="text" name="cc_cvv2" id="cc_cvv2" class="new-user-info user-pass grid_2 alpha omega<?php echo (@in_array('cc_cvv2', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" value="" /></div>
						</div>
					</div>
					<br clear="all" />
					<div id="billing-info-div"<?php echo (empty($payment_methods)) ? ' style="display: block;"' : ''; ?>>
						<p class="billing-info alpha"><?php echo __('Billing Info'); ?></p>
						<p class="use_contact_info" id="use_contact_info_p">
							<label for="use_contact_info" id="use_contact_info_label">
								<input type="checkbox" id="use_contact_info" name="use_client_info" value="1"<?php echo (empty($payment_methods) || empty($new_card_info['use_new_info'])) ? ' checked' : ''; ?> /> <span><?php echo __('Use contact information'); ?></span>
							</label>
						</p>
					</div>
					<div class="grid_22 alpha" id="new-card-info-div">
						<div class="grid_14 clearfix alpha omega">
							<p class="user-pass grid_7 alpha"><?php echo __('First Name'); ?> <sup>*</sup></p>
							<p class="user-pass grid_7 omega"><?php echo __('Last Name'); ?> <sup>*</sup></p>
							<div class="user-pass-input grid_14 alpha omega">
								<div class="user-pass-input grid_7 alpha"><input type="text" name="cc_first" id="cc_first" class="new-user-info user-pass grid_6 alpha<?php echo (@in_array('cc_first', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" value="<?php echo htmlentities(@$new_card_info['cc_first']); ?>" /></div>
								<div class="user-pass-input grid_7 omega"><input type="text" name="cc_last" id="cc_last" class="new-user-info user-pass grid_6 alpha<?php echo (@in_array('cc_last', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" value="<?php echo htmlentities(@$new_card_info['cc_last']); ?>" /></div>
							</div>
							<div class="grid_14 clearfix alpha omega">
								<p class="user-pass grid_7 alpha"><?php echo __('Address'); ?> <sup>*</sup></p>
								<p class="user-pass grid_7 omega"><?php echo __('City'); ?> <sup>*</sup></p>
								<div class="user-pass-input grid_7 alpha"><input type="text" name="cc_address" id="cc_address" class="new-user-info user-pass grid_6 alpha<?php echo (@in_array('cc_address', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" value="<?php echo htmlentities(@$new_card_info['cc_address']); ?>" <?php echo (@$new_card_info['use_contact_info']) ? 'readonly' : ''; ?> /></div>
								<div class="user-pass-input grid_7 omega"><input type="text" name="cc_city" id="cc_city" class="new-user-info user-pass grid_6 alpha<?php echo (@in_array('cc_city', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" value="<?php echo htmlentities(@$new_card_info['cc_city']); ?>" <?php echo (@$new_client_info['use_contact_info']) ? 'readonly' : ''; ?> /></div>
							</div>
							<p class="user-pass grid_7 alpha"><?php echo __('State/Province'); ?> <sup>*</sup></p>
							<p class="user-pass grid_7 omega"><?php echo __('Postal Code'); ?> <sup>*</sup></p>
							<div class="user-pass-input grid_7 alpha">
								<select name="cc_state" id="state" class="new-user-info user-pass new-account-select<?php echo (@in_array('state', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" style="display: <?php echo (empty($new_card_info['cc_country']) || $new_card_info['cc_country'] == 'US') ? 'block' : 'none'; ?>">
									<?php foreach ($us_states as $code => $state) : ?>
										<option value="<?php echo htmlentities($code); ?>"<?php echo (@$new_card_info['cc_state'] == $code) ? ' selected' : ''; ?>><?php echo htmlentities($state); ?></option>
									<?php endforeach; ?>
								</select>
								<select name="cc_province" id="province" class="new-user-info user-pass new-account-select<?php echo (@in_array('province', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" style="display: <?php echo (!empty($new_card_info['cc_country']) && $new_card_info['cc_country'] == 'CA') ? 'block' : 'none'; ?>">
									<?php foreach ($ca_provinces as $code => $province) : ?>
										<option value="<?php echo htmlentities($code); ?>"<?php echo (@$new_card_info['cc_province'] == $code) ? ' selected' : ''; ?>><?php echo htmlentities($province); ?></option>
									<?php endforeach; ?>
								</select>
								<input type="text" id="other_state" name="cc_other_state" class="new-user-info user-pass grid_6 alpha<?php echo (@in_array('cc_other_state', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" value="<?php echo htmlentities(@$new_card_info['cc_other_state']); ?>" style="display: <?php echo (!empty($new_card_info['cc_country']) && $new_card_info['cc_country'] != 'US' && $new_card_info['cc_country'] != 'CA') ? 'block' : 'none'; ?>" />
							</div>
							<div class="user-pass-input grid_7 omega"><input type="text" name="cc_zip" id="cc_zip" class="new-user-info user-pass grid_4 alpha<?php echo (@in_array('cc_zip', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" value="<?php echo htmlentities(@$new_card_info['cc_zip']); ?>" <?php echo (@$new_card_info['use_contact_info']) ? 'readonly' : ''; ?> /></div>
							<p class="user-pass grid_7 alpha"><?php echo __('Country'); ?> <sup>*</sup></p>
							<div class="user-pass-input grid_14 alpha omega">
								<div class="user-pass-input grid_7 alpha">
									<select name="cc_country" onchange="update_state_field(this);" id="cc_country" class="new-user-info user-pass new-account-select alpha<?php echo (@in_array('cc_country', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>">
										<?php foreach ($countries as $code => $country) : ?>
										<option value="<?php echo htmlentities($code); ?>"<?php echo (@$new_card_info['cc_country'] == $code) ? ' selected' : ''; ?>><?php echo htmlentities($country); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<p class="user-pass grid_14 alpha omega"><?php echo __('Phone Number'); ?> <sup>*</sup></p>
							<div class="user-pass-input grid_14 alpha omega"><input type="text" name="cc_phone" id="cc_phone" class="new-user-info user-pass grid_7 alpha omega<?php echo (@in_array('cc_phone', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" value="<?php echo htmlentities(@$new_card_info['cc_phone']); ?>" <?php echo (@$new_card_info['use_contact_info']) ? 'readonly' : ''; ?> /></div>
						</div>
					</div>
					<div class="clear"></div>
				</div>
			</div>
		</div>
		<div class="continue billing-info-continue">
			<input class="button_proceed" type="submit" value="<?php echo __('Continue'); ?>" />
		</div>
	</div>
</div>
</form>
<script type="text/javascript">
	$('input.highlight-error').effect('highlight', { color: '#f7f493' }, 1250);
</script>
