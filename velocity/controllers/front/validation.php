<?php
/*
 *
 *  @author chetu
 *  @copyright  2007-2014 velocity NorthAmericanbancard.
 *  @brief International Registered Trademark & Property of velocity NorthAmericanbancard.
*/

/**
 * @since 1.5.0
 */

 /* 
  * @brief here we inculde the PHP SDK to handle the gateway request/response.
 */

require_once _PS_MODULE_DIR_ . 'velocity/sdk/Velocity.php';
     
class VelocityValidationModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::postProcess()
	 * @brief standard method of prestashop.
	 */
	public function postProcess() {
	
		$cart = $this->context->cart;
		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
			Tools::redirect('index.php?controller=order&step=1');

		/*  
		 * @brief Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
		 */
		$authorized = false;
		foreach (Module::getPaymentModules() as $module)
			if ($module['name'] == 'velocity')
			{
				$authorized = true;
				break;
			}
		if (!$authorized)
			die($this->module->l('This payment method is not available.', 'validation'));

		$customer = new Customer($cart->id_customer);
		if (!Validate::isLoadedObject($customer))
			Tools::redirect('index.php?controller=order&step=1');

		$currency = $this->context->currency;
		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);		
		
		$velocity = new Velocity();
		$configdata = $velocity->getConfigFieldsValues();
		
		/* 
		 * @brief here we set the credential of velocity.
		*/
		$identitytoken = $configdata['VELOCITY_IDENTITYTOKEN'];
		$workflowid = $configdata['VELOCITY_WORKFLOWID'];
		$applicationprofileid = $configdata['VELOCITY_APPLICATIONPROFILEID'];
		$merchantprofileid = $configdata['VELOCITY_MERCHANTPROFILEID'];

		if($configdata['ISTESTACCOUNT'])
			$isTestAccount = true;
		else
			$isTestAccount = false;

		if (isset($_POST['TransactionToken']) && $_POST['TransactionToken'] != '') { // check transparent redirect form data.
		 
			$verify_array = json_decode(base64_decode($_POST['TransactionToken'])); // base64 decode the transactiontoken and then decode json string into array.
			
			/* 
			 * @brief here we validate our order.
			*/
			$this->module->validateOrder($cart->id, Configuration::get('PS_OS_VELOCITY'), $total, $this->module->displayName, NULL, array(), (int)$currency->id, false, $customer->secure_key);
			
			//d($verify_array); // for display the response TR data
			
			$avsdata = isset($verify_array->CardSecurityData->AVSData) ? $verify_array->CardSecurityData->AVSData : null;
			$paymentAccountDataToken = isset($verify_array->PaymentAccountDataToken) ? $verify_array->PaymentAccountDataToken : null;

			/* 
			 * @brief create object of processor class 
			 */
			try { 
				$obj_transaction = new Velocity_Processor( $identitytoken, $applicationprofileid, $merchantprofileid, $workflowid, $isTestAccount );
			} catch (Exception $e) {
			    $this->context->cookie->__set('key',$e->getMessage());
				Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
			}
			
			/*
			 * @brief convert standard class array into normat php array. 
			 */
			$avsData = array();
			if($avsdata != null) {
				foreach($avsdata as $key => $value) {
					$avsData[$key] = $value; 
				}
			} 
			if( $avsData['Country'] == 'US' ) {
				$avsData['Country'] = 'USA';
			} else {
				$this->context->cookie->__set('key','Country Code Error : check validation controller two letter format not supported!');
				Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
			}
			try {
				/* 
				 * @brief request for authorizeandcapture method for payment.
				*/			

				$res_authandcap = $obj_transaction->authorizeAndCapture( array(
                                                                                                'amount' => $total, 
                                                                                                'token' => $paymentAccountDataToken, 
                                                                                                'avsdata' => $avsData, 
                                                                                                'carddata' => array(),
                                                                                                'invoice_no' => '',
                                                                                                'order_id' => $this->module->currentOrder
                                                                                                )
											);
				
				
				//d($res_authandcap);
				
				if ( gettype($res_authandcap) == 'array' && isset($res_authandcap['BankcardTransactionResponsePro']['StatusCode']) && $res_authandcap['BankcardTransactionResponsePro']['StatusCode'] == '000') { // check the response of gateway.
				
					$authcapres = json_encode($res_authandcap);
					$transaction_id = $res_authandcap['BankcardTransactionResponsePro']['TransactionId'];
					$transaction_state = $res_authandcap['BankcardTransactionResponsePro']['TransactionState'];
					$order_id = $res_authandcap['BankcardTransactionResponsePro']['OrderId'];
					
					$transaction = array(
											'transaction_id' => $transaction_id,
											'transaction_status' => $transaction_state,
											'order_id' => $order_id,
											'response_obj' => $authcapres
										);
					if(!Db::getInstance()->autoExecute(_DB_PREFIX_.'velocity_transaction', $transaction, 'INSERT')) // save response in database
						return false;	
						
					/* 
					 * @brief update the order status after response from gateway.
					 */
						
					$objOrder = new Order($this->module->currentOrder); 
					$history = new OrderHistory();
					$history->id_order = (int)$objOrder->id;
					$history->changeIdOrderState(Configuration::get('PS_OS_PAYMENT'), (int)($objOrder->id));
					$sql = 'update '._DB_PREFIX_.'order_history set id_order_state = "'.Configuration::get('PS_OS_PAYMENT').'" where id_order = "'.$objOrder->id.'"';
					Db::getInstance()->execute($sql);
					$payment = $objOrder->getOrderPaymentCollection();
					if (isset($payment[0]))
					{
						$payment[0]->transaction_id = pSQL($transaction_id);
						$payment[0]->save();
					}
						
					
					Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);	
						
				} else {

					/* 
					 * @brief gateway error received in transaction then save data.
					*/
					$authcapres = serialize($res_authandcap);
					$transaction = array(
											'transaction_id' => '',
											'transaction_status' => $res_authandcap->name,
											'order_id' => $this->module->currentOrder,
											'response_obj' => $authcapres
										);
					if(!Db::getInstance()->autoExecute(_DB_PREFIX_.'velocity_transaction', $transaction, 'INSERT')) // save response in database
						return false;	
					
					/* 
					 * @brief update the order status after response from gateway.
					 */	
					$objOrder = new Order($this->module->currentOrder); 
					$history = new OrderHistory();
					$history->id_order = (int)$objOrder->id;
					$history->changeIdOrderState(Configuration::get('PS_OS_ERROR'), (int)($objOrder->id));
					$sql = 'update '._DB_PREFIX_.'order_history set id_order_state = "'.Configuration::get('PS_OS_ERROR').'" where id_order = "'.$objOrder->id.'"';
					Db::getInstance()->execute($sql);
					
					
					if ( isset($res_authandcap['StatusCode']) ) {
						$this->context->cookie->__set('key', $res_authandcap['StatusMessage']);
					} else if ( isset($res_authandcap['ErrorResponse']['ErrorId']) && $res_authandcap['ErrorResponse']['ErrorId'] == '0' ) {
						$this->context->cookie->__set('key', $res_authandcap['ErrorResponse']['ValidationErrors']['ValidationError']['RuleMessage']);
					} else if ( isset($res_authandcap['ErrorResponse']['Reason']) ){
						$this->context->cookie->__set('key', $res_authandcap['ErrorResponse']['Reason']);
					} else {
						$this->context->cookie->__set('key', 'Unexpected unkown error!');
					}
					Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);	
						
				}
				
			} catch(Exception $e) { // for unexpected condition
				$this->context->cookie->__set('key', $e->getMessage().' please contact to side admin.');
				Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
			}
			
		} else {
		
			Tools::redirect('http://'.$_SERVER[HTTP_HOST].__PS_BASE_URI__.'module/velocity/payment?msg='.Velocity_Message::$descriptions['errtransparentjs']);
			
		}	
	}
}