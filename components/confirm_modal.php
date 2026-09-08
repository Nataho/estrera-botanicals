<?php
// ConfirmModal helper component
// Lets you easily render a confirm modal anywhere using simple procedural PHP or OOP

require_once ROOT_DIR . 'classes/ConfirmModal.php';

function render_confirm_modal(
    string $message = "Are you sure?",
    array $choices = ["Yes", "No"],
    string $id = "confirmModal",
    string $action_url = "",
    string $method = "POST",
    array $hidden_fields = [],
    string $confirm_class = "danger"
): void {
    $modal = new ConfirmModal($message, $choices, $id, $action_url, $method, $hidden_fields, $confirm_class);
    $modal->render();
}
?>
