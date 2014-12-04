<?php
/*
 *
 *  @author chetu
 *  @copyright  2007-2014 velocity NorthAmericanbancard.
 *  International Registered Trademark & Property of velocity NorthAmericanbancard.
*/

/**
 * @since 1.5.0
 */

 /* 
   here we inculde the PHP SDK to handle the gateway request/response.
 */
require_once _PS_MODULE_DIR_ . 'velocity/lib/Velocity.php';
	
class VelocityValidationModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::postProcess()
	 */
	public function postProcess() {
	
		$cart = $this->context->cart;
		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
			Tools::redirect('index.php?controller=order&step=1');

		/*  
		 * Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
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

		/* 
		 * here we validate our order.
		*/
		$this->module->validateOrder($cart->id, Configuration::get('PS_OS_VELOCITY'), $total, $this->module->displayName, NULL, array(), (int)$currency->id, false, $customer->secure_key);
		
		/* SDK code embeded */
		$apppfid = Tools::getValue('VELOCITY_APPLICATIONPROFILEID', Configuration::get('VELOCITY_APPLICATIONPROFILEID'));
		$mrhtpfid = Tools::getValue('VELOCITY_MERCHANTPROFILEID', Configuration::get('VELOCITY_MERCHANTPROFILEID'));
		$baseurl = "https://api.cert.nabcommerce.com/REST/2.0.18/";
		$identytoken = Tools::getValue('VELOCITY_IDENTITYTOKEN', Configuration::get('VELOCITY_IDENTITYTOKEN'));
		$workflowid = Tools::getValue('VELOCITY_WORKFLOWID', Configuration::get('VELOCITY_WORKFLOWID'));
		$debug = false;
		VelocityCon::setups($apppfid, $mrhtpfid, $baseurl, $identytoken, $workflowid, $debug);
		if (isset($_POST['TransactionToken']) && $_POST['TransactionToken'] != '') { // check transparent redirect form data.
		 
			$verify_array = json_decode($_POST['TransactionToken']);

			//p($verify_array);
			
			$avsdata = isset($verify_array->CardSecurityData->AVSData) ? $verify_array->CardSecurityData->AVSData : null;
			$paymentAccountDataToken = isset($verify_array->PaymentAccountDataToken) ? $verify_array->PaymentAccountDataToken : null;

			/* create object of processor class */
			try {
				$obj_processor = new Velocity_Processor(VelocityCon::$identitytoken);
			} catch (Exception $e) {
				echo $e->getMessage();
			}

			/*
			 * convert standard class array into normat php array. 
			 */
			$avsData = array();
			if($avsdata != null) {
				foreach($avsdata as $key => $value) {
					$avsData[$key] = $value; 
				}
			} 
			
			try {
				/* 
				 * request for authorizeandcapture method for payment.
				*/
				$obj_transaction = new Velocity_Transaction($arr = array());			
				$res_authandcap = $obj_transaction->authorizeAndCapture( array(
																				'amount' => $total, 
																				'token' => $paymentAccountDataToken, 
																				'avsdata' => $avsData, 
																				'carddata' => array(),
																				'invoice_no' => '',
																				'order_id' => $this->module->currentOrder,
																				'method' => 'authorizeandcapture'
																				)
																		);
				
				
				//d($res_authandcap);
				
				if ( gettype($res_authandcap) == 'array' || isset($res_authandcap['BankcardTransactionResponsePro']['StatusCode'])) { // check the response of gateway.
				
					$authcapres = json_encode($res_authandcap);
					$transaction_id = $res_authandcap['BankcardTransactionResponsePro']['TransactionId'];
					$transaction_state = $res_authandcap['BankcardTransactionResponsePro']['TransactionState'];
					$order_id = $res_authandcap['BankcardTransactionResponsePro']['OrderId'];
					$status = $res_authandcap['BankcardTransactionResponsePro']['Status'];
					$status_code = $res_authandcap['BankcardTransactionResponsePro']['StatusCode'];
					
					$transaction = array(
											'transaction_id' => $transaction_id,
											'transaction_status' => $transaction_state,
											'order_id' => $order_id,
											'response_obj' => $authcapres
										);
					if(!Db::getInstance()->autoExecute(_DB_PREFIX_.'velocity_transaction', $transaction, 'INSERT')) // save response in database
						return false;	
						
				} else {
				// stop execution if return object.
					die;
				}
				
			} catch(Exception $e) {
				d($e->getMessage());
			}
		} else {

			d(Velocity_Message::$descriptions['errtransparentjs']);
		}
		
		/* 
		 * update the order status after response from gateway.
		*/
		if( isset($status) && $status == 'Successful' && isset($status_code) && $status_code == '000' ) { 
		    
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
			
		} else {
		    $objOrder = new Order($this->module->currentOrder); 
			$history = new OrderHistory();
			$history->id_order = (int)$objOrder->id;
			$history->changeIdOrderState(Configuration::get('PS_OS_ERROR'), (int)($objOrder->id));
			$sql = 'update '._DB_PREFIX_.'order_history set id_order_state = "'.Configuration::get('PS_OS_ERROR').'" where id_order = "'.$objOrder->id.'"';
			Db::getInstance()->execute($sql);
		}
		
		Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
	}
}
