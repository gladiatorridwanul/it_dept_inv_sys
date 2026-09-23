<?php
// Simple autoloader for barcode generator
spl_autoload_register(function ($class) {
    $prefix = 'Picqer\\Barcode\\';
    $base_dir = __DIR__ . '/picqer/barcode-generator/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});