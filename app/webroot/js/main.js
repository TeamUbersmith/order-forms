upgrades = [];

old_upgrade_option_id = '';

is_animating = false;

$(function() {
	$(window).bind('hashchange', history_callback);
	history_callback();
	bind_sidebar_update();
	populate_summary();
	
	$('#apply_coupon').click(function(e) {
		apply_coupon();
	});
	
	$('#add-another-server').click(function(e) {
		location.href='/';
	});
	
	$('#view-msa-inline').click(function(e) {
		e.preventDefault();
		show_msaoverlay_lightbox();
		swfobject.embedSWF('/msa/view', 'msaoverlay_contents', 800, 600, '9.0.0', '/swfobject/expressInstall.swf');
	});

	$('#use_contact_info').on('click',function(e) {
		if ($('#use_contact_info:checked').length > 0) {
			$('#new-card-info-div').hide();
		}
		else {
			$('#new-card-info-div').show();
		}
	});
	
	$('.remove_coupon').on('click', function(event) {
		$.ajax({
			type:  'POST',
			url:   '/ajax/remove/coupon',
			data: {
				'coupon_code' : $(this).data('coupon-code')
			},
			success: function(data) {
				console.debug(data);
				if (data == 'ok') {
					location.reload(true);
				} else {
					alert(data);
				}
			},
			error: function(data) {
				alert('There was a problem removing the coupon, please try again.');
			}
		});	
	});
	
	$('.hostname').on('blur', function(event) {
		update_hostname($(this));
	});
	
	$('.quantity').on('blur', function(event) {
		var elm = $(this);
		var old_quantity = elm.data('old-quantity');
		if (elm.val() == old_quantity) {
			return;
		}
		$.ajax({
			type: 'POST',
			url: '/ajax/update/quantity',
			data: {
				'item_id'  : elm.data('item-id'),
				'quantity' : elm.val()
			},
			success: function(data) {
				elm.data('old-quantity', elm.val());
			}
		});
	});
});

function show_msaoverlay_lightbox(event) {
	if (typeof(event) == 'undefined') {
		event = {}
	}
	if (event.preventDefault) {
		event.preventDefault();
	}
	else {
		event.returnValue = false;
	}
	$('#msaoverlay_container').show();
	$('#msaoverlay').css('left', $(window).scrollLeft() + ($(window).width() / 2) - ($('#msaoverlay').width() / 2));
	$('#msaoverlay').css('top', $(window).scrollTop() + ($(window).height() / 2) - ($('#msaoverlay').height() / 2));
}

$(window).scroll(function () {
	if ($('#msaoverlay_conatiner').css('display') == 'none') {
		return;
	}
	$('#msaoverlay').css('left', $(window).scrollLeft() + ($(window).width() / 2) - ($('#msaoverlay').width() / 2));
	$('#msaoverlay').css('top', $(window).scrollTop() + ($(window).height() / 2) - ($('#msaoverlay').height() / 2));
});

Number.prototype.formatMoney = function(c, d, t){
	var n = this, c = isNaN(c = Math.abs(c)) ? 2 : c, d = d == undefined ? "," : d, t = t == undefined ? "." : t, s = n < 0 ? "-" : "", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
	return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
};

function update_hostname(elm) {
	elm.each(function() {
		var elm = $(this);
		var old_hostname = elm.data('old-hostname');
		if (elm.val() == old_hostname) {
			return;
		}
		$.ajax({
			type: 'POST',
			url: '/ajax/update/hostname',
			data: {
				'item_id'     : elm.data('item-id'),
				'hostname'    : elm.val(),
				'hostname_id' : parseInt(elm.data('hostname-id') - 1)
			},
			success: function(data) {
				elm.data('old-hostname', elm.val());
			}
		});
	});
}

function history_callback(e) {
	$('#tabs').tabs('select', $.bbq.getState('tab'));
}

function go_to_tab(index) {
	$('#tabs').tabs('select', index);
	$.bbq.pushState({ 'tab' : index });
}

function update_tab_history(elm) {
	var id = $(elm).attr('id');
	match = id.match(/tab-([0-9]+)-link/);
	$.bbq.pushState({ 'tab' : match[1] });
}

function toggle_upsell_div(id) {
	$('#upsell_div_' + id).toggle();
}

function cancel_upsell(item_id, upgrade_id, upgrade_option_id) {
	old_upgrade_option_id='upgrades[' + item_id + '][' + upgrade_id + '][' + upgrade_option_id + ']';
	$('#upgrades\\\[' + item_id + '\\\]\\\[' + upgrade_id + '\\\]\\\[' + upgrade_option_id + '\\\]').attr('checked', true);
	update_upsell_price(item_id);
	toggle_upsell_div(item_id + '_' + upgrade_id);
}

function update_state_field(elm) {
	if($(elm).val() == 'US') {
		$('#state').show();
		$('#province').hide();
		$('#other_state').hide();
	}
	else if ($(elm).val() == 'CA') {
		$('#state').hide();
		$('#province').show();
		$('#other_state').hide();
	}
	else {
		$('#state').hide();
		$('#province').hide();
		$('#other_state').show();
	}
}

function select_payment_method(id) {
	$('.card-or-bank-details').removeClass('selected');
	$('#div_payment_method_' + id).addClass('selected');
	if (id == 'new') {
		$('#billing-info-div').show();
		if ($('#use_contact_info:checked').length == 0) {
			$('#new-card-info-div').show();
		}
	}
	else {
		$('#billing-info-div').hide();
		$('#new-card-info-div').hide();
	}
}

function check_required(form_id) {
	var is_good = true;

	if (form_id) {
		var elms = $('#' + form_id + ' .required');
	}
	else {
		var elms = $('.required');
	}


	for (var i = 0; i < elms.length; i++) {
		if ($(elms[i]).val() == '' && $(elms[i]).css('display') != 'none' && $(elms[i]).parent().css('display') != 'none') {
			is_good = false;
			$(elms[i]).addClass('highlight-error');
		}
	}
	if (is_good == false) {
		if (is_animating == false && $('.highlight-error').length > 0) {
			is_animating = true;
			$('.highlight-error').effect('highlight', { color: '#f7f493' }, 2000, function() { is_animating = false });
		}
		return false;
	}
	return true;
}

function select_new_credit_card() {
	$('.card-or-bank-details').removeClass('selected');
	$('#div_payment_method_new').addClass('selected');
	$('#new_credit_card').attr('checked', true);
}

