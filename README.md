# 🚀 SQLManager v1.0

> Lightweight PHP Query Builder + Schema Builder + Mini ORM  
> Built for Speed. Designed for Simplicity.

![PHP](https://img.shields.io/badge/PHP-8.0+-blue)
![Database](https://img.shields.io/badge/MySQL-Supported-orange)
![License](https://img.shields.io/badge/License-MIT-green)
![Status](https://img.shields.io/badge/Version-1.0-success)

---

## 📌 About SQLManager

SQLManager is a powerful and lightweight PHP-based database engine built on PDO.  
It combines:

- Query Builder
- CRUD Engine
- Schema Builder
- Mini ORM
- Transactions
- Backup System
- Debug Mode
- Role-Based Access

Perfect for custom hosting panels, admin dashboards, SaaS systems, and custom frameworks.

---

# ⚡ Features

## 🧠 Query Builder
```php
$db->table("users")
   ->where("status","=","active")
   ->orderBy("id","DESC")
   ->limit(10)
   ->get();




✍ Insert Data
$db->table("users")->insert([
   "name" => "John",
   "email" => "john@example.com"
]);


🔄 Update
$db->table("users")
   ->where("id","=",1)
   ->update([
      "name"=>"Updated Name"
   ]);


🗑 Delete
$db->table("users")
   ->where("id","=",1)
   ->delete();



🏗 Schema Builder
$db->createTable("users", function($bp) use ($db){
   $db->column_id($bp);
   $db->column_string($bp,"name");
   $db->column_string($bp,"email");
});



🔄 Transactions
$db->beginTransaction();

try{
   $db->table("users")->insert([...]);
   $db->commit();
}catch(Exception $e){
   $db->rollback();
}



📦 Installation
1️⃣ Include Files
require("config.php");
require("SQLManager.php");
$db = new SQLManager();



2️⃣ config.php Example
$pdo = new PDO(
    "mysql:host=localhost;dbname=test_db;charset=utf8mb4",
    "root",
    ""
);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);





🔐 Safe Mode

Prevents full table delete without WHERE.

Disable if needed:

$db->disableSafeMode();





🧩 Mini ORM
$user = $db->model("users");

$user->all();
$user->find(1);
$user->create(["name"=>"New User"]);




📊 Feature Status


| Feature        | Status      |
| -------------- | ----------- |
| Query Builder  | ✅ Stable    |
| CRUD Engine    | ✅ Stable    |
| Schema Builder | ✅ Stable    |
| Backup Engine  | ✅ Medium DB |
| Mini ORM       | ✅ Basic     |
| RBAC           | ✅ Basic     |



📁 Project Structure
SQLManager/
│
├── SQLManager.php
├── config.php
├── documentation.html
└── README.md



🎯 Use Cases

Custom Hosting Panels

Admin Dashboards

SaaS Projects

API Backends

Learning PDO Architecture




# 📜 License

MIT License

Copyright (c) 2026 TechElevateX

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND.