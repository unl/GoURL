<?php $page->doctitle = 'Your URLs - Go URL | University of Nebraska&ndash;Lincoln'; ?>
<div class="dcf-bleed dcf-pt-8 dcf-pb-8">
    <div class="dcf-wrapper">
      <h2>Your Go URLs</h2>
        <?php $urls = $lilurl->getUserURLs("pnguyen16"); ?>
        <?php if ($urls->columnCount()): ?>
            <table class="go-urls dcf-w-100% wdn_responsive_table flush-left dcf-table dcf-txt-sm" data-order="[[ 3, &quot;desc&quot; ]]">
                <thead class="unl-bg-lighter-gray">
                    <tr>
                        <th>Short URL</th>
                        <th>Long URL</th>
                        <th>Redirects</th>
                        <th>Last Redirect</th>
                        <th>Created&nbsp;on</th>
                        <th data-searchable="false" data-orderable="false">Actions</th>
                        
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $urls->fetch(PDO::FETCH_ASSOC)): ?>
                    <?php
                    $rowDateTime = null;
                    $rowLastRedirectDate = null;
                    if ($row['submitDate'] !== '0000-00-00 00:00:00') {
                        $rowDateTime = new DateTime($row['submitDate']);
                    }
                    if($row['lastRedirectDate'] !== '0000-00-00 00:00:00'){
                        $rowLastRedirectDate = new DateTime($row['lastRedirectDate']);
                    }
                    ?>
                    <tr class="unl-bg-cream">
                        <td data-header="Short URL"><a href="<?php echo $lilurl->getBaseUrl($row['urlID']); ?>"><?php echo $row['urlID']; ?></a></td>
                        <td data-header="Long URL"><a href="<?php echo $escape($row['longURL']) ?>"><?php echo $escape($row['longURL']) ?></a></td>
                        <td data-header="Redirects"><?php echo $escape($row['redirects']) ?></td>
                        <!-- IMPLEMENT THIS -->
                        <td data-header="Last Redirect"<?php if ($rowLastRedirectDate): ?> data-search="<?php echo $rowLastRedirectDate->format('M j, Y m/d/Y') ?>" data-order="<?php echo $rowLastRedirectDate->format('U') ?>"<?php endif; ?>>
                            <?php if ($rowLastRedirectDate): ?>
                                <?php echo $rowLastRedirectDate->format('M j, Y') ?>
                            <?php endif; ?>
                        </td>
                        <td data-header="Created on"<?php if ($rowDateTime): ?> data-search="<?php echo $rowDateTime->format('M j, Y m/d/Y') ?>" data-order="<?php echo $rowDateTime->format('U') ?>"<?php endif; ?>>
                            <?php if ($rowDateTime): ?>
                                <?php echo $rowDateTime->format('M j, Y') ?>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a class="dcf-btn dcf-btn-secondary go-url-qr" href="<?php echo $lilurl->getBaseUrl($row['urlID'] . '.qr') ?>" title="QR Code for <?php echo $row['urlID']; ?> Go URL"><span class="qrImage"></span> QR Code®</a>
                            <a class="dcf-btn dcf-btn-secondary" href="<?php echo $lilurl->getBaseUrl($row['urlID'] . '/reset') ?>" title="Reset redirect count for <?php echo $row['urlID']; ?> Go URL" onclick="return confirm('Are you sure you want to reset the redirect count for \'<?php echo $row['urlID']; ?>\'?');">Reset Redirect Count</a>
                            <form action="<?php echo $lilurl->getBaseUrl('templates/manage.php') ?>" method="post">
                                <input type="hidden" name="urlID" value="<?php echo $row['urlID']; ?>" />
                                <button class="dcf-btn dcf-btn-primary" type="submit" onclick="return confirm('Are you for sure?');">Delete</button>
                            </form>
                        </td>
                        

                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You haven't created any Go URLs, yet.</p>
        <?php endif;?>
    </div>
</div>

<?php
$page->addScriptDeclaration("
require(['jquery', 'wdn', 'https://cdn.datatables.net/1.10.10/js/jquery.dataTables.min.js'], function($, WDN) {
    WDN.initializePlugin('modal', [function() {
        $('.go-url-qr').colorbox({photo:true, maxWidth: \"75%\"});
    }]);

    $(function() {
        $('.go-urls').DataTable();
        $('select').addClass('dcf-input-select dcf-d-inline-block dcf-w-10');
    });
});");
?>
