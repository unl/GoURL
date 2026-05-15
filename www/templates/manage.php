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
                                <?php echo $savvy->render((object) array(
                                    "id" => $url->urlID,
                                    "srcPNG" => $lilurl->getBaseUrl($url->urlID). '.png',
                                    "srcSVG" => $lilurl->getBaseUrl($url->urlID). '.svg',
                                    "appName" => $appName,
                                    ), "qrCodeModal.php");
                                ?>
                                <button
                                    class="dcf-btn dcf-btn-secondary dcf-btn-toggle-dialog"
                                    data-controls="qr-modal-<?php echo htmlspecialchars($url->urlID ?? ''); ?>"
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

<script src="/wdn/templates_6.0/js/auto-loader.js" type="module"></script>

<script type="module">
    import * as datatables from '/wdn/templates_6.0/js/plugins/plugin.datatables.js';

    const $ = await datatables.initialize();

    $('#go-urls').DataTable({
        responsive: true,

        // Sort by "Last Redirect" column descending by default
        order: [[4, 'desc']],

        columnDefs: [
            // Last Redirect column
            {
                targets: 4,
                type: 'date'
            },

            // Created On column
            {
                targets: 5,
                type: 'date'
            },

            // Actions column should not be sortable/searchable
            {
                targets: 6,
                orderable: false,
                searchable: false
            },

            // Center redirect counts
            {
                targets: 3,
                className: 'dt-center'
            }
        ],

        buttons: [
            {
                extend: 'csvHtml5',
                text: 'Download CSV',
                title: 'your_go_urls',
                bom: true,

                // Exclude Actions column from export
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                }
            }
        ],

        layout: {
            topStart: 'pageLength',
            topEnd: ['search', 'buttons'],
            bottomStart: 'info',
            bottomEnd: 'paging'
        },

        pageLength: 25,

        language: {
            search: 'Search:',
            lengthMenu: 'Show _MENU_ URLs per page',
            info: 'Showing _START_ to _END_ of _TOTAL_ URLs',
            emptyTable: 'No URLs found'
        }
    });
</script>