<?php 
/*
 *  @author chetu
 *  @copyright  2007-2014 velocity NorthAmericanbancard.
 *  International Registered Trademark & Property of velocity NorthAmericanbancard.
*/

/**
 * @since 1.5.0
 */

class VelocityPaymentModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $display_column_left = false;

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{ 
		parent::initContent();

		$cart = $this->context->cart;
		if (!$this->module->checkCurrency($cart))
			Tools::redirect('index.php?controller=order');
		
		$address_inovice = new address($cart->id_address_invoice);
		$state_iso_codes = State::getStates();
		foreach($state_iso_codes as $var) {
			if($var['id_state'] == $address_inovice->id_state) {
				$state_iso_code = $var['iso_code'];
			}
				
		}
		$country_iso_code = Country::getIsoById($address_inovice->id_country);

		$address = array('street1' => $address_inovice->address1, 'street2' => $address_inovice->address2, 'city' => $address_inovice->city, 'state' => $state_iso_code, 'country' => $country_iso_code, 'postcode' => $address_inovice->postcode, 'phone' => $address_inovice->phone_mobile );
		
		$velocity = new Velocity();
		$configdata = $velocity->getConfigFieldsValues();
		
		/* 
		 * @brief set the detail to smarty .
		 */
		$this->context->smarty->assign(array(
			'nbProducts' => $cart->nbProducts(),
			'cust_currency' => $cart->id_currency,
			'currencies' => $this->module->getCurrency((int)$cart->id_currency),
			'total' => $cart->getOrderTotal(true, Cart::BOTH),
			'address' => $address,
			'config' => $configdata,
			'error' => isset($_REQUEST['msg']) ? $_REQUEST['msg'] : '',
			'this_path' => $this->module->getPathUri(),
			'this_path_bw' => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		));
		
		/* 
		 * @brief this template display the payment form after the cart page.
		 */
		$this->setTemplate('payment_execution.tpl');
	}
}