<?php
session_start();
include("db.php");

// OPTIONAL USER
$role = isset($_SESSION['role']) ? $_SESSION['role'] : "guest";
$barangay_id = isset($_SESSION['barangay_id']) ? $_SESSION['barangay_id'] : null;
?>

<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">

<title>SK KONEXT | Naic Barangay Map</title>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
>


<style>

/* =========================================================
   GLOBAL
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
    background:#06100b;
}

:root{

    --green:#4ade80;
    --green-dark:#16a34a;
    --green-light:#86efac;

    --dark:#06100b;

    --glass:rgba(7,17,12,.82);

    --border:rgba(255,255,255,.13);

    --muted:rgba(255,255,255,.60);
}


/* =========================================================
   MAP
========================================================= */

#map{

    position:absolute;

    inset:0;

    width:100%;
    height:100%;

    z-index:1;
}


/* =========================================================
   WEATHER OVERLAY
========================================================= */

#weatherOverlay{

    position:absolute;

    inset:0;

    z-index:500;

    pointer-events:none;

    overflow:hidden;

    display:none;
}


/* =========================================================
   RAIN
========================================================= */

.rain-drop{

    position:absolute;

    top:-80px;

    width:1px;

    height:55px;

    background:
        linear-gradient(
            to bottom,
            transparent,
            rgba(180,225,255,.75)
        );

    transform:rotate(18deg);

    animation:
        rainFall linear infinite;
}

@keyframes rainFall{

    0%{

        transform:
            translate3d(0,-100px,0)
            rotate(18deg);

    }

    100%{

        transform:
            translate3d(-160px,110vh,0)
            rotate(18deg);

    }

}


/* =========================================================
   HEAVY RAIN
========================================================= */

.weather-heavy .rain-drop{

    height:75px;

    animation-duration:.55s !important;

    opacity:.8;
}


/* =========================================================
   LIGHT RAIN
========================================================= */

.weather-light .rain-drop{

    height:45px;

    animation-duration:1.15s !important;

    opacity:.5;
}


/* =========================================================
   CLOUDS
========================================================= */

.cloud{

    position:absolute;

    width:180px;
    height:60px;

    border-radius:100px;

    background:
        rgba(220,235,230,.12);

    filter:blur(8px);

    animation:
        cloudMove 35s linear infinite;
}

.cloud::before{

    content:"";

    position:absolute;

    width:90px;
    height:90px;

    left:35px;
    bottom:0;

    border-radius:50%;

    background:
        rgba(220,235,230,.13);
}

.cloud::after{

    content:"";

    position:absolute;

    width:70px;
    height:70px;

    right:25px;
    bottom:0;

    border-radius:50%;

    background:
        rgba(220,235,230,.12);
}

@keyframes cloudMove{

    from{
        transform:translateX(-250px);
    }

    to{
        transform:translateX(calc(100vw + 300px));
    }

}


/* =========================================================
   WEATHER FLASH
========================================================= */

.lightning{

    position:absolute;

    inset:0;

    background:
        rgba(255,255,255,.35);

    opacity:0;

    animation:
        lightningFlash 7s infinite;
}

@keyframes lightningFlash{

    0%,
    91%,
    94%,
    100%{
        opacity:0;
    }

    92%{
        opacity:.45;
    }

    93%{
        opacity:0;
    }

    94%{
        opacity:.25;
    }

}


/* =========================================================
   WEATHER STATUS
========================================================= */

.weather-status{

    position:absolute;

    top:20px;

    left:50%;

    transform:translateX(-50%);

    z-index:1001;

    display:flex;

    align-items:center;

    gap:8px;

    padding:10px 15px;

    border-radius:50px;

    background:
        rgba(7,17,12,.88);

    border:
        1px solid rgba(255,255,255,.12);

    color:white;

    backdrop-filter:blur(18px);

    -webkit-backdrop-filter:blur(18px);

    box-shadow:
        0 12px 35px rgba(0,0,0,.25);

    font-size:11px;

    font-weight:700;

    white-space:nowrap;
}

.weather-status i{

    color:var(--green);

}

.weather-temperature{

    color:var(--green);

}


/* =========================================================
   TOP NAV
========================================================= */

.topbar{

    position:absolute;

    top:20px;

    left:20px;

    z-index:1000;

    display:flex;

    align-items:center;

    gap:12px;
}


/* BACK BUTTON */

