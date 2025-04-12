<?php
include "koneksi.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_id = isset($_POST['task-id']) ? intval($_POST['task-id']) : 0;

    $task_title = isset($_POST['task-title']) && is_string($_POST['task-title']) 
        ? trim($_POST['task-title']) 
        : '';

    if ($task_id > 0 && !empty($task_title)) {
        $sql = "UPDATE tugas SET judul = ? WHERE id = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("si", $task_title, $task_id);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['new-sub-tasks']) && is_array($_POST['new-sub-tasks'])) {
        foreach ($_POST['new-sub-tasks'] as $nama_baru) {
            $nama_baru = is_string($nama_baru) ? trim($nama_baru) : '';

            if (!empty($nama_baru) && $task_id > 0) {
                $sql = "INSERT INTO sub_tugas (tugas_id, nama, status) VALUES (?, ?, 'pending')";
                $stmt = $koneksi->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("is", $task_id, $nama_baru);
                    if ($stmt->execute()) {
                        echo "Sub-tugas '$nama_baru' berhasil ditambahkan!<br>";
                    } else {
                        echo "Error: " . $stmt->error . "<br>";
                    }
                    $stmt->close();
                } else {
                    echo "Error prepare statement: " . $koneksi->error . "<br>";
                }
            }
        }
    } else {
        echo "Tidak ada sub-tugas baru yang dikirim.<br>";
    }

    header("Location: dashboard.php");
    exit();
}
?>