function update_hostname_list(item_id) {
	var quantity = parseInt($('#quantity-' + item_id).val());
	
	if (/[^0-9]/.test(quantity) || quantity <= 0) {
		$('#quantity-error-' + item_id).css('display', 'block');
		$('#hostnames-div-' + item_id).css('display', 'none');
	}
	else {
		$('#quantity-error-' + item_id).css('display', 'none');
		$('#hostnames-div-' + item_id).css('display', 'block');
		var hostnames = $('.hostnames-' + item_id);
		var hostnames_for_removal = [];
		for (var i = 0; i < hostnames.length; i++) {
			var regex = new RegExp('hostnames\-' + item_id + '\-([0-9]+)\-p');
			var match = $(hostnames[i]).parent().attr('id').match(regex);
			var hostname_number = parseInt(match[1]);
			if (hostname_number > quantity) {
				hostnames_for_removal[hostnames_for_removal.length] = $(hostnames[i]).parent();
			}
		}
		if (hostnames_for_removal.length > 0) {
			for (var i = 0; i < hostnames_for_removal.length; i++) {
				$(hostnames_for_removal[i]).css('display', 'none');
			}
		}
		for (var i = 1; i <= quantity; i ++) {
			if ($('#hostnames-' + item_id + '-' + i).length == 0) {
				var new_input = $('<input class="required hostname hostnames-' + item_id + '" placeholder="server' + i + '.yourdomain.com" id="hostnames-' + item_id + '-' + i + '" type="text" name="hostnames[' + item_id + '][]" data-item-id="' + item_id + '" data-hostname-id="' + i + '" />');
				new_input.on('blur', function(event) {
					update_hostname($(this));
				});
				new_input.append('<sup>*</sup></p>');
				var new_p = $('<p id="hostnames-' + item_id + '-' + i + '-p"></p>');
				new_p.append(new_input);
				$('#hostnames-div-' + item_id).append(new_p);
			}
			else {
				$('#hostnames-' + item_id + '-' + i + '-p').css('display', 'inline');
			}
		}
	}
}

