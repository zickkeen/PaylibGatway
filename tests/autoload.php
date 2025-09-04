<?php

declare(strict_types=1);

/**
 * Simple Autoloader untuk Testing
 *
 * Autoloader sederhana untuk testing tanpa composer
 */

spl_autoload_register(function ($class) {
    // Base directory untuk source code
    $baseDir = __DIR__ . '/../src/';

    // Convert namespace to file path
    $classPath = str_replace('\\', '/', $class);
    $classPath = str_replace('zickkeen/PaylibGateway/', '', $classPath);
    $file = $baseDir . $classPath . '.php';

    // Check if file exists
    if (file_exists($file)) {
        require_once $file;
        return true;
    }

    return false;
});