<?php
extract($viewParams);

$apiKeyRow = $lilurl->getUserAPIKey($auth->getUserId());

if (!$apiKeyRow) {
    $generatedKey = $lilurl->createUserAPIKey($auth->getUserId());
} else {
    $generatedKey = $apiKeyRow->apiKey;
}

$maskedKey = substr($generatedKey, 0, 7) . str_repeat('*', max(strlen($generatedKey) - 7, 0));
?>

<div class="dcf-bleed dcf-pt-8 dcf-pb-8">
    <div class="dcf-wrapper">
        <div class="dcf-d-grid dcf-grid-cols-1 dcf-grid-cols-2@md dcf-grid-cols-12 dcf-col-gap-vw dcf-row-gap-8">

            <div>
                <h2 class="dcf-txt-h4">API Token</h2>

                <p class="dcf-txt-sm">
                    Use this token to authenticate API requests. Keep it secure and do not share it publicly.
                </p>

                <div class="dcf-form">
                    <div class="dcf-form-group">
                        <label for="api-key">Your API Token</label>

                        <div class="dcf-input-group">
                            <input
                                id="api-key"
                                type="text"
                                readonly
                                value="<?php echo htmlspecialchars($maskedKey); ?>"
                            >

                            <button
                                type="button"
                                class="dcf-btn dcf-btn-secondary"
                                onclick="copyKey()"
                            >
                                Copy
                            </button>
                        </div>

                        <span class="dcf-form-help">
                            Click “Copy” to copy your API token to the clipboard.
                        </span>
                    </div>
                </div>

                <div class="dcf-mt-6 dcf-mb-6">
                    <a
                        class="dcf-btn dcf-btn-primary dcf-mr-6"
                        id="generateTokenBtn"
                    >
                        Generate Token
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
let actualApiKey = <?php echo json_encode($generatedKey); ?>;

function maskKey(key) {
    return key.substring(0, 7) + "*".repeat(Math.max(key.length - 7, 0));
}

function copyKey() {
    navigator.clipboard.writeText(actualApiKey);
}

document.getElementById("generateTokenBtn").addEventListener("click", async () => {
    try {
        const res = await fetch("/a/new-uuid");
        const data = await res.json();

        if (data.success) {
            actualApiKey = data.apiKey;
            document.getElementById("api-key").value = maskKey(actualApiKey);
        }
    } catch (err) {
        console.error("Failed to generate token:", err);
    }
});
</script>