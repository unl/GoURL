<?php
    $qrModals = '';
    // Load JQuery dataTables for filtering GoURLs
    $page->addScript($lilurl->getBaseUrl('js/datatables-1.10.21.min.js'), NULL, TRUE);
    //$page->addStyleSheet('https://cdn.datatables.net/1.10.21/css/jquery.dataTables.css');
    $page->doctitle = 'Your URLs - Go URL | University of Nebraska&ndash;Lincoln';
?>

<div class="dcf-bleed dcf-pt-8 dcf-pb-8">
    <div class="dcf-wrapper">
      <h2 class="dcf-txt-h4">Your Go URLs</h2>
        <?php $urls = $lilurl->getUserURLs(phpCAS::getUser()); ?>
        <?php if ($urls->columnCount()): ?>
            <table id="go-urls" class="dcf-w-100% go_responsive_table flush-left dcf-table dcf-txt-sm" data-order="[[ 3, &quot;desc&quot; ]]">
                <thead class="unl-bg-lighter-gray">
                    <tr>
                        <th>Short URL</th>
                        <th>Long URL</th>
                        <th>Redirects</th>
                        <th>Created&nbsp;on</th>
                        <th data-searchable="false" data-orderable="false">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $urls->fetch(PDO::FETCH_ASSOC)): ?>
                    <?php
                    $rowDateTime = null;
                    if ($row['submitDate'] !== '0000-00-00 00:00:00') {
                        $rowDateTime = new DateTime($row['submitDate']);
                    }
                    // Generate QR modal for each GoURL
                    $qrModals .= generateQRModal($row['urlID'], $lilurl->getBaseUrl($row['urlID']). '.qr');
                    $longURLDisplay = strlen($row['longURL']) > 50 ? substr($row['longURL'],0,50)."..." : $row['longURL'];
                    ?>
                    <tr class="unl-bg-cream">
                        <td data-header="Short URL"><a href="<?php echo $lilurl->getBaseUrl($row['urlID']); ?>" target="_blank"><?php echo $row['urlID']; ?></a></td>
                        <td data-header="Long URL"><a href="<?php echo $lilurl->escapeURL($row['longURL']); ?>"><?php echo $longURLDisplay; ?></a></td>
                        <td data-header="Redirects"><?php echo $row['redirects'] ?></td>
                        <td data-header="Created on"<?php if ($rowDateTime): ?> data-search="<?php echo $rowDateTime->format('M j, Y m/d/Y') ?>" data-order="<?php echo $rowDateTime->format('U') ?>"<?php endif; ?>>
                            <?php if ($rowDateTime): ?>
                                <?php echo $rowDateTime->format('M j, Y') ?>
                            <?php endif; ?>
                        </td>
                        <td class="dcf-txt-sm">
                            <button class="dcf-btn dcf-btn-secondary dcf-btn-toggle-modal dcf-mt-1" data-toggles-modal="qr-modal-<?php echo $row['urlID']; ?>" type="button" title="QR Code for <?php echo $row['urlID']; ?> Go URL"><span class="qrImage"></span> QR Code®</button>
                            <a class="dcf-btn dcf-btn-secondary dcf-mt-1" href="<?php echo $lilurl->getBaseUrl($row['urlID'] . '/edit') ?>" title="Edit <?php echo $row['urlID']; ?> Go URL" >Edit</a>
                            <a class="dcf-btn dcf-btn-secondary dcf-mt-1" href="<?php echo $lilurl->getBaseUrl($row['urlID'] . '/reset') ?>" title="Reset redirect count for <?php echo $row['urlID']; ?> Go URL" onclick="return confirm('Are you sure you want to reset the redirect count for \'<?php echo $row['urlID']; ?>\'?');">Reset Redirects</a>
                            <form class="dcf-form dcf-d-inline" action="<?php echo $lilurl->getBaseUrl('a/links') ?>" method="post">
                                <input type="hidden" name="urlID" value="<?php echo $row['urlID']; ?>" />
                                <button class="dcf-btn dcf-btn-primary dcf-mt-1" type="submit" onclick="return confirm('Are you for sure you want to delete \'<?php echo $row['urlID']; ?>\'?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You haven't created any Go URLs, yet.</p>
        <?php endif;?>
        <div class="dcf-mt-6 dcf-mb-6">
            <a class="dcf-btn dcf-btn-primary" href="<?php echo $lilurl->getBaseUrl(); ?>">Add URL</a>
        </div>
    </div>
</div>

<?php
// Display QR Modal Markup
echo $qrModals;

$page->addScriptDeclaration("
$(function() {
    $.noConflict();
    $('#go-urls').DataTable();
    $('.dataTables_length label').addClass('dcf-label');
    $('.dataTables_length select').addClass('dcf-input-select dcf-d-inline-block dcf-w-10 dcf-txt-sm');
    $('.dataTables_filter label').addClass('dcf-label');
    $('.dataTables_filter label input').addClass('dcf-d-inline dcf-input-text dcf-txt-sm');
    $('.dataTables_info, .dataTables_paginate, .dataTables_paginate a').addClass('dcf-txt-sm');
});");

function generateQRModal($id, $src) {
    $modalId = "qr-modal-" . $id;
    return "<div class=\"dcf-modal dcf-bg-overlay-dark dcf-fixed dcf-pin-top dcf-pin-left dcf-h-100% dcf-w-100% dcf-d-flex dcf-ai-center dcf-jc-center dcf-opacity-0 dcf-pointer-events-none dcf-invisible\" id=\"" . $modalId . "\" aria-labelledby=\"" . $modalId . "-heading\" aria-hidden=\"true\" role=\"dialog\" tabindex=\"-1\">
    <div class=\"dcf-modal-wrapper dcf-relative dcf-h-auto dcf-overflow-y-auto\" role=\"document\">
        <div class=\"dcf-modal-header dcf-wrapper dcf-pt-8 dcf-sticky dcf-pin-top\">
            <h3 id=\"" . $modalId . "-heading\">QR Code for " . $id . " Go URL</h3>
            <button class=\"dcf-btn-close-modal dcf-btn dcf-btn-tertiary dcf-absolute dcf-pin-top dcf-pin-right dcf-z-1\" type=\"button\" aria-label=\"Close\">Close</button>
        </div>
        <div class=\"dcf-modal-content dcf-wrapper dcf-pb-8\">
            <img style=\"max-height: 60vh;\" src=\"" . $src . "\" alt=\"QR Code for " . $id ." Go URL\">
        </div>
    </div>
</div>";
}
?>
