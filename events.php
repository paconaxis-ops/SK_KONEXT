<?php
include("db.php");

/* =========================================
   LOAD PROJECTS
========================================= */

$maps = mysqli_query($conn, "
    SELECT *
    FROM map_projects
    ORDER BY id DESC
");

$projects = [];

while($row = mysqli_fetch_assoc($maps)){
    $projects[] = $row;
}

$totalProjects = count($projects);

$ongoing = 0;
$completed = 0;
$planned = 0;

foreach($projects as $p){

    $status = strtolower(trim($p['status'] ?? ''));

    if($status === 'ongoing'){
        $ongoing++;
    }

    if($status === 'completed' || $status === 'done'){
        $completed++;
    }

    if($status === 'planned'){
        $planned++;
    }
}

$jsonProjects = json_encode(
    $projects,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
);
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>SK KONEXT — Community Map</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect"
      href="https://fonts.gstatic.com"
      crossorigin>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap"
      rel="stylesheet">

<link rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>

/* =====================================================
   RESET
===================================================== */

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
    background:#111;
    color:#fff;
}


/* =====================================================
   MAP
===================================================== */

#map{
    position:fixed;
    inset:0;
    width:100%;
    height:100%;
    z-index:1;
}


/* =====================================================
   MAP OVERLAY
===================================================== */

.map-overlay{
    position:fixed;
    inset:0;
    z-index:2;
    pointer-events:none;

    background:
        linear-gradient(
            180deg,
            rgba(0,0,0,.48) 0%,
            rgba(0,0,0,.03) 27%,
            rgba(0,0,0,.02) 55%,
            rgba(0,0,0,.58) 100%
        );
}


/* =====================================================
   TOP BAR
===================================================== */

.topbar{
    position:fixed;

    top:24px;
    left:28px;
    right:28px;

    z-index:20;

    display:flex;
    align-items:center;
    justify-content:space-between;

    pointer-events:none;
}


/* =====================================================
   BRAND
===================================================== */

.brand{
    display:flex;
    align-items:center;
    gap:12px;

    pointer-events:auto;

    text-decoration:none;
    color:white;
}

.brand-symbol{
    width:44px;
    height:44px;

    border-radius:14px;

    display:flex;
    align-items:center;
    justify-content:center;

    background:rgba(255,255,255,.92);

    color:#111;

    font-family:"Manrope",sans-serif;
    font-weight:800;
    font-size:17px;

    box-shadow:
        0 10px 30px rgba(0,0,0,.25);
}

.brand-text{
    display:flex;
    flex-direction:column;
}

.brand-title{
    font-family:"Manrope",sans-serif;
    font-size:15px;
    font-weight:800;
    letter-spacing:.5px;
}

.brand-subtitle{
    font-size:11px;
    opacity:.75;
    margin-top:2px;
}


/* =====================================================
   TOP ACTIONS
===================================================== */

.top-actions{
    display:flex;
    gap:10px;

    pointer-events:auto;
}


/* =====================================================
   GLASS BUTTON
===================================================== */

.glass-btn{
    height:46px;

    padding:0 17px;

    border:1px solid rgba(255,255,255,.22);

    border-radius:14px;

    background:rgba(20,20,20,.58);

    backdrop-filter:blur(18px);
    -webkit-backdrop-filter:blur(18px);

    color:white;

    display:flex;
    align-items:center;
    gap:8px;

    text-decoration:none;

    font-size:13px;
    font-weight:600;

    cursor:pointer;

    transition:.25s ease;
}

.glass-btn:hover{
    background:rgba(255,255,255,.92);
    color:#111;

    transform:translateY(-2px);
}


/* =====================================================
   INTRO
===================================================== */

.intro{
    position:fixed;

    left:30px;
    top:50%;

    transform:translateY(-50%);

    z-index:10;

    max-width:440px;

    pointer-events:none;
}

.eyebrow{
    display:inline-flex;

    align-items:center;
    gap:8px;

    padding:7px 11px;

    border:1px solid rgba(255,255,255,.22);

    border-radius:100px;

    background:rgba(0,0,0,.42);

    backdrop-filter:blur(12px);

    font-size:10px;

    font-weight:700;

    letter-spacing:1.5px;

    text-transform:uppercase;

    margin-bottom:16px;
}

.eyebrow-dot{
    width:6px;
    height:6px;

    background:#fff;

    border-radius:50%;
}

.intro h1{
    font-family:"Manrope",sans-serif;

    font-size:clamp(42px,5vw,76px);

    line-height:.94;

    letter-spacing:-4px;

    font-weight:800;

    margin-bottom:20px;

    text-shadow:
        0 10px 35px rgba(0,0,0,.35);
}

.intro h1 span{
    opacity:.48;
}

.intro p{
    max-width:360px;

    font-size:14px;

    line-height:1.7;

    color:rgba(255,255,255,.82);

    text-shadow:
        0 4px 20px rgba(0,0,0,.6);
}


