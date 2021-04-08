<?php
extract($viewParams);
$rowDateTime = null;
if ($link['submitDate'] !== '0000-00-00 00:00:00') {
    $rowDateTime = new DateTime($link['submitDate']);
}
$appPart = !empty($appName) ? ' - ' . $appName : '';
$institutionPart = !empty($institution) ? ' | ' . $institution : '';
$page->doctitle = 'URL Info' . $appPart . $institutionPart;

?>
<div class="dcf-bleed dcf-pt-8 dcf-pb-8">
    <div class="dcf-wrapper">
        <h2 class="dcf-txt-h4"><?php echo $appName; ?> info for <?php echo $link['urlID'] ?></h2>
        <p><a href="<?php echo $lilurl->getBaseUrl($link['urlID']) ?>"><?php echo $link['urlID'] ?></a></p>
        <p><a href="<?php echo $lilurl->escapeURL($link['longURL']) ?>"><?php echo $lilurl->escapeURL($link['longURL']) ?></a></p>
        <p>
            Created
            <?php if ($rowDateTime): ?><?php echo $rowDateTime->format('F j, Y') ?><?php endif;?>
            by
            <?php if ($link['createdBy']): ?>
                <a href="http://directory.unl.edu/people/<?php echo $lilurl->escapeURL($link['createdBy']) ?>"><?php echo $lilurl->escapeURL($link['createdBy']) ?></a>
            <?php else: ?>
                Unknown
            <?php endif; ?>
        </p>
    </div>
</div>
