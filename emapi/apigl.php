<?php
session_start();

// --- 配置 ---
$config_file = __DIR__ . '/config.json';
$admin_username = 'aiyunwl'; // 登录账号
$admin_password = '24844365qq'; // 登录密码

// --- 退出登录逻辑 ---
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// --- 检查登录状态 ---
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_username'], $_POST['login_password'])) {
        // 验证账号和密码
        if (hash_equals($admin_username, $_POST['login_username']) && hash_equals($admin_password, $_POST['login_password'])) {
            $_SESSION['logged_in'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $login_error = '账号或密码错误';
        }
    }
    
    // 显示登录页面
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>EMShop信息管理 - 登录</title>
        <meta charset="utf-8">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
            .login-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; max-width: 400px; }
            .login-title { text-align: center; margin-bottom: 1.5rem; color: #333; }
            .form-group { margin-bottom: 1rem; }
            .form-group label { display: block; margin-bottom: 0.5rem; color: #555; font-weight: 500; }
            .form-group input { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; transition: border-color 0.3s; }
            .form-group input:focus { outline: none; border-color: #667eea; }
            .btn { width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 5px; font-size: 1rem; cursor: pointer; transition: opacity 0.3s; }
            .btn:hover { opacity: 0.9; }
            .error { color: #e74c3c; text-align: center; margin-top: 1rem; }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2 class="login-title">EMShop信息管理</h2>
            <form method="post">
                <div class="form-group">
                    <label for="login_username">管理员账号</label>
                    <input type="text" id="login_username" name="login_username" required>
                </div>
                <div class="form-group">
                    <label for="login_password">管理员密码</label>
                    <input type="password" id="login_password" name="login_password" required>
                </div>
                <button type="submit" class="btn">登录</button>
                <?php if (isset($login_error)): ?>
                    <div class="error"><?php echo htmlspecialchars($login_error); ?></div>
                <?php endif; ?>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- 用户已登录，开始处理和显示页面 ---

// --- 读取并标准化配置文件 ---
function readAndStandardizeConfig($file_path) {
    $raw_data = file_get_contents($file_path);
    if ($raw_data === false) {
        die("无法读取配置文件: $file_path");
    }
    
    $decoded = json_decode($raw_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("配置文件JSON格式错误: " . json_last_error_msg());
    }

    // 标准化结构，确保存在必需的键
    $config = [
        'agent' => [
            'service_qq' => $decoded['agent']['service_qq'] ?? '',
            'qq_group' => $decoded['agent']['qq_group'] ?? '',
            'tg_group_url' => $decoded['agent']['tg_group_url'] ?? '',
            'buy_url' => [], // 移除功能，设为空数组
            'download_url' => [], // 移除功能，设为空数组
        ],
        'notice' => $decoded['notice'] ?? [],
        'ad' => $decoded['ad'] ?? [],
    ];

    return $config;
}

$config = readAndStandardizeConfig($config_file);

// --- 处理表单提交 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['logout'])) {
    $new_config = $config; // 继承旧配置，只更新提交的部分

    // 更新 agent 信息
    $new_config['agent']['service_qq'] = trim($_POST['service_qq'] ?? '');
    $new_config['agent']['qq_group'] = trim($_POST['qq_group'] ?? '');
    $new_config['agent']['tg_group_url'] = trim($_POST['tg_group_url'] ?? '');

    // 更新 notice (已修改为按天计算)
    $notices = [];
    if (isset($_POST['notice_content']) && is_array($_POST['notice_content'])) {
        foreach ($_POST['notice_content'] as $index => $content) {
            if (!empty(trim($content))) {
                $expire_days = intval($_POST['notice_expire_time'][$index] ?? 0);
                $expire_timestamp = $_POST['notice_expire'][$index] === 'never' ? 0 : (time() + ($expire_days * 86400)); // 天数转为秒
                
                $notices[] = [
                    'id' => intval($_POST['notice_id'][$index]),
                    'content' => trim($content),
                    'link_url' => trim($_POST['notice_link'][$index] ?? ''),
                    'expire_time' => $expire_timestamp
                ];
            }
        }
    }
    $new_config['notice'] = $notices;

    // 更新 ad (已修改为按天计算)
    $ads = [];
    if (isset($_POST['ad_content']) && is_array($_POST['ad_content'])) {
        foreach ($_POST['ad_content'] as $index => $content) {
            if (!empty(trim($content))) {
                $expire_days = intval($_POST['ad_expire_time'][$index] ?? 0);
                $expire_timestamp = $_POST['ad_expire'][$index] === 'never' ? 0 : (time() + ($expire_days * 86400)); // 天数转为秒
                
                $ads[] = [
                    'id' => intval($_POST['ad_id'][$index]),
                    'admin_id' => 5, // 保持不变
                    'content' => trim($content),
                    'link_url' => trim($_POST['ad_link'][$index] ?? ''),
                    'expire_time' => $expire_timestamp
                ];
            }
        }
    }
    $new_config['ad'] = $ads;

    // 将新配置写回文件
    $write_result = file_put_contents($config_file, json_encode($new_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($write_result !== false) {
        $success_message = '配置已成功保存！';
        $config = $new_config; // 更新当前页使用的 $config 变量
    } else {
        $error_message = '保存失败，请检查文件权限。';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>EMShop信息管理</title>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        .logout-btn { background: rgba(255,255,255,0.2); color: white; padding: 0.5rem 1rem; border: none; border-radius: 5px; cursor: pointer; }
        .logout-btn:hover { background: rgba(255,255,255,0.3); }
        .main-content { padding: 2rem; }
        .section { margin-bottom: 2rem; }
        .section-title { font-size: 1.2rem; font-weight: 600; color: #333; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea; }
        .card { background: #fff; border: 1px solid #e1e5e9; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .form-row { display: flex; gap: 1rem; margin-bottom: 1rem; align-items: end; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #555; font-weight: 500; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9rem; transition: border-color 0.3s; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #667eea; }
        .btn { padding: 0.5rem 1rem; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #5a6fd8; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219a52; }
        .item-actions { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
        .message { background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border: 1px solid #c3e6cb; }
        .error-message { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border: 1px solid #f5c6cb; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; }
        @media (max-width: 768px) {
            .form-row { flex-direction: column; }
            .header { flex-direction: column; gap: 1rem; text-align: center; }
            .form-group { min-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>EMShop信息管理</h1>
            <form method="post" style="display: inline;">
                <input type="hidden" name="logout" value="1">
                <button type="submit" class="logout-btn" onclick="return confirm('确定要退出登录吗？');">退出登录</button>
            </form>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="main-content">
            <form method="post">
                
                <!-- 联系信息 -->
                <div class="section">
                    <h3 class="section-title">联系信息</h3>
                    <div class="card">
                        <div class="grid">
                            <div class="form-group">
                                <label>客服QQ</label>
                                <input type="text" name="service_qq" value="<?php echo htmlspecialchars($config['agent']['service_qq'] ?? ''); ?>" placeholder="请输入QQ号码">
                            </div>
                            <div class="form-group">
                                <label>QQ群</label>
                                <input type="text" name="qq_group" value="<?php echo htmlspecialchars($config['agent']['qq_group'] ?? ''); ?>" placeholder="请输入QQ群号">
                            </div>
                            <div class="form-group">
                                <label>TG群链接</label>
                                <input type="text" name="tg_group_url" value="<?php echo htmlspecialchars($config['agent']['tg_group_url'] ?? ''); ?>" placeholder="请输入TG群链接">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 官方公告 -->
                <div class="section">
                    <h3 class="section-title">官方公告</h3>
                    <div id="notices_container">
                        <?php foreach ($config['notice'] ?? [] as $index => $item): ?>
                        <div class="card item">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>ID (不可修改)</label>
                                    <input type="text" readonly value="<?php echo $item['id']; ?>" style="background: #f8f9fa; cursor: not-allowed;">
                                    <input type="hidden" name="notice_id[]" value="<?php echo $item['id']; ?>">
                                </div>
                                <div class="form-group">
                                    <label>内容</label>
                                    <textarea name="notice_content[]" rows="2" placeholder="请输入公告内容"><?php echo htmlspecialchars($item['content']); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>链接</label>
                                    <input type="text" name="notice_link[]" value="<?php echo htmlspecialchars($item['link_url']); ?>" placeholder="例如：https://example.com">
                                </div>
                                <div class="form-group">
                                    <label>过期时间</label>
                                    <select name="notice_expire[]" onchange="toggleExpireTime(this)">
                                        <option value="never" <?php echo $item['expire_time'] == 0 ? 'selected' : ''; ?>>永不过期</option>
                                        <option value="custom" <?php echo $item['expire_time'] != 0 ? 'selected' : ''; ?>>自定义天数</option>
                                    </select>
                                    <input type="number" name="notice_expire_time[]" value="<?php echo $item['expire_time'] != 0 ? round(($item['expire_time'] - time()) / 86400) : 0; ?>" 
                                           style="display:<?php echo $item['expire_time'] == 0 ? 'none' : 'block'; ?>; margin-top: 0.5rem;" 
                                           placeholder="天数">
                                </div>
                                <div class="item-actions">
                                    <button type="button" onclick="removeItem(this)" class="btn btn-danger">删除</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="addItem('notices_container', 'notice')" class="btn btn-success">+ 添加公告</button>
                </div>

                <!-- 推荐服务 -->
                <div class="section">
                    <h3 class="section-title">推荐服务</h3>
                    <div id="ads_container">
                        <?php foreach ($config['ad'] ?? [] as $index => $item): ?>
                        <div class="card item">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>ID (不可修改)</label>
                                    <input type="text" readonly value="<?php echo $item['id']; ?>" style="background: #f8f9fa; cursor: not-allowed;">
                                    <input type="hidden" name="ad_id[]" value="<?php echo $item['id']; ?>">
                                </div>
                                <div class="form-group">
                                    <label>内容</label>
                                    <textarea name="ad_content[]" rows="2" placeholder="请输入广告内容"><?php echo htmlspecialchars($item['content']); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>链接</label>
                                    <input type="text" name="ad_link[]" value="<?php echo htmlspecialchars($item['link_url']); ?>" placeholder="例如：https://example.com">
                                </div>
                                <div class="form-group">
                                    <label>过期时间</label>
                                    <select name="ad_expire[]" onchange="toggleExpireTime(this)">
                                        <option value="never" <?php echo $item['expire_time'] == 0 ? 'selected' : ''; ?>>永不过期</option>
                                        <option value="custom" <?php echo $item['expire_time'] != 0 ? 'selected' : ''; ?>>自定义天数</option>
                                    </select>
                                    <input type="number" name="ad_expire_time[]" value="<?php echo $item['expire_time'] != 0 ? round(($item['expire_time'] - time()) / 86400) : 0; ?>" 
                                           style="display:<?php echo $item['expire_time'] == 0 ? 'none' : 'block'; ?>; margin-top: 0.5rem;" 
                                           placeholder="天数">
                                </div>
                                <div class="item-actions">
                                    <button type="button" onclick="removeItem(this)" class="btn btn-danger">删除</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="addItem('ads_container', 'ad')" class="btn btn-success">+ 添加广告</button>
                </div>

                <button type="submit" class="btn" style="width: 100%; margin-top: 2rem; padding: 1rem; font-size: 1.1rem;">保存所有配置</button>
            </form>
        </div>
    </div>

    <script>
    function removeItem(button) {
        if (confirm('确定要删除此项吗？')) {
            button.closest('.item').remove();
        }
    }

    function toggleExpireTime(select) {
        const timeInput = select.nextElementSibling;
        timeInput.style.display = select.value === 'custom' ? 'block' : 'none';
    }

    function addItem(containerId, type) {
        const container = document.getElementById(containerId);
        const index = Date.now();
        const itemDiv = document.createElement('div');
        itemDiv.className = 'card item';
        
        let html = '';
        if (type === 'notice') {
            html = `
                <div class="form-row">
                    <div class="form-group">
                        <label>ID (自动生成)</label>
                        <input type="text" readonly value="${index}" style="background: #f8f9fa; cursor: not-allowed;">
                        <input type="hidden" name="notice_id[]" value="${index}">
                    </div>
                    <div class="form-group">
                        <label>内容</label>
                        <textarea name="notice_content[]" rows="2" placeholder="请输入公告内容"></textarea>
                    </div>
                    <div class="form-group">
                        <label>链接</label>
                        <input type="text" name="notice_link[]" placeholder="例如：https://example.com">
                    </div>
                    <div class="form-group">
                        <label>过期时间</label>
                        <select name="notice_expire[]" onchange="toggleExpireTime(this)">
                            <option value="never">永不过期</option>
                            <option value="custom">自定义天数</option>
                        </select>
                        <input type="number" name="notice_expire_time[]" value="0" 
                               style="display:none; margin-top: 0.5rem;" 
                               placeholder="天数">
                    </div>
                    <div class="item-actions">
                        <button type="button" onclick="removeItem(this)" class="btn btn-danger">删除</button>
                    </div>
                </div>
            `;
        } else if (type === 'ad') {
            html = `
                <div class="form-row">
                    <div class="form-group">
                        <label>ID (自动生成)</label>
                        <input type="text" readonly value="${index}" style="background: #f8f9fa; cursor: not-allowed;">
                        <input type="hidden" name="ad_id[]" value="${index}">
                    </div>
                    <div class="form-group">
                        <label>内容</label>
                        <textarea name="ad_content[]" rows="2" placeholder="请输入广告内容"></textarea>
                    </div>
                    <div class="form-group">
                        <label>链接</label>
                        <input type="text" name="ad_link[]" placeholder="例如：https://example.com">
                    </div>
                    <div class="form-group">
                        <label>过期时间</label>
                        <select name="ad_expire[]" onchange="toggleExpireTime(this)">
                            <option value="never">永不过期</option>
                            <option value="custom">自定义天数</option>
                        </select>
                        <input type="number" name="ad_expire_time[]" value="0" 
                               style="display:none; margin-top: 0.5rem;" 
                               placeholder="天数">
                    </div>
                    <div class="item-actions">
                        <button type="button" onclick="removeItem(this)" class="btn btn-danger">删除</button>
                    </div>
                </div>
            `;
        }
        
        itemDiv.innerHTML = html;
        container.appendChild(itemDiv);
    }
    </script>
</body>
</html>