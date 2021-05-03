<?php
extract($viewParams);
$rowDateTime = null;
$appPart = !empty($appName) ? ' - ' . $appName : '';
$institutionPart = !empty($institution) ? ' | ' . $institution : '';
$page->doctitle = 'URL Info' . $appPart . $institutionPart;
$lookupTerm = !empty($_POST['lookupTerm']) ? $_POST['lookupTerm'] : '';
?>
<div class="dcf-bleed dcf-pt-8 dcf-pb-8">
    <div class="dcf-wrapper">
        <div class="dcf-grid-full dcf-grid-halves@md dcf-col-gap-8 dcf-row-gap-8">
            <div>
                <h2 class="dcf-txt-h4"><?php echo $appName; ?> Lookup</h2>
                <form class="dcf-form" id="lookup-form" method="post" action="<?php echo htmlspecialchars($lilurl->getBaseUrl('a/lookup')) ?>">
                    <label>Short <abbr title="Uniform Resource Locator">URL</abbr></label>
                    <div class="dcf-input-group">
                        <input id="lookupTerm" name="lookupTerm" type="text" value="<?php echo trim($lookupTerm); ?>" required >
                        <button class="dcf-btn dcf-btn-primary" id="submit" name="submit" type="submit">Search</button>
                    </div>
                    <span class="dcf-form-help">Lookup only searches against short <abbr title="Uniform Resource Locators">URLs</abbr>.</span>
                </form>
            </div>
            <?php if (!empty($link)) :
                $http = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === TRUE ? 'https://' : 'http://';
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
                </dl>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
