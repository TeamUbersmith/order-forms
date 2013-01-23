<?php
/**
 * Static content controller.
 *
 * This file will render views from views/pages/
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
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppController', 'Controller');

class PagesController extends AppController
{
	public $name = 'Pages';
	
	public $uses = array('UberApi', 'UberServicePlan', 'Cart', 'CartItem', 'CartItemUpgrade', 'CartItemHostname', 'Utilities');
	
	public $components = array('Session', 'UbersmithCart', 'SWFTool');
	
	public $helpers = array('Session');
	
	public function index()
	{
		$this->set(array(
			'navigation_summary' => $this->UbersmithCart->navigation_summary(),
			'title_for_layout'   => __('%s Configurator',Configure::read('Ubersmith.organization_name')),
			'service_plans'      => $this->UbersmithCart->service_plans(),
		));
		
		$this->layout = 'default';
	}
	
	public function msa($what)
	{
		switch ($what) {
			case 'view':
				$this->view_msa();
				break;
			case 'download':
				$this->download_msa();
				break;
			default:
				throw new NotFoundException(__('Can only view or download the %s', 'MSA'));
		}
	}
	
	public function view_msa()
	{
		// this doesnt' view a client MSA (yet)
		$uber_client = $this->Session->read('uber_client');
		
		if (empty($uber_client) || !SWFToolComponent::configured()) {
			throw new NotFoundException(__('swftools not configured'));
		}
		
		if (!empty($uber_client)) {
			$args = array(
				'method'     => 'client.msa_get',
				'client_id'  => $uber_client->client_id,
				'format'     => 'json',
			);
			$pdf_data = $this->UberApi->call_api($args);
		}
		
		if (!empty($pdf_json_data->data->active)) {
			$args = array(
				'method' => 'client.msa_get',
				'client_id'  => $uber_client->client_id,
				'format' => 'pdf',
			);
			$pdf_data = $this->UberApi->call_api($args, true);
		} else {
			// either not active or not on file exists
			$args = array(
				'method' => 'uber.msa_get',
				'format' => 'json',
			);
			$pdf_json_data = $this->UberApi->call_api($args);
			$args = array(
				'method' => 'uber.msa_get',
				'format' => 'pdf',
			);
			$pdf_data = $this->UberApi->call_api($args, true);
		}
		
		$temp_filename = tempnam('/tmp', 'msa_');
		
		file_put_contents($temp_filename . '.pdf', $pdf_data);
		
		exec(SWFTools::pdf2swf() . ' ' . escapeshellarg($temp_filename . '.pdf') . ' -o ' . escapeshellarg($temp_filename . '.swf') . ' 2>/dev/null');
		
		exec(SWFTools::swfcombine() . ' -o ' . escapeshellarg($temp_filename . '2.swf') . ' ' . escapeshellarg(dirname(dirname(__FILE__)) . '/Plugin/fdviewer.swf') . ' ' . escapeshellarg('#1=' . $temp_filename . '.swf'). ' 2>&1');
		
		unlink($temp_filename . '.pdf');
		unlink($temp_filename . '.swf');
		
		header('Cache-Control: cache, must-revalidate');
		header('Pragma: public');
		header('Content-Type: application/x-shockwave-flash');
		
		$swf_data = file_get_contents($temp_filename . '2.swf');
		
		echo $swf_data;
		
		unlink($temp_filename . '2.swf');
		
		exit;
	}
	
	public function download_msa()
	{
		$uber_client = $this->Session->read('uber_client');
		
		if (empty($uber_client)) {
			throw new NotFoundException(__('Not logged in'));
		}
		
		if (!empty($uber_client)) {
			$args = array(
				'method'     => 'client.msa_get',
				'client_id'  => $uber_client->client_id,
				'format'     => 'json',
			);
			$pdf_json_data = $this->UberApi->call_api($args);
		}
		
		if (!empty($pdf_json_data->data->active)) {
			$args = array(
				'method' => 'client.msa_get',
				'client_id'  => $uber_client->client_id,
				'format' => 'pdf',
			);
			$pdf_data = $this->UberApi->call_api($args, true);
		} else {
			// either not active or not on file exists
			$args = array(
				'method' => 'uber.msa_get',
				'format' => 'json',
			);
			$pdf_json_data = $this->UberApi->call_api($args);
			$args = array(
				'method' => 'uber.msa_get',
				'format' => 'pdf',
			);
			$pdf_data = $this->UberApi->call_api($args, true);
		}
		
		header('Content-type: application/pdf');
		header('Content-Disposition: attachment; filename="' . $pdf_json_data->data->filename . '"');
		
		echo $pdf_data;
		
		exit;
	}
}
