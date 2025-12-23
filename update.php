<?php
header('Content-Type: application/json');

// --- Configuration ---
$adminPassword = 'admin123';
$repoZipUrl = 'https://github.com/oguzgokyar/Vitrin/archive/refs/heads/main.zip';
// ---------------------

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$pass = $input['password'] ?? '';

if ($pass !== $adminPassword) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($action === 'update') {
    // Try 'main' first, then 'master'
    $branches = ['main', 'master'];
    $zipFile = 'update_temp.zip';
    $downloadSuccess = false;
    $UsedBranch = '';

    foreach ($branches as $branch) {
        $url = "https://github.com/oguzgokyar/Vitrin/archive/refs/heads/$branch.zip";
        
        $fp = fopen($zipFile, 'w+');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_FILE, $fp); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Vitrin-Auto-Updater');
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($httpCode === 200) {
            $downloadSuccess = true;
            $UsedBranch = $branch;
            break; // Success!
        }
    }

    if (!$downloadSuccess) {
        unlink($zipFile);
        echo json_encode(['error' => 'Failed to download update from GitHub (Checked branches: main, master). Last Code: ' . $httpCode]);
        exit;
    }

    // 2. Unzip
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        $extractPath = './temp_update_folder/';
        $zip->extractTo($extractPath);
        $zip->close();
        
        // 3. Move files
        // GitHub zips extract to 'RepoName-BranchName'
        $sourceDir = $extractPath . 'Vitrin-' . $UsedBranch; // Predictable path logic
        
        // Fallback if prediction fails (e.g. repo name case difference)
        if (!is_dir($sourceDir)) {
            $subDirs = glob($extractPath . '/*', GLOB_ONLYDIR);
            if (count($subDirs) > 0) {
                $sourceDir = $subDirs[0];
            }
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $relativePath = substr($file->getPathname(), strlen($sourceDir) + 1);
            $targetPath = './' . $relativePath;

            // Skip data.json
            if ($relativePath === 'data.json') {
                continue;
            }

            if ($file->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath);
                }
            } else {
                copy($file->getPathname(), $targetPath);
            }
        }

        // 4. Cleanup
        unlink($zipFile);
        
        // Recursive delete temp folder
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($extractPath);

        echo json_encode(['success' => true, 'message' => 'System updated successfully.']);
    } else {
        echo json_encode(['error' => 'Failed to unzip update package.']);
    }
} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>
