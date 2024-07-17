<?php
session_start();

define('AUTH_KEY'， 'YOURSECRETKEY'); // 定义鉴权密钥
define('STATUS_FILE'， 'status_log.txt'); // 定义状态日志文件
define('POOP_FILE'， 'poop_log.txt'); // 定义拉屎日志文件

header('Content-Type: application/json; charset=utf-8'); // 设置 HTTP 响应头

// 函数用于验证请求中的密钥
function isValidKey($key) {
    return hash_equals(AUTH_KEY, $key);
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

    $fp = fopen(STATUS_FILE, 'c+');
    if (flock($fp, LOCK_EX)) {
        $entries = file(STATUS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $entries[] = rtrim($log_entry);

        if (count($entries) > 10) {
            $entries = array_slice($entries, -10);
        }

        file_put_contents(STATUS_FILE, implode(PHP_EOL, $entries) . PHP_EOL);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

function calculateSleepQuality($history) {
    $currentTime = time(); // 获取当前时间戳
    $twentyFourHoursAgo = $currentTime - 259200; // 过去24小时的时间戳

    // 筛选出最近24小时内的记录
    $recentHistory = array_filter($history, function($entry) use ($twentyFourHoursAgo) {
        return $entry['time'] >= $twentyFourHoursAgo;
    });

    // 如果没有记录，返回初始值
    if (empty($recentHistory)) {
        return [
            'sleep_quality' => 0,
            'mental_state' => '未知',
            'sleep_time' => 0,
            'awake_time' => 0,
            'total_time' => 259200 // 固定为48小时
        ];
    }

    $sleepTime = 0;
    $awakeTime = 0;

    // 确定24小时内最早的一个时间戳
    $recentHistory = array_values($recentHistory); // 确保数组键从0开始
    $firstEntry = $recentHistory[0];
    $previousTime = max($twentyFourHoursAgo, $firstEntry['time']);
    // 计算从24小时前到第一个记录时间的状态时间
    if ($firstEntry['status'] === '醒着') {
        // 如果最早的记录是"醒着"，则24小时前到第一个记录之间为"睡着"
        $sleepTime += $firstEntry['time'] - $twentyFourHoursAgo;
    } else {
        // 如果最早的记录是"睡着"，则24小时前到第一个记录之间为"醒着"
        $awakeTime += $firstEntry['time'] - $twentyFourHoursAgo;
    }

    $previousEntry = $firstEntry;

    // 遍历计算每个状态的持续时间
    foreach ($recentHistory as $i => $entry) {
        if ($i === 0) continue; // 跳过第一个条目

        // 确保时间戳顺序正确
        if ($entry['time'] >= $previousEntry['time']) {
            if ($previousEntry['status'] === '睡着') {
                $sleepTime += $entry['time'] - $previousEntry['time'];
            } else {
                $awakeTime += $entry['time'] - $previousEntry['time'];
            }

            $previousEntry = $entry;
        }
    }

    // 包含最新状态到当前时间的距离
    $lastEntry = end($recentHistory);
    if ($lastEntry['status'] === '睡着') {
        $sleepTime += $currentTime - $lastEntry['time'];
    } else {
        $awakeTime += $currentTime - $lastEntry['time'];
    }

    $totalTime = $currentTime - $twentyFourHoursAgo;
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



function handleStatusUpdate($status) {
    $history = readStatusLog();
    $lastStatus = end($history)['status'];

    if ($status === $lastStatus) {
        return ['message' => '状态未变化，无需更新'];
    } else {
        writeStatusLog($status);
        $_SESSION['status'] = $status;
        $_SESSION['status_time'] = time();

        return [
            'message' => '状态更新成功',
            'status' => $status,
            'time' => date('Y-m-d H:i:s', $_SESSION['status_time'])
        ];
    }
}

function readPoopLog() {
    $poopHistory = [];
    if (file_exists(POOP_FILE)) {
        $lines = file(POOP_FILE, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            $poopHistory[] = (int)$line;
        }
    }
    return $poopHistory;
}

function writePoopLog() {
    $current_time = time();
    $log_entry = $current_time . PHP_EOL;

    $fp = fopen(POOP_FILE, 'c+');
    if (flock($fp, LOCK_EX)) {
        $entries = file(POOP_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $entries[] = rtrim($log_entry);

        if (count($entries) > 10) { // 限制日志条目数
            $entries = array_slice($entries, -10);
        }

        file_put_contents(POOP_FILE, implode(PHP_EOL, $entries) . PHP_EOL);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

function calculatePoopCount($poopHistory) {
    $currentTime = time();
    $twentyFourHoursAgo = $currentTime - 86400; // 过去24小时的时间戳

    $recentPoops = array_filter($poopHistory, function($entry) use ($twentyFourHoursAgo) {
        return $entry >= $twentyFourHoursAgo;
    });

    return count($recentPoops);
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['key'])) {
    if (isValidKey($_POST['key'])) {
        if ($_POST['action'] === 'updateStatus' && isset($_POST['status'])) {
            $response = handleStatusUpdate($_POST['status']);
        } elseif ($_POST['action'] === 'recordPoop') {
            writePoopLog();
            $response = ['message' => '拉屎记录成功'];
        } else {
            $response = ['message' => '无效的操作'];
        }
    } else {
        $response = ['message' => '密钥无效'];
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && isset($_GET['key'])) {
    if (isValidKey($_GET['key'])) {
        if ($_GET['action'] === 'updateStatus' && isset($_GET['status'])) {
            $response = handleStatusUpdate($_GET['status']);
        } elseif ($_GET['action'] === 'recordPoop') {
            writePoopLog();
            $response = ['message' => '拉屎记录成功'];
        } else {
            $response = ['message' => '无效的操作'];
        }
    } else {
        $response = ['message' => '密钥无效'];
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}


$status = '未知';
$status_time = time();
$history = readStatusLog();
$poopHistory = readPoopLog();

if (!empty($history)) {
    $lastEntry = end($history);
    $status = $lastEntry['status'];
    $status_time = $lastEntry['time'];
}
$sleepQuality = calculateSleepQuality($history);
$poopCount = calculatePoopCount($poopHistory);

echo json_encode([
    'status' => $status,
    'status_time' => $status_time,
    'history' => $history,
    'sleep_time' => $sleepQuality['sleep_time'],
    'awake_time' => $sleepQuality['awake_time'],
    'total_time' => $sleepQuality['total_time'],
    'sleep_quality' => $sleepQuality['sleep_quality'],
    'mental_state' => $sleepQuality['mental_state'],
    'poop_count' => $poopCount // 返回拉屎次数
], JSON_UNESCAPED_UNICODE);


?>
