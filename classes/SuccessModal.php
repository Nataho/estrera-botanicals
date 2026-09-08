<?php
// backwards compatible wrapper for AlertModal
require_once __DIR__ . '/AlertModal.php';

class SuccessModal extends AlertModal {
    public function __construct(
        string $message = "Action completed successfully!",
        string $button_text = "Continue",
        string $id = "successModal",
        ?string $redirect_url = null
    ) {
        parent::__construct($message, "success", $button_text, $id, $redirect_url);
    }
}
?>
