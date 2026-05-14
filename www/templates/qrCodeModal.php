<?php
    $modalId = "qr-modal-" . $context->id;
?>
<dialog 
    class="
        dcf-dialog 
        go-qr-modal"

    id="<?php echo $modalId; ?>"
    aria-labelledby="<?php echo $modalId; ?>-heading"
    aria-hidden="true"
    role="dialog"
    tabindex="-1"
>
    <div class="dcf-relative dcf-h-auto dcf-overflow-y-auto" role="document">
        <div class="dcf-dialog-header dcf-wrapper dcf-pt-4 dcf-sticky dcf-pin-top">
            <h3 id="<?php echo $modalId ?>-heading">
                <?php echo htmlspecialchars($context->appName); ?>
                QR Code for &apos;<?php echo htmlspecialchars($context->id); ?>&apos;
            </h3>
            <button
                class="
                    dcf-btn-close-dialog
                    dcf-btn
                    dcf-btn-tertiary
                    dcf-absolute
                    dcf-pin-top
                    dcf-pin-right
                    dcf-z-1"
                type="button"
                aria-label="Close"
            >
                Close
            </button>
        </div>
        <div
            class="
                dcf-dialog-content
                dcf-wrapper
                dcf-pb-4
                dcf-mt-4
                dcf-d-flex
                dcf-flex-wrap
                dcf-flex-row
                dcf-ai-center
                dcf-jc-evenly"
        >
            <figure class="dcf-mb-4">
                <img
                    style="max-height: 10rem;"
                    loading="lazy"
                    src="<?php echo htmlspecialchars($context->srcPNG); ?>"
                    alt="
                        <?php echo htmlspecialchars($context->appName); ?>
                        QR Code for &apos;
                        <?php echo htmlspecialchars($context->id); ?>
                        &apos;"
                >
                <figcaption class="dcf-figcaption dcf-txt-center">
                    <a
                        download="<?php echo $context->id; ?>.png"
                        href="<?php echo htmlspecialchars($context->srcPNG); ?>"
                        title="Download PNG Version"
                    >
                        PNG Version
                    </a>
                </figcaption>
            </figure>
            <figure class="dcf-mb-4">
                <img
                    style="max-height: 10rem;"
                    loading="lazy"
                    src="<?php echo htmlspecialchars($context->srcSVG); ?>"
                    alt="
                        <?php echo htmlspecialchars($context->appName); ?>
                        QR Code for &apos;
                        <?php echo htmlspecialchars($context->id); ?>
                        &apos;"
                >
                <figcaption class="dcf-figcaption dcf-txt-center">
                    <a
                        download="<?php echo $context->id; ?>.svg"
                        href="<?php echo htmlspecialchars($context->srcSVG); ?>"
                        title="Download SVG Version"
                    >
                        SVG Version
                    </a>
                </figcaption>
            </figure>
        </div>
    </div>
</dialog>

<?php