.home-btn{

    display:flex;

    align-items:center;

    gap:9px;

    padding:12px 18px;

    background:
        rgba(7,17,12,.86);

    color:white;

    text-decoration:none;

    border:
        1px solid rgba(255,255,255,.13);

    border-radius:14px;

    font-size:13px;

    font-weight:700;

    backdrop-filter:blur(18px);

    -webkit-backdrop-filter:blur(18px);

    box-shadow:
        0 15px 40px rgba(0,0,0,.25);

    transition:.3s ease;
}

.home-btn:hover{

    background:
        rgba(74,222,128,.15);

    color:var(--green);

    transform:translateY(-2px);
}


/* MAP TITLE */

.map-title{

    display:flex;

    align-items:center;

    gap:10px;

    padding:12px 18px;

    background:
        rgba(7,17,12,.82);

    border:
        1px solid rgba(255,255,255,.12);

    border-radius:14px;

    color:white;

    font-size:13px;

    font-weight:700;

    backdrop-filter:blur(18px);

    -webkit-backdrop-filter:blur(18px);

    box-shadow:
        0 15px 40px rgba(0,0,0,.25);
}

.map-title i{
    color:var(--green);
}

.map-title span{
    color:var(--green);
}


/* =========================================================
   SIDE PANEL
========================================================= */

.sidebar{

    position:absolute;

    top:20px;

    right:20px;

    width:370px;

    max-height:
        calc(100vh - 40px);

    overflow-y:auto;

    padding:24px;

    z-index:1000;

    background:
        rgba(7,17,12,.88);

    border:
        1px solid rgba(255,255,255,.13);

    border-radius:24px;

    color:white;

    backdrop-filter:blur(25px);

    -webkit-backdrop-filter:blur(25px);

    box-shadow:
        0 25px 70px rgba(0,0,0,.35);
}


/* SCROLLBAR */

.sidebar::-webkit-scrollbar{
    width:5px;
}

.sidebar::-webkit-scrollbar-thumb{

    background:
        rgba(74,222,128,.35);

    border-radius:10px;
}


/* PANEL HEADER */

.panel-kicker{

    color:var(--green);

    font-size:10px;

    letter-spacing:2px;

    font-weight:800;

    text-transform:uppercase;

    margin-bottom:8px;
}

#barangayName{

    font-family:"Manrope",sans-serif;

    font-size:28px;

    line-height:1.05;

    letter-spacing:-1px;

    color:white;

    margin-bottom:4px;
}

.panel-description{

    color:var(--muted);

    font-size:12px;

    line-height:1.6;

    margin-bottom:18px;
}


/* =========================================================
   SEARCH
========================================================= */

.search-wrapper{

    position:relative;

    margin-bottom:18px;
}

.search-wrapper i{

    position:absolute;

    left:14px;

    top:50%;

    transform:translateY(-50%);

    color:
        rgba(255,255,255,.4);

    font-size:13px;
}

#searchBox{

    width:100%;

    padding:13px 14px 13px 38px;

    border-radius:13px;

    border:
        1px solid rgba(255,255,255,.12);

    background:
        rgba(255,255,255,.06);

    color:white;

    outline:none;

    font-family:"DM Sans",sans-serif;

    font-size:13px;

    transition:.3s ease;
}

#searchBox::placeholder{

    color:
        rgba(255,255,255,.35);
}

#searchBox:focus{

    border-color:
        rgba(74,222,128,.5);

    background:
        rgba(74,222,128,.06);

    box-shadow:
        0 0 0 3px rgba(74,222,128,.06);
}


/* =========================================================
   STATISTICS
========================================================= */

.stats-wrapper{

    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:8px;

    margin-top:5px;
}

.stat-box{

    padding:15px 8px;

    background:
        rgba(255,255,255,.055);

    border:
        1px solid rgba(255,255,255,.08);

    border-radius:15px;

    text-align:center;

    transition:.3s ease;
}

.stat-box:hover{

    background:
        rgba(74,222,128,.08);

    border-color:
        rgba(74,222,128,.22);

    transform:translateY(-2px);
}

.stat-number{

    font-family:"Manrope",sans-serif;

    font-size:25px;

    font-weight:800;

    color:var(--green);

    line-height:1;

    margin-bottom:7px;
}

.stat-box small{

    color:
        rgba(255,255,255,.5);

    font-size:10px;

    text-transform:uppercase;

    letter-spacing:.5px;
}


/* =========================================================
   OPEN BARANGAY
========================================================= */

