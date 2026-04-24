<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class ProjectAssets {
    private $projectsRoot = __DIR__ . '/../../projects/';

    public function handleRequest() {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        $id = $_GET['id'] ?? $_POST['id'] ?? '';

        if (empty($id) || !$this->isValidProjectId($id)) {
            return $this->error('Invalid project ID');
        }

        $projectDir = $this->projectsRoot . $id . '/';
        if (!file_exists($projectDir)) {
            // Try to create it if sync mode is on
            $setup = $this->getSetupConfig();
            if (($setup['mode'] ?? '') === 'sync') {
                if (!file_exists($this->projectsRoot)) {
                    mkdir($this->projectsRoot, 0777, true);
                }
                mkdir($projectDir, 0777, true);
            } else {
                return $this->error('Project directory does not exist');
            }
        }

        switch ($action) {
            case 'list':
                return $this->listFiles($projectDir);
            case 'upload':
                return $this->uploadFile($projectDir);
            case 'delete':
                return $this->deleteFile($projectDir, $_GET['filename'] ?? $_POST['filename'] ?? '');
            default:
                return $this->error('Invalid action');
        }
    }

    private function isValidProjectId($id) {
        return preg_match('/^project_[a-z0-9.]+$/', $id);
    }

    private function getSetupConfig() {
        $setupFile = __DIR__ . '/../../management/setup.json';
        if (file_exists($setupFile)) {
            return json_decode(file_get_contents($setupFile), true);
        }
        return [];
    }

    private function listFiles($dir) {
        $files = [];
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === 'info.json') continue;
            
            $path = $dir . $item;
            if (is_dir($path)) continue;

            $files[] = [
                'name' => $item,
                'size' => filesize($path),
                'date' => date('Y-m-d H:i:s', filemtime($path)),
                'type' => function_exists('mime_content_type') ? mime_content_type($path) : 'application/octet-stream'
            ];
        }
        return $this->success('Files listed', $files);
    }

    private function uploadFile($dir) {
        if (!isset($_FILES['asset'])) {
            return $this->error('No file uploaded');
        }

        $file = $_FILES['asset'];
        $filename = basename($file['name']);
        $targetPath = $dir . $filename;

        // Prevent overwriting info.json
        if ($filename === 'info.json') {
            return $this->error('Cannot overwrite info.json');
        }

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $this->success('File uploaded successfully');
        } else {
            return $this->error('Failed to move uploaded file');
        }
    }

    private function deleteFile($dir, $filename) {
        if (empty($filename)) {
            return $this->error('Filename required');
        }

        $filename = basename($filename);
        $path = $dir . $filename;

        if ($filename === 'info.json') {
            return $this->error('Cannot delete info.json');
        }

        if (file_exists($path)) {
            if (unlink($path)) {
                return $this->success('File deleted');
            } else {
                return $this->error('Failed to delete file');
            }
        } else {
            return $this->error('File not found');
        }
    }

    private function success($message, $data = null) {
        $res = ['success' => true, 'message' => $message];
        if ($data !== null) $res['data'] = $data;
        return $res;
    }

    private function error($message) {
        return ['success' => false, 'message' => $message];
    }
}

$api = new ProjectAssets();
echo json_encode($api->handleRequest());
