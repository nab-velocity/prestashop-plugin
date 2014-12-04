<?php
/*
 *  @author chetu
 *  @copyright  2007-2014 velocity NorthAmericanbancard.
 *  International Registered Trademark & Property of velocity NorthAmericanbancard.
 */

if (!defined('_PS_VERSION_'))
	exit;

class Velocity extends PaymentModule {

	private $_postErrors = array();
	private $_warnings = array();
	private $_html = '';
	public  $identitytoken;
	public  $workflowid;
	public  $applicationprofileid;
	public  $merchantprofileid;

	/**
	 * @brief Constructor
	 */
	public function __construct() {
	
		$this->name = 'velocity';
		$this->tab = 'payments_gateways';
		$this->version = '1.0.0';
		$this->author = 'chetu';
		$this->display = 'view';
		$this->meta_title = $this->l('Velocity Merchant Expertise');
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		$config = Configuration::getMultiple(array('VELOCITY_IDENTITY_TOKEN', 'VELOCITY_WORKFLOWID', 'VELOCITY_APPLICATIONPROFILEID', 'VELOCITY_MERCHANTPROFILEID'));
		if (!empty($config['VELOCITY_IDENTITYTOKEN']))
			$this->identitytoken = $config['VELOCITY_IDENTITYTOKEN'];
		if (!empty($config['VELOCITY_WORKFLOWID']))
			$this->workflowid = $config['VELOCITY_WORKFLOWID'];
		if (!empty($config['VELOCITY_APPLICATIONPROFILEID']))
			$this->applicationprofileid = $config['VELOCITY_APPLICATIONPROFILEID'];
		if (!empty($config['VELOCITY_MERCHANTPROFILEID']))
			$this->merchantprofileid = $config['VELOCITY_MERCHANTPROFILEID'];
			
		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Velocity');
		$this->description = $this->l('Fastest and Secure transaction by Velocity northamericanbancard.');
		$this->confirmUninstall =	$this->l('Are you sure you want to delete your details?');
		if (!isset($this->identitytoken) || !isset($this->workflowid) || !isset($this->applicationprofileid) || !isset($this->merchantprofileid))
			$this->warning = $this->l('Identitytoken, workflowid, applicationprofileid & merchantprofileid must be configured before using this module.');
		if (!count(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module.');
	}

	/**
	 * @brief Install method
	 *
	 * @return Success or failure
	 */
	public function install() {
		
		/* 
		 * check velocity order is set or not if not set then set at the time of module instalataion. 	
		*/
		$sql = 'select module_name from '._DB_PREFIX_.'order_state where module_name = "'.$this->name.'"'; 
		if(!Db::getInstance()->getValue($sql)) {
		
			$preset_order = array(
									'invoice' => 0,
									'send_email' => 0,
									'module_name' => $this->name,
									'color' => '#00456c',
									'unremovable' => 1,
									'hidden' => 0,
									'logable' => 0,
									'delivery' => 0,
									'shipped' => 0,
									'paid' => 0,
									'deleted' => 0
								);

			if(!Db::getInstance()->autoExecute(_DB_PREFIX_.'order_state', $preset_order, 'INSERT'))
				return false;
			$id_order_state = (int)Db::getInstance()->Insert_ID();
			$languages = Language::getLanguages(false);
			foreach ($languages as $language)
			Db::getInstance()->autoExecute(_DB_PREFIX_.'order_state_lang', array('id_order_state'=>$id_order_state, 'id_lang'=>$language['id_lang'], 'name'=>'Awaiting velocity payment', 'template'=>''), 'INSERT');
			
			if (!@copy(dirname(__FILE__).DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.'logo.gif', _PS_ROOT_DIR_.DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.'os'.DIRECTORY_SEPARATOR.$id_order_state.'.gif'))
			return false;
			
			Configuration::updateValue('PS_OS_VELOCITY', $id_order_state);
			unset($id_order_state);
			
		}
		
		if ( !parent::install() || !$this->registerHook('payment') || !$this->registerHook('orderConfirmation') || !$this->registerHook('actionOrderStatusUpdate') || !$this->_installDb())
			return false;
		return true;
	}
	
	/**
	 * Velocity database table installation (to store the transaction details)
	 *
	 * @return boolean Database table installation result
	 */
	 
	private function _installDb()
	{
		$status = Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'velocity_transaction` (
			`id_velocity` int(11) NOT NULL AUTO_INCREMENT,
			`transaction_id` varchar(220) NOT NULL,
			`transaction_status` varchar(220) NOT NULL,
			`order_id` varchar(32) NOT NULL,
			`response_obj` text NOT NULL,
		PRIMARY KEY (`id_velocity`))
		ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1');
		
		return $status;
	}

	/**
	 * @brief Uninstall function
	 *
	 * @return Success or failure
	 */
	public function uninstall()
	{
		// Uninstall parent and unregister Configuration
		if (!Configuration::deleteByName('VELOCITY_IDENTITYTOKEN')
				|| !Configuration::deleteByName('VELOCITY_WORKFLOWID')
				|| !Configuration::deleteByName('VELOCITY_APPLICATIONPROFILEID')
				|| !Configuration::deleteByName('VELOCITY_MERCHANTPROFILEID')
				|| !parent::uninstall())
			return false;
		return true;
	}
	
	/* Velocity credential configuration section
	 *
	 * @return HTML page (template) to configure the Addon
	 */
	public function getContent() {
	
		if (Tools::isSubmit('btnSubmit'))
		{
			$this->_postValidation();
			if (!count($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors as $err)
					$this->_html .= $this->displayError($err);
		}
		else
			$this->_html .= '<br />';
		
		$this->_html .= $this->_displayVelocity();
		$this->_html .= $this->renderForm();

		return $this->_html;
	}
	
	/* 
	 * velocity configuration section validate before submit.
	 */
	private function _postValidation() {
	
		if (Tools::isSubmit('btnSubmit')) {
		
			if (!Tools::getValue('VELOCITY_IDENTITYTOKEN'))
				$this->_postErrors[] = $this->l('identitytoken is required.');
			elseif (!Tools::getValue('VELOCITY_WORKFLOWID'))
				$this->_postErrors[] = $this->l('workflowid or serviceid is required.');
			elseif (!Tools::getValue('VELOCITY_APPLICATIONPROFILEID'))
				$this->_postErrors[] = $this->l('applicationprofileid is required.');
			elseif (!Tools::getValue('VELOCITY_MERCHANTPROFILEID'))
				$this->_postErrors[] = $this->l('merchantprofileid is required.');
		}
	}
	
	/* 
	 * velocity configuration submit to save in database.
	 */
	private function _postProcess()	{
		if (Tools::isSubmit('btnSubmit'))
		{
			Configuration::updateValue('VELOCITY_IDENTITYTOKEN', Tools::getValue('VELOCITY_IDENTITYTOKEN'));
			Configuration::updateValue('VELOCITY_WORKFLOWID', Tools::getValue('VELOCITY_WORKFLOWID'));
			Configuration::updateValue('VELOCITY_APPLICATIONPROFILEID', Tools::getValue('VELOCITY_APPLICATIONPROFILEID'));
			Configuration::updateValue('VELOCITY_MERCHANTPROFILEID', Tools::getValue('VELOCITY_MERCHANTPROFILEID'));
		}
		$this->_html .= $this->displayConfirmation($this->l('Settings updated'));
	}
	
	/* 
	*	check cart currency valid for module.
	*/
	public function checkCurrency($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}
	
	/* 
		admin from.
	*/
	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Velocity Credential details'),
					'icon' => 'icon-envelope'
				),
				'input' => array(
					array(
						'type' => 'textarea',
						'label' => $this->l('Identity Token'),
						'name' => 'VELOCITY_IDENTITYTOKEN',
						'desc' => $this->l('provided by Northamericanbancard.')
					),
					array(
						'type' => 'text',
						'label' => $this->l('WorkFlowId/ServiceId'),
						'name' => 'VELOCITY_WORKFLOWID',
						'desc' => $this->l('if workflowid is available then use workflowid otherwise use serviceid.')
					),
					array(
						'type' => 'text',
						'label' => $this->l('ApplicationProfileId'),
						'name' => 'VELOCITY_APPLICATIONPROFILEID',
						'desc' => $this->l('provided by Northamericanbancard.')
					),
					array(
						'type' => 'text',
						'label' => $this->l('MerchantProfileId'),
						'name' => 'VELOCITY_MERCHANTPROFILEID',
						'desc' => $this->l('provided by Northamericanbancard.')
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);
		
		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table =  $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		); 

		return $helper->generateForm(array($fields_form));
	}
	
