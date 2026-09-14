<?php

require_once __DIR__ . '/../auth/session.php';
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["success" => false, "error" => "Authentication required."]);
    exit;
}

header(
    "Content-Type: application/json"
);

require_once "../config/database.php";

/** @var mysqli $conn */
$conn = $conn ?? null;

$totalServers = 0;
$onlineServers = 0;
$warningServers = 0;
$offlineServers = 0;


$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM servers"
);

$row = mysqli_fetch_assoc($result);

$totalServers = (int) $row["total"];


$result = mysqli_query(
    $conn,
    "SELECT status, COUNT(*) AS total
     FROM servers
     GROUP BY status"
);


while (
    $row = mysqli_fetch_assoc($result)
) {

    if ($row["status"] === "online") {

        $onlineServers =
            (int) $row["total"];

    }

    elseif (
        $row["status"] === "warning"
    ) {

        $warningServers =
            (int) $row["total"];

    }

    elseif (
        $row["status"] === "offline"
    ) {

        $offlineServers =
            (int) $row["total"];

    }

}


echo json_encode(
    [
        "success" => true,

        "data" => [

            "total_servers" =>
                $totalServers,

            "online_servers" =>
                $onlineServers,

            "warning_servers" =>
                $warningServers,

            "offline_servers" =>
                $offlineServers

        ]

    ],
    JSON_PRETTY_PRINT
);

?>