/* =====================================================
   SEARCH
===================================================== */

.search-box{
    position:fixed;

    left:30px;
    top:30px;

    z-index:25;

    margin-top:76px;

    width:280px;

    pointer-events:auto;
}

.search-wrapper{
    height:48px;

    display:flex;
    align-items:center;

    padding:0 15px;

    gap:10px;

    border-radius:15px;

    background:rgba(15,15,15,.62);

    border:1px solid rgba(255,255,255,.18);

    backdrop-filter:blur(18px);

    box-shadow:
        0 15px 40px rgba(0,0,0,.18);
}

.search-icon{
    opacity:.7;
    font-size:15px;
}

.search-wrapper input{
    width:100%;

    background:none;

    border:none;

    outline:none;

    color:white;

    font-family:inherit;

    font-size:13px;
}

.search-wrapper input::placeholder{
    color:rgba(255,255,255,.55);
}


/* =====================================================
   FILTERS
===================================================== */

.filters{
    position:fixed;

    left:30px;

    top:160px;

    z-index:20;

    display:flex;

    gap:7px;

    flex-wrap:wrap;

    max-width:350px;
}

.filter{
    border:1px solid rgba(255,255,255,.17);

    background:rgba(20,20,20,.55);

    backdrop-filter:blur(14px);

    color:white;

    padding:8px 12px;

    border-radius:100px;

    font-family:inherit;

    font-size:10px;

    cursor:pointer;

    transition:.2s;
}

.filter:hover,
.filter.active{
    background:white;
    color:#111;
}


/* =====================================================
   PROJECT DRAWER
===================================================== */

.projects-drawer{
    position:fixed;

    right:28px;
    top:50%;

    transform:
        translateY(-50%)
        translateX(calc(100% + 35px));

    width:330px;

    max-height:70vh;

    z-index:30;

    padding:18px;

    border-radius:24px;

    background:rgba(12,12,12,.72);

    border:1px solid rgba(255,255,255,.16);

    backdrop-filter:blur(25px);
    -webkit-backdrop-filter:blur(25px);

    box-shadow:
        0 25px 80px rgba(0,0,0,.35);

    transition:
        transform .45s cubic-bezier(.2,.8,.2,1);

    overflow:hidden;
}

.projects-drawer.open{
    transform:
        translateY(-50%)
        translateX(0);
}


/* =====================================================
   DRAWER HEADER
===================================================== */

.drawer-header{
    display:flex;
    align-items:center;
    justify-content:space-between;

    margin-bottom:15px;
}

.drawer-title{
    font-family:"Manrope",sans-serif;

    font-size:16px;
    font-weight:800;
}

.drawer-count{
    font-size:10px;

    opacity:.6;
}


/* =====================================================
   PROJECT LIST
===================================================== */

.project-list{
    overflow-y:auto;

    max-height:calc(70vh - 70px);

    padding-right:4px;
}

.project-list::-webkit-scrollbar{
    width:4px;
}

.project-list::-webkit-scrollbar-thumb{
    background:rgba(255,255,255,.25);
    border-radius:10px;
}


/* =====================================================
   PROJECT CARD
===================================================== */

.project-card{
    display:flex;

    gap:12px;

    padding:10px;

    border-radius:17px;

    cursor:pointer;

    transition:.25s;

    margin-bottom:7px;

    border:1px solid transparent;
}

.project-card:hover{
    background:rgba(255,255,255,.09);

    border-color:rgba(255,255,255,.1);

    transform:translateX(-3px);
}

.project-card.active{
    background:rgba(255,255,255,.13);

    border-color:rgba(255,255,255,.2);
}

.project-image{
    width:76px;
    height:76px;

    flex-shrink:0;

    border-radius:13px;

    overflow:hidden;

    background:#292929;
}

.project-image img{
    width:100%;
    height:100%;

    object-fit:cover;
}

.project-no-image{
    width:100%;
    height:100%;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:22px;

    opacity:.35;
}

.project-info{
    min-width:0;

    display:flex;
    flex-direction:column;
    justify-content:center;
}

.project-category{
    font-size:9px;

    text-transform:uppercase;

    letter-spacing:1px;

    opacity:.55;

    margin-bottom:4px;
}

.project-name{
    font-family:"Manrope",sans-serif;

    font-size:13px;

    font-weight:700;

    white-space:nowrap;

    overflow:hidden;

    text-overflow:ellipsis;

    margin-bottom:5px;
}

.project-place{
    font-size:10px;

    opacity:.58;

    white-space:nowrap;

    overflow:hidden;

    text-overflow:ellipsis;
}


/* =====================================================
   SELECTED PROJECT
   DRAGGABLE
===================================================== */

