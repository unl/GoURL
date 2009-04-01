<script type="text/javascript" charset="utf-8">
$(document).ready(function () {
	$('input').focus( function() {
		$('.hint').hide();
		$(this).siblings('.hint').show();
	});
	$('li.required input').after('<img src="sharedcode/css/images/forms/requiredIndicator.png" alt="Required Information" />');
});
</script>
<form action="#" method="POST">
<fieldset>
	<legend>Basic Option</legend>
	<ol>
		<li class="required">
			<label for="theURL" class="element">Long URL</label>
			<div class="element"><input name="theURL" id="theURL" type="text" /></div>
		</li>
	</ol>
</fieldset>
<fieldset>
	<legend>Custom Alias</legend>
	<ol>
		<li>
			<label for="theAlias" class="element">Alias</label>
			<div class="element"><input name="theAlias" id="theAlias" type="text" /></div>
		</li>
	</ol>
</fieldset>
<fieldset>
	<legend>Google Analytics Campaign Tagging</legend>
	<ol>
		<li class="required">
			<label for="gaSource" class="element">Source</label>
			<div class="element"><input name="gaSource" id="gaSource" type="text" />			
			<span class="hint"><span class="hintPointer"></span>The Google Analytics Source The Google Analytics Source The Google Analytics Source The Google Analytics Source The Google Analytics Source</span>
			</div>
		</li>	
		<li class="required">
			<label for="gaMedium" class="element">Medium</label>
			<div class="element"><input name="gaMedium" id="gaMedium" type="text" /></div>
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
