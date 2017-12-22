{capture assign="kava_link_overname"}{strip}
  <li class="crm-contact-xataface">
    <a href="{crmURL p='civicrm/kava/overname' q="cid=`$contactId`"}" class="link-overname">
      <span><div class="icon edit-icon"></div>{ts}Overname uitvoeren{/ts}</span>
    </a>
  </li>
{/strip}{/capture}


<script type="text/javascript">
  {literal}
  cj(function() {
      cj('.crm-contact-actions-list-inner li.crm-contact-user-record, .crm-contact-actions-list-inner li.crm-contact-crm-contact-user-add').after('{/literal}{$kava_link_overname}{literal}');
  });
  {/literal}
</script>