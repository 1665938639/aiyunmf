<?php
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

if ($action === 'admin_index') {
    $config_file = __DIR__ . '/config.json';
    $config = json_decode(file_get_contents($config_file), true);
    
    // 过滤已过期的项目
    $current_time = time();
    $active_notice = array_filter($config['notice'], function($item) use ($current_time) {
        return $item['expire_time'] == 0 || $item['expire_time'] > $current_time;
    });
    
    $active_ad = array_filter($config['ad'], function($item) use ($current_time) {
        return $item['expire_time'] == 0 || $item['expire_time'] > $current_time;
    });
    
    $data = [
        'agent' => $config['agent'],
        'ad' => array_values($active_ad),
        'notice' => array_values($active_notice)
    ];

    exit(json_encode(['code' => 200, 'msg' => '', 'data' => $data], JSON_UNESCAPED_UNICODE));
}

exit(json_encode(['code' => 404, 'msg' => 'Action not found'], JSON_UNESCAPED_UNICODE));