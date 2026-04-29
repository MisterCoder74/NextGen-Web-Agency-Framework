<?php
/**
 * Tenant Handler API
 * Manages multi-tenant isolated workspaces.
 * 
 * Actions:
 *   create      - Seller creates a tenant (full master copy + setup)
 *   list        - List all tenants (with pagination)
 *   get         - Get single tenant details
 *   update      - Update tenant (name, authorized_emails, status)
 *   suspend     - Suspend a tenant (soft delete)
 *   activate    - Manually activate a tenant
 *   add_user    - Add a user to tenant's users.json
 *   list_users  - List users of a specific tenant
 *   remove_user - Remove a user from tenant
 *   check_email - Validate if an email is authorized
 *   copy_status - Check master copy status
 */

require_once __DIR__ . '/security_helper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class TenantHandler
{
    private $registryFile;
    private $baseDir;
    private $tenantsDir;
    private $masterDir;

    public function __construct()
    {
        $this->baseDir = dirname(__DIR__, 2);
        $this->tenantsDir = $this->baseDir . '/tenants';
        $this->masterDir = $this->baseDir . '/master';
        $this->registryFile = $this->baseDir . '/tenants.json';

        if (!is_dir($this->tenantsDir)) {
            mkdir($this->tenantsDir, 0755, true);
        }
    }

    public function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $input = $method === 'POST' ? json_decode(file_get_contents('php://input'), true) : [];

        $action = $input['action'] ?? $_GET['action'] ?? '';
        $username = $input['username'] ?? $input['u'] ?? $_GET['u'] ?? 'Anonymous';
        $tenantId = $input['tenant_id'] ?? $_GET['tenant_id'] ?? '';

        if (empty($action)) {
            return $this->error('Action is required');
        }

        switch ($action) {
            case 'create':
                return $this->createTenant($input, $username);
            case 'list':
                return $this->listTenants($username, $_GET['page'] ?? 1, $_GET['limit'] ?? 20);
            case 'get':
                return $this->getTenant($tenantId, $username);
            case 'update':
                return $this->updateTenant($tenantId, $input, $username);
            case 'suspend':
                return $this->suspendTenant($tenantId, $username);
            case 'activate':
                return $this->activateTenant($tenantId, $username);
            case 'add_user':
                return $this->addUser($tenantId, $input, $username);
            case 'list_users':
                return $this->listUsers($tenantId, $username);
            case 'remove_user':
                return $this->removeUser($tenantId, $input, $username);
            case 'check_email':
                return $this->checkEmail($tenantId, $input);
            case 'copy_status':
                return $this->copyStatus($tenantId);
            default:
                return $this->error('Action not recognized');
        }
    }

    private function checkPermission($username, $requireManagement = true)
    {
        $config = $this->getSetupConfig();
        $mode = strtolower($config['mode'] ?? 'sync');
        $role = $this->getUserRole($username);

        if ($mode === 'control' && $requireManagement && $role !== 'management') {
            return false;
        }
        return true;
    }

    private function getUserRole($username)
    {
        $usersFile = $this->baseDir . '/users.json';
        if (!file_exists($usersFile)) return 'technician';

        $users = json_decode(file_get_contents($usersFile), true);
        if (!is_array($users)) return 'technician';

        foreach ($users as $user) {
            if ($user['username'] === $username) {
                return $user['role'] ?? 'technician';
            }
        }
        return 'technician';
    }

    private function getSetupConfig()
    {
        $setupFile = $this->baseDir . '/management/setup.json';
        if (file_exists($setupFile)) {
            return json_decode(file_get_contents($setupFile), true);
        }
        return [];
    }

    private function loadRegistry()
    {
        if (!file_exists($this->registryFile)) {
            return [];
        }
        $data = file_get_contents($this->registryFile);
        return json_decode($data, true) ?: [];
    }

    private function saveRegistry($tenants)
    {
        return file_put_contents($this->registryFile, json_encode($tenants, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }

    private function slugify($string)
    {
        $string = preg_replace('/[^a-zA-Z0-9\s-]/', '', $string);
        $string = preg_replace('/[\s_]+/', '-', trim($string));
        $string = preg_replace('/-+/', '-', $string);
        $string = strtolower(substr($string, 0, 60));
        return trim($string, '-');
    }

    private function isSlugUnique($slug, $excludeId = '')
    {
        $tenants = $this->loadRegistry();
        foreach ($tenants as $t) {
            if ($t['slug'] === $slug && $t['id'] !== $excludeId) {
                return false;
            }
        }
        return true;
    }

    private function getTenantPath($tenantId, $slug)
    {
        return $this->tenantsDir . '/' . $tenantId . '___' . $slug;
    }

    private function generateId()
    {
        return 'tenant_' . uniqid('', true);
    }

    private function copyDirectory($src, $dst)
    {
        $dir = opendir($src);
        if ($dir === false) {
            return false;
        }

        if (!is_dir($dst)) {
            if (!mkdir($dst, 0755, true)) {
                closedir($dir);
                return false;
            }
        }

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;

            if (is_dir($srcPath)) {
                if (!$this->copyDirectory($srcPath, $dstPath)) {
                    closedir($dir);
                    return false;
                }
            } else {
                if (!copy($srcPath, $dstPath)) {
                    closedir($dir);
                    return false;
                }
            }
        }

        closedir($dir);
        return true;
    }

    private function createTenant($input, $username)
    {
        if (!$this->checkPermission($username, true)) {
            return $this->error('Permission denied: Only managers can create tenants');
        }

        $name = trim($input['name'] ?? '');
        $authorizedEmails = $input['authorized_emails'] ?? [];
        $initialUser = $input['initial_user'] ?? [];

        if (empty($name)) {
            return $this->error('Tenant name is required');
        }

        if (!is_array($authorizedEmails) || count($authorizedEmails) === 0) {
            return $this->error('At least one authorized email is required');
        }

        $slug = $this->slugify($name);
        if (empty($slug)) {
            return $this->error('Invalid tenant name for URL slug');
        }

        if (!$this->isSlugUnique($slug)) {
            $slug = $slug . '-' . substr(uniqid(), -6);
        }

        $id = $this->generateId();
        $tenantPath = $this->getTenantPath($id, $slug);

        $tenant = [
            'id' => $id,
            'slug' => $slug,
            'name' => $name,
            'status' => 'pending',
            'authorized_emails' => array_map('strtolower', array_map('trim', $authorizedEmails)),
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $username,
            'activated_at' => null,
            'activated_by' => null,
            'path' => 'tenants/' . $id . '___' . $slug,
            'copy_complete' => false
        ];

        if (!empty($initialUser['username']) && !empty($initialUser['password'])) {
            $tenant['initial_user'] = [
                'username' => trim($initialUser['username']),
                'password' => trim($initialUser['password']),
                'role' => $initialUser['role'] ?? 'management'
            ];
        }

        if (!is_dir($this->masterDir)) {
            return $this->error('Master template not found. Please create the master/ directory first.');
        }

        $copySuccess = $this->copyDirectory($this->masterDir, $tenantPath);

        if (!$copySuccess) {
            SecurityHelper::logEvent('Tenant Creation Failed: Copy Error', $username, ['tenant' => $name]);
            return $this->error('Failed to copy master template');
        }

        $setupFile = $tenantPath . '/management/setup.json';
        if (file_exists($setupFile)) {
            $setup = json_decode(file_get_contents($setupFile), true);
            $setup['tenant_id'] = $id;
            $setup['tenant_name'] = $name;
            $setup['tenant_mode'] = 'active';
            file_put_contents($setupFile, json_encode($setup, JSON_PRETTY_PRINT));
        }

        $setupFileBase = $tenantPath . '/setup.json';
        if (file_exists($setupFileBase)) {
            $setupBase = json_decode(file_get_contents($setupFileBase), true);
            $setupBase['mode'] = 'sync';
            file_put_contents($setupFileBase, json_encode($setupBase, JSON_PRETTY_PRINT));
        }

        $auditFile = $tenantPath . '/audit_log.json';
        if (file_exists($auditFile)) {
            file_put_contents($auditFile, json_encode([], JSON_PRETTY_PRINT));
        }

        $usersFile = $tenantPath . '/users.json';
        file_put_contents($usersFile, json_encode([], JSON_PRETTY_PRINT));

        if (!empty($initialUser['username']) && !empty($initialUser['password'])) {
            $this->addUser($id, $initialUser, 'system');
        }

        $setupDataFile = $tenantPath . '/.setup.json';
        file_put_contents($setupDataFile, json_encode([
            'id' => $id,
            'slug' => $slug,
            'name' => $name,
            'authorized_emails' => $tenant['authorized_emails'],
            'created_at' => $tenant['created_at'],
            'created_by' => $username,
            'master_version' => date('Y-m-d')
        ], JSON_PRETTY_PRINT));

        $tenant['copy_complete'] = true;

        $tenants = $this->loadRegistry();
        $tenants[] = $tenant;
        $this->saveRegistry($tenants);

        SecurityHelper::logEvent('Tenant Created', $username, [
            'tenant_id' => $id,
            'tenant_name' => $name,
            'slug' => $slug,
            'emails_count' => count($authorizedEmails)
        ]);

        return $this->success('Tenant created successfully', $tenant);
    }

    private function listTenants($username, $page = 1, $limit = 20)
    {
        $tenants = $this->loadRegistry();

        usort($tenants, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        $total = count($tenants);
        $offset = ((int)$page - 1) * (int)$limit;
        $paginated = array_slice($tenants, $offset, (int)$limit);

        foreach ($paginated as &$t) {
            unset($t['path']);
        }

        return $this->success('Tenants listed', [
            'tenants' => $paginated,
            'total' => $total,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'pages' => ceil($total / $limit)
        ]);
    }

    private function getTenant($tenantId, $username)
    {
        if (empty($tenantId)) {
            return $this->error('Tenant ID is required');
        }

        $tenants = $this->loadRegistry();
        foreach ($tenants as $t) {
            if ($t['id'] === $tenantId || $t['slug'] === $tenantId) {
                unset($t['path']);
                return $this->success('Tenant found', $t);
            }
        }

        return $this->error('Tenant not found');
    }

    private function updateTenant($tenantId, $input, $username)
    {
        if (!$this->checkPermission($username, true)) {
            return $this->error('Permission denied');
        }

        if (empty($tenantId)) {
            return $this->error('Tenant ID is required');
        }

        $tenants = $this->loadRegistry();
        $found = false;

        foreach ($tenants as &$t) {
            if ($t['id'] === $tenantId) {
                if (isset($input['name'])) {
                    $t['name'] = trim($input['name']);
                }
                if (isset($input['authorized_emails'])) {
                    $t['authorized_emails'] = array_map('strtolower', array_map('trim', $input['authorized_emails']));
                }
                if (isset($input['status'])) {
                    $t['status'] = in_array($input['status'], ['pending', 'active', 'suspended']) ? $input['status'] : $t['status'];
                }
                $t['updated_at'] = date('Y-m-d H:i:s');

                $tenantPath = $this->getTenantPath($t['id'], $t['slug']);
                $setupFile = $tenantPath . '/.setup.json';
                if (file_exists($setupFile)) {
                    $setup = json_decode(file_get_contents($setupFile), true);
                    $setup['name'] = $t['name'];
                    $setup['authorized_emails'] = $t['authorized_emails'];
                    file_put_contents($setupFile, json_encode($setup, JSON_PRETTY_PRINT));
                }

                $found = true;
                $updated = $t;
                break;
            }
        }

        if (!$found) {
            return $this->error('Tenant not found');
        }

        $this->saveRegistry($tenants);
        SecurityHelper::logEvent('Tenant Updated', $username, ['tenant_id' => $tenantId]);
        unset($updated['path']);
        return $this->success('Tenant updated', $updated);
    }

    private function suspendTenant($tenantId, $username)
    {
        if (!$this->checkPermission($username, true)) {
            return $this->error('Permission denied');
        }

        if (empty($tenantId)) {
            return $this->error('Tenant ID is required');
        }

        $tenants = $this->loadRegistry();
        $found = false;

        foreach ($tenants as &$t) {
            if ($t['id'] === $tenantId) {
                $t['status'] = 'suspended';
                $t['suspended_at'] = date('Y-m-d H:i:s');
                $t['suspended_by'] = $username;
                $found = true;
                break;
            }
        }

        if (!$found) {
            return $this->error('Tenant not found');
        }

        $this->saveRegistry($tenants);
        SecurityHelper::logEvent('Tenant Suspended', $username, ['tenant_id' => $tenantId]);
        return $this->success('Tenant suspended');
    }

    private function activateTenant($tenantId, $username)
    {
        if (!$this->checkPermission($username, true)) {
            return $this->error('Permission denied');
        }

        if (empty($tenantId)) {
            return $this->error('Tenant ID is required');
        }

        $tenants = $this->loadRegistry();
        $found = false;

        foreach ($tenants as &$t) {
            if ($t['id'] === $tenantId) {
                $t['status'] = 'active';
                $t['activated_at'] = date('Y-m-d H:i:s');
                $t['activated_by'] = $username;
                $found = true;

                $tenantPath = $this->getTenantPath($t['id'], $t['slug']);
                $activatedFile = $tenantPath . '/.activated';
                file_put_contents($activatedFile, json_encode([
                    'activated_at' => $t['activated_at'],
                    'activated_by' => $username
                ], JSON_PRETTY_PRINT));

                $setupFile = $tenantPath . '/management/setup.json';
                if (file_exists($setupFile)) {
                    $setup = json_decode(file_get_contents($setupFile), true);
                    $setup['tenant_mode'] = 'active';
                    file_put_contents($setupFile, json_encode($setup, JSON_PRETTY_PRINT));
                }
                break;
            }
        }

        if (!$found) {
            return $this->error('Tenant not found');
        }

        $this->saveRegistry($tenants);
        SecurityHelper::logEvent('Tenant Activated', $username, ['tenant_id' => $tenantId]);
        return $this->success('Tenant activated');
    }

    private function addUser($tenantId, $input, $username)
    {
        if (empty($tenantId)) {
            return $this->error('Tenant ID is required');
        }

        $userUsername = trim($input['username'] ?? '');
        $userPassword = trim($input['password'] ?? '');
        $userRole = $input['role'] ?? 'technician';

        if (empty($userUsername) || empty($userPassword)) {
            return $this->error('Username and password are required');
        }

        if (!in_array($userRole, ['management', 'technician'])) {
            $userRole = 'technician';
        }

        $tenants = $this->loadRegistry();
        $tenant = null;
        $tenantIndex = -1;

        foreach ($tenants as $i => $t) {
            if ($t['id'] === $tenantId || $t['slug'] === $tenantId) {
                $tenant = &$tenants[$i];
                $tenantIndex = $i;
                break;
            }
        }

        if (!$tenant) {
            return $this->error('Tenant not found');
        }

        $tenantPath = $this->getTenantPath($tenant['id'], $tenant['slug']);
        $usersFile = $tenantPath . '/users.json';

        $users = [];
        if (file_exists($usersFile)) {
            $users = json_decode(file_get_contents($usersFile), true) ?: [];
        }

        foreach ($users as $u) {
            if ($u['username'] === $userUsername) {
                return $this->error('Username already exists in this tenant');
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
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        SecurityHelper::logEvent('Tenant User Added', $username, [
            'tenant_id' => $tenantId,
            'user' => $userUsername,
            'role' => $userRole
        ]);

        unset($newUser['password']);
        return $this->success('User added to tenant', $newUser);
    }

    private function listUsers($tenantId, $username)
    {
        if (empty($tenantId)) {
            return $this->error('Tenant ID is required');
        }

        $tenants = $this->loadRegistry();
        $tenant = null;

        foreach ($tenants as $t) {
            if ($t['id'] === $tenantId || $t['slug'] === $tenantId) {
                $tenant = $t;
                break;
            }
        }

        if (!$tenant) {
            return $this->error('Tenant not found');
        }

        $tenantPath = $this->getTenantPath($tenant['id'], $tenant['slug']);
        $usersFile = $tenantPath . '/users.json';

        if (!file_exists($usersFile)) {
            return $this->success('Users listed', ['users' => []]);
        }

        $users = json_decode(file_get_contents($usersFile), true) ?: [];

        foreach ($users as &$u) {
            unset($u['password']);
        }

        return $this->success('Users listed', ['users' => $users]);
    }

    private function removeUser($tenantId, $input, $username)
    {
        if (!$this->checkPermission($username, true)) {
            return $this->error('Permission denied');
        }

        if (empty($tenantId)) {
            return $this->error('Tenant ID is required');
        }

        $targetUser = trim($input['username'] ?? '');

        if (empty($targetUser)) {
            return $this->error('Username is required');
        }

        $tenants = $this->loadRegistry();
        $tenant = null;

        foreach ($tenants as $t) {
            if ($t['id'] === $tenantId || $t['slug'] === $tenantId) {
                $tenant = $t;
                break;
            }
        }

        if (!$tenant) {
            return $this->error('Tenant not found');
        }

        $tenantPath = $this->getTenantPath($tenant['id'], $tenant['slug']);
        $usersFile = $tenantPath . '/users.json';

        if (!file_exists($usersFile)) {
            return $this->error('Users file not found');
        }

        $users = json_decode(file_get_contents($usersFile), true) ?: [];
        $originalCount = count($users);

        $users = array_values(array_filter($users, function($u) use ($targetUser) {
            return $u['username'] !== $targetUser;
        }));

        if (count($users) === $originalCount) {
            return $this->error('User not found');
        }

        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        SecurityHelper::logEvent('Tenant User Removed', $username, [
            'tenant_id' => $tenantId,
            'removed_user' => $targetUser
        ]);

        return $this->success('User removed from tenant');
    }

    private function checkEmail($tenantId, $input)
    {
        if (empty($tenantId)) {
            return ['valid' => false, 'authorized' => false, 'message' => 'Tenant ID required'];
        }

        $email = strtolower(trim($input['email'] ?? ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'authorized' => false, 'message' => 'Invalid email format'];
        }

        $tenants = $this->loadRegistry();
        $tenant = null;

        foreach ($tenants as $t) {
            if ($t['id'] === $tenantId || $t['slug'] === $tenantId) {
                $tenant = $t;
                break;
            }
        }

        if (!$tenant) {
            return ['valid' => true, 'authorized' => false, 'message' => 'Tenant not found'];
        }

        $authorizedEmails = $tenant['authorized_emails'] ?? [];
        $isAuthorized = in_array($email, $authorizedEmails);

        return [
            'valid' => true,
            'authorized' => $isAuthorized,
            'email' => $email,
            'tenant_status' => $tenant['status']
        ];
    }

    private function copyStatus($tenantId)
    {
        if (empty($tenantId)) {
            return $this->error('Tenant ID is required');
        }

        $tenants = $this->loadRegistry();
        $tenant = null;

        foreach ($tenants as $t) {
            if ($t['id'] === $tenantId) {
                $tenant = $t;
                break;
            }
        }

        if (!$tenant) {
            return $this->error('Tenant not found');
        }

        $tenantPath = $this->getTenantPath($tenant['id'], $tenant['slug']);
        $checks = [
            'directory_exists' => is_dir($tenantPath),
            'users_file' => file_exists($tenantPath . '/users.json'),
            'setup_file' => file_exists($tenantPath . '/management/setup.json'),
            'audit_file' => file_exists($tenantPath . '/audit_log.json'),
            'activated_file' => file_exists($tenantPath . '/.activated')
        ];

        $checks['all_ok'] = $checks['directory_exists'] && $checks['users_file'];

        return $this->success('Copy status retrieved', [
            'tenant_id' => $tenantId,
            'copy_complete' => $tenant['copy_complete'] ?? false,
            'checks' => $checks
        ]);
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
    $handler = new TenantHandler();
    $response = $handler->handleRequest();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('Tenant Handler Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ], JSON_UNESCAPED_UNICODE);
}
?>
