<?php
/*
 *  @author chetu
 *  @copyright  2007-2014 velocity NorthAmericanbancard.
 *  $brief International Registered Trademark & Property of velocity NorthAmericanbancard.
 */

if (!defined('_PS_VERSION_'))
	exit;

class Velocity extends PaymentModule {

	private $_postErrors = array();
	private $_warnings = array();
	private $_html = '';
	private static  $identitytoken;
	private static  $applicationprofileid;
	private static  $merchantprofileid;
	private static  $workflowid;

	/**
	 * @brief Constructor
	 */
	 
	public function __construct() {
	
		$this->name = 'velocity';   // name of module
		$this->tab = 'payments_gateways'; // module category name
		$this->version = '1.0.0';  // version of module
		$this->author = 'chetu';
		$this->display = 'view';
		$this->meta_title = $this->l('Velocity Merchant Expertise');
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';
		self::$identitytoken = 'PHNhbWw6QXNzZXJ0aW9uIE1ham9yVmVyc2lvbj0iMSIgTWlub3JWZXJzaW9uPSIxIiBBc3NlcnRpb25JRD0iXzdlMDhiNzdjLTUzZWEtNDEwZC1hNmJiLTAyYjJmMTAzMzEwYyIgSXNzdWVyPSJJcGNBdXRoZW50aWNhdGlvbiIgSXNzdWVJbnN0YW50PSIyMDE0LTEwLTEwVDIwOjM2OjE4LjM3OVoiIHhtbG5zOnNhbWw9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjEuMDphc3NlcnRpb24iPjxzYW1sOkNvbmRpdGlvbnMgTm90QmVmb3JlPSIyMDE0LTEwLTEwVDIwOjM2OjE4LjM3OVoiIE5vdE9uT3JBZnRlcj0iMjA0NC0xMC0xMFQyMDozNjoxOC4zNzlaIj48L3NhbWw6Q29uZGl0aW9ucz48c2FtbDpBZHZpY2U+PC9zYW1sOkFkdmljZT48c2FtbDpBdHRyaWJ1dGVTdGF0ZW1lbnQ+PHNhbWw6U3ViamVjdD48c2FtbDpOYW1lSWRlbnRpZmllcj5GRjNCQjZEQzU4MzAwMDAxPC9zYW1sOk5hbWVJZGVudGlmaWVyPjwvc2FtbDpTdWJqZWN0PjxzYW1sOkF0dHJpYnV0ZSBBdHRyaWJ1dGVOYW1lPSJTQUsiIEF0dHJpYnV0ZU5hbWVzcGFjZT0iaHR0cDovL3NjaGVtYXMuaXBjb21tZXJjZS5jb20vSWRlbnRpdHkiPjxzYW1sOkF0dHJpYnV0ZVZhbHVlPkZGM0JCNkRDNTgzMDAwMDE8L3NhbWw6QXR0cmlidXRlVmFsdWU+PC9zYW1sOkF0dHJpYnV0ZT48c2FtbDpBdHRyaWJ1dGUgQXR0cmlidXRlTmFtZT0iU2VyaWFsIiBBdHRyaWJ1dGVOYW1lc3BhY2U9Imh0dHA6Ly9zY2hlbWFzLmlwY29tbWVyY2UuY29tL0lkZW50aXR5Ij48c2FtbDpBdHRyaWJ1dGVWYWx1ZT5iMTVlMTA4MS00ZGY2LTQwMTYtODM3Mi02NzhkYzdmZDQzNTc8L3NhbWw6QXR0cmlidXRlVmFsdWU+PC9zYW1sOkF0dHJpYnV0ZT48c2FtbDpBdHRyaWJ1dGUgQXR0cmlidXRlTmFtZT0ibmFtZSIgQXR0cmlidXRlTmFtZXNwYWNlPSJodHRwOi8vc2NoZW1hcy54bWxzb2FwLm9yZy93cy8yMDA1LzA1L2lkZW50aXR5L2NsYWltcyI+PHNhbWw6QXR0cmlidXRlVmFsdWU+RkYzQkI2REM1ODMwMDAwMTwvc2FtbDpBdHRyaWJ1dGVWYWx1ZT48L3NhbWw6QXR0cmlidXRlPjwvc2FtbDpBdHRyaWJ1dGVTdGF0ZW1lbnQ+PFNpZ25hdHVyZSB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnIyI+PFNpZ25lZEluZm8+PENhbm9uaWNhbGl6YXRpb25NZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzEwL3htbC1leGMtYzE0biMiPjwvQ2Fub25pY2FsaXphdGlvbk1ldGhvZD48U2lnbmF0dXJlTWV0aG9kIEFsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnI3JzYS1zaGExIj48L1NpZ25hdHVyZU1ldGhvZD48UmVmZXJlbmNlIFVSST0iI183ZTA4Yjc3Yy01M2VhLTQxMGQtYTZiYi0wMmIyZjEwMzMxMGMiPjxUcmFuc2Zvcm1zPjxUcmFuc2Zvcm0gQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwLzA5L3htbGRzaWcjZW52ZWxvcGVkLXNpZ25hdHVyZSI+PC9UcmFuc2Zvcm0+PFRyYW5zZm9ybSBBbGdvcml0aG09Imh0dHA6Ly93d3cudzMub3JnLzIwMDEvMTAveG1sLWV4Yy1jMTRuIyI+PC9UcmFuc2Zvcm0+PC9UcmFuc2Zvcm1zPjxEaWdlc3RNZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwLzA5L3htbGRzaWcjc2hhMSI+PC9EaWdlc3RNZXRob2Q+PERpZ2VzdFZhbHVlPnl3NVZxWHlUTUh5NUNjdmRXN01TV2RhMDZMTT08L0RpZ2VzdFZhbHVlPjwvUmVmZXJlbmNlPjwvU2lnbmVkSW5mbz48U2lnbmF0dXJlVmFsdWU+WG9ZcURQaUorYy9IMlRFRjNQMWpQdVBUZ0VDVHp1cFVlRXpESERwMlE2ZW92T2lhN0pkVjI1bzZjTk1vczBTTzRISStSUGRUR3hJUW9xa0paeEtoTzZHcWZ2WHFDa2NNb2JCemxYbW83NUFSWU5jMHdlZ1hiQUVVQVFCcVNmeGwxc3huSlc1ZHZjclpuUytkSThoc2lZZW4vT0VTOUdtZUpsZVd1WUR4U0xmQjZJZnd6dk5LQ0xlS0FXenBkTk9NYmpQTjJyNUJWQUhQZEJ6WmtiSGZwdUlablp1Q2l5OENvaEo1bHU3WGZDbXpHdW96VDVqVE0wU3F6bHlzeUpWWVNSbVFUQW5WMVVGMGovbEx6SU14MVJmdWltWHNXaVk4c2RvQ2IrZXpBcVJnbk5EVSs3NlVYOEZFSEN3Q2c5a0tLSzQwMXdYNXpLd2FPRGJJUFpEYitBPT08L1NpZ25hdHVyZVZhbHVlPjxLZXlJbmZvPjxvOlNlY3VyaXR5VG9rZW5SZWZlcmVuY2UgeG1sbnM6bz0iaHR0cDovL2RvY3Mub2FzaXMtb3Blbi5vcmcvd3NzLzIwMDQvMDEvb2FzaXMtMjAwNDAxLXdzcy13c3NlY3VyaXR5LXNlY2V4dC0xLjAueHNkIj48bzpLZXlJZGVudGlmaWVyIFZhbHVlVHlwZT0iaHR0cDovL2RvY3Mub2FzaXMtb3Blbi5vcmcvd3NzL29hc2lzLXdzcy1zb2FwLW1lc3NhZ2Utc2VjdXJpdHktMS4xI1RodW1icHJpbnRTSEExIj5ZREJlRFNGM0Z4R2dmd3pSLzBwck11OTZoQ2M9PC9vOktleUlkZW50aWZpZXI+PC9vOlNlY3VyaXR5VG9rZW5SZWZlcmVuY2U+PC9LZXlJbmZvPjwvU2lnbmF0dXJlPjwvc2FtbDpBc3NlcnRpb24+';
		self::$applicationprofileid = '14644';
		self::$merchantprofileid = 'PrestaShop Global HC';
		self::$workflowid = '2317000001';
		
		$config = Configuration::getMultiple(array('VELOCITY_WORKFLOWID', 'VELOCITY_MERCHANTPROFILEID'));
		if (!empty($config['VELOCITY_WORKFLOWID']))
			self::$workflowid = $config['VELOCITY_WORKFLOWID'];
		if (!empty($config['VELOCITY_MERCHANTPROFILEID']))
			self::$merchantprofileid  = $config['VELOCITY_MERCHANTPROFILEID'];
			
		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Velocity');
		$this->description = $this->l('Fastest and Secure transaction by Velocity northamericanbancard.');
		$this->confirmUninstall =	$this->l('Are you sure you want to delete your details?');
		if (!isset($this->workflowid) || !isset($this->merchantprofileid))
			$this->warning = $this->l('Workflowid & merchantprofileid must be configured before using this module.');
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
		 * @brief check velocity order is set or not if not set then set at the time of module instalataion. 	
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
		
		if ( !parent::install() || !$this->registerHook('payment') || !$this->registerHook('orderConfirmation') || !$this->registerHook('actionOrderStatusUpdate') || !$this->registerHook('displayAdminOrder') || !$this->_installDb())
			return false;
		return true;
	}
	
