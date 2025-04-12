<?php
include "koneksi.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["sub_id"])) {
    $sub_id = intval($_POST["sub_id"]);
    $sql = "DELETE FROM sub_tugas WHERE id = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $sub_id);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    
    $stmt->close();
    $koneksi->close();
}
?>
