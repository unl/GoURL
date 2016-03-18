<?php if ($msg) : ?>
	<div class="wdn-band">
		<div class="wdn-inner-wrapper wdn-inner-padding-sm">
			<?php if(!$error) :?>
				<div class="wdn_notice affirm">
					<div class="message">
						<?php if (isset($url)): ?>
						    <div class="wdn-grid-set">
								<div class="bp1-wdn-col-three-fourths"><?php echo $msg;?></div>
					    		<div class="qrCode bp1-wdn-col-one-fourth">
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
			<script>
			WDN.initializePlugin('notice');
			</script>
		</div>
	</div>
<?php endif; ?>
