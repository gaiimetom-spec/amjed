# استخدم صورة PHP مع Apache
FROM php:8.2-apache

# نسخ جميع الملفات في مجلد Apache
COPY . /var/www/html/

# فتح البورت 80
EXPOSE 80

# جعل ملف amjed.php هو نقطة الدخول (اختياري)
# يمكن الوصول إليه عبر http://localhost/amjed.php