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
<style>
a.velocity:after {ldelim}
    color: #777777;
    content: "\f054";
    display: block;
    font-family: "FontAwesome";
    font-size: 25px;
    height: 22px;
    margin-top: -11px;
    position: absolute;
    right: 15px;
    top: 50%;
    width: 14px;
{rdelim}
p.payment_module a.velocity {ldelim}
    background-image: url("{$base_dir_ssl}modules/velocity/img/paymenticonvelocity.png");
	background-repeat: no-repeat;
	background-position: 20px 35px;
{rdelim}
</style>

<div class="row">
	<div class="col-xs-12 col-md-6">
<p class="payment_module">
	<a  class="velocity" href="{$link->getModuleLink('velocity', 'payment')|escape:'html'}" title="{l s='Pay by Northamericanbancard' mod='velocity'}">
		{l s='Pay by Northamericanbancard' mod='velocity'}&nbsp;<span>{l s='(order processing will be longer)' mod='velocity'}</span>
	</a>
</p>
</div>
</div>