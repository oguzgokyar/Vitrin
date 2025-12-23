// --- Configuration ---
require_once 'config.php';
// $adminPassword is here
$repoUser = 'oguzgokyar';
$repoName = 'Vitrin';
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
    // 0. Fetch Git Commit Info (Version)
    $commitApiUrl = "https://api.github.com/repos/$repoUser/$repoName/commits/HEAD";
    $ch = curl_init($commitApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Vitrin-Auto-Updater');
    $commitResponse = curl_exec($ch);
    $commitHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $versionInfo = ['hash' => 'unknown', 'date' => date('Y-m-d')];
    if ($commitHttpCode === 200) {
        $commitData = json_decode($commitResponse, true);
        $shortHash = substr($commitData['sha'], 0, 7);
        $commitDate = date('Y-m-d H:i', strtotime($commitData['commit']['committer']['date']));
        $versionInfo = ['hash' => $shortHash, 'date' => $commitDate];
        
        // Save version.json
        file_put_contents('version.json', json_encode($versionInfo));
    }

    // 1. Download ZIP
    // Try 'main' first, then 'master'
    $branches = ['main', 'master'];
    $zipFile = 'update_temp.zip';
    $downloadSuccess = false;
    $UsedBranch = '';

    foreach ($branches as $branch) {
        $url = "https://github.com/$repoUser/$repoName/archive/refs/heads/$branch.zip";
        
        $fp = fopen($zipFile, 'w+');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
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
        if (file_exists($zipFile)) unlink($zipFile);
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
        $sourceDir = $extractPath . "$repoName-$UsedBranch"; // Predictable path logic
        
        // Fallback if prediction fails
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

            // Skip protected files
            if ($relativePath === 'data.json' || $relativePath === 'config.php' || $relativePath === 'version.json') {
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

        echo json_encode(['success' => true, 'message' => 'System updated successfully.', 'version' => $versionInfo]);
    } else {
        echo json_encode(['error' => 'Failed to unzip update package.']);
    }
} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>