.btn-open{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:9px;

    width:100%;

    padding:14px;

    margin-top:15px;

    border-radius:14px;

    background:
        linear-gradient(
            135deg,
            #4ade80,
            #16a34a
        );

    color:#06100b !important;

    text-decoration:none;

    font-size:13px;

    font-weight:800;

    box-shadow:
        0 10px 30px rgba(74,222,128,.16);

    transition:.3s ease;
}

.btn-open:hover{

    transform:translateY(-3px);

    box-shadow:
        0 15px 35px rgba(74,222,128,.25);
}

.btn-open.disabled{

    opacity:.35;

    pointer-events:none;

    filter:grayscale(.5);
}


/* =========================================================
   MAP MODE
========================================================= */

.map-mode{

    display:flex;

    gap:7px;

    margin-top:18px;
}

.mode-btn{

    flex:1;

    border:
        1px solid rgba(255,255,255,.10);

    background:
        rgba(255,255,255,.05);

    color:
        rgba(255,255,255,.6);

    padding:10px;

    border-radius:11px;

    cursor:pointer;

    font-family:"DM Sans",sans-serif;

    font-size:11px;

    font-weight:700;

    transition:.3s ease;
}

.mode-btn:hover{

    background:
        rgba(255,255,255,.1);

    color:white;
}

.mode-btn.active{

    background:
        rgba(74,222,128,.14);

    border-color:
        rgba(74,222,128,.3);

    color:var(--green);
}


/* =========================================================
   PIN COUNTER
========================================================= */

.pin-count{

    position:absolute;

    bottom:20px;

    left:20px;

    z-index:1000;

    display:flex;

    align-items:center;

    gap:8px;

    padding:11px 15px;

    background:
        rgba(7,17,12,.88);

    border:
        1px solid rgba(255,255,255,.12);

    color:white;

    border-radius:13px;

    font-size:12px;

    font-weight:700;

    backdrop-filter:blur(18px);

    -webkit-backdrop-filter:blur(18px);

    box-shadow:
        0 12px 35px rgba(0,0,0,.25);
}

.pin-count i{
    color:var(--green);
}


/* =========================================================
   BARANGAY LABELS
========================================================= */

.barangay-label{

    background:
        rgba(5,12,8,.88) !important;

    border:
        1px solid rgba(74,222,128,.45) !important;

    color:white !important;

    border-radius:8px !important;

    padding:5px 9px !important;

    font-family:"DM Sans",sans-serif !important;

    font-size:11px !important;

    font-weight:700 !important;

    white-space:nowrap;

    box-shadow:
        0 5px 18px rgba(0,0,0,.4) !important;

    text-shadow:
        0 1px 2px rgba(0,0,0,.7);
}

.barangay-label::before{
    display:none !important;
}


/* SELECTED LABEL */

.barangay-label-selected{

    background:
        linear-gradient(
            135deg,
            #4ade80,
            #16a34a
        ) !important;

    color:#06100b !important;

    border-color:
        rgba(255,255,255,.65) !important;

    box-shadow:
        0 7px 25px rgba(74,222,128,.35) !important;

    text-shadow:none !important;
}


/* =========================================================
   LEAFLET
========================================================= */

.leaflet-control-zoom{

    border:none !important;

    box-shadow:
        0 10px 30px rgba(0,0,0,.3) !important;
}

.leaflet-control-zoom a{

    background:
        rgba(7,17,12,.88) !important;

    color:white !important;

    border:none !important;
}

.leaflet-control-zoom a:hover{

    background:
        rgba(74,222,128,.18) !important;

    color:var(--green) !important;
}

.leaflet-control-attribution{

    background:
        rgba(7,17,12,.75) !important;

    color:
        rgba(255,255,255,.5) !important;

    border-radius:
        7px 0 0 0;

    backdrop-filter:
        blur(10px);
}

.leaflet-control-attribution a{
    color:var(--green) !important;
}


/* =========================================================
   POPUP
========================================================= */

.leaflet-popup-content-wrapper{

    background:#091810;

    color:white;

    border-radius:15px;

    border:
        1px solid rgba(255,255,255,.12);

    box-shadow:
        0 15px 45px rgba(0,0,0,.4);
}

.leaflet-popup-tip{
    background:#091810;
}

