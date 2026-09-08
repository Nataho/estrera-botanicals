<?php
// alert modal helper function
// lets you render a quick success, error, or warning modal anywhere

require_once ROOT_DIR . 'classes/AlertModal.php';

function render_alert_modal(
    string $message = "Action completed!",
    string $type = "success",
    string $button_text = "Continue",
    string $id = "alertModal",
    ?string $redirect_url = null
): void {
    $modal = new AlertModal($message, $type, $button_text, $id, $redirect_url);
    $modal->render();
}
?>
