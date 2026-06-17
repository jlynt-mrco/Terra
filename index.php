<?php
/**
 * TERRA — Entry Point
 */
require_once __DIR__ . '/config.php';

// Redirect to home if logged in, otherwise to login
if (isLoggedIn()) {
    redirect('pages/home.php');
} else {
    redirect('pages/login/index.php');
}
