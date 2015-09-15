<?php
/*
 *  @author Ashish
 *  @copyright  2007-2014 velocity NorthAmericanbancard.
 *  $brief International Registered Trademark & Property of velocity NorthAmericanbancard.
 */

if (!defined('_PS_VERSION_'))
	exit;

class Velocity extends PaymentModule {

	private $_html = '';
        private $_postErrors = array();

	/**
	 * @brief Constructor
	 */
	 
	public function __construct() {

            $this->name                 = 'velocity';   // name of module
            $this->tab                  = 'payments_gateways'; // module category name
            $this->version              = '1.0.0';  // version of module
            $this->author               = 'Ashish';
            $this->display              = 'view';
            $this->meta_title           = $this->l('Velocity Merchant Expertise');
            $this->currencies           = true;
            $this->currencies_mode      = 'checkbox';

            $this->bootstrap = true;
            parent::__construct();

            $this->displayName = $this->l('Velocity');
            $this->description = $this->l('Fastest and Secure transaction by Velocity northamericanbancard.');
            $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
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
            $sql = 'select module_name from ' . _DB_PREFIX_ . 'order_state where module_name = "'.$this->name.'"'; 
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

            if ( !parent::install() || !$this->registerHook('payment') || !$this->registerHook('orderConfirmation') || !$this->registerHook('displayAdminOrder') || !$this->registerHook('actionOrderReturn') || !$this->registerHook('actionProductCancel') || !$this->_installDb())
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
            $field = Db::getInstance()->ExecuteS("show tables like '%velocity_transaction'");

            if ($field == NULL) { // if table not created then create table.
                $status = Db::getInstance()->Execute('
                CREATE TABLE `'._DB_PREFIX_.'velocity_transaction` (
                `id_velocity` int(11) NOT NULL AUTO_INCREMENT,
                `transaction_id` varchar(220) NOT NULL,
                `transaction_status` varchar(220) NOT NULL,
                `order_id` varchar(32) NOT NULL,
                `request_obj` text NOT NULL,
                `response_obj` text NOT NULL,
                PRIMARY KEY (`id_velocity`))
                ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1');
            } else { // check if table created and all field matched as per new update ortherwise update.
                foreach($field[0] as $key => $val) {
                    $tablename = $val;
                }
                $fields = Db::getInstance()->ExecuteS("SHOW COLUMNS FROM " . $tablename);

                $count     = 0;
                foreach ($fields as $key => $val) {
                    if ($val['Field'] == 'request_obj'){
                        $count += 1;
                    }
                }
                if ($count == 0) {
                    $status = Db::getInstance()->Execute("ALTER TABLE " . $tablename . " add request_obj text");
                } else {
                    $status = true;
                }
            }
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
		if (!Configuration::deleteByName('VELOCITY_IDENTITYTOKEN')
				|| !Configuration::deleteByName('VELOCITY_WORKFLOWID')
                                || !Configuration::deleteByName('VELOCITY_APPLICATIONPROFILEID')
                                || !Configuration::deleteByName('VELOCITY_MERCHANTPROFILEID')
                                || !Configuration::deleteByName('VELOCITY_TESTMODE_')
				|| !parent::uninstall())
			return false;
		return true;
	}
	
	/* @brief Velocity credential configuration section
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
	 * @brief velocity configuration section validate before submit.
	 */
	private function _postValidation() {
	
            if (Tools::isSubmit('btnSubmit')) {

                if (!Tools::getValue('VELOCITY_IDENTITYTOKEN'))
                        $this->_postErrors[] = $this->l('Identity token is required');
                elseif (!Tools::getValue('VELOCITY_WORKFLOWID'))
                        $this->_postErrors[] = $this->l('workflowid or serviceid is required.');
                elseif (!Tools::getValue('VELOCITY_APPLICATIONPROFILEID'))
                        $this->_postErrors[] = $this->l('ApplicationProfileId is required');
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
                Configuration::updateValue('VELOCITY_IDENTITYTOKEN', Tools::getValue('VELOCITY_IDENTITYTOKEN'));
                Configuration::updateValue('VELOCITY_WORKFLOWID', Tools::getValue('VELOCITY_WORKFLOWID'));
                Configuration::updateValue('VELOCITY_APPLICATIONPROFILEID', Tools::getValue('VELOCITY_APPLICATIONPROFILEID'));
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
                                            'type' => 'textarea',
                                            'label' => $this->l('Identity Token'),
                                            'name' => 'VELOCITY_IDENTITYTOKEN',
                                            'desc' => $this->l('This token is use for genrate session token.')
                                    ),
                                    array(
                                            'type'  => 'text',
                                            'label' => $this->l('WorkFlowId/ServiceId'),
                                            'name'  => 'VELOCITY_WORKFLOWID',
                                            'desc'  => $this->l('if workflowid is available then use workflowid otherwise use serviceid.')
                                    ),
                                    array(
                                            'type'  => 'text',
                                            'label' => $this->l('ApplicationProfileId'),
                                            'name'  => 'VELOCITY_APPLICATIONPROFILEID',
                                            'desc'  => $this->l('provided by Northamericanbancard.')
                                    ),
                                    array(
                                            'type'  => 'text',
                                            'label' => $this->l('MerchantProfileId'),
                                            'name'  => 'VELOCITY_MERCHANTPROFILEID',
                                            'desc'  => $this->l('provided by Northamericanbancard.')
                                    ),
                                    array(
                                            'type'   => 'checkbox',
                                            'label'  => $this->l('Test Mode'),
                                            'name'   => 'VELOCITY_TESTMODE',
                                            'desc'   => $this->l('Checked if you want to test this plugin.'),
                                            'values' => array(
                                                    'query' => array('test_mode' => 1),
                                                    'id'    => 'test_mode',
                                                    'name'  => $this->l('')
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
                    'fields_value' => array(
                        'VELOCITY_IDENTITYTOKEN'        => Tools::getValue('VELOCITY_IDENTITYTOKEN', Configuration::get('VELOCITY_IDENTITYTOKEN')),
                        'VELOCITY_WORKFLOWID'           => Tools::getValue('VELOCITY_WORKFLOWID', Configuration::get('VELOCITY_WORKFLOWID')),
                        'VELOCITY_APPLICATIONPROFILEID' => Tools::getValue('VELOCITY_APPLICATIONPROFILEID', Configuration::get('VELOCITY_APPLICATIONPROFILEID')),
                        'VELOCITY_MERCHANTPROFILEID'    => Tools::getValue('VELOCITY_MERCHANTPROFILEID', Configuration::get('VELOCITY_MERCHANTPROFILEID')),
                        'VELOCITY_TESTMODE_'            => Tools::getValue('VELOCITY_TESTMODE_', Configuration::get('VELOCITY_TESTMODE_')),
                    ),
                    'languages'   => $this->context->controller->getLanguages(),
                    'id_language' => $this->context->language->id
		); 

		return $helper->generateForm(array($fields_form));
	}
        
	/* 
	 * @brief create an array for velocity credential for application.
	 */
	public function getConfigFieldsValues()	{

            $credential = array(
                'VELOCITY_IDENTITYTOKEN'        => Tools::getValue('VELOCITY_IDENTITYTOKEN', Configuration::get('VELOCITY_IDENTITYTOKEN')),
                'VELOCITY_WORKFLOWID'           => Tools::getValue('VELOCITY_WORKFLOWID', Configuration::get('VELOCITY_WORKFLOWID')),
                'VELOCITY_APPLICATIONPROFILEID' => Tools::getValue('VELOCITY_APPLICATIONPROFILEID', Configuration::get('VELOCITY_APPLICATIONPROFILEID')),
                'VELOCITY_MERCHANTPROFILEID'    => Tools::getValue('VELOCITY_MERCHANTPROFILEID', Configuration::get('VELOCITY_MERCHANTPROFILEID'))
            );
            
            if ( Tools::getValue('VELOCITY_TESTMODE_', Configuration::get('VELOCITY_TESTMODE_')) == 'on') {
                $credential['ISTESTACCOUNT'] = true;
            } else {
                $credential['ISTESTACCOUNT'] = false;
            }
            return $credential;
	}
	
	/* 
	 * @brief Display the decription and logo of velocity payment at backoffice.
	 */
	private function _displayVelocity() {
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
        public function hookDisplayAdminOrder() {
            $context = Context::getContext();
            if ($context->cookie->keys != '') {
                $error = '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">×</button>'.$context->cookie->keys.'</div>';
                echo $error;
                $this->context->cookie->__set('keys','');
            }
        }

        /* 
	 * @brief hook handle the request for standrd refund and products return.
	 * @param array $params this is hold the detail of order from order_confirmation controler.
	 */
        public function hookActionProductCancel($params) {

            $genrate_slip = isset($_REQUEST['generateCreditSlip']) ? $_REQUEST['generateCreditSlip'] : '';
            $shipping_back = isset($_REQUEST['shippingBack']) ? $_REQUEST['shippingBack'] : '';
            $generate_discount = isset($_REQUEST['generateDiscount']) ? $_REQUEST['generateDiscount'] : '';
            $cancel_product = isset($_REQUEST['cancelProduct']) ? $_REQUEST['cancelProduct'] : '';
            $paymentmode = isset($params['order']->payment) ? $params['order']->payment : '';
            $order_status = isset($params['order']->current_state) ? $params['order']->current_state : NULL;
            $shipping_cost = isset($params['order']->total_shipping) ? $params['order']->total_shipping : 0;
            $payment = OrderPayment::getByOrderId($params['order']->id); // get transaction id.

            // check condition for velocity gateway request only with specific condition 
            if( $paymentmode == 'Velocity' && $generate_discount != 'on' && ($order_status == 2 || $order_status == 5 ) && ($cancel_product == 'Refund products' || $cancel_product == 'Return products') ) {
                  
                // array of product quantity.
                $cancelqty = array();
                foreach ($_REQUEST['cancelQuantity'] as $count){
                    array_push($cancelqty, $count);
                }
                
                // Refund cost on the basis of product quantity
                $refund_cast = 0;
                $objCart = new cart($params['order']->id_cart);
                foreach ($objCart->getProducts() as $num => $product) {
                    $refund_cast += (float)$cancelqty[$num] * (float)$product['price_wt'];
                }
                
                // Add shipping cost on the request of basis form admin side
                if ($shipping_back == 'on') {
                    $refund_cast = $refund_cast + (float)$shipping_cost;
                }
                
                if ( isset($payment[0]->transaction_id) ) {
                    
                    require_once _PS_MODULE_DIR_ . 'velocity/sdk/Velocity.php';
                    /* SDK code embeded */
                    
                    $identitytoken = Tools::getValue('VELOCITY_IDENTITYTOKEN', Configuration::get('VELOCITY_IDENTITYTOKEN'));
                    $workflowid = Tools::getValue('VELOCITY_WORKFLOWID', Configuration::get('VELOCITY_WORKFLOWID'));
                    $applicationprofileid = Tools::getValue('VELOCITY_APPLICATIONPROFILEID', Configuration::get('VELOCITY_APPLICATIONPROFILEID'));
                    $merchantprofileid = Tools::getValue('VELOCITY_MERCHANTPROFILEID', Configuration::get('VELOCITY_MERCHANTPROFILEID'));
 
                    if( Tools::getValue('VELOCITY_TESTMODE_', Configuration::get('VELOCITY_TESTMODE_')) == 'on')
                        $isTestAccount = true;
                    else
                        $isTestAccount = false;

                    /* 
                     * @brief create object of processor class 
                     */
                    try {
                        $obj_transaction = new Velocity_Processor( $identitytoken, $applicationprofileid, $merchantprofileid, $workflowid, $isTestAccount );
                    } catch (Exception $e) {	
                        $this->context->cookie->__set('keys',$e->getMessage());
                        return false;
                    }	

                    try {
                        // request for returnbyid	
                        if(!is_null($obj_transaction)) {

                            $res_returnbyid = $obj_transaction->returnById( array(
                                                                                    'amount' => $refund_cast, 
                                                                                    'TransactionId' => $payment[0]->transaction_id
                                                                                  ) 
                                                                            ); 

                            /* 
                             * check the gateway response and save gateway response in database. 	
                            */
                            if ( gettype($res_returnbyid) == 'array' || isset($res_returnbyid['BankcardTransactionResponsePro']['StatusCode'])) {

                                $returnbidres      = json_encode($res_returnbyid);
                                $transaction_id    = $res_returnbyid['BankcardTransactionResponsePro']['TransactionId'];
                                $transaction_state = $res_returnbyid['BankcardTransactionResponsePro']['TransactionState'];
                                $order_id          = $res_returnbyid['BankcardTransactionResponsePro']['OrderId'];
                                $status            = $res_returnbyid['BankcardTransactionResponsePro']['Status'];
                                $status_code       = $res_returnbyid['BankcardTransactionResponsePro']['StatusCode'];
                                $xml               = Velocity_XmlCreator::returnById_XML($refund_cast, $payment[0]->transaction_id);  // got ReturnById xml object. 
                                $req_obj           = $xml->saveXML();
                                $req_obj           = serialize($req_obj);

                                $transaction = array(
                                                        'transaction_id'     => $transaction_id,
                                                        'transaction_status' => $transaction_state,
                                                        'order_id'           => $order_id,
                                                        'request_obj'        => $req_obj,
                                                        'response_obj'       => $returnbidres
                                                     );

                                if(!Db::getInstance()->autoExecute(_DB_PREFIX_.'velocity_transaction', $transaction, 'INSERT')) {
                                    $this->context->cookie->__set('keys', 'your transaction is successfull but not save in databsae, please contact technical team.');
                                    return false;
                                } else {
                                    return true;
                                }
                            }
                        }	
                    } catch (Exception $e) {
                        $this->context->cookie->__set('keys',$e->getMessage());
                        return true;
                    }
                    
                } else {
                    $this->context->cookie->__set('keys','Please contact with technical team transaction is not set.');
                    return false;
                }
  
            }
            
        
        }
	
}
