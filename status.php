<?php
session_start();

define('AUTH_KEY', 'DominoHAOQIANGA'); // 定义鉴权密钥
define('STATUS_FILE', 'status_log.txt'); // 定义状态日志文件

header('Content-Type: application/json; charset=utf-8'); // 设置HTTP响应头

// 函数用于验证请求中的密钥
function isValidKey($key) {
    return $key === AUTH_KEY;
}

// 函数用于读取状态记录
function readStatusLog() {
    $history = [];
    if (file_exists(STATUS_FILE)) {
        $lines = file(STATUS_FILE, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            list($status, $time) = explode(',', $line);
            $history[] = ['status' => $status, 'time' => (int)$time];
        }
    }
    return $history;
}

// 函数用于写入状态记录
function writeStatusLog($status) {
    $current_time = time();
    $log_entry = $status . ',' . $current_time . PHP_EOL;

    // 读取现有的日志内容
    $entries = file(STATUS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // 添加新条目到数组的末尾
    $entries[] = rtrim($log_entry);

    // 保留最后的10条记录
    if (count($entries) > 10) {
        $entries = array_slice($entries, -10);
    }

    // 将最新的10条记录写回文件
    file_put_contents(STATUS_FILE, implode(PHP_EOL, $entries) . PHP_EOL);
}

// 函数用于计算睡眠质量和精神状态
function calculateSleepQuality($history) {
    $recentHistory = array_slice($history, -10);
    $sleepTime = 0;
    $awakeTime = 0;
    $currentTime = time();
    $firstEntryTime = $recentHistory[0]['time'];

    for ($i = 1; $i < count($recentHistory); $i++) {
        $currentEntry = $recentHistory[$i];
        $previousEntry = $recentHistory[$i - 1];

        if ($currentEntry['status'] === '醒着' && $previousEntry['status'] === '睡着') {
            $sleepTime += $currentEntry['time'] - $previousEntry['time'];
        } elseif ($currentEntry['status'] === '睡着' && $previousEntry['status'] === '醒着') {
            $awakeTime += $currentEntry['time'] - $previousEntry['time'];
        }
    }

    // 包含最新状态到当前时间的距离
    $lastEntry = end($recentHistory);
    if ($lastEntry['status'] === '睡着') {
        $sleepTime += $currentTime - $lastEntry['time'];
    } elseif ($lastEntry['status'] === '醒着') {
        $awakeTime += $currentTime - $lastEntry['time'];
    }

    $totalTime = $currentTime - $firstEntryTime;
    $sleepQuality = ($totalTime > 0) ? ($sleepTime / $totalTime) * 100 : 0;

    // 判断精神状态
    if ($sleepQuality >= 95) {
        $mentalState = '睡死了';
    } elseif ($sleepQuality >= 70) {
        $mentalState = '猪儿虫';
    } elseif ($sleepQuality >= 60) {
        $mentalState = '睡饱饱';
    } elseif ($sleepQuality >= 45) {
        $mentalState = '很健康';
    } elseif ($sleepQuality >= 30) {
        $mentalState = '没睡够';
    } elseif ($sleepQuality >= 10) {
        $mentalState = '熬大夜';
    } else {
        $mentalState = '猝死边缘';
    }

    return [
        'sleep_quality' => round($sleepQuality, 2),
        'mental_state' => $mentalState,
        'sleep_time' => $sleepTime,
        'awake_time' => $awakeTime,
        'total_time' => $totalTime
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status']) && isset($_POST['key'])) {
    if (isValidKey($_POST['key'])) {
        $status = $_POST['status'];
        $history = readStatusLog();
        $lastStatus = end($history)['status'];

        // 检查新状态是否与之前的状态相同
        if ($status === $lastStatus) {
            echo json_encode(['message' => '状态未变化，无需更新'], JSON_UNESCAPED_UNICODE);
        } else {
            // 写入新的状态和时间戳
            writeStatusLog($status);

            $_SESSION['status'] = $status;
            $_SESSION['status_time'] = time();

            echo json_encode([
                'message' => '状态更新成功',
                'status' => $status,
                'time' => date('Y-m-d H:i:s', $_SESSION['status_time'])
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(['message' => '密钥无效'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['status']) && isset($_GET['key'])) {
    if (isValidKey($_GET['key'])) {
        $status = $_GET['status'];
        $history = readStatusLog();
        $lastStatus = end($history)['status'];

        // 检查新状态是否与之前的状态相同
        if ($status === $lastStatus) {
            echo json_encode(['message' => '状态未变化，无需更新'], JSON_UNESCAPED_UNICODE);
        } else {
            // 写入新的状态和时间戳
            writeStatusLog($status);

            $_SESSION['status'] = $status;
            $_SESSION['status_time'] = time();

            echo json_encode([
                'message' => '状态更新成功',
                'status' => $status,
                'time' => date('Y-m-d H:i:s', $_SESSION['status_time'])
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(['message' => '密钥无效'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$status = isset($_SESSION['status']) ? $_SESSION['status'] : '未知';
$status_time = isset($_SESSION['status_time']) ? $_SESSION['status_time'] : time();
$history = readStatusLog();
$sleepQuality = calculateSleepQuality($history);

echo json_encode([
    'status' => $status,
    'status_time' => $status_time,
    'history' => $history,
    'sleep_quality' => $sleepQuality['sleep_quality'],
    'mental_state' => $sleepQuality['mental_state'],
    'sleep_time' => $sleepQuality['sleep_time'],
    'awake_time' => $sleepQuality['awake_time'],
    'total_time' => $sleepQuality['total_time'],
    'recent_activity' => '我最近清醒了 ' . $sleepQuality['awake_time'] . ' 秒，睡了 ' . $sleepQuality['sleep_time'] . ' 秒'
], JSON_UNESCAPED_UNICODE);
?>
