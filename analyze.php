<?php
$log_dir = __DIR__ . '/logs';
$current_domain = $_SERVER['HTTP_HOST'] ?? 'koyururi-jinja.com';

if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}

// 管理者除外（?admin=secret）
if (isset($_GET['admin']) && $_GET['admin'] === 'secret') {
    setcookie('admin_exclude', '1', time() + (365 * 86400), "/");
    echo "Admin Excluded";
    exit;
}

if (!isset($_COOKIE['admin_exclude'])) {
    $cookie_name = "v_" . str_replace('.', '_', $current_domain);
    
    if (!isset($_COOKIE[$cookie_name])) {
        $log_file = $log_dir . '/access_' . date('Y-m') . '.log';
        $date = date('Y-m-d H:i:s');
        $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua   = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // --- 流入元（リファラー）の解析 ---
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        $source = '直接アクセス';
        
        if ($ref) {
            $ref_host = parse_url($ref, PHP_URL_HOST);
            
            // 自サイト・関連サイトからの流入
            if (preg_match('/mebirose\.com/i', $ref)) $source = 'mebirose.com';
            elseif (preg_match('/koyururi\.com/i', $ref)) $source = 'koyururi.com';
            elseif (preg_match('/koyururi-jinja\.com/i', $ref)) $source = 'koyururi-jinja.com';
            elseif (preg_match('/rav4j\.com/i', $ref)) $source = 'rav4j.com';
            
            // 検索エンジン
            elseif (preg_match('/google\./i', $ref_host)) $source = 'Google検索';
            elseif (preg_match('/search\.yahoo\.co\.jp/i', $ref_host) || preg_match('/yahoo\.co\.jp/i', $ref_host)) $source = 'Yahoo検索';
            elseif (preg_match('/bing\.com/i', $ref_host)) $source = 'Bing検索';
            
            // SNS
            elseif (preg_match('/(t\.co|twitter\.com|x\.com)/i', $ref_host)) $source = 'X (Twitter)';
            elseif (preg_match('/instagram\.com/i', $ref_host)) $source = 'Instagram';
            elseif (preg_match('/facebook\.com/i', $ref_host)) $source = 'Facebook';
            
            else {
                $source = $ref_host ? $ref_host : 'その他';
            }
        }

        // デバイス判定
        $device = 'PC';
        if (preg_match('/iPhone/i', $ua)) $device = 'iPhone';
        elseif (preg_match('/Android.*Mobile/i', $ua)) $device = 'Android';
        elseif (preg_match('/iPad|Android/i', $ua)) $device = 'Tablet';

        // ブラウザ判定
        $browser = '不明';
        if (preg_match('/Edg/i', $ua)) $browser = 'Edge';
        elseif (preg_match('/Chrome/i', $ua)) $browser = 'Chrome';
        elseif (preg_match('/Safari/i', $ua)) $browser = 'Safari';
        elseif (preg_match('/Firefox/i', $ua)) $browser = 'Firefox';

        // ログ形式: [日時] ドメイン | IP | デバイス | ブラウザ | 流入元 | UA
        $log_entry = sprintf("[%s] %-20s | %s | %-7s | %-7s | %-15s | %s\n", 
            $date, $current_domain, $ip, $device, $browser, $source, $ua);
        
        @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        $expire = strtotime('tomorrow') - 1;
        setcookie($cookie_name, '1', $expire, "/");
    }
}
?>