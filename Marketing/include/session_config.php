<?php
session_start();


// If user is NOT logged in, redirect to main site
if (!isset($_SESSION['user_mobile']) || empty($_SESSION['user_mobile'])) {
    header("Location: http://quotation.tuckermotors.com/"); // main site
    exit();
}

// Optional: check session timeout
$timeout = 8 * 60 * 60;
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout) {
    session_destroy();
    header("Location: http://quotation.tuckermotors.com/");
    exit();
}


// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Change to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Lax');

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_mobile']) && !empty($_SESSION['user_mobile']);
}

// Redirect to login page if not logged in
function requireLogin()
{
    if (!isLoggedIn()) {
        // Make sure the login URL exists
        $loginURL = "/admin_cms/index.php"; // relative path
        header("Location: $loginURL");
        exit();
    }
}

// Get current user info
function getCurrentUser()
{
    return $_SESSION['user_mobile'] ?? null;
}

function getCurrentUsername()
{
    return $_SESSION['user_name'] ?? null;
}

// Set user session
function setUserSession($user_mobile, $user_name = null)
{
    $_SESSION['user_mobile'] = $user_mobile;
    $_SESSION['user_name'] = $user_name;
    $_SESSION['login_time'] = time();
}

// Logout user
function logout()
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();

    $loginURL = "index.php";
    header("Location: $loginURL");
    exit();
}

// Session timeout (8 hours)
function checkSessionTimeout()
{
    $timeout = 8 * 60 * 60;
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout) {
        logout();
    }
}

// Allowed users
function isAllowedUser()
{
    $allowedUsers = ['Amsathvani', 'Srihari', 'Kannan'];
    $currentUsername = getCurrentUsername();
    return in_array($currentUsername, $allowedUsers);
}

// Page access control
function hasPageAccess($page)
{
    $currentUsername = getCurrentUsername();
    $commonPages = ['Dashboard.php', 'lead.php', 'Add_Quatation.php', 'Quotation.php', 'invoice.php', 'logout.php'];
    $restrictedPages = ['product.php'];

    if (in_array($page, $commonPages)) {
        return isAllowedUser();
    } elseif (in_array($page, $restrictedPages)) {
        return $currentUsername === 'Amsathvani';
    }

    return isAllowedUser();
}

// Require login and check access
function requireLoginAndAccess($page = null)
{
    requireLogin();

    if ($page && !hasPageAccess($page)) {
        // Make sure this page exists
        $dashboardURL = "/Marketing/Dashboard.php";
        header("Location: $dashboardURL");
        exit();
    }
}

// Example usage: call this at the top of each page
checkSessionTimeout();
requireLoginAndAccess(basename(__FILE__));
