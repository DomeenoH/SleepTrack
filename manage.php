<?php
session_start();

define('STATUS_FILE', 'status_log.txt');

// 验证是否已登录
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

// 读取状态记录
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

// 写入状态记录
function writeStatusLog($history) {
    usort($history, function ($a, $b) {
        return $a['time'] - $b['time']; // 按时间升序排序
    });
    $lines = [];
    foreach ($history as $entry) {
        $lines[] = $entry['status'] . ',' . $entry['time'];
    }
    file_put_contents(STATUS_FILE, implode(PHP_EOL, $lines) . PHP_EOL);
}

// 删除记录
if (isset($_POST['delete'])) {
    $index = $_POST['delete'];
    $history = readStatusLog();
    if (isset($history[$index])) {
        unset($history[$index]);
        $history = array_values($history); // 重新索引数组
        writeStatusLog($history);
    }
}

// 修改记录
if (isset($_POST['edit'])) {
    $index = $_POST['edit'];
    $newStatus = $_POST['new_status'];
    $newTime = strtotime($_POST['new_time']);
    $history = readStatusLog();
    if (isset($history[$index])) {
        $history[$index] = ['status' => $newStatus, 'time' => $newTime];
        writeStatusLog($history);
    }
}

// 添加记录
if (isset($_POST['add'])) {
    $newStatus = $_POST['new_status'];
    $newTime = strtotime($_POST['new_time']);
    $history = readStatusLog();
    $history[] = ['status' => $newStatus, 'time' => $newTime];
    writeStatusLog($history);
}

$history = readStatusLog();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>管理后台</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
        }

        .container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 900px;
            text-align: center;
        }

        h2, h3 {
            color: #333;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        form {
            display: inline-block;
        }

        input[type="text"], input[type="datetime-local"], button {
            padding: 8px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        button {
            background-color: #4caf50;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #45a049;
        }

        .delete-button {
            background-color: #f44336;
        }

        .delete-button:hover {
            background-color: #e53935;
        }

        .logout-button {
            background-color: #ff9800;
        }

        .logout-button:hover {
            background-color: #fb8c00;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>管理后台</h2>
        <h3>状态记录</h3>
        <table>
            <tr>
                <th>状态</th>
                <th>时间</th>
                <th>操作</th>
            </tr>
            <?php foreach ($history as $index => $entry) : ?>
            <tr>
                <td><?php echo htmlspecialchars($entry['status']); ?></td>
                <td><?php echo date('Y-m-d H:i:s', $entry['time']); ?></td>
                <td>
                    <form method="post" action="manage.php">
                        <input type="hidden" name="delete" value="<?php echo $index; ?>">
                        <button type="submit" class="delete-button">删除</button>
                    </form>
                    <form method="post" action="manage.php">
                        <input type="hidden" name="edit" value="<?php echo $index; ?>">
                        状态: <input type="text" name="new_status" value="<?php echo htmlspecialchars($entry['status']); ?>" required>
                        时间: <input type="datetime-local" name="new_time" value="<?php echo date('Y-m-d\TH:i', $entry['time']); ?>" required>
                        <button type="submit">修改</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h3>添加新记录</h3>
        <form method="post" action="manage.php">
            状态: <input type="text" name="new_status" required>
            时间: <input type="datetime-local" name="new_time" required>
            <button type="submit" name="add">添加</button>
        </form>

        <h3>登出</h3>
        <form method="post" action="logout.php">
            <button type="submit" class="logout-button">登出</button>
        </form>
    </div>
</body>
</html>