.leaflet-popup-content{

    margin:16px;

    font-family:"DM Sans",sans-serif;

    font-size:13px;

    line-height:1.6;
}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:800px){

    .topbar{

        top:10px;

        left:10px;

        right:10px;

        gap:7px;
    }

    .map-title{
        display:none;
    }

    .home-btn{
        padding:11px 15px;
    }

    .weather-status{

        top:10px;

        left:auto;

        right:10px;

        transform:none;

        font-size:10px;
    }

    .sidebar{

        top:auto;

        bottom:10px;

        left:10px;

        right:10px;

        width:auto;

        max-height:45vh;

        padding:18px;

        border-radius:20px;
    }

    #barangayName{
        font-size:23px;
    }

    .pin-count{

        bottom:auto;

        top:65px;

        left:10px;
    }

}


/* =========================================================
   REDUCED MOTION
========================================================= */

@media(prefers-reduced-motion:reduce){

    *{
        transition:none !important;
    }

    .rain-drop,
    .cloud,
    .lightning{
        animation:none !important;
    }

}

</style>

</head>


<body>


<!-- =====================================================
     WEATHER ANIMATION
===================================================== -->

<div id="weatherOverlay"></div>


<!-- =====================================================
     WEATHER STATUS
===================================================== -->

<div
    class="weather-status"
    id="weatherStatus"
>

    <i class="fa-solid fa-cloud-sun"></i>

    <span id="weatherText">
        Checking Naic weather...
    </span>

    <span
        class="weather-temperature"
        id="weatherTemp"
    ></span>

</div>


<!-- =====================================================
     TOP BAR
===================================================== -->

<div class="topbar">

    <a
        href="index.php"
        class="home-btn"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back

    </a>


    <div class="map-title">

        <i class="fa-solid fa-map-location-dot"></i>

        <span>SK KONEXT</span>

        Community Map

    </div>

</div>


<!-- =====================================================
     PIN COUNTER
===================================================== -->

<div
    class="pin-count"
    id="pinCount"
>

    <i class="fa-solid fa-location-dot"></i>

    Pins: 0

</div>


<!-- =====================================================
     MAP
===================================================== -->

<div id="map"></div>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">

    <div class="panel-kicker">

        NAIC COMMUNITY MAP

    </div>


    <h4 id="barangayName">

        Select a Barangay

    </h4>


    <p class="panel-description">

        Select a barangay on the map to view
        community statistics and information.

    </p>


    <!-- SEARCH -->

    <div class="search-wrapper">

        <i class="fa-solid fa-magnifying-glass"></i>

        <input
            id="searchBox"
            placeholder="Search barangay..."
            onkeyup="searchBarangay()"
        >

    </div>


    <!-- STATISTICS -->

    <div class="stats-wrapper">

        <div class="stat-box">

            <div
                class="stat-number"
                id="finished"
            >
                0
            </div>

            <small>
                Finished
            </small>

        </div>


        <div class="stat-box">

            <div
                class="stat-number"
                id="upcoming"
            >
                0
            </div>

            <small>
                Upcoming
            </small>

        </div>


        <div class="stat-box">

            <div
                class="stat-number"
                id="scholars"
            >
                0
            </div>

            <small>
                Scholars
            </small>

        </div>

    </div>


    <!-- OPEN BARANGAY -->

    <a
        id="openBarangay"
        class="btn-open disabled"
        href="javascript:void(0)"
    >

        <i class="fa-solid fa-arrow-up-right-from-square"></i>

        Open Barangay Page

    </a>


    <!-- MAP MODE -->

    <div class="map-mode">

        <button
            class="mode-btn active"
            id="streetBtn"
            onclick="setMapMode('street')"
        >

            <i class="fa-solid fa-map"></i>

            Street

        </button>


        <button
            class="mode-btn"
            id="satelliteBtn"
            onclick="setMapMode('satellite')"
        >

            <i class="fa-solid fa-satellite"></i>

            Satellite

        </button>

    </div>

</div>


<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>


<script>

/* =========================================================
   VARIABLES
========================================================= */

let selectedBarangayName = null;


/* =========================================================
   MAP
========================================================= */

const map = L.map(
    'map',
    {

        zoomControl:true,

        preferCanvas:true,

        /*
           Prevent the user from zooming
           too far away from Naic.
        */

        minZoom:12,

        maxZoom:19,

        /*
           Prevent panning far outside
           the Naic area.
        */

        maxBoundsViscosity:1.0

    }
).setView(
    [14.318,120.768],
    13
);


/* =========================================================
   MAP LAYERS
========================================================= */

const streetLayer = L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
        maxZoom:19,

        attribution:
            '&copy; OpenStreetMap contributors'
    }
);


