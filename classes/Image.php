<?php
// image lookup class
// get an image by id or filename, easy

class Image {
    private PDO $pdo;

    // upload dir lives as a sibling to the project
    private string $upload_dir;
    private string $upload_url;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;

        // sibling directory of the project in htdocs
        $doc_root = !empty($_SERVER['DOCUMENT_ROOT']) 
            ? rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/') 
            : dirname(rtrim(str_replace('\\', '/', ROOT_DIR), '/'));

        $this->upload_dir = $doc_root . '/eb-uploads/';

        // web-accessible path
        $this->upload_url = '/eb-uploads/';
    }

    // the main thing — pass in an id (int) or filename (string) and get the image data back
    public function get($input): ?array {
        if (is_numeric($input)) {
            return $this->by_id((int) $input);
        }
        return $this->by_filename((string) $input);
    }

    // fetch by image_id
    public function by_id(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM images WHERE image_id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->attach_paths($row) : null;
    }

    // fetch by filename or original_name
    public function by_filename(string $filename): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM images WHERE filename = ? OR original_name = ? LIMIT 1");
        $stmt->execute([$filename, $filename]);
        $row = $stmt->fetch();

        return $row ? $this->attach_paths($row) : null;
    }

    // get all images, newest first
    public function all(): array {
        $stmt = $this->pdo->query("SELECT * FROM images ORDER BY uploaded_at DESC");
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => $this->attach_paths($row), $rows);
    }

    // delete an image by id (file + db record)
    public function delete(int $id): bool {
        $img = $this->by_id($id);
        if (!$img) return false;

        // nuke the file if it exists
        $filepath = $this->upload_dir . $img['filename'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        $stmt = $this->pdo->prepare("DELETE FROM images WHERE image_id = ?");
        return $stmt->execute([$id]);
    }

    // tack on the full path and url so templates can use them directly
    private function attach_paths(array $row): array {
        $row['filepath'] = $this->upload_dir . $row['filename'];
        $row['url'] = $this->upload_url . $row['filename'];
        $row['exists'] = file_exists($this->upload_dir . $row['filename']);
        return $row;
    }

    // get the upload directory path
    public function get_upload_dir(): string {
        return $this->upload_dir;
    }

    // get the web url base
    public function get_upload_url(): string {
        return $this->upload_url;
    }
}
