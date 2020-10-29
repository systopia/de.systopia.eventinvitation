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
    <div class="label">{$form.link_target_is_custom.label}&nbsp;{help id="id-link-target-enabled" title=$form.link_target_is_custom.label}</div>
    <div class="content">{$form.link_target_is_custom.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.custom_link_target.label}&nbsp;{help id="id-link-target" title=$form.custom_link_target.label}</div>
    <div class="content">{$form.custom_link_target.html}</div>
    <div class="clear"></div>
  </div>



  {* FOOTER *}
    <br>
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
{/crmScope}