const satelliteLayer = L.tileLayer(
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    {
        maxZoom:19,

        attribution:
            'Tiles &copy; Esri'
    }
);


/* DEFAULT */

streetLayer.addTo(map);


/* =========================================================
   BARANGAYS
========================================================= */

const barangays = [

    {name:"Bagong Kalsada",lat:14.3211,lng:120.7535},

    {name:"Balsahan",lat:14.3198,lng:120.7627},

    {name:"Bancaan",lat:14.3172,lng:120.7512},

    {name:"Bucana Malaki",lat:14.3238,lng:120.7561},

    {name:"Bucana Sasahan",lat:14.3232,lng:120.7598},

    {name:"Capt. C. Nazareno",lat:14.3179,lng:120.7656},

    {name:"Calubcob",lat:14.2988,lng:120.7877},

    {name:"Gomez-Zamora",lat:14.3199893,lng:120.7653072},

    {name:"Halang",lat:14.2939,lng:120.8007},

    {name:"Humbac",lat:14.3169,lng:120.7691},

    {name:"Ibayo Estacion",lat:14.3236,lng:120.7650},

    {name:"Ibayo Silangan",lat:14.325254,lng:120.767446},

    {name:"Kanluran Rizal",lat:14.3169,lng:120.7640},

    {name:"Labac",lat:14.3124,lng:120.7379},

    {name:"Latoria",lat:14.3216,lng:120.7615},

    {name:"Mabolo",lat:14.3148,lng:120.7476},

    {name:"Makina",lat:14.3143,lng:120.7708},

    {name:"Malainen Bago",lat:14.3028,lng:120.7666},

    {name:"Malainen Luma",lat:14.2705,lng:120.7922},

    {name:"Molino",lat:14.2804,lng:120.7781},

    {name:"Munting Mapino",lat:14.3341,lng:120.7738},

    {name:"Muzon",lat:14.2941,lng:120.7551},

    {name:"Palangue 1",lat:14.2857,lng:120.8097},

    {name:"Palangue 2 & 3",lat:14.2618,lng:120.8297},

    {name:"Sabang",lat:14.3134,lng:120.8011},

    {name:"San Roque",lat:14.3078,lng:120.7767},

    {name:"Santulan",lat:14.3138,lng:120.7690},

    {name:"Sapa",lat:14.3202,lng:120.7574},

    {name:"Timalan Balsahan",lat:14.340389,lng:120.782747},

    {name:"Timalan Concepcion",lat:14.331206,lng:120.783723}

];


let allMarkers = [];


/* =========================================================
   NAIC MAP BOUNDS
========================================================= */

/*
   These bounds are intentionally based around
   the barangays already included in your map.

   This keeps the map focused on Naic.
*/

const naicBounds = L.latLngBounds(

    [14.245,120.720],

    [14.355,120.845]

);


/*
   Apply the restriction.
*/

map.setMaxBounds(naicBounds);


/*
   If user somehow drags toward the edge,
   Leaflet pulls the map back inside Naic.
*/

map.on(
    "drag",
    function(){

        map.panInsideBounds(
            naicBounds,
            {
                animate:false
            }
        );

    }
);


/* =========================================================
   CREATE BARANGAY MARKERS
========================================================= */

barangays.forEach(
    (b,index)=>{

        const marker =
            L.marker(
                [b.lat,b.lng],
                {
                    title:b.name
                }
            ).addTo(map);


        /*
           PERMANENT LABEL
        */

        marker.bindTooltip(
            b.name,
            {

                permanent:true,

                direction:"top",

                offset:[0,-12],

                className:
                    "barangay-label",

                opacity:1

            }
        );


        marker.on(
            "click",
            function(){

                selectBarangay(
                    b.name
                );


                map.flyTo(
                    [b.lat,b.lng],
                    15,
                    {
                        duration:.8
                    }
                );

            }
        );


        allMarkers.push(
            marker
        );

    }
);


/* =========================================================
   SEARCH BARANGAY
========================================================= */

function searchBarangay(){

    let val =
        document
        .getElementById(
            "searchBox"
        )
        .value
        .toLowerCase()
        .trim();


    allMarkers.forEach(
        (marker,i)=>{

            const barangay =
                barangays[i];


            if(
                barangay.name
                .toLowerCase()
                .includes(val)
            ){

                if(
                    !map.hasLayer(
                        marker
                    )
                ){

                    marker.addTo(
                        map
                    );

                }

            }else{

                if(
                    map.hasLayer(
                        marker
                    )
                ){

                    map.removeLayer(
                        marker
                    );

                }

            }

        }
    );

}


