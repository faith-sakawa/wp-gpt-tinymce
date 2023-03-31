<?php
/*
Plugin Name: gpt-tinymce
Description: TinyMCEにGPT機能を追加するプラグイン
Author: Your Name
Version: 1.0
*/

// TinyMCEにカスタムボタンを追加する関数
add_action('init', 'my_tinymce_button');

function my_tinymce_button() {
   if (current_user_can('edit_posts') && current_user_can('edit_pages')) {
       add_filter('mce_buttons', 'register_my_tinymce_button');
       add_filter('mce_external_plugins', 'add_my_tinymce_button');
   }
}

function register_my_tinymce_button($buttons) {
    $num_buttons = 5;
    for ($i = 1; $i <= $num_buttons; $i++) {
        array_push($buttons, "my_tinymce_button{$i}");
    }
    return $buttons;
}

function add_my_tinymce_button($plugin_array) {
    $plugin_array['my_tinymce_button'] = plugin_dir_url(__FILE__) . 'gpt-tinymce.js';
    return $plugin_array;
}

// 管理画面にメニューを追加し、APIキーを設定・保存する機能を追加する関数
add_action('admin_menu', 'gpt_tinymce_add_admin_menu');
add_action('admin_init', 'gpt_tinymce_settings_init');

function gpt_tinymce_add_admin_menu() {
    add_options_page(
        'GPT TinyMCE Settings',
        'GPT TinyMCE',
        'manage_options',
        'gpt-tinymce',
        'gpt_tinymce_settings_page'
    );
}

function gpt_tinymce_settings_page() {
    ?>
    <div class="wrap">
        <h1>GPT TinyMCE Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('gpt_tinymce_settings');
            do_settings_sections('gpt_tinymce_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function gpt_tinymce_settings_init() {
    register_setting('gpt_tinymce_settings', 'gpt_tinymce_api_key');

    add_settings_section(
        'gpt_tinymce_settings_section',
        'API Key Settings',
        'gpt_tinymce_settings_section_callback',
        'gpt_tinymce_settings'
    );

    add_settings_field(
        'gpt_tinymce_api_key',
        'API Key',
        'gpt_tinymce_api_key_render',
        'gpt_tinymce_settings',
        'gpt_tinymce_settings_section'
    );
}

function gpt_tinymce_api_key_render() {
    $api_key = get_option('gpt_tinymce_api_key');
    ?>
    <input type="text" name="gpt_tinymce_api_key" value="<?php echo esc_attr($api_key); ?>" size="40">
    <?php
}

function gpt_tinymce_settings_section_callback() {
    echo 'Enter your GPT API key below:';
}

// GPT APIを呼び出す関数
function gpt_tiny_api() {
    // POSTリクエストから選択されたテキストを取得
    $get_message = $_POST['get_message'];
    // リクエスト用のデータを作成
    $message = ["role"=>"user", "content"=> $get_message];
    $api_key = get_option('gpt_tinymce_api_key');
    $model = "gpt-3.5-turbo";
    $system_content = <<< EOF
あなたはブログのプロフェッショナルライターです。
ブログ用のコンテンツを考えてください。
EOF;
    $system = ["role" => "system", "content" => $system_content];
    $contents = array();
    $contents[] = $system;
    $contents[] = ["role" => $message["role"], "content" => $message["content"]];
    $header = ["Authorization: Bearer ".$api_key,
    "Content-type: application/json"];
    $params = json_encode(
    ["messages" => $contents,
    "model"=> $model]);
    // cURLを使用してGPT APIにリクエストを送信
    $curl = curl_init("https://api.openai.com/v1/chat/completions");
    $options = [CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => $header,
    CURLOPT_POSTFIELDS => $params,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false];
    curl_setopt_array($curl, $options);
    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $data = json_decode($response, true);
    $content = $data['choices'][0]['message']['content'];

// GPT APIからの応答を返す
echo $content;

wp_die();
}

add_action( 'wp_ajax_gpt_tiny_api', 'gpt_tiny_api' );
add_action( 'wp_ajax_nopriv_gpt_tiny_api', 'gpt_tiny_api' );