.selected-project{
    position:fixed;

    right:30px;
    bottom:30px;

    width:520px;

    z-index:40;

    background:rgba(10,10,10,.78);

    border:1px solid rgba(255,255,255,.17);

    border-radius:28px;

    backdrop-filter:blur(24px);
    -webkit-backdrop-filter:blur(24px);

    overflow:hidden;

    box-shadow:
        0 30px 90px rgba(0,0,0,.45);

    transform:translateY(130%);

    opacity:0;

    transition:
        opacity .45s cubic-bezier(.2,.8,.2,1),
        transform .45s cubic-bezier(.2,.8,.2,1);

    /* DRAG */
    cursor:grab;

    user-select:none;
    -webkit-user-select:none;

    touch-action:none;
}

.selected-project.show{
    transform:translateY(0);
    opacity:1;
}

.selected-project.dragging{
    cursor:grabbing;

    transition:none;

    box-shadow:
        0 35px 100px rgba(0,0,0,.55);
}


/* =====================================================
   SELECTED CLOSE BUTTON
===================================================== */

.selected-close{
    position:absolute;

    top:14px;
    right:14px;

    width:40px;
    height:40px;

    z-index:20;

    border:1px solid rgba(255,255,255,.25);

    border-radius:50%;

    background:rgba(0,0,0,.65);

    backdrop-filter:blur(14px);
    -webkit-backdrop-filter:blur(14px);

    color:white;

    font-size:24px;
    line-height:1;

    display:flex;
    align-items:center;
    justify-content:center;

    cursor:pointer;

    transition:.25s ease;

    touch-action:auto;
}

.selected-close:hover{
    background:white;

    color:#111;

    transform:rotate(90deg);
}


/* =====================================================
   SELECTED IMAGE
===================================================== */

.selected-image{
    width:100%;
    height:260px;

    position:relative;

    background:#202020;

    overflow:hidden;
}

.selected-image img{
    width:100%;
    height:100%;

    object-fit:cover;
}

.selected-gradient{
    position:absolute;

    inset:0;

    background:
        linear-gradient(
            180deg,
            rgba(0,0,0,.05) 20%,
            transparent 45%,
            rgba(0,0,0,.75)
        );
}


/* =====================================================
   SELECTED CONTENT
===================================================== */

.selected-content{
    padding:22px 24px 24px;
}

.selected-top{
    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:10px;

    margin-bottom:10px;
}

.selected-category{
    font-size:10px;

    letter-spacing:1.3px;

    text-transform:uppercase;

    opacity:.55;
}

.selected-status{
    font-size:10px;

    padding:6px 10px;

    border-radius:100px;

    background:rgba(255,255,255,.1);

    border:1px solid rgba(255,255,255,.1);

    text-transform:uppercase;

    letter-spacing:.7px;
}

.selected-status.planned{
    color:#60a5fa;
    background:rgba(59,130,246,.14);
    border-color:#3b82f6;
}

.selected-status.ongoing{
    color:#facc15;
    background:rgba(250,204,21,.14);
    border-color:#facc15;
}

.selected-status.completed{
    color:#4ade80;
    background:rgba(34,197,94,.14);
    border-color:#22c55e;
}

.selected-title{
    font-family:"Manrope",sans-serif;

    font-size:28px;

    line-height:1.05;

    font-weight:800;

    margin-bottom:10px;
}

.selected-description{
    color:rgba(255,255,255,.68);

    font-size:12px;

    line-height:1.7;

    margin-bottom:14px;
}

.selected-location{
    font-size:11px;

    color:rgba(255,255,255,.5);
}


/* =====================================================
   EXPLORE CARD
===================================================== */

.explore-card{
    position:fixed;

    left:30px;
    bottom:30px;

    z-index:15;

    pointer-events:none;

    max-width:400px;
}

.explore-title{
    font-family:"Manrope",sans-serif;

    font-size:14px;

    font-weight:700;

    margin-bottom:6px;
}

.explore-text{
    font-size:11px;

    opacity:.65;
}


/* =====================================================
   PROJECT BUTTON
===================================================== */

.projects-button{
    position:fixed;

    right:28px;
    bottom:30px;

    z-index:35;

    height:52px;

    padding:0 20px;

    border:none;

    border-radius:16px;

    background:white;

    color:#111;

    font-family:"Manrope",sans-serif;

    font-size:12px;

    font-weight:800;

    cursor:pointer;

    display:flex;

    align-items:center;

    gap:10px;

    box-shadow:
        0 15px 40px rgba(0,0,0,.28);

    transition:.25s;
}

.projects-button:hover{
    transform:translateY(-3px);
}

.projects-button span{
    display:flex;

    width:22px;
    height:22px;

    align-items:center;
    justify-content:center;

    border-radius:50%;

    background:#111;

    color:white;

    font-size:10px;
}


/* =====================================================
   MAP CONTROLS
===================================================== */

.leaflet-control-layers{
    border:none !important;

    border-radius:14px !important;

    overflow:hidden;

    box-shadow:
        0 10px 35px rgba(0,0,0,.25) !important;
}

