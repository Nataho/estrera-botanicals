<?php
// ConfirmModal component/class
// A reusable confirmation modal with customizable message and choices

class ConfirmModal {
    private string $id;
    private string $message;
    private array $choices;
    private string $action_url;
    private string $method;
    private array $hidden_fields;
    private string $confirm_class;

    /**
     * @param string $message The prompt question shown to the user
     * @param array $choices The buttons [0 => Yes/Confirm label, 1 => No/Cancel label]
     * @param string $id Unique HTML ID for the modal dialog
     * @param string $action_url Optional form action URL if submitting via POST/GET
     * @param string $method Form method (POST or GET)
     * @param array $hidden_fields Key-value pairs for hidden inputs to submit on confirm
     * @param string $confirm_class CSS class for the affirmative action button (e.g. 'danger' or 'primary')
     */
    public function __construct(
        string $message = "Are you sure?",
        array $choices = ["Yes", "No"],
        string $id = "confirmModal",
        string $action_url = "",
        string $method = "POST",
        array $hidden_fields = [],
        string $confirm_class = "danger"
    ) {
        $this->message = $message;
        $this->choices = $choices;
        $this->id = $id;
        $this->action_url = $action_url;
        $this->method = strtoupper($method);
        $this->hidden_fields = $hidden_fields;
        $this->confirm_class = $confirm_class;
    }

    /**
     * Renders the modal HTML
     */
    public function render(): void {
        $confirm_text = $this->choices[0] ?? 'Yes';
        $cancel_text  = $this->choices[1] ?? 'No';
        $is_form      = !empty($this->action_url) || !empty($this->hidden_fields);
        ?>
        <div id="<?= htmlspecialchars($this->id) ?>" class="confirm-modal-overlay" role="dialog" aria-modal="true" style="display: none;">
            <div class="confirm-modal-box">
                <div class="confirm-modal-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="confirm-modal-body">
                    <p class="confirm-modal-message"><?= htmlspecialchars($this->message) ?></p>
                </div>
                <div class="confirm-modal-actions">
                    <?php if ($is_form): ?>
                        <form method="<?= htmlspecialchars($this->method) ?>" action="<?= htmlspecialchars($this->action_url) ?>" style="display: inline-flex; gap: 10px; width: 100%;">
                            <?php foreach ($this->hidden_fields as $name => $val): ?>
                                <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($val) ?>">
                            <?php endforeach; ?>
                            <button type="button" class="btn-modal-cancel" onclick="ConfirmModal.close('<?= htmlspecialchars($this->id) ?>')">
                                <?= htmlspecialchars($cancel_text) ?>
                            </button>
                            <button type="submit" class="btn-modal-confirm <?= htmlspecialchars($this->confirm_class) ?>">
                                <?= htmlspecialchars($confirm_text) ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn-modal-cancel" onclick="ConfirmModal.close('<?= htmlspecialchars($this->id) ?>')">
                            <?= htmlspecialchars($cancel_text) ?>
                        </button>
                        <button type="button" class="btn-modal-confirm <?= htmlspecialchars($this->confirm_class) ?>" id="<?= htmlspecialchars($this->id) ?>_confirmBtn">
                            <?= htmlspecialchars($confirm_text) ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
?>
