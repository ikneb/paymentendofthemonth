
{if $status == 'ok'}
	<p>{l s='Your order on is complete.' mod='paymentendofthemonth'}
		<br /><br />
		{l s='Please send us a bank wire with' mod='paymentendofthemonth'}
		<br /><br />- {l s='Amount' mod='paymentendofthemonth'} <span class="price"><strong>{$total_to_pay}</strong></span>
		<br /><br />- {l s='Name of account owner' mod='paymentendofthemonth'}  <strong>{if $paymentendofthemonthOwner}{$paymentendofthemonthOwner}{else}___________{/if}</strong>
		<br /><br />- {l s='Include these details' mod='paymentendofthemonth'}  <strong>{if $paymentendofthemonthDetails}{$paymentendofthemonthDetails}{else}___________{/if}</strong>
		<br /><br />- {l s='Bank name' mod='paymentendofthemonth'}  <strong>{if $paymentendofthemonthAddress}{$paymentendofthemonthAddress}{else}___________{/if}</strong>
		<br /><br />{l s='An email has been sent with this information.' mod='paymentendofthemonth'}
		<br /><br /> <strong>{l s='Your order will be sent as soon as we receive payment.' mod='paymentendofthemonth'}</strong>
		<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='paymentendofthemonth'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='paymentendofthemonth'}</a>.
	</p>
{else}
	<p class="warning">
		{l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='paymentendofthemonth'}
		<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='paymentendofthemonth'}</a>.
	</p>
{/if}
