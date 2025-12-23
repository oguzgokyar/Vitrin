<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$dataFile = 'data.json';
$adminPassword = 'admin123';

// Get request body
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'save_all') {
    // Admin Only
    if (($input['password'] ?? '') !== $adminPassword) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $data = $input['data'] ?? [];
    if (file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to write file']);
    }

} elseif ($action === 'vote') {
    // Public Access
    $id = $input['id'] ?? null;
    $score = $input['score'] ?? 0;

    if (!$id || !$score) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing parameters']);
        exit;
    }

    $currentData = json_decode(file_get_contents($dataFile), true) ?? [];
    $found = false;

    foreach ($currentData as &$app) {
        if ($app['id'] == $id) {
            $app['rating'] = $app['rating'] ?? 0;
            $app['votes'] = $app['votes'] ?? 0;

            // Calculate new average
            $totalScore = ($app['rating'] * $app['votes']) + $score;
            $app['votes']++;
            $app['rating'] = $totalScore / $app['votes'];
            
            $found = true;
            break;
        }
    }

    if ($found) {
        if (file_put_contents($dataFile, json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save vote']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'App not found']);
    }

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
