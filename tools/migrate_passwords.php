<?php
/**
 * Migration script to hash plain-text passwords in users.json files.
 */

require_once __DIR__ . '/api/security_helper.php';

function migrateUsersFile($filePath) {
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        return;
    }

    $users = json_decode(file_get_contents($filePath), true);
    if (!is_array($users)) {
        echo "Invalid JSON in $filePath\n";
        return;
    }

    $modified = false;
    foreach ($users as &$user) {
        if (isset($user['password']) && !str_starts_with($user['password'], '$2y$')) {
            echo "Hashing password for user: {$user['username']} in $filePath\n";
            $user['password'] = password_hash($user['password'], PASSWORD_DEFAULT);
            $modified = true;
        }
    }

    if ($modified) {
        if (SecurityHelper::writeJson($filePath, $users)) {
            echo "Successfully updated $filePath\n";
        } else {
            echo "Failed to update $filePath\n";
        }
    } else {
        echo "No passwords needed hashing in $filePath\n";
    }
}

// Migrate root users.json
migrateUsersFile(__DIR__ . '/../users.json');

// Migrate master users.json
migrateUsersFile(__DIR__ . '/../master/users.json');

// Migrate tenant users.json if they exist
$tenantsDir = __DIR__ . '/../tenants';
if (is_dir($tenantsDir)) {
    $dh = opendir($tenantsDir);
    if ($dh) {
        while (($entry = readdir($dh)) !== false) {
            if ($entry === '.' || $entry === '..') continue;
            $tenantUsersFile = $tenantsDir . '/' . $entry . '/users.json';
            if (file_exists($tenantUsersFile)) {
                migrateUsersFile($tenantUsersFile);
            }
        }
        closedir($dh);
    }
}

echo "Migration complete.\n";
