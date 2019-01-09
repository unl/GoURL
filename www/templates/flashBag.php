<?php if ($msg) : ?>
	<div class="dcf-bleed">
		<div class="dcf-wrapper dcf-pt-4">
			<?php if(!$error) :?>
				<div class="wdn_notice affirm">
					<div class="message">
						<?php if (isset($url)): ?>
						    <div class="dcf-grid dcf-col-gap-vw">
								<div class="dcf-col-100% dcf-col-75%-start@sm"><?php echo $msg;?></div>
					    		<div class="qrCode dcf-col-100% dcf-col-25%-end@sm">
						    		<img alt="QR Code for your Go URL" class="frame" id="qrCode" src="<?php echo $lilurl->getBaseUrl(substr(strrchr($url, '/'), 1) . '.qr') ?>" />
					    		</div>
				    		</div>
			    		<?php else: ?>
			    			<?php echo $msg ?>
			    		<?php endif; ?>
					</div>
				</div>
			<?php else :?>
				<div class="wdn_notice negate">
					<div class="message">
						<?php echo $msg;?>
					</div>
				</div>
			<?php endif;?>
			<?php
			  $page->addScriptDeclaration("WDN.initializePlugin('notice');");
			?>
		</div>
	</div>
<?php endif; ?>
