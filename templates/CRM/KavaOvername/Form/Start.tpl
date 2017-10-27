<div class="overname-info">
  <p>{ts}Met dit formulier kunt u de overname van een apotheekuitbating uitvoeren.{/ts}<br />
  {ts}Vul de apotheek, overnamedatum, de nieuwe naam en BTW-nummer, en de nieuwe titularis en eigenaar in.{/ts}</p>
</div>

{foreach from=$elementNames item=elementName}
  <div class="crm-section" style="margin: 15px 0;">
    <div class="label">{$form.$elementName.label}</div>
    <div class="content">{$form.$elementName.html}</div>
    <div class="clear"></div>
  </div>
{/foreach}

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
