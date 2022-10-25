<?php extract($flashBagParams); ?>
<?php if (!empty($type) &&!empty($heading) && !empty($msg)) : ?>
    <div class="dcf-bleed">
        <div class="dcf-wrapper dcf-mt-4">
            <div class="dcf-notice <?php echo $type;?>" hidden>
                <h2><?php echo $heading;?></h2>
                <div>
                    <?php if (isset($url) && !empty($url)): ?>
                        <div class="dcf-d-flex dcf-flex-col dcf-mt-4">
                            <div class="dcf-mb-4"><?php echo $msg;?></div>
                            <div class="qrCode dcf-d-flex dcf-flex-wrap dcf-flex-row dcf-ai-center dcf-jc-evenly">
                                <figure class="dcf-mb-4">
                                    <img style="max-height: 10rem;" alt="QR Code for your Go URL" id="qrCode" src="<?php echo $lilurl->getBaseUrl(substr(strrchr($url, '/'), 1) . '.png') ?>" />
                                    <figcaption class="dcf-figcaption dcf-txt-center">
                                        <a download="<?php echo substr(strrchr($url, '/'), 1) . '.png'; ?>" href="<?php echo $lilurl->getBaseUrl(substr(strrchr($url, '/'), 1) . '.png') ?>" title="Download PNG Version"> PNG Version </a>
                                    </figcaption>
                                </figure>
                                <figure class="dcf-mb-4">
                                    <img style="max-height: 10rem;" alt="QR Code for your Go URL" id="qrCode" src="<?php echo $lilurl->getBaseUrl(substr(strrchr($url, '/'), 1) . '.svg') ?>" />
                                    <figcaption class="dcf-figcaption dcf-txt-center">
                                        <a download="<?php echo substr(strrchr($url, '/'), 1) . '.svg'; ?>" href="<?php echo $lilurl->getBaseUrl(substr(strrchr($url, '/'), 1) . '.svg') ?>" title="Download SVG Version"> SVG Version </a>
                                    </figcaption>
                                </figure>
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
