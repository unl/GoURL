<?php extract($flashBagParams); ?>
<?php if (!empty($heading) && !empty($msg)) : ?>
    <div class="dcf-bleed">
        <div class="dcf-wrapper dcf-pt-4">
            <?php if(!$error) :?>
                <div class="dcf-notice dcf-notice-success" hidden>
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
            <?php else :?>
                <div class="dcf-notice dcf-notice-danger" hidden>
                    <h2><?php echo $heading;?></h2>
                    <div>
                        <?php echo $msg;?>
                    </div>
                </div>
            <?php endif;?>
        </div>
    </div>
<?php endif; ?>
