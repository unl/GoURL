<?php /* index.php ( lilURL implementation ) */

require_once 'includes/action.php'; // <- start the URL building file
?>
<script type="text/javascript" charset="utf-8">
WDN.jQuery(document).ready(function () {
	WDN.jQuery('.hint').hide();
	WDN.jQuery('input').focus(function() {
		WDN.jQuery('.hint').hide();
		WDN.jQuery(this).siblings('.hint').show();
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
<?php echo $msg; ?>
<?php if (!$cas_client->isLoggedIn()) : ?>
<div id="serviceIndicator">
	<div class="close">
		<a href="#">Close message</a>
	</div>
	<div class="message">
		<?php if ($cas_client->isLoggedIn()) : ?>
		<p><strong style="font-size:1.2em">You are signed in</strong><br/>
		Since you have signed in with your My.UNL username and password, you can use the advanced features.</p>
		<?php else: ?>
		<p><a href="?login"><strong>Sign in with your My.UNL Account</strong></a><br/>
		This service has advanced features reserved for authenticated UNL users. <a href="?login">Please sign in</a> with your My.UNL username and password.</p>
		<?php endif;?>
	</div>
</div>
<?php endif; ?>
<div class="three_col left">
<form action="./" method="post" class="cool">
<p class="required">Indicates a required field.</p>
<fieldset>
    <legend>Basic Option</legend>
    <ol>
        <li class="required">
            <label for="theURL" class="element">Long URL</label>
            <div class="element"><input name="theURL" id="theURL" type="text" value="<?php echo (isset($_POST['theURL']))?htmlentities($_POST['theURL'], ENT_QUOTES):'';?>" />
            </div>
        </li>
    </ol>
</fieldset>
<p class="submit"><input type="submit" id="submit1" name="submit" value="Create URL" /></p>
<fieldset>
    <legend>Custom Alias</legend>
    <p>If you would like to control the URL, then enter the alias you would like to use.</p>
    <?php if ($cas_client->isLoggedIn()) : ?>
    <ol>
        <li>
            <label for="theAlias" class="element">Alias</label>
            <div class="element"><input name="theAlias" id="theAlias" type="text" />            
            <span class="hint"><span class="hintPointer">&nbsp;</span>ex: <strong>admissions</strong> for go.unl.edu/admissions</span>            
            </div>
        </li>
    </ol>
    <?php else: ?>
    <ol>
        <li>
            <label for="theAlias" class="element">Alias</label>
            <div class="element"><input name="theAlias" id="theAlias" type="text" disabled="disabled"/>            
            </div>
        </li>
    </ol>
    <p class="attention"><a href="?login">Please sign in to use this feature.</a></p>
    <?php endif; ?>
</fieldset>
<fieldset>
    <legend>Google Analytics Campaign Tagging</legend>
    <p>Add your campaign information here and it will be automatically added to your URL when redirected.</p>
    <ol>
        <li>
            <label for="gaSource" class="element">Source</label>
            <div class="element"><input name="gaSource" id="gaSource" type="text" />            
                <span class="hint"><span class="hintPointer">&nbsp;</span>referrer: google, facebook, twitter</span>
            </div>
        </li>    
        <li>
            <label for="gaMedium" class="element">Medium</label>
            <div class="element"><input name="gaMedium" id="gaMedium" type="text" />            
                <span class="hint"><span class="hintPointer">&nbsp;</span>marketing medium: email, web, banner</span>
            </div>
        </li>
        <li>
            <label for="gaTerm" class="element">Term</label>
            <div class="element"><input name="gaTerm" id="gaTerm" type="text" />        
                <span class="hint"><span class="hintPointer">&nbsp;</span>identify the keywords</span>
            </div>
        </li>
        <li>
            <label for="gaContent" class="element">Content</label>
            <div class="element"><input name="gaContent" id="gaContent" type="text" />            
                <span class="hint"><span class="hintPointer">&nbsp;</span>use to differentiate ads (A/B testing)</span>
                </div>
        </li>
        <li>
            <label for="gaName" class="element">Name</label>
            <div class="element"><input name="gaName" id="gaName" type="text" />            
                <span class="hint"><span class="hintPointer">&nbsp;</span>product, promo code, or slogan</span>
            </div>
        </li>    
    </ol>
</fieldset>
<p class="submit"><input type="submit" id="submit" name="submit" value="Create URL" /></p>
</form>
</div>
<div class="col right">
<div class="zenbox">
	<h4 class="sec_header">What is Go URL?</h4>
	<p>Go URL is a URL shortening service similar to <a href="http://www.tinyurl.com" class="external">TinyURL</a>. Use this when you would like a shorter URL while retaining a unl.edu URL.</p>
</div>
</div>