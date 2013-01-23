<div class="home-navigation prefix_1">
<a href="/"><?php echo __('Home'); ?></a>
</div>
<div class="cart-summary-container">
<?php if ($navigation_summary['summary_items'] > 0) { ?>
	<a href="<?php echo $this->Html->url(array('controller' => 'configurator', 'action' => 'configure', 'cart'), true);?>">
<?php } ?>
	(<?php echo $navigation_summary['summary_items']; ?>) &nbsp;
	$<?php echo number_format($navigation_summary['summary_setup_fee'], 2); ?> <?php echo __('Setup'); ?> &nbsp;&#47;&nbsp;
	<?php echo __('Recurring Total'); ?> $<?php echo number_format($navigation_summary['summary_monthly_price'], 2); ?>
<?php if ($navigation_summary['summary_items'] > 0) { ?>
	</a>
<?php } ?>
</div>