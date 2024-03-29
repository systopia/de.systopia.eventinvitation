{*-------------------------------------------------------+
| SYSTOPIA Event Invitation                              |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Zschiedrich (zschiedrich@systopia.de)       |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*}

{crmScope extensionKey='de.systopia.eventinvitation'}
    <div class="crm-section">
        <div class="label">{$form.event.label}</div>
        <div class="content">{$form.event.html}</div>
        <div class="clear"></div>
    </div>

    {if $form.resource_demand_id}
      <div class="help">
          {ts 1=$resource_role_label}For inviting contacts as resources for the selected event, select the role %1 and choose the resource demand they should be assigned to.{/ts}
      </div>

      <div class="crm-section">
        <div class="label">{$form.resource_demand_id.label}</div>
        <div class="content">
            {$form.resource_demand_id.html}
            <div class="description">{ts}A resource demand is mandatory when inviting contacts as resources.{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>
    {/if}

    <div class="crm-section">
      <div class="label">{$form.participant_roles.label}</div>
      <div class="content">{$form.participant_roles.html}</div>
      <div class="clear"></div>
    </div>

    <div class="crm-section">
        {capture assign=label_help}{ts}Template Help{/ts}{/capture}
        <div class="label">{$form.template.label}{help id="id-template-tokens" title=$label_help}</div>
        <div class="content">{$form.template.html}</div>
        <div class="clear"></div>
    </div>
    <div class="crm-section">
        <div class="label">{$form.pdfs_instead_of_emails.label}</div>
        <div class="content">{$form.pdfs_instead_of_emails.html}</div>
        <div class="clear"></div>
    </div>
    <div class="crm-section">
        <div class="label">{$form.email_sender.label}</div>
        <div class="content">{$form.email_sender.html}</div>
        <div class="clear"></div>
    </div>

    {* FOOTER *}
    <br>
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
{/crmScope}

<script>
{literal}
cj(document).ready(function() {
  // simple script to hide 'email sender' field when generating pdfs
  function eventInvitation_hideSenderField() {
    if (cj("input[name=pdfs_instead_of_emails]").prop('checked')) {
      cj("#email_sender").parent().parent().hide();
    } else {
      cj("#email_sender").parent().parent().show();
    }
  }
  cj("input[name=pdfs_instead_of_emails]").change(eventInvitation_hideSenderField);
  eventInvitation_hideSenderField();
});
{/literal}
</script>