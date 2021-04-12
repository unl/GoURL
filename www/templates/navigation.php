<ul>
    <?php if ($auth->isAuthenticated()): ?>
        <li><a href="<?php echo htmlspecialchars($lilurl->getBaseUrl('a/links')) ?>">Your URLs</a></li>
        <li><a href="<?php echo htmlspecialchars($lilurl->getBaseUrl('a/groups')) ?>">Your Groups</a></li>
        <li><a href="<?php echo htmlspecialchars($lilurl->getBaseUrl('a/lookup')) ?>">URL Lookup</a></li>
        <li><a href="<?php echo htmlspecialchars($lilurl->getBaseUrl('a/logout')) ?>">Log out as <?php echo $auth->getUserDisplayName(); ?></a></li>
    <?php else: ?>
        <li><a href="<?php echo htmlspecialchars($lilurl->getBaseUrl('a/login')) ?>">Log In</a></li>
    <?php endif ?>
</ul>