function update_upsell_price(item_id) {
	
	var regex = new RegExp('^upgrades\\\[' + item_id + '\\\]\\\[([0-9]+)\\\]\\\[([0-9]+)\\\]$');
	
	var matches = old_upgrade_option_id.match(regex);

	var upgrade_id = matches[1];
	var upgrade_option_id = matches[2]

	var current_upgrade_monthly_price = parseFloat(upgrades[item_id][upgrade_id][upgrades[item_id][upgrade_id]['selected']]['monthly_price']);
	var current_upgrade_monthly_price_discount_amount = parseFloat(upgrades[item_id][upgrade_id][upgrades[item_id][upgrade_id]['selected']]['monthly_price_discount_amount']);
	var current_upgrade_monthly_price_one_time_discount_amount = parseFloat(upgrades[item_id][upgrade_id][upgrades[item_id][upgrade_id]['selected']]['monthly_price_one_time_discount_amount']);
	var current_upgrade_monthly_price_recurring_discount_amount = parseFloat(upgrades[item_id][upgrade_id][upgrades[item_id][upgrade_id]['selected']]['monthly_price_recurring_discount_amount']);
	var current_upgrade_setup_fee = parseFloat(upgrades[item_id][upgrade_id][upgrades[item_id][upgrade_id]['selected']]['setup_fee']);
	var current_upgrade_setup_fee_discount_amount = parseFloat(upgrades[item_id][upgrade_id][upgrades[item_id][upgrade_id]['selected']]['setup_fee_discount_amount']);
	var current_upgrade_one_time_price = parseFloat(upgrades[item_id][upgrade_id][upgrades[item_id][upgrade_id]['selected']]['one_time_price']);

	var new_upgrade_monthly_price = parseFloat(upgrades[item_id][upgrade_id][upgrade_option_id]['monthly_price']);
	var new_upgrade_monthly_price_discount_amount = parseFloat(upgrades[item_id][upgrade_id][upgrade_option_id]['monthly_price_discount_amount']);
	var new_upgrade_monthly_price_one_time_discount_amount = parseFloat(upgrades[item_id][upgrade_id][upgrade_option_id]['monthly_price_one_time_discount_amount']);
	var new_upgrade_monthly_price_recurring_discount_amount = parseFloat(upgrades[item_id][upgrade_id][upgrade_option_id]['monthly_price_recurring_discount_amount']);
	var new_upgrade_setup_fee = parseFloat(upgrades[item_id][upgrade_id][upgrade_option_id]['setup_fee']);
	var new_upgrade_setup_fee_discount_amount = parseFloat(upgrades[item_id][upgrade_id][upgrade_option_id]['setup_fee_discount_amount']);
	var new_upgrade_one_time_price = parseFloat(upgrades[item_id][upgrade_id][upgrade_option_id]['one_time_price']);

	var item_monthly_price = parseFloat($('#item_' + item_id + '_unit_monthly_price').val());
	var item_monthly_price_one_time_discount_amount = parseFloat($('#item_' + item_id + '_unit_monthly_price_one_time_discount_amount').val());
	var item_monthly_price_recurring_discount_amount = parseFloat($('#item_' + item_id + '_unit_monthly_price_recurring_discount_amount').val());
	var item_monthly_one_time_price = item_monthly_price - item_monthly_price_one_time_discount_amount;
	var item_monthly_recurring_price = item_monthly_price - item_monthly_price_recurring_discount_amount;
	var item_setup_fee = parseFloat($('#item_' + item_id + '_unit_setup_fee').val());
	var item_one_time_price = parseFloat($('#item_' + item_id + '_unit_one_time_price').val());

	for (var key in upgrades[item_id][upgrade_id]) {
		if (key == 'selected') {
			continue;
		}
		var upgrade_option_monthly_price = parseFloat(upgrades[item_id][upgrade_id][key]['monthly_price']);
		var upgrade_option_monthly_price_discount_amount = parseFloat(upgrades[item_id][upgrade_id][key]['monthly_price_discount_amount']);
		var upgrade_option_monthly_price_one_time_discount_amount = parseFloat(upgrades[item_id][upgrade_id][key]['monthly_price_one_time_discount_amount']);
		var upgrade_option_monthly_price_recurring_discount_amount = parseFloat(upgrades[item_id][upgrade_id][key]['monthly_price_recurring_discount_amount']);
		var upgrade_option_setup_fee = parseFloat(upgrades[item_id][upgrade_id][key]['setup_fee']);
		var upgrade_option_setup_fee_discount_amount = parseFloat(upgrades[item_id][upgrade_id][key]['setup_fee_discount_amount']);
		var upgrade_option_one_time_price = parseFloat(upgrades[item_id][upgrade_id][key]['one_time_price']);
		if (upgrade_option_id != key) {
			// this is NOT the selected upgrade option
			
			upgrade_option_language = '(';
			
			// update monthly change
			if ((upgrade_option_monthly_price - upgrade_option_monthly_price_discount_amount) < (new_upgrade_monthly_price - new_upgrade_monthly_price_discount_amount)) {
				upgrade_option_language += ' - $' + ((new_upgrade_monthly_price - new_upgrade_monthly_price_discount_amount) - (upgrade_option_monthly_price - upgrade_option_monthly_price_discount_amount)).formatMoney(2, '.', ',')  + '/mo';
			}
			else if ((upgrade_option_monthly_price - upgrade_option_monthly_price_discount_amount) > (new_upgrade_monthly_price - new_upgrade_monthly_price_discount_amount)) {
				upgrade_option_language += '$' + (upgrade_option_monthly_price - (new_upgrade_monthly_price - new_upgrade_monthly_price_discount_amount)).formatMoney(2, '.', ',') + '/mo';
				if (upgrade_option_monthly_price_discount_amount > 0) {
					if (upgrade_option_monthly_price_discount_amount == upgrade_option_monthly_price_recurring_discount_amount) {
						upgrade_option_language += ' - $' + upgrade_option_monthly_price_discount_amount.formatMoney(2, '.', ',')  + '/mo discount = <span class="discount">$' + ((upgrade_option_monthly_price - (new_upgrade_monthly_price - new_upgrade_monthly_price_discount_amount)) - upgrade_option_monthly_price_discount_amount).formatMoney(2, '.', ',') + '/mo</span>';
					}
					else {
						if (upgrade_option_monthly_price_one_time_discount_amount > 0) {
							upgrade_option_language += ' - $' + upgrade_option_monthly_price_one_time_discount_amount.formatMoney(2, '.', ',');
						}
						if (upgrade_option_monthly_price_recurring_discount_amount > 0) {
							upgrade_option_language += ' - $' + upgrade_option_monthly_price_recurring_discount_amount.formatMoney(2, '.', ',') + '/mo discount';
						}
						upgrade_option_language += ' = <span class="discount">';
						if (upgrade_option_monthly_price_one_time_discount_amount > 0) {
							upgrade_option_language += '$' + (upgrade_option_monthly_price - (new_upgrade_monthly_price - new_upgrade_monthly_price_one_time_discount_amount) - upgrade_option_monthly_price_one_time_discount_amount).formatMoney(2, '.', ',') + ' first month; ';
						}
						upgrade_option_language += ' $' + (upgrade_option_monthly_price - (new_upgrade_monthly_price - new_upgrade_monthly_price_one_time_discount_amount) - upgrade_option_monthly_price_recurring_discount_amount).formatMoney(2, '.', ',') + '/mo</span>';
					}
				}
			}
			else if ((upgrade_option_monthly_price - upgrade_option_monthly_price_discount_amount) == (new_upgrade_monthly_price - new_upgrade_monthly_price_discount_amount)) {
				upgrade_option_language += '$0.00/mo';
			}
			
			// update setup fee change
			if ((upgrade_option_setup_fee - upgrade_option_setup_fee_discount_amount) < (new_upgrade_setup_fee - new_upgrade_setup_fee_discount_amount)) {
				upgrade_option_language += ' - $' + ((new_upgrade_setup_fee - new_upgrade_setup_fee_discount_amount) - (upgrade_option_setup_fee - upgrade_option_setup_fee_discount_amount)).formatMoney(2, '.', ',')  + ' setup';
			}
			else if ((upgrade_option_setup_fee - upgrade_option_setup_fee_discount_amount) > (new_upgrade_setup_fee - new_upgrade_setup_fee_discount_amount)) {
				if (upgrade_option_setup_fee_discount_amount > 0) {
					upgrade_option_language += ' + <span class="discount">$' + ((upgrade_option_setup_fee - upgrade_option_setup_fee_discount_amount) - (new_upgrade_setup_fee - new_upgrade_setup_fee_discount_amount)).formatMoney(2, '.', ',')  + ' setup</span>';
				}
				else {
					upgrade_option_language += ' + $' + ((upgrade_option_setup_fee - upgrade_option_setup_fee_discount_amount) - (new_upgrade_setup_fee - new_upgrade_setup_fee_discount_amount)).formatMoney(2, '.', ',')  + ' setup';
				}
			}
			else if (upgrade_option_setup_fee == new_upgrade_setup_fee) {
			}

			// update one-time fee change
			if (upgrade_option_one_time_price < new_upgrade_one_time_price) {
				upgrade_option_language += ' - $' + (new_upgrade_one_time_price - upgrade_option_one_time_price).formatMoney(2, '.', ',')  + ' one-time';
			}
			else if (upgrade_option_one_time_price > new_upgrade_one_time_price) {
				upgrade_option_language += ' + $' + (upgrade_option_one_time_price - new_upgrade_one_time_price).formatMoney(2, '.', ',')  + ' one-time';
			}
			else if (upgrade_option_one_time_price == new_upgrade_one_time_price) {
			}
			
			upgrade_option_language += ')';
		}
		else {
			// this is the selected upgrade option
			
			// update item subtotal
			if ((new_upgrade_monthly_price - new_upgrade_monthly_price_discount_amount) > (current_upgrade_monthly_price - current_upgrade_monthly_price_discount_amount)) {
				item_monthly_recurring_price += ((new_upgrade_monthly_price - new_upgrade_monthly_price_recurring_discount_amount) - (current_upgrade_monthly_price - current_upgrade_monthly_price_recurring_discount_amount));
				item_monthly_one_time_price += ((new_upgrade_monthly_price - new_upgrade_monthly_price_one_time_discount_amount) - (current_upgrade_monthly_price - current_upgrade_monthly_price_one_time_discount_amount));
			}
			else if ((new_upgrade_monthly_price - new_upgrade_monthly_price_discount_amount) < (current_upgrade_monthly_price - current_upgrade_monthly_price_discount_amount)) {
				item_monthly_recurring_price = item_monthly_recurring_price - ((current_upgrade_monthly_price - current_upgrade_monthly_price_recurring_discount_amount) - (new_upgrade_monthly_price - new_upgrade_monthly_price_recurring_discount_amount));
				item_monthly_one_time_price = item_monthly_one_time_price - ((current_upgrade_monthly_price - current_upgrade_monthly_price_one_time_discount_amount) - (new_upgrade_monthly_price - new_upgrade_monthly_price_one_time_discount_amount));
			}

			// update item unit setup subtotal
			if (new_upgrade_setup_fee > current_upgrade_setup_fee) {
				item_setup_fee += (new_upgrade_setup_fee - current_upgrade_setup_fee);
			}
			else if (new_upgrade_setup_fee < current_upgrade_setup_fee) {
				item_setup_fee = item_setup_fee - (current_upgrade_setup_fee - new_upgrade_setup_fee);
			}
			
			// update item unit setup subtotal
			if (new_upgrade_one_time_price > current_upgrade_one_time_price) {
				item_one_time_price += (new_upgrade_one_time_price - current_upgrade_one_time_price);
			}
			else if (new_upgrade_one_time_price < current_upgrade_one_time_price) {
				item_one_time_price = item_one_time_price - (current_upgrade_one_time_price - new_upgrade_one_time_price);
			}
			
			upgrade_option_language = '(Included in price)';
		}
		$('#upgrade_option_language_' + item_id + '_' + upgrade_id + '_' + key).html(upgrade_option_language);
	}
	if (item_monthly_price < 0) {
		item_monthly_price = 0;
	}
	if (item_monthly_recurring_price < 0) {
		item_monthly_recurring_price = 0;
	}
	if (item_monthly_one_time_price < 0) {
		item_monthly_one_time_price = 0;
	}
	$('#item_' + item_id + '_unit_monthly_price_one_time_discount_amount').val(item_monthly_price - item_monthly_one_time_price);
	$('#item_' + item_id + '_unit_monthly_price_recurring_discount_amount').val(item_monthly_price - item_monthly_recurring_price);
	if (item_monthly_recurring_price == item_monthly_one_time_price) {
		$('#item_' + item_id + '_unit_monthly_price_text_discount').hide();
		$('#item_' + item_id + '_unit_monthly_price_text_discount_monthly').hide();
	}
	else {
		$('#item_' + item_id + '_unit_monthly_price_text_discount').html('$' + (item_monthly_one_time_price).formatMoney(2, '.', ','));
		$('#item_' + item_id + '_unit_monthly_price_text_discount').show();
		$('#item_' + item_id + '_unit_monthly_price_text_discount_monthly').show();
	}
	$('#item_' + item_id + '_unit_monthly_price').val(item_monthly_price);
	$('#item_' + item_id + '_unit_monthly_price_text').html('$' + (item_monthly_recurring_price).formatMoney(2, '.', ','));

	if (item_setup_fee < 0) {
		item_setup_fee = 0;
	}
	$('#item_' + item_id + '_unit_setup_fee').val(item_setup_fee);
	$('#item_' + item_id + '_unit_setup_fee_text').html('$' + item_setup_fee.formatMoney(2, '.', ','));

	if (item_one_time_price < 0) {
		item_one_time_price = 0;
	}
	$('#item_' + item_id + '_unit_one_time_price').val(item_one_time_price);
	$('#item_' + item_id + '_unit_one_time_price_text').html('$' + item_one_time_price.formatMoney(2, '.', ','));

	// set the new upgrade option for this upgrade
	upgrades[item_id][upgrade_id]['selected'] = upgrade_option_id;

	update_subtotals(item_id);
}

