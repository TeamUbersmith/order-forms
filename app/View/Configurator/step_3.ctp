<div class="header page-header grid_21 prefix_3 alpha omega">
	<div class="header-logo">
		<h2 class="register">Account & Payment</h2>
	</div>
	<div class="config steps">
		<p class="step-1"><a href="<?php echo $this->Html->url(array('controller' => 'configurator', 'action' => 'configure', 'cart'));?>"><span class="step-bold">Step 1</span> Cart</a></p>
		<p class="step-2 step-selected"><span class="step-bold">Step 2</span> Account & Payment</p>
		<p class="step-3"><span class="step-bold">Step 3</span> Confirm</p>
	</div>
</div>
<br clear="all" />
<?php echo SessionHelper::flash(); ?>
<p class="order_summary prefix_1">Account Info</p>
<div class="prefix_1">
	<label for="new_customer_radio">
		<p class="new-or-existing-list-items"><input type="radio" id="new_customer_radio" name="new_or_existing" value="new" <?php echo (!empty($new_client_info)) ? 'checked' : ''; ?> onchange="show_new_or_existing_div();" /> <span>I am a new customer</span></p>
	</label>
	<label for="existing_customer_radio">
		<p class="new-or-existing-list-items"><input type="radio" id="existing_customer_radio" name="new_or_existing" value="existing" <?php echo (empty($new_client_info)) ? 'checked' : ''; ?> onchange="show_new_or_existing_div();" /> <span>I have an existing account</span></p>
	</label>
	<div id="existing-customer" <?php echo (!empty($new_client_info)) ? 'style="display: none;"' : ''; ?>>
		<form action="<?php echo $this->Html->url(array('controller' => 'configurator', 'action' => 'configure', 4));?>" method="POST">
			<p class="user-pass">Username</p>
			<p class="user-pass-input"><input class="user-pass" type="text" name="login" class="login" placeholder="1234" /></p>
			<p class="user-pass">Password</p>
			<p class="user-pass-input"><input class="user-pass" type="password" name="password" class="login" /></p>
			<input type="hidden" name="logging_in" />
			<p><button class="button_proceed" type="submit">Login Now<img style="position: relative; bottom: 2px; left: 14px;" src="/img/arrow_right.png" /></button></p>
		</form>
	</div>
	<div id="new-customer" <?php echo (empty($new_client_info)) ? 'style="display: none;"' : ''; ?>>
		<form action="<?php echo $this->Html->url(array('controller' => 'configurator', 'action' => 'configure', 4));?>" method="POST">
			<div class="new-account-info grid_22 alpha">
				<div class="grid_16 clearfix alpha omega">
					<p class="user-pass grid_8 alpha">First Name <sup>*</sup></p>
					<p class="user-pass grid_8 omega">Last Name <sup>*</sup></p>
					<div class="user-pass-input grid_8 alpha"><input type="text" id="first_name" name="first_name" class="new-user-info user-pass grid_7 alpha omega<?php echo (@in_array('first_name', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" value="<?php echo htmlentities(@$new_client_info['first_name']); ?>" /></div>
					<div class="user-pass-input grid_8 omega"><input type="text" id="last_name" name="last_name" class="new-user-info user-pass grid_7 alpha omega<?php echo (@in_array('last_name', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" value="<?php echo htmlentities(@$new_client_info['last_name']); ?>" /></div>
					<div class="grid_16 clearfix alpha omega">
						<p class="user-pass grid_8 alpha">Address <sup>*</sup></p>
						<p class="user-pass grid_8 omega">City <sup>*</sup></p>
						<div class="user-pass-input grid_8 alpha"><input type="text" id="address" name="address" class="new-user-info user-pass grid_7 alpha<?php echo (@in_array('address', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" onchange="update_billing_info(this);" value="<?php echo htmlentities(@$new_client_info['address']); ?>" /></div>
						<div class="user-pass-input grid_8 omega"><input type="text" id="city" name="city" class="new-user-info user-pass grid_7 alpha<?php echo (@in_array('city', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" onchange="update_billing_info(this);" value="<?php echo htmlentities(@$new_client_info['city']); ?>" /></div>
					</div>
					<p class="user-pass grid_8 alpha">State/Province <sup>*</sup></p>
					<p class="user-pass grid_8 omega">Postal Code <sup>*</sup></p>
					<div class="user-pass-input grid_8 alpha">
						<select name="state" id="state" class="new-user-info user-pass new-account-select<?php echo (@in_array('state', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" style="display: <?php echo (empty($new_client_info['country']) || $new_client_info['country'] == 'US') ? 'block' : 'none'; ?>">
							<?php foreach ($us_states as $code => $state) : ?>
								<option value="<?php echo htmlentities($code); ?>"<?php echo (@$new_client_info['state'] == $code) ? ' selected' : ''; ?>><?php echo htmlentities($state); ?></option>
							<?php endforeach; ?>
						</select>
						<select name="province" id="province" class="new-user-info user-pass new-account-select<?php echo (@in_array('province', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" style="display: <?php echo (!empty($new_client_info['country']) && $new_client_info['country'] == 'CA') ? 'block' : 'none'; ?>">
							<?php foreach ($ca_provinces as $code => $province) : ?>
								<option value="<?php echo htmlentities($code); ?>"<?php echo (@$new_client_info['province'] == $code) ? ' selected' : ''; ?>><?php echo htmlentities($province); ?></option>
							<?php endforeach; ?>
						</select>
						<input type="text" id="other_state" name="other_state" class="new-user-info user-pass grid_7 alpha<?php echo (@in_array('other_state', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" value="<?php echo htmlentities(@$new_client_info['other_state']); ?>" style="display: <?php echo (!empty($new_client_info['country']) && $new_client_info['country'] != 'US' && $new_client_info['country'] != 'CA') ? 'block' : 'none'; ?>" />
					</div>
					<div class="user-pass-input grid_8 omega"><input type="text" name="postal_code" id="postal_code" class="new-user-info user-pass grid_6 alpha<?php echo (@in_array('postal_code', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" onchange="update_billing_info(this);" value="<?php echo htmlentities(@$new_client_info['postal_code']); ?>" /></div>
					<p class="user-pass grid_16 alpha omega">Country <sup>*</sup></p>
					<div class="user-pass-input grid_16 alpha omega">
						<select id="country" name="country" onchange="update_state_field(this);" class="new-user-info user-pass new-account-select alpha<?php echo (@in_array('country', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>">
							<?php foreach ($countries as $code => $country) : ?>
							<option value="<?php echo htmlentities($code); ?>"<?php echo (@$new_client_info['country'] == $code) ? ' selected' : ''; ?>><?php echo htmlentities($country); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<p class="user-pass grid_8 alpha">Company Name</p>
					<p class="user-pass grid_8 omega">Phone Number <sup>*</sup></p>
					<div class="user-pass-input grid_8 alpha"><input type="text" name="company_name" class="new-user-info user-pass grid_7 alpha" value="<?php echo htmlentities(@$new_client_info['company_name']); ?>" /></div>
					<div class="user-pass-input grid_8 omega"><input type="text" name="phone_number" id="phone_number" class="new-user-info user-pass grid_4 alpha<?php echo (@in_array('phone_number', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" onchange="update_billing_info(this);" value="<?php echo htmlentities(@$new_client_info['phone_number']); ?>" /></div>
					<p class="user-pass grid_8 alpha">Email Address <sup>*</sup></p>
					<p class="user-pass grid_8 omega">Password (length between 6-255) <sup>*</sup></p>
					<div class="user-pass-input grid_8 alpha"><input type="text" id="email" name="email" class="new-user-info user-pass grid_7 alpha<?php echo (@in_array('email', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" value="<?php echo htmlentities(@$new_client_info['email']); ?>" /></div>
					<div class="user-pass-input grid_8 omega"><input type="password" id="password" name="password" class="new-user-info user-pass grid_7 alpha<?php echo (@in_array('password', $highlight_error_inputs)) ? ' highlight-error' : ''; ?>" /></div>
				</div>
			</div>
			<br clear="all" />
			<div class="grid_22 alpha" style="padding-top: 15px;">
		<input type="hidden" name="new_client" />
		<button class="button_proceed button_continue" type="submit">Continue<img style="position: relative; bottom: 2px; left: 14px;" src="/img/arrow_right.png"></button>
            </div>


            <br clear="all" />
		</form>
	</div>
</div>
<script type="text/javascript">
	$('input.highlight-error').effect('highlight', { color: '#f7f493' }, 1250);
</script>
