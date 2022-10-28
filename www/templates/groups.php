<?php
extract($viewParams);
$appPart = !empty($appName) ? ' - ' . $appName : '';
$institutionPart = !empty($institution) ? ' | ' . $institution : '';
$page->doctitle = 'Your Groups' . $appPart . $institutionPart;
?>
<div class="dcf-bleed dcf-pt-8 dcf-pb-8">
  <div class="dcf-wrapper">
    <h2 class="dcf-txt-h4">Your Groups</h2>
    <?php $groups = $lilurl->getUserGroups($auth->getUserId()); ?>
    <?php if (count($groups) > 0): ?>
      <table id="groups" class="dcf-w-100% go_responsive_table flush-left dcf-table dcf-txt-sm" data-order="[[ 0, &quot;asc&quot; ]]">
        <caption class="dcf-sr-only">Your Groups</caption>
        <thead>
        <tr>
          <th scope="col">Group</th>
          <th scope="col" data-searchable="false" data-orderable="false">Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($groups as $group): ?>
          <tr>
            <td data-header="Group"><?php echo htmlspecialchars($group->groupName ?? ''); ?></td>
            <td class="dcf-txt-sm">
              <div class="dcf-d-flex dcf-flex-wrap dcf-col-gap-1 dcf-row-gap-1">
                <a class="dcf-btn dcf-btn-secondary" href="<?php echo htmlspecialchars($lilurl->getBaseUrl('a/group/' . urlencode($group->groupID ?? ''))); ?>" title="Edit <?php echo htmlspecialchars($group->groupID ?? ''); ?> Go URL" >Edit</a>
                <form class="dcf-form dcf-d-inline" action="<?php echo htmlspecialchars($lilurl->getBaseUrl('a/groups')); ?>" method="post">
                  <input type="hidden" name="groupID" value="<?php echo htmlspecialchars($group->groupID ?? ''); ?>" />
                  <button class="dcf-btn dcf-btn-primary" type="submit" onclick="return confirm('Are you for sure you want to delete group, \'<?php echo htmlspecialchars($group->groupName ?? ''); ?>\'?');">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>You don't have any groups yet.</p>
    <?php endif;?>
    <div class="dcf-mt-6 dcf-mb-6">
      <a class="dcf-btn dcf-btn-primary" href="<?php echo htmlspecialchars($lilurl->getBaseUrl('a/group')); ?>">Add Group</a>
    </div>
  </div>
</div>
<?php
$page->addScriptDeclaration("
  require(['jquery', '/js/datatables-1.10.21.min.js'], function(jq) {
    jq(document).ready(function ($) {
      $('#groups').DataTable({
        'oLanguage': {
          'sSearch': 'Search'
        }
      });
      $('.dataTables_length label').addClass('dcf-label');
      $('.dataTables_length select').addClass('dcf-input-select dcf-d-inline-block dcf-w-10 dcf-txt-sm');
      $('.dataTables_filter label').addClass('dcf-label');
      $('.dataTables_filter label input').addClass('dcf-d-inline dcf-input-text dcf-txt-sm');
      $('.dataTables_info, .dataTables_paginate, .dataTables_paginate a').addClass('dcf-txt-sm');
    });
  });");
?>
