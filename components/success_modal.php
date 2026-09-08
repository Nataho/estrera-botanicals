<?php
// SuccessModal helper component
// Lets you render a success modal with a single function call

require_once ROOT_DIR . 'classes/SuccessModal.php';

function render_success_modal(
    string $message = "Action completed successfully!",
    string $button_text = "Continue",
    string $id = "successModal",
    ?string $redirect_url = null
): void {
    $modal = new SuccessModal($message, $button_text, $id, $redirect_url);
    $modal->render();
}
?>
