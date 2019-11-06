<div class="dcf-bleed dcf-wrapper dcf-pt-8 dcf-pb-8 dcf-d-flex dcf-jc-center">
    <form class="dcf-form dcf-w-max-lg" id="shorten-form" action="<?php echo $lilurl->getBaseUrl() ?>" method="post">
        <div class="dcf-form-controls-inline dcf-mb-5">
            <label for="theURL">Enter the <abbr class="dcf-txt-sm" title="Uniform Resource Locator">URL</abbr> that you want to shorten <small class="dcf-required">Required</small></label>
            <div class="dcf-input-group">
              <input id="theURL" name="theURL" type="text" value="<?php echo (isset($_POST['theURL']))?htmlentities($_POST['theURL'], ENT_QUOTES):'';?>" required>
              <button class="dcf-btn dcf-btn-primary" type="submit">Shorten</button>
            </div>
        </div>
        <button class="dcf-btn dcf-btn-secondary" id="showMoreOptions">Show More Options
            <svg class="dcf-ml-1 dcf-h-3 dcf-w-3 dcf-fill-current" aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24">
                <path d="M23.9 2.3c-.1-.2-.2-.3-.4-.3H.5c-.2 0-.3.1-.4.3-.1.1-.1.3 0 .5l11.5 19c.1.1.3.2.4.2s.3-.1.4-.2l11.5-19c.1-.2.1-.4 0-.5z"></path>
            </svg>
        </button>
        <div id="moreOptions">
            <fieldset>
                <legend class="dcf-bold dcf-txt-lg">Custom Alias</legend>
                <?php if (phpCAS::isAuthenticated()) : ?>
                <div class="dcf-form-group">
                    <label id="theAliasLabel" class="dcf-label" for="theAlias">Alias</label>
                    <span>
                        <input id="theAlias" name="theAlias" type="text" aria-labelledby="theAliasLabel" aria-describedby="theAliasDesc" disabled>
                        <span class="dcf-form-help" id="theAliasDesc" tabindex="-1">For example, <em>admissions</em> for <i>go.unl.edu/admissions</i> <strong>(letters, numbers, underscores and dashes only)</strong></span>
                    </span>
                </div>
                <?php else: ?>
                <div class="dcf-form-group">
                    <label id="theAliasLabel" class="dcf-label" for="theAlias">Alias</label>
                    <span>
                        <input id="theAlias" name="theAlias" type="text" aria-labelledby="theAliasLabel" aria-describedby="theAliasDesc" disabled>
                        <span class="dcf-form-help" id="theAliasDesc" tabindex="-1">If you would like to control the <abbr class="dcf-txt-sm" title="Uniform Resource Locator">URL</abbr>, then enter the alias you would like to use. Please <a href="./?login">log in</a> to use this feature.</span>
                    </span>
                </div>
                <?php endif; ?>
            </fieldset>
            <fieldset>
                <legend class="dcf-bold dcf-txt-lg">Google Analytics Campaign Tagging</legend>
                <p class="dcf-txt-sm">Add your campaign information here and it will be automatically added to your URL when redirected.</p>
                <div class="dcf-form-group">
                    <label id="gaNameLabel" for="gaName" class="element">Campaign Name <small class="dcf-required">Required</small></label>
                    <span>
                        <input id="gaName" name="gaName" type="text" aria-labelledby="gaNameLabel" aria-describedby="gaNameDesc" required>
                        <span class="dcf-form-help" id="gaNameDesc" tabindex="-1">Product, promo code or slogan</span>
                    </span>
                </div>
                <div class="dcf-form-group">
                    <label id="gaMediumLabel" for="gaMedium">Medium <small class="dcf-required">Required</small></label>
                    <span>
                        <input id="gaMedium" name="gaMedium" type="text" aria-labelledby="gaMediumLabel" aria-describedby="gaMediumDesc" required>
                        <span class="dcf-form-help" id="gaMediumDesc" tabindex="-1">Marketing medium: email, web, banner</span>
                    </span>
                </div>
                <div class="dcf-form-group">
                    <label id="gaSourceLabel" for="gaSource">Source <small class="dcf-required">Required</small></label>
                    <span>
                        <input id="gaSource" name="gaSource" type="text" aria-labelledby="gaSourceLabel" aria-describedby="gaSourceDesc" required>
                        <span class="dcf-form-help" id="gaSourceDesc" tabindex="-1">Referrer: Google, Facebook, Twitter</span>
                    </span>
                </div>
                <div class="dcf-form-group">
                    <label id="gaTermLabel" for="gaTerm">Term</label>
                    <span>
                        <input id="gaTerm" name="gaTerm" type="text" aria-labelledby="gaContentLabel" aria-describedby="gaTermDesc">
                        <span class="dcf-form-help" id="gaTermDesc" tabindex="-1">Identify the keywords</span>
                    </span>
                </div>
                <div class="dcf-form-group">
                    <label id="gaContentLabel" for="gaContent">Content</label>
                    <span>
                        <input id="gaContent" name="gaContent" type="text" aria-labelledby="gaContentLabel" aria-describedby="gaContentDesc">
                        <span class="dcf-form-help" id="gaContentDesc" tabindex="-1">Use to differentiate ads (A/B testing)</span>
                    </span>
                </div>
            </fieldset>
            <input class="dcf-mt-6" id="submit" name="submit" type="submit" value="Create URL">
        </div>
    </form>
</div>
