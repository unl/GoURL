<?php
    extract($viewParams);
    $qrModals = '';
    // Load JQuery dataTables for filtering GoURLs
    $page->addScript($lilurl->getBaseUrl('js/datatables-1.10.21.min.js'), NULL, TRUE);
    if (!isset($appName)) {
        $appName = GoController::$appName;
    }
    $appPart = !empty($appName) ? ' - ' . $appName : '';
    $institutionPart = !empty($institution) ? ' | ' . $institution : '';
    $page->doctitle = 'Your URLs' . $appPart . $institutionPart;

    function generateQRModal($id, $src, $appName) {
        $modalId = "qr-modal-" . $id;
        return "<div class=\"dcf-modal dcf-bg-overlay-dark dcf-fixed dcf-pin-top dcf-pin-left dcf-h-100% dcf-w-100% dcf-d-flex dcf-ai-center dcf-jc-center dcf-opacity-0 dcf-pointer-events-none dcf-invisible\" id=\"" . $modalId . "\" aria-labelledby=\"" . $modalId . "-heading\" aria-hidden=\"true\" role=\"dialog\" tabindex=\"-1\">
        <div class=\"dcf-modal-wrapper dcf-relative dcf-h-auto dcf-overflow-y-auto\" role=\"document\">
            <div class=\"dcf-modal-header dcf-wrapper dcf-pt-4 dcf-sticky dcf-pin-top\">
                <h3 id=\"" . $modalId . "-heading\">". $appName . " QR Code for &apos;" . $id . "&apos;</h3>
                <button class=\"dcf-btn-close-modal dcf-btn dcf-btn-tertiary dcf-absolute dcf-pin-top dcf-pin-right dcf-z-1\" type=\"button\" aria-label=\"Close\">Close</button>
            </div>
            <div class=\"dcf-modal-content dcf-wrapper dcf-pb-4\">
                <img style=\"max-height: 60vh;\" src=\"" . htmlspecialchars($src) . "\" alt=\"". $appName . " QR Code for &apos;" . $id ."&apos;\">
            </div>
        </div>
    </div>";
    }
?>

<div class="dcf-bleed dcf-pt-8 dcf-pb-8">
    <div class="dcf-wrapper">
      <h2 class="dcf-txt-h4">Your URLs</h2>
        <?php $urls = $lilurl->getUserURLs($auth->getUserId()); ?>
        <?php if (count($urls) > 0): ?>
            <table id="go-urls" class="dcf-w-100% go_responsive_table flush-left dcf-table dcf-txt-sm" data-order="[[ 4, &quot;desc&quot; ]]">
                <caption class="dcf-sr-only">Your Go URLs</caption>
                <thead class="unl-bg-lighter-gray">
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

                    // Generate QR modal for each GoURL
                    $qrModals .= generateQRModal($url->urlID, $lilurl->getBaseUrl($url->urlID). '.qr', $appName);
                    $longURLDisplay = strlen($url->longURL) > 30 ? substr($url->longURL,0,30)."..." : $url->longURL;
                    ?>
                    <tr class="unl-bg-cream">
                        <td data-header="Short URL"><a href="<?php echo htmlspecialchars($lilurl->getBaseUrl($url->urlID)); ?>" target="_blank" rel="noopener"><?php echo $url->urlID; ?></a></td>
                        <td data-header="Long URL"><a href="<?php echo $lilurl->escapeURL($url->longURL); ?>" title="Full URL: <?php echo $url->longURL; ?>"><?php echo $longURLDisplay; ?></a></td>
                        <td data-header="Group"><?php echo !empty($url->groupName) ? $url->groupName : 'N/A' ?></td>
                        <td data-header="Redirects"><?php echo $url->redirects ?></td>
                        <td data-header="LastRedirect"<?php if ($lastRedirect): ?> data-search="<?php echo $lastRedirect->format('M j, Y m/d/Y') ?>" data-order="<?php echo $lastRedirect->format('U') ?>"<?php endif; ?>>
                            <?php echo !empty($lastRedirect) ? $lastRedirect->format('M j, Y') : 'Never'; ?>
                        </td>
                        <td data-header="Created on"<?php if ($submitDate): ?> data-search="<?php echo $submitDate->format('M j, Y m/d/Y') ?>" data-order="<?php echo $submitDate->format('U') ?>"<?php endif; ?>>
                            <?php echo !empty($submitDate) ? $submitDate->format('M j, Y') : 'N/A'; ?>
                        </td>
                        <td class="dcf-txt-sm">
                            <button class="dcf-btn dcf-btn-secondary dcf-btn-toggle-modal dcf-mt-1" data-toggles-modal="qr-modal-<?php echo $url->urlID; ?>" type="button" title="QR Code for <?php echo $url->urlID; ?> URL"><span class="qrImage"></span> QR Code®</button>
                            <a class="dcf-btn dcf-btn-secondary dcf-mt-1" href="<?php echo htmlspecialchars($lilurl->getBaseUrl($url->urlID . '/edit')) ?>" title="Edit <?php echo $url->urlID; ?> URL" >Edit</a>
                            <a class="dcf-btn dcf-btn-secondary dcf-mt-1" href="<?php echo htmlspecialchars($lilurl->getBaseUrl($url->urlID . '/reset')) ?>" title="Reset redirect count for <?php echo $url->urlID; ?> URL" onclick="return confirm('Are you sure you want to reset the redirect count for \'<?php echo $url->urlID; ?>\'?');">Reset Redirects</a>
                            <form class="dcf-form dcf-d-inline" action="<?php echo htmlspecialchars($lilurl->getBaseUrl('a/links')) ?>" method="post">
                                <input type="hidden" name="urlID" value="<?php echo $url->urlID; ?>" />
                                <button class="dcf-btn dcf-btn-primary dcf-mt-1" type="submit" onclick="return confirm('Are you for sure you want to delete \'<?php echo $url->urlID; ?>\'?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You haven't created any Go URLs, yet.</p>
        <?php endif;?>
        <div class="dcf-mt-6 dcf-mb-6">
            <a class="dcf-btn dcf-btn-primary dcf-mr-6" href="<?php echo htmlspecialchars($lilurl->getBaseUrl()); ?>">Add URL</a>
            <span class="dcf-d-inline-block dcf-mt-6 dcf-mt-0@md dcf-form-help"><?php echo GoController::URL_AUTO_PURGE_NOTICE; ?></span>
        </div>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    $('#go-urls').DataTable({
      "oLanguage": {
        "sSearch": "Search"
      }
    });
    $('.dataTables_length label').addClass('dcf-label');
    $('.dataTables_length select').addClass('dcf-input-select dcf-d-inline-block dcf-w-10 dcf-txt-sm');
    $('.dataTables_filter label').addClass('dcf-label');
    $('.dataTables_filter label input').addClass('dcf-d-inline dcf-input-text dcf-txt-sm');
    $('.dataTables_info, .dataTables_paginate, .dataTables_paginate a').addClass('dcf-txt-sm');
});
</script>

<?php
// Display QR Modal Markup
echo $qrModals;
?>