function update_subtotals(item_id) {
	var item_monthly_price = ((parseFloat($('#item_' + item_id + '_unit_monthly_price').val()) - $('#item_' + item_id + '_unit_monthly_price_one_time_discount_amount').val()) * parseFloat($('#quantity-' + item_id).val()));
	if (item_monthly_price < 0) {
		item_monthly_price = 0;
	}
	$('#item_' + item_id + '_subtotal_monthly_price_text').html('$' + item_monthly_price.formatMoney(2, '.', ','));
	
	var item_setup_fee = (parseFloat($('#item_' + item_id + '_unit_setup_fee').val()) * parseFloat($('#quantity-' + item_id).val()));
	if (item_setup_fee < 0) {
		item_setup_fee = 0;
	}
	$('#item_' + item_id + '_subtotal_setup_fee_text').html('$' + item_setup_fee.formatMoney(2, '.', ','));
	
	var item_one_time_price = (parseFloat($('#item_' + item_id + '_unit_one_time_price').val()) * parseFloat($('#quantity-' + item_id).val()));
	if (item_one_time_price < 0) {
		item_one_time_price = 0;
	}
	$('#item_' + item_id + '_subtotal_one_time_price_text').html('$' + item_one_time_price.formatMoney(2, '.', ','));

	update_totals();
}

