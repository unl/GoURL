<?php
    extract($viewParams);
    $qrModals = '';
    if (!isset($appName)) {
        $appName = GoController::$appName;
    }
    $appPart = !empty($appName) ? ' - ' . $appName : '';
    $institutionPart = !empty($institution) ? ' | ' . $institution : '';
    $page->doctitle = 'Your URLs' . $appPart . $institutionPart;

    function generateQRModal($id, $srcPNG, $srcSVG, $appName) {
        $modalId = "qr-modal-" . $id;
        return "
        <div
            class=\"
                go-qr-modal
                dcf-modal
                dcf-bg-overlay-dark
                dcf-fixed
                dcf-pin-top
                dcf-pin-left
                dcf-h-100%
                dcf-w-100%
                dcf-d-flex
                dcf-ai-center
                dcf-jc-center
                dcf-opacity-0
                dcf-pointer-events-none
                dcf-invisible\"
            id=\"" . $modalId . "\"
            aria-labelledby=\"" . $modalId . "-heading\"
            aria-hidden=\"true\"
            role=\"dialog\"
            tabindex=\"-1\"
        >
            <div class=\"dcf-modal-wrapper dcf-relative dcf-h-auto dcf-overflow-y-auto\" role=\"document\">
                <div class=\"dcf-modal-header dcf-wrapper dcf-pt-4 dcf-sticky dcf-pin-top\">
                    <h3 id=\"" . $modalId . "-heading\">"
                        . htmlspecialchars($appName) . " QR Code for &apos;" . htmlspecialchars($id) . "&apos;
                    </h3>
                    <button
                        class=\"
                            dcf-btn-close-modal
                            dcf-btn
                            dcf-btn-tertiary
                            dcf-absolute
                            dcf-pin-top
                            dcf-pin-right
                            dcf-z-1\"
                        type=\"button\"
                        aria-label=\"Close\"
                    >
                        Close
                    </button>
                </div>
                <div
                    class=\"
                        dcf-modal-content
                        dcf-wrapper
                        dcf-pb-4
                        dcf-mt-4
                        dcf-d-flex
                        dcf-flex-wrap
                        dcf-flex-row
                        dcf-ai-center
                        dcf-jc-evenly\"
                >
                    <figure class=\"dcf-mb-4\">
                        <img
                            style=\"max-height: 10rem;\"
                            data-src=\"" . htmlspecialchars($srcPNG) . "\"
                            alt=\"".
                                htmlspecialchars($appName) .
                                " QR Code for &apos;" .
                                htmlspecialchars($id) .
                                "&apos;\"
                        >
                        <figcaption class=\"dcf-figcaption dcf-txt-center\">
                            <a
                                download=\"" . $id . ".png\"
                                href=\"" . htmlspecialchars($srcPNG) . "\"
                                title=\"Download PNG Version\"
                            >
                                PNG Version
                            </a>
                        </figcaption>
                    </figure>
                    <figure class=\"dcf-mb-4\">
                        <img
                            style=\"max-height: 10rem;\"
                            data-src=\"" . htmlspecialchars($srcSVG) . "\"
                            alt=\"".
                                htmlspecialchars($appName) .
                                " QR Code for &apos;" .
                                htmlspecialchars($id) .
                                "&apos;\"
                        >
                        <figcaption class=\"dcf-figcaption dcf-txt-center\">
                            <a
                                download=\"" . $id . ".svg\"
                                href=\"" . htmlspecialchars($srcSVG) . "\"
                                title=\"Download SVG Version\"
                            >
                                SVG Version
                            </a>
                        </figcaption>
                    </figure>
                </div>
            </div>
        </div>
        ";
    }
?>

