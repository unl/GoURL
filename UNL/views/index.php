<script type="text/javascript" charset="utf-8">
$(document).ready(function () {
	$('.hint').hide();
	$('input').focus( function() {
		$('.hint').hide();
		$(this).siblings('.hint').show();
	});
});
</script>
<form action="#" method="POST">
<p class="required">Indicates a required field.</p>
<fieldset>
	<legend>Basic Option</legend>
	<ol>
		<li class="required">
			<label for="theURL" class="element">Long URL</label>
			<div class="element"><input name="theURL" id="theURL" type="text" />			
			<span class="hint"><span class="hintPointer"></span>ex: go.unl.edu/<strong>admissions</strong></span>
			</div>
		</li>
	</ol>
</fieldset>
<fieldset>
	<legend>Custom Alias</legend>
	<p>If you would like to control the URL, then use enter the alias you would like to use.</p>
	<ol>
		<li>
			<label for="theAlias" class="element">Alias</label>
			<div class="element"><input name="theAlias" id="theAlias" type="text" />			
			<span class="hint"><span class="hintPointer"></span>ex: go.unl.edu/<strong>admissions</strong></span>
			</div>
		</li>
	</ol>
</fieldset>
<fieldset>
	<legend>Google Analytics Campaign Tagging</legend>
	<p>Add your campaign information here and it will be automatically added to your URL when redirected.</p>
	<ol>
		<li class="required">
			<label for="gaSource" class="element">Source</label>
			<div class="element"><input name="gaSource" id="gaSource" type="text" />			
			<span class="hint"><span class="hintPointer"></span>The Google Analytics Source The Google Analytics Source The Google Analytics Source The Google Analytics Source The Google Analytics Source</span>
			</div>
		</li>	
		<li class="required">
			<label for="gaMedium" class="element">Medium</label>
			<div class="element"><input name="gaMedium" id="gaMedium" type="text" />			
			<span class="hint"><span class="hintPointer"></span>Testing. Testing. Testing. Testing. Testing. Testing.</span>
			</div>
		</li>
		<li>
			<label for="gaTerm" class="element">Term</label>
			<div class="element"><input name="gaTerm" id="gaTerm" type="text" /></div>
		</li>
		<li>
			<label for="gaContent" class="element">Content</label>
			<div class="element"><input name="gaContent" id="gaContent" type="text" /></div>
		</li>
		<li>
			<label for="gaName" class="element">Name</label>
			<div class="element"><input name="gaName" id="gaName" type="text" /></div>
		</li>	
		<li><label class="element">I Can Has Cheezburger?</label>
		<div class="element"><input name="helpful" value="1" type="radio" id="cheezyes" /><label for="cheezyes">Yes</label><input name="helpful" value="0" type="radio" id="cheezno" /><label for="cheezno">No</label></div></li>	
	</ol>
</fieldset>
</form>
<p class="submit"><input type="submit" value="Create URL" /></p>