function update_totals() {

	var monthly_prices = $('.calc-unit-monthly-price');
	var monthly_prices_one_time_discounts = $('.calc-unit-monthly-price-one-time-discount-amount');
	var monthly_prices_recurring_discounts = $('.calc-unit-monthly-price-recurring-discount-amount');
	var setup_fees = $('.calc-unit-setup-fee');
	var one_times = $('.calc-unit-one-time-price');
	
	var monthly_one_time_total = 0;
	var monthly_recurring_total = 0;
	var setup_fee_total = 0;
	var one_time_total = 0;

	for (var i = 0; i < monthly_prices.length; i++) {
		elm = monthly_prices[i];
		one_time_elm = monthly_prices_one_time_discounts[i];
		recurring_elm = monthly_prices_recurring_discounts[i];
		var item_id = $(elm).data('item-id');
		monthly_one_time_total += ((parseFloat($(elm).val()) - parseFloat($(one_time_elm).val())) * parseFloat($('#quantity-' + item_id).val()));
		monthly_recurring_total += ((parseFloat($(elm).val()) - parseFloat($(recurring_elm).val())) * parseFloat($('#quantity-' + item_id).val()));
	}

	for (var i = 0; i < setup_fees.length; i++) {
		elm = setup_fees[i];
		var item_id = $(elm).data('item-id');
		setup_fee_total += (parseFloat($(elm).val()) * parseFloat($('#quantity-' + item_id).val()));
	}
	
	for (var i = 0; i < one_times.length; i++) {
		elm = one_times[i];
		var item_id = $(elm).data('item-id');
		one_time_total += (parseFloat($(elm).val()) * parseFloat($('#quantity-' + item_id).val()));
	}
	
	if (monthly_one_time_total < 0) {
		monthly_one_time_total = 0;
	}
	if (monthly_recurring_total < 0) {
		monthly_recurring_total = 0;
	}
	$('#monthly_one_time_total_value').val(monthly_one_time_total);
	$('#monthly_one_time_total_text').html('$' + monthly_one_time_total.formatMoney(2, '.', ','));
	$('#monthly_recurring_total_value').val(monthly_recurring_total);
	$('#monthly_recurring_total_text').html('$' + monthly_recurring_total.formatMoney(2, '.', ','));
	if (monthly_one_time_total == monthly_recurring_total) {
		$('#monthly_one_time_total_p').hide();
	}
	else {
		$('#monthly_one_time_total_p').show();
	}

	if (setup_fee_total < 0) {
		setup_fee_total = 0;
	}
	$('#setup_fee_total_value').val(setup_fee_total);
	$('#setup_fee_total_text').html('$' + setup_fee_total.formatMoney(2, '.', ','));

	if (one_time_total < 0) {
		one_time_total = 0;
	}
	$('#one_time_total_value').val(one_time_total);
	$('#one_time_total_text').html('$' + one_time_total.formatMoney(2, '.', ','));

	var total_due_today = parseFloat(one_time_total + setup_fee_total + monthly_one_time_total);
	if (total_due_today < 0) {
		total_due_today = 0;
	}
	$('#total_due_today_text').html('$' + total_due_today.formatMoney(2, '.', ','));
}

