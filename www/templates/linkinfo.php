<?php
$rowDateTime = null;
if ($link['submitDate'] !== '0000-00-00 00:00:00') {
    $rowDateTime = new DateTime($link['submitDate']);
}
?>
<h2>Go URL Info for <?php echo $link['urlID'] ?></h2>
<div class="dcf-bleed">
    <div class="dcf-wrapper">
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
