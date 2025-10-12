<?php

/**
 * CrewAI Tours Verification Script
 *
 * Verifies that onboarding tours have been properly implemented:
 * - Tour JavaScript files exist
 * - Functions are compiled in the bundle
 * - UI buttons are present in views
 * - Tour configurations are valid
 */

echo "=== CrewAI Tours Verification ===\n\n";

$basePath = __DIR__;
$errors = [];
$warnings = [];

// Test 1: Verify tour files exist
echo "1. Checking tour files...\n";
$tourFiles = [
    'resources/js/tours/crew-launch-tour.js',
    'resources/js/tours/execution-monitor-tour.js',
];

foreach ($tourFiles as $file) {
    $fullPath = $basePath . '/' . $file;
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        echo "   ✓ Found: {$file} (" . number_format($size) . " bytes)\n";

        // Check if file has content
        if ($size < 100) {
            $warnings[] = "{$file} seems too small (< 100 bytes)";
        }
    } else {
        $errors[] = "Missing tour file: {$file}";
        echo "   ✗ Missing: {$file}\n";
    }
}

// Test 2: Verify app.js imports
echo "\n2. Checking app.js imports...\n";
$appJsPath = $basePath . '/resources/js/app.js';
if (file_exists($appJsPath)) {
    $appJsContent = file_get_contents($appJsPath);

    $requiredImports = [
        "import {" => "Tour imports",
        "from './tours/crew-launch-tour'" => "Crew launch tour import",
        "from './tours/execution-monitor-tour'" => "Execution monitor tour import",
        "window.startCrewLaunchTour" => "Crew tour global export",
        "window.startExecutionMonitorTour" => "Execution tour global export",
        "autoStartCrewLaunchTour()" => "Auto-start crew tour",
        "autoStartExecutionMonitorTour()" => "Auto-start execution tour",
    ];

    foreach ($requiredImports as $needle => $description) {
        if (strpos($appJsContent, $needle) !== false) {
            echo "   ✓ {$description}\n";
        } else {
            $errors[] = "Missing in app.js: {$description}";
            echo "   ✗ Missing: {$description}\n";
        }
    }
} else {
    $errors[] = "app.js not found";
    echo "   ✗ app.js not found\n";
}

// Test 3: Verify compiled bundle
echo "\n3. Checking compiled bundle...\n";
$buildPath = $basePath . '/public/build/assets';
if (is_dir($buildPath)) {
    $jsFiles = glob($buildPath . '/app-*.js');
    if (!empty($jsFiles)) {
        $bundlePath = $jsFiles[0];
        $bundleSize = filesize($bundlePath);
        echo "   ✓ Found bundle: " . basename($bundlePath) . " (" . number_format($bundleSize) . " bytes)\n";

        $bundleContent = file_get_contents($bundlePath);

        // Check for tour functions (they might be minified)
        $functionsToCheck = [
            'startCrewLaunchTour' => 'Crew launch function',
            'startExecutionMonitorTour' => 'Execution monitor function',
            'shepherd' => 'Shepherd.js library',
        ];

        foreach ($functionsToCheck as $func => $desc) {
            if (stripos($bundleContent, $func) !== false) {
                echo "   ✓ {$desc} found in bundle\n";
            } else {
                $warnings[] = "{$desc} not clearly visible in bundle (might be minified)";
                echo "   ⚠ {$desc} not found (may be minified)\n";
            }
        }
    } else {
        $errors[] = "No compiled JS bundle found";
        echo "   ✗ No compiled bundle found\n";
    }
} else {
    $errors[] = "Build directory not found";
    echo "   ✗ Build directory not found\n";
}

// Test 4: Verify UI buttons in views
echo "\n4. Checking UI buttons in views...\n";
$viewFiles = [
    'resources/views/tenant/crews/show.blade.php' => 'Crew detail page',
    'resources/views/tenant/crew-executions/show.blade.php' => 'Execution monitor page',
];