	/**
	 * @brief Velocity database table installation (to store the transaction details)
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
	 * @return boolean for Success or failure
	 */
	public function uninstall()
	{
		// Uninstall parent and unregister Configuration
		if (!Configuration::deleteByName('VELOCITY_WORKFLOWID')
				|| !Configuration::deleteByName('VELOCITY_MERCHANTPROFILEID')
				|| !parent::uninstall())
			return false;
		return true;
	}
	
	/* @brief Velocity credential configuration section
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
		?>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
		<script>
			$(document).ready(function(){
				var check = '<?php echo Tools::getValue('VELOCITY_TESTMODE_', Configuration::get('VELOCITY_TESTMODE_')); ?>';
				if(check == 'on') {
					$("#VELOCITY_TESTMODE_").attr("checked","checked");
				} else {
					$("#VELOCITY_TESTMODE_").removeAttr("checked");
				}
			});
		</script>
		<?php
		
		return $this->_html;
	}
	
	/* 
	 * @brief velocity configuration section validate before submit.
	 */
	private function _postValidation() {
	
		if (Tools::isSubmit('btnSubmit')) {
		
			if (!Tools::getValue('VELOCITY_WORKFLOWID'))
				$this->_postErrors[] = $this->l('workflowid or serviceid is required.');
			elseif (!Tools::getValue('VELOCITY_MERCHANTPROFILEID'))
				$this->_postErrors[] = $this->l('merchantprofileid is required.');	
		}
	}
	
