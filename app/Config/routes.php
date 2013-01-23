<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different urls to chosen controllers and their actions (functions).
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
/**
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'display', and we pass a param to select the view file
 * to use (in this case, /app/View/Pages/home.ctp)...
 */
	
	Router::connect('/', array('controller' => 'pages', 'action' => 'index'));
	
	Router::connect('/msa/*', array('controller' => 'pages', 'action' => 'msa'));
	
	Router::connect('/configure/*', array('controller' => 'configurator', 'action' => 'configure'));
	
	Router::connect('/ajax/update/*', array('controller' => 'ajax', 'action' => 'update'));
	
	Router::connect('/logout', array('controller' => 'configurator', 'action' => 'logout'));
	
	/*
	Router::connect('/ajax/submit-cart', array('controller' => 'configurator', 'action' => 'ajax_submit_cart'));
	
	Router::connect('/ajax/apply_coupon', array('controller' => 'configurator', 'action' => 'ajax_apply_coupon'));
	
	Router::connect('/ajax/update/*', array('controller' => 'configurator', 'action' => 'ajax_update_order'));
	
	Router::connect('/ajax/update_quantity', array('controller' => 'configurator', 'action' => 'ajax', 'update_quantity'));
	
	Router::connect('/ajax/update_hostnames', array('controller' => 'configurator', 'action' => 'ajax_update_hostnames'));
	*/
	
	Router::connect('/coupon/*', array('controller' => 'coupon', 'action' => 'coupon'));
	
	Router::connect('/save-rack', array('controller' => 'configurator', 'action' => 'saved_racks'));
	
	Router::connect('/load-rack/*', array('controller' => 'configurator', 'action' => 'load_racks'));
	
	Router::connect('/utils/*', array('controller' => 'configurator', 'action' => 'utils'));
	
	Router::connect('/logout', array('controller' => 'pages', 'action' => 'logout'));

/**
 * Load all plugin routes.  See the CakePlugin documentation on
 * how to customize the loading of plugin routes.
 */
	CakePlugin::routes();

/**
 * Load the CakePHP default routes. Only remove this if you do not want to use
 * the built-in default routes.
 */
	require CAKE . 'Config' . DS . 'routes.php';
