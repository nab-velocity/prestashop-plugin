{*
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License "----"
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to xyz@abc.com so we can send you a copy immediately.
*
* DISCLAIMER
*
*  @author chetu
*  @copyright  2007-2014 velocity NorthAmericanbancard.
*  @license    
*  International Registered Trademark & Property of velocity NorthAmericanbancard.
*}
<script src="{$base_dir_ssl}modules/velocity/TRSOLUTION_JS/transparent_js.js" type="text/javascript"></script>

<script type="text/javascript">
            $(document).ready(function() {ldelim}
            
                $("#process-payment-btn").click(function(){ldelim}
				   //$("body").css({ "background":"black", "opacity":"0.5", "z-index":"200"});
                    var identitytoken = "{$config.VELOCITY_IDENTITYTOKEN}";
					var applicationprofileid = {$config.VELOCITY_APPLICATIONPROFILEID};
                    var merchantprofileid = "{$config.VELOCITY_MERCHANTPROFILEID}";

                    identitytoken = Nab.base64_encode(identitytoken+":");

                    var  card = {ldelim}
					        CardholderName: $("#card_holder_name").val(), cardtype: $("#cardtype").val(), number: $("#cc-number").val(), cvc: $("#cc-cvc").val(), expMonth: $("#cc-exp-month").val(), expYear: $("#cc-exp-year").val()
                    {rdelim};
                                 
                    var  address = {ldelim}
                            Street: "{$address.street1}", City: "{$address.city}", StateProvince: "{$address.state}", PostalCode: "{$address.postcode}", Country: "{$address.country}", Phone: "{$address.phone}"
                    {rdelim};

                    Nab.tokenizeForm(identitytoken, card, address, applicationprofileid, merchantprofileid, responseHandler);
                    return false;
                {rdelim});
              
                function responseHandler(result) {ldelim}

                    if (result['code'] == 0) {ldelim}
                        // Request was successful. Insert hidden field into the form before submitting.
                        $('#payment').append("<input type='hidden' name='TransactionToken' value='"+result.text+"' />");
                        // Continue to submit the form to the action, where we will read the decode and extract POST data.
                        document.forms['payment'].submit();
                    {rdelim}else {ldelim}
					
                        for (var i in result) {ldelim}
							if(i == 'text')
							$('#result').append("Error: "+result[i]+"<br />");
                        {rdelim}
						
                    {rdelim}
                {rdelim}
            {rdelim});
        </script>

{capture name=path}{l s='Velocity NorthAmericanBancard payment.' mod='velocity'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='velocity'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='velocity'}</p>
{else}

<h3>{l s='Velocity NorthAmericanBancard payment.' mod='velocity'}</h3>

<div id="result"></div>
<form id="payment" action="{$link->getModuleLink('velocity', 'validation', [], true)|escape:'html'}" method="post" class="defaultForm form-horizontal">
<div class="panel form-wrapper bootstrap">
<div class="form-group">
	<img src="{$this_path_bw}velocity.png" alt="{l s='Bank wire' mod='velocity'}" width="86" height="49" style="margin: 0px 10px 5px 0px;" />
</div>

<div class="form-group">
	<label class="control-label col-lg-3">Card Type</label>
	<div class="col-lg-9">
	<select id="cardtype" >
	<option value="Visa">Visa</option>
	<option value="Master">Master</option>
	</select>
	</div>
</div>

	<div class="form-group">
		<label class="control-label col-lg-3">Card holder name</label>
		<div class="col-lg-9 ">
		<input id="card_holder_name" size="30" type="text" value="ashish" class="form-control"/>
		</div>
	</div>

	<div class="form-group">
		<label class="control-label col-lg-3">Credit Card Number: </label>
		<div class="col-lg-9 ">
		<input id="cc-number" type="text" maxlength="16" autocomplete="off" value="4012888812348882" autofocus  class="form-control"/>
		</div>
	</div>
               

	<div class="form-group">
		<label class="control-label col-lg-3">CVC: </label>
		<div class="col-lg-9 ">
		<input id="cc-cvc" type="text" maxlength="4" autocomplete="off" value="123" class="form-control"/>
		</div>
	</div>

	<div class="form-group">
		<label class="control-label col-lg-3">Expiry Month / Expiry Year: </label>
		<div class="col-lg-9">
		<input id="cc-exp-month" type="text" style="width:35px; display:inline;" autocomplete="off" value="04" class="form-control"/> / 
		<input id="cc-exp-year" type="text" style="width:35px; display:inline;" autocomplete="off" value="14" class="form-control"/>
		</div>
	</div>	
	
	<div style="margin-left:200px;" class="form-group"><br>
		<button id="process-payment-btn" type="submit" class="btn-info">Process Payment</button>
	</div>
</div>
</form>
{/if}
