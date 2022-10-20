<?php
extract($viewParams);
$rowDateTime = null;
if (!isset($appName)) {
    $appName = null;
}
$appPart = !empty($appName) ? ' - ' . $appName : '';
$institutionPart = !empty($institution) ? ' | ' . $institution : '';
$page->doctitle = 'URL Info' . $appPart . $institutionPart;
$lookupTerm = !empty($_POST['lookupTerm']) ? $_POST['lookupTerm'] : '';
$http = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === TRUE ? 'https://' : 'http://';
$exampleURLID = 'sample';
$exampleURL = $http . $_SERVER['SERVER_NAME'] . $lilurl->getBaseUrl($exampleURLID);
?>
<div class="dcf-bleed dcf-pt-8 dcf-pb-8">
    <div class="dcf-wrapper">
        <div class="dcf-grid-full dcf-grid-halves@md dcf-col-gap-8 dcf-row-gap-8">
            <div>
                <h2 class="dcf-txt-h4"><?php echo $appName; ?> Lookup</h2>
                <form class="dcf-form" id="lookup-form" method="post" action="<?php echo htmlspecialchars($lilurl->getBaseUrl('a/lookup')) ?>">
                    <label for="lookupTerm">Short <abbr title="Uniform Resource Locator">URL</abbr></label>
                    <div class="dcf-input-group">
                        <input id="lookupTerm" name="lookupTerm" type="text" value="<?php echo trim($lookupTerm); ?>" required >
                        <button class="dcf-btn dcf-btn-primary" id="lookup-submit" name="submit" type="submit">Search</button>
                    </div>
                    <span class="dcf-form-help">Lookup only searches against short <abbr title="Uniform Resource Locators">URLs</abbr> with exact match. To lookup <em><?php echo htmlspecialchars($exampleURL); ?></em>, search for <em><?php echo htmlspecialchars($exampleURLID); ?></em>.</span>
                </form>
            </div>
            <?php if (!empty($link)) :
                $shorURL = $http . $_SERVER['SERVER_NAME'] . $lilurl->getBaseUrl($link->urlID);
            ?>
            <div>
                <h2 class="dcf-txt-h4">Details for &apos;<?php echo $link->urlID ?>&apos;</h2>
                <dl class="dcf-txt-sm">
                    <dt><?php echo $appName; ?></dt>
                    <dd class="dcf-pl-6"><a href="<?php echo htmlspecialchars($shorURL); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($shorURL); ?></a></dd>

                    <dt>Long URL</dt>
                    <dd class="dcf-pl-6"><a href="<?php echo htmlspecialchars($lilurl->escapeURL($link->longURL)) ?>" target="_blank" rel="noopener"><?php echo $lilurl->escapeURL($link->longURL) ?></a></dd>

                    <dt>Redirect Count</dt>
                    <dd class="dcf-pl-6"><?php echo $link->redirects ?></dd>

                    <dt>Last Redirect</dt>
                    <dd class="dcf-pl-6"><?php
	                    if ($link->lastRedirect) {
		                    $lastRedirect = $lilurl->createDateTimeFromTimestamp($link->lastRedirect);
		                    echo !empty($lastRedirect) ? $lastRedirect->format('F j, Y') : 'Never';
	                    } else {
	                        echo 'Never';
                        }
                    ?></dd>

                    <?php if ($link->submitDate): ?>
                        <dt>Created On</dt>
                        <dd class="dcf-pl-6"><?php
	                        $createdOn = $lilurl->createDateTimeFromTimestamp($link->submitDate);
	                        echo !empty($createdOn) ? $createdOn->format('F j, Y') : 'Unknown';
                        ?></dd>
                    <?php endif;?>

                    <dt>Owner/Created By</dt>
                    <dd class="dcf-pl-6">
                    <?php if ($link->createdBy): ?>
                        <?php echo $lilurl->escapeURL($link->createdBy) ?></a>
                    <?php else: ?>
                        Anonymous
                    <?php endif; ?>
                    </dd>

                    <dt>Group</dt>
                    <dd class="dcf-pl-6">
                    <?php if (!empty($group)): ?>
                    <?php echo $group->groupName ?>
                    <?php else: ?>
                        None
                    <?php endif; ?>
                    </dd>

                    <?php if (!empty($group)): ?>
                    <dt>Group Users</dt>
                    <dd class="dcf-pl-6">
                        <?php if (!empty($group->users)): ?>
                        <ul class="dcf-list-bare">
                        <?php foreach($group->users as $index => $user): ?>
                            <li><?php echo $user->uid; ?></li>
                        <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        None
                        <?php endif; ?>
                    </dd>
                    <?php endif; ?>


                    <?php if ($lilurl->checkOldURL($link->urlID)): ?>
                        <form class="dcf-form dcf-mb-0" action="<?php echo htmlspecialchars($lilurl->getBaseUrl('a/links')) ?>" method="post">
                            <input type="hidden" name="urlID" value="<?php echo $link->urlID; ?>" />
                            <p class="dcf-bg-white dcf-p-4 dcf-rounded">
                                This URL has NOT been used or created in the past two years. You may delete this URL if you would like to use it for a different purpose.
                                <button class="dcf-btn dcf-btn-primary dcf-d-block dcf-mt-4" type="submit" onclick="return confirm('Are you for sure you want to delete \'<?php echo $link->urlID; ?>\'?');">Delete</button>
                            </p>
                        </form>
                    <?php else:?>
                        <p class="dcf-bg-white dcf-p-4 dcf-rounded">
                            This URL has been used or created in the past two years, so you will be unable to delete it for now, but you can always ask the person who created to delete it.
                        </p>
                    <?php endif; ?>
                    
                </dl>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