/* =========================================================
   MAP MODE
========================================================= */

function setMapMode(mode){

    const streetBtn =
        document.getElementById(
            "streetBtn"
        );

    const satelliteBtn =
        document.getElementById(
            "satelliteBtn"
        );


    if(
        mode === "satellite"
    ){

        if(
            map.hasLayer(
                streetLayer
            )
        ){

            map.removeLayer(
                streetLayer
            );

        }

        satelliteLayer.addTo(
            map
        );


        satelliteBtn.classList.add(
            "active"
        );

        streetBtn.classList.remove(
            "active"
        );

    }

    else{

        if(
            map.hasLayer(
                satelliteLayer
            )
        ){

            map.removeLayer(
                satelliteLayer
            );

        }

        streetLayer.addTo(
            map
        );


        streetBtn.classList.add(
            "active"
        );

        satelliteBtn.classList.remove(
            "active"
        );

    }

}


/* =========================================================
   PINS
========================================================= */

let pinLayer =
    L.layerGroup().addTo(map);


/* =========================================================
   LOAD PINS
========================================================= */

function loadPins(){

    fetch(
        "get_map_pins.php?barangay_id=<?php echo $barangay_id; ?>"
    )

    .then(
        r=>r.json()
    )

    .then(
        data=>{

            document
            .getElementById(
                "pinCount"
            )
            .innerHTML =

                `
                <i class="fa-solid fa-location-dot"></i>
                Pins: ${data.length}
                `;


            pinLayer.clearLayers();


            data.forEach(
                pin=>{

                    L.marker(
                        [
                            pin.lat,
                            pin.lng
                        ]
                    )

                    .addTo(
                        pinLayer
                    )

                    .bindPopup(

                        `

                        <div style="
                            min-width:180px;
                        ">

                            <div style="
                                font-weight:800;
                                font-size:15px;
                                margin-bottom:5px;
                            ">

                                ${pin.title}

                            </div>

                            <div style="
                                color:rgba(255,255,255,.65);
                                font-size:12px;
                            ">

                                ${pin.details || ""}

                            </div>

                            <br>

                            <button
                                onclick="deletePin(${pin.id})"
                                class="btn btn-danger btn-sm"
                            >

                                Delete

                            </button>

                        </div>

                        `
                    );

                }
            );

        }
    )

    .catch(
        error=>{

            console.error(
                "Unable to load pins:",
                error
            );

        }
    );

}


/* =========================================================
   SAVE PIN
========================================================= */

function savePin(lat,lng){

    const title =
        document
        .getElementById(
            "pinTitle"
        )
        .value;


    const details =
        document
        .getElementById(
            "pinDetails"
        )
        .value;


    const barangay =
        document
        .getElementById(
            "pinBarangay"
        )
        .value;


    let formData =
        new FormData();


    formData.append(
        "title",
        title
    );

    formData.append(
        "details",
        details
    );

    formData.append(
        "barangay",
        barangay
    );

    formData.append(
        "lat",
        lat
    );

    formData.append(
        "lng",
        lng
    );


    fetch(
        "save_map_pin.php",
        {
            method:"POST",

            body:formData
        }
    )

    .then(
        r=>r.text()
    )

    .then(
        ()=>{

            alert(
                "Saved!"
            );


            if(
                typeof tempMarker
                !== "undefined"
            ){

                map.removeLayer(
                    tempMarker
                );

            }


            loadPins();

        }
    );

}


/* =========================================================
   DELETE PIN
========================================================= */

function deletePin(id){

    if(
        !confirm(
            "Delete this pin?"
        )
    ){

        return;

    }


    fetch(
        "delete_map_pin.php",
        {

            method:"POST",

            headers:{
                "Content-Type":
                    "application/json"
            },

            body:JSON.stringify({
                id:id
            })

        }
    )

    .then(
        r=>r.text()
    )

    .then(
        ()=>{

            alert(
                "Deleted"
            );

            loadPins();

        }
    );

}


/* =========================================================
   BARANGAY SELECT
========================================================= */

