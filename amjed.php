<?php
$token = "8453831306:AAEcF34R9Ive00hywzVoxlTWcJmqHfxahQs";
$owner_id = 6568145373; // ايديك
$channel1 = "@YourChannel1";
$channel2 = "@YourChannel2";
$group1 = "@YourGroup";

$api = "https://api.telegram.org/bot$token/";

$update = json_decode(file_get_contents("php://input"), true);
$chat_id = $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'] ?? null;
$user_id = $update['message']['from']['id'] ?? $update['callback_query']['from']['id'] ?? null;
$text = $update['message']['text'] ?? '';
$cb = $update['callback_query']['data'] ?? null;

// إنشاء مجلد البيانات
@mkdir("data");
@mkdir("data/uploads");

// ملفات البوت
$status_file = "data/status.txt";
$users_file = "data/users.json";
$banned_file = "data/banned.txt";

if(!file_exists($status_file)) file_put_contents($status_file,"on");
if(!file_exists($users_file)) file_put_contents($users_file,json_encode([]));
if(!file_exists($banned_file)) file_put_contents($banned_file,"");

$status = trim(file_get_contents($status_file));
$users = json_decode(file_get_contents($users_file), true);
$banned = file($banned_file, FILE_IGNORE_NEW_LINES);

// دالة إرسال
function send($chat_id,$text,$buttons=null){
    global $api;
    $data = ['chat_id'=>$chat_id,'text'=>$text,'parse_mode'=>'HTML'];
    if($buttons) $data['reply_markup'] = json_encode($buttons);
    file_get_contents($api."sendMessage?".http_build_query($data));
}

// تحقق الحظر
if(in_array($user_id,$banned)) exit;

// تحقق تشغيل البوت
if($status=="off" && $user_id != $owner_id) exit;

// تسجيل مستخدم جديد
if(!isset($users[$user_id])){
    $users[$user_id] = ['subscribed'=>false];
    file_put_contents($users_file,json_encode($users));
    send($owner_id,"🔔 مستخدم جديد دخل البوت\n🆔 $user_id");
}

// /start
if($text=="/start"){
    $msg = "✨ أهلاً بك في بوت 𝑨𝒎𝒋𝒆𝒅 𝑨𝒍𝒌𝒘𝒓𝒚 ✨
🤖 بوت PHP عربي لإدارة ورفع الملفات!
💡 يجب الاشتراك أولاً في القنوات والمجموعة:";

    $buttons = [
        'inline_keyboard'=>[
            [['text'=>'🔗 القناة 1','url'=>$channel1]],
            [['text'=>'🔗 القناة 2','url'=>$channel2]],
            [['text'=>'🔗 المجموعة','url'=>$group1]],
            [['text'=>'✅ تحقق','callback_data'=>'check_sub']]
        ]
    ];
    send($chat_id,$msg,$buttons);
    exit;
}

// التحقق عند الضغط
if($cb=="check_sub"){
    $users[$user_id]['subscribed'] = true;
    file_put_contents($users_file,json_encode($users));
    send($chat_id,"✅ تم التحقق من الاشتراك! يمكنك الآن استخدام البوت.");

    // عرض لوحة المستخدم
    send($chat_id,"اختر ما تريد:",[
        'inline_keyboard'=>[
            [['text'=>'📂 رفع ملف PHP','callback_data'=>'upload']],
            [['text'=>'📁 عرض الملفات المرفوعة','callback_data'=>'list_files']]
        ]
    ]);
    exit;
}

// رفع الملفات
if(isset($update['message']['document']) && $users[$user_id]['subscribed']){
    $file_id = $update['message']['document']['file_id'];
    $file_name = $update['message']['document']['file_name'];
    $file_info = file_get_contents($api."getFile?file_id=$file_id");
    $file_info = json_decode($file_info,true);
    $file_path = $file_info['result']['file_path'];
    $content = file_get_contents("https://api.telegram.org/file/bot$token/$file_path");
    file_put_contents("data/uploads/".$file_name,$content);
    send($chat_id,"✅ تم رفع الملف بنجاح: $file_name");
    exit;
}

// لوحة الأدمن
if($user_id==$owner_id && $text=="/admin"){
    send($chat_id,"👑 لوحة تحكم الأدمن",[
        'inline_keyboard'=>[
            [['text'=>'🟢 تشغيل','callback_data'=>'on'],['text'=>'🔴 إيقاف','callback_data'=>'off']],
            [['text'=>'👥 عدد المستخدمين','callback_data'=>'count'],['text'=>'📋 قائمة المحظورين','callback_data'=>'banned']],
            [['text'=>'⛔ حظر مستخدم','callback_data'=>'ban'],['text'=>'✅ فك الحظر','callback_data'=>'unban']]
        ]
    ]);
}

// باقي عمليات الأدمن (تشغيل/إيقاف/حظر/فك)
if($cb=="on" && $user_id==$owner_id){ file_put_contents($status_file,"on"); send($chat_id,"✅ تم تشغيل البوت"); exit; }
if($cb=="off" && $user_id==$owner_id){ file_put_contents($status_file,"off"); send($chat_id,"✅ تم إيقاف البوت"); exit; }
if($cb=="count" && $user_id==$owner_id){ send($chat_id,"👥 عدد المستخدمين: ".count($users)); exit; }
if($cb=="banned" && $user_id==$owner_id){ $banned_list = implode("\n",$banned); send($chat_id,"📋 المحظورين:\n$banned_list"); exit; }}

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

