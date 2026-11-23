<?php
include("db.php");

$result = mysqli_query(
    $conn,
    "SELECT * FROM barangays ORDER BY name ASC"
);

if (!$result) {
    die("Database error: " . mysqli_error($conn));
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

<title>Barangay Profiles | SK KONEXT</title>


<!-- Bootstrap -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- Font Awesome -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
>


<!-- Google Font -->

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>


<style>

/* =====================================================
   GENERAL
===================================================== */

*{
    box-sizing:border-box;
}

body{

    margin:0;

    background:#f4f7fc;

    font-family:'Inter',sans-serif;

    color:#172033;

}


/* =====================================================
   HEADER
===================================================== */

.header{

    position:relative;

    overflow:hidden;

    padding:55px 20px 90px;

    text-align:center;

    color:white;

    background:
        linear-gradient(
            135deg,
            #082f6b 0%,
            #0f4fa8 50%,
            #2875e8 100%
        );

}


.header:before{

    content:"";

    position:absolute;

    width:420px;
    height:420px;

    border-radius:50%;

    background:rgba(255,255,255,.07);

    top:-270px;

    right:-120px;

}


.header:after{

    content:"";

    position:absolute;

    width:330px;
    height:330px;

    border-radius:50%;

    background:rgba(255,255,255,.05);

    bottom:-250px;

    left:-100px;

}


.header-content{

    position:relative;

    z-index:2;

}


.logo-icon{

    width:60px;
    height:60px;

    margin:0 auto 17px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:18px;

    background:rgba(255,255,255,.14);

    border:1px solid rgba(255,255,255,.2);

    font-size:25px;

}


.header h1{

    margin:0;

    font-size:32px;

    font-weight:800;

    letter-spacing:-.5px;

}


.header p{

    margin:10px 0 0;

    font-size:12px;

    opacity:.78;

}


/* =====================================================
   MAIN CONTAINER
===================================================== */

.main-container{

    max-width:1200px;

    margin:-45px auto 0;

    padding:0 20px 50px;

    position:relative;

    z-index:5;

}


/* =====================================================
   TOP INFO
===================================================== */

.info-card{

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

    padding:20px 23px;

    background:white;

    border:1px solid #e5ebf3;

    border-radius:18px;

    box-shadow:
        0 12px 35px rgba(20,60,110,.09);

    margin-bottom:25px;

}


.info-left{

    display:flex;

    align-items:center;

    gap:13px;

}


.info-icon{

    width:42px;
    height:42px;

    flex-shrink:0;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:13px;

    color:#2166c5;

    background:#edf4ff;

}


.info-title{

    color:#173f78;

    font-size:13px;

    font-weight:800;

}


.info-text{

    margin-top:3px;

    color:#929dad;

    font-size:10px;

}


.back-btn{

    display:inline-flex;

    align-items:center;

    gap:7px;

    padding:9px 13px;

    color:#2166c5;

    background:#f0f5fc;

    border-radius:10px;

    text-decoration:none;

    font-size:10px;

    font-weight:800;

    transition:.25s;

}


.back-btn:hover{

    color:white;

    background:#2166c5;

    text-decoration:none;

}


/* =====================================================
   BARANGAY GRID
===================================================== */

.barangay-grid{

    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:20px;

}


/* =====================================================
   BARANGAY CARD
===================================================== */

.barangay-card{

    overflow:hidden;

    background:white;

    border:1px solid #e5ebf3;

    border-radius:20px;

    box-shadow:
        0 8px 25px rgba(25,65,115,.06);

    transition:.3s;

}


.barangay-card:hover{

    transform:translateY(-7px);

    box-shadow:
        0 18px 40px rgba(25,65,115,.13);

}


/* =====================================================
   COVER
===================================================== */

.cover{

    height:175px;

    position:relative;

    background:
        linear-gradient(
            135deg,
            #dbe8fb,
            #eef4fc
        );

    background-size:cover;

    background-position:center;

}


.cover-overlay{

    position:absolute;

    inset:0;

    background:
        linear-gradient(
            to top,
            rgba(5,35,77,.55),
            transparent 65%
        );

}


.cover-label{

    position:absolute;

    left:15px;

    bottom:13px;

    z-index:2;

    display:inline-flex;

    align-items:center;

    gap:6px;

    color:white;

    font-size:9px;

    font-weight:800;

}


.cover-label i{

    font-size:10px;

}


/* =====================================================
   CARD BODY
===================================================== */

.card-body{

    padding:20px;

}


.barangay-name{

    margin:0 0 9px;

    color:#173f78;

    font-size:17px;

    font-weight:800;

}


.description{

    height:43px;

    overflow:hidden;

    color:#788597;

    font-size:10px;

    line-height:1.7;

    margin-bottom:14px;

}


/* =====================================================
   STATS
===================================================== */

.stats{

    display:flex;

    gap:7px;

    margin-bottom:17px;

}


.stat{

    flex:1;

    padding:9px 7px;

    text-align:center;

    background:#f6f8fb;

    border-radius:10px;

}


.stat-number{

    color:#2166c5;

    font-size:14px;

    font-weight:800;

}


.stat-label{

    margin-top:2px;

    color:#929dad;

    font-size:8px;

    font-weight:600;

}


/* =====================================================
   VIEW BUTTON
===================================================== */

.view-btn{

    width:100%;

    display:flex;

    justify-content:center;

    align-items:center;

    gap:8px;

    padding:11px;

    color:white;

    border-radius:11px;

    background:
        linear-gradient(
            135deg,
            #1d62c1,
            #347cf0
        );

    text-decoration:none;

    font-size:10px;

    font-weight:800;

    box-shadow:
        0 7px 16px rgba(33,102,197,.18);

    transition:.25s;

}


.view-btn:hover{

    color:white;

    text-decoration:none;

    transform:translateY(-2px);

    box-shadow:
        0 10px 22px rgba(33,102,197,.27);

}


/* =====================================================
   EMPTY STATE
===================================================== */

.empty{

    grid-column:1/-1;

    padding:60px 20px;

    text-align:center;

    background:white;

    border:1px solid #e5ebf3;

    border-radius:20px;

}


.empty-icon{

    width:65px;
    height:65px;

    margin:0 auto 15px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:20px;

    color:#8ea0b7;

    background:#f0f4f9;

    font-size:25px;

}


.empty h4{

    color:#66758a;

    font-size:15px;

    font-weight:800;

}


.empty p{

    color:#9ba6b4;

    font-size:10px;

}


/* =====================================================
   FOOTER
===================================================== */

.footer{

    text-align:center;

    color:#9aa5b4;

    font-size:9px;

    padding:10px 0 30px;

}


.footer i{

    color:#2875e8;

}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:950px){

    .barangay-grid{

        grid-template-columns:
            repeat(2,1fr);

    }

}