function update_price() {
	
	var matches = old_upgrade_option_id.match(/^upgrades\[([0-9]+)\]\[([0-9]+)\]$/);

	var upgrade_id = matches[1];
	var upgrade_option_id = matches[2]

	var current_upgrade_monthly_price = parseFloat(upgrades[upgrade_id][upgrades[upgrade_id]['selected']]['monthly_price']);
	var current_upgrade_monthly_price_discount_amount = parseFloat(upgrades[upgrade_id][upgrades[upgrade_id]['selected']]['monthly_price_discount_amount']);
	var current_upgrade_monthly_price_one_time_discount_amount = parseFloat(upgrades[upgrade_id][upgrades[upgrade_id]['selected']]['monthly_price_one_time_discount_amount']);
	var current_upgrade_monthly_price_recurring_discount_amount = parseFloat(upgrades[upgrade_id][upgrades[upgrade_id]['selected']]['monthly_price_recurring_discount_amount']);
	var current_upgrade_setup_fee = parseFloat(upgrades[upgrade_id][upgrades[upgrade_id]['selected']]['setup_fee']);
	var current_upgrade_setup_fee_discount_amount = parseFloat(upgrades[upgrade_id][upgrades[upgrade_id]['selected']]['setup_fee_discount_amount']);
	var current_upgrade_one_time_price = parseFloat(upgrades[upgrade_id][upgrades[upgrade_id]['selected']]['one_time_price']);

	var new_upgrade_monthly_price = parseFloat(upgrades[upgrade_id][upgrade_option_id]['monthly_price']);
	var new_upgrade_monthly_price_discount_amount = parseFloat(upgrades[upgrade_id][upgrade_option_id]['monthly_price_discount_amount']);
	var new_upgrade_monthly_price_one_time_discount_amount = parseFloat(upgrades[upgrade_id][upgrade_option_id]['monthly_price_one_time_discount_amount']);
	var new_upgrade_monthly_price_recurring_discount_amount = parseFloat(upgrades[upgrade_id][upgrade_option_id]['monthly_price_recurring_discount_amount']);
	var new_upgrade_setup_fee = parseFloat(upgrades[upgrade_id][upgrade_option_id]['setup_fee']);
	var new_upgrade_setup_fee_discount_amount = parseFloat(upgrades[upgrade_id][upgrade_option_id]['setup_fee_discount_amount']);
	var new_upgrade_one_time_price = parseFloat(upgrades[upgrade_id][upgrade_option_id]['one_time_price']);

	var monthly_recurring_subtotal = parseFloat($('#monthly_recurring_subtotal_value').val());
	var monthly_one_time_subtotal = parseFloat($('#monthly_one_time_subtotal_value').val());
	var setup_fee_subtotal = parseFloat($('#setup_fee_subtotal_value').val());
	var one_time_subtotal = parseFloat($('#one_time_subtotal_value').val());

	for (var key in upgrades[upgrade_id]) {
		if (key == 'selected') {
			continue;
		}
		var upgrade_option_monthly_price = parseFloat(upgrades[upgrade_id][key]['monthly_price']);
		var upgrade_option_monthly_price_discount_amount = parseFloat(upgrades[upgrade_id][key]['monthly_price_discount_amount']);
		var upgrade_option_monthly_price_one_time_discount_amount = parseFloat(upgrades[upgrade_id][key]['monthly_price_one_time_discount_amount']);
		var upgrade_option_monthly_price_recurring_discount_amount = parseFloat(upgrades[upgrade_id][key]['monthly_price_recurring_discount_amount']);
		var upgrade_option_setup_fee = parseFloat(upgrades[upgrade_id][key]['setup_fee']);
		var upgrade_option_setup_fee_discount_amount = parseFloat(upgrades[upgrade_id][key]['setup_fee_discount_amount']);
		var upgrade_option_one_time_price = parseFloat(upgrades[upgrade_id][key]['one_time_price']);
		
		if (upgrade_option_id != key) {
			// this is NOT the selected upgrade option

			upgrade_option_language = '(';
			
			// update monthly change
			if ((upgrade_option_monthly_price - upgrade_option_monthly_price_discount_amount) < (new_upgrade_monthly_price - new_upgrade_monthly_price_discount_amount)) {
				upgrade_option_language += ' - $' + ((new_upgrade_monthly_price - new_upgrade_monthly_price_discount_amount) - (upgrade_option_monthly_price - upgrade_option_monthly_price_discount_amount)).formatMoney(2, '.', ',')  + '/mo';
			}
			else if ((upgrade_option_monthly_price - upgrade_option_monthly_price_discount_amount) > (new_upgrade_monthly_price - new_upgrade_monthly_price_discount_amount)) {
				upgrade_option_language += '$' + (upgrade_option_monthly_price - (new_upgrade_monthly_price - new_upgrade_monthly_price_discount_amount)).formatMoney(2, '.', ',') + '/mo';
				if (upgrade_option_monthly_price_discount_amount > 0) {
					if (upgrade_option_monthly_price_discount_amount == upgrade_option_monthly_price_recurring_discount_amount) {
						upgrade_option_language += ' - $' + upgrade_option_monthly_price_discount_amount.formatMoney(2, '.', ',')  + '/mo discount = <span class="discount">$' + ((upgrade_option_monthly_price - (new_upgrade_monthly_price - new_upgrade_monthly_price_discount_amount)) - upgrade_option_monthly_price_discount_amount).formatMoney(2, '.', ',') + '/mo</span>';
					}
					else {
						if (upgrade_option_monthly_price_one_time_discount_amount > 0) {
							upgrade_option_language += ' - $' + upgrade_option_monthly_price_one_time_discount_amount.formatMoney(2, '.', ',');
						}
						if (upgrade_option_monthly_price_recurring_discount_amount > 0) {
							upgrade_option_language += ' - $' + upgrade_option_monthly_price_recurring_discount_amount.formatMoney(2, '.', ',') + '/mo discount';
						}
						upgrade_option_language += ' = <span class="discount">';
						if (upgrade_option_monthly_price_one_time_discount_amount > 0) {
							upgrade_option_language += '$' + (upgrade_option_monthly_price - (new_upgrade_monthly_price - new_upgrade_monthly_price_one_time_discount_amount) - upgrade_option_monthly_price_one_time_discount_amount).formatMoney(2, '.', ',') + ' first month; ';
						}
						upgrade_option_language += ' $' + (upgrade_option_monthly_price - (new_upgrade_monthly_price - new_upgrade_monthly_price_one_time_discount_amount) - upgrade_option_monthly_price_recurring_discount_amount).formatMoney(2, '.', ',') + '/mo</span>';
					}
				}
			}
			else if ((upgrade_option_monthly_price - upgrade_option_monthly_price_discount_amount) == (new_upgrade_monthly_price - new_upgrade_monthly_price_discount_amount)) {
				upgrade_option_language += '$0.00/mo';
			}
			
			// update setup fee change
			if ((upgrade_option_setup_fee - upgrade_option_setup_fee_discount_amount) < (new_upgrade_setup_fee - new_upgrade_setup_fee_discount_amount)) {
				upgrade_option_language += ' - $' + ((new_upgrade_setup_fee - new_upgrade_setup_fee_discount_amount) - (upgrade_option_setup_fee - upgrade_option_setup_fee_discount_amount)).formatMoney(2, '.', ',')  + ' setup';
			}
			else if ((upgrade_option_setup_fee - upgrade_option_setup_fee_discount_amount) > (new_upgrade_setup_fee - new_upgrade_setup_fee_discount_amount)) {
				if (upgrade_option_setup_fee_discount_amount > 0) {
					upgrade_option_language += ' + <span class="discount">$' + ((upgrade_option_setup_fee - upgrade_option_setup_fee_discount_amount) - (new_upgrade_setup_fee - new_upgrade_setup_fee_discount_amount)).formatMoney(2, '.', ',')  + ' setup</span>';
				}
				else {
					upgrade_option_language += ' + $' + ((upgrade_option_setup_fee - upgrade_option_setup_fee_discount_amount) - (new_upgrade_setup_fee - new_upgrade_setup_fee_discount_amount)).formatMoney(2, '.', ',')  + ' setup';
				}
			}
			else if (upgrade_option_setup_fee == new_upgrade_setup_fee) {
			}

			// update one-time fee change
			if (upgrade_option_one_time_price < new_upgrade_one_time_price) {
				upgrade_option_language += ' - $' + (new_upgrade_one_time_price - upgrade_option_one_time_price).formatMoney(2, '.', ',')  + ' one-time';
			}
			else if (upgrade_option_one_time_price > new_upgrade_one_time_price) {
				upgrade_option_language += ' + $' + (upgrade_option_one_time_price - new_upgrade_one_time_price).formatMoney(2, '.', ',')  + ' one-time';
			}
			else if (upgrade_option_one_time_price == new_upgrade_one_time_price) {
			}			
			
			upgrade_option_language += ')';
		}
		else {
			// this is the selected upgrade option
			
			// update monthly subtotal
			if ((new_upgrade_monthly_price - new_upgrade_monthly_price_discount_amount) > (current_upgrade_monthly_price - current_upgrade_monthly_price_discount_amount)) {
				monthly_recurring_subtotal += ((new_upgrade_monthly_price - new_upgrade_monthly_price_recurring_discount_amount) - (current_upgrade_monthly_price - current_upgrade_monthly_price_recurring_discount_amount));
				monthly_one_time_subtotal += ((new_upgrade_monthly_price - new_upgrade_monthly_price_one_time_discount_amount) - (current_upgrade_monthly_price - current_upgrade_monthly_price_one_time_discount_amount));
			}
			else if ((new_upgrade_monthly_price - new_upgrade_monthly_price_discount_amount) < (current_upgrade_monthly_price - current_upgrade_monthly_price_discount_amount)) {
				monthly_recurring_subtotal = monthly_recurring_subtotal - ((current_upgrade_monthly_price - current_upgrade_monthly_price_recurring_discount_amount) - (new_upgrade_monthly_price - new_upgrade_monthly_price_recurring_discount_amount));
				monthly_one_time_subtotal = monthly_one_time_subtotal - ((current_upgrade_monthly_price - current_upgrade_monthly_price_one_time_discount_amount) - (new_upgrade_monthly_price - new_upgrade_monthly_price_one_time_discount_amount));
			}

			/*
			if ($('#div-upgrade-option-' + upgrade_option_id).attr('data-monthly-price-one-time-discount-amount') != '0' || $('#div-upgrade-option-' + upgrade_option_id).attr('data-monthly-price-recurring-discount-amount') != '0') {
				var coupons = upgrades[upgrade_id][upgrade_option_id]['coupons'];
				if (coupons.length > 0) {
					var current_one_time_coupon_savings = current_recurring_coupon_savings = updated_one_time_coupon_savings = updated_recurring_coupon_savings = 0;
					for (var i = 0; i < coupons.length; i++) {
						current_one_time_coupon_savings = parseFloat($('#coupon-one-time-' + coupon_id).val());
						current_recurring_coupon_savings = parseFloat($('#coupon-recurring-' + coupon_id).val());
						updated_one_time_coupon_savings = current_one_time_coupon_savings + (new_upgrade_monthly_price_one_time_discount_amount - current_upgrade_monthly_price_one_time_discount_amount);
						updated_recurring_coupon_savings = current_recurring_coupon_savings + (new_upgrade_monthly_price_recurring_discount_amount - current_upgrade_monthly_price_recurring_discount_amount);
					}
					$('#coupon-one-time-' + coupon_id).val(updated_one_time_coupon_savings);
					$('#coupon-recurring-' + coupon_id).val(updated_recurring_coupon_savings);
					$('#div-coupon-one-time-' + coupon_id + ' .price').text('$' + updated_one_time_coupon_savings.formatMoney(2, '.', ','));
					$('#div-coupon-recurring-' + coupon_id + ' .price').text('$' + updated_recurring_coupon_savings.formatMoney(2, '.', ','));
				}
			}
			else if ($('#div-upgrade-option-' + upgrades[upgrade_id]['selected']).attr('data-monthly-price-one-time-discount-amount') != '0' || $('#div-upgrade-option-' + upgrades[upgrade_id]['selected']).attr('data-monthly-price-recurring-discount-amount') != '0' ) {
				var old_coupons = $('#div-upgrade-option-' + upgrades[upgrade_id]['selected']).attr('data-coupon-id');
				if (old_coupon_id != '0') {
					var current_one_time_coupon_savings = parseFloat($('#coupon-one-time-' + coupon_id).val());
					var current_recurring_coupon_savings = parseFloat($('#coupon-recurring-' + coupon_id).val());
					var updated_one_time_coupon_savings = current_one_time_coupon_savings + (new_upgrade_monthly_price_one_time_discount_amount - current_upgrade_monthly_price_one_time_discount_amount);
					var updated_recurring_coupon_savings = current_recurring_coupon_savings + (new_upgrade_monthly_price_recurring_discount_amount - current_upgrade_monthly_price_recurring_discount_amount);
					$('#coupon-one-time-' + old_coupon_id).val(updated_one_time_coupon_savings);
					$('#coupon-recurring-' + old_coupon_id).val(updated_recurring_coupon_savings);
					$('#div-coupon-one-time-' + old_coupon_id + ' .price').text('$' + updated_one_time_coupon_savings.formatMoney(2, '.', ','));
					$('#div-coupon-recurring-' + old_coupon_id + ' .price').text('$' + updated_recurring_coupon_savings.formatMoney(2, '.', ','));
				}
			}
			*/

			// update setup subtotal
			if ((new_upgrade_setup_fee - new_upgrade_setup_fee_discount_amount) > (current_upgrade_setup_fee - current_upgrade_setup_fee_discount_amount)) {
				setup_fee_subtotal += ((new_upgrade_setup_fee - new_upgrade_setup_fee_discount_amount) - (current_upgrade_setup_fee - current_upgrade_setup_fee_discount_amount));
			}
			else if ((new_upgrade_setup_fee - new_upgrade_setup_fee_discount_amount) < (current_upgrade_setup_fee - current_upgrade_setup_fee_discount_amount)) {
				setup_fee_subtotal = setup_fee_subtotal - ((current_upgrade_setup_fee - current_upgrade_setup_fee_discount_amount) - (new_upgrade_setup_fee - new_upgrade_setup_fee_discount_amount));
			}
			
			// update setup subtotal
			if (new_upgrade_one_time_price > current_upgrade_one_time_price) {
				one_time_subtotal += (new_upgrade_one_time_price - current_upgrade_one_time_price);
			}
			else if (new_upgrade_one_time_price < current_upgrade_one_time_price) {
				one_time_subtotal = one_time_subtotal - (current_upgrade_one_time_price - new_upgrade_one_time_price);
			}
			upgrade_option_language = '(Included in price)';
		}
		if (monthly_recurring_subtotal > 0) {
			$('#monthly_recurring_subtotal_value').val(monthly_recurring_subtotal);
			$('#monthly_recurring_subtotal').html(monthly_recurring_subtotal.formatMoney(2, '.', ','));
		}
		else {
			$('#monthly_recurring_subtotal_value').val(0);
			$('#monthly_recurring_subtotal').html('0.00');
		}
		if (monthly_one_time_subtotal > 0 && monthly_one_time_subtotal != monthly_recurring_subtotal) {
			$('#monthly_one_time_subtotal_value').val(monthly_one_time_subtotal);
			$('#monthly_one_time_subtotal').html(monthly_one_time_subtotal.formatMoney(2, '.', ','));
			$('#first_month_discount').css('display', 'block');
			$('#first_month_monthly').css('display', 'block');
			$('#monthly_recurring_subtotal').parent().addClass('monthly_recurring_header');
		}
		else {
			$('#monthly_one_time_subtotal_value').val(monthly_one_time_subtotal < 0 ? 0 : monthly_one_time_subtotal);
			$('#monthly_one_time_subtotal').html(monthly_one_time_subtotal < 0 ? '0.00' : monthly_one_time_subtotal.formatMoney(2, '.', ','));
			$('#first_month_discount').css('display', 'none');
			$('#first_month_monthly').css('display', 'none');
			$('#monthly_recurring_subtotal').parent().removeClass('monthly_recurring_header');
		}
		if (setup_fee_subtotal > 0) {
			$('#setup_fee_subtotal_value').val(setup_fee_subtotal);
			$('#setup_fee_subtotal').html(setup_fee_subtotal.formatMoney(2, '.', ','));
		}
		else {
			$('#setup_fee_subtotal_value').val(0);
			$('#setup_fee_subtotal').html('0.00');
 		}
		if (one_time_subtotal > 0) {
			$('#one_time_subtotal_value').val(one_time_subtotal);
			$('#one_time_subtotal').html(one_time_subtotal.formatMoney(2, '.', ','));
			$('#one-time-price').css('display', 'inline');
		}
		else {
			$('#one_time_subtotal_value').val(0);
			$('#one_time_subtotal').html('0.00');
			$('#one-time-price').css('display', 'none');
		}
		$('#upgrade_option_language_' + upgrade_id + '_' + key).html(upgrade_option_language);
	}
	
	// set the new upgrade option for this upgrade
	upgrades[upgrade_id]['selected'] = upgrade_option_id;
}

