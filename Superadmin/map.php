
<?php
include("../db.php");
include("../includes/auth.php");

allowRoles(['superadmin']);


/* =========================================================
   NAIC MAP SETTINGS
========================================================= */

$naicCenterLat = 14.333;
$naicCenterLng = 120.772;

$minLat = 14.25;
$maxLat = 14.42;
$minLng = 120.70;
$maxLng = 120.86;


/* =========================================================
   LOAD BARANGAYS
========================================================= */

$barangays = [];

$barangayQuery = mysqli_query(
    $conn,
    "SELECT id, name FROM barangays ORDER BY name ASC"
);

if($barangayQuery){

    while($row = mysqli_fetch_assoc($barangayQuery)){

        $barangays[] = $row;

    }

}


/* =========================================================
   IMAGE UPLOAD
========================================================= */

function uploadImage($file){

    $targetDir = "../uploads/maps/";

    if(!is_dir($targetDir)){
        mkdir($targetDir, 0777, true);
    }

    $allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    if(!in_array($file["type"], $allowedTypes)){
        return null;
    }

    if($file["size"] > 5 * 1024 * 1024){
        return null;
    }

    $extension = pathinfo(
        $file["name"],
        PATHINFO_EXTENSION
    );

    $fileName =
        time() .
        "_" .
        uniqid() .
        "." .
        strtolower($extension);

    $targetFile =
        $targetDir .
        $fileName;

    if(
        move_uploaded_file(
            $file["tmp_name"],
            $targetFile
        )
    ){

        return $fileName;

    }

    return null;
}


/* =========================================================
   ADD PROJECT
========================================================= */