function selectBarangay(name){

    selectedBarangayName =
        name;


    document
    .getElementById(
        "barangayName"
    )
    .innerText =
        name;


    fetch(
        "get_barangay_stats.php?barangay="
        +
        encodeURIComponent(
            name
        )
    )

    .then(
        r=>r.json()
    )

    .then(
        data=>{

            document
            .getElementById(
                "finished"
            )
            .innerText =
                data.finished;


            document
            .getElementById(
                "upcoming"
            )
            .innerText =
                data.upcoming;


            document
            .getElementById(
                "scholars"
            )
            .innerText =
                data.scholars;

        }
    );


    let btn =
        document
        .getElementById(
            "openBarangay"
        );


    btn.classList.remove(
        "disabled"
    );


    btn.href =
        "barangay_view.php?name="
        +
        encodeURIComponent(
            name
        );


    /*
       Reset all labels.
    */

    allMarkers.forEach(
        marker=>{

            const tooltip =
                marker.getTooltip();


            if(!tooltip){
                return;
            }


            const label =
                tooltip.getElement();


            if(!label){
                return;
            }


            label.classList.remove(
                "barangay-label-selected"
            );

        }
    );


    /*
       Highlight selected.
    */

    const selected =
        barangays.find(
            b=>b.name === name
        );


    if(selected){

        const selectedIndex =
            barangays.indexOf(
                selected
            );


        const selectedMarker =
            allMarkers[
                selectedIndex
            ];


        if(
            selectedMarker
            &&
            selectedMarker.getTooltip()
        ){

            selectedMarker
            .getTooltip()
            .getElement()
            .classList.add(
                "barangay-label-selected"
            );

        }

    }


    loadPins();

}


/* =========================================================
   WEATHER SYSTEM
========================================================= */

/*
   Naic, Cavite coordinates.

   Open-Meteo is used here so you do NOT need
   to create another weather API key.
*/

const NAIC_LAT = 14.3181;

const NAIC_LNG = 120.7660;


/* =========================================================
   WEATHER OVERLAY
========================================================= */

const weatherOverlay =
    document.getElementById(
        "weatherOverlay"
    );


const weatherText =
    document.getElementById(
        "weatherText"
    );


const weatherTemp =
    document.getElementById(
        "weatherTemp"
    );


/* =========================================================
   WEATHER ICON
========================================================= */

function weatherIcon(
    weatherCode
){

    if(
        weatherCode === 0
    ){

        return "fa-sun";

    }


    if(
        weatherCode >= 1
        &&
        weatherCode <= 3
    ){

        return "fa-cloud-sun";

    }


    if(
        weatherCode >= 45
        &&
        weatherCode <= 48
    ){

        return "fa-smog";

    }


    if(
        weatherCode >= 51
        &&
        weatherCode <= 67
    ){

        return "fa-cloud-rain";

    }


    if(
        weatherCode >= 71
        &&
        weatherCode <= 77
    ){

        return "fa-snowflake";

    }


    if(
        weatherCode >= 80
        &&
        weatherCode <= 82
    ){

        return "fa-cloud-showers-heavy";

    }


    if(
        weatherCode >= 95
    ){

        return "fa-cloud-bolt";

    }


    return "fa-cloud";

}


/* =========================================================
   WEATHER DESCRIPTION
========================================================= */

function weatherDescription(
    code
){

    if(code === 0){
        return "Clear sky";
    }

    if(code === 1){
        return "Mainly clear";
    }

    if(code === 2){
        return "Partly cloudy";
    }

    if(code === 3){
        return "Overcast";
    }

    if(
        code === 45 ||
        code === 48
    ){
        return "Foggy";
    }

    if(
        code >= 51 &&
        code <= 57
    ){
        return "Drizzle";
    }

    if(
        code >= 61 &&
        code <= 67
    ){
        return "Rain";
    }

    if(
        code >= 71 &&
        code <= 77
    ){
        return "Snow";
    }

    if(
        code >= 80 &&
        code <= 82
    ){
        return "Rain showers";
    }

    if(
        code >= 95
    ){
        return "Thunderstorm";
    }

    return "Weather update";

}


/* =========================================================
   CLEAR WEATHER
========================================================= */

function showClearWeather(){

    weatherOverlay.innerHTML = "";

    weatherOverlay.style.display =
        "none";

}


/* =========================================================
   CLOUDY WEATHER
========================================================= */

function showCloudyWeather(){

    weatherOverlay.innerHTML = "";

    weatherOverlay.style.display =
        "block";


    for(
        let i=0;
        i<4;
        i++
    ){

        const cloud =
            document.createElement(
                "div"
            );

        cloud.className =
            "cloud";


        cloud.style.top =
            (
                8 +
                Math.random()*45
            )
            +
            "%";


        cloud.style.animationDelay =
            (
                -Math.random()*35
            )
            +
            "s";


        cloud.style.opacity =
            (
                .35 +
                Math.random()*.35
            );


        weatherOverlay.appendChild(
            cloud
        );

    }

}


