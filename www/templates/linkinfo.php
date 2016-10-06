<?php
$rowDateTime = null;
if ($link['submitDate'] !== '0000-00-00 00:00:00') {
    $rowDateTime = new DateTime($link['submitDate']);
}
?>

<?php ob_start() ?>
<ul>
    <li><a href="http://www.unl.edu">UNL</a></li>
    <li><a href="<?php echo $lilurl->getBaseUrl() ?>">Go URL</a></li>
    <li>Go URL Info</li>
</ul>
<?php $page->breadcrumbs = ob_get_clean(); ?>
<h1>Go URL Info for <?php echo $link['urlID'] ?></h1>
<div class="wdn-band">
    <div class="wdn-inner-wrapper">
        <p><a href="<?php echo $lilurl->getBaseUrl($link['urlID']) ?>"><?php echo $link['urlID'] ?></a></p>
        <p><a href="<?php echo $escape($link['longURL']) ?>"><?php echo $escape($link['longURL']) ?></a></p>
        <p>
            Created
            <?php if ($rowDateTime): ?><?php echo $rowDateTime->format('M j, Y') ?><?php endif;?>
            by
            <?php if ($link['createdBy']): ?>
                <a href="http://directory.unl.edu/people/<?php echo $escape($link['createdBy']) ?>"><?php echo $escape($link['createdBy']) ?></a>
            <?php else: ?>
                Somebody
            <?php endif; ?>
        </p>
    </div>
</div>
