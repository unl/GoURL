<?php
    extract($viewParams);
    if (!isset($appName)) {
        $appName = GoController::$appName;
    }
    $appPart = !empty($appName) ? ' - ' . $appName : '';
    $institutionPart = !empty($institution) ? ' | ' . $institution : '';
    $page->doctitle = 'Your URLs' . $appPart . $institutionPart;
?>

<div class="dcf-bleed dcf-pt-8 dcf-pb-8">
    <div class="dcf-wrapper">
      <h2 class="dcf-txt-h4">Your URLs</h2>
        <?php $urls = $lilurl->getUserURLs($auth->getUserId()); ?>
        <?php if (count($urls) > 0): ?>
            <div id="loading_table_spinner" class="dcf-progress-spinner"></div>
            <div id="table_wrapper" class="dcf-d-none!">
                <table
                    id="go-urls"
                    class="dcf-w-100% go_responsive_table flush-left dcf-table"
                    data-order="[[ 4, &quot;desc&quot; ]]"
                >
                    <caption class="dcf-sr-only">Your Go URLs</caption>
                    <thead>
                        <tr>
                            <th scope="col">Short URL</th>
                            <th scope="col">Long URL</th>
                            <th scope="col">Group</th>
                            <th scope="col">Redirects</th>
                            <th scope="col">Last Redirect</th>
                            <th scope="col">Created&nbsp;on</th>
                            <th scope="col" data-searchable="false" data-orderable="false">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($urls as $url) : ?>
                        <?php
                        $submitDate = $lilurl->createDateTimeFromTimestamp($url->submitDate);
                        $lastRedirect = $lilurl->createDateTimeFromTimestamp($url->lastRedirect);
                        $longURLDisplay = htmlspecialchars($url->longURL);
                        ?>
                        <tr>
                            <td class="dcf-txt-sm" data-header="Short URL">
                                    <a
                                        href="<?php echo htmlspecialchars($lilurl->getBaseUrl($url->urlID)); ?>"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        <?php echo htmlspecialchars($url->urlID ?? ''); ?>
                                    </a>
                            </td>
                            <td class="dcf-txt-sm" data-header="Long URL">
                                <a
                                    href="<?php echo $lilurl->escapeURL($url->longURL); ?>"
                                    title="Full URL: <?php echo htmlspecialchars($url->longURL ?? ''); ?>"
                                >
                                    <span style="display: inline-block; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo $longURLDisplay; ?>
                                    </span>
                                </a>
                            </td>
                            <td class="dcf-txt-sm" data-header="Group">
                                    <?php echo !empty($url->groupName) ? htmlspecialchars($url->groupName) : 'N/A'; ?>
                            </td>
                            <td class="dcf-txt-sm" data-header="Redirects">
                                <?php echo htmlspecialchars($url->redirects ?? ''); ?>
                            </td>
                            <td
                                class="dcf-txt-sm"
                                data-header="LastRedirect"
                                <?php if ($lastRedirect): ?>
                                    data-search="<?php echo $lastRedirect->format('M j, Y m/d/Y') ?>"
                                    data-order="<?php echo $lastRedirect->format('U') ?>"
                                <?php endif; ?>
                            >
                                <?php echo !empty($lastRedirect) ? $lastRedirect->format('M j, Y') : 'Never'; ?>
                            </td>
                            <td
                                class="dcf-txt-sm"
                                data-header="Created on"
                                <?php if ($submitDate): ?>
                                    data-search="<?php echo $submitDate->format('M j, Y m/d/Y') ?>"
                                    data-order="<?php echo $submitDate->format('U') ?>"
                                <?php endif; ?>
                            >
                                <?php echo !empty($submitDate) ? $submitDate->format('M j, Y') : 'N/A'; ?>
                            </td>
                            <td>
                                <div class="dcf-d-flex dcf-flex-wrap dcf-col-gap-1 dcf-row-gap-1">
                                    <?php echo $savvy->render((object) array(
                                        "id" => $url->urlID,
                                        "srcPNG" => $lilurl->getBaseUrl($url->urlID). '.png',
                                        "srcSVG" => $lilurl->getBaseUrl($url->urlID). '.svg',
                                        "appName" => $appName,
                                        ), "qrCodeModal.php");
                                    ?>
                                    <button
                                        class="dcf-btn dcf-btn-secondary dcf-btn-toggle-dialog dcf-txt-xs"
                                        data-controls="qr-modal-<?php echo htmlspecialchars($url->urlID ?? ''); ?>"
                                        type="button"
                                        title="QR Code for <?php echo htmlspecialchars($url->urlID ?? ''); ?> URL"
                                    >
                                        <span class="qrImage"></span>
                                        QR Code®
                                    </button>
                                    <a
                                        class="dcf-btn dcf-btn-secondary dcf-txt-xs"
                                        href="<?php echo htmlspecialchars($lilurl->getBaseUrl($url->urlID . '/edit')); ?>"
                                        title="Edit <?php echo htmlspecialchars($url->urlID ?? ''); ?> URL"
                                    >
                                        Edit
                                    </a>
                                    <a
                                        class="dcf-btn dcf-btn-secondary dcf-txt-xs"
                                        href="<?php echo htmlspecialchars($lilurl->getBaseUrl($url->urlID . '/reset')); ?>"
                                        title="Reset redirect count for <?php
                                            echo htmlspecialchars($url->urlID ?? '');
                                            ?> URL"
                                        onclick="return confirm('Are you sure you want to reset the redirect count for \'
                                            <?php echo htmlspecialchars($url->urlID ?? ''); ?>\'?');"
                                    >
                                        Reset Redirects
                                    </a>
                                    <form
                                        class="dcf-form dcf-mb-0"
                                        action="<?php echo htmlspecialchars($lilurl->getBaseUrl('a/links')); ?>"
                                        method="post"
                                    >
                                        <input
                                            type="hidden"
                                            name="urlID"
                                            value="<?php echo htmlspecialchars($url->urlID ?? ''); ?>"
                                        />
                                        <button
                                            class="dcf-btn dcf-btn-primary dcf-txt-xs"
                                            type="submit"
                                            onclick="return confirm('Are you for sure you want to delete \'
                                                <?php echo htmlspecialchars($url->urlID ?? ''); ?>\'?');"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>You haven't created any Go URLs, yet.</p>
        <?php endif;?>
        <div class="dcf-mt-6 dcf-mb-6">
            <a
                class="dcf-btn dcf-btn-primary dcf-mr-6"
                href="<?php echo htmlspecialchars($lilurl->getBaseUrl()); ?>"
            >
                Add URL
            </a>
            <span class="dcf-d-inline-block dcf-mt-6 dcf-mt-0@md dcf-form-help">
                <?php echo GoController::URL_AUTO_PURGE_NOTICE; ?>
            </span>
        </div>
    </div>
</div>

<?php
    $page->addScript($lilurl->getBaseUrl() . 'js/manage.js?v=' . filemtime(__DIR__ . '/../js/manage.js') , 'module');
?>