	/* 
	 * @brief velocity configuration submit to save in database.
	 */
	private function _postProcess()	{
		if (Tools::isSubmit('btnSubmit'))
		{
			Configuration::updateValue('VELOCITY_WORKFLOWID', Tools::getValue('VELOCITY_WORKFLOWID'));
			Configuration::updateValue('VELOCITY_MERCHANTPROFILEID', Tools::getValue('VELOCITY_MERCHANTPROFILEID'));
			Configuration::updateValue('VELOCITY_TESTMODE_', Tools::getValue('VELOCITY_TESTMODE_'));
			
		}
		$this->_html .= $this->displayConfirmation($this->l('Settings updated'));
	}
	
	/* 
	*	@brief check cart currency valid for module.
	* 	@return boolean for Success or failure
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
		@brief admin from display at admin side to save configuration.
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
						'type' => 'text',
						'label' => $this->l('WorkFlowId/ServiceId'),
						'name' => 'VELOCITY_WORKFLOWID',
						'desc' => $this->l('if workflowid is available then use workflowid otherwise use serviceid.')
					),
					array(
						'type' => 'text',
						'label' => $this->l('MerchantProfileId'),
						'name' => 'VELOCITY_MERCHANTPROFILEID',
						'desc' => $this->l('provided by Northamericanbancard.')
					),
					array(
						'type' => 'checkbox',
						'label' => $this->l('Test Mode'),
						'name' => 'VELOCITY_TESTMODE',
						'desc' => $this->l('Checked if you want to test this plugin.'),
						'values' => array(
							'query' => array('test_mode' => 1),
							'id' => 'test_mode',
							'name' => $this->l('')
						)	
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
	
	/* 
	 * @brief create an array for velocity credential.
	 */
	public function getConfigFieldsValues()	{
	
		if( Tools::getValue('VELOCITY_TESTMODE_', Configuration::get('VELOCITY_TESTMODE_')) == 'on') {
			$credential = array(
				'VELOCITY_IDENTITYTOKEN' => Velocity::$identitytoken,
				'VELOCITY_WORKFLOWID' => Velocity::$workflowid,
				'VELOCITY_APPLICATIONPROFILEID' => Velocity::$applicationprofileid,
				'VELOCITY_MERCHANTPROFILEID' => Velocity::$merchantprofileid,
				'ISTESTACCOUNT' => true,
			);
		} else {
			$credential = array(
				'VELOCITY_IDENTITYTOKEN' => Velocity::$identitytoken,
				'VELOCITY_WORKFLOWID' => Tools::getValue('VELOCITY_WORKFLOWID', Configuration::get('VELOCITY_WORKFLOWID')),
				'VELOCITY_APPLICATIONPROFILEID' => Velocity::$applicationprofileid,
				'VELOCITY_MERCHANTPROFILEID' => Tools::getValue('VELOCITY_MERCHANTPROFILEID', Configuration::get('VELOCITY_MERCHANTPROFILEID')),
				'ISTESTACCOUNT' => false,
			);
		}
		return $credential;
	}
	
	/* 
	 * @brief Display the decription and logo of velocity payment at backoffice.
	 */
	private function _displayVelocity()	{
	
		return $this->display(__FILE__, 'infos.tpl');
	}
	
