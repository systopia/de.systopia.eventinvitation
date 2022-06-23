/*-------------------------------------------------------+
| SYSTOPIA Event Invitation                              |
| Copyright (C) 2022 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

(function ($, _, ts) {
  $(document).ready(function () {
    let $form = $('form.CRM_Eventinvitation_Form_Task_ContactSearch');
    let $participant_roles = $form.find('[name="participant_roles"]');
    let $resource_demand_id = $form.find('[name="resource_demand_id"]');

    // restrict the resource demand field to the selected event.
    $form.find('[name="event"]')
        .on('change', function() {
          let api_params = $resource_demand_id.data('api-params');
          api_params.params.entity_id = $(this).val();
          if ($resource_demand_id.data('event_id') !== parseInt($(this).val())) {
            $resource_demand_id
              .data('event_id', null)
              .val('').trigger('change');
          }
        });

    $resource_demand_id.on('change', function () {
          let $resource_demand_id = $(this);
          if ($resource_demand_id.val()) {
            // Set the event field according to the selected resource demand.
            CRM.api4(
              'ResourceDemand',
              'get',
              {where: [['id', '=', $resource_demand_id.val()]]}
            ).then(function (results) {
              $resource_demand_id.data('event_id', results[0].entity_id);
              $form.find('[name="event"]')
                .val(results[0].entity_id)
                .trigger('change');
            });

            // Set the participant role field to the resource role.
            $participant_roles
              .val(CRM.vars.eventinvitation.resource_role_id).trigger('change')
              .find('option[value=""]').show();
          }
          else {
            $participant_roles
              .val($participant_roles.find('option:first').val())
          }
        });

    $participant_roles.on('change', function() {
      if (parseInt($(this).val()) !== CRM.vars.eventinvitation.resource_role_id) {
        $resource_demand_id.val('').trigger('change');
      }
    });
  });
})(CRM.$, CRM._, CRM.ts('de.systopia.eventinvitation'));