function update_all_billing_info() {
	if ($('#use_contact_info:checked').length > 0) {
		$('#billing_address').val($('#address').val()).attr('readonly', true);
		$('#billing_city').val($('#city').val()).attr('readonly', true);
		$('#billing_state').val($('#state').val()).attr('readonly', true);
		$('#billing_postal_code').val($('#postal_code').val()).attr('readonly', true);
		$('#billing_phone_number').val($('#phone_number').val()).attr('readonly', true);
	} else {
		$('#billing_address').attr('readonly', false);
		$('#billing_city').attr('readonly', false);
		$('#billing_state').attr('readonly', false);
		$('#billing_postal_code').attr('readonly', false);
		$('#billing_phone_number').attr('readonly', false);
	}
}

function update_billing_info(elm) {
	if ($('#use_contact_info:checked').length > 0) {
		$('#billing_' + $(elm).attr('id')).val($(elm).val());
	}
}

function show_new_or_existing_div() {
	if ($('input[name=new_or_existing]:radio:checked').val() == 'existing') {
		$('#existing-customer').show();
		$('#new-customer').hide();
	} else {
		$('#existing-customer').hide();
		$('#new-customer').show();
	}
}

function bind_sidebar_update() {
	$('label').on('click', update_sidebar_and_select);
}
function populate_summary() {
	$('input[type="radio"]:checked').each(function() {
		update_sidebar_and_select($(this), false);
	});
	if ($('div.summary-config').length > 0) {
		is_animating = true;
		$('div.summary-config').effect('highlight', { color: '#f7f493' }, 1250, function() { is_animating = false });
	}
}

