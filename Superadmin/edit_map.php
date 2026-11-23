```php
<?php

include("../db.php");
include("../includes/auth.php");

allowRoles(['superadmin']);


/* =========================================================
   CHECK PROJECT ID
========================================================= */

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){

    header("Location: map.php");
    exit;

}

$id = (int) $_GET['id'];


/* =========================================================
   LOAD PROJECT
========================================================= */

$query = mysqli_query(
    $conn,
    "SELECT *
     FROM map_projects
     WHERE id = $id
     LIMIT 1"
);

$data = mysqli_fetch_assoc($query);


if(!$data){

    header("Location: map.php");
    exit;

}


/* =========================================================
   LOAD BARANGAYS
========================================================= */

$barangays = [];

$barangayQuery = mysqli_query(
    $conn,
    "SELECT id, name
     FROM barangays
     ORDER BY name ASC"
);

if($barangayQuery){

    while($row = mysqli_fetch_assoc($barangayQuery)){

        $barangays[] = $row;

    }

}


/* =========================================================
   IMAGE UPLOAD FUNCTION
========================================================= */

function uploadImage($file){

    $targetDir = "../uploads/maps/";

    if(!is_dir($targetDir)){

        mkdir(
            $targetDir,
            0777,
            true
        );

    }


    $allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];


    if(
        !isset($file['type']) ||
        !in_array(
            $file['type'],
            $allowedTypes
        )
    ){

        return null;

    }


    if(
        isset($file['size']) &&
        $file['size'] > 5 * 1024 * 1024
    ){

        return null;

    }


    $extension = strtolower(
        pathinfo(
            $file['name'],
            PATHINFO_EXTENSION
        )
    );


    $fileName =
        time() .
        "_" .
        uniqid() .
        "." .
        $extension;


    $targetFile =
        $targetDir .
        $fileName;


    if(
        move_uploaded_file(
            $file['tmp_name'],
            $targetFile
        )
    ){

        return $fileName;

    }


    return null;

}


/* =========================================================
   UPDATE PROJECT
========================================================= */

$error = "";
$success = "";


if(isset($_POST['update'])){

    $title = trim(
        $_POST['title'] ?? ''
    );

    $description = trim(
        $_POST['description'] ?? ''
    );

    $barangay = trim(
        $_POST['barangay'] ?? ''
    );

    $category = trim(
        $_POST['category'] ?? ''
    );

    $status = trim(
        $_POST['status'] ?? ''
    );

    $lat = floatval(
        $_POST['lat'] ?? 0
    );

    $lng = floatval(
        $_POST['lng'] ?? 0
    );


    /* =====================================================
       VALIDATION
    ===================================================== */

    if($title === ''){

        $error = "Project title is required.";

    }

    elseif($description === ''){

        $error = "Project description is required.";

    }

    elseif($barangay === ''){

        $error = "Please select a barangay.";

    }

    elseif($category === ''){

        $error = "Please select a category.";

    }

    elseif($status === ''){

        $error = "Please select a project status.";

    }

    elseif(
        $lat < 14.25 ||
        $lat > 14.42 ||
        $lng < 120.70 ||
        $lng > 120.86
    ){

        $error =
            "Please select a location within the Naic map area.";

    }


    /* =====================================================
       SAVE UPDATE
    ===================================================== */

    if($error === ''){

        $titleSQL = mysqli_real_escape_string(
            $conn,
            $title
        );

        $descriptionSQL = mysqli_real_escape_string(
            $conn,
            $description
        );

        $barangaySQL = mysqli_real_escape_string(
            $conn,
            $barangay
        );

        $categorySQL = mysqli_real_escape_string(
            $conn,
            $category
        );

        $statusSQL = mysqli_real_escape_string(
            $conn,
            $status
        );


        /* =================================================
           CHECK NEW IMAGE
        ================================================= */

        $newImage = null;

        if(
            isset($_FILES['image']) &&
            !empty($_FILES['image']['name'])
        ){

            $newImage =
                uploadImage(
                    $_FILES['image']
                );


            if(!$newImage){

                $error =
                    "Invalid image. Please use JPG, PNG, or WEBP under 5MB.";

            }

        }


        /* =================================================
           UPDATE DATABASE
        ================================================= */

        if($error === ''){

            if($newImage){

                $newImageSQL =
                    mysqli_real_escape_string(
                        $conn,
                        $newImage
                    );


                $updateQuery = mysqli_query(
                    $conn,
                    "
                    UPDATE map_projects
                    SET
                        title='$titleSQL',
                        description='$descriptionSQL',
                        barangay='$barangaySQL',
                        latitude='$lat',
                        longitude='$lng',
                        image='$newImageSQL',
                        category='$categorySQL',
                        status='$statusSQL'
                    WHERE id=$id
                    "
                );


                /* =========================================
                   DELETE OLD IMAGE
                ========================================= */

                if($updateQuery){

                    if(
                        !empty($data['image'])
                    ){

                        $oldImage =
                            "../uploads/maps/" .
                            $data['image'];


                        if(
                            file_exists($oldImage)
                        ){

                            @unlink(
                                $oldImage
                            );

                        }

                    }

                }

            }

            else{

                $updateQuery = mysqli_query(
                    $conn,
                    "
                    UPDATE map_projects
                    SET
                        title='$titleSQL',
                        description='$descriptionSQL',
                        barangay='$barangaySQL',
                        latitude='$lat',
                        longitude='$lng',
                        category='$categorySQL',
                        status='$statusSQL'
                    WHERE id=$id
                    "
                );

            }


            if($updateQuery){

                header(
                    "Location: map.php"
                );

                exit;

            }

            else{

                $error =
                    "Unable to update the project.";

            }

        }

    }

}


/* =========================================================
   ESCAPE DISPLAY VALUES
========================================================= */

$currentTitle =
    htmlspecialchars(
        $data['title'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    );

$currentDescription =
    htmlspecialchars(
        $data['description'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    );

$currentBarangay =
    $data['barangay'] ?? '';

$currentCategory =
    $data['category'] ?? '';

$currentStatus =
    $data['status'] ?? '';

$currentLat =
    htmlspecialchars(
        $data['latitude'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    );

$currentLng =
    htmlspecialchars(
        $data['longitude'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    );

$currentImage =
    $data['image'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Edit Project | SK KONEXT
</title>


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

    --panel:#0c1713;

    --border:rgba(255,255,255,.12);

    --text:#f5f8f6;

    --muted:#9ba9a2;

    --green:#62d59a;

    --danger:#ff7272;

    --shadow:
        0 30px 100px rgba(0,0,0,.45);

}


/* =========================================================
   RESET
========================================================= */

*{

    margin:0;

    padding:0;

    box-sizing:border-box;

}


body{

    min-height:100vh;

    padding:40px 20px;

    font-family:
        "DM Sans",
        sans-serif;

    background:

        radial-gradient(
            circle at top right,
            rgba(98,213,154,.08),
            transparent 35%
        ),

        var(--bg);

    color:var(--text);

}


/* =========================================================
   CONTAINER
========================================================= */

.page{

    width:100%;

    max-width:760px;

    margin:auto;

}


/* =========================================================
   HEADER
========================================================= */

.top{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:20px;

    margin-bottom:20px;

}


.brand{

    display:flex;

    align-items:center;

    gap:12px;

}


.brand-mark{

    width:44px;

    height:44px;

    border-radius:50%;

    display:flex;

    align-items:center;

    justify-content:center;

    background:var(--green);

    color:#062015;

    font-family:Manrope,sans-serif;

    font-size:14px;

    font-weight:800;

}


.brand-text h1{

    font-family:Manrope,sans-serif;

    font-size:17px;

}


.brand-text p{

    margin-top:3px;

    color:var(--muted);

    font-size:10px;

    text-transform:uppercase;

    letter-spacing:1px;

}


/* =========================================================
   BACK BUTTON
========================================================= */

.back-btn{

    padding:10px 15px;

    border:1px solid var(--border);

    border-radius:999px;

    background:rgba(255,255,255,.05);

    color:white;

    text-decoration:none;

    font-size:11px;

    font-weight:700;

    transition:.2s ease;

}


.back-btn:hover{

    background:rgba(255,255,255,.1);

    transform:translateY(-2px);

}


/* =========================================================
   PANEL
========================================================= */

.panel{

    padding:28px;

    border:1px solid var(--border);

    border-radius:28px;

    background:

        linear-gradient(
            145deg,
            rgba(13,29,23,.96),
            rgba(6,15,11,.94)
        );

    box-shadow:var(--shadow);

    backdrop-filter:blur(25px);

}


.panel-header{

    margin-bottom:25px;

}


.panel-header small{

    color:var(--green);

    font-size:9px;

    font-weight:800;

    text-transform:uppercase;

    letter-spacing:1.5px;

}


.panel-header h2{

    margin-top:6px;

    font-family:Manrope,sans-serif;

    font-size:27px;

    letter-spacing:-1px;

}


.panel-header p{

    margin-top:7px;

    color:var(--muted);

    font-size:12px;

}


/* =========================================================
   ALERT
========================================================= */

.alert{

    margin-bottom:18px;

    padding:12px 14px;

    border-radius:13px;

    font-size:11px;

    line-height:1.5;

}


.alert.error{

    background:rgba(255,114,114,.1);

    border:1px solid rgba(255,114,114,.2);

    color:#ffabab;

}


/* =========================================================
   FORM
========================================================= */

.form-group{

    margin-bottom:16px;

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

    padding:13px 14px;

    border:1px solid rgba(255,255,255,.1);

    border-radius:13px;

    outline:none;

    background:rgba(255,255,255,.055);

    color:white;

    font-family:inherit;

    font-size:12px;

    transition:.2s ease;

}


.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus{

    border-color:
        rgba(98,213,154,.6);

    background:
        rgba(255,255,255,.075);

    box-shadow:
        0 0 0 3px
        rgba(98,213,154,.08);

}


.form-group textarea{

    min-height:120px;

    resize:vertical;

}


.form-group select option{

    background:#102019;

    color:white;

}


.two-columns{

    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:12px;

}


/* =========================================================
   COORDINATES
========================================================= */

.coordinates{

    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:12px;

}


/* =========================================================
   CURRENT IMAGE
========================================================= */

.current-image{

    margin-bottom:15px;

}


.current-image label{

    display:block;

    margin-bottom:7px;

    color:#c4d0ca;

    font-size:10px;

    font-weight:700;

    text-transform:uppercase;

    letter-spacing:.9px;

}


.current-image img{

    display:block;

    width:100%;

    max-width:260px;

    height:150px;

    object-fit:cover;

    border-radius:14px;

    border:1px solid var(--border);

}


.no-image{

    width:260px;

    height:120px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:14px;

    border:1px solid var(--border);

    background:rgba(255,255,255,.04);

    color:var(--muted);

    font-size:10px;

}


/* =========================================================
   FILE INPUT
========================================================= */

.file-input{

    padding:10px !important;

}


/* =========================================================
   HELPER
========================================================= */

.helper{

    margin-top:7px;

    color:#7f8d86;

    font-size:10px;

    line-height:1.5;

}


/* =========================================================
   BUTTONS
========================================================= */

.actions{

    display:flex;

    gap:10px;

    margin-top:22px;

}


.update-btn{

    flex:1;

    padding:14px;

    border:none;

    border-radius:14px;

    background:var(--green);

    color:#062015;

    font-family:Manrope,sans-serif;

    font-size:12px;

    font-weight:800;

    cursor:pointer;

    transition:.2s ease;

}


.update-btn:hover{

    transform:translateY(-2px);

    box-shadow:
        0 12px 30px
        rgba(98,213,154,.18);

}


.cancel-btn{

    padding:14px 20px;

    border:1px solid var(--border);

    border-radius:14px;

    background:rgba(255,255,255,.05);

    color:white;

    text-decoration:none;

    font-size:12px;

    font-weight:700;

    text-align:center;

}


.cancel-btn:hover{

    background:rgba(255,255,255,.1);

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:600px){

    body{

        padding:20px 12px;

    }


    .top{

        align-items:flex-start;

    }


    .panel{

        padding:20px;

        border-radius:22px;

    }


    .panel-header h2{

        font-size:23px;

    }


    .two-columns,
    .coordinates{

        grid-template-columns:1fr;

    }


    .actions{

        flex-direction:column;

    }

}

</style>

</head>


<body>


<div class="page">


    <!-- =====================================================
         TOP
    ====================================================== -->

    <div class="top">

        <div class="brand">

            <div class="brand-mark">
                SK
            </div>

            <div class="brand-text">

                <h1>
                    SK KONEXT
                </h1>

                <p>
                    Superadmin GeoMap
                </p>

            </div>

        </div>


        <a
            href="map.php"
            class="back-btn"
        >
            ← Back to Map
        </a>

    </div>


    <!-- =====================================================
         PANEL
    ====================================================== -->

    <div class="panel">


        <div class="panel-header">

            <small>
                Superadmin Control
            </small>

            <h2>
                Edit Community Project
            </h2>

            <p>
                Update the project information and map location.
            </p>

        </div>


        <?php if($error !== ''): ?>

            <div class="alert error">

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- =================================================
                 TITLE
            ================================================== -->

            <div class="form-group">

                <label>
                    Project Title
                </label>

                <input
                    type="text"
                    name="title"
                    value="<?= $currentTitle ?>"
                    placeholder="Project title"
                    required
                >

            </div>


            <!-- =================================================
                 DESCRIPTION
            ================================================== -->

            <div class="form-group">

                <label>
                    Description
                </label>

                <textarea
                    name="description"
                    placeholder="Describe the community project..."
                    required
                ><?= $currentDescription ?></textarea>

            </div>


            <!-- =================================================
                 BARANGAY
            ================================================== -->

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
                            value="<?= htmlspecialchars(
                                $barangay['name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            <?= (
                                $currentBarangay ===
                                $barangay['name']
                            )
                            ? 'selected'
                            : '' ?>
                        >

                            <?= htmlspecialchars(
                                $barangay['name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- =================================================
                 CATEGORY / STATUS
            ================================================== -->

           <div class="form-group">

    <label>
        Center of Youth Participation
    </label>

    <select
        name="category"
        required
    >

        <option value="">
            Select Center of Youth Participation
        </option>

        <option value="Health"
            <?= $currentCategory === 'Health' ? 'selected' : '' ?>>
            Health
        </option>

        <option value="Education"
            <?= $currentCategory === 'Education' ? 'selected' : '' ?>>
            Education
        </option>

        <option value="Economic Empowerment"
            <?= $currentCategory === 'Economic Empowerment' ? 'selected' : '' ?>>
            Economic Empowerment
        </option>

        <option value="Social Inclusion and Equity"
            <?= $currentCategory === 'Social Inclusion and Equity' ? 'selected' : '' ?>>
            Social Inclusion and Equity
        </option>

        <option value="Peace-Building and Security"
            <?= $currentCategory === 'Peace-Building and Security' ? 'selected' : '' ?>>
            Peace-Building and Security
        </option>

        <option value="Governance"
            <?= $currentCategory === 'Governance' ? 'selected' : '' ?>>
            Governance
        </option>

        <option value="Active Citizenship"
            <?= $currentCategory === 'Active Citizenship' ? 'selected' : '' ?>>
            Active Citizenship
        </option>

        <option value="Environment"
            <?= $currentCategory === 'Environment' ? 'selected' : '' ?>>
            Environment
        </option>

        <option value="Global Mobility"
            <?= $currentCategory === 'Global Mobility' ? 'selected' : '' ?>>
            Global Mobility
        </option>

        <option value="Agriculture"
            <?= $currentCategory === 'Agriculture' ? 'selected' : '' ?>>
            Agriculture
        </option>

    </select>

</div>


                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <select
                        name="status"
                        required
                    >

                        <option value="">
                            Select status
                        </option>

                        <option
                            value="Planned"
                            <?= $currentStatus === 'Planned'
                                ? 'selected'
                                : '' ?>
                        >
                            Planned
                        </option>

                        <option
                            value="Ongoing"
                            <?= $currentStatus === 'Ongoing'
                                ? 'selected'
                                : '' ?>
                        >
                            Ongoing
                        </option>

                        <option
                            value="Completed"
                            <?= $currentStatus === 'Completed'
                                ? 'selected'
                                : '' ?>
                        >
                            Completed
                        </option>

                    </select>

                </div>

            </div>


            <!-- =================================================
                 LOCATION
            ================================================== -->

            <div class="form-group">

                <label>
                    Project Location
                </label>


                <div class="coordinates">

                    <input
                        type="number"
                        step="any"
                        name="lat"
                        value="<?= $currentLat ?>"
                        placeholder="Latitude"
                        required
                    >


                    <input
                        type="number"
                        step="any"
                        name="lng"
                        value="<?= $currentLng ?>"
                        placeholder="Longitude"
                        required
                    >

                </div>


                <div class="helper">

                    Naic map area:
                    Latitude 14.25–14.42,
                    Longitude 120.70–120.86.

                </div>

            </div>


            <!-- =================================================
                 CURRENT IMAGE
            ================================================== -->

            <div class="current-image">

                <label>
                    Current Project Image
                </label>


                <?php if(!empty($currentImage)): ?>

                    <img
                        src="../uploads/maps/<?= htmlspecialchars(
                            $currentImage,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        alt="Current Project Image"
                    >

                <?php else: ?>

                    <div class="no-image">
                        NO IMAGE
                    </div>

                <?php endif; ?>

            </div>


            <!-- =================================================
                 NEW IMAGE
            ================================================== -->

            <div class="form-group">

                <label>
                    Replace Project Image
                </label>

                <input
                    type="file"
                    name="image"
                    class="file-input"
                    accept="image/jpeg,image/png,image/webp"
                >


                <div class="helper">

                    Leave this empty if you want to keep the current image.
                    Maximum file size: 5MB.

                </div>

            </div>


            <!-- =================================================
                 ACTIONS
            ================================================== -->

            <div class="actions">

                <a
                    href="map.php"
                    class="cancel-btn"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    name="update"
                    class="update-btn"
                >
                    Save Changes
                </button>

            </div>


        </form>

    </div>

</div>


</body>

</html>
```
