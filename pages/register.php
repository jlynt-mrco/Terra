<?php
/**
 * TERRA — Register redirection
 * Redirects to unified login onboarding screen and opens the registration sheet automatically.
 */
require_once __DIR__ . '/../config.php';

redirect('pages/login.php?action=register');
