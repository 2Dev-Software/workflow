<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        exit('403 Forbidden: Invalid Security Token');
    }

    $pID = trim($_POST['pID'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($pID === '' || $password === '') {
        http_response_code(400);
        exit('400 Bad Request: Missing Credentials');
    }

    $sql = "SELECT * FROM teacher WHERE pID = ? AND password = ? AND status = 1 LIMIT 1";

    if ($stmt = mysqli_prepare($connection, $sql)) {
        
        mysqli_stmt_bind_param($stmt, "ss", $pID, $password);
        
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            session_regenerate_id(true);
            $_SESSION['pID'] = $row['pID'];
            
            header("Location: ");
            exit();
        } else {
            http_response_code(401);
            exit('401 Unauthorized: Invalid Credentials');
        }

        mysqli_stmt_close($stmt);
    } else {
        error_log("Database Error: " . mysqli_error($connection));
        http_response_code(500);
        exit('500 Internal Server Error');
    }

    mysqli_close($connection);
}
?>