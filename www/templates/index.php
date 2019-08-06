<div class="dcf-bleed dcf-pt-8 dcf-pb-8">
    <div class="dcf-wrapper dcf-txt-center">
        <form id="shorten-form" class="dcf-input-group-form dcf-d-inline-block dcf-txt-left" action="<?php echo $lilurl->getBaseUrl() ?>" method="post">
          <ol>
            <li>
              <label class="dcf-label" for="theURL"><span class="dcf-required">*</span>Enter the URL that you want to shorten</label>
              <div class="dcf-input-group">
                <input class="dcf-input-text" name="theURL" id="theURL" type="text" placeholder="http://www.unl.edu/" value="<?php echo (isset($_POST['theURL']))?htmlentities($_POST['theURL'], ENT_QUOTES):'';?>" />
                <button class="dcf-btn dcf-btn-primary" type="submit">Shorten</button>
              </div>
            </li>
          </ol>
          <p><a href="#" id="moreOptions">More Options</a></p>
          <div class="moreOptions">
            <fieldset>
              <legend class="dcf-legend dcf-pt-2 dcf-txt-lg">Custom Alias</legend>
               <?php if (phpCAS::isAuthenticated()) : ?>
                 <ol>
                   <li>
                     <label class="dcf-label" for="theAlias">Alias <span class="helper">ex: <strong>admissions</strong> for go.unl.edu/admissions <em>(letters/numbers/underscores/dashes only)</em></span></label>
                     <input class="dcf-input-text" name="theAlias" id="theAlias" type="text" />
                   </li>
                 </ol>
               <?php else: ?>
                 <ol>
                   <li>
                     <label class="dcf-label" for="theAlias">Alias <span class="helper">If you would like to control the URL, then enter the alias you would like to use. Please <a href="./?login">log in</a> to use this feature.</span></label>
                     <input class="dcf-input-text" name="theAlias" id="theAlias" type="text" disabled="disabled"/>
                   </li>
                 </ol>
               <?php endif; ?>
            </fieldset>
            <fieldset>
              <legend class="dcf-legend dcf-pt-4 dcf-txt-lg">Google Analytics Campaign Tagging</legend>
              <p class=" dcf-txt-sm">Add your campaign information here and it will be automatically added to your URL when redirected.</p>
              <ol>
                <li>
                  <label class="dcf-label" for="gaName" class="element">Campaign Name<span class="required">*</span> <span class="helper">product, promo code, or slogan</span></label>
                  <input class="dcf-input-text" name="gaName" id="gaName" type="text" />
                </li>
                <li>
                  <label class="dcf-label" for="gaMedium">Medium<span class="required">*</span> <span class="helper">marketing medium: email, web, banner</span></label>
                  <input class="dcf-input-text" name="gaMedium" id="gaMedium" type="text" />
                </li>
                <li>
                  <label class="dcf-label" for="gaSource">Source<span class="required">*</span> <span class="helper">referrer: google, facebook, twitter</span></label>
                  <input class="dcf-input-text" name="gaSource" id="gaSource" type="text" />
                </li>
                <li>
                  <label class="dcf-label" for="gaTerm">Term <span class="helper">identify the keywords</span></label>
                  <input class="dcf-input-text" name="gaTerm" id="gaTerm" type="text" />
                </li>
                <li>
                  <label class="dcf-label" for="gaContent">Content <span class="helper">use to differentiate ads (A/B testing)</span></label>
                  <input class="dcf-input-text" name="gaContent" id="gaContent" type="text" />
                </li>
              </ol>
            </fieldset>
            <input class="dcf-mt-6" type="submit" id="submit" name="submit" value="Create URL" />
          </div>
        </form>
    </div>
</div>
