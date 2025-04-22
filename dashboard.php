<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start(); 

include "koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

$sql = "SELECT t.id, t.judul, t.deadline, s.id AS sub_id, s.nama, s.status, u.id AS user_id, u.username AS user_nama
        FROM tugas t
        LEFT JOIN sub_tugas s ON t.id = s.tugas_id
        LEFT JOIN tbl_user u ON t.user_id = u.id
        WHERE t.user_id = ?
        ORDER BY t.deadline ASC, t.id, s.id";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$tugas = array();
$user_nama = "";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $user_nama = $row["user_nama"]; 
        
        if (!isset($tugas[$row["id"]])) {
            $tugas[$row["id"]] = [
                "judul" => $row["judul"],
                "deadline" => $row["deadline"],
                "sub_tugas" => []
            ];
        }                
        if ($row["sub_id"] !== null) {
            $tugas[$row["id"]]["sub_tugas"][] = [
                "id" => $row["sub_id"], 
                "nama" => $row["nama"], 
                "status" => $row["status"]
            ];
        }
    }
}

$non_overdue_tasks = [];
$overdue_tasks = [];

foreach ($tugas as $id => $tugas_data) {
    $deadline = $tugas_data["deadline"];
    if ($deadline !== null && strtotime($deadline) < strtotime($today)) {
        $overdue_tasks[$id] = $tugas_data;
    } else {
        $non_overdue_tasks[$id] = $tugas_data; 
    }
}

