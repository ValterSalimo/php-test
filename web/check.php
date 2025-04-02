<?php
// Display all errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if autoload.php exists
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
echo 'Checking autoload file: ' . $autoloadPath . "\n";
echo 'File exists: ' . (file_exists($autoloadPath) ? 'Yes' : 'No') . "\n";

// Directory listing
echo "Directory listing of /server/http:\n";
system('ls -la /server/http');
echo "\n";

echo "Directory listing of parent vendor directory:\n";
system('ls -la /server/http/vendor');
echo "\n";

// Try to include the file if it exists
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    echo "Autoload file included successfully\n";
} else {
    echo "Autoload file not found! Creating structure...\n";
    
    // Create vendor directory if it doesn't exist
    if (!is_dir(dirname($autoloadPath))) {
        mkdir(dirname($autoloadPath), 0755, true);
    }
    
    // Create a minimal autoloader
    $code = '<?php
// Simple autoloader
spl_autoload_register(function ($class) {
    $prefix = "App\\\\";
    $base_dir = __DIR__ . "/../src/";
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace("\\\\", "/", $relative_class) . ".php";
    
    if (file_exists($file)) {
        require $file;
    }
});';
    
    file_put_contents($autoloadPath, $code);
    echo "Created autoload file at $autoloadPath\n";
}

// Print the loaded classes
echo "Registered autoload functions: \n";
print_r(spl_autoload_functions());
