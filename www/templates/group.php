<?php
    $users = NULL;
    $groupMode = goController::MODE_CREATE;
    extract($viewParams);
    $groupID = NULL;
    $groupName = !empty($_POST) && array_key_exists('groupName', $_POST) ? $_POST['groupName'] : '';
    $uid = !empty($_POST) && array_key_exists('uid', $_POST) ? $_POST['uid'] : '';
    $saveBtnLabel = 'Add';
    if ($groupMode === goController::MODE_EDIT && isset($group)) {
        $groupID = $group->groupID;
        if (empty($groupName)) {
          $groupName = $group->groupName;
        }
        $saveBtnLabel = 'Update';
    }
?>
<div class="dcf-bleed dcf-pt-8 dcf-pb-8">
    <div class="dcf-wrapper">
        <h2 class="dcf-txt-h4">Manage Group</h2>
        <form class="dcf-form dcf-w-max-lg" id="group-form" method="post">
            <input type="hidden" name="formName" value="group-form">
            <input type="hidden" name="groupID" value="<?php echo $groupID; ?>">
            <label for="groupName">Name <small class="dcf-required">Required</small></label>
            <div class="dcf-input-group">
                <input id="groupName" name="groupName" type="text" value="<?php echo trim($groupName); ?>" required >
                <button class="dcf-btn dcf-btn-primary" id="group-name-submit" name="submit" type="submit"><?php echo $saveBtnLabel; ?></button>
            </div>
        </form>

        <?php if (isset($group->users)) { ?>
            <h2 class="dcf-mt-6 dcf-txt-h5">Group Users</h2>
            <form class="dcf-form dcf-w-max-lg" id="user-form" method="post" action="<?php echo htmlspecialchars($lilurl->getBaseUrl(goController::ROUTE_PATH_GROUP_USER_ADD) . '/' . $groupID); ?>">
                <input type="hidden" name="formName" value="user-form">
                <input type="hidden" name="groupID" value="<?php echo $groupID; ?>">
                <label for="uid">Username <small class="dcf-required">Required</small></label>
                <div class="dcf-input-group">
                    <input id="uid" name="uid" type="text" value="<?php echo trim($uid); ?>" required >
                    <button class="dcf-btn dcf-btn-primary" id="add-user-submit" name="submit" type="submit">Add User</button>
                </div>
            </form>
            <ol class="dcf-list-inline dcf-mt-6 dcf-p-0">
            <?php foreach($group->users as $index => $user) { ?>
                <li class="dcf-p-2"><?php echo $user->uid; ?>&nbsp;<a class="dcf-btn dcf-btn-secondary dcf-txt-3xs" href="<?php echo htmlspecialchars($lilurl->getBaseUrl(goController::ROUTE_PATH_GROUP_USER_REMOVE . '/' . $groupID . '-'. urlencode($user->uid))) ?>" title="Remove <?php echo $user->uid;?> from <?php echo $group->groupName; ?>" >&times;</a></li>
            <?php } ?>
            </ol>
        <?php } ?>

  </div>
</div>
