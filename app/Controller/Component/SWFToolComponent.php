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

class SWFToolComponent extends Component
{
	public static $configured = null;
	
	public static $pdf2swf = null;
	
	public static $swfcombine = null;
	
	public static function pdf2swf()
	{
		if (!self::configured()) {
			return false;
		}
		
		if (is_null(self::$pdf2swf)) {
			self::$pdf2swf = Configure::read('Ubersmith.swftool_paths.pdf2swf');
		}
		
		return self::$pdf2swf;
	}
	
	public static function swfcombine()
	{
		if (!self::configured()) {
			return false;
		}
		
		if (is_null(self::$swfcombine)) {
			self::$swfcombine = Configure::read('Ubersmith.swftool_paths.swfcombine');
		}
		
		return self::$swfcombine;
		
		return Configure::read('Ubersmith.swftool_paths.swfcombine');
	}
	
	public static function configured()
	{
		if (is_null(self::$configured)) {
			self::$configured = (Configure::read('Ubersmith.swftool_paths.pdf2swf') != '' && Configure::read('Ubersmith.swftool_paths.swfcombine') != '');
		}
		return self::$configured;
	}
}