/* =========================================================
   RAIN WEATHER
========================================================= */

function showRainWeather(
    heavy=false
){

    weatherOverlay.innerHTML = "";

    weatherOverlay.style.display =
        "block";


    if(heavy){

        weatherOverlay.classList.add(
            "weather-heavy"
        );

    }else{

        weatherOverlay.classList.add(
            "weather-light"
        );

    }


    /*
       Create rain drops.
    */

    const amount =
        heavy ? 150 : 85;


    for(
        let i=0;
        i<amount;
        i++
    ){

        const drop =
            document.createElement(
                "div"
            );


        drop.className =
            "rain-drop";


        drop.style.left =
            Math.random()*100
            +
            "%";


        drop.style.animationDelay =
            (
                Math.random()*1.5
            )
            +
            "s";


        drop.style.animationDuration =
            (
                heavy
                ?
                .45 + Math.random()*.5
                :
                .75 + Math.random()*.8
            )
            +
            "s";


        weatherOverlay.appendChild(
            drop
        );

    }

}


/* =========================================================
   THUNDERSTORM
========================================================= */

function showThunderstorm(){

    showRainWeather(
        true
    );


    const lightning =
        document.createElement(
            "div"
        );


    lightning.className =
        "lightning";


    weatherOverlay.appendChild(
        lightning
    );

}


/* =========================================================
   UPDATE WEATHER ANIMATION
========================================================= */

function updateWeatherAnimation(
    code
){

    /*
       Clear
    */

    if(code === 0){

        showClearWeather();

        return;

    }


    /*
       Cloudy
    */

    if(
        code >= 1 &&
        code <= 48
    ){

        showCloudyWeather();

        return;

    }


    /*
       Light/moderate rain
    */

    if(
        (
            code >= 51 &&
            code <= 67
        )
        ||
        (
            code >= 80 &&
            code <= 82
        )
    ){

        const heavy =
            code >= 63 ||
            code >= 81;


        showRainWeather(
            heavy
        );

        return;

    }


    /*
       Thunderstorm
    */

    if(code >= 95){

        showThunderstorm();

        return;

    }


    showClearWeather();

}


/* =========================================================
   LOAD NAIC WEATHER
========================================================= */

function loadNaicWeather(){

    const weatherURL =

        "https://api.open-meteo.com/v1/forecast"
        +
        "?latitude="
        +
        NAIC_LAT
        +
        "&longitude="
        +
        NAIC_LNG
        +
        "&current=temperature_2m,weather_code"
        +
        "&timezone=Asia%2FManila";


    fetch(
        weatherURL
    )

    .then(
        response=>{

            if(
                !response.ok
            ){

                throw new Error(
                    "Weather request failed"
                );

            }

            return response.json();

        }
    )

    .then(
        data=>{

            const current =
                data.current;


            const code =
                current.weather_code;


            const temperature =
                Math.round(
                    current.temperature_2m
                );


            /*
               Update text.
            */

            weatherText.innerText =
                weatherDescription(
                    code
                );


            weatherTemp.innerText =
                temperature
                +
                "°C";


            /*
               Update icon.
            */

            const status =
                document.getElementById(
                    "weatherStatus"
                );


            const icon =
                status.querySelector(
                    "i"
                );


            icon.className =
                "fa-solid "
                +
                weatherIcon(
                    code
                );


            /*
               Update animation.
            */

            updateWeatherAnimation(
                code
            );

        }
    )

    .catch(
        error=>{

            console.error(
                "Weather error:",
                error
            );


            weatherText.innerText =
                "Weather unavailable";

            weatherTemp.innerText =
                "";

            showClearWeather();

        }
    );

}


/* =========================================================
   INITIALIZE WEATHER
========================================================= */

loadNaicWeather();


/*
   Refresh every 10 minutes.
*/

setInterval(
    loadNaicWeather,
    10 * 60 * 1000
);


/* =========================================================
   INITIAL PIN LOAD
========================================================= */

loadPins();


/* =========================================================
   CLICK MAP
========================================================= */

map.on(
    "click",
    function(e){

        console.log(
            "Map clicked:",
            e.latlng.lat,
            e.latlng.lng
        );

    }
);

</script>

</body>
</html>