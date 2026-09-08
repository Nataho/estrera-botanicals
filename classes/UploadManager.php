<?php
// handles file uploads to eb-uploads/
// admin only — check before calling this

require_once __DIR__ . '/Image.php';

class UploadManager {
    private PDO $pdo;
    private Image $image;
    private string $upload_dir;

    // what we'll accept
    private array $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private int $max_size = 5 * 1024 * 1024; // 5mb should be enough

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->image = new Image($pdo);
        $this->upload_dir = $this->image->get_upload_dir();

        // make sure the folder exists
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }

    // upload a single file, returns the image data array or an error string
    public function upload(array $file, int $uploaded_by = null): array|string {
        // basic checks
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->upload_error_msg($file['error']);
        }

        if ($file['size'] > $this->max_size) {
            return 'file too big, max is 5mb';
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $this->allowed_types)) {
            return 'only jpg, png, gif, and webp are allowed';
        }

        // generate a unique filename so nothing overwrites
        $ext = $this->get_extension($mime);
        $filename = $this->generate_filename($ext);

        $destination = $this->upload_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return 'failed to move uploaded file';
        }

        // save to db
        $stmt = $this->pdo->prepare(
            "INSERT INTO images (filename, original_name, uploaded_by) VALUES (?, ?, ?)"
        );
        $stmt->execute([$filename, $file['name'], $uploaded_by]);

        $id = (int) $this->pdo->lastInsertId();

        // return the full image data
        return $this->image->by_id($id);
    }

    // upload multiple files at once
    public function upload_multiple(array $files, int $uploaded_by = null): array {
        $results = [];

        // php structures multi-file uploads weird, gotta reorganize
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            $single = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
            $results[] = $this->upload($single, $uploaded_by);
        }

        return $results;
    }

    // turn the php upload error code into something readable
    private function upload_error_msg(int $code): string {
        return match($code) {
            UPLOAD_ERR_INI_SIZE   => 'file exceeds server upload limit',
            UPLOAD_ERR_FORM_SIZE  => 'file exceeds form upload limit',
            UPLOAD_ERR_PARTIAL    => 'file was only partially uploaded',
            UPLOAD_ERR_NO_FILE    => 'no file was selected',
            UPLOAD_ERR_NO_TMP_DIR => 'server missing temp folder',
            UPLOAD_ERR_CANT_WRITE => 'failed to write to disk',
            UPLOAD_ERR_EXTENSION  => 'upload blocked by extension',
            default               => 'unknown upload error',
        };
    }

    // map mime type to file extension
    private function get_extension(string $mime): string {
        return match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            default      => 'bin',
        };
    }

    // unique filename based on timestamp + random
    private function generate_filename(string $ext): string {
        return date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    }
}
