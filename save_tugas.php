<?php
include "koneksi.php";
session_start();  // Mulai session

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $judul_tugas = isset($_POST["task-title"]) ? trim($_POST["task-title"]) : "";
    $deadline = isset($_POST["task-deadline"]) ? trim($_POST["task-deadline"]) : "";
    $sub_tugas = isset($_POST["sub-tasks"]) && is_array($_POST["sub-tasks"]) ? $_POST["sub-tasks"] : [];

    if (!empty($judul_tugas) && !empty($deadline)) {
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $sql = "INSERT INTO tugas (judul, deadline, user_id) VALUES (?, ?, ?)";
            
            $stmt = $koneksi->prepare($sql); // Definisikan $stmt
            $stmt->bind_param("ssi", $judul_tugas, $deadline, $user_id); // Perbaikan tipe data: ssi (string, string, integer)
            
            if ($stmt->execute()) {
                $tugas_id = $stmt->insert_id;
                $stmt->close();

                if (!empty($sub_tugas)) {
                    $stmt = $koneksi->prepare("INSERT INTO sub_tugas (tugas_id, nama) VALUES (?, ?)");
                    foreach ($sub_tugas as $sub) {
                        $sub = trim($sub);
                        if (!empty($sub)) {
                            $stmt->bind_param("is", $tugas_id, $sub);
                            $stmt->execute();
                        }
                    }
                    $stmt->close();
                }
            } else {
                echo "Error: " . $stmt->error;
            }
        } else {
            echo "Error: User tidak terdeteksi. Silakan login.";
        }
    }
    
    $koneksi->close();
    header("Location: dashboard.php");
    exit();
}
