<?php
include "koneksi.php";

if (isset($_GET['id'])) {
    $task_id = intval($_GET['id']);

    $sql_delete_sub = "DELETE FROM sub_tugas WHERE tugas_id = $task_id";
    $koneksi->query($sql_delete_sub);

    $sql_delete_task = "DELETE FROM tugas WHERE id = $task_id";
    $koneksi->query($sql_delete_task);
}

$koneksi->close();
header("Location: dashboard.php");
exit();
?>
