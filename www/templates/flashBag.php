<?php extract($flashBagParams); ?>
<?php if (!empty($type) &&!empty($heading) && !empty($msg)) : ?>
    <div class="dcf-bleed">
        <div class="dcf-wrapper dcf-mt-4">
            <div class="dcf-notice <?php echo $type;?>" hidden>
                <h2><?php echo $heading;?></h2>
                <div>
                    <?php if (isset($url) && !empty($url)): ?>
                        <div class="dcf-grid dcf-col-gap-vw dcf-row-gap-4 dcf-mt-4">
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
        </div>
    </div>
<?php endif; ?>
