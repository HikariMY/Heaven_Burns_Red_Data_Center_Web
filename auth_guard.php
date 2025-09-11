<?php
// auth_guard.php
// ไฟล์นี้เอาไว้ include ที่ด้านบนของทุกหน้า admin_*
// เพื่อบังคับตรวจสอบสิทธิ์การเข้าถึง

session_start();

// ถ้าไม่ได้ login หรือไม่ใช่ admin → เด้งไปหน้า login
if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// (ออปชัน) ถ้าอยากตรวจ role จาก users table ที่เก็บไว้ใน session
// เช่น $_SESSION['role'] !== 'admin'
// ก็เพิ่มเงื่อนไขได้
