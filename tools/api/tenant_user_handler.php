<?php
/**
 * Tenant User Handler API
 * Manages users within a specific tenant workspace.
 * 
 * This API operates on the local users.json of the tenant that contains it.
 * Detects tenant context from the directory path.
 * 
 * Actions:
 *   list   - List all users in the tenant
 *   add    - Add a new user to the tenant
 *   remove - Remove a user from the tenant
 *   update - Update a user's role
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class TenantUserHandler
{
    private $usersFile;

    public function __construct()
    {
        // Detect tenant context from directory path
        // Path structure: /path/to/tenants/tenant_xxx___slug/management/tools/api/tenant_user_handler.php
        $currentPath = dirname(__FILE__);
        $tenantsBase = dirname(dirname(dirname(__DIR__))) . '/tenants';
        
        $this->usersFile = null;
        
        if (strpos($currentPath, $tenantsBase) === 0) {
            $relPath = substr($currentPath, strlen($tenantsBase) + 1);
            // Extract tenant directory (e.g., tenant_xxx___slug from relative path)
            if (preg_match('/^([^_]+___[^\/]+)/', $relPath, $m)) {
                $tenantDir = $m[1];
                $tenantPath = $tenantsBase . '/' . $tenantDir;
                $this->usersFile = $tenantPath . '/users.json';
            }
        }
        
        // Fallback: try current directory structure (management/tools/api/)
        if ($this->usersFile === null || !file_exists($this->usersFile)) {
            $parentDir = dirname(dirname(dirname(__FILE__)));
            if (file_exists($parentDir . '/users.json')) {
                $this->usersFile = $parentDir . '/users.json';
            }
        }
    }

    public function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $input = $method === 'POST' ? json_decode(file_get_contents('php://input'), true) : [];
        
        $action = $input['action'] ?? $_GET['action'] ?? '';
        $username = $input['requester'] ?? 'Anonymous';
        
        if (empty($action)) {
            return $this->error('Action is required');
        }
        
        if ($this->usersFile === null) {
            return $this->error('Tenant context not found');
        }
        
        // Verify the file exists or create it
        if (!file_exists($this->usersFile)) {
            $dir = dirname($this->usersFile);
            if (!is_dir($dir)) {
                return $this->error('Tenant directory not found');
            }
            file_put_contents($this->usersFile, json_encode([], JSON_PRETTY_PRINT));
        }
        
        switch ($action) {
            case 'list':
                return $this->listUsers($username);
            case 'add':
                return $this->addUser($input, $username);
            case 'remove':
                return $this->removeUser($input, $username);
            case 'update':
                return $this->updateUser($input, $username);
            default:
                return $this->error('Action not recognized');
        }
    }

    private function listUsers($username)
    {
        $users = $this->loadUsers();
        
        // Remove passwords from response
        foreach ($users as &$u) {
            unset($u['password']);
        }
        
        return $this->success('Users listed', ['users' => $users]);
    }

    private function addUser($input, $username)
    {
        $userUsername = trim($input['username'] ?? '');
        $userPassword = trim($input['password'] ?? '');
        $userRole = $input['role'] ?? 'technician';
        
        if (empty($userUsername) || empty($userPassword)) {
            return $this->error('Username and password are required');
        }
        
        // Validate role
        if (!in_array($userRole, ['management', 'technician'])) {
            $userRole = 'technician';
        }
        
        // Validate username format (alphanumeric, underscore, dash, 3-30 chars)
        if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $userUsername)) {
            return $this->error('Invalid username format. Use 3-30 alphanumeric characters, underscore, or dash.');
        }
        
        $users = $this->loadUsers();
        
        // Check for duplicate username
        foreach ($users as $u) {
            if ($u['username'] === $userUsername) {
                return $this->error('Username already exists');
            }
        }
        
        $newUser = [
            'username' => $userUsername,
            'password' => $userPassword,
            'role' => $userRole,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $username
        ];
        
        $users[] = $newUser;
        
        if (!$this->saveUsers($users)) {
            return $this->error('Failed to save user');
        }
        
        $this->logEvent('User Added', $username, [
            'target_user' => $userUsername,
            'role' => $userRole
        ]);
        
        unset($newUser['password']);
        return $this->success('User added successfully', $newUser);
    }

    private function removeUser($input, $username)
    {
        $targetUsername = trim($input['username'] ?? '');
        
        if (empty($targetUsername)) {
            return $this->error('Username is required');
        }
        
        $users = $this->loadUsers();
        $originalCount = count($users);
        
        // Filter out the user to remove
        $newUsers = array_values(array_filter($users, function($u) use ($targetUsername) {
            return $u['username'] !== $targetUsername;
        }));
        
        if (count($newUsers) === $originalCount) {
            return $this->error('User not found');
        }
        
        if (!$this->saveUsers($newUsers)) {
            return $this->error('Failed to save changes');
        }
        
        $this->logEvent('User Removed', $username, [
            'removed_user' => $targetUsername
        ]);
        
        return $this->success('User removed successfully');
    }

    private function updateUser($input, $username)
    {
        $targetUsername = trim($input['username'] ?? '');
        $newRole = $input['role'] ?? '';
        
        if (empty($targetUsername)) {
            return $this->error('Username is required');
        }
        
        if (!in_array($newRole, ['management', 'technician'])) {
            return $this->error('Invalid role');
        }
        
        $users = $this->loadUsers();
        $found = false;
        
        foreach ($users as &$u) {
            if ($u['username'] === $targetUsername) {
                $u['role'] = $newRole;
                $u['updated_at'] = date('Y-m-d H:i:s');
                $u['updated_by'] = $username;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            return $this->error('User not found');
        }
        
        if (!$this->saveUsers($users)) {
            return $this->error('Failed to save changes');
        }
        
        $this->logEvent('User Updated', $username, [
            'target_user' => $targetUsername,
            'new_role' => $newRole
        ]);
        
        return $this->success('User updated successfully');
    }

    private function loadUsers()
    {
        if (!file_exists($this->usersFile)) {
            return [];
        }
        
        $data = file_get_contents($this->usersFile);
        return json_decode($data, true) ?: [];
    }

    private function saveUsers($users)
    {
        return file_put_contents($this->usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
    }

    private function logEvent($action, $username, $params)
    {
        $auditFile = dirname($this->usersFile) . '/audit_log.json';
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
            'action' => $action,
            'user' => $username,
            'params' => $params
        ];
        
        $logs = [];
        if (file_exists($auditFile)) {
            $logs = json_decode(file_get_contents($auditFile), true) ?: [];
        }
        
        $logs[] = $entry;
        
        // Keep only last 1000 entries
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }
        
        file_put_contents($auditFile, json_encode($logs, JSON_PRETTY_PRINT), LOCK_EX);
    }

    private function success($message, $data = null)
    {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $response;
    }

    private function error($message)
    {
        return [
            'success' => false,
            'message' => $message
        ];
    }
}

try {
    $handler = new TenantUserHandler();
    $response = $handler->handleRequest();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('Tenant User Handler Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ], JSON_UNESCAPED_UNICODE);
}
?>