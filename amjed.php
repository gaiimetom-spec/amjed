<?php
// ====== إعدادات البوت ======
$token = "8453831306:AAEcF34R9Ive00hywzVoxlTWcJmqHfxahQs";
$admin_id = 6568145373; // ضع ايدي حسابك في تيليجرام
$api = "https://api.telegram.org/bot$token/";

// ملفات الحالة
$bot_status_file = "bot_status.txt";
$ban_file = "banned.txt";
$users_file = "users.json";

if (!file_exists($bot_status_file)) file_put_contents($bot_status_file, "on");
if (!file_exists($ban_file)) file_put_contents($ban_file, "");
if (!file_exists($users_file)) file_put_contents($users_file, json_encode([]));

$update = json_decode(file_get_contents("php://input"), true);
if (!$update) exit;

$chat_id = $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'] ?? null;
$user_id = $update['message']['from']['id'] ?? $update['callback_query']['from']['id'] ?? null;
$text = trim($update['message']['text'] ?? '');
$callback_data = $update['callback_query']['data'] ?? '';
$name = $update['message']['from']['first_name'] ?? $update['callback_query']['from']['first_name'] ?? '';

// ===== دوال =====
function send($id, $msg, $buttons = null){
    global $api;
    $data = [
        'chat_id' => $id,
        'text' => $msg,
        'parse_mode' => 'HTML'
    ];
    if($buttons){
        $data['reply_markup'] = json_encode(['inline_keyboard'=>$buttons]);
    }
    $ch = curl_init($GLOBALS['api']."sendMessage");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

// ===== التحقق من حالة البوت =====
$bot_status = trim(file_get_contents($bot_status_file));
$banned = file($ban_file, FILE_IGNORE_NEW_LINES);
if ($bot_status == "off" && $user_id != $admin_id) exit;
if (in_array($user_id, $banned)) exit;

// ===== تسجيل مستخدم جديد =====
$users = json_decode(file_get_contents($users_file), true);
if(!isset($users[$user_id])){
    $users[$user_id] = ['email'=>'','subject'=>'','message'=>''];
    file_put_contents($users_file, json_encode($users));
    send($admin_id, "🔔 دخل مستخدم جديد\n👤 الاسم: $name\n🆔 ID: $user_id");
}

// ===== لوحة تحكم المالك =====
if($user_id == $admin_id){
    if($text == "/off"){ file_put_contents($bot_status_file,"off"); send($chat_id,"🔴 تم إيقاف البوت"); exit; }
    if($text == "/on"){ file_put_contents($bot_status_file,"on"); send($chat_id,"🟢 تم تشغيل البوت"); exit; }
    if(strpos($text,"/ban")===0){ $id=trim(str_replace("/ban","",$text)); file_put_contents($ban_file,$id."\n",FILE_APPEND); send($chat_id,"⛔ تم حظر المستخدم $id"); exit; }
    if(strpos($text,"/unban")===0){ $id=trim(str_replace("/unban","",$text)); $new=array_diff($banned,[$id]); file_put_contents($ban_file,implode("\n",$new)); send($chat_id,"✅ تم فك الحظر عن $id"); exit; }
}

// ===== رسالة الترحيب + أزرار =====
if($text=="/start"){
    $buttons = [
        [['text'=>"📧 تعيين الإيميل",'callback_data'=>"set_email"]],
        [['text'=>"📝 تعيين الموضوع",'callback_data'=>"set_subject"]],
        [['text'=>"✉️ تعيين الرسالة",'callback_data'=>"set_message"]],
        [['text'=>"🚀 إرسال الرسالة",'callback_data'=>"send_email"]]
    ];
    send($chat_id,
"مرحبًا بك في بوت 𝑨𝒎𝒋𝒆𝒅 𝑨𝒍𝒌𝒘𝒓𝒚 📧
لمراسلة الشركات والأشخاص عبر الإيميل
اضغط على الزر المناسب لإدخال البيانات أو لإرسال الرسالة.", $buttons);
    exit;
}

// ===== التعامل مع الأزرار =====
if($callback_data){
    $user = $users[$user_id];
    switch($callback_data){
        case "set_email":
            send($chat_id,"📧 أرسل الآن الإيميل المطلوب:");
            $users[$user_id]['step']="email"; file_put_contents($users_file,json_encode($users));
            break;
        case "set_subject":
            send($chat_id,"📝 أرسل الآن عنوان الموضوع:");
            $users[$user_id]['step']="subject"; file_put_contents($users_file,json_encode($users));
            break;
        case "set_message":
            send($chat_id,"✉️ أرسل الآن نص الرسالة:");
            $users[$user_id]['step']="message"; file_put_contents($users_file,json_encode($users));
            break;
        case "send_email":
            if($user['email'] && $user['subject'] && $user['message']){
                $buttons=[[['text'=>"نعم", 'callback_data'=>"confirm_send"]]];
                send($chat_id,"هل تريد إرسال الرسالة؟", $buttons);
            } else {
                send($chat_id,"❌ يرجى تعيين الإيميل، الموضوع، والرسالة أولاً.");
            }
            break;
        case "confirm_send":
            $to=$user['email']; $sub=$user['subject']; $msg=$user['message'];
            $headers = "From: bot@yourdomain.com\r\nContent-Type: text/plain; charset=UTF-8";
            if(mail($to,$sub,$msg,$headers)){
                send($chat_id,"✅ تم إرسال الرسالة بنجاح");
            } else { send($chat_id,"❌ فشل الإرسال"); }
            break;
    }
    exit;
}

// ===== استقبال البيانات النصية حسب الخطوة =====
if(isset($users[$user_id]['step'])){
    $step = $users[$user_id]['step'];
    $users[$user_id][$step] = $text;
    unset($users[$user_id]['step']);
    file_put_contents($users_file,json_encode($users));
    send($chat_id,"✅ تم تعيين $step بنجاح");
}
?>?>