if(isset($_POST['add'])){

    $title = mysqli_real_escape_string(
        $conn,
        $_POST['title']
    );

    $desc = mysqli_real_escape_string(
        $conn,
        $_POST['description']
    );

    $barangay = mysqli_real_escape_string(
        $conn,
        $_POST['barangay']
    );

    $category = mysqli_real_escape_string(
        $conn,
        $_POST['category']
    );

    $status = mysqli_real_escape_string(
        $conn,
        $_POST['status']
    );

    $lat = floatval($_POST['lat']);
    $lng = floatval($_POST['lng']);

    $image = null;

    if(
        isset($_FILES['image']) &&
        !empty($_FILES['image']['name'])
    ){

        $image = uploadImage(
            $_FILES['image']
        );

    }

    $imageSQL = $image
        ? "'" .
            mysqli_real_escape_string(
                $conn,
                $image
            ) .
          "'"
        : "NULL";


    mysqli_query($conn, "

        INSERT INTO map_projects
        (
            title,
            description,
            barangay,
            latitude,
            longitude,
            image,
            category,
            status
        )

        VALUES
        (
            '$title',
            '$desc',
            '$barangay',
            '$lat',
            '$lng',
            $imageSQL,
            '$category',
            '$status'
        )

    ");


    header("Location: map.php");
    exit;
}


/* =========================================================
   DELETE PROJECT
========================================================= */

if(isset($_GET['delete'])){

    $id = (int) $_GET['delete'];

    $get = mysqli_fetch_assoc(

        mysqli_query(
            $conn,
            "SELECT image
             FROM map_projects
             WHERE id=$id"
        )

    );


    if(
        $get &&
        !empty($get['image'])
    ){

        $imagePath =
            "../uploads/maps/" .
            $get['image'];

        if(file_exists($imagePath)){

            @unlink($imagePath);

        }

    }


    mysqli_query(
        $conn,
        "DELETE FROM map_projects
         WHERE id=$id"
    );


    header("Location: map.php");
    exit;
}


/* =========================================================
   LOAD PROJECTS
========================================================= */

$result = mysqli_query(
    $conn,
    "SELECT *
     FROM map_projects
     ORDER BY id DESC"
);

$pins = [];

while($row = mysqli_fetch_assoc($result)){

    $pins[] = $row;

}


/* =========================================================
   STATISTICS
========================================================= */

$totalProjects = count($pins);

$ongoing = 0;
$completed = 0;
$planned = 0;

foreach($pins as $pin){

    $status =
        strtolower(
            trim(
                $pin['status'] ?? ''
            )
        );


    if($status == 'ongoing'){
        $ongoing++;
    }

    if($status == 'completed'){
        $completed++;
    }

    if($status == 'planned'){
        $planned++;
    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>SK KONEXT — Community Map</title>

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>

<link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
/>

<style>

/* =========================================================
   ROOT
========================================================= */

:root{

    --bg:#07100d;
    --bg-soft:#0c1713;

    --glass:rgba(11,24,19,.78);
    --glass-light:rgba(255,255,255,.08);

    --border:rgba(255,255,255,.12);

    --text:#f5f8f6;
    --muted:#9ba9a2;

    --green:#62d59a;
    --green-dark:#183b2b;

    --danger:#ff7272;

    --shadow:
        0 24px 80px rgba(0,0,0,.35);

    --radius:24px;
}


/* =========================================================
   RESET
========================================================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

html,
body{
    width:100%;
    height:100%;
    overflow:hidden;
}

body{
    font-family:"DM Sans",sans-serif;
    background:var(--bg);
    color:var(--text);
}


/* =========================================================
   MAP
========================================================= */

#map{
    position:fixed;
    inset:0;
    width:100%;
    height:100%;
    z-index:1;
}


.map-overlay{
    position:fixed;
    inset:0;
    z-index:2;
    pointer-events:none;

    background:
        linear-gradient(
            180deg,
            rgba(3,10,7,.72) 0%,
            rgba(3,10,7,0) 24%,
            rgba(3,10,7,0) 68%,
            rgba(3,10,7,.72) 100%
        );
}


/* =========================================================
   TOP BAR
========================================================= */

.topbar{
    position:fixed;
    z-index:20;

    top:22px;
    left:22px;
    right:22px;

    display:flex;
    justify-content:space-between;
    align-items:center;

    pointer-events:none;
}

.brand{
    pointer-events:auto;

    display:flex;
    align-items:center;
    gap:14px;

    padding:10px 16px 10px 11px;

    border:1px solid var(--border);

    border-radius:999px;

    background:rgba(7,16,13,.78);

    backdrop-filter:blur(22px);

    box-shadow:var(--shadow);
}

.brand-mark{
    width:42px;
    height:42px;

    border-radius:50%;

    display:flex;
    align-items:center;
    justify-content:center;

    background:var(--green);

    color:#082016;

    font-family:Manrope,sans-serif;
    font-size:15px;
    font-weight:800;
}

.brand-text{
    display:flex;
    flex-direction:column;
    line-height:1.1;
}

.brand-name{
    font-family:Manrope,sans-serif;
    font-weight:800;
    letter-spacing:-.4px;
}

.brand-sub{
    margin-top:4px;

    font-size:10px;
    color:var(--muted);

    letter-spacing:1.2px;
    text-transform:uppercase;
}


/* =========================================================
   TOP ACTIONS
========================================================= */

.top-actions{
    pointer-events:auto;

    display:flex;
    gap:9px;
}

.top-btn{
    border:1px solid var(--border);

    background:rgba(7,16,13,.78);

    color:var(--text);

    text-decoration:none;

    padding:13px 17px;

    border-radius:999px;

    font-size:12px;
    font-weight:700;

    backdrop-filter:blur(22px);

    transition:.25s ease;
}

.top-btn:hover{
    background:rgba(255,255,255,.12);
    border-color:rgba(255,255,255,.2);
    transform:translateY(-2px);
}

.top-btn.primary{
    background:var(--green);
    color:#062015;
    border-color:var(--green);
}


/* =========================================================
   LOCATION LABEL
========================================================= */

.location-label{
    position:fixed;
    z-index:10;

    top:105px;
    left:25px;

    pointer-events:none;
}

.location-label span{
    display:block;

    color:var(--green);

    font-size:10px;
    font-weight:800;

    text-transform:uppercase;
    letter-spacing:2px;

    margin-bottom:7px;
}

.location-label h1{
    font-family:Manrope,sans-serif;

    font-size:clamp(27px,3vw,46px);

    line-height:.95;

    letter-spacing:-2px;

    max-width:400px;
}

.location-label p{
    margin-top:10px;

    color:#c0cbc5;

    font-size:12px;
}


/* =========================================================
   STAT PILLS
========================================================= */

.stats{
    position:fixed;
    z-index:15;

    top:105px;
    right:25px;

    display:flex;
    gap:8px;

    flex-wrap:wrap;
    justify-content:flex-end;

    max-width:500px;
}

.stat{
    min-width:105px;

    padding:12px 15px;

    border:1px solid var(--border);

    border-radius:18px;

    background:rgba(7,16,13,.76);

    backdrop-filter:blur(20px);

    box-shadow:0 10px 40px rgba(0,0,0,.18);
}

.stat-number{
    font-family:Manrope,sans-serif;

    font-size:20px;
    font-weight:800;

    line-height:1;
}

.stat-label{
    margin-top:5px;

    color:var(--muted);

    font-size:9px;

    text-transform:uppercase;
    letter-spacing:1px;
}


/* =========================================================
   FLOATING CONTROLS
========================================================= */

.floating-controls{
    position:fixed;
    z-index:30;

    right:25px;
    bottom:25px;

    display:flex;
    gap:9px;
}

.control-btn{
    border:1px solid var(--border);

    background:rgba(7,16,13,.86);

    color:var(--text);

    padding:14px 18px;

    border-radius:999px;

    font-family:inherit;

    font-size:12px;
    font-weight:700;

    cursor:pointer;

    backdrop-filter:blur(22px);

    transition:.25s ease;
}

.control-btn:hover{
    transform:translateY(-3px);
    background:rgba(255,255,255,.12);
}

.control-btn.add{
    background:var(--green);
    border-color:var(--green);
    color:#062015;
}


/* =========================================================
   DRAWER
========================================================= */

.drawer{
    position:fixed;
    z-index:50;

    top:90px;
    right:25px;

    width:390px;

    max-height:
        calc(100vh - 125px);

    overflow-y:auto;

    padding:23px;

    border:1px solid var(--border);

    border-radius:28px;

    background:
        linear-gradient(
            145deg,
            rgba(13,29,23,.96),
            rgba(6,15,11,.94)
        );

    backdrop-filter:blur(30px);

    box-shadow:
        0 30px 100px rgba(0,0,0,.5);

    transform:translateX(120%);

    opacity:0;

    transition:
        transform .45s cubic-bezier(.22,1,.36,1),
        opacity .3s ease;
}

.drawer.open{
    transform:translateX(0);
    opacity:1;
}

.drawer::-webkit-scrollbar{
    width:5px;
}

.drawer::-webkit-scrollbar-thumb{
    background:rgba(255,255,255,.15);
    border-radius:20px;
}


/* =========================================================
   DRAWER HEADER
========================================================= */

.drawer-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;

    margin-bottom:22px;
}

.drawer-title small{
    color:var(--green);

    font-size:9px;
    font-weight:800;

    text-transform:uppercase;
    letter-spacing:1.5px;
}

.drawer-title h2{
    margin-top:5px;

    font-family:Manrope,sans-serif;

    font-size:22px;

    letter-spacing:-.7px;
}

.close-btn{
    width:34px;
    height:34px;

    border-radius:50%;

    border:1px solid var(--border);

    background:rgba(255,255,255,.06);

    color:white;

    cursor:pointer;

    font-size:18px;
}


/* =========================================================
   FORM
========================================================= */

.form-group{
    margin-bottom:14px;
}

.form-group label{
    display:block;

    margin-bottom:7px;

    color:#c4d0ca;

    font-size:10px;
    font-weight:700;

    text-transform:uppercase;
    letter-spacing:.9px;
}

.form-group input,
.form-group textarea,
.form-group select{

    width:100%;

    border:1px solid rgba(255,255,255,.1);

    background:rgba(255,255,255,.055);

    color:white;

    border-radius:13px;

    padding:12px 13px;

    outline:none;

    font-family:inherit;

    font-size:12px;

    transition:.2s ease;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus{

    border-color:rgba(98,213,154,.6);

    background:rgba(255,255,255,.075);

    box-shadow:
        0 0 0 3px rgba(98,213,154,.08);
}

.form-group textarea{
    min-height:90px;
    resize:vertical;
}

.form-group select option{
    background:#102019;
    color:white;
}

.two-columns{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
}

.file-input{
    padding:10px !important;
}

.coordinates{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
}

.coordinate-box{
    position:relative;
}

.coordinate-box small{
    position:absolute;

    right:11px;
    top:50%;

    transform:translateY(-50%);

    color:#718079;

    font-size:9px;
}

.save-btn{

    width:100%;

    margin-top:5px;

    padding:14px;

    border:none;

    border-radius:14px;

    background:var(--green);

    color:#062015;

    font-family:Manrope,sans-serif;

    font-size:12px;
    font-weight:800;

    cursor:pointer;

    transition:.25s ease;
}

.save-btn:hover{
    transform:translateY(-2px);

    box-shadow:
        0 12px 30px rgba(98,213,154,.18);
}

.helper{
    margin-top:9px;

    color:#7f8d86;

    font-size:10px;

    line-height:1.5;
}


/* =========================================================
   PROJECT PANEL
========================================================= */

.projects-panel{

    position:fixed;

    z-index:25;

    left:25px;
    right:25px;
    bottom:25px;

    max-height:230px;

    padding:16px;

    border:1px solid var(--border);

    border-radius:25px;

    background:rgba(7,16,13,.86);

    backdrop-filter:blur(25px);

    box-shadow:var(--shadow);

    transform:translateY(
        calc(100% + 40px)
    );

    opacity:0;

    transition:
        transform .45s cubic-bezier(.22,1,.36,1),
        opacity .3s ease;
}

.projects-panel.open{
    transform:translateY(0);
    opacity:1;
}

.projects-header{
    display:flex;

    justify-content:space-between;
    align-items:center;

    margin-bottom:12px;
}

.projects-header h3{
    font-family:Manrope,sans-serif;

    font-size:14px;
}

.projects-header span{
    color:var(--muted);

    font-size:10px;
}

.project-strip{

    display:flex;

    gap:10px;

    overflow-x:auto;

    padding-bottom:3px;
}

.project-strip::-webkit-scrollbar{
    height:4px;
}

.project-strip::-webkit-scrollbar-thumb{
    background:rgba(255,255,255,.15);
    border-radius:20px;
}


/* =========================================================
   PROJECT CARD
========================================================= */

.project-card{

    flex:0 0 285px;

    min-height:135px;

    display:grid;

    grid-template-columns:95px 1fr;

    gap:12px;

    padding:9px;

    border:1px solid rgba(255,255,255,.09);

    border-radius:17px;

    background:rgba(255,255,255,.045);

    cursor:pointer;

    transition:.25s ease;
}

.project-card:hover{
    background:rgba(255,255,255,.08);

    transform:translateY(-2px);

    border-color:rgba(98,213,154,.25);
}

.project-image{
    width:95px;
    height:115px;

    border-radius:12px;

    overflow:hidden;

    background:#16231d;
}

.project-image img{
    width:100%;
    height:100%;

    object-fit:cover;
}

.project-image.empty{
    display:flex;

    align-items:center;
    justify-content:center;

    color:#68756e;

    font-size:9px;
}

.project-info{
    min-width:0;

    display:flex;
    flex-direction:column;
}

.project-number{
    color:var(--green);

    font-size:8px;
    font-weight:800;

    letter-spacing:1px;
}

.project-info h4{
    margin-top:4px;

    font-family:Manrope,sans-serif;

    font-size:13px;

    line-height:1.15;

    white-space:nowrap;

    overflow:hidden;

    text-overflow:ellipsis;
}

.project-info p{
    margin-top:6px;

    color:#8f9d96;

    font-size:9px;

    line-height:1.4;

    display:-webkit-box;
    -webkit-line-clamp:3;
    -webkit-box-orient:vertical;

    overflow:hidden;
}

.badges{
    display:flex;

    gap:5px;

    flex-wrap:wrap;

    margin-top:auto;
    padding-top:7px;
}

.badge{
    padding:4px 7px;

    border-radius:999px;

    background:rgba(98,213,154,.1);

    color:var(--green);

    font-size:8px;
    font-weight:700;
}

.badge.status{
    background:rgba(255,255,255,.08);
    color:#ccd5d0;
}

.project-actions{
    display:flex;

    gap:5px;

    margin-top:8px;
}

.project-actions a{

    flex:1;

    text-align:center;

    text-decoration:none;

    padding:6px 5px;

    border-radius:8px;

    font-size:8px;
    font-weight:700;

    background:rgba(255,255,255,.07);

    color:#dce4df;
}

.project-actions a:hover{
    background:rgba(255,255,255,.13);
}

.project-actions a.delete{
    color:#ff8b8b;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty-projects{

    width:100%;

    padding:25px;

    text-align:center;

    color:#7f8d86;

    font-size:11px;
}


/* =========================================================
   LEAFLET CUSTOMIZATION
========================================================= */

.leaflet-control-layers{

    border:none !important;

    border-radius:14px !important;

    overflow:hidden;

    box-shadow:
        0 10px 35px rgba(0,0,0,.3) !important;
}

.leaflet-control-layers-toggle{

    width:42px !important;
    height:42px !important;
}

.leaflet-control-zoom{

    border:none !important;
    box-shadow:none !important;
}

.leaflet-control-zoom a{

    width:38px !important;
    height:38px !important;

    line-height:38px !important;

    background:rgba(7,16,13,.86) !important;

    color:white !important;

    border:1px solid rgba(255,255,255,.1) !important;
}

.leaflet-control-zoom a:first-child{
    border-radius:12px 12px 0 0;
}

.leaflet-control-zoom a:last-child{
    border-radius:0 0 12px 12px;
}


/* =========================================================
   CUSTOM MARKER - STATUS COLORS + HOVER ANIMATION
========================================================= */

.project-marker{

    position:relative;

    width:34px;
    height:34px;

    border-radius:50% 50% 50% 0;

    transform:rotate(-45deg);

    border:3px solid #07100d;

    transition:
        transform .35s cubic-bezier(.22,1,.36,1),
        box-shadow .35s ease,
        filter .35s ease;

    cursor:pointer;
}


/* PLANNED - BLUE */

.project-marker.planned{

    background:#3B82F6;

    box-shadow:
        0 7px 20px rgba(59,130,246,.35);

}


/* ONGOING - YELLOW */

.project-marker.ongoing{

    background:#EAB308;

    box-shadow:
        0 7px 20px rgba(234,179,8,.35);

}


/* COMPLETED - GREEN */

.project-marker.completed{

    background:#22C55E;

    box-shadow:
        0 7px 20px rgba(34,197,94,.35);

}


/* MARKER CENTER */

.project-marker::after{

    content:"";

    position:absolute;

    width:9px;
    height:9px;

    border-radius:50%;

    background:#07100d;

    top:50%;
    left:50%;

    transform:
        translate(-50%,-50%);

}


/* HOVER ANIMATION */

.project-marker:hover,
.project-marker.is-hovered{

    transform:
        rotate(-45deg)
        scale(1.28)
        translateY(-3px);

    z-index:9999;

}


/* BLUE GLOW */

.project-marker.planned:hover,
.project-marker.planned.is-hovered{

    box-shadow:
        0 0 0 6px rgba(59,130,246,.15),
        0 0 22px 8px rgba(59,130,246,.55),
        0 12px 28px rgba(0,0,0,.4);

    filter:brightness(1.2);

}


/* YELLOW GLOW */

.project-marker.ongoing:hover,
.project-marker.ongoing.is-hovered{

    box-shadow:
        0 0 0 6px rgba(234,179,8,.15),
        0 0 22px 8px rgba(234,179,8,.55),
        0 12px 28px rgba(0,0,0,.4);

    filter:brightness(1.2);

}


/* GREEN GLOW */

.project-marker.completed:hover,
.project-marker.completed.is-hovered{

    box-shadow:
        0 0 0 6px rgba(34,197,94,.15),
        0 0 22px 8px rgba(34,197,94,.55),
        0 12px 28px rgba(0,0,0,.4);

    filter:brightness(1.2);

}


.project-popup{
    min-width:220px;

    font-family:"DM Sans",sans-serif;
}

.project-popup img{

    width:100%;
    height:105px;

    object-fit:cover;

    border-radius:10px;

    margin-bottom:8px;
}

.project-popup h3{

    margin:0 0 5px;

    font-family:Manrope,sans-serif;

    font-size:15px;
}

.project-popup p{

    margin:0 0 8px;

    font-size:11px;

    color:#68756e;
}

.popup-meta{

    display:flex;

    gap:5px;

    flex-wrap:wrap;

    margin-bottom:9px;
}

.popup-meta span{

    padding:4px 7px;

    border-radius:999px;

    background:#edf7f1;

    color:#24543b;

    font-size:9px;
}

.popup-actions{

    display:flex;

    gap:6px;
}

.popup-actions a{

    flex:1;

    text-align:center;

    padding:7px;

    border-radius:8px;

    text-decoration:none;

    background:#10291e;

    color:white;

    font-size:10px;
}

.popup-actions a.delete{
    background:#fff0f0;
    color:#b83838;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:900px){

    .stats{
        top:90px;
        left:20px;
        right:20px;

        justify-content:flex-start;

        max-width:none;
    }

    .stat{
        min-width:88px;
        padding:9px 11px;
    }

    .stat-number{
        font-size:16px;
    }

    .location-label{
        top:170px;
        left:20px;
    }

    .location-label h1{
        font-size:30px;
    }

    .drawer{
        left:15px;
        right:15px;

        width:auto;

        top:82px;
        bottom:15px;

        max-height:none;
    }

    .projects-panel{
        left:15px;
        right:15px;
        bottom:15px;
    }

    .floating-controls{
        right:15px;
        bottom:15px;
    }

}


@media(max-width:600px){

    .topbar{
        top:12px;
        left:12px;
        right:12px;
    }

    .brand{
        padding:7px 11px 7px 7px;
    }

    .brand-mark{
        width:35px;
        height:35px;
        font-size:11px;
    }

    .brand-sub{
        display:none;
    }

    .top-btn{
        padding:10px 12px;
        font-size:10px;
    }

    .location-label{
        top:155px;
    }

    .location-label p{
        font-size:10px;
    }

    .stats{
        top:88px;
    }

    .stat{
        min-width:74px;
    }

    .stat-label{
        font-size:7px;
    }

    .floating-controls{
        left:15px;
        right:15px;

        justify-content:flex-end;
    }

    .control-btn{
        padding:12px 14px;
    }

    .two-columns,
    .coordinates{
        grid-template-columns:1fr;
    }

}

</style>

</head>


<body>


<!-- =====================================================
     MAP
===================================================== -->

<div id="map"></div>

<div class="map-overlay"></div>


<!-- =====================================================
     TOP BAR
===================================================== -->

<div class="topbar">

    <div class="brand">

        <div class="brand-mark">
            SK
        </div>

        <div class="brand-text">

            <div class="brand-name">
                SK KONEXT
            </div>

            <div class="brand-sub">
                Superadmin GeoMap
            </div>

        </div>

    </div>


    <div class="top-actions">

        <a
            href="dashboard.php"
            class="top-btn"
        >
            Dashboard
        </a>

    </div>

</div>


<!-- =====================================================
     LOCATION
===================================================== -->

<div class="location-label">

    <span>
        Community Intelligence
    </span>

    <h1>
        Naic<br>
        Project Map
    </h1>

    <p>
        Manage and monitor SK community projects.
    </p>

</div>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="stats">

    <div class="stat">

        <div class="stat-number">
            <?= $totalProjects ?>
        </div>

        <div class="stat-label">
            Total Projects
        </div>

    </div>


    <div class="stat">

        <div class="stat-number">
            <?= $ongoing ?>
        </div>

        <div class="stat-label">
            Ongoing
        </div>

    </div>


    <div class="stat">

        <div class="stat-number">
            <?= $completed ?>
        </div>

        <div class="stat-label">
            Completed
        </div>

    </div>


    <div class="stat">

        <div class="stat-number">
            <?= $planned ?>
        </div>

        <div class="stat-label">
            Planned
        </div>

    </div>

</div>


<!-- =====================================================
     ADD PROJECT DRAWER
===================================================== -->

<div
    class="drawer"
    id="addDrawer"
>

    <div class="drawer-header">

        <div class="drawer-title">

            <small>
                Superadmin Control
            </small>

            <h2>
                Add Community Project
            </h2>

        </div>

        <button
            type="button"
            class="close-btn"
            onclick="toggleAddDrawer()"
        >
            ×
        </button>

    </div>


    <form
        method="POST"
        enctype="multipart/form-data"
        id="projectForm"
    >


        <!-- TITLE -->

        <div class="form-group">

            <label>
                Project Title
            </label>

            <input
                type="text"
                name="title"
                placeholder="Example: Youth Basketball League"
                required
            >

        </div>


        <!-- DESCRIPTION -->

        <div class="form-group">

            <label>
                Description
            </label>

            <textarea
                name="description"
                placeholder="Describe the community project..."
                required
            ></textarea>

        </div>


        <!-- BARANGAY -->

        <div class="form-group">

            <label>
                Barangay
            </label>

            <select
                name="barangay"
                required
            >

                <option value="">
                    Select Barangay
                </option>

                <?php foreach($barangays as $barangay): ?>

                    <option
                        value="<?= htmlspecialchars($barangay['name']) ?>"
                    >
                        <?= htmlspecialchars($barangay['name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- CATEGORY -->

        <div class="form-group">

            <label>
                Center of Youth Participation
            </label>

            <select name="category" required>

                <option value="">Select Center of Youth Participation</option>
                <option value="Health">Health</option>
                <option value="Education">Education</option>
                <option value="Economic Empowerment">Economic Empowerment</option>
                <option value="Social Inclusion and Equity">Social Inclusion and Equity</option>
                <option value="Peace-Building and Security">Peace-Building and Security</option>
                <option value="Governance">Governance</option>
                <option value="Active Citizenship">Active Citizenship</option>
                <option value="Environment">Environment</option>
                <option value="Global Mobility">Global Mobility</option>
                <option value="Agriculture">Agriculture</option>

            </select>

        </div>


        <!-- STATUS -->

        <div class="form-group">

            <label>
                Status
            </label>

            <select name="status" required>

                <option value="">Select status</option>
                <option value="Planned">Planned</option>
                <option value="Ongoing">Ongoing</option>
                <option value="Completed">Completed</option>

            </select>

        </div>


        <!-- COORDINATES -->

        <div class="form-group">

            <label>
                Project Location
            </label>

            <div class="coordinates">

                <div class="coordinate-box">

                    <input
                        type="number"
                        step="any"
                        name="lat"
                        id="lat"
                        placeholder="Latitude"
                        required
                    >

                    <small>
                        LAT
                    </small>

                </div>


                <div class="coordinate-box">

                    <input
                        type="number"
                        step="any"
                        name="lng"
                        id="lng"
                        placeholder="Longitude"
                        required
                    >

                    <small>
                        LNG
                    </small>

                </div>

            </div>

        </div>


        <!-- IMAGE -->

        <div class="form-group">

            <label>
                Project Image
            </label>

            <input
                type="file"
                name="image"
                class="file-input"
                accept="image/jpeg,image/png,image/webp"
            >

        </div>


        <button
            type="submit"
            name="add"
            class="save-btn"
        >
            Save Community Project
        </button>


        <div class="helper">

            Tip: You can also click anywhere on the Naic map
            to select the project location automatically.

        </div>

    </form>

</div>


<!-- =====================================================
     PROJECTS PANEL
===================================================== -->

<div
    class="projects-panel"
    id="projectsPanel"
>

    <div class="projects-header">

        <h3>
            Community Projects
        </h3>

        <span>
            <?= $totalProjects ?> locations mapped
        </span>

    </div>


    <div class="project-strip">

        <?php if(empty($pins)): ?>

            <div class="empty-projects">
                No community projects have been added yet.
            </div>

        <?php else: ?>


            <?php foreach($pins as $index => $pin): ?>

                <div
                    class="project-card"
                    onclick="focusProject(
                        <?= (int)$pin['id'] ?>,
                        <?= (float)$pin['latitude'] ?>,
                        <?= (float)$pin['longitude'] ?>
                    )"
                >


                    <div class="project-image">

                        <?php if(!empty($pin['image'])): ?>

                            <img
                                src="../uploads/maps/<?= htmlspecialchars($pin['image']) ?>"
                                alt="<?= htmlspecialchars($pin['title']) ?>"
                            >

                        <?php else: ?>

                            <div class="project-image empty">
                                NO IMAGE
                            </div>

                        <?php endif; ?>

                    </div>


                    <div class="project-info">

                        <div class="project-number">
                            PROJECT
                            <?= str_pad(
                                $index + 1,
                                2,
                                '0',
                                STR_PAD_LEFT
                            ) ?>
                        </div>


                        <h4>
                            <?= htmlspecialchars(
                                $pin['title']
                            ) ?>
                        </h4>


                        <p>
                            <?= htmlspecialchars(
                                $pin['description']
                            ) ?>
                        </p>


                        <div class="badges">

                            <?php if(!empty($pin['barangay'])): ?>

                                <span class="badge">
                                    <?= htmlspecialchars(
                                        $pin['barangay']
                                    ) ?>
                                </span>

                            <?php endif; ?>


                            <?php if(!empty($pin['category'])): ?>

                                <span class="badge">
                                    <?= htmlspecialchars(
                                        $pin['category']
                                    ) ?>
                                </span>

                            <?php endif; ?>


                            <?php if(!empty($pin['status'])): ?>

                                <span class="badge status">
                                    <?= htmlspecialchars(
                                        $pin['status']
                                    ) ?>
                                </span>

                            <?php endif; ?>

                        </div>


                        <div class="project-actions">

                            <a
                                href="edit_map.php?id=<?= (int)$pin['id'] ?>"
                                onclick="event.stopPropagation();"
                            >
                                Edit
                            </a>


                            <a
                                href="?delete=<?= (int)$pin['id'] ?>"
                                class="delete"
                                onclick="
                                    event.stopPropagation();
                                    return confirm('Delete this project?');
                                "
                            >
                                Delete
                            </a>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>


<!-- =====================================================
     FLOATING CONTROLS
===================================================== -->

<div class="floating-controls">

    <button
        type="button"
        class="control-btn"
        onclick="toggleProjects()"
    >
        Projects
    </button>


    <button
        type="button"
        class="control-btn add"
        onclick="toggleAddDrawer()"
    >
        + Add Project
    </button>

</div>


<!-- =====================================================
     LEAFLET
===================================================== -->

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>


<script>

/* =========================================================
   MAP INITIALIZATION
========================================================= */

const map = L.map("map", {

    center:[
        <?= $naicCenterLat ?>,
        <?= $naicCenterLng ?>
    ],

    zoom:14,

    minZoom:12,
    maxZoom:18,

    maxBounds:[
        [<?= $minLat ?>, <?= $minLng ?>],
        [<?= $maxLat ?>, <?= $maxLng ?>]
    ],

    maxBoundsViscosity:1.0,

    zoomControl:false

});


/* =========================================================
   MAP LAYERS
========================================================= */

const street = L.tileLayer(

    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",

    {
        maxZoom:19,
        attribution:"© OpenStreetMap"
    }

);


const satellite = L.tileLayer(

    "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",

    {
        maxZoom:19,
        attribution:"Tiles © Esri"
    }

);


street.addTo(map);


L.control.zoom({
    position:"bottomright"
}).addTo(map);


L.control.layers(

    {
        "Street":street,
        "Satellite":satellite
    },

    null,

    {
        position:"topright",
        collapsed:true
    }

).addTo(map);


/* =========================================================
   NAIC BOUNDARY
========================================================= */

const naicBoundary = L.rectangle(

    [
        [<?= $minLat ?>, <?= $minLng ?>],
        [<?= $maxLat ?>, <?= $maxLng ?>]
    ],

    {
        color:"#62d59a",
        weight:1,
        opacity:.15,
        fillColor:"#62d59a",
        fillOpacity:.02,
        interactive:false
    }

).addTo(map);


/* =========================================================
   CUSTOM MARKER - STATUS COLORS
========================================================= */

function getProjectStatus(status){

    const value = String(status || "")
        .trim()
        .toLowerCase();

    if(value === "planned"){
        return "planned";
    }

    if(value === "ongoing"){
        return "ongoing";
    }

    if(value === "completed"){
        return "completed";
    }

    return "planned";

}


function createMarkerIcon(status){

    const statusClass = getProjectStatus(status);

    return L.divIcon({

        className:"",

        html:`
            <div class="project-marker ${statusClass}"></div>
        `,

        iconSize:[34,34],

        iconAnchor:[17,34],

        popupAnchor:[0,-30]

    });

}


/* =========================================================
   PROJECT MARKERS
========================================================= */

const projectMarkers = {};


const projects = <?= json_encode(
    $pins,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
) ?>;


function escapeHtml(value){

    if(
        value === null ||
        value === undefined
    ){

        return "";

    }

    return String(value)
        .replace(/&/g,"&amp;")
        .replace(/</g,"&lt;")
        .replace(/>/g,"&gt;")
        .replace(/"/g,"&quot;")
        .replace(/'/g,"&#039;");

}


projects.forEach(function(project){

    const lat =
        parseFloat(
            project.latitude
        );

    const lng =
        parseFloat(
            project.longitude
        );


    if(
        isNaN(lat) ||
        isNaN(lng)
    ){

        return;

    }


    let imageHTML = "";


    if(project.image){

        imageHTML = `

            <img
                src="../uploads/maps/${escapeHtml(project.image)}"
                alt=""
            >

        `;

    }


    const popupHTML = `

        <div class="project-popup">

            ${imageHTML}

            <h3>
                ${escapeHtml(project.title)}
            </h3>

            <p>
                ${escapeHtml(project.description)}
            </p>

            <div class="popup-meta">

                ${
                    project.barangay
                    ?
                    `<span>
                        Barangay:
                        ${escapeHtml(project.barangay)}
                    </span>`
                    :
                    ""
                }

                ${
                    project.category
                    ?
                    `<span>
                        ${escapeHtml(project.category)}
                    </span>`
                    :
                    ""
                }

                ${
                    project.status
                    ?
                    `<span>
                        ${escapeHtml(project.status)}
                    </span>`
                    :
                    ""
                }

            </div>

            <div class="popup-actions">

                <a
                    href="edit_map.php?id=${project.id}"
                >
                    Edit
                </a>

                <a
                    href="?delete=${project.id}"
                    class="delete"
                    onclick="
                        return confirm('Delete this project?');
                    "
                >
                    Delete
                </a>

            </div>

        </div>

    `;


    /* CREATE STATUS-BASED MARKER */

    const marker = L.marker(

        [lat,lng],

        {
            icon:createMarkerIcon(project.status)
        }

    ).addTo(map);


    marker.bindPopup(
        popupHTML
    );


    /* =====================================================
       HOVER ANIMATION
    ===================================================== */

    marker.on("mouseover",function(){

        const element = marker.getElement();

        if(element){

            const markerShape =
                element.querySelector(".project-marker");

            if(markerShape){

                markerShape.classList.add("is-hovered");

            }

        }

    });


    marker.on("mouseout",function(){

        const element = marker.getElement();

        if(element){

            const markerShape =
                element.querySelector(".project-marker");

            if(markerShape){

                markerShape.classList.remove("is-hovered");

            }

        }

    });


    projectMarkers[
        project.id
    ] = marker;

});


/* =========================================================
   TEMP LOCATION MARKER
========================================================= */

let tempMarker = null;


function enablePlaceMode(){

    map.once(
        "click",
        function(e){

            const lat =
                e.latlng.lat;

            const lng =
                e.latlng.lng;


            document.getElementById(
                "lat"
            ).value =
                lat.toFixed(7);


            document.getElementById(
                "lng"
            ).value =
                lng.toFixed(7);


            if(tempMarker){

                tempMarker.remove();

            }


            tempMarker = L.marker(

                [lat,lng],

                {
                    draggable:true
                }

            ).addTo(map);


            tempMarker.bindPopup(
                "New project location"
            ).openPopup();


            tempMarker.on(
                "dragend",
                function(event){

                    const position =
                        event.target.getLatLng();


                    document.getElementById(
                        "lat"
                    ).value =
                        position.lat.toFixed(7);


                    document.getElementById(
                        "lng"
                    ).value =
                        position.lng.toFixed(7);

                }
            );


            document.getElementById(
                "addDrawer"
            ).classList.add("open");

        }
    );

}


/* =========================================================
   CLICK MAP TO PLACE PROJECT
========================================================= */

map.on(
    "click",
    function(){

        enablePlaceMode();

    }
);


/* =========================================================
   FOCUS PROJECT
========================================================= */

function focusProject(
    id,
    lat,
    lng
){

    map.flyTo(

        [lat,lng],

        17,

        {
            duration:1.2
        }

    );


    setTimeout(
        function(){

            if(
                projectMarkers[id]
            ){

                projectMarkers[id]
                    .openPopup();

            }

        },
        700
    );

}


/* =========================================================
   ADD DRAWER
========================================================= */

function toggleAddDrawer(){

    const drawer =
        document.getElementById(
            "addDrawer"
        );

    drawer.classList.toggle(
        "open"
    );

}


/* =========================================================
   PROJECT PANEL
========================================================= */

function toggleProjects(){

    const panel =
        document.getElementById(
            "projectsPanel"
        );

    panel.classList.toggle(
        "open"
    );

}


/* =========================================================
   FORM VALIDATION
========================================================= */

document
    .getElementById(
        "projectForm"
    )
    .addEventListener(
        "submit",
        function(e){

            const barangay =
                document.querySelector(
                    'select[name="barangay"]'
                ).value;


            if(!barangay){

                e.preventDefault();

                alert(
                    "Please select a barangay."
                );

                return;

            }


            const lat =
                parseFloat(
                    document.getElementById(
                        "lat"
                    ).value
                );


            const lng =
                parseFloat(
                    document.getElementById(
                        "lng"
                    ).value
                );


            if(
                isNaN(lat) ||
                isNaN(lng)
            ){

                e.preventDefault();

                alert(
                    "Please select a project location on the map."
                );

                return;

            }


            if(
                lat < <?= $minLat ?> ||
                lat > <?= $maxLat ?> ||
                lng < <?= $minLng ?> ||
                lng > <?= $maxLng ?>
            ){

                e.preventDefault();

                alert(
                    "Please select a location within the Naic map area."
                );

            }

        }
    );


/* =========================================================
   INITIAL MAP VIEW
========================================================= */

map.setView(

    [
        <?= $naicCenterLat ?>,
        <?= $naicCenterLng ?>
    ],

    14

);

</script>


</body>
</html>
