<?php
// alert modal - one button notice for success, error, or info

class AlertModal {
    private string $message;
    private string $type;
    private string $button_text;
    private string $id;
    private ?string $redirect_url;

    public function __construct(
        string $message = "Action completed!",
        string $type = "success",
        string $button_text = "Continue",
        string $id = "alertModal",
        ?string $redirect_url = null
    ) {
        $this->message = $message;
        $this->type = strtolower($type);
        $this->button_text = $button_text;
        $this->id = $id;
        $this->redirect_url = $redirect_url;
    }

    public function render(): void {
        // pick icon and styling based on modal type
        $is_error = in_array($this->type, ['error', 'danger', 'failed']);
        $icon_class = $is_error ? 'fa-solid fa-circle-xmark' : 'fa-solid fa-circle-check';
        $icon_container_class = $is_error ? 'confirm-modal-icon error' : 'confirm-modal-icon success';
        $btn_class = $is_error ? 'btn-modal-confirm danger' : 'btn-modal-confirm';
        ?>
        <div id="<?= htmlspecialchars($this->id) ?>" class="confirm-modal-overlay" role="dialog" aria-modal="true" style="display: none;">
            <div class="confirm-modal-box">
                <div class="<?= $icon_container_class ?>">
                    <i class="<?= $icon_class ?>"></i>
                </div>
                <div class="confirm-modal-body">
                    <p class="confirm-modal-message"><?= htmlspecialchars($this->message) ?></p>
                </div>
                <div class="confirm-modal-actions">
                    <?php if (!empty($this->redirect_url)): ?>
                        <a href="<?= htmlspecialchars($this->redirect_url) ?>" class="<?= $btn_class ?>" style="text-decoration: none; width: 100%;">
                            <?= htmlspecialchars($this->button_text) ?>
                        </a>
                    <?php else: ?>
                        <button type="button" class="<?= $btn_class ?>" style="width: 100%;" onclick="AlertModal.close('<?= htmlspecialchars($this->id) ?>')">
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