	public function getConfigFieldsValues()	{
	
		return array(
			'VELOCITY_IDENTITYTOKEN' => Tools::getValue('VELOCITY_IDENTITYTOKEN', Configuration::get('VELOCITY_IDENTITYTOKEN')),
			'VELOCITY_WORKFLOWID' => Tools::getValue('VELOCITY_WORKFLOWID', Configuration::get('VELOCITY_WORKFLOWID')),
			'VELOCITY_APPLICATIONPROFILEID' => Tools::getValue('VELOCITY_APPLICATIONPROFILEID', Configuration::get('VELOCITY_APPLICATIONPROFILEID')),
			'VELOCITY_MERCHANTPROFILEID' => Tools::getValue('VELOCITY_MERCHANTPROFILEID', Configuration::get('VELOCITY_MERCHANTPROFILEID')),
		);
		
	}
	
	/* 
		display velocity payment option at frontend.
	*/
	private function _displayVelocity()	{
	
		return $this->display(__FILE__, 'infos.tpl');
	}
	
	public function hookPayment($params) {
		if (!$this->active)
			return;
		if (!$this->checkCurrency($params['cart']))
			return;

		$this->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_bw' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));

		return $this->display(__FILE__, 'payment.tpl');
	}
	
	public function hookOrderConfirmation($params)
	{

		if (!$this->active)
			return;
			
		if (!isset($params['objOrder']) || ($params['objOrder']->module != $this->name))
			return false;


		if (isset($params['objOrder']) && Validate::isLoadedObject($params['objOrder']) && isset($params['objOrder']->valid) &&
				 isset($params['objOrder']->reference))
		{
		   
			$this->smarty->assign(array(
										'id' => $params['objOrder']->id, 
										'status' => 'ok',
										'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
										'reference' => $params['objOrder']->reference, 
										'valid' => $params['objOrder']->valid
										)
								 );
			return $this->display(__FILE__, 'orderconfirmation.tpl');
		}

	}
	
