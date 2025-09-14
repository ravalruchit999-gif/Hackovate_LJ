<?php
require_once __DIR__ . '/config.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin - <?= APP_NAME ?></title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #f5f6fa; margin: 0; }
    .navbar {
      background: linear-gradient(135deg, #667eea, #764ba2);
      padding: 15px;
      display: flex; justify-content: space-between; align-items: center;
      color: #fff;
    }
    .navbar h1 { margin: 0; font-size: 1.3rem; }
    .navbar a {
      color: #fff; text-decoration: none; margin-left: 15px; font-weight: 600;
    }
    .navbar a:hover { text-decoration: underline; }
    .container { padding: 25px; }
    .btn {
      padding: 8px 15px; border-radius: 8px;
      background: linear-gradient(135deg,#667eea,#764ba2);
      color: #fff; text-decoration: none; font-weight: 600; border: none;
      cursor: pointer;
    }
    .btn:hover { opacity: 0.9; }
    table {
      width: 100%; border-collapse: collapse; margin-top: 20px;
    }
    th, td {
      padding: 10px; border: 1px solid #ddd; text-align: left;
    }
    th { background: #667eea; color: #fff; }
    tr:nth-child(even) { background: #f9f9f9; }
    form input, form select {
      padding: 8px; border: 1px solid #ccc; border-radius: 6px;
      width: 100%; margin-bottom: 10px;
    }
    .card {
      background: #fff; padding: 20px; border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div class="navbar">
    <h1>🚌 Admin Panel</h1>
    <div>
      <a href="admin_dashboard.php"style="text-decoration:none">Admin Dashboard</a>
      <a href="logout.php"style="text-decoration:none">Logout</a>
    </div>
  </div>
  <div class="container">
