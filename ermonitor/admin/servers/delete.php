<?php

require_once "../../auth/check.php";
requireAdmin();
require_once "../../config/database.php";
require_once "../../includes/audit.php";


$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !verifyCsrf($_POST["csrf_token"] ?? null)) {
    header("Location: index.php");
    exit;
}


if (!$id) {

    header("Location: index.php");

    exit;

}


$sql = "
    DELETE FROM servers
    WHERE id = ?
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);
writeAudit("server_deleted", "Server ID: " . $id);

header(
    "Location: index.php"
);

exit;

?>