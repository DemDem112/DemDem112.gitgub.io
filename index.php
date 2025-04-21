<?php
// ข้อมูลการเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "u187991277_vaultAdmin";
$password = "@Vault7416";
$dbname = "u187991277_valut";

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);
mysqli_set_charset($conn, "utf8");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$uploadDir = 'uploads/';

// ฟังก์ชันสำหรับลบรายการและรูปภาพ
function deleteItem($conn, $itemName, $uploadDir)
{
    $itemName = mysqli_real_escape_string($conn, $itemName);
    $sql_select_image = "SELECT image FROM item_vault WHERE name = '$itemName'";
    $result_image = $conn->query($sql_select_image);

    if ($result_image->num_rows > 0) {
        $row_image = $result_image->fetch_assoc();
        $imageToDelete = $row_image['image'];

        $sql_delete = "DELETE FROM item_vault WHERE name = '$itemName'";

        if ($conn->query($sql_delete) === TRUE) {
            $filePathToDelete = $uploadDir . $imageToDelete;
            if (file_exists($filePathToDelete)) {
                unlink($filePathToDelete);
            }
            return "ลบรายการ '$itemName' สำเร็จ";
        } else {
            return "Error deleting record: " . $conn->error;
        }
    } else {
        return "ไม่พบรายการ '$itemName' ที่ต้องการลบ";
    }
}

// จัดการการลบรายการเดียว
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_item' && isset($_POST['name'])) {
    echo deleteItem($conn, $_POST['name'], $uploadDir);
    exit;
}

// จัดการการลบรายการพร้อมจำนวน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_with_quantity' && isset($_POST['name']) && isset($_POST['quantity'])) {
    $nameToDelete = mysqli_real_escape_string($conn, $_POST['name']);
    $quantityToDelete = intval($_POST['quantity']);

    if ($quantityToDelete > 0) {
        for ($i = 0; $i < $quantityToDelete; $i++) {
            $sql_delete = "DELETE FROM item_vault WHERE name = '$nameToDelete' LIMIT 1";
            $conn->query($sql_delete);
        }
        echo "ลบ $quantityToDelete รายการของ '$nameToDelete' สำเร็จ";
    } else {
        echo "กรุณาระบุจำนวนที่มากกว่า 0";
    }
    exit;
}

// จัดการการเพิ่มรายการใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && isset($_FILES['image'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $size = mysqli_real_escape_string($conn, $_POST['size']);

    if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        $newFileName = md5(time() . $fileName) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
        $destFilePath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destFilePath)) {
            $imagePath = $newFileName;
            $sql = "INSERT INTO item_vault (name, size, image) VALUES ('$name', '$size', '$imagePath')";
            if ($conn->query($sql) === TRUE) {
                echo "เพิ่มรายการ '$name' และอัปโหลดรูปภาพสำเร็จ";
                exit;
            } else {
                unlink($destFilePath);
                echo "Error adding item: " . $conn->error;
                exit;
            }
        } else {
            echo 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์.';
            exit;
        }
    } else {
        echo 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ: ' . $_FILES['image']['error'];
        exit;
    }
}

