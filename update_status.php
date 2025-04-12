<?php
include "koneksi.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sub_id = $_POST["sub_id"];
    $status = $_POST["status"];

    $stmt = $koneksi->prepare("UPDATE sub_tugas SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $sub_id);
    $stmt->execute();
    $stmt->close();
}
?>
