<?php
/**
 * Security Headers Implementation
 * Include this file at the beginning of your PHP files
 */

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Block direct access to this file
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    http_response_code(403);
    exit('Access denied');
}

// Block access to sensitive files
$sensitive_files = ['config.php', '.env', '*.sql', '*.log'];
$current_file = basename($_SERVER['SCRIPT_NAME']);

foreach ($sensitive_files as $pattern) {
    if (fnmatch($pattern, $current_file)) {
        http_response_code(403);
        exit('Access denied');
    }
}

// Block suspicious user agents
$blocked_agents = ['', 'java', 'curl', 'wget', 'python', 'nikto', 'scan', 'HTTrack'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

foreach ($blocked_agents as $agent) {
    if (stripos($user_agent, $agent) !== false) {
        http_response_code(403);
        exit('Access denied');
    }
}

// Block suspicious query strings
$suspicious_patterns = [
    '/<script/i',
    '/javascript:/i',
    '/vbscript:/i',
    '/onload=/i',
    '/onerror=/i',
    '/<iframe/i',
    '/base64_decode/i',
    '/eval\(/i',
    '/exec\(/i'
];

$query_string = $_SERVER['QUERY_STRING'] ?? '';
foreach ($suspicious_patterns as $pattern) {
    if (preg_match($pattern, $query_string)) {
        http_response_code(403);
        exit('Access denied');
    }
}
?> 