<?php
extract($viewParams);

// Load JQuery dataTables for filtering GoURLs
$page->addScript($lilurl->getBaseUrl('js/datatables-1.10.21.min.js'), NULL, TRUE);
$appPart = !empty($appName) ? ' - ' . $appName : '';
$institutionPart = !empty($institution) ? ' | ' . $institution : '';
$page->doctitle = 'Your Groups' . $appPart . $institutionPart;

?>

<div class="dcf-bleed dcf-pt-4 dcf-pb-8">
  <div class="dcf-wrapper">
    <h2 class="dcf-txt-h4">Your Groups</h2>
    <?php $groups = $lilurl->getUserGroups($auth->getUserId()); ?>
    <?php if (count($groups) > 0): ?>
      <table id="groups" class="dcf-w-100% go_responsive_table flush-left dcf-table dcf-txt-sm" data-order="[[ 0, &quot;asc&quot; ]]">
        <caption class="dcf-sr-only">Your Groups</caption>
        <thead class="unl-bg-lighter-gray">
        <tr>
          <th scope="col">Group</th>
          <th scope="col" data-searchable="false" data-orderable="false">Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($groups as $group): ?>
          <?php
          ?>
          <tr class="unl-bg-cream">
            <td data-header="Group"><?php echo $group->groupName; ?></td>
            <td class="dcf-txt-sm">
              <a class="dcf-btn dcf-btn-secondary dcf-mt-1" href="<?php echo htmlspecialchars($lilurl->getBaseUrl('a/group/' . $group->groupID)) ?>" title="Edit <?php echo $group->groupID; ?> Go URL" >Edit</a>
              <form class="dcf-form dcf-d-inline" action="<?php echo htmlspecialchars($lilurl->getBaseUrl('a/groups')) ?>" method="post">
                <input type="hidden" name="groupID" value="<?php echo $group->groupID; ?>" />
                <button class="dcf-btn dcf-btn-primary dcf-mt-1" type="submit" onclick="return confirm('Are you for sure you want to delete group, \'<?php echo $group->groupName; ?>\'?');">Delete</button>
              </form>
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
<script>
  jQuery(document).ready(function($) {
    $('#groups').DataTable();
    $('.dataTables_length label').addClass('dcf-label');
    $('.dataTables_length select').addClass('dcf-input-select dcf-d-inline-block dcf-w-10 dcf-txt-sm');
    $('.dataTables_filter label').addClass('dcf-label');
    $('.dataTables_filter label input').addClass('dcf-d-inline dcf-input-text dcf-txt-sm');
    $('.dataTables_info, .dataTables_paginate, .dataTables_paginate a').addClass('dcf-txt-sm');
  });
</script>
