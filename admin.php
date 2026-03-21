<?php

$notify_email = 'bxatikit@gmail.com'; // ← заміни на свій email

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$users = ['pasha' => 'pasha12345'];

if (isset($_POST['login'], $_POST['password'])) {
    if (isset($users[$_POST['login']]) && $users[$_POST['login']] === $_POST['password']) {
        $_SESSION['user'] = $_POST['login'];

        $ip = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip .= ' (Proxy: ' . $_SERVER['HTTP_X_FORWARDED_FOR'] . ')';
        }
        $agent = $_SERVER['HTTP_USER_AGENT'];
        $time = date("Y-m-d H:i:s");

        $message = "🔐 Вхід у адмін-панель b2b:\n\n"
            . "👤 Логін: {$_POST['login']}\n"
            . "🕒 Час: $time\n"
            . "🌍 IP: $ip\n"
            . "🖥️ User-Agent: $agent\n";

        @mail($notify_email, "🔔 Вхід у адмінку b2b [$time]", $message, "From: notifier@{$_SERVER['SERVER_NAME']}");
        // ...вдалий вхід...
    } else {
        $error = "Невірний логін або пароль!";

        $ip = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip .= ' (Proxy: ' . $_SERVER['HTTP_X_FORWARDED_FOR'] . ')';
        }
        $agent = $_SERVER['HTTP_USER_AGENT'];
        $time = date("Y-m-d H:i:s");

        $message = "❌ Невдала спроба входу в адмінку:\n\n"
            . "👤 Спроба з логіном: {$_POST['login']}\n"
            . "🕒 Час: $time\n"
            . "🌍 IP: $ip\n"
            . "🖥️ User-Agent: $agent\n";

        @mail($notify_email, "⚠️ Невдалий вхід [$time]", $message, "From: notifier@{$_SERVER['SERVER_NAME']}");    
        // ...не вдалий вхід...
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$pages_dir = __DIR__; // використовуємо поточну директорію
if (!is_dir($pages_dir)) mkdir($pages_dir);

$files = array_filter(scandir($pages_dir), fn($f) => substr($f, -5) === '.html');

$edit_link = '';
if (isset($_POST['filename'], $_POST['file_content']) && isset($_SESSION['user'])) {
    $filename = basename($_POST['filename']);
    $filepath = "$pages_dir/$filename";

    file_put_contents($filepath, $_POST['file_content']);
    $saved = true;
    $edit_link = "$filename";
}

if (isset($_POST['new_file']) && isset($_SESSION['user'])) {
    $new_file = preg_replace('/[^a-z0-9_\-]/i', '', $_POST['new_file']) . '.html';
    $full_path = "$pages_dir/$new_file";
    if (!file_exists($full_path)) {
        $template = <<<HTML
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$new_file</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 2em;
            background: #f9f9f9;
            color: #333;
        }
        h1 {
            color: #007acc;
        }
    </style>
</head>
<body>
    <h1>Ласкаво просимо!</h1>
    <p>Це нова HTML-сторінка <strong>$new_file</strong>.</p>
</body>
</html>
HTML;

        file_put_contents($full_path, $template);
        header("Location: admin.php?edit=$new_file");
        exit;
    } else {
        $error = "Файл вже існує!";
    }
}

// Включимо логіку для обробки запиту на видалення файлів
if (isset($_GET['delete']) && isset($_SESSION['user'])) {
    $file_to_delete = $_GET['delete'];
    $filepath = "$pages_dir/$file_to_delete";

    if (file_exists($filepath)) {
        unlink($filepath); // Видалення файлу
        header("Location: admin.php"); // Перезавантажуємо сторінку після видалення
        exit;
    } else {
        $error = "Файл не знайдений!";
    }
}