.leaflet-control-layers-toggle{
    width:44px !important;
    height:44px !important;
}

.leaflet-control-zoom{
    border:none !important;
}

.leaflet-control-zoom a{
    width:38px !important;
    height:38px !important;

    line-height:38px !important;

    background:rgba(20,20,20,.78) !important;

    color:white !important;

    border:none !important;

    backdrop-filter:blur(12px);
}


/* =====================================================
   CUSTOM MARKER
===================================================== */

.custom-pin{
    width:34px;
    height:34px;

    border-radius:50%;

    background:white;

    border:5px solid rgba(255,255,255,.38);

    box-shadow:
        0 8px 25px rgba(0,0,0,.4);

    position:relative;
}

/* STATUS COLORS */
.custom-pin.planned{
    border-color:#3b82f6;
}

.custom-pin.ongoing{
    border-color:#facc15;
}

.custom-pin.completed{
    border-color:#22c55e;
}

/* MARKER HOVER ANIMATION */
.leaflet-marker-icon .custom-pin{
    transition:transform .25s ease, box-shadow .25s ease;
}

.leaflet-marker-icon:hover .custom-pin{
    transform:scale(1.25);
    box-shadow:0 10px 30px rgba(0,0,0,.55);
}

.custom-pin::after{
    content:"";

    position:absolute;

    left:50%;
    top:50%;

    width:8px;
    height:8px;

    transform:
        translate(-50%,-50%);

    background:#111;

    border-radius:50%;
}


/* =====================================================
   LEAFLET POPUP
===================================================== */

.leaflet-popup-content-wrapper{
    background:#111 !important;

    color:white !important;

    border-radius:16px !important;
}

.leaflet-popup-tip{
    background:#111 !important;
}

.leaflet-popup-content{
    margin:14px !important;
}


/* =====================================================
   MAP STATUS LEGEND
===================================================== */

.map-legend{
    position:fixed;
    left:30px;
    bottom:90px;
    z-index:15;
    padding:12px 15px;
    border:1px solid rgba(255,255,255,.18);
    border-radius:15px;
    background:rgba(15,15,15,.68);
    backdrop-filter:blur(16px);
    -webkit-backdrop-filter:blur(16px);
    box-shadow:0 10px 30px rgba(0,0,0,.22);
    pointer-events:none;
}

.map-legend-title{
    font-family:"Manrope",sans-serif;
    font-size:10px;
    font-weight:800;
    letter-spacing:.7px;
    margin-bottom:8px;
    text-transform:uppercase;
}