function apply_coupon() {
	if ($('#coupon_code').val() == '') {
		alert('Please enter a valid coupon');
		return;
	}
	$.ajax({
		type:  'POST',
		url:   '/ajax/update/coupon',
		data: {
			'coupon_code' : $('#coupon_code').val()
		},
		success: function(data) {
			console.debug(data);
			if (data == 'ok') {
				location.reload(true);
			} else {
				alert(data);
			}
		},
		error: function(data) {
			alert('There was a problem applying the coupon, please try again.');
		}
	});	
}

function save_upsell_option(service_plan_id, item_id, upgrade_id) {
	post_upgrades = {};
	if (upgrades[item_id][upgrade_id]['default_callout_upgrade_option_id'] == upgrades[item_id][upgrade_id]['selected']) {
		// nothing to save
		toggle_upsell_div(item_id + '_' + upgrade_id);
	} else {
		post_upgrades[upgrade_id] = upgrades[item_id][upgrade_id]['selected'];
		$.ajax({
			type: 'POST',
			url: '/ajax/update/item',
			data: {
				'item_id'  : item_id,
				'upgrades' : post_upgrades
			},
			success: function(data) {
				if (data == 'ok') {
					$('#p_upgrade_option_' + item_id + '_' + upgrade_id).removeClass('upgrade-option-callout');
					$('#p_upgrade_option_' + item_id + '_' + upgrade_id).html($('#item_' + item_id + '_' + upgrade_id + '_' + upgrades[item_id][upgrade_id]['selected']).data('upgrade-text'));
					$('#p_upgrade_option_' + item_id + '_' + upgrade_id).effect('highlight', { color: '#f7f493' }, 1250);
					$('#upsell_div_' + item_id + '_' + upgrade_id).remove();
				} else {
					alert(data);
				}
			},
			error: function(data) {
				alert('There was a problem saving your change, please try again.');
			}
		});
	}
}

function update_sidebar_and_select(e, animate) {
	if (typeof(animate) == 'undefined') {
		animate = true;
	}

	var targ = e instanceof jQuery ? e : $(e.target);
	var name = targ.next().text();
	var item = targ.closest('.item');
	var update_id = item.attr('data-sidebar-id');
	var price_float = parseFloat(item.attr('data-monthly-price'));

	var price = price_float ? '$' + item.attr('data-monthly-price') : '$0.00';
	var discount_amount_float = parseFloat(item.attr('data-monthly-price-discount-amount'));

	if (e.target && !$(e.target).is('input')) {
		return;
	}
	
	if (!$('#' + update_id).length) {
		$('<div id="' + update_id  +'" class="config-item clearfix"><span class="name"></span><span class="price"></span></div>').appendTo('.summary-config');
		$('<div id="' + update_id  +'-discount" class="config-item config-item-discount clearfix"><span class="name"></span><span class="price discount"></span></div>').appendTo('.summary-config');
	}
	
	$('#' + update_id + ' .name').text(name).next().text(price);
	
	if (discount_amount_float > 0) {
		$('#' + update_id + '-discount .name').text('').next().text('-$' + discount_amount_float.formatMoney(2, '.', ','));
		$('#' + update_id + '-discount').show();
	}
	else {
		$('#' + update_id + '-discount').hide();
	}
	
	if (price_float == 0 && /none/i.test(name)) {
		$('#' + update_id).hide();
	}
	else {
		$('#' + update_id).show();
		if (animate == true && !is_animating && $('#' + update_id + ' .name').parent().length) {
			is_animating = true;
			$('#' + update_id + ' .name').parent().effect('highlight', { color: '#f7f493' }, 1250, function() { is_animating = false });
			if (discount_amount_float > 0) {
				$('#' + update_id + '-discount .name').parent().effect('highlight', { color: '#f7f493' }, 1250, function() { is_animating = false });
			}
		}
	}
	select_radio(item, item.siblings());
}

function select_radio(toselect, siblings) {
	// both need to be jquery objects
	siblings.removeClass('selected');
	toselect.addClass('selected');
}

var form_validation = {

	init: function (form_id) {
		var self = this
		  , valid = true

		$('#' + form_id).find('.email').each(function () {
			valid = self.email($(this).val()) ? true : self.invalid_field($(this))
		})

		return valid;
	}

	, email: function (input_email) {
		var regex = /\b[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i
		;return regex.test(input_email)
	}

	, invalid_field: function (field) {
		field.effect('highlight')
		return false;
	}

}
