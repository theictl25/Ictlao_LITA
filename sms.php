<?php
// ส่งข้อความ SMS ไปยังเบอร์ไทย (รูปแบบ 0812345678) คืนค่า true เมื่อสำเร็จ หรือข้อความ error
function send_sms($phone,$msg){
 if(SMS_PROVIDER=='demo'){
  if(!in_array($_SERVER['SERVER_NAME']??'',['localhost','127.0.0.1','::1']))return 'โหมดทดสอบใช้ได้เฉพาะบน localhost กรุณาตั้งค่า SMS_PROVIDER ใน config.php';
  $_SESSION['demo_otp']=$msg;return true;}
 if(SMS_PROVIDER=='thaibulksms'){
  if(!function_exists('curl_init'))return 'เซิร์ฟเวอร์ไม่รองรับ cURL';
  $ch=curl_init('https://api-v2.thaibulksms.com/sms');
  curl_setopt_array($ch,[CURLOPT_POST=>1,CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>15,CURLOPT_USERPWD=>SMS_KEY.':'.SMS_SECRET,
   CURLOPT_POSTFIELDS=>http_build_query(['msisdn'=>'66'.substr($phone,1),'message'=>$msg,'sender'=>SMS_SENDER])]);
  curl_exec($ch);$c=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
  return ($c>=200&&$c<300)?true:'ส่ง SMS ไม่สำเร็จ (รหัส '.$c.') กรุณาลองใหม่';}
 return 'ยังไม่ได้ตั้งค่า SMS_PROVIDER';
}