<div class="dcf-bleed dcf-pt-8 dcf-pb-8">
    <div class="dcf-wrapper">
      <h2 class="dcf-txt-h4">Your URLs</h2>
        <?php $urls = $lilurl->getUserURLs($auth->getUserId()); ?>
        <?php if (count($urls) > 0): ?>
            <table
                id="go-urls"
                class="dcf-w-100% go_responsive_table flush-left dcf-table dcf-txt-sm"
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

                    // Generate QR modal for each GoURL
                    $qrModals .= generateQRModal(
                        $url->urlID,
                        $lilurl->getBaseUrl($url->urlID). '.png',
                        $lilurl->getBaseUrl($url->urlID). '.svg',
                        $appName
                    );
                    $longURLDisplay = strlen($url->longURL) > 30 ?
                        substr($url->longURL, 0, 30)."..." : htmlspecialchars($url->longURL);
                    ?>
                    <tr>
                        <td data-header="Short URL">
                                <a
                                    href="<?php echo htmlspecialchars($lilurl->getBaseUrl($url->urlID)); ?>"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    <?php echo htmlspecialchars($url->urlID ?? ''); ?>
                                </a>
                        </td>
                        <td data-header="Long URL">
                            <a
                                href="<?php echo $lilurl->escapeURL($url->longURL); ?>"
                                title="Full URL: <?php echo htmlspecialchars($url->longURL ?? ''); ?>"
                            >
                                <?php echo $longURLDisplay; ?>
                            </a>
                        </td>
                        <td data-header="Group">
                                <?php echo !empty($url->groupName) ? htmlspecialchars($url->groupName) : 'N/A'; ?>
                        </td>
                        <td data-header="Redirects">
                            <?php echo htmlspecialchars($url->redirects ?? ''); ?>
                        </td>
                        <td
                            data-header="LastRedirect"
                            <?php if ($lastRedirect): ?>
                                data-search="<?php echo $lastRedirect->format('M j, Y m/d/Y') ?>"
                                data-order="<?php echo $lastRedirect->format('U') ?>"
                            <?php endif; ?>
                        >
                            <?php echo !empty($lastRedirect) ? $lastRedirect->format('M j, Y') : 'Never'; ?>
                        </td>
                        <td
                            data-header="Created on"
                            <?php if ($submitDate): ?>
                                data-search="<?php echo $submitDate->format('M j, Y m/d/Y') ?>"
                                data-order="<?php echo $submitDate->format('U') ?>"
                            <?php endif; ?>
                        >
                            <?php echo !empty($submitDate) ? $submitDate->format('M j, Y') : 'N/A'; ?>
                        </td>
                        <td class="dcf-txt-sm">
                            <div class="dcf-d-flex dcf-flex-wrap dcf-col-gap-1 dcf-row-gap-1">
                                <button
                                    class="dcf-btn dcf-btn-secondary dcf-btn-toggle-modal"
                                    data-toggles-modal="qr-modal-<?php echo htmlspecialchars($url->urlID ?? ''); ?>"
                                    type="button"
                                    title="QR Code for <?php echo htmlspecialchars($url->urlID ?? ''); ?> URL"
                                >
                                    <span class="qrImage"></span>
                                    QR Code®
                                </button>
                                <a
                                    class="dcf-btn dcf-btn-secondary"
                                    href="<?php echo htmlspecialchars($lilurl->getBaseUrl($url->urlID . '/edit')); ?>"
                                    title="Edit <?php echo htmlspecialchars($url->urlID ?? ''); ?> URL"
                                >
                                    Edit
                                </a>
                                <a
                                    class="dcf-btn dcf-btn-secondary"
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
                                        class="dcf-btn dcf-btn-primary"
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
$page->addScriptDeclaration("
    // get all the modals
    document.querySelectorAll('.go-qr-modal').forEach((modal) => {

        // when the modal opens
        document.addEventListener('ModalOpenEvent_' + modal.id, (e) => {

            // load any images that have not been loaded yet
            modal.querySelectorAll(`[data-src]:not([src])`).forEach((img) => {
                img.src = img.dataset.src;
            });
        });
    });
");
$page->addScriptDeclaration("
require(['jquery', '/js/datatables-1.10.21.min.js'], function(jq) {
    jq(function($) {
        // do not place in dom
        var \$entities = $('<textarea />');

        //https://datatables.net/reference/option/columnDefs
        //https://datatables.net/reference/option/columns.data

        $('#go-urls').DataTable({
            'oLanguage': {
                'sSearch': 'Search'
            },
            'columnDefs': [
                {
                    'targets': 1,
                    'data': function (row, type, val, meta) {
                        if (type === 'set') {
                            row.d = val;
                            row.d_display = val;

                            // DOM parser will decode any entities in the url ( like &amp; )
                            // strip 'Full URL:' from the search text if present
                            \$entities.html(/^(?:full\s*url\s*:\s*)?(.*)$/i.exec($(val).attr('title'))[1]);

                            row.d_filter = \$entities.val();
                            return;

                        } else if (type === 'display') {
                            return row.d_display;

                        } else if (type === 'filter') {
                            return row.d_filter;

                        }

                        return row.d;
                    }
                }
            ]
        });
        $('.dataTables_length label').addClass('dcf-label');
        $('.dataTables_length select').addClass('dcf-input-select dcf-d-inline-block dcf-w-10 dcf-txt-sm');
        $('.dataTables_filter label').addClass('dcf-label');
        $('.dataTables_filter label input').addClass('dcf-d-inline dcf-input-text dcf-txt-sm');
        $('.dataTables_info, .dataTables_paginate, .dataTables_paginate a').addClass('dcf-txt-sm');
    });
});");
?>

<?php
// Display QR Modal Markup
echo $qrModals;
?>
