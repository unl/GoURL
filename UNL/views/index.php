<?php /* index.php ( lilURL implementation ) */

require_once 'includes/action.php'; // <- start the URL building file
?>
<script type="text/javascript" charset="utf-8">
WDN.jQuery(document).ready(function () {
	WDN.jQuery('.moreOptions').hide(); 
	WDN.jQuery('#moreOptions').click(function() {
	    WDN.jQuery('.moreOptions').slideDown('fast', function() {
	        WDN.jQuery('.moreOptions fieldset').each(function() {
		        WDN.jQuery(this).insertBefore('.moreOptions');
	        });
	        WDN.jQuery('.moreOptions').hide();
	        WDN.jQuery('#moreOptions').remove();
	    });
	    return false;
	});
	WDN.jQuery('#gaSource'|'#gaMedium'|'#gaName').change(function() {
        if (WDN.jQuery(this) != "") {
        	WDN.jQuery('#gaSource').parent('.element').parent('li').addClass('required');
        	WDN.jQuery('#gaMedium').parent('.element').parent('li').addClass('required');
        	WDN.jQuery('#gaName').parent('.element').parent('li').addClass('required');
        }
    });
	WDN.jQuery('div.close a').click(function() {
		WDN.jQuery('#serviceIndicator').slideUp("slow");
        return false;
    });
});
</script>
<?php if ($msg) : ?> 
	<?php if(!$error) :?>
	<div class="wdn_notice affirm">
		<div class="message">
			<?php echo $msg;?>
		</div>
		<div class="qrCode">
			<p>Here's a QR Code for your Go URL &rarr;</p>
			<?php echo '<img class="frame" id="qrCode" src="qr.php?id=' . substr(strrchr($url, '/'), 1) . '" />';?>
		</div>
	</div>
	<?php else :?>
	<div class="wdn_notice negate">
		<div class="message">
			<?php echo $msg;?>
		</div>
	</div>
	<?php endif;?>
<?php endif; ?>
<div class="three_col left">
<form action="./" method="post" class="zenform cool">
<fieldset>
    <legend>Basic Option</legend>
    <ol>
        <li>
            <label for="theURL"><span class="required">*</span>Long URL</label>
            <input name="theURL" id="theURL" type="text" value="<?php echo (isset($_POST['theURL']))?htmlentities($_POST['theURL'], ENT_QUOTES):'';?>" />
        </li>
    </ol>
</fieldset>
<p><a href="#" id="moreOptions">More Options</a></p>
<div class="moreOptions">
<fieldset>
    <legend>Custom Alias</legend>
    <?php if ($cas_client->isLoggedIn()) : ?>
    <ol>
        <li>
            <label for="theAlias">Alias <span class="helper">ex: <strong>admissions</strong> for go.unl.edu/admissions <em>(letters/numbers/underscores/dashes only)</em></span></label>
            <input name="theAlias" id="theAlias" type="text" /> 
        </li>
    </ol>
    <?php else: ?>
    <ol>
        <li>
            <label for="theAlias">Alias <span class="helper">If you would like to control the URL, then enter the alias you would like to use. <a href="?login">Please sign in to use this feature.</a></span></label>
            <input name="theAlias" id="theAlias" type="text" disabled="disabled"/> 
        </li>
    </ol>
    <?php endif; ?>
</fieldset>
<fieldset>
    <legend>Google Analytics Campaign Tagging</legend>
        <p>Add your campaign information here and it will be automatically added to your URL when redirected.</p>
    <ol>
        <li>
            <label for="gaSource">Source <span class="helper">referrer: google, facebook, twitter</span></label>
            <input name="gaSource" id="gaSource" type="text" />
        </li>    
        <li>
            <label for="gaMedium">Medium <span class="helper">marketing medium: email, web, banner</span></label>
            <input name="gaMedium" id="gaMedium" type="text" />
        </li>
        <li>
            <label for="gaTerm">Term <span class="helper">identify the keywords</span></label>
            <input name="gaTerm" id="gaTerm" type="text" /> 
        </li>
        <li>
            <label for="gaContent">Content <span class="helper">use to differentiate ads (A/B testing)</span></label>
            <input name="gaContent" id="gaContent" type="text" />   
        </li>
        <li>
            <label for="gaName" class="element">Name <span class="helper">product, promo code, or slogan</span></label>
            <input name="gaName" id="gaName" type="text" />   
        </li>    
    </ol>
</fieldset>
</div>
<input type="submit" id="submit" name="submit" value="Create URL" />
</form>
</div>
<div class="col right">
<?php if (!$cas_client->isLoggedIn()) : ?>
<a id="wdnLoginButton" href="?login">Log in with your <span>My.UNL Account</span></a>
<?php endif;?>
<div class="zenbox">
	<h4 class="sec_header">What is Go URL?</h4>
	<p>Go URL is a URL shortening service similar to <a href="http://www.tinyurl.com" class="external">TinyURL</a>. Use this when you would like a shorter URL while retaining the unl.edu domain.</p>
</div>
</div>