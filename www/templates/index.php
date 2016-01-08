<div class="wdn-band">
    <div class="wdn-inner-wrapper">
        <form action="<?php echo $lilurl->getBaseUrl() ?>" method="post" class="">
            <div class="wdn-grid-set">
                <div class="wdn-col bp1-wdn-col-six-sevenths">
                <ol>
                    <li>
                        <label for="theURL" class="wdn-text-hidden"><span class="required">*</span>Long URL</label>
                        <div class="wdn-input-group">
                            <input name="theURL" id="theURL" type="text" placeholder="http://www.unl.edu/" value="<?php echo (isset($_POST['theURL']))?htmlentities($_POST['theURL'], ENT_QUOTES):'';?>" />
                            <span class="wdn-input-group-btn"><button type="submit">Shorten</button></span>
                        </div>
                    </li>
                </ol>
                <p><a href="#" id="moreOptions">More Options</a></p>
                <div class="moreOptions">
                <fieldset>
                    <legend>Custom Alias</legend>
                    <?php if (phpCAS::isAuthenticated()) : ?>
                    <ol>
                        <li>
                            <label for="theAlias">Alias <span class="helper">ex: <strong>admissions</strong> for go.unl.edu/admissions <em>(letters/numbers/underscores/dashes only)</em></span></label>
                            <input name="theAlias" id="theAlias" type="text" />
                        </li>
                    </ol>
                    <?php else: ?>
                    <ol>
                        <li>
                            <label for="theAlias">Alias <span class="helper">If you would like to control the URL, then enter the alias you would like to use. Please <a href="./?login">log in</a> to use this feature.</span></label>
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
                            <label for="gaName" class="element">Campaign Name<span class="required">*</span> <span class="helper">product, promo code, or slogan</span></label>
                            <input name="gaName" id="gaName" type="text" />
                        </li>
                        <li>
                            <label for="gaMedium">Medium<span class="required">*</span> <span class="helper">marketing medium: email, web, banner</span></label>
                            <input name="gaMedium" id="gaMedium" type="text" />
                        </li>
                        <li>
                            <label for="gaSource">Source<span class="required">*</span> <span class="helper">referrer: google, facebook, twitter</span></label>
                            <input name="gaSource" id="gaSource" type="text" />
                        </li>
                        <li>
                            <label for="gaTerm">Term <span class="helper">identify the keywords</span></label>
                            <input name="gaTerm" id="gaTerm" type="text" />
                        </li>
                        <li>
                            <label for="gaContent">Content <span class="helper">use to differentiate ads (A/B testing)</span></label>
                            <input name="gaContent" id="gaContent" type="text" />
                        </li>
                    </ol>
                </fieldset>
                <input type="submit" id="submit" name="submit" value="Create URL" />
                </div>
                </div>
                <div class="wdn-col bp1-wdn-col-one-seventh">
                <p style="text-align: right">
                <?php if (!phpCAS::isAuthenticated()) : ?>
                <a href="<?php echo $lilurl->getBaseUrl('a/login') ?>">Log in</a>
                <?php else: ?>
                <a href="<?php echo $lilurl->getBaseUrl('a/links') ?>">Your URLs</a>
                <?php endif;?>
                </p>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
require(['jquery'], function($) {
    $(function() {
        $('.moreOptions').hide();
        $('#moreOptions').click(function() {
            var self = this;
            $('.moreOptions').slideDown('fast', function() {
                $(self).remove();
            });
            return false;
        });
        var $out = $('.wdn_notice input');
        $out.attr('id', 'gourl_out');
        $out.attr('title', 'Your Go URL');
    });
});
</script>