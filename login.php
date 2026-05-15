<?php
/**
 * Vivacity NextGen Web Agency Framework
 * Login Validation - Secure Sessions
 * Supports both global and tenant-scoped authentication.
 */

require_once __DIR__ . '/tools/api/security_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST['password'] ?? '';
    $tenantSlug = filter_input(INPUT_POST, 'tenant', FILTER_SANITIZE_SPECIAL_CHARS);

    // Auto-detect tenant from path if running from a tenant directory
    $currentPath = __DIR__;
    $tenantsBase = dirname(__DIR__) . '/tenants';
    if (strpos($currentPath, $tenantsBase) === 0) {
        $relPath = substr($currentPath, strlen($tenantsBase) + 1);
        if (preg_match('/^[^_]+___(.+?)[\/\\\\]/', $relPath, $m)) {
            $tenantSlug = $m[1];
        }
    }

    if ($username && $password) {
        // If tenant is specified, try tenant authentication first
        if ($tenantSlug) {
            $safeSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $tenantSlug);
            $tenantsDir = __DIR__ . '/tenants';

            if (is_dir($tenantsDir)) {
                $tenantPath = null;
                $dh = opendir($tenantsDir);
                if ($dh) {
                    while (($entry = readdir($dh)) !== false) {
                        if ($entry === '.' || $entry === '..') continue;
                        $fullPath = $tenantsDir . '/' . $entry;
                        if (!is_dir($fullPath)) continue;
                        if (strpos($entry, '___' . $safeSlug) === 0) {
                            $tenantPath = $fullPath;
                            break;
                        }
                    }
                    closedir($dh);
                }

                if ($tenantPath) {
                    // Load registry to check status
                    $registryFile = __DIR__ . '/tenants.json';
                    $tenants = [];
                    if (file_exists($registryFile)) {
                        $tenants = json_decode(file_get_contents($registryFile), true) ?: [];
                    }

                    $currentTenant = null;
                    foreach ($tenants as $t) {
                        if ($t['slug'] === $tenantSlug) {
                            $currentTenant = $t;
                            break;
                        }
                    }

                    if ($currentTenant && ($currentTenant['status'] ?? '') === 'suspended') {
                        session_write_close();
                        header('Location: index.php?error=5&tenant=' . urlencode($tenantSlug));
                        exit;
                    }

                    $tenantUsersFile = $tenantPath . '/users.json';

                    if (file_exists($tenantUsersFile)) {
                        $tenantUsers = json_decode(file_get_contents($tenantUsersFile), true) ?: [];
                        $authenticated = false;
                        $role = 'technician';
                        $matchedUser = null;

                        foreach ($tenantUsers as $user) {
                            if ($user['username'] === $username && password_verify($password, $user['password'])) {
                                $authenticated = true;
                                $role = $user['role'] ?? 'technician';
                                $matchedUser = $user;
                                break;
                            }
                        }

                        if ($authenticated) {
                            // Check for first-login activation
                            $activatedFile = $tenantPath . '/.activated';
                            $needsActivation = !file_exists($activatedFile);

                            // Update first login flag in users
                            if ($needsActivation) {
                                foreach ($tenantUsers as &$u) {
                                    if ($u['username'] === $username) {
                                        $u['first_login_done'] = true;
                                        break;
                                    }
                                }
                                SecurityHelper::writeJson($tenantUsersFile, $tenantUsers);
                            }

                            // Start session
                            SecurityHelper::initSession();
                            $_SESSION['username'] = $username;
                            $_SESSION['role'] = $role;
                            $_SESSION['tenant'] = $tenantSlug;
                            $_SESSION['tenant_path'] = $tenantPath;

                            // Redirect to tenant workspace - No sensitive data in URL
                            $redirect = $tenantPath . '/management/dashboard.html';
                            if ($needsActivation) {
                                $redirect .= '?first_login=1';
                            }
                            session_write_close();
                            header('Location: ' . $redirect);
                            exit;
                        }
                    }
                }
            }
        }

        // Fall back to global users.json
        $usersFile = 'users.json';
        if (file_exists($usersFile)) {
            $users = json_decode(file_get_contents($usersFile), true);

            $authenticated = false;
            $role = 'technician';

            foreach ($users as $user) {
                if ($user['username'] === $username && password_verify($password, $user['password'])) {
                    $authenticated = true;
                    $role = $user['role'] ?? 'technician';
                    break;
                }
            }

            if ($authenticated) {
                // Start session
                SecurityHelper::initSession();
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                $_SESSION['tenant'] = null;

                session_write_close();
                if ($role === 'management') {
                    header('Location: ./management/dashboard.html');
                } else {
                    header('Location: ./tools/dashboard.html');
                }
                exit;
            }
        }

        // All authentication failed
        session_write_close();
        if ($tenantSlug) {
            header('Location: index.php?error=1&tenant=' . urlencode($tenantSlug));
        } else {
            header('Location: index.php?error=1');
        }
        exit;
    } else {
        session_write_close();
        header('Location: index.php?error=3' . ($tenantSlug ? '&tenant=' . urlencode($tenantSlug) : ''));
        exit;
    }
} else {
    session_write_close();
    header('Location: index.php');
    exit;
}
?>