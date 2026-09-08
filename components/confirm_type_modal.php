<?php
// ConfirmTypeModal component helper
// Procedural function to render a type-to-confirm modal

require_once ROOT_DIR . 'classes/ConfirmTypeModal.php';

function render_confirm_type_modal(
    string $message = "Are you sure?",
    string $word = "DELETE_USERNAME",
    string $id = "confirmTypeModal",
    string $action_url = "",
    string $method = "POST",
    array $hidden_fields = [],
    string $confirm_button_text = "Confirm",
    string $cancel_button_text = "Cancel"
): void {
    $modal = new ConfirmTypeModal(
        $message,
        $word,
        $id,
        $action_url,
        $method,
        $hidden_fields,
        $confirm_button_text,
        $cancel_button_text
    );
    $modal->render();
}
?>