.map-legend-items{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.map-legend-item{
    display:flex;
    align-items:center;
    gap:6px;
    font-size:10px;
    color:rgba(255,255,255,.85);
}

.map-legend-dot{
    width:9px;
    height:9px;
    border-radius:50%;
    display:inline-block;
}

.map-legend-dot.planned{background:#3b82f6;}
.map-legend-dot.ongoing{background:#facc15;}
.map-legend-dot.completed{background:#22c55e;}


/* =====================================================
   HIDE EXPLORE WHEN PROJECT SELECTED
===================================================== */

body.project-selected .explore-card{
    display:none;
}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:800px){

    .topbar{
        top:14px;
        left:14px;
        right:14px;
    }

    .brand-text{
        display:none;
    }

    .brand-symbol{
        width:42px;
        height:42px;
    }

    .glass-btn{
        height:42px;
        padding:0 13px;
    }

    .glass-btn span{
        display:none;
    }

    .intro{
        left:18px;
        top:115px;

        transform:none;

        max-width:300px;
    }

    .intro h1{
        font-size:40px;
        letter-spacing:-2px;
    }

    .intro p{
        font-size:11px;
    }

    .search-box{
        left:18px;
        right:18px;

        width:auto;

        top:210px;

        margin-top:0;
    }

    .filters{
        left:18px;
        right:18px;

        top:270px;

        max-width:none;

        overflow-x:auto;

        flex-wrap:nowrap;

        padding-bottom:4px;
    }

    .filter{
        white-space:nowrap;
    }

    .projects-drawer{
        left:14px;
        right:14px;

        width:auto;

        top:auto;
        bottom:14px;

        max-height:58vh;

        transform:
            translateY(calc(100% + 30px));
    }

    .projects-drawer.open{
        transform:
            translateY(0);
    }

    .selected-project{
        left:14px;
        right:14px;

        width:auto;

        bottom:14px;

        border-radius:22px;
    }

    .selected-image{
        height:170px;
    }

    .selected-content{
        padding:18px 18px 20px;
    }

    .selected-title{
        font-size:22px;
    }

    .selected-description{
        font-size:11px;
    }

    .selected-close{
        width:36px;
        height:36px;

        top:10px;
        right:10px;
    }

    .projects-button{
        right:14px;
        bottom:14px;
    }

    .explore-card{
        left:18px;
        bottom:25px;

        max-width:270px;
    }

    .map-legend{
        left:18px;
        bottom:90px;
        padding:10px 12px;
    }

    .map-legend-items{
        gap:9px;
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

    <a href="index.php" class="brand">

        <div class="brand-symbol">
            SK
        </div>

        <div class="brand-text">

            <div class="brand-title">
                SK KONEXT
            </div>

            <div class="brand-subtitle">
                Community Project Map
            </div>

        </div>

    </a>


    <div class="top-actions">

        <button
            class="glass-btn"
            onclick="resetMap()">

            ⌖

            <span>
                Reset View
            </span>

        </button>


        <a
            href="index.php"
            class="glass-btn">

            ←

            <span>
                Back to Home
            </span>

        </a>

    </div>

</div>


<!-- =====================================================
     INTRO
===================================================== -->

<div class="intro">

    <div class="eyebrow">

        <div class="eyebrow-dot"></div>

        Naic, Cavite

    </div>


    <h1>
        Community<br>
        <span>in motion.</span>
    </h1>


    <p>
        Explore youth programs, community projects,
        events and activities happening across
        Naic through the SK KONEXT interactive map.
    </p>

</div>


<!-- =====================================================
     SEARCH
===================================================== -->

<div class="search-box">

    <div class="search-wrapper">

        <div class="search-icon">
            ⌕
        </div>

        <input
            type="text"
            id="searchInput"
            placeholder="Search projects, events, barangays..."
            autocomplete="off">

    </div>

</div>


<!-- =====================================================
     FILTERS
===================================================== -->

<div class="filters">

    <button
        class="filter active"
        data-filter="all">

        All

    </button>


    <button
        class="filter"
        data-filter="ongoing">

        Ongoing

    </button>


    <button
        class="filter"
        data-filter="completed">

        Completed

    </button>


    <button
        class="filter"
        data-filter="planned">

        Planned

    </button>

</div>


<!-- =====================================================
     EXPLORE MESSAGE
===================================================== -->

<div class="explore-card">

    <div class="explore-title">

        <?= $totalProjects ?> community projects

    </div>

    <div class="explore-text">

        Select a location on the map to explore.

    </div>

</div>


<!-- =====================================================
     MAP STATUS LEGEND
===================================================== -->

<div class="map-legend">
    <div class="map-legend-title">Project Status</div>
    <div class="map-legend-items">
        <div class="map-legend-item">
            <span class="map-legend-dot planned"></span>
            Planned
        </div>
        <div class="map-legend-item">
            <span class="map-legend-dot ongoing"></span>
            Ongoing
        </div>
        <div class="map-legend-item">
            <span class="map-legend-dot completed"></span>
            Completed
        </div>
    </div>
</div>


<!-- =====================================================
     PROJECT DRAWER
===================================================== -->

<div
    class="projects-drawer"
    id="projectsDrawer">

    <div class="drawer-header">

        <div class="drawer-title">
            Projects
        </div>

        <div
            class="drawer-count"
            id="drawerCount">

            <?= $totalProjects ?> locations

        </div>

    </div>


    <div
        class="project-list"
        id="projectList">

    </div>

</div>


<!-- =====================================================
     PROJECT BUTTON
===================================================== -->

<button
    class="projects-button"
    id="projectsButton"
    onclick="toggleProjects()">

    <span id="projectIcon">
        +
    </span>

    Explore Projects

</button>


<!-- =====================================================
     SELECTED PROJECT CARD
===================================================== -->

<div
    class="selected-project"
    id="selectedProject">

    <!-- CLOSE BUTTON -->

    <button
        class="selected-close"
        onclick="closeSelectedProject()"
        aria-label="Close selected project">

        ×

    </button>


    <div class="selected-image">

        <img
            id="selectedImage"
            src=""
            alt="Project">

        <div class="selected-gradient"></div>

    </div>


    <div class="selected-content">

        <div class="selected-top">

            <div
                class="selected-category"
                id="selectedCategory">
            </div>


            <div
                class="selected-status"
                id="selectedStatus">
            </div>

        </div>


        <div
            class="selected-title"
            id="selectedTitle">
        </div>


        <div
            class="selected-description"
            id="selectedDescription">
        </div>


        <div
            class="selected-location"
            id="selectedLocation">
        </div>

    </div>

</div>


<script>

/* =====================================================
   PROJECT DATA
===================================================== */

const projects = <?= $jsonProjects ?>;


/* =====================================================
   MAP
===================================================== */

const map = L.map(
    'map',
    {
        minZoom:12,

        maxZoom:18,

        maxBounds:[
            [14.25,120.70],
            [14.42,120.86]
        ],

        maxBoundsViscosity:1.0,

        zoomControl:false
    }
).setView(
    [14.333,120.772],
    13
);


/* =====================================================
   ZOOM CONTROL
===================================================== */

L.control.zoom({
    position:"bottomright"
}).addTo(map);


/* =====================================================
   STREET MAP
===================================================== */

const streetLayer =
    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom:18,
            attribution:'© OpenStreetMap'
        }
    );


/* =====================================================
   SATELLITE MAP
===================================================== */

const satelliteLayer =
    L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        {
            maxZoom:18,
            attribution:'Tiles © Esri'
        }
    );


satelliteLayer.addTo(map);


/* =====================================================
   MAP LAYERS
===================================================== */

L.control.layers(
    {
        "Satellite":satelliteLayer,
        "Street":streetLayer
    },
    null,
    {
        position:"topright"
    }
).addTo(map);


/* =====================================================
   MARKERS
===================================================== */

let markers = [];

let markerMap = {};


/* =====================================================
   CUSTOM PIN
===================================================== */

function createPinIcon(status){

    const statusClass = normalizeStatus(status);

    return L.divIcon({

        className:"",

        html:`
            <div class="custom-pin ${statusClass}"></div>
        `,

        iconSize:[34,34],

        iconAnchor:[17,17],

        popupAnchor:[0,-17]

    });

}


/* =====================================================
   ESCAPE HTML
===================================================== */

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


/* =====================================================
   IMAGE PATH
===================================================== */

function imagePath(project){

    if(project.image){

        return "uploads/maps/" +
               encodeURIComponent(
                   project.image
               );

    }

    return "";

}


/* =====================================================
   NORMALIZE STATUS
===================================================== */

function normalizeStatus(status){

    status =
        String(status || "")
        .toLowerCase()
        .trim();


    if(
        status === "done" ||
        status === "completed"
    ){

        return "completed";

    }


    if(status === "ongoing"){

        return "ongoing";

    }


    if(status === "planned"){

        return "planned";

    }


    return "other";

}


/* =====================================================
   DISPLAY STATUS
===================================================== */

function displayStatus(status){

    const normalized =
        normalizeStatus(status);


    if(normalized === "completed"){

        return "Completed";

    }


    if(normalized === "ongoing"){

        return "Ongoing";

    }


    if(normalized === "planned"){

        return "Planned";

    }


    return status || "Project";

}


/* =====================================================
   CREATE MAP MARKERS
===================================================== */

projects.forEach(
    (project,index)=>{

        if(
            !project.latitude ||
            !project.longitude
        ){

            return;

        }


        const lat =
            parseFloat(
                project.latitude
            );


        const lng =
            parseFloat(
                project.longitude
            );


        const marker =
            L.marker(
                [lat,lng],
                {
                    icon:createPinIcon(project.status)
                }
            ).addTo(map);


        marker.bindPopup(`

            <div style="
                min-width:180px;
                font-family:DM Sans,sans-serif;
            ">

                ${
                    project.image
                    ?
                    `
                    <img
                        src="${imagePath(project)}"
                        style="
                            width:100%;
                            height:100px;
                            object-fit:cover;
                            border-radius:10px;
                            margin-bottom:9px;
                        "
                    >
                    `
                    :
                    ""
                }


                <div style="
                    font-size:9px;
                    text-transform:uppercase;
                    opacity:.5;
                    letter-spacing:1px;
                    margin-bottom:4px;
                ">

                    ${escapeHtml(
                        project.category ||
                        "Community Project"
                    )}

                </div>


                <strong style="
                    font-size:14px;
                ">

                    ${escapeHtml(
                        project.title
                    )}

                </strong>


                <div style="
                    margin-top:5px;
                    font-size:10px;
                    opacity:.6;
                ">

                    ${escapeHtml(
                        project.barangay ||
                        "Naic, Cavite"
                    )}

                </div>

            </div>

        `);


        marker.on(
            "click",
            function(){

                selectProject(
                    project,
                    marker
                );

            }
        );


        markers.push(marker);

        markerMap[project.id] =
            marker;

    }
);


/* =====================================================
   RENDER PROJECTS
===================================================== */

function renderProjects(){

    const search =
        document
        .getElementById(
            "searchInput"
        )
        .value
        .toLowerCase()
        .trim();


    const activeFilter =
        document
        .querySelector(
            ".filter.active"
        )
        .dataset.filter;


    const list =
        document
        .getElementById(
            "projectList"
        );


    list.innerHTML = "";


    let count = 0;


    projects.forEach(
        project=>{

            const title =
                String(
                    project.title || ""
                ).toLowerCase();


            const description =
                String(
                    project.description || ""
                ).toLowerCase();


            const barangay =
                String(
                    project.barangay || ""
                ).toLowerCase();


            const category =
                String(
                    project.category || ""
                ).toLowerCase();


            const status =
                normalizeStatus(
                    project.status
                );


            const searchable =
                title +
                " " +
                description +
                " " +
                barangay +
                " " +
                category;


            const searchMatch =
                !search ||
                searchable.includes(
                    search
                );


            const filterMatch =
                activeFilter === "all" ||
                status === activeFilter;


            if(
                !searchMatch ||
                !filterMatch
            ){

                return;

            }


            count++;


            const card =
                document.createElement(
                    "div"
                );


            card.className =
                "project-card";


            card.dataset.id =
                project.id;


            const image =
                imagePath(project);


            card.innerHTML = `

                <div class="project-image">

                    ${
                        image
                        ?
                        `
                        <img
                            src="${image}"
                            alt=""
                        >
                        `
                        :
                        `
                        <div class="project-no-image">
                            ◎
                        </div>
                        `
                    }

                </div>


                <div class="project-info">

                    <div class="project-category">

                        ${escapeHtml(
                            project.category ||
                            "Community Project"
                        )}

                    </div>


                    <div class="project-name">

                        ${escapeHtml(
                            project.title
                        )}

                    </div>


                    <div class="project-place">

                        ${escapeHtml(
                            project.barangay ||
                            "Naic, Cavite"
                        )}

                    </div>

                </div>

            `;


            card.onclick =
                function(){

                    const marker =
                        markerMap[
                            project.id
                        ];


                    selectProject(
                        project,
                        marker
                    );

                };


            list.appendChild(card);

        }
    );


    document
        .getElementById(
            "drawerCount"
        )
        .textContent =
        count +
        (
            count === 1
            ? " location"
            : " locations"
        );


    updateMarkers();

}


/* =====================================================
   UPDATE MARKERS
===================================================== */

function updateMarkers(){

    const search =
        document
        .getElementById(
            "searchInput"
        )
        .value
        .toLowerCase()
        .trim();


    const activeFilter =
        document
        .querySelector(
            ".filter.active"
        )
        .dataset.filter;


    projects.forEach(
        project=>{

            const marker =
                markerMap[
                    project.id
                ];


            if(!marker){

                return;

            }


            const searchable = (

                String(
                    project.title || ""
                ) +

                " " +

                String(
                    project.description || ""
                ) +

                " " +

                String(
                    project.barangay || ""
                ) +

                " " +

                String(
                    project.category || ""
                )

            ).toLowerCase();


            const status =
                normalizeStatus(
                    project.status
                );


            const searchMatch =
                !search ||
                searchable.includes(
                    search
                );


            const filterMatch =
                activeFilter === "all" ||
                status === activeFilter;


            if(
                searchMatch &&
                filterMatch
            ){

                if(
                    !map.hasLayer(marker)
                ){

                    marker.addTo(map);

                }

            }else{

                if(
                    map.hasLayer(marker)
                ){

                    map.removeLayer(marker);

                }

            }

        }
    );

}


/* =====================================================
   SELECT PROJECT
===================================================== */

function selectProject(
    project,
    marker
){

    if(!project){

        return;

    }


    document
        .body
        .classList
        .add(
            "project-selected"
        );


    const panel =
        document.getElementById(
            "selectedProject"
        );


    const image =
        document.getElementById(
            "selectedImage"
        );


    const imageUrl =
        imagePath(project);


    if(imageUrl){

        image.src =
            imageUrl;

        image.style.display =
            "block";

    }else{

        image.removeAttribute(
            "src"
        );

        image.style.display =
            "none";

    }


    document
        .getElementById(
            "selectedCategory"
        )
        .textContent =
        project.category ||
        "Community Project";


    const statusBadge =
        document.getElementById("selectedStatus");

    statusBadge.textContent =
        displayStatus(project.status);

    statusBadge.classList.remove(
        "planned",
        "ongoing",
        "completed"
    );

    const selectedStatusClass =
        normalizeStatus(project.status);

    if(
        selectedStatusClass === "planned" ||
        selectedStatusClass === "ongoing" ||
        selectedStatusClass === "completed"
    ){
        statusBadge.classList.add(selectedStatusClass);
    }


    document
        .getElementById(
            "selectedTitle"
        )
        .textContent =
        project.title ||
        "Community Project";


    document
        .getElementById(
            "selectedDescription"
        )
        .textContent =
        project.description ||
        "No description available.";


    document
        .getElementById(
            "selectedLocation"
        )
        .textContent =
        "⌖ " +
        (
            project.barangay ||
            "Naic, Cavite"
        );


    panel.classList.add(
        "show"
    );


    if(marker){

        map.flyTo(
            marker.getLatLng(),
            16,
            {
                duration:1.1
            }
        );


        marker.openPopup();

    }


    document
        .querySelectorAll(
            ".project-card"
        )
        .forEach(
            card=>{

                card.classList.remove(
                    "active"
                );


                if(
                    String(
                        card.dataset.id
                    ) ===
                    String(
                        project.id
                    )
                ){

                    card.classList.add(
                        "active"
                    );

                }

            }
        );

}


/* =====================================================
   CLOSE SELECTED PROJECT
===================================================== */

function closeSelectedProject(){

    const panel =
        document.getElementById(
            "selectedProject"
        );


    panel.classList.remove(
        "show"
    );


    document
        .body
        .classList
        .remove(
            "project-selected"
        );


    document
        .querySelectorAll(
            ".project-card"
        )
        .forEach(
            card=>{

                card.classList.remove(
                    "active"
                );

            }
        );

}


/* =====================================================
   FILTER BUTTONS
===================================================== */

document
    .querySelectorAll(
        ".filter"
    )
    .forEach(
        button=>{

            button.addEventListener(
                "click",
                function(){

                    document
                        .querySelectorAll(
                            ".filter"
                        )
                        .forEach(
                            btn=>{

                                btn.classList.remove(
                                    "active"
                                );

                            }
                        );


                    this.classList.add(
                        "active"
                    );


                    renderProjects();

                }
            );

        }
    );


/* =====================================================
   SEARCH
===================================================== */

document
    .getElementById(
        "searchInput"
    )
    .addEventListener(
        "input",
        function(){

            renderProjects();

        }
    );


/* =====================================================
   PROJECT DRAWER
===================================================== */

function toggleProjects(){

    const drawer =
        document.getElementById(
            "projectsDrawer"
        );


    const button =
        document.getElementById(
            "projectIcon"
        );


    drawer.classList.toggle(
        "open"
    );


    if(
        drawer.classList.contains(
            "open"
        )
    ){

        button.textContent =
            "×";

    }else{

        button.textContent =
            "+";

    }

}


/* =====================================================
   RESET MAP
===================================================== */

function resetMap(){

    map.flyTo(
        [14.333,120.772],
        13,
        {
            duration:1.2
        }
    );

}


/* =====================================================
   DRAGGABLE SELECTED PROJECT CARD
===================================================== */

const selectedProject =
    document.getElementById(
        "selectedProject"
    );


let isDraggingProject = false;

let projectStartX = 0;
let projectStartY = 0;

let projectStartLeft = 0;
let projectStartTop = 0;


/* =====================================================
   START DRAG
===================================================== */

selectedProject.addEventListener(
    "pointerdown",
    function(e){

        /*
         * Do not drag when
         * clicking the X button.
         */

        if(
            e.target.closest(
                ".selected-close"
            )
        ){

            return;

        }


        /*
         * Only drag when card
         * is currently visible.
         */

        if(
            !selectedProject.classList.contains(
                "show"
            )
        ){

            return;

        }


        isDraggingProject = true;


        selectedProject.classList.add(
            "dragging"
        );


        const rect =
            selectedProject.getBoundingClientRect();


        projectStartX =
            e.clientX;


        projectStartY =
            e.clientY;


        projectStartLeft =
            rect.left;


        projectStartTop =
            rect.top;


        /*
         * Convert the card from
         * right/bottom positioning
         * to left/top positioning.
         */

        selectedProject.style.left =
            projectStartLeft + "px";


        selectedProject.style.top =
            projectStartTop + "px";


        selectedProject.style.right =
            "auto";


        selectedProject.style.bottom =
            "auto";


        selectedProject.setPointerCapture(
            e.pointerId
        );

    }
);


/* =====================================================
   DRAG CARD
===================================================== */

selectedProject.addEventListener(
    "pointermove",
    function(e){

        if(!isDraggingProject){

            return;

        }


        const moveX =
            e.clientX -
            projectStartX;


        const moveY =
            e.clientY -
            projectStartY;


        let newLeft =
            projectStartLeft +
            moveX;


        let newTop =
            projectStartTop +
            moveY;


        /*
         * Keep card inside
         * browser window.
         */

        const cardWidth =
            selectedProject.offsetWidth;


        const cardHeight =
            selectedProject.offsetHeight;


        const maxLeft =
            window.innerWidth -
            cardWidth;


        const maxTop =
            window.innerHeight -
            cardHeight;


        newLeft =
            Math.max(
                0,
                Math.min(
                    newLeft,
                    maxLeft
                )
            );


        newTop =
            Math.max(
                0,
                Math.min(
                    newTop,
                    maxTop
                )
            );


        selectedProject.style.left =
            newLeft + "px";


        selectedProject.style.top =
            newTop + "px";

    }
);


/* =====================================================
   END DRAG
===================================================== */

selectedProject.addEventListener(
    "pointerup",
    function(e){

        isDraggingProject = false;


        selectedProject.classList.remove(
            "dragging"
        );


        try{

            selectedProject.releasePointerCapture(
                e.pointerId
            );

        }catch(error){}

    }
);


/* =====================================================
   CANCEL DRAG
===================================================== */

selectedProject.addEventListener(
    "pointercancel",
    function(){

        isDraggingProject = false;


        selectedProject.classList.remove(
            "dragging"
        );

    }
);


/* =====================================================
   INITIAL LOAD
===================================================== */

renderProjects();

</script>

</body>

</html>