@media(max-width:600px){

    .header{

        padding:40px 15px 80px;

    }


    .header h1{

        font-size:25px;

    }


    .main-container{

        padding:0 12px 40px;

    }


    .info-card{

        align-items:flex-start;

        flex-direction:column;

    }


    .back-btn{

        width:100%;

        justify-content:center;

    }


    .barangay-grid{

        grid-template-columns:1fr;

    }


    .cover{

        height:190px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="header">

    <div class="header-content">


        <div class="logo-icon">

            <i class="fa-solid fa-landmark"></i>

        </div>


        <h1>

            Naic Barangay Profiles

        </h1>


        <p>

            SK KONEXT Governance & Community Information System

        </p>


    </div>

</header>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="main-container">


    <!-- INFO CARD -->

    <div class="info-card">


        <div class="info-left">


            <div class="info-icon">

                <i class="fa-solid fa-map-location-dot"></i>

            </div>


            <div>

                <div class="info-title">

                    Explore Barangay Communities

                </div>


                <div class="info-text">

                    Select a barangay to view announcements,
                    projects, activities, and upcoming events.

                </div>

            </div>


        </div>


       <a href="barangay_view.php?name=Balsahan" class="back-btn" > <i class="fa-solid fa-rotate-left"></i> Barangay Directory </a> </div>


    <!-- BARANGAY GRID -->

    <div class="barangay-grid">


    <?php if (mysqli_num_rows($result) == 0) { ?>


        <div class="empty">

            <div class="empty-icon">

                <i class="fa-solid fa-building-circle-exclamation"></i>

            </div>


            <h4>

                No Barangays Found

            </h4>


            <p>

                There are currently no barangay profiles available.

            </p>

        </div>


    <?php } ?>


    <?php while ($row = mysqli_fetch_assoc($result)) { ?>


        <?php

        /*
         * Use the uploaded cover photo if available.
         * Otherwise use a simple default background.
         */

        $cover_photo = !empty($row['cover_photo'])
            ? $row['cover_photo']
            : '';

        ?>


        <div class="barangay-card">


            <!-- COVER PHOTO -->

            <div
                class="cover"
                <?php if ($cover_photo) { ?>

                    style="
                        background-image:
                        url('<?= htmlspecialchars($cover_photo) ?>');
                    "

                <?php } ?>
            >


                <div class="cover-overlay"></div>


                <div class="cover-label">

                    <i class="fa-solid fa-location-dot"></i>

                    Naic, Cavite

                </div>


            </div>


            <!-- CARD BODY -->

            <div class="card-body">


                <h3 class="barangay-name">

                    <?= htmlspecialchars(
                        $row['name']
                    ) ?>

                </h3>


                <p class="description">

                    <?php

                    $description =
                        !empty($row['description'])
                        ? $row['description']
                        : 'Official barangay information and community updates.';

                    echo htmlspecialchars(
                        substr(
                            $description,
                            0,
                            95
                        )
                    );

                    if (strlen($description) > 95) {
                        echo "...";
                    }

                    ?>

                </p>


                <!-- STATS -->

                <div class="stats">


                    <div class="stat">

                        <div class="stat-number">

                            <?= (int)$row['total_scholars'] ?>

                        </div>

                        <div class="stat-label">

                            Scholars

                        </div>

                    </div>


                    <div class="stat">

                        <div class="stat-number">

                            <?= (int)$row['total_sk_members'] ?>

                        </div>

                        <div class="stat-label">

                            SK Members

                        </div>

                    </div>


                </div>


                <!-- VIEW PROFILE -->

                <a
                    href="barangay_view.php?name=<?= urlencode($row['name']) ?>"
                    class="view-btn"
                >

                    View Barangay Profile

                    <i class="fa-solid fa-arrow-right"></i>

                </a>


            </div>


        </div>


    <?php } ?>


    </div>


    <!-- FOOTER -->

    <div class="footer">

        <i class="fa-solid fa-landmark"></i>

        &nbsp;

        SK KONEXT

        &nbsp;•&nbsp;

        Naic Barangay Information Portal

    </div>


</div>


</body>

</html>