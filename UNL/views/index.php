<?php /* index.php ( lilURL implementation ) */

require_once 'includes/lilurl.php'; // <- lilURL class file


$lilurl = new lilURL();
$lilurl->setAllowedProtocols($allowed_protocols);

$msg = '';

if (isset($_POST['theURL'])) {
    try {
        $url = $lilurl->handlePOST();
        $msg = '<p class="success">Your Go URL is: <a href="'.$url.'">'.$url.'</a></p>';
    } catch (Exception $e) {
        switch ($e->getCode()) {
            case lilurl::ERR_INVALID_PROTOCOL:
                $msg = 'Your URL must begin with <code>http://</code>, <code>https://</code> or <code>mailto:</code>.';
                break;
            default:
                $msg = 'There was an error submitting your url. Please try again later.';
        }
        $msg = '<p class="error">'.$msg.'</p>';
    }
} else {
    // if the form hasn't been submitted, look for an id to redirect to
    $explodo = explode('/', $_SERVER['REQUEST_URI']);
    $id = mysql_escape_string($explodo[count($explodo)-1]);
    if (!empty($id)) {
        if (!$lilurl->handleRedirect($id)) {
            $msg = '<p class="error">'.htmlentities($id).' - Sorry, but that Go URL isn\'t in our database.</p>';
        }
    }
}
?>
<script type="text/javascript" charset="utf-8">
$(document).ready(function () {
    $('.hint').hide();
    $('input').focus(function() {
        $('.hint').hide();
        $(this).siblings('.hint').show();
    });
    $('#gaSource'|'#gaMedium'|'#gaName').change(function() {
        if ($(this) != "") {
            $('#gaSource').parent('.element').parent('li').addClass('required');
            $('#gaMedium').parent('.element').parent('li').addClass('required');
            $('#gaName').parent('.element').parent('li').addClass('required');
        }
    });
    $('div.close a').click(function() {
        $('#serviceIndicator').slideUp("slow");
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
		<p><strong style="font-size:1.2em">You are logged in</strong><br/>
		Since you have logged in with your My.UNL username and password, you can use the advanced features.</p>
		<?php else: ?>
		<p><a href="?login"><strong>Login with your My.UNL Account</strong></a><br/>
		This service has advanced features reserved for authenticated UNL users. <a href="?login">Please login</a> with your My.UNL username and password.</p>
		<?php endif;?>
	</div>
</div>
<?php endif; ?>
<form action="<?php echo $_SERVER['PHP_SELF']?>" method="post">
<p class="required">Indicates a required field.</p>
<fieldset>
    <legend>Basic Option</legend>
    <ol>
        <li class="required">
            <label for="theURL" class="element">Long URL</label>
            <div class="element"><input name="theURL" id="theURL" type="text" />
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
            <span class="hint"><span class="hintPointer">&nbsp;</span>ex: go.unl.edu/<strong>admissions</strong></span>            
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
    <p class="attention"><a href="?login">Please login to use this feature.</a></p>
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
