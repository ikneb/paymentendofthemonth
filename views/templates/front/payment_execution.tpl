

{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='paymentendofthemonth'}">{l s='Checkout' mod='paymentendofthemonth'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Bank-wire payment' mod='paymentendofthemonth'}
{/capture}

{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='paymentendofthemonth'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='paymentendofthemonth'}</p>
{else}

<h3>{l s='Bank-wire payment' mod='paymentendofthemonth'}</h3>
<form action="{$link->getModuleLink('paymentendofthemonth', 'validation', [], true)|escape:'html'}" method="post">
<p>
	<img src="{$this_path_bw}paymentendofthemonth.jpg" alt="{l s='Bank wire' mod='paymentendofthemonth'}" width="86" height="49" style="float:left; margin: 0px 10px 5px 0px;" />
	{l s='You have chosen to pay by bank wire.' mod='paymentendofthemonth'}
	<br/><br />
	{l s='Here is a short summary of your order:' mod='paymentendofthemonth'}
</p>
<p style="margin-top:20px;">
	- {l s='The total amount of your order is' mod='paymentendofthemonth'}
	<span id="amount" class="price">{displayPrice price=$total}</span>
	{if $use_taxes == 1}
    	{l s='(tax incl.)' mod='paymentendofthemonth'}
    {/if}
</p>
<p>
	-
	{if $currencies|@count > 1}
		{l s='We allow several currencies to be sent via bank wire.' mod='paymentendofthemonth'}
		<br /><br />
		{l s='Choose one of the following:' mod='paymentendofthemonth'}
		<select id="currency_payement" name="currency_payement" onchange="setCurrency($('#currency_payement').val());">
			{foreach from=$currencies item=currency}
				<option value="{$currency.id_currency}" {if $currency.id_currency == $cust_currency}selected="selected"{/if}>{$currency.name}</option>
			{/foreach}
		</select>
	{else}
		{l s='We allow the following currency to be sent via bank wire:' mod='paymentendofthemonth'}&nbsp;<b>{$currencies.0.name}</b>
		<input type="hidden" name="currency_payement" value="{$currencies.0.id_currency}" />
	{/if}
</p>
<p>
	{l s='Bank wire account information will be displayed on the next page.' mod='paymentendofthemonth'}
	<br /><br />
	<b>{l s='Please confirm your order by clicking "I confirm my order".' mod='paymentendofthemonth'}</b>
</p>
<p class="cart_navigation" id="cart_navigation">
	<input type="submit" value="{l s='I confirm my order' mod='paymentendofthemonth'}" class="exclusive_large" />
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Other payment methods' mod='paymentendofthemonth'}</a>
</p>
</form>
{/if}