foreach ($viewFiles as $viewPath => $description) {
    $fullPath = $basePath . '/' . $viewPath;
    if (file_exists($fullPath)) {
        $viewContent = file_get_contents($fullPath);

        // Check for Show Tour button
        $hasShowTourButton = strpos($viewContent, 'Show Tour') !== false;
        $hasOnclickHandler = strpos($viewContent, 'onclick="window.start') !== false;

        if ($hasShowTourButton && $hasOnclickHandler) {
            echo "   ✓ {$description}: Show Tour button present\n";

            // Count occurrences
            $buttonCount = substr_count($viewContent, 'Show Tour');
            if ($buttonCount > 1) {
                $warnings[] = "{$description} has {$buttonCount} 'Show Tour' buttons (expected 1)";
            }
        } else {
            $errors[] = "{$description}: Show Tour button missing or incomplete";
            echo "   ✗ {$description}: Show Tour button missing\n";
        }
    } else {
        $errors[] = "View file not found: {$viewPath}";
        echo "   ✗ {$description}: File not found\n";
    }
}

// Test 5: Verify tour step count
echo "\n5. Checking tour configurations...\n";
foreach ($tourFiles as $tourFile) {
    $fullPath = $basePath . '/' . $tourFile;
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);

        // Count tour steps (looking for .addStep calls)
        $stepCount = substr_count($content, '.addStep(');

        if ($stepCount > 0) {
            echo "   ✓ " . basename($tourFile) . ": {$stepCount} steps configured\n";

            if ($stepCount < 5) {
                $warnings[] = basename($tourFile) . " has only {$stepCount} steps (might be incomplete)";
            }
        } else {
            $errors[] = basename($tourFile) . ": No tour steps found";
            echo "   ✗ " . basename($tourFile) . ": No steps found\n";
        }

        // Check for localStorage usage
        if (strpos($content, 'localStorage') !== false) {
            echo "   ✓ " . basename($tourFile) . ": Uses localStorage for persistence\n";
        } else {
            $warnings[] = basename($tourFile) . ": No localStorage usage found";
        }
    }
}

// Test 6: Verify documentation
echo "\n6. Checking documentation...\n";
$docFiles = [
    'CREWAI_ONBOARDING_IMPLEMENTATION.md',
    'CREWAI_ONBOARDING_TESTS.md',
    'CREWAI_TOURS_QUICK_REFERENCE.md',
];

foreach ($docFiles as $docFile) {
    $fullPath = $basePath . '/' . $docFile;
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        echo "   ✓ {$docFile} (" . number_format($size) . " bytes)\n";
    } else {
        $warnings[] = "Documentation missing: {$docFile}";
        echo "   ⚠ {$docFile} not found\n";
    }
}

// Summary
echo "\n=== Verification Summary ===\n";

if (empty($errors)) {
    echo "✅ All critical checks passed!\n";
} else {
    echo "❌ Errors found: " . count($errors) . "\n";
    foreach ($errors as $error) {
        echo "   - {$error}\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠️  Warnings: " . count($warnings) . "\n";
    foreach ($warnings as $warning) {
        echo "   - {$warning}\n";
    }
}

echo "\n=== Next Steps ===\n";
echo "1. Open browser to: http://localhost:8000/dashboard/crews/01k777jwyg8ctc23zm3z1ta7tz\n";
echo "2. Wait for auto-tour to start (1 second delay)\n";
echo "3. Click through all tour steps\n";
echo "4. Click 'Show Tour' button to restart manually\n";
echo "5. Test execution monitor tour on an execution page\n";
echo "\n";
echo "To clear tour state:\n";
echo "  localStorage.removeItem('ainstein_tour_crew_launch_completed');\n";
echo "  localStorage.removeItem('ainstein_tour_execution_monitor_completed');\n";
echo "\n";

if (empty($errors)) {
    echo "✓ Tours are ready for testing!\n";
    exit(0);
} else {
    echo "✗ Please fix errors before testing\n";
    exit(1);
}