// ดึงข้อมูลรายการ
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_items') {
    $sql = "SELECT name, size, image FROM item_vault ORDER BY name ASC";
    $result = $conn->query($sql);
    $items = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['img'] = 'uploads/' . $row['image'];
            $items[] = $row;
        }
    }
    header('Content-Type: application/json');
    echo json_encode($items);
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการรายการในตู้แช่</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Prompt', sans-serif;
            padding: 1rem;
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            max-width: 800px;
            margin: auto;
        }

        h1, h2, h3 {
            text-align: center;
            color: #333;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        h2 {
            font-size: 1.3rem;
            margin-top: 1.5rem;
            color: #444;
        }

        input, button, select {
            padding: 0.6rem;
            margin: 0.2rem 0.2rem 0.4rem 0;
            font-size: 1rem;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-family: 'Prompt', sans-serif;
        }

        button {
            background-color: #4caf50;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }

        button:hover {
            background-color: #43a047;
        }

        .custom-file {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .custom-file input[type="file"] {
            display: none;
        }

        .custom-file-label {
            padding: 0.6rem 1rem;
            background-color: #2196f3;
            color: white;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }

        .custom-file-label:hover {
            background-color: #1976d2;
        }

        #newItemFileName {
            font-size: 0.95rem;
            color: #555;
            max-width: 60%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #itemGallery {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .item-card {
            border: 1px solid #ccc;
            border-radius: 12px;
            padding: 10px;
            width: 120px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s;
        }

        .item-card:hover {
            transform: scale(1.05);
        }

        .item-card img {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 5px;
        }

        .item-card .item-name {
            font-size: 0.9rem;
            margin: 0.3rem 0;
            font-weight: bold;
        }

        .item-card .item-size {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 5px;
        }

        .item-card button {
            margin-top: 0.3rem;
            padding: 3px 8px;
            font-size: 0.8rem;
            width: 100%;
        }

        #selectedListDisplay {
            list-style: none;
            padding: 0;
        }

        #selectedListDisplay li {
            background: white;
            padding: 0.8rem;
            margin: 0.5rem 0;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .selected-item {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: space-between;
        }

        .selected-item img {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
        }

        .selected-item-info {
            flex-grow: 1;
        }

        .selected-item button {
            background: #f44336;
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 6px;
        }

        #fullItemList {
            list-style: none;
            padding: 0;
        }

        #fullItemList li {
            background: white;
            padding: 0.8rem;
            margin: 0.5rem 0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        #fullItemList li img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }

        #fullItemList li button {
            margin-left: auto;
            background: #f44336;
        }

        @media (max-width: 600px) {
            body {
                padding: 0.5rem;
            }

            .item-card {
                width: 100px;
            }

            input, button, select {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <h1>📦 จัดการรายการในตู้แช่</h1>

    <section style="padding:1rem; border:1px solid #ccc; border-radius:8px; background:#f9f9f9; margin-bottom:1rem;">
        <h2>คลิกเลือกรายการ</h2>
        <div id="itemGallery"></div>
        <label for="itemQuantity">จำนวน:</label>
        <input type="number" id="itemQuantity" value="1" min="1" style="width:60px; text-align:center;">
    </section>

    <section style="padding:1rem; border:1px solid #ccc; border-radius:8px; background:#f9f9f9; margin-bottom:1rem;">
        <h3>รายการที่เลือก</h3>
        <ul id="selectedListDisplay"></ul>
    </section>

    <section style="padding:1rem; border:1px solid #ccc; border-radius:8px; background:#f9f9f9; margin-bottom:1rem;">
        <h2>เพิ่มรายการใหม่</h2>
        <input type="text" id="newItemName" placeholder="ชื่อรายการใหม่" style="width:100%; margin-bottom:0.5rem;">
        <input type="text" id="newItemSize" placeholder="ขนาด/ความจุ (เช่น 500ml, 1kg)" style="width:100%; margin-bottom:0.5rem;">
        <div class="custom-file">
            <label for="newItemImage" class="custom-file-label">📷 เลือกรูป</label>
            <span id="newItemFileName">ยังไม่เลือกรูป</span>
            <input type="file" id="newItemImage" accept="image/*">
        </div>
        <button type="button" onclick="addNewItem()" style="margin-top:0.5rem;">➕ เพิ่มรายการใหม่</button>
    </section>

    <section style="padding:1rem; border:1px solid #ccc; border-radius:8px; background:#f9f9f9;">
        <h2>รายการทั้งหมด</h2>
        <ul id="fullItemList"></ul>
    </section>

    <script>
        let items = [];
        let selectedItemsList = [];

        // ดึงข้อมูลรายการจากเซิร์ฟเวอร์
        function fetchItems() {
            fetch('?action=get_items')
                .then(response => response.json())
                .then(data => {
                    items = data;
                    renderItemGallery();
                    renderFullItemList();
                })
                .catch(error => {
                    console.error('เกิดข้อผิดพลาดในการดึงข้อมูล:', error);
                    alert('เกิดข้อผิดพลาดในการดึงข้อมูล');
                });
        }

        // แสดงรายการในรูปแบบการ์ด
        function renderItemGallery() {
            const itemGallery = document.getElementById('itemGallery');
            itemGallery.innerHTML = '';

            items.forEach(item => {
                const card = document.createElement('div');
                card.className = 'item-card';

                const img = document.createElement('img');
                img.src = item.img;
                img.alt = item.name;

                const name = document.createElement('div');
                name.className = 'item-name';
                name.textContent = item.name;

                const size = document.createElement('div');
                size.className = 'item-size';
                size.textContent = item.size || 'ไม่มีข้อมูลขนาด';

                const btn = document.createElement('button');
                btn.textContent = 'เลือก';
                btn.onclick = () => {
                    const quantity = parseInt(document.getElementById('itemQuantity').value);
                    if (quantity > 0) {
                        addToSelectedList(item, quantity);
                    } else {
                        alert("กรุณาระบุจำนวนที่ต้องการเพิ่ม");
                    }
                };

                card.appendChild(img);
                card.appendChild(name);
                card.appendChild(size);
                card.appendChild(btn);

                itemGallery.appendChild(card);
            });
        }

        // เพิ่มรายการที่เลือกเข้าไปในลิสต์
        function addToSelectedList(item, quantity) {
            const existingItemIndex = selectedItemsList.findIndex(i => i.name === item.name && i.size === item.size);

            if (existingItemIndex >= 0) {
                selectedItemsList[existingItemIndex].quantity += quantity;
            } else {
                selectedItemsList.push({
                    name: item.name,
                    size: item.size,
                    image: item.image,
                    quantity: quantity
                });
            }

            renderSelectedItemsList();
        }

        // แสดงรายการที่เลือก
        function renderSelectedItemsList() {
            const ul = document.getElementById('selectedListDisplay');
            ul.innerHTML = '';

            selectedItemsList.forEach((item, index) => {
                const li = document.createElement('li');

                const div = document.createElement('div');
                div.className = 'selected-item';

                const img = document.createElement('img');
                img.src = 'uploads/' + item.image;
                img.alt = item.name;

                const info = document.createElement('div');
                info.className = 'selected-item-info';
                info.innerHTML = `<strong>${item.name}</strong> (${item.size || '-'}) × ${item.quantity}`;

                const btn = document.createElement('button');
                btn.textContent = 'ลบ';
                btn.onclick = () => removeSelectedItem(index);

                div.appendChild(img);
                div.appendChild(info);
                div.appendChild(btn);
                li.appendChild(div);
                ul.appendChild(li);
            });
        }

        // ลบรายการที่เลือก
        function removeSelectedItem(index) {
            selectedItemsList.splice(index, 1);
            renderSelectedItemsList();
        }

        // แสดงรายการทั้งหมด
        function renderFullItemList() {
            const ul = document.getElementById('fullItemList');
            ul.innerHTML = '';

            items.forEach(item => {
                const li = document.createElement('li');

                const img = document.createElement('img');
                img.src = 'uploads/' + item.image;
                img.alt = item.name;

                const info = document.createElement('span');
                info.textContent = `${item.name} (${item.size || '-'})`;

                const btn = document.createElement('button');
                btn.textContent = '🗑️ ลบ';
                btn.onclick = () => deleteSingleItem(item.name);

                li.appendChild(img);
                li.appendChild(info);
                li.appendChild(btn);
                ul.appendChild(li);
            });
        }

        // เพิ่มรายการใหม่
        function addNewItem() {
            const name = document.getElementById('newItemName').value.trim();
            const size = document.getElementById('newItemSize').value.trim();
            const fileInput = document.getElementById('newItemImage');
            const file = fileInput.files[0];

            if (name && file) {
                const formData = new FormData();
                formData.append('name', name);
                formData.append('size', size);
                formData.append('image', file);

                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    alert(data);
                    document.getElementById('newItemName').value = '';
                    document.getElementById('newItemSize').value = '';
                    document.getElementById('newItemImage').value = '';
                    document.getElementById('newItemFileName').textContent = 'ยังไม่เลือกรูป';
                    fetchItems();
                })
                .catch(error => {
                    console.error('เกิดข้อผิดพลาดในการเพิ่มข้อมูล:', error);
                    alert('เกิดข้อผิดพลาดในการเพิ่มข้อมูล');
                });
            } else {
                alert('กรุณากรอกชื่อและเลือกรูปภาพสำหรับรายการใหม่');
            }
        }

        // ลบรายการเดียว
        function deleteSingleItem(name) {
            if (confirm(`คุณต้องการลบรายการ "${name}" ใช่หรือไม่?`)) {
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_item&name=${encodeURIComponent(name)}`
                })
                .then(response => response.text())
                .then(data => {
                    alert(data);
                    fetchItems();
                })
                .catch(error => {
                    console.error('เกิดข้อผิดพลาดในการลบ:', error);
                    alert('เกิดข้อผิดพลาดในการลบ');
                });
            }
        }

        // แสดงชื่อไฟล์เมื่อเลือกไฟล์
        document.getElementById('newItemImage').addEventListener('change', function() {
            const file = this.files[0];
            document.getElementById('newItemFileName').textContent = file ? file.name : 'ยังไม่เลือกรูป';
        });

        // โหลดข้อมูลครั้งแรก
        fetchItems();
    </script>
</body>
</html>