$koneksi->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The D-Line</title>
    <link rel="stylesheet" href="dashboard.css">
    <script>
    window.onload = function () {
        const popup = document.getElementById('welcome-popup');
        popup.style.display = 'block';

        document.getElementById('close-popup').onclick = function () {
            popup.style.display = 'none';
        };
    };
        
    function addSubTaskFieldForm(containerId, taskId) {
        const container = document.getElementById(containerId);
        const div = document.createElement("div");
        div.className = "sub-task";
        
        const input = document.createElement("input");
        input.type = "text";
        input.name = "sub-tasks[]";
        input.placeholder = "Sub Tugas Baru";

        div.appendChild(input);
        container.appendChild(div);


        document.querySelector(`.task[data-id='${taskId}']`).classList.remove('completed');
    }  

    function addSubTaskField(containerId, taskId) {
        const container = document.getElementById(containerId);
        const div = document.createElement("div");
        div.className = "sub-task";
        
        const input = document.createElement("input");
        input.type = "text";
        input.name = "new-sub-tasks[]";
        input.placeholder = "Sub Tugas Baru";

        div.appendChild(input);
        container.appendChild(div);

        document.querySelector(`.task[data-id='${taskId}']`).classList.remove('completed');
    }

    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".sub-task-checkbox").forEach(checkbox => {
            checkbox.addEventListener("change", function() {
                const subTaskId = this.getAttribute("data-id");
                const status = this.checked ? "done" : "pending";
                
                fetch("update_status.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `sub_id=${subTaskId}&status=${status}`
                }).then(() => {
                    this.nextElementSibling.classList.toggle("completed", this.checked);
                    if (this.checked) {
                        this.disabled = true; 
                    }
 
                    const taskId = this.closest('.task').getAttribute('data-id');
                    const allCheckboxes = document.querySelectorAll(`.task[data-id='${taskId}'] .sub-task-checkbox`);
                    let allDone = true;
                    allCheckboxes.forEach(checkbox => {
                        if (!checkbox.checked) {
                            allDone = false;
                        }
                    });
                    if (allDone) {
                        document.querySelector(`.task[data-id='${taskId}']`).classList.add('completed1');
                    } else {
                        document.querySelector(`.task[data-id='${taskId}']`).classList.remove('completed1');
                    }
                });
            });
        });
    });

    function toggleEditForm(taskId) {
        const form = document.getElementById(`edit-form-${taskId}`);
        const deleteButtons = document.querySelectorAll(`.delete-sub-task-${taskId}`);
        
        if (form.style.display === "none" || form.style.display === "") {
            form.style.display = "block";
            deleteButtons.forEach(btn => btn.style.display = "inline");
        } else {
            form.style.display = "none";
            deleteButtons.forEach(btn => btn.style.display = "none");
        }
    }

    function deleteSubTask(subTaskId) {
        if (confirm("Apakah Anda yakin ingin menghapus sub-tugas ini?")) {
            fetch("hapus_subtugas.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `sub_id=${subTaskId}`
            }).then(response => response.text()).then(data => {
                if (data === "success") {
                    document.getElementById(`sub-task-${subTaskId}`).remove();
                } else {
                    alert("Gagal menghapus sub-tugas");
                }
            });
        }
    }

    function searchTasks() {
    const input = document.getElementById('search-box').value.toLowerCase();
    const resultsContainer = document.getElementById('search-results');
    resultsContainer.innerHTML = '';

    if (input.length === 0) return;

    const allTasks = document.querySelectorAll('.task');
    const today = new Date().toISOString().split('T')[0];

    allTasks.forEach(task => {
        const titleElement = task.querySelector('strong');
        const title = titleElement.textContent.toLowerCase();
        const id = task.getAttribute('data-id');
        const deadlineText = task.querySelector('p').textContent.trim();
        const match = deadlineText.match(/Deadline: (\d{4}-\d{2}-\d{2})/);
        let isOverdue = false;

        if (match) {
            const deadlineDate = match[1];
            isOverdue = deadlineDate < today;
        }

        if (title.includes(input) || isOverdue) {
            const li = document.createElement('li');
            const link = document.createElement('a');
            link.href = `#task-${id}`;
            link.textContent = `${titleElement.textContent} (${isOverdue ? 'Lewat Deadline' : 'Aktif'})`;
            li.appendChild(link);
            resultsContainer.appendChild(li);
        }
    });
}


    </script>
    <style>
        .completed1 {
            background-color: #e0f7fa; 
        }
        .sub-task.completed{
            text-decoration: line-through; 
            color: gray; 
        }
        .deadline-mendekati {
            color: red;
            font-weight: bold;
        }
        .deadline-urgent {
            color: red;
            font-weight: bold;
        }
        #welcome-popup {
            display: none;
            position: fixed;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.6);
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        #welcome-popup .popup-content {
            background: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 400px;
            text-align: center;
        }

        #welcome-popup .popup-content button {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div id="welcome-popup">
        <div class="popup-content">
            <h3>Selamat Datang, <?= htmlspecialchars($user_nama) ?>!</h3>
            <p>Semoga harimu produktif 🎯</p>
            <button id="close-popup">Tutup</button>
        </div>
    </div>
    
    <div class="header">
        <div class="namaaplikasi">
            <h2>The D-Line</h2>
            <p>Selamat datang, <?= htmlspecialchars($user_nama) ?>!</p>
        </div>
        <form action="logout.php" method="post">
            <button class="logout-button" type="submit" onclick="return confirm('Apakah Anda yakin ingin keluar?');">Logout</button>
        </form>
    </div>

    <h4>Tambah Tugas Baru</h4>
    <form class="tambah" action="save_tugas.php" method="POST">
        <label for="task-title">Judul Tugas:</label>
        <input type="text" id="task-title" name="task-title" required>
        <label for="task-deadline">Deadline:</label>
        <input type="date" id="task-deadline" name="task-deadline" min="<?= date('Y-m-d') ?>" required>
        <div id="sub-task-container"></div>
        <button type="button" onclick="addSubTaskFieldForm('sub-task-container', 'new')">Tambah Sub Tugas</button>
        <button type="submit" onclick="alert('Tugas telah ditambahkan!')">Tambah</button>
    </form>

    <div class="task-header">
          <h4>Daftar Tugas</h4>
        <div class="search-box-container">
            <input type="text" id="search-box" placeholder="Cari judul..." onkeyup="searchTasks()">
        </div>
    </div>
    <ul id="search-results" style="list-style:none; padding:0;"></ul>
    <?php foreach ($non_overdue_tasks as $id => $tugas_data): ?>
    <?php
        $judul = $tugas_data["judul"] ?? 'No title';
        $deadline = $tugas_data["deadline"] ?? null;

        $deadline_class = "";
        $days_left = (strtotime($deadline) - strtotime($today)) / (60 * 60 * 24);
        if ($days_left < 3) {
            $deadline_class = "deadline-urgent";
        }
    ?>
    <div class="task <?= (count($tugas_data["sub_tugas"]) > 0 && array_reduce($tugas_data["sub_tugas"], function($carry, $sub) {
        return $carry && ($sub['status'] === 'done');
    }, true)) ? 'completed' : '' ?>" data-id="<?= $id ?>" id="task-<?= $id ?>">
        <div class="juduldandeadline">
            <strong><?= htmlspecialchars($judul) ?></strong>
            <p class="<?= $deadline_class ?>">Deadline: <?= $deadline ? htmlspecialchars($deadline) : 'No deadline set' ?></p>
        </div>
        <?php if (!empty($tugas_data["sub_tugas"])): ?>
            <?php foreach ($tugas_data["sub_tugas"] as $sub): ?>
                <div class="sub-task" id="sub-task-<?= $sub["id"] ?>">
                    <input type="checkbox" data-id="<?= $sub["id"] ?>" class="sub-task-checkbox" <?= $sub["status"] == "done" ? "checked" : "" ?>>
                    <span class="<?= $sub["status"] == "done" ? "completed" : "" ?>">
                        <?= htmlspecialchars($sub["nama"]) ?>
                    </span>
                    <button class="delete-sub-task-<?= $id ?>" style="display:none;" onclick="deleteSubTask(<?= $sub["id"] ?>)">Hapus</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <button onclick="toggleEditForm(<?= $id ?>)">Edit</button>
        <form action="hapus_tugas.php" method="GET" style="display:inline;">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menghapus tugas ini beserta sub-tugasnya?');">Hapus</button>
        </form>
        
        <form id="edit-form-<?= $id ?>" action="edit_tugas.php" method="POST" style="display:none;">
            <input type="hidden" name="task-id" value="<?= $id ?>">
            <input type="text" name="task-title" value="<?= htmlspecialchars($judul) ?>" required>
            <input type="date" name="task-deadline" value="<?= htmlspecialchars($deadline) ?>" min="<?= date('Y-m-d')?>" required>
            <div id="subtaskcontaineredit"></div>
            <button type="button" onclick="addSubTaskField('subtaskcontaineredit', <?= $id ?>)">Tambah Sub Tugas</button>
            <button type="submit" onclick="return confirm('Apakah Anda yakin ingin merubah data?');">Simpan</button>
        </form>
    </div>
    <?php endforeach; ?>

    <h4>Tugas Lewat Deadline</h4>
    <?php foreach ($overdue_tasks as $id => $tugas_data): ?>
    <div class="task" data-id="<?= $id ?>" id="task-<?= $id ?>">
        <div class="juduldandeadline">
            <strong><?= htmlspecialchars($tugas_data["judul"]) ?></strong>
            <p class="deadline-mendekati">Deadline: <?= htmlspecialchars($tugas_data["deadline"]) ?></p>
        </div>
        <?php if (!empty($tugas_data["sub_tugas"])): ?>
            <?php foreach ($tugas_data["sub_tugas"] as $sub): ?>
                <div class="sub-task" id="sub-task-<?= $sub["id"] ?>">
                    <input type="checkbox" data-id="<?= $sub["id"] ?>" class="sub-task-checkbox" <?= $sub["status"] == "done" ? "checked" : "" ?> onclick="this.checked = true; this.disabled = true;">
                    <span class="<?= $sub["status"] == "done" ? "completed" : "" ?>">
                        <?= htmlspecialchars($sub["nama"]) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <p>Tugas ini sudah lewat deadline dan tidak dapat diedit.</p>
        <form action="hapus_tugas.php" method="GET" style="display:inline;">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menghapus tugas ini beserta sub-tugasnya?');">Hapus</button>
        </form>
    </div>
    <?php endforeach; ?>

</body>
</html>
