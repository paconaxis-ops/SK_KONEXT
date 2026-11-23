<?php

include("db.php");

header("Content-Type: application/json");

$barangay = isset($_GET['barangay']) ? trim($_GET['barangay']) : '';

if ($barangay == '') {
    echo json_encode([
        "finished" => 0,
        "upcoming" => 0,
        "scholars" => 0
    ]);
    exit;
}

$barangay_safe = mysqli_real_escape_string($conn, $barangay);


/* =========================================================
   GET BARANGAY ID
========================================================= */

$barangay_query = mysqli_query($conn, "
    SELECT id, name, total_scholars
    FROM barangays
    WHERE TRIM(name) = TRIM('$barangay_safe')
    LIMIT 1
");

if (!$barangay_query || mysqli_num_rows($barangay_query) == 0) {

    echo json_encode([
        "finished" => 0,
        "upcoming" => 0,
        "scholars" => 0
    ]);

    exit;
}

$barangay_data = mysqli_fetch_assoc($barangay_query);

$barangay_id = (int)$barangay_data['id'];


/* =========================================================
   FINISHED PROJECTS
========================================================= */

$finished_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM barangay_projects
    WHERE barangay_id = $barangay_id
    AND LOWER(TRIM(status)) IN ('completed', 'finished')
");

$finished = 0;

if ($finished_query) {
    $finished_data = mysqli_fetch_assoc($finished_query);
    $finished = (int)$finished_data['total'];
}


/* =========================================================
   UPCOMING PROJECTS
========================================================= */

$upcoming_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM barangay_projects
    WHERE barangay_id = $barangay_id
    AND LOWER(TRIM(status)) IN ('planned', 'upcoming')
");

$upcoming = 0;

if ($upcoming_query) {
    $upcoming_data = mysqli_fetch_assoc($upcoming_query);
    $upcoming = (int)$upcoming_data['total'];
}


/* =========================================================
   SCHOLARS
========================================================= */

$scholars = isset($barangay_data['total_scholars'])
    ? (int)$barangay_data['total_scholars']
    : 0;


/* =========================================================
   RETURN JSON
========================================================= */

echo json_encode([
    "finished" => $finished,
    "upcoming" => $upcoming,
    "scholars" => $scholars
]);

?>