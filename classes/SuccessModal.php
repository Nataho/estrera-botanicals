<?php
// SuccessModal component/class
// A clean success modal displaying a message with a single "Continue" button

class SuccessModal {
    private string $message;
    private string $button_text;
    private string $id;
    private ?string $redirect_url;

    /**
     * @param string $message The success message shown to the user
     * @param string $button_text Text for the button (default: "Continue")
     * @param string $id Unique HTML ID for the modal dialog
     * @param string|null $redirect_url Optional URL to redirect to when "Continue" is clicked
     */
    public function __construct(
        string $message = "Action completed successfully!",
        string $button_text = "Continue",
        string $id = "successModal",
        ?string $redirect_url = null
    ) {
        $this->message = $message;
        $this->button_text = $button_text;
        $this->id = $id;
        $this->redirect_url = $redirect_url;
    }

    /**
     * Renders the modal HTML
     */
    public function render(): void {
        ?>
        <div id="<?= htmlspecialchars($this->id) ?>" class="confirm-modal-overlay" role="dialog" aria-modal="true" style="display: none;">
            <div class="confirm-modal-box">
                <div class="confirm-modal-icon success">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="confirm-modal-body">
                    <p class="confirm-modal-message"><?= htmlspecialchars($this->message) ?></p>
                </div>
                <div class="confirm-modal-actions">
                    <?php if (!empty($this->redirect_url)): ?>
                        <a href="<?= htmlspecialchars($this->redirect_url) ?>" class="btn-modal-confirm" style="text-decoration: none; width: 100%;">
                            <?= htmlspecialchars($this->button_text) ?>
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn-modal-confirm" style="width: 100%;" onclick="SuccessModal.close('<?= htmlspecialchars($this->id) ?>')">
                            <?= htmlspecialchars($this->button_text) ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
?>