if (isset($_POST['filename'], $_POST['file_content']) && isset($_SESSION['user'])) {
    $filename = basename($_POST['filename']);
    $filepath = "$pages_dir/$filename";
    $new_filename = isset($_POST['new_filename']) ? preg_replace('/[^a-z0-9_\-]/i', '', $_POST['new_filename']) . '.html' : $filename;

    // Перевірка, чи нова назва відрізняється від старої
    if ($new_filename !== $filename) {
        $new_filepath = "$pages_dir/$new_filename";
        // Якщо файл з новим ім'ям вже існує, помилка
        if (!file_exists($new_filepath)) {
            rename($filepath, $new_filepath); // Перейменування файлу
            $filepath = $new_filepath; // Оновити шлях до файлу
            $filename = $new_filename; // Оновити ім'я файлу
        } else {
            $error = "Файл з такою адресою вже існує!";
        }
    }

    // Збереження вмісту файлу
    file_put_contents($filepath, $_POST['file_content']);
    $saved = true;
    $edit_link = "$filename";
}

?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Адмін-панель b2b</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { margin: 0; font-family: sans-serif; background: #f1f5f9; color: #222; }
        .container { max-width: 1000px; margin: 20px auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px #ccc; }
        h2, h3 { color: #007acc; }
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="password"] { width: 98%; padding: 8px; border: 1px solid #ccc; border-radius: 6px; }
        button { background: #007acc; color: white; border: none; padding: 10px 16px; border-radius: 6px; margin-top: 10px; cursor: pointer; }
        button:hover { background: #005fa3; }
        .top { display: flex; justify-content: space-between; flex-wrap: wrap; align-items: center; }
        ul { padding-left: 0; list-style: none; }
        li { margin-bottom: 10px; padding: 10px; border: 1px solid #e0e0e0; border-radius: 6px; background-color: #f9f9f9; display: flex; align-items: center; justify-content: space-between; }
        li:hover { background-color: #f0f4f8; }
        a { color: #007acc; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .success { color: green; margin-top: 10px; }
        .error { color: red; margin-top: 10px; }
        textarea { width: 98%; height: 600px; font-family: monospace; padding: 10px; border: 1px solid #ccc; border-radius: 6px; background: #f9f9f9; margin-top: 10px; }
        .file-buttons { display: flex; gap: 8px; }
        .file-buttons button {
            background: #e0f0ff;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 6px 10px;
            cursor: pointer;
            font-size: 13px;
            min-width: 70px;
            transition: background-color 0.3s ease;
        }
        .file-buttons button.edit { background-color: #4CAF50; color: white; border-color: #3e8e41; }
        .file-buttons button.edit:hover { background-color: #3e8e41; }
        .file-buttons button.view { background-color: #007acc; color: white; border-color: #009acd; }
        .file-buttons button.view:hover { background-color: #009acd; }
        .file-buttons button.delete { background-color: #FF6347; color: white; border-color: #d64b3f; }
        .file-buttons button.delete:hover { background-color: #d64b3f; }
        .file-info {
            font-size: 0.9em;
            color: #555;
            white-space: nowrap;
            margin-left: 20px;
            min-width: 280px;
            text-align: right;
        }
        #toolbar button {
            margin-right: 5px;
            background: #248beb;
            border: 1px solid #007acc;
            border-radius: 4px;
            padding: 6px 10px;
            cursor: pointer;
            font-size: 14px;
        }
        #toolbar button:hover { background: #005fa3; }
        #toolbar { margin-bottom: 10px; }
        @media (max-width: 600px) {
            .top { flex-direction: column; align-items: flex-start; }
            textarea { height: 400px; }
            li { flex-direction: column; align-items: flex-start; }
            .file-info { margin-left: 0; margin-top: 6px; text-align: left; }
            .file-buttons { margin-top: 6px; }

            /* Текст часу створення та редагування */
            .file-info span {
                display: block;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
<div class="container">
<?php if (!isset($_SESSION['user'])): ?>
    <h2>🔐 Вхід</h2>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="post">
        <label>Логін: <input type="text" name="login" required></label>
        <label>Пароль: <input type="password" name="password" required></label>
        <button type="submit">Увійти</button>
    </form>
<?php else: ?>
    <div class="top">
        <h2>👋 Вітаємо, <?=htmlspecialchars($_SESSION['user'])?></h2>
        <a href="?logout=true">🚪 Вийти</a>
    </div>

    <h3>📄 Існуючі сторінки:</h3>
    <ul>
        <?php foreach ($files as $file):
            $file_path = "$pages_dir/$file";
            $file_created = date("Y-m-d H:i:s", filectime($file_path));
            $file_modified = date("Y-m-d H:i:s", filemtime($file_path));
        ?>
            <li>
                <a href="?edit=<?=urlencode($file)?>" style="font-weight: 600;"><?=htmlspecialchars($file)?></a>
                <div class="file-info">
                <span>Створено: <?=date("Y-m-d H:i:s", filectime("$pages_dir/$file"))?></span>
                <span>Останнє редагування: <?=date("Y-m-d H:i:s", filemtime("$pages_dir/$file"))?></span>
                </div>
                <div class="file-buttons">
                    <a href="?edit=<?=urlencode($file)?>"><button class="edit" type="button">Редагувати</button></a>
                    <a href="<?=urlencode($file)?>" target="_blank"><button class="view" type="button">Переглянути</button></a>
                    <a href="?delete=<?=urlencode($file)?>" onclick="return confirm('Ви дійсно хочете видалити <?=htmlspecialchars($file)?>?');">
                    <button class="delete" type="button">Видалити</button>
                    </a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <h3>➕ Створити нову сторінку</h3>
    <form method="post">
        <label>Назва (без .html): <input type="text" name="new_file" required></label>
        <button type="submit">Створити</button>
    </form>
    <hr>

    <?php if (isset($_GET['edit']) && in_array($_GET['edit'], $files)):
        $file_to_edit = $_GET['edit'];
        $content = file_get_contents("$pages_dir/$file_to_edit");
    ?>
        <h3 id="edit-form">✏️ Редагування: <?=htmlspecialchars($file_to_edit)?></h3>
        <?php if (!empty($saved)): ?>
            <p class="success">✅ Збережено. <a href="<?=$edit_link?>" target="_blank">Переглянути</a></p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="filename" value="<?=htmlspecialchars($file_to_edit)?>">

            <!-- Нове поле для редагування адреси
            <label for="new_filename">Нова адреса (без .html):</label>
            <input type="text" name="new_filename" value="<?=htmlspecialchars(pathinfo($file_to_edit, PATHINFO_FILENAME))?>" required> -->


            <label for="file_content">HTML код:</label>
            <div id="toolbar">
                <button type="button" onclick="insertTag('<b>', '</b>')"><b>B</b></button>
                <button type="button" onclick="insertTag('<i>', '</i>')"><i>I</i></button>
                <button type="button" onclick="insertTag('<u>', '</u>')"><u>U</u></button>
                <button type="button" onclick="insertTag('<h1>', '</h1>')">H1</button>
                <button type="button" onclick="insertTag('<h2>', '</h2>')">H2</button>
                <button type="button" onclick="insertTag('<p>', '</p>')">P</button>
                <button type="button" onclick="insertTag('<br>', '')">BR</button>
                <button type="button" onclick="insertTag('<iframe src=\'\'></iframe>', '')">Фрейм</button>
                <button type="button" onclick="insertTag('<a href=\'\'>', '</a>')">Посилання</button>
            </div>

            <textarea name="file_content" id="file_content"><?=htmlspecialchars($content)?></textarea>
            <button type="submit">💾 Зберегти</button>
        </form>

    <?php endif; ?>
<?php endif; ?>
</div>

<script>
function insertTag(openTag, closeTag) {
    const textarea = document.getElementById('file_content');
    const scroll = textarea.scrollTop;
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = textarea.value.substring(start, end);
    const before = textarea.value.substring(0, start);
    const after = textarea.value.substring(end);
    textarea.value = before + openTag + selected + closeTag + after;
    textarea.focus();
    textarea.selectionStart = start + openTag.length;
    textarea.selectionEnd = end + openTag.length;
    textarea.scrollTop = scroll;
}

document.addEventListener("DOMContentLoaded", function() {
    const form = document.querySelector('#edit-form');
    if (form) {
        form.scrollIntoView({ behavior: 'smooth' });
    }
});

</script>

</body>
</html>