<?php

/**
 * Script to create deployment package for Hostinger Shared Server
 * Domain: https://gallopingunicorn.com
 */

$rootDir = __DIR__;
$deployDir = $rootDir . '/deploy';

echo "=== Creating Deployment Package ===\n\n";

// Create deploy directory structure
$directories = [
    'deploy',
    'deploy/database',
    'deploy/storage/app',
    'deploy/storage/framework/cache',
    'deploy/storage/framework/sessions',
    'deploy/storage/framework/views',
    'deploy/storage/logs',
    'deploy/bootstrap/cache',
];

foreach ($directories as $dir) {
    $fullPath = $rootDir . '/' . $dir;
    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0755, true);
        echo "✓ Created directory: $dir\n";
    }
}

// Files and directories to copy
$itemsToCopy = [
    // Core Laravel files
    'app' => 'app',
    'bootstrap' => 'bootstrap',
    'config' => 'config',
    'database/migrations' => 'database/migrations',
    'database/seeders' => 'database/seeders',
    'database/factories' => 'database/factories',
    'Modules' => 'Modules',
    'public' => 'public',
    'resources' => 'resources',
    'routes' => 'routes',
    'storage/app/public' => 'storage/app/public',
    'storage/framework' => 'storage/framework',
    'storage/logs' => 'storage/logs',
    'tests' => 'tests',
    'vendor' => 'vendor',
    
    // Root files
    'artisan' => 'artisan',
    'composer.json' => 'composer.json',
    'composer.lock' => 'composer.lock',
    'package.json' => 'package.json',
    'package-lock.json' => 'package-lock.json',
    'phpunit.xml' => 'phpunit.xml',
    'webpack.mix.js' => 'webpack.mix.js',
];

// Copy files and directories
foreach ($itemsToCopy as $source => $dest) {
    $sourcePath = $rootDir . '/' . $source;
    $destPath = $deployDir . '/' . $dest;
    
    if (file_exists($sourcePath)) {
        if (is_dir($sourcePath)) {
            // Skip vendor if it doesn't exist (will be installed via composer)
            if ($source === 'vendor' && !is_dir($sourcePath)) {
                echo "⚠ Skipping vendor (will install via composer)\n";
                continue;
            }
            
            copyDirectory($sourcePath, $destPath);
            echo "✓ Copied directory: $source\n";
        } else {
            // Create destination directory if needed
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            copy($sourcePath, $destPath);
            echo "✓ Copied file: $source\n";
        }
    } else {
        echo "⚠ Not found: $source\n";
    }
}

// Create .gitignore for deploy folder
$gitignore = <<<'GITIGNORE'
/vendor/
/node_modules/
/.env
/.idea
/.vscode
/storage/*.key
/storage/logs/*.log
/storage/framework/cache/*
/storage/framework/sessions/*
/storage/framework/views/*
!storage/framework/cache/.gitignore
!storage/framework/sessions/.gitignore
!storage/framework/views/.gitignore
GITIGNORE;

file_put_contents($deployDir . '/.gitignore', $gitignore);
echo "✓ Created .gitignore\n";

// Create storage placeholder files
$storageFiles = [
    'storage/framework/cache/.gitignore' => '*\n!.gitignore',
    'storage/framework/sessions/.gitignore' => '*\n!.gitignore',
    'storage/framework/views/.gitignore' => '*\n!.gitignore',
    'storage/logs/.gitignore' => '*\n!.gitignore',
];

foreach ($storageFiles as $file => $content) {
    $filePath = $deployDir . '/' . $file;
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($filePath, $content);
}

echo "✓ Created storage placeholder files\n";

// Update public/index.php to work from root
$publicIndexPath = $deployDir . '/public/index.php';
if (file_exists($publicIndexPath)) {
    $indexContent = file_get_contents($publicIndexPath);
    // The index.php should already work, but we'll create a root index.php too
    $rootIndexPath = $deployDir . '/index.php';
    if (!file_exists($rootIndexPath)) {
        // Create a root index.php that points to public
        $rootIndex = <<<'PHP'
<?php

/**
 * Laravel Application Entry Point
 * For shared hosting where public_html is the root
 */

require __DIR__.'/public/index.php';
PHP;
        file_put_contents($rootIndexPath, $rootIndex);
        echo "✓ Created root index.php\n";
    }
}

echo "\n=== Deployment Package Created Successfully ===\n";
echo "Location: $deployDir\n";
echo "\nNext steps:\n";
echo "1. Export your database and place in: deploy/database/pharma_crm_database.sql\n";
echo "2. Copy env.example to .env and configure it\n";
echo "3. Upload all files to Hostinger public_html/\n";
echo "4. Follow DEPLOYMENT_INSTRUCTIONS.md\n";

function copyDirectory($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst, 0755, true);
    
    // Skip certain directories
    $skipDirs = ['.git', 'node_modules', '.idea', '.vscode'];
    
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            // Skip if in skip list
            if (in_array($file, $skipDirs)) {
                continue;
            }
            
            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;
            
            if (is_dir($srcPath)) {
                copyDirectory($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }
    closedir($dir);
}

