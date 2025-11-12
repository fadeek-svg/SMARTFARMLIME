<?php
// dashboard.php
session_start();

// 🔴 ส่วนจัดการปุ่มออกจากระบบ (LOGOUT) 🔴
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    $_SESSION = array(); // ล้างข้อมูล Session ทั้งหมด
    session_destroy();  // ทำลาย Session
    header("location: login.php");
    exit;
}

// ตรวจสอบสิทธิ์ (Protection)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แดชบอร์ดความชื้นในดิน</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Style */
        :root { --primary-color: #007bff; --text-color: #343a40; --background-color: #e9ecef; --card-background: #ffffff; --danger-color: #dc3545; --success-color: #28a745; --warning-color: #ffc107; --border-radius: 12px; }
        body { font-family: 'Kanit', sans-serif; background-color: var(--background-color); color: var(--text-color); margin: 0; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; padding-bottom: 15px; border-bottom: 2px solid #ccc; }
        h1 { color: var(--primary-color); font-weight: 700; }
        /* สไตล์สำหรับปุ่ม Logout */
        .logout-button { background-color: var(--danger-color); color: white; padding: 10px 20px; border: none; border-radius: var(--border-radius); cursor: pointer; font-weight: 500; transition: background-color 0.3s; text-decoration: none;}
        .logout-button:hover { background-color: #c82333; }
        .data-grid { display: flex; justify-content: center; gap: 20px; }
        .data-card { background-color: var(--card-background); padding: 40px; border-radius: var(--border-radius); box-shadow: 0 8px 15px rgba(0,0,0,0.1); text-align: center; width: 300px; transition: box-shadow 0.3s; }
        .data-card:hover { box-shadow: 0 12px 20px rgba(0,0,0,0.15); }
        .card-title { font-size: 1.5em; font-weight: 500; margin-bottom: 15px; color: var(--primary-color); }
        .value { font-size: 6em; font-weight: 700; line-height: 1; color: var(--success-color); }
        .unit { font-size: 2.5em; color: var(--secondary-color); display: block; margin-top: -10px; }
        .timestamp { margin-top: 25px; font-size: 0.9em; color: var(--secondary-color); border-top: 1px dashed #eee; padding-top: 10px; }
        .status-loading { color: var(--warning-color) !important; }
        .status-error { color: var(--danger-color) !important; }
    </style>
</head>
<body>
    <div class="header">
        <h1>แดชบอร์ดความชื้นในดิน 🌿</h1>
        <a href="dashboard.php?action=logout" class="logout-button">ออกจากระบบ</a> 
    </div>
    
    <div class="data-grid">
        <div class="data-card">
            <div class="card-title">ความชื้นในดิน (Soil Moisture)</div>
            <p id="humidity-display" class="value status-loading">---</p>
            <span class="unit">%</span>
            <p id="timestamp" class="timestamp">กำลังเชื่อมต่อ ThingSpeak...</p>
        </div>
    </div>

    <script>
        // *** 🔴 สำคัญ: เปลี่ยนค่าเหล่านี้ให้เป็นของ ThingSpeak Channel ของคุณ 🔴 ***
        const CHANNEL_ID = '3160319'; 
        const READ_API_KEY = 'DPTAJIDC9JYD8YM6'; 
        const FIELD_NUMBER = 1; // <-- หมายเลข Field ที่เก็บค่า "ความชื้นในดิน"

        const THINGSPEAK_URL = `https://api.thingspeak.com/channels/${CHANNEL_ID}/fields/${FIELD_NUMBER}/last.json?api_key=${READ_API_KEY}`;
        
        function fetchHumidity() {
            const display = document.getElementById('humidity-display');
            const timestamp = document.getElementById('timestamp');
            
            display.textContent = '...';
            display.classList.add('status-loading');
            timestamp.textContent = 'กำลังโหลดข้อมูล...';

            fetch(THINGSPEAK_URL)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    const fieldKey = `field${FIELD_NUMBER}`;
                    const humidity = parseFloat(data[fieldKey]);
                    const updateTime = new Date(data.created_at).toLocaleString('th-TH', { 
                        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' 
                    });

                    if (isNaN(humidity) || data[fieldKey] === null) {
                         throw new Error('Invalid or missing humidity value.');
                    }

                    display.textContent = humidity.toFixed(1);
                    display.classList.remove('status-loading', 'status-error');
                    display.style.color = 'var(--success-color)';
                    timestamp.textContent = `อัปเดตล่าสุด: ${updateTime}`;
                    
                    // ตัวอย่าง: การเปลี่ยนสีตามสถานะความชื้น
                    if (humidity < 30) {
                        display.style.color = 'var(--danger-color)'; // แห้งเกินไป
                    } else if (humidity > 70) {
                         display.style.color = 'var(--warning-color)'; // เปียกเกินไป
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    display.textContent = 'ERR';
                    display.classList.remove('status-loading');
                    display.classList.add('status-error');
                    display.style.color = 'var(--danger-color)';
                    timestamp.textContent = 'ไม่สามารถดึงข้อมูลได้';
                });
        }
        
        document.addEventListener('DOMContentLoaded', fetchHumidity);
        setInterval(fetchHumidity, 30000); // อัปเดตทุก 30 วินาที
    </script>
</body>
</html>