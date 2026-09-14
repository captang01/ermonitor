<?php

require_once __DIR__ . '/../auth/check.php';

header(
    "Content-Type: application/json"
);

require_once "../config/database.php";


$sql = "
    SELECT
        id,
        name,
        ip_address,
        os,
        status,
        description,
        created_at,
        updated_at
    FROM servers
    ORDER BY id DESC
";


$result = mysqli_query(
    $conn,
    $sql
);


$servers = [];


while (
    $row = mysqli_fetch_assoc($result)
) {

    $servers[] = $row;

}


echo json_encode(
    [
        "success" => true,
        "data" => $servers
    ],
    JSON_PRETTY_PRINT
);

?>