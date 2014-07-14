<?php if ($didDelete) : ?> 
<div class="wdn_notice affirm">
	<div class="message">
		<h4>Delete Successful</h4>
		<p>Your Go URL has been deleted</p>
	</div>
</div>
<script type="text/javascript">
WDN.initializePlugin('notice');
</script>
<?php endif; ?>
<script type="text/javascript">
require(['jquery', 'wdn'], function($, WDN) {
    WDN.initializePlugin('modal', [function() {
    	$('.go-url-qr').colorbox({photo:true, maxWidth: 500});
        
    }]);
});
</script>
<h1>Your Go URLs</h1>
<p>You can manage the URLs you've created.</p>
<div class="wdn-band"><div class="wdn-inner-wrapper">
<?php $urls = $lilurl->getUserURLs($cas_client->getUser()); ?>
<?php if (mysql_num_rows($urls)): ?>
<ul class="go-urls">
    <?php while ($row = mysql_fetch_assoc($urls)): ?>
    <li>
        <h2><a href="<?php echo $row['longURL']; ?>"><?php echo $row['longURL']; ?></a></h2>
        <?php if ($row['submitDate'] !== '0000-00-00 00:00:00'): ?>
        <p>Created on <?php echo date('M j, Y', strtotime($row['submitDate']))?></p>
        <?php endif; ?>
        <form action="?manage" method="post">
            <input type="hidden" name="urlID" value="<?php echo $row['urlID']; ?>" />
            <p class="actions">
                <a href="./<?php echo $row['urlID']; ?>"><?php echo $row['urlID']; ?></a>
                <a class="go-url-qr" href="./<?php echo $row['urlID']; ?>.qr" title="Show QR Code for <?php echo $row['urlID']; ?> Go URL"><span class="qrImage"></span> QR Code®</a>
                <input type="submit" name="submit" value="Delete" onclick="return confirm('Are you for sure?');" />
            </p>
        </form>
    </li>
    <?php endwhile; ?>
</ul>
<?php else: ?>
<p>You haven't creating any Go URLs, yet.</p>
<?php endif;?>
</div></div>
