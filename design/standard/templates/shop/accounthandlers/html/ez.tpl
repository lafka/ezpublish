{* DO NOT EDIT THIS FILE! Use an override template instead. *}
<table width="100%" cellspacing="0" cellpadding="0" border="0">
<tr>
<td valign="top">
<p>
<b>{"Customer"|i18n("design/standard/shop")}</b>
</p>
<p>
{'Name'|i18n('design/standard/shop')}: {$order.account_information.first_name} {$order.account_information.last_name}<br />
{'Email'|i18n('design/standard/shop')}: {$order.account_information.email}<br />
</p>

</td>
<td valign="top">

<p>
<b>{"Address"|i18n("design/standard/shop")}</b>
</p>
<p>
{'Company'|i18n('design/standard/shop')}: {$order.account_information.street1}<br />
{'Street'|i18n('design/standard/shop')}: {$order.account_information.street2}<br />
{'Zip'|i18n('design/standard/shop')}: {$order.account_information.zip}<br />
{'Place'|i18n('design/standard/shop')}: {$order.account_information.place}<br />
{'State'|i18n('design/standard/shop')}: {$order.account_information.state}<br />
{'Country/region'|i18n('design/standard/shop')}: {$order.account_information.country}<br />
</p>
</td>
</tr>
</table>

{if $order.account_information.comment}
<p>
<b>{'Comment'|i18n( 'design/standard/shop' )}</b>
</p>
<p>
{$order.account_information.comment|wash|nl2br}
</p>
{/if}
