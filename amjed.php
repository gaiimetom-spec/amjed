<?php
// ====== الإعدادات ======
$token = "8453831306:AAEcF34R9Ive00hywzVoxlTWcJmqHfxahQs";
$admin_id = 6568145373; // أيدي المالك
$api = "https://api.telegram.org/bot$token/";
$bot_status_file = "bot_status.txt";
$ban_file = "banned.txt";

// ====== ملفات ======
if (!file_exists($bot_status_file)) file_put_contents($bot_status_file, "on");
if (!file_exists($ban_file)) file_put_contents($ban_file, "");

// ====== استقبال التحديث ======
$update = json_decode(file_get_contents("php://input"), true);
if (!$update) exit;

$chat_id = $update['message']['chat']['id'];
$user_id = $update['message']['from']['id'];
$text = trim($update['message']['text'] ?? "");
$name = $update['message']['from']['first_name'];

// ====== دوال ======
function send($id, $msg){
    global $api;
    file_get_contents($api."sendMessage?chat_id=$id&text=".urlencode($msg));
}

// ====== إشعار دخول مستخدم ======
if ($text == "/start") {
    send($admin_id, "🔔 دخل مستخدم جديد\n👤 الاسم: $name\n🆔 ID: $user_id");
}

// ====== تحقق من حالة البوت ======
$bot_status = trim(file_get_contents($bot_status_file));
if ($bot_status == "off" && $user_id != $admin_id) {
    send($chat_id, "⛔ البوت متوقف حاليًا");
    exit;
}

// ====== تحقق من الحظر ======
$banned = file($ban_file, FILE_IGNORE_NEW_LINES);
if (in_array($user_id, $banned)) {
    send($chat_id, "🚫 أنت محظور من استخدام البوت");
    exit;
}

// ====== أوامر المالك ======
if ($user_id == $admin_id) {

    if ($text == "/off") {
        file_put_contents($bot_status_file, "off");
        send($chat_id, "🔴 تم إيقاف البوت");
        exit;
    }

    if ($text == "/on") {
        file_put_contents($bot_status_file, "on");
        send($chat_id, "🟢 تم تشغيل البوت");
        exit;
    }

    if (strpos($text, "/ban") === 0) {
        $id = trim(str_replace("/ban", "", $text));
        file_put_contents($ban_file, $id."\n", FILE_APPEND);
        send($chat_id, "🚫 تم حظر المستخدم $id");
        exit;
    }

    if (strpos($text, "/unban") === 0) {
        $id = trim(str_replace("/unban", "", $text));
        $new = array_diff($banned, [$id]);
        file_put_contents($ban_file, implode("\n", $new));
        send($chat_id, "✅ تم فك الحظر عن $id");
        exit;
    }
}

// ====== رسالة الترحيب ======
if ($text == "/start") {
    send($chat_id,
"مرحبًا بك في بوت 𝑨𝒎𝒋𝒆𝒅 𝑨𝒍𝒌𝒘𝒓𝒚 📧
لمراسلة الشركات والأشخاص عبر الإيميل

📌 أرسل الرسالة بهذا الشكل:
Email: example@email.com
Subject: العنوان
Message: نص الرسالة");
    exit;
}

// ====== إرسال الإيميل ======
if (preg_match("/Email:(.+)\nSubject:(.+)\nMessage:(.+)/s", $text, $m)) {

    $to = trim($m[1]);
    $subject = trim($m[2]);
    $msg = trim($m[3]);

    $headers = "From: bot@yourdomain.com\r\nContent-Type: text/plain; charset=UTF-8";

    if (mail($to, $subject, $msg, $headers)) {
        send($chat_id, "✅ تم إرسال الإيميل بنجاح");
    } else {
        send($chat_id, "❌ فشل الإرسال");
    }
}
?>