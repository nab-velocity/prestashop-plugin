{*
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License "----"
* that is bundled with this package in the file
* It is also available through the world-wide-web 
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

{if $status == 'failure'}
<p>{$message}</p>
{else}
<p class="warning">
	{l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='velocity'} 
	<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team. ' mod='velocity'}</a>.
</p>
{/if}