<?php

// Simple script to clear Laravel cache
require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "Clearing Laravel caches...\n";

// Clear config cache
$kernel->call('config:clear');
echo "✓ Config cache cleared\n";

// Clear application cache
$kernel->call('cache:clear');
echo "✓ Application cache cleared\n";

// Clear route cache
$kernel->call('route:clear');
echo "✓ Route cache cleared\n";

echo "\nCache clearing complete!\n";
echo "Now test with: php artisan ai:test-openai\n";