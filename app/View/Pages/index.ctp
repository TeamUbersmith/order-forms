<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<?php echo $this->Session->flash(); ?>
<div class="prefix_1 grid_22 box">
	<p>Pick a service plan to configure:</p>
	<?php foreach ($service_plans as $service_plan_id => $service_plan) { ?>
	<p><a href="/configure/1/<?php echo $service_plan_id; ?>"><?php echo $service_plan->title; ?></a></p>
	<?php } ?>
</div>