	/* 
	 * @brief This hook display the payment option in the list payment at frontoffice 
	 * @return smarty template to display payment option.	
	 */
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
	
	/* 
	 * @brief This hook work after gateway transaction for order confirmation detail
	 * @return smarty template for order confirmation meaasge or trasaction failure message.
	 */
	public function hookOrderConfirmation($params)
	{
		$context = Context::getContext();
		
		if (!$this->active)
			return;
			
		if (!isset($params['objOrder']) || ($params['objOrder']->module != $this->name))
			return false;


		if (isset($params['objOrder']) && Validate::isLoadedObject($params['objOrder']) && isset($params['objOrder']->valid) &&
				 isset($params['objOrder']->reference) && isset($params['objOrder']->total_products) && isset($params['objOrder']->total_shipping) && isset($params['objOrder']->payment) && isset($params['currency']) && isset($params['objOrder']->current_state) && $params['objOrder']->current_state == '2')
		{
		   
			$this->smarty->assign(array(
										'id' => $params['objOrder']->id, 
										'status' => 'ok',
										'total_to_pay' => $params['total_to_pay'],
										'reference' => $params['objOrder']->reference, 
										'total_products' => $params['objOrder']->total_products, 
										'total_shipping' => $params['objOrder']->total_shipping,
										'currency' => $params['currency'],
										'payment' => $params['objOrder']->payment, 
										'valid' => $params['objOrder']->valid
										)
								 );
			return $this->display(__FILE__, 'orderconfirmation.tpl');
		} else {
			$this->smarty->assign(array(
										'id' => $params['objOrder']->id, 
										'status' => 'failure',
										'message' => $context->cookie->key,
										//'message' => 'Due to some unexpected error from gateway, payment transaction has been failed, please call admin',
										'total_to_pay' => $params['total_to_pay'],
										'reference' => $params['objOrder']->reference, 
										'total_products' => $params['objOrder']->total_products, 
										'total_shipping' => $params['objOrder']->total_shipping,
										'currency' => $params['currency'],
										'payment' => $params['objOrder']->payment, 
										'valid' => $params['objOrder']->valid
										)
								 );
			return $this->display(__FILE__, 'error.tpl');
		}

	}
	
        /* 
	 * @brief hook display the error message in admin panel.
	 */
        public function hookDisplayAdminOrder()
        {
            $context = Context::getContext();
            if ($context->cookie->keys != '') {
                $error = '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">×</button>'.$context->cookie->keys.'</div>';
                echo $error;
                $this->context->cookie->__set('keys','');
            }
        }
        
	/* 
	 * @brief hook handle the request for refund.
	 * @param array $params this is hold the detail of order from order_confirmation controler.
	 */
	public function hookActionOrderStatusUpdate($params)
	{
		$objOrder = new Order($params['id_order']);
		if( $params['newOrderStatus']->name == 'Refund' && $objOrder->payment == 'Velocity' )
		{
			$payment = OrderPayment::getByOrderId($params['id_order']);
			if (isset($payment[0]))
			{
				require_once _PS_MODULE_DIR_ . 'velocity/sdk/Velocity.php';
				
				/* SDK code embeded */
				if( Tools::getValue('VELOCITY_TESTMODE_', Configuration::get('VELOCITY_TESTMODE_')) == 'on') {
					
					$identitytoken = Velocity::$identitytoken;
					$workflowid = Velocity::$workflowid;
					$applicationprofileid = Velocity::$applicationprofileid;
					$merchantprofileid = Velocity::$merchantprofileid;
					$isTestAccount = true;
					
				} else {
				
					$identitytoken = Velocity::$identitytoken;
					$workflowid = Tools::getValue('VELOCITY_WORKFLOWID', Configuration::get('VELOCITY_WORKFLOWID'));
					$applicationprofileid = Velocity::$applicationprofileid;
					$merchantprofileid = Tools::getValue('VELOCITY_MERCHANTPROFILEID', Configuration::get('VELOCITY_MERCHANTPROFILEID'));
					$isTestAccount = false;
					
				}
				
				/* 
				 * @brief create object of processor class 
				 */
				try {
					$obj_transaction = new Velocity_Processor( $ident1itytoken, $applicationprofileid, $merchantprofileid, $workflowid, $isTestAccount );
				} catch (Exception $e) {	
                                         $this->context->cookie->__set('keys',$e->getMessage());
				}	

				try {
					// request for returnbyid	
					if(!is_null($obj_transaction)) {
					$res_returnbyid = $obj_transaction->returnById( array(
                                                                                                'amount' => $payment[0]->amount, 
                                                                                                'TransactionId' => $payment[0]->transaction_id
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
				}	
				} catch (Exception $e) {
					$this->context->cookie->__set('keys',$e->getMessage());
				}  
	
			}
		}
	}
	
}
