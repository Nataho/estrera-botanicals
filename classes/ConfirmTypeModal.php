<?php
// ConfirmTypeModal component/class
// A modal requiring the user to type a specific confirmation word (e.g. DELETE_USERNAME) before the confirm button enables

class ConfirmTypeModal {
    private string $message;
    private string $word;
    private string $id;
    private string $action_url;
    private string $method;
    private array $hidden_fields;
    private string $confirm_button_text;
    private string $cancel_button_text;

    /**
     * @param string $message The warning / prompt message
     * @param string $word The exact required word the user must type (e.g. "DELETE_USERNAME")
     * @param string $id Unique HTML ID for the modal
     * @param string $action_url Target URL if submitting a form
     * @param string $method POST or GET
     * @param array $hidden_fields Key-value pairs for form payload
     * @param string $confirm_button_text Text for confirm button
     * @param string $cancel_button_text Text for cancel button
     */
    public function __construct(
        string $message = "Are you sure?",
        string $word = "DELETE_USERNAME",
        string $id = "confirmTypeModal",
        string $action_url = "",
        string $method = "POST",
        array $hidden_fields = [],
        string $confirm_button_text = "Confirm",
        string $cancel_button_text = "Cancel"
    ) {
        $this->message = $message;
        $this->word = $word;
        $this->id = $id;
        $this->action_url = $action_url;
        $this->method = strtoupper($method);
        $this->hidden_fields = $hidden_fields;
        $this->confirm_button_text = $confirm_button_text;
        $this->cancel_button_text = $cancel_button_text;
    }

    /**
     * Renders the modal HTML
     */
    public function render(): void {
        $input_id = $this->id . '_input';
        $btn_id   = $this->id . '_btn';
        $is_form  = !empty($this->action_url) || !empty($this->hidden_fields);
        ?>
        <div id="<?= htmlspecialchars($this->id) ?>" class="confirm-modal-overlay" role="dialog" aria-modal="true" style="display: none;">
            <div class="confirm-modal-box">
                <div class="confirm-modal-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="confirm-modal-body">
                    <p class="confirm-modal-message"><?= htmlspecialchars($this->message) ?></p>
                    <p style="font-family: var(--document-font); font-size: 0.9rem; color: var(--palette-gray); margin-bottom: 12px;">
                        To confirm, please type <strong style="color: #991b1b; user-select: all; font-family: monospace; font-size: 1rem; background: #fee2e2; padding: 2px 6px; border-radius: 4px;"><?= htmlspecialchars($this->word) ?></strong> below:
                    </p>
                    <div style="margin-bottom: 20px;">
                        <input type="text" id="<?= htmlspecialchars($input_id) ?>" 
                               class="confirm-type-input" 
                               placeholder="Type '<?= htmlspecialchars($this->word) ?>'" 
                               autocomplete="off"
                               style="width: 100%; padding: 10px 14px; border: 1.5px solid #d8e2d7; border-radius: 8px; font-family: inherit; font-size: 0.95rem; text-align: center; outline: none; box-sizing: border-box;"
                               oninput="ConfirmTypeModal.check('<?= htmlspecialchars($this->id) ?>', '<?= htmlspecialchars($this->word) ?>')">
                    </div>
                </div>
                <div class="confirm-modal-actions">
                    <?php if ($is_form): ?>
                        <form method="<?= htmlspecialchars($this->method) ?>" action="<?= htmlspecialchars($this->action_url) ?>" style="display: inline-flex; gap: 10px; width: 100%;">
                            <?php foreach ($this->hidden_fields as $name => $val): ?>
                                <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($val) ?>">
                            <?php endforeach; ?>
                            <button type="button" class="btn-modal-cancel" onclick="ConfirmTypeModal.close('<?= htmlspecialchars($this->id) ?>')">
                                <?= htmlspecialchars($this->cancel_button_text) ?>
                            </button>
                            <button type="submit" id="<?= htmlspecialchars($btn_id) ?>" class="btn-modal-confirm danger" disabled style="opacity: 0.5; cursor: not-allowed;">
                                <?= htmlspecialchars($this->confirm_button_text) ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn-modal-cancel" onclick="ConfirmTypeModal.close('<?= htmlspecialchars($this->id) ?>')">
                            <?= htmlspecialchars($this->cancel_button_text) ?>
                        </button>
                        <button type="button" id="<?= htmlspecialchars($btn_id) ?>" class="btn-modal-confirm danger" disabled style="opacity: 0.5; cursor: not-allowed;">
                            <?= htmlspecialchars($this->confirm_button_text) ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
?>