	/* 
	 * hook handle the request for refund.
	*/
	public function hookActionOrderStatusUpdate($params)
	{ 
		$objOrder = new Order($params['id_order']);
		if( $params['newOrderStatus']->name == 'Refund' && $objOrder->payment == 'Velocity' )
		{
			$payment = OrderPayment::getByOrderId($params['id_order']);
			if (isset($payment[0]))
			{
				require_once _PS_MODULE_DIR_ . 'velocity/lib/Velocity.php';
				
				/* SDK code embeded */
				$apppfid = Tools::getValue('VELOCITY_APPLICATIONPROFILEID', Configuration::get('VELOCITY_APPLICATIONPROFILEID'));
				$mrhtpfid = Tools::getValue('VELOCITY_MERCHANTPROFILEID', Configuration::get('VELOCITY_MERCHANTPROFILEID'));
				$baseurl = "https://api.cert.nabcommerce.com/REST/2.0.18/";
				$identytoken = Tools::getValue('VELOCITY_IDENTITYTOKEN', Configuration::get('VELOCITY_IDENTITYTOKEN'));
				$workflowid = Tools::getValue('VELOCITY_WORKFLOWID', Configuration::get('VELOCITY_WORKFLOWID'));
				$debug = true;
				VelocityCon::setups($apppfid, $mrhtpfid, $baseurl, $identytoken, $workflowid, $debug);
					
				try {
					// request for returnbyid
					$obj_transaction = new Velocity_Transaction($arr = array());		
					$res_returnbyid = $obj_transaction->returnById( array(
																		  'amount' => $payment[0]->amount, 
																		  'TransactionId' => $payment[0]->transaction_id, 
																		  'method' => 'returnbyid'
																		  ) 
																  );
					
					
					//d($res_returnbyid);
					
					/* 
					 * check the gateway response and save gateway response in database. 	
					*/
					if ( gettype($res_returnbyid) == 'array' || isset($res_returnbyid['BankcardTransactionResponsePro']['StatusCode'])) {
				
					$returnbidres = json_encode($res_returnbyid);
					$transaction_id = $res_returnbyid['BankcardTransactionResponsePro']['TransactionId'];
					$transaction_state = $res_returnbyid['BankcardTransactionResponsePro']['TransactionState'];
					$order_id = $res_returnbyid['BankcardTransactionResponsePro']['OrderId'];
					$status = $res_returnbyid['BankcardTransactionResponsePro']['Status'];
					$status_code = $res_returnbyid['BankcardTransactionResponsePro']['StatusCode'];
					
					$transaction = array(
											'transaction_id' => $transaction_id,
											'transaction_status' => $transaction_state,
											'order_id' => $order_id,
											'response_obj' => $returnbidres
										);
					if(!Db::getInstance()->autoExecute(_DB_PREFIX_.'velocity_transaction', $transaction, 'INSERT'))
						return false;	
									
					} else { // stop execution if return array object.
						return false;
					}
					
				} catch (Exception $e) {
					d($e->getMessage());
				}  
	
			}
		}
	}
}
