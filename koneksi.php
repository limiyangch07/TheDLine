<?php
    $server = "localhost";
    $username = "root";
    $password = "";
    $db_name = "thedline";

    $koneksi = mysqli_connect($server,$username,$password,$db_name);

    if(!$koneksi){
        die("koneksi gagal: " .$koneksi->connection_erorr);
    }
?>