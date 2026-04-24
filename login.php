<?php
/**
 * Vivacity NextGen Web Agency Framework
 * Login Validation - No Sessions
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);

    if ($username && $password) {
        $usersFile = 'users.json';
        if (file_exists($usersFile)) {
            $users = json_decode(file_get_contents($usersFile), true);
            
            $authenticated = false;
            $role = 'technician'; // Default role if not found
            foreach ($users as $user) {
                if ($user['username'] === $username && $user['password'] === $password) {
                    $authenticated = true;
                    if (isset($user['role'])) {
                        $role = $user['role'];
                    }
                    break;
                }
            }

            if ($authenticated) {
                // Successful login - Redirect based on role
                if ($role === 'management') {
                    header('Location: ./management/dashboard.html?u=' . urlencode($username));
                } else {
                    // Default to technician path
                    header('Location: ./tools/dashboard.html?u=' . urlencode($username));
                }
                exit;
            } else {
                // Invalid credentials
                header('Location: index.php?error=1');
                exit;
            }
        } else {
            // users.json missing
            header('Location: index.php?error=2');
            exit;
        }
    } else {
        // Missing fields
        header('Location: index.php?error=3');
        exit;
    }
} else {
    // Direct access not allowed
    header('Location: index.php');
    exit;
}
?>
