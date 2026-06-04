<?php
// Vercel PHP Front Controller
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

if ($path === '/' || $path === '') {
    $path = '/index.php';
}

$file = realpath(__DIR__ . '/..' . $path);
$root = realpath(__DIR__ . '/..');

// Basic security check: ensure the file is within the project root
if ($file && strpos($file, $root) === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    if (file_exists($file) && is_file($file)) {
        require $file;
    } else {
        http_response_code(404);
        echo "404 Not Found";
    }
} else {
    http_response_code(404);
    echo "404 Not Found";
}
