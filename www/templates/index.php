<div class="dcf-bleed dcf-wrapper dcf-pt-8 dcf-pb-8 dcf-d-flex dcf-jc-center">
    <form class="dcf-form dcf-w-max-lg" id="shorten-form" action="<?php echo $lilurl->getBaseUrl() ?>" method="post">
        <div class="dcf-form-controls-inline dcf-mb-5">
            <label for="theURL">Enter the <abbr class="dcf-txt-sm" title="Uniform Resource Locator">URL</abbr> that you want to shorten <small class="dcf-required">Required</small></label>
            <div class="dcf-input-group">
              <input id="theURL" name="theURL" type="text" value="<?php echo (isset($_POST['theURL']))?htmlentities($_POST['theURL'], ENT_QUOTES):'';?>" required oninvalid="this.setCustomValidity('Please provide a URL to redirect to.')" oninput="this.setCustomValidity('')">
            </div>
        </div>
        <div>
            <fieldset>
                <legend class="dcf-bold dcf-txt-lg">Custom Alias</legend>
                <?php if (phpCAS::isAuthenticated()) : ?>
                <div class="dcf-form-group">
                    <label>Alias <small class="dcf-pl-1 dcf-txt-xs dcf-italic unl-dark-gray">Optional</small></label>                    <span>
                        <input id="theAlias" name="theAlias" type="text" aria-labelledby="theAliasLabel" aria-describedby="theAliasDesc">
                        <span class="dcf-form-help" id="theAliasDesc" tabindex="-1">For example, <em>admissions</em> for <i>go.unl.edu/admissions</i> <strong>(letters, numbers, underscores and dashes only)</strong></span>
                    </span>
                </div>
                <?php else: ?>
                <div class="dcf-form-group">
                    <label id="theAliasLabel" for="theAlias">Alias</label>
                    <span>
                        <input id="theAlias" name="theAlias" type="text" aria-labelledby="theAliasLabel" aria-describedby="theAliasDesc" disabled>
                        <span class="dcf-form-help" id="theAliasDesc" tabindex="-1">If you would like to control the <abbr class="dcf-txt-sm" title="Uniform Resource Locator">URL</abbr>, then enter the alias you would like to use. Please <a href="./?login">log in</a> to use this feature.</span>
                    </span>
                </div>
                <?php endif; ?>
            </fieldset>
            <fieldset>
                <legend class="dcf-bold dcf-txt-lg">Google Analytics Campaign Tagging</legend>
                <div class="dcf-input-checkbox">
                    <input id="with-ga-campaign" name="with-ga-campaign" type="checkbox" value="0" aria-labelledby="with-ga-campaign-label">
                    <label for="with-ga-campaign" id="with-ga-campaign-label">Use Google Analytics Campaign with URL <small class="dcf-pl-1 dcf-txt-xs dcf-italic unl-dark-gray">Optional</small></label>
                </div>
                <div id="ga-tagging" style="display:none">
                    <p class="dcf-txt-sm">Add your campaign information here and it will be automatically added to your URL when redirected.</p>
                    <div class="dcf-form-group">
                        <label id="gaNameLabel" for="gaName">Campaign Name <small class="dcf-required">Required</small></label>
                        <span>
                            <input class="ga-required" id="gaName" name="gaName" type="text" aria-labelledby="gaNameLabel" aria-describedby="gaNameDesc">
                            <span class="dcf-form-help" id="gaNameDesc" tabindex="-1">Product, promo code or slogan</span>
                        </span>
                    </div>
                    <div class="dcf-form-group">
                        <label id="gaMediumLabel" for="gaMedium">Medium <small class="dcf-required">Required</small></label>
                        <span>
                            <input class="ga-required" id="gaMedium" name="gaMedium" type="text" aria-labelledby="gaMediumLabel" aria-describedby="gaMediumDesc">
                            <span class="dcf-form-help" id="gaMediumDesc" tabindex="-1">Marketing medium: email, web, banner</span>
                        </span>
                    </div>
                    <div class="dcf-form-group">
                        <label id="gaSourceLabel" for="gaSource">Source <small class="dcf-required">Required</small></label>
                        <span>
                            <input class="ga-required" id="gaSource" name="gaSource" type="text" aria-labelledby="gaSourceLabel" aria-describedby="gaSourceDesc">
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
                </div>
            </fieldset>
            <input class="dcf-mt-6" id="submit" name="submit" type="submit" value="Create URL">
        </div>
    </form>
</div>

<!--Script for displaying Google Analytics Campaign Tagging when checkbox is clicked-->
<script>
    var withGACheckbox = document.getElementById('with-ga-campaign');
    var gaSection = document.getElementById('ga-tagging');
    var gaRequiredVars = document.getElementsByClassName('ga-required');

    withGACheckbox.addEventListener('click', function() {
        if (this.checked) {
            gaSection.style.display = 'block';
            for (var i=0; i<gaRequiredVars.length; i++) {
                var currentElement = document.getElementsByClassName('ga-required')[i].id;
                gaRequiredVars[i].required = true;
                gaRequiredVars[i].disabled = false;

                if(currentElement == 'gaName'){
                    gaRequiredVars[i].setAttribute('oninvalid', 'this.setCustomValidity(\' Please provide a campaign name.\')');
                    gaRequiredVars[i].setAttribute('oninput', 'this.setCustomValidity(\'\')');
                }
                else if(currentElement == 'gaMedium'){
                    gaRequiredVars[i].setAttribute('oninvalid', 'this.setCustomValidity(\' Please provide a marketing medium.\')');
                    gaRequiredVars[i].setAttribute('oninput', 'this.setCustomValidity(\'\')');
                }
                else if(currentElement == "gaSource"){
                    gaRequiredVars[i].setAttribute('oninvalid', 'this.setCustomValidity(\' Please provide a source.\')');
                    gaRequiredVars[i].setAttribute('oninput', 'this.setCustomValidity(\'\')');
                }
            }
        } else{
            gaSection.style.display = 'none';
            for (var i=0; i<gaRequiredVars.length; i++) {
                gaRequiredVars[i].required = false;
                gaRequiredVars[i].disabled = true;
                gaRequiredVars[i].removeAttribute('oninvalid');
                gaRequiredVars[i].removeAttribute('oninput');
                gaRequiredVars[i].value = '';
            }
            document.getElementById('gaTerm').value = '';
            document.getElementById('gaContent').value = '';
        }
    });
</script>
