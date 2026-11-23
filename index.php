<?php
include("db.php");

/* ================================
   ACCOUNT ACCESS
================================ */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = !empty($_SESSION['user_id']);

/* ================================
   DATABASE STATISTICS
================================ */
$barangay_count = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM barangays
"))['total'];

$scholar_count = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM scholars
"))['total'];

$sk_count = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM users WHERE role='sk_member'
"))['total'];

$project_count = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM map_projects
"))['total'];

/* ================================
   RECENT ACTIVITIES
================================ */
$activities = mysqli_query($conn, "
    SELECT * FROM records ORDER BY id DESC LIMIT 3
");

/* ================================
   SCHOLARSHIP STATUS
================================ */
$scholarship_query = mysqli_query($conn, "
    SELECT * FROM scholar_batches
    WHERE status = 'Active'
      AND application_status = 'Open'
      AND start_date <= CURDATE()
      AND (end_date IS NULL OR end_date >= CURDATE())
    ORDER BY id DESC
    LIMIT 1
");

$active_scholarship = $scholarship_query
    ? mysqli_fetch_assoc($scholarship_query)
    : null;

$scholarship_open = ($active_scholarship !== null);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SK KONEXT | Sangguniang Kabataan</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{
    font-family:"DM Sans",sans-serif;
    background:#06100b;
    color:#fff;
    overflow-x:hidden
}
a{text-decoration:none;color:inherit}

:root{
    --green:#4ade80;
    --green-dark:#16a34a;
    --green-light:#86efac;
    --dark:#06100b;
    --dark-2:#091810;
    --white:#fff;
    --muted:rgba(255,255,255,.60);
    --glass:rgba(255,255,255,.07);
    --border:rgba(255,255,255,.12);
}

/* BACKGROUND */
body::before{
    content:"";
    position:fixed;
    inset:0;
    pointer-events:none;
    background:
        radial-gradient(circle at 15% 20%,rgba(74,222,128,.10),transparent 28%),
        radial-gradient(circle at 85% 70%,rgba(22,163,74,.08),transparent 30%);
    z-index:-2;
}
body::after{
    content:"";
    position:fixed;
    inset:0;
    pointer-events:none;
    background-image:
        linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),
        linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);
    background-size:70px 70px;
    mask-image:linear-gradient(to bottom,black,transparent 85%);
    z-index:-1;
}

/* NAVIGATION */
.navbar{
    position:fixed;
    top:22px;
    left:50%;
    transform:translateX(-50%);
    width:min(1180px,calc(100% - 40px));
    height:70px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 25px;
    background:rgba(7,17,12,.72);
    border:1px solid var(--border);
    border-radius:18px;
    backdrop-filter:blur(18px);
    -webkit-backdrop-filter:blur(18px);
    z-index:999;
    box-shadow:0 15px 50px rgba(0,0,0,.25);
}
.nav-logo{
    display:flex;
    align-items:center;
    gap:12px;
    font-family:"Manrope",sans-serif;
    font-weight:800;
    letter-spacing:-.5px
}
.nav-logo-mark{
    width:42px;
    height:42px;
    border-radius:12px;
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.15);
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
}
.nav-logo-mark img{
    width:35px;
    height:35px;
    object-fit:contain
}
.nav-logo span{color:var(--green)}
.nav-links{
    display:flex;
    align-items:center;
    gap:8px
}
.nav-links a{
    padding:11px 15px;
    border-radius:10px;
    color:rgba(255,255,255,.72);
    font-size:14px;
    transition:.3s ease
}
.nav-links a:hover{
    background:rgba(255,255,255,.07);
    color:#fff
}
.nav-login{
    background:#fff!important;
    color:#07100c!important;
    font-weight:700;
    padding:11px 20px!important
}
.nav-login:hover{
    background:var(--green)!important;
    transform:translateY(-2px)
}

/* HERO */
.hero{
    min-height:100vh;
    position:relative;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:150px 30px 90px;
    overflow:hidden;
    z-index:1;
}
.hero-glow{
    position:absolute;
    width:600px;
    height:600px;
    border-radius:50%;
    background:rgba(34,197,94,.09);
    filter:blur(100px);
    pointer-events:none;
}
.hero-content{
    width:min(1250px,100%);
    display:grid;
    grid-template-columns:1fr 450px;
    align-items:center;
    gap:70px;
    position:relative;
    z-index:2;
}
.hero-left{max-width:750px}
.live-badge{
    display:inline-flex;
    align-items:center;
    gap:9px;
    padding:8px 13px;
    border:1px solid rgba(74,222,128,.25);
    background:rgba(74,222,128,.07);
    border-radius:50px;
    color:var(--green-light);
    font-size:11px;
    font-weight:700;
    letter-spacing:1.4px;
    margin-bottom:28px;
}
.live-dot{
    width:7px;
    height:7px;
    border-radius:50%;
    background:var(--green);
    box-shadow:0 0 12px var(--green);
    animation:pulse 1.8s infinite;
}
@keyframes pulse{
    0%{opacity:1;transform:scale(1)}
    50%{opacity:.4;transform:scale(.65)}
    100%{opacity:1;transform:scale(1)}
}
.hero-title{
    font-family:"Manrope",sans-serif;
    font-size:clamp(65px,9vw,125px);
    line-height:.84;
    letter-spacing:-7px;
    font-weight:800;
    margin-bottom:35px;
}
.hero-title .green{color:var(--green)}
.hero-title .outline{
    color:transparent;
    -webkit-text-stroke:1.5px rgba(255,255,255,.65)
}
.hero-description{
    max-width:620px;
    color:var(--muted);
    font-size:17px;
    line-height:1.7;
    margin-bottom:30px
}
.hero-buttons{
    display:flex;
    flex-wrap:wrap;
    gap:12px
}
.btn{
    display:inline-flex;
    align-items:center;
    gap:9px;
    padding:14px 20px;
    border-radius:12px;
    font-weight:700;
    font-size:14px;
    transition:.3s ease;
    border:1px solid var(--border);
}
.btn-green{
    background:var(--green);
    color:#06100b;
    border-color:var(--green)
}
.btn-green:hover{
    transform:translateY(-3px);
    box-shadow:0 12px 35px rgba(74,222,128,.22)
}
.btn-dark{
    background:rgba(255,255,255,.05);
    color:#fff
}
.btn-dark:hover{
    background:rgba(255,255,255,.1);
    transform:translateY(-3px)
}

/* EXISTING 3D LOGO AREA */
.logo-stage{
    min-height:450px;
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
    perspective:1200px
}
.logo-shadow{
    position:absolute;
    width:260px;
    height:55px;
    bottom:50px;
    border-radius:50%;
    background:rgba(0,0,0,.65);
    filter:blur(25px);
    transform:rotateX(70deg)
}
.sk-logo-3d{
    position:relative;
    width:310px;
    height:310px;
    transform-style:preserve-3d;
    cursor:pointer;
    animation:logoFloat 4s ease-in-out infinite;
    transition:transform .15s ease-out
}
.sk-logo-3d img{
    width:100%;
    height:100%;
    object-fit:contain;
    display:block;
    border-radius:50%
}
.logo-main,.logo-depth{
    position:absolute;
    inset:0;
    transform-style:preserve-3d
}
.logo-main{
    z-index:10;
    transform:translateZ(30px);
    filter:drop-shadow(0 18px 22px rgba(0,0,0,.45))
           drop-shadow(0 0 30px rgba(74,222,128,.15));
    overflow:hidden;
    border-radius:50%;
}

/* CHROME / SUNLIGHT REFLECTION */
.logo-main::after{
    content:"";
    position:absolute;
    top:-35%;
    left:-100%;
    width:55%;
    height:170%;
    pointer-events:none;
    border-radius:50%;
    background:linear-gradient(
        105deg,
        transparent 0%,
        rgba(255,255,255,.02) 25%,
        rgba(255,255,255,.28) 42%,
        rgba(255,255,255,.88) 50%,
        rgba(220,255,235,.48) 57%,
        rgba(255,255,255,.08) 70%,
        transparent 100%
    );
    filter:blur(3px);
    mix-blend-mode:screen;
    opacity:0;
    transform:skewX(-18deg);
    z-index:20;
}

.sk-logo-3d.is-shining .logo-main::after{
    animation:logoSunlightShine 1s cubic-bezier(.2,.65,.3,1) forwards;
}

@keyframes logoSunlightShine{
    0%{
        left:-100%;
        opacity:0;
    }
    15%{
        opacity:.9;
    }
    55%{
        opacity:1;
    }
    100%{
        left:150%;
        opacity:0;
    }
}

.logo-depth{opacity:.95}
.depth-1{
    z-index:3;
    transform:translateZ(15px) translate(3px,3px);
    filter:brightness(.82)
}
.depth-2{
    z-index:2;
    transform:translateZ(5px) translate(6px,6px);
    filter:brightness(.65)
}
.depth-3{
    z-index:1;
    transform:translateZ(-5px) translate(9px,9px);
    filter:brightness(.45)
}
@keyframes logoFloat{
    0%,100%{transform:translateY(0) rotateX(0deg) rotateY(0deg)}
    50%{transform:translateY(-13px) rotateX(2deg) rotateY(-2deg)}
}
.sk-logo-3d.animate{
    animation:logo3DAnimation 2.8s cubic-bezier(.2,.8,.2,1)
}
@keyframes logo3DAnimation{
    0%{transform:rotateX(0) rotateY(0) scale(1)}
    20%{transform:rotateX(12deg) rotateY(-20deg) scale(1.04)}
    45%{transform:rotateX(-8deg) rotateY(22deg) scale(1.08)}
    70%{transform:rotateX(6deg) rotateY(-10deg) scale(1.04)}
    100%{transform:rotateX(0) rotateY(0) scale(1)}
}
.sk-logo-3d::before{
    content:"";
    position:absolute;
    width:85%;
    height:85%;
    left:7.5%;
    top:7.5%;
    border-radius:50%;
    background:rgba(74,222,128,.14);
    filter:blur(45px);
    z-index:-10;
    transform:translateZ(-40px)
}

/* VERTICAL LABEL */
.vertical-label{
    position:absolute;
    right:30px;
    top:50%;
    transform:translateY(-50%) rotate(90deg);
    transform-origin:center;
    font-size:10px;
    letter-spacing:4px;
    color:rgba(255,255,255,.35);
    font-weight:700
}

/* SCROLL INDICATOR */
.scroll-indicator{
    position:absolute;
    bottom:30px;
    left:50%;
    transform:translateX(-50%);
    display:flex;
    align-items:center;
    gap:10px;
    color:rgba(255,255,255,.35);
    font-size:10px;
    letter-spacing:2px
}
.scroll-line{
    width:45px;
    height:1px;
    background:rgba(255,255,255,.25)
}

/* STATS */
.stats-section{
    padding:80px 30px 110px;
    border-top:1px solid rgba(255,255,255,.07);
    position:relative;
    z-index:1
}
.container{width:min(1200px,100%);margin:auto}
.section-kicker{
    color:var(--green);
    font-size:11px;
    font-weight:700;
    letter-spacing:2px;
    text-transform:uppercase;
    margin-bottom:15px
}
.section-title{
    font-family:"Manrope",sans-serif;
    font-size:clamp(38px,5vw,65px);
    line-height:1;
    letter-spacing:-3px;
    margin-bottom:45px
}
.stats-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:14px
}
.stat-card{
    min-height:190px;
    padding:28px;
    border:1px solid var(--border);
    background:linear-gradient(145deg,rgba(255,255,255,.08),rgba(255,255,255,.025));
    border-radius:20px;
    position:relative;
    overflow:hidden;
    transition:.35s ease
}
.stat-card:hover{
    transform:translateY(-7px);
    border-color:rgba(74,222,128,.35);
    background:linear-gradient(145deg,rgba(74,222,128,.11),rgba(255,255,255,.03))
}
.stat-icon{
    width:40px;
    height:40px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:rgba(74,222,128,.10);
    color:var(--green);
    margin-bottom:35px
}
.stat-number{
    font-family:"Manrope",sans-serif;
    font-size:48px;
    font-weight:800;
    letter-spacing:-3px;
    line-height:1;
    margin-bottom:8px
}
.stat-label{
    color:var(--muted);
    font-size:13px;
    text-transform:uppercase;
    letter-spacing:1px
}

/* ACTIVITIES */
.activities-section{
    padding:80px 30px 120px;
    background:rgba(255,255,255,.018);
    border-top:1px solid rgba(255,255,255,.05);
    position:relative;
    z-index:1
}
.activities-header{
    display:flex;
    justify-content:space-between;
    align-items:end;
    margin-bottom:45px
}
.activities-header .section-title{margin-bottom:0}
.activities-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:15px
}
.activity-card{
    min-height:300px;
    padding:28px;
    border:1px solid var(--border);
    border-radius:20px;
    background:rgba(255,255,255,.045);
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    transition:.35s ease;
    position:relative;
    overflow:hidden
}
.activity-card::after{
    content:"";
    position:absolute;
    width:150px;
    height:150px;
    right:-60px;
    bottom:-60px;
    border-radius:50%;
    background:rgba(74,222,128,.08);
    filter:blur(20px);
    pointer-events:none
}
.activity-card:hover{
    transform:translateY(-8px);
    border-color:rgba(74,222,128,.30)
}
.activity-type{
    color:var(--green);
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:2px;
    font-weight:700
}
.activity-title{
    font-family:"Manrope",sans-serif;
    font-size:25px;
    line-height:1.15;
    margin-top:20px;
    margin-bottom:12px
}
.activity-description{
    color:var(--muted);
    font-size:14px;
    line-height:1.7
}
.activity-date{
    color:rgba(255,255,255,.35);
    font-size:11px;
    margin-top:25px
}
.empty{
    color:rgba(255,255,255,.4);
    padding:50px;
    text-align:center;
    border:1px dashed rgba(255,255,255,.12);
    border-radius:15px
}

/* SCHOLARSHIP BANNER */
.scholarship-section{
    padding:80px 30px;
    position:relative;
    z-index:1
}
.scholarship-card{
    width:min(1200px,100%);
    margin:auto;
    padding:50px;
    border-radius:25px;
    background:linear-gradient(120deg,rgba(74,222,128,.13),rgba(255,255,255,.035));
    border:1px solid rgba(74,222,128,.20);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:30px
}
.scholarship-title{
    font-family:"Manrope",sans-serif;
    font-size:38px;
    letter-spacing:-2px;
    margin-bottom:10px
}
.scholarship-text{
    color:var(--muted);
    max-width:600px;
    line-height:1.6;
    margin-bottom:20px
}

/* FOOTER */
footer{
    padding:45px 30px;
    border-top:1px solid rgba(255,255,255,.07);
    color:rgba(255,255,255,.4);
    font-size:13px;
    position:relative;
    z-index:1
}
.footer-inner{
    width:min(1200px,100%);
    margin:auto;
    display:flex;
    justify-content:space-between;
    align-items:center
}
.footer-brand{color:#fff;font-weight:800}
.footer-brand span{color:var(--green)}

/* =========================================================
   ASTRONAUT DEFAULT
========================================================= */
.astronaut-buddy{
    position:fixed;
    right:20px;
    bottom:18px;
    z-index:1600;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:0;
    cursor:pointer;
    transform-origin:bottom right;
    animation:buddyFloat 4s ease-in-out infinite;
    transition:filter .25s ease;
    -webkit-tap-highlight-color:transparent;
    user-select:none;
    opacity:1!important;
    visibility:visible!important;
}
.astronaut-buddy:hover{
    filter:drop-shadow(0 0 16px rgba(74,222,128,.35))
}
.astronaut-buddy:focus-visible{
    outline:2px solid #4ade80;
    outline-offset:5px;
    border-radius:18px
}
.astronaut-figure{
    width:135px;
    flex-shrink:0;
    transform-origin:bottom center;
    transition:transform .3s ease;
    position:relative;
    z-index:2
}
.astronaut-figure svg{
    display:block;
    width:100%;
    height:auto;
    overflow:visible
}

/* SPEECH BUBBLE CONNECTED TO ASTRONAUT */
.astronaut-sign{
    width:190px;
    padding:15px;
    margin-bottom:-3px;
    position:relative;
    background:linear-gradient(145deg,rgba(12,39,24,.98),rgba(5,20,12,.98));
    border:1px solid rgba(74,222,128,.65);
    border-radius:17px;
    box-shadow:
        0 12px 35px rgba(0,0,0,.4),
        inset 0 1px 0 rgba(255,255,255,.08),
        0 0 18px rgba(74,222,128,.12);
    backdrop-filter:blur(12px);
    text-align:center;
    transition:border-color .25s ease;
}
.astronaut-sign::after{
    content:"";
    position:absolute;
    bottom:-10px;
    left:50%;
    width:18px;
    height:18px;
    background:#092016;
    border-right:1px solid rgba(74,222,128,.65);
    border-bottom:1px solid rgba(74,222,128,.65);
    transform:translateX(-50%) rotate(45deg);
    z-index:1;
}
.astronaut-buddy:hover .astronaut-sign{
    border-color:#4ade80
}
.sign-label{
    display:block;
    color:#86efac;
    font-size:9px;
    font-weight:800;
    letter-spacing:1.3px;
    margin-bottom:9px
}
#astronautQuote{
    color:white;
    font-family:"Manrope",sans-serif;
    font-weight:800;
    font-size:17px;
    line-height:1.35;
    overflow-wrap:anywhere;
    transition:opacity .18s ease
}
.sign-hint{
    display:block;
    margin-top:10px;
    color:rgba(255,255,255,.48);
    font-size:8px;
    letter-spacing:.8px
}
@keyframes buddyFloat{
    0%,100%{transform:translateY(0) rotate(0)}
    50%{transform:translateY(-9px) rotate(1deg)}
}
.astronaut-buddy.message-change .astronaut-figure{
    animation:astronautWave .5s ease
}
@keyframes astronautWave{
    0%,100%{transform:rotate(0)}
    30%{transform:rotate(-9deg) translateY(-5px)}
    65%{transform:rotate(6deg)}
}

/* =========================================================
   3D SPACE ASTRONAUT
========================================================= */

.astronaut-buddy.is-tour{
    position:fixed!important;
    left:20px;
    top:20px;
    right:auto!important;
    bottom:auto!important;
    z-index:2004!important;
    display:flex!important;
    align-items:center;
    justify-content:center;
    width:170px;
    height:210px;
    opacity:1!important;
    visibility:visible!important;
    animation:none!important;
    transform:none;
    transition:
        left 1.35s cubic-bezier(.22,.75,.25,1),
        top 1.35s cubic-bezier(.22,.75,.25,1),
        filter .4s ease;
    filter:drop-shadow(0 15px 25px rgba(0,0,0,.5))
           drop-shadow(0 0 18px rgba(74,222,128,.3));
}

.astronaut-buddy.is-tour .astronaut-sign{
    display:none;
}

.astronaut-buddy.is-tour .astronaut-figure{
    width:160px;
    height:200px;
    position:relative;
    flex-shrink:0;
    animation:spaceHover 3s ease-in-out infinite;
    transform-style:preserve-3d;
}

.astronaut-buddy.is-tour .astronaut-figure svg{
    display:none;
}

.space-suit{
    position:absolute;
    inset:0;
    transform-style:preserve-3d;
}

.space-backpack{
    position:absolute;
    width:42px;
    height:75px;
    left:21px;
    top:76px;
    border-radius:15px;
    background:linear-gradient(135deg,#dfffee,#6fbd91 45%,#174b32);
    border:3px solid #b8f5d0;
    box-shadow:inset -7px 0 10px rgba(0,0,0,.25),5px 8px 15px rgba(0,0,0,.35);
    transform:rotate(-8deg) translateZ(-10px);
}

.space-backpack::before,
.space-backpack::after{
    content:"";
    position:absolute;
    left:8px;
    width:20px;
    height:7px;
    border-radius:5px;
    background:#4ade80;
    box-shadow:0 0 9px rgba(74,222,128,.6);
}
.space-backpack::before{top:14px}
.space-backpack::after{top:30px}

.space-body{
    position:absolute;
    width:76px;
    height:91px;
    left:43px;
    top:82px;
    border-radius:29px 29px 24px 24px;
    background:linear-gradient(135deg,#ffffff 0%,#e5f8ed 35%,#8fb8a0 100%);
    border:3px solid #d7fbe4;
    box-shadow:
        inset -12px -8px 15px rgba(0,0,0,.18),
        inset 7px 5px 12px rgba(255,255,255,.9),
        0 12px 22px rgba(0,0,0,.3);
    transform:rotateX(-5deg);
}

.space-chest{
    position:absolute;
    left:19px;
    top:27px;
    width:35px;
    height:29px;
    border-radius:8px;
    background:linear-gradient(145deg,#164c31,#071c11);
    border:2px solid #4ade80;
    box-shadow:0 0 12px rgba(74,222,128,.35);
}
.space-chest::before{
    content:"SK";
    position:absolute;
    inset:0;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#86efac;
    font-family:Arial,sans-serif;
    font-size:11px;
    font-weight:900;
    letter-spacing:1px;
}

/* HELMET */
.space-helmet{
    position:absolute;
    width:100px;
    height:100px;
    left:31px;
    top:5px;
    border-radius:50%;
    background:linear-gradient(135deg,#ffffff 0%,#e9fff1 30%,#83ad95 75%,#d9f8e4 100%);
    border:4px solid #eafff0;
    box-shadow:
        inset -9px -8px 15px rgba(0,0,0,.22),
        inset 8px 5px 12px rgba(255,255,255,.95),
        0 10px 20px rgba(0,0,0,.4);
    z-index:5;
    transform:translateZ(15px);
}

.space-visor{
    position:absolute;
    inset:12px;
    border-radius:50%;
    overflow:hidden;
    background:
        radial-gradient(circle at 25% 20%,rgba(74,222,128,.5),transparent 25%),
        linear-gradient(145deg,#193d32,#06140f 65%,#020907);
    border:3px solid #83e9ad;
    box-shadow:inset 0 0 18px rgba(0,0,0,.9),0 0 14px rgba(74,222,128,.4);
}
.space-visor::before{
    content:"";
    position:absolute;
    width:35px;
    height:12px;
    top:9px;
    left:9px;
    border-radius:50%;
    background:rgba(255,255,255,.45);
    filter:blur(5px);
    transform:rotate(-35deg);
}
.space-eye{
    position:absolute;
    top:40px;
    width:8px;
    height:12px;
    border-radius:50%;
    background:#86efac;
    box-shadow:0 0 10px #4ade80;
    animation:spaceBlink 4s infinite;
}
.space-eye.left{left:20px}
.space-eye.right{right:20px}
.space-smile{
    position:absolute;
    width:17px;
    height:8px;
    bottom:15px;
    left:50%;
    transform:translateX(-50%);
    border-bottom:2px solid #86efac;
    border-radius:50%;
}

.space-ear{
    position:absolute;
    width:15px;
    height:27px;
    top:37px;
    border-radius:8px;
    background:linear-gradient(135deg,#86efac,#16834a);
    border:2px solid #eafff0;
    box-shadow:0 0 12px rgba(74,222,128,.6);
    z-index:6;
}
.space-ear.left{left:25px}
.space-ear.right{right:25px}

/* ARMS */
.space-arm{
    position:absolute;
    width:24px;
    height:58px;
    top:93px;
    border-radius:15px;
    background:linear-gradient(90deg,#789e87,#ffffff 45%,#c4ebd1);
    border:2px solid #eafff0;
    transform-origin:top center;
    z-index:3;
    box-shadow:inset -5px 0 7px rgba(0,0,0,.15);
}
.space-arm.left{
    left:27px;
    transform:rotate(35deg);
}
.space-arm.right{
    right:20px;
    transform:rotate(-35deg);
}
.space-glove{
    position:absolute;
    width:26px;
    height:24px;
    bottom:-9px;
    left:-3px;
    border-radius:10px;
    background:linear-gradient(135deg,#ffffff,#91c9a5);
    border:2px solid #eafff0;
}

/* LEGS */
.space-leg{
    position:absolute;
    width:28px;
    height:49px;
    top:153px;
    border-radius:12px 12px 15px 15px;
    background:linear-gradient(90deg,#789e87,#ffffff 45%,#c4ebd1);
    border:2px solid #eafff0;
    z-index:2;
}
.space-leg.left{
    left:48px;
    transform:rotate(8deg);
}
.space-leg.right{
    left:86px;
    transform:rotate(-8deg);
}
.space-boot{
    position:absolute;
    width:34px;
    height:19px;
    bottom:-5px;
    left:-4px;
    border-radius:8px 8px 12px 12px;
    background:linear-gradient(135deg,#dffff0,#548f6b);
    border:2px solid #eafff0;
}

/* PARTICLES */
.space-particle{
    position:absolute;
    width:6px;
    height:6px;
    border-radius:50%;
    background:#86efac;
    box-shadow:0 0 12px #4ade80;
    pointer-events:none;
    animation:particleFloat 2.5s ease-in-out infinite;
}
.space-particle.one{top:35px;left:10px}
.space-particle.two{top:105px;right:3px;animation-delay:.8s}
.space-particle.three{bottom:18px;left:20px;animation-delay:1.3s}

@keyframes spaceHover{
    0%,100%{transform:translateY(0) rotate(-2deg)}
    50%{transform:translateY(-12px) rotate(2deg)}
}
@keyframes spaceBlink{
    0%,46%,50%,100%{transform:scaleY(1)}
    48%{transform:scaleY(.1)}
}
@keyframes particleFloat{
    0%,100%{transform:translate(0,0);opacity:.35}
    50%{transform:translate(8px,-12px);opacity:1}
}

/* ASTRONAUT ACTIONS */
.astronaut-buddy.is-pointing .space-arm.right{
    animation:spacePoint 1s ease-in-out forwards;
}
@keyframes spacePoint{
    0%{transform:rotate(-35deg)}
    45%{transform:rotate(-105deg)}
    100%{transform:rotate(-95deg)}
}
.astronaut-buddy.is-waving .space-arm.right{
    animation:spaceWave .45s ease-in-out 3;
}
@keyframes spaceWave{
    0%,100%{transform:rotate(-35deg)}
    50%{transform:rotate(-115deg)}
}
.astronaut-buddy.is-spinning .astronaut-figure{
    animation:spaceSpin 1.2s ease-in-out;
}
@keyframes spaceSpin{
    0%{transform:rotate(0) scale(1)}
    50%{transform:rotate(190deg) scale(1.08)}
    100%{transform:rotate(360deg) scale(1)}
}
.astronaut-buddy.is-happy .astronaut-figure{
    animation:spaceHappy .55s ease-in-out 3;
}
@keyframes spaceHappy{
    0%,100%{transform:translateY(0) rotate(0)}
    50%{transform:translateY(-18px) rotate(12deg)}
}

/* =========================================================
   FLIGHT SPARK / FIRE TRAIL
========================================================= */

#flightEffects{
    position:fixed;
    inset:0;
    z-index:2003;
    pointer-events:none;
    overflow:hidden;
}

.flight-spark{
    position:absolute;
    width:var(--spark-size,7px);
    height:var(--spark-size,7px);
    border-radius:50%;
    pointer-events:none;
    background:var(--spark-color,#ffb02e);
    box-shadow:
        0 0 5px var(--spark-color,#ffb02e),
        0 0 12px var(--spark-color,#ffb02e),
        0 0 20px rgba(255,120,20,.6);
    will-change:transform,opacity;
}

.flight-spark.fire{
    width:var(--spark-size,9px);
    height:var(--spark-size,9px);
    border-radius:50% 50% 50% 0;
    transform:rotate(-45deg);
    filter:blur(.2px);
}

.flight-spark.landing{
    width:6px;
    height:6px;
    box-shadow:
        0 0 7px #ffcf55,
        0 0 15px #ff7a18,
        0 0 25px rgba(255,90,0,.65);
}

/* =========================================================
   WEBSITE GUIDE
========================================================= */
.guide-overlay{
    position:fixed;
    inset:0;
    z-index:2000;
    background:rgba(0,0,0,.76);
    opacity:0;
    visibility:hidden;
    transition:.3s ease;
    pointer-events:none;
}
.guide-overlay.active{
    opacity:1;
    visibility:visible;
}

.guide-highlight{
    position:relative!important;
    z-index:2002!important;
    outline:3px solid #4ade80!important;
    outline-offset:7px!important;
    border-radius:14px;
    box-shadow:0 0 0 9999px rgba(0,0,0,.68),
               0 0 30px rgba(74,222,128,.5)!important;
}

.guide-card{
    position:fixed;
    z-index:2005;
    width:min(390px,calc(100% - 30px));
    background:#0b1b12;
    border:1px solid rgba(74,222,128,.4);
    border-radius:20px;
    padding:22px;
    box-shadow:0 20px 70px rgba(0,0,0,.6);
    left:50%;
    bottom:35px;
    transform:translateX(-50%) translateY(25px);
    opacity:0;
    visibility:hidden;
    transition:.3s ease;
}
.guide-card.active{
    opacity:1;
    visibility:visible;
    transform:translateX(-50%) translateY(0);
}

/* FINAL STEP: KEEP GUIDE CARD AT TOP */
.guide-card.final-step{
    top:25px;
    bottom:auto;
}
.guide-card.final-step.active{
    transform:translateX(-50%) translateY(0);
}

.guide-card-top{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:15px;
}
.guide-mini-astronaut{
    width:48px;
    height:48px;
    border-radius:14px;
    background:rgba(74,222,128,.12);
    display:flex;
    align-items:center;
    justify-content:center;
    color:#4ade80;
    font-size:25px;
}
.guide-label{
    color:#4ade80;
    font-size:10px;
    font-weight:800;
    letter-spacing:1.5px;
}
.guide-counter{
    color:rgba(255,255,255,.45);
    font-size:11px;
    margin-top:4px;
}
.guide-card h3{
    font-family:"Manrope",sans-serif;
    font-size:21px;
    margin-bottom:9px;
}
.guide-card p{
    color:rgba(255,255,255,.68);
    font-size:13px;
    line-height:1.7;
    margin-bottom:20px;
}
.guide-progress{
    width:100%;
    height:5px;
    border-radius:10px;
    background:rgba(255,255,255,.1);
    overflow:hidden;
    margin-bottom:18px;
}
.guide-progress-bar{
    height:100%;
    width:0;
    background:#4ade80;
    border-radius:10px;
    transition:width .3s ease;
}
.guide-controls{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
}
.guide-controls button,
.assistant-action{
    border:0;
    cursor:pointer;
    font-family:inherit;
    font-weight:700;
    border-radius:10px;
    padding:10px 14px;
    font-size:12px;
    transition:.2s ease;
}
.guide-skip{
    background:transparent;
    color:rgba(255,255,255,.55);
}
.guide-back{
    background:rgba(255,255,255,.08);
    color:white;
}
.guide-next{
    background:#4ade80;
    color:#06100b;
}
.guide-controls button:hover,
.assistant-action:hover{
    transform:translateY(-2px);
    filter:brightness(1.1);
}

/* WELCOME */
.guide-welcome{
    position:fixed;
    inset:0;

    /* FIX: Keep welcome overlay below the astronaut */
    z-index:1500;

    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
    background:rgba(0,0,0,.78);
    opacity:0;
    visibility:hidden;
    transition:.3s ease;
}
.guide-welcome.active{
    opacity:1;
    visibility:visible;
}
.guide-welcome-card{
    width:min(450px,100%);
    padding:30px;
    text-align:center;
    border-radius:24px;
    background:#0b1b12;
    border:1px solid rgba(74,222,128,.4);
    box-shadow:0 20px 70px rgba(0,0,0,.6);
    transform:translateY(20px) scale(.97);
    transition:.3s ease;
}
.guide-welcome.active .guide-welcome-card{
    transform:translateY(0) scale(1);
}
.guide-welcome-icon{
    width:75px;
    height:75px;
    margin:0 auto 18px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:22px;
    background:rgba(74,222,128,.12);
    color:#4ade80;
    font-size:36px;
}
.guide-welcome-card h2{
    font-family:"Manrope",sans-serif;
    font-size:25px;
    margin-bottom:10px;
}
.guide-welcome-card p{
    color:rgba(255,255,255,.65);
    font-size:13px;
    line-height:1.7;
    margin-bottom:22px;
}
.guide-welcome-buttons{
    display:flex;
    gap:9px;
}
.guide-welcome-buttons button{
    flex:1;
    border:0;
    border-radius:11px;
    padding:13px;
    cursor:pointer;
    font-weight:700;
    font-size:12px;
}
.guide-start{
    background:#4ade80;
    color:#06100b;
}
.guide-later{
    background:rgba(255,255,255,.08);
    color:white;
}

/* ASSISTANT */
.assistant-panel{
    position:fixed;
    right:25px;
    bottom:175px;
    z-index:1700;
    width:min(350px,calc(100% - 30px));
    background:#0b1b12;
    border:1px solid rgba(74,222,128,.35);
    border-radius:20px;
    padding:20px;
    box-shadow:0 20px 60px rgba(0,0,0,.5);
    opacity:0;
    visibility:hidden;
    transform:translateY(15px) scale(.97);
    transition:.25s ease;
}
.assistant-panel.active{
    opacity:1;
    visibility:visible;
    transform:translateY(0) scale(1);
}
.assistant-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:15px;
}
.assistant-heading{
    display:flex;
    align-items:center;
    gap:10px;
}
.assistant-avatar{
    width:40px;
    height:40px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:12px;
    background:rgba(74,222,128,.12);
    color:#4ade80;
    font-size:20px;
}
.assistant-heading strong{
    font-family:"Manrope",sans-serif;
    font-size:14px;
}
.assistant-heading small{
    display:block;
    color:#4ade80;
    font-size:10px;
    margin-top:3px;
}
.assistant-close{
    background:transparent;
    border:0;
    color:rgba(255,255,255,.6);
    font-size:20px;
    cursor:pointer;
}
.assistant-welcome{
    color:rgba(255,255,255,.7);
    font-size:13px;
    line-height:1.6;
    margin-bottom:15px;
}
.assistant-questions{
    display:grid;
    gap:8px;
}
.assistant-question{
    width:100%;
    text-align:left;
    padding:12px;
    border:1px solid rgba(255,255,255,.1);
    border-radius:11px;
    color:white;
    background:rgba(255,255,255,.045);
    font-size:12px;
    cursor:pointer;
    transition:.2s ease;
}
.assistant-question:hover{
    border-color:rgba(74,222,128,.5);
    background:rgba(74,222,128,.08);
}
.assistant-answer{
    display:none;
    margin-top:13px;
    padding:13px;
    border-radius:12px;
    background:rgba(74,222,128,.08);
    border:1px solid rgba(74,222,128,.2);
    color:rgba(255,255,255,.8);
    font-size:12px;
    line-height:1.7;
}
.assistant-answer.active{
    display:block;
}
.assistant-footer{
    margin-top:15px;
    padding-top:13px;
    border-top:1px solid rgba(255,255,255,.08);
}
.assistant-action{
    width:100%;
    background:#4ade80;
    color:#06100b;
}

/* RESPONSIVE */
@media(max-width:1000px){
    .hero-content{
        grid-template-columns:1fr;
        text-align:center;
        gap:30px
    }
    .hero-left{margin:auto}
    .hero-buttons{justify-content:center}
    .logo-stage{min-height:390px}
    .stats-grid{grid-template-columns:repeat(2,1fr)}
    .activities-grid{grid-template-columns:repeat(2,1fr)}
    .scholarship-card{
        flex-direction:column;
        align-items:flex-start
    }
}
@media(max-width:700px){
    .navbar{
        width:calc(100% - 20px);
        top:10px;
        padding:0 15px
    }
    .nav-links a:not(.nav-login){display:none}
    .hero{padding:130px 20px 70px}
    .hero-title{font-size:65px;letter-spacing:-4px}
    .hero-description{font-size:15px}
    .sk-logo-3d{width:240px;height:240px}
    .logo-shadow{width:200px}
    .vertical-label{display:none}
    .stats-section,.activities-section,.scholarship-section{
        padding-left:20px;
        padding-right:20px
    }
    .stats-grid,.activities-grid{grid-template-columns:1fr}
    .activities-header{display:block}
    .section-title{font-size:45px}
    .scholarship-card{padding:30px}
    .scholarship-title{font-size:30px}
    .footer-inner{
        flex-direction:column;
        gap:10px;
        text-align:center
    }
    .astronaut-buddy{
        right:8px;
        bottom:8px;
        gap:0
    }
    .astronaut-figure{width:95px}
    .astronaut-sign{
        width:145px;
        padding:10px
    }
    #astronautQuote{font-size:13px}
    .sign-label{font-size:7px}
    .sign-hint{font-size:7px}
    .assistant-panel{
        right:12px;
        bottom:125px;
    }
    .guide-card{bottom:15px}
    .guide-welcome-card{padding:23px}
    .astronaut-buddy.is-tour{
        width:125px;
        height:165px;
    }
    .astronaut-buddy.is-tour .astronaut-figure{
        transform:scale(.78);
    }
    .guide-card.final-step{
        top:12px;
        bottom:auto;
    }
}
@media(max-width:380px){
    .astronaut-figure{width:78px}
    .astronaut-sign{width:125px;padding:8px}
    #astronautQuote{font-size:12px}
}
@media(prefers-reduced-motion:reduce){
    *,*::before,*::after{
        animation-duration:.01ms!important;
        animation-iteration-count:1!important;
        scroll-behavior:auto!important;
        transition-duration:.01ms!important
    }
}
</style>
</head>

<body>

<!-- NAVIGATION -->
<nav class="navbar" data-guide="navbar">
    <a href="index.php" class="nav-logo">
        <div class="nav-logo-mark">
            <img src="assets/sk-logo.png.webp" alt="SK Logo">
        </div>
        <div>SK <span>KONEXT</span></div>
    </a>

    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="#activities">Activities</a>
        <a href="map.php">Community Map</a>
        <a href="events.php">Projects Map</a>
        <a href="about.php">About</a>
        <a href="login.php" class="nav-login">Login</a>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-glow"></div>

    <div class="hero-content">

        <div class="hero-left">
            <div class="live-badge">
                <span class="live-dot"></span>
                SK KONEXT
            </div>

            <h1 class="hero-title">
                <span class="green">CONNECT.</span><br>
                <span>ENGAGE.</span><br>
                <span class="outline">EMPOWER.</span>
            </h1>

            <p class="hero-description" data-guide="intro">
                SK KONEXT is a digital platform designed
                to connect the Sangguniang Kabataan with
                the community through transparency,
                youth engagement, scholarships,
                projects, and activities.
            </p>

            <div class="hero-buttons">
                <?php if($scholarship_open): ?>
                    <a href="<?= $is_logged_in ? 'scholarship.php' : 'login.php' ?>"
                       class="btn btn-green"
                       data-guide="scholarship-button">
                        <i class="fa-solid fa-graduation-cap"></i>
                        Apply for Scholarship
                    </a>
                <?php endif; ?>

                <a href="map.php" class="btn btn-dark" data-guide="map-button">
                    <i class="fa-solid fa-map-location-dot"></i>
                    Community Map
                </a>

                <a href="events.php" class="btn btn-dark" data-guide="events-button">
                    <i class="fa-solid fa-calendar-days"></i>
                    Events
                </a>
            </div>
        </div>

        <!-- EXISTING 3D LOGO -->
        <div class="logo-stage">
            <div class="logo-shadow"></div>

            <div class="sk-logo-3d" id="skLogo">
                <div class="logo-depth depth-3">
                    <img src="assets/sk-logo.png.webp" alt="SK Logo">
                </div>

                <div class="logo-depth depth-2">
                    <img src="assets/sk-logo.png.webp" alt="SK Logo">
                </div>

                <div class="logo-depth depth-1">
                    <img src="assets/sk-logo.png.webp" alt="SK Logo">
                </div>

                <div class="logo-main">
                    <img src="assets/sk-logo.png.webp" alt="Sangguniang Kabataan Logo">
                </div>
            </div>
        </div>
    </div>

    <div class="vertical-label">
        SK KONEXT • COMMUNITY • TRANSPARENCY
    </div>

    <div class="scroll-indicator">
        <span class="scroll-line"></span>
        SCROLL TO EXPLORE
        <span class="scroll-line"></span>
    </div>
</section>

<!-- STATISTICS -->
<section class="stats-section" data-guide="statistics">
    <div class="container">
        <div class="section-kicker">OVERVIEW</div>
        <h2 class="section-title">Community<br>at a glance.</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <div class="stat-number">
                    <?= number_format($barangay_count) ?>
                </div>
                <div class="stat-label">Barangays</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div class="stat-number">
                    <?= number_format($scholar_count) ?>
                </div>
                <div class="stat-label">Scholars</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-number">
                    <?= number_format($sk_count) ?>
                </div>
                <div class="stat-label">SK Chairman</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div class="stat-number">
                    <?= number_format($project_count) ?>
                </div>
                <div class="stat-label">Community Projects</div>
            </div>
        </div>
    </div>
</section>

<!-- SCHOLARSHIP -->
<?php if($scholarship_open): ?>
<section class="scholarship-section" data-guide="scholarship-section">
    <div class="scholarship-card">
        <div>
            <div class="section-kicker">
                NOW ACCEPTING APPLICATIONS
            </div>

            <div class="scholarship-title">
                <?= htmlspecialchars($active_scholarship['title']) ?>
            </div>

            <p class="scholarship-text">
                Scholarship applications are currently
                open. Submit your application through
                the SK KONEXT platform.
            </p>

            <a href="<?= $is_logged_in ? 'scholarship.php' : 'login.php' ?>" class="btn btn-green">
                <i class="fa-solid fa-arrow-right"></i>
                Apply Now
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ACTIVITIES -->
<section class="activities-section" id="activities" data-guide="activities">
    <div class="container">
        <div class="activities-header">
            <div>
                <div class="section-kicker">COMMUNITY FEED</div>
                <h2 class="section-title">What's<br>happening.</h2>
            </div>

            <a href="events.php" class="btn btn-dark">
                View All Events
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="activities-grid">
            <?php if($activities && mysqli_num_rows($activities) > 0): ?>
                <?php while($activity = mysqli_fetch_assoc($activities)): ?>
                    <div class="activity-card">
                        <div>
                            <div class="activity-type">
                                <?= htmlspecialchars($activity['type'] ?? 'COMMUNITY ACTIVITY') ?>
                            </div>

                            <div class="activity-title">
                                <?= htmlspecialchars($activity['title']) ?>
                            </div>

                            <div class="activity-description">
                                <?= htmlspecialchars($activity['description'] ?? 'No description available.') ?>
                            </div>
                        </div>

                        <div class="activity-date">
                            <i class="fa-regular fa-clock"></i>
                            Community Activity
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty" style="grid-column:1/-1;">
                    No recent activities available.
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer data-guide="footer">
    <div class="footer-inner">
        <div>
            <span class="footer-brand">SK <span>KONEXT</span></span>
            &nbsp; — &nbsp;
            Digital Platform for Youth and Community
        </div>

        <div>Sangguniang Kabataan</div>
    </div>
</footer>

<!-- ASTRONAUT -->
<div class="astronaut-buddy"
     id="astronautBuddy"
     data-guide="astronaut"
     role="button"
     tabindex="0"
     aria-label="Open SK KONEXT Assistant"
     title="Click to open SK KONEXT Assistant">

    <div class="astronaut-sign">
        <span class="sign-label">YOUTH SPACE MESSAGE</span>
        <p id="astronautQuote">SK KONEXT</p>
        <span class="sign-hint">CLICK TO ASK ME ↗</span>
    </div>

    <div class="astronaut-figure">

        <div class="space-suit">

            <div class="space-particle one"></div>
            <div class="space-particle two"></div>
            <div class="space-particle three"></div>

            <div class="space-backpack"></div>

            <div class="space-arm left">
                <div class="space-glove"></div>
            </div>

            <div class="space-arm right">
                <div class="space-glove"></div>
            </div>

            <div class="space-body">
                <div class="space-chest"></div>
            </div>

            <div class="space-leg left">
                <div class="space-boot"></div>
            </div>

            <div class="space-leg right">
                <div class="space-boot"></div>
            </div>

            <div class="space-ear left"></div>
            <div class="space-ear right"></div>

            <div class="space-helmet">
                <div class="space-visor">
                    <span class="space-eye left"></span>
                    <span class="space-eye right"></span>
                    <span class="space-smile"></span>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- FLIGHT SPARK EFFECTS -->
<div id="flightEffects" aria-hidden="true"></div>

<!-- FIRST VISIT WELCOME -->
<div class="guide-welcome" id="guideWelcome">
    <div class="guide-welcome-card">
        <div class="guide-welcome-icon">
            <i class="fa-solid fa-user-astronaut"></i>
        </div>

        <h2>Welcome to SK KONEXT!</h2>

        <p>
            Hi! I'm your SK KONEXT guide.
            I'll help you explore the website and
            show you what each important section does.
        </p>

        <div class="guide-welcome-buttons">
            <button class="guide-later" id="guideLater">
                Maybe Later
            </button>

            <button class="guide-start" id="guideStart">
                Start Tour <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

<!-- TOUR BACKGROUND -->
<div class="guide-overlay" id="guideOverlay"></div>

<!-- TOUR CARD -->
<div class="guide-card" id="guideCard">
    <div class="guide-card-top">
        <div class="guide-mini-astronaut">
            <i class="fa-solid fa-user-astronaut"></i>
        </div>

        <div>
            <div class="guide-label">SK KONEXT GUIDE</div>
            <div class="guide-counter" id="guideCounter">
                Step 1
            </div>
        </div>
    </div>

    <h3 id="guideTitle">Welcome!</h3>

    <p id="guideDescription">
        Let's explore the website.
    </p>

    <div class="guide-progress">
        <div class="guide-progress-bar" id="guideProgress"></div>
    </div>

    <div class="guide-controls">
        <button class="guide-skip" id="guideSkip">Skip Tour</button>

        <div style="display:flex;gap:7px;">
            <button class="guide-back" id="guideBack">Back</button>
            <button class="guide-next" id="guideNext">Next</button>
        </div>
    </div>
</div>

<!-- INTERACTIVE ASSISTANT -->
<div class="assistant-panel" id="assistantPanel">
    <div class="assistant-header">
        <div class="assistant-heading">
            <div class="assistant-avatar">
                <i class="fa-solid fa-user-astronaut"></i>
            </div>

            <div>
                <strong>SK KONEXT Assistant</strong>
                <small><i class="fa-solid fa-circle"></i> Online Guide</small>
            </div>
        </div>

        <button class="assistant-close" id="assistantClose"
                aria-label="Close assistant">
            &times;
        </button>
    </div>

    <p class="assistant-welcome">
        Hello! What would you like to know about SK KONEXT?
    </p>

    <div class="assistant-questions">
        <button class="assistant-question" data-answer="scholarship">
            <i class="fa-solid fa-graduation-cap"></i>
            How can I apply for a scholarship?
        </button>

        <button class="assistant-question" data-answer="account">
            <i class="fa-solid fa-user-plus"></i>
            How do I create an account?
        </button>

        <button class="assistant-question" data-answer="map">
            <i class="fa-solid fa-map-location-dot"></i>
            What is the Community Map?
        </button>

        <button class="assistant-question" data-answer="events">
            <i class="fa-solid fa-calendar-days"></i>
            Where can I see events?
        </button>

        <button class="assistant-question" data-answer="about">
            <i class="fa-solid fa-circle-info"></i>
            What is SK KONEXT?
        </button>
    </div>

    <div class="assistant-answer" id="assistantAnswer"></div>

    <div class="assistant-footer">
        <button class="assistant-action" id="replayGuide">
            <i class="fa-solid fa-rotate-right"></i>
            Replay Website Tour
        </button>
    </div>
</div>

<script>


/* =====================================================
   3D LOGO ANIMATION + RANDOM MOVEMENT + SUNLIGHT
===================================================== */

const skLogo = document.getElementById("skLogo");

let logoShineTimeout = null;
let randomLogoTimer = null;
let randomLogoResetTimer = null;

function triggerLogoShine(){

    if(!skLogo) return;

    skLogo.classList.remove("is-shining");

    void skLogo.offsetWidth;

    skLogo.classList.add("is-shining");

    if(logoShineTimeout){
        clearTimeout(logoShineTimeout);
    }

    logoShineTimeout = setTimeout(function(){
        skLogo.classList.remove("is-shining");
    },1100);
}

/* PLAY LOGO ANIMATION */
function playLogoAnimation(){

    if(!skLogo) return;

    if(randomLogoResetTimer){
        clearTimeout(randomLogoResetTimer);
        randomLogoResetTimer = null;
    }

    skLogo.style.animation = "";
    skLogo.style.transform = "";
    skLogo.style.transition = "";

    skLogo.classList.remove("animate");

    void skLogo.offsetWidth;

    skLogo.classList.add("animate");

    triggerLogoShine();
}

/* RANDOM LOGO MOVEMENT */
function randomLogoMovement(){

    if(!skLogo) return;

    if(skLogo.matches(":hover")){
        randomLogoTimer = setTimeout(
            randomLogoMovement,
            1000
        );
        return;
    }

    if(randomLogoResetTimer){
        clearTimeout(randomLogoResetTimer);
    }

    skLogo.classList.remove("animate");

    const rotateX = Math.random() * 24 - 12;
    const rotateY = Math.random() * 30 - 15;
    const rotateZ = Math.random() * 10 - 5;

    const moveX = Math.random() * 24 - 12;
    const moveY = Math.random() * 30 - 15;

    const scale = 1 + Math.random() * 0.06;

    skLogo.style.animation = "none";
    skLogo.style.transition =
        "transform 1.5s cubic-bezier(.22,.61,.36,1)";

    void skLogo.offsetWidth;

    skLogo.style.transform =
        `translate(${moveX}px, ${moveY}px)
         rotateX(${rotateX}deg)
         rotateY(${rotateY}deg)
         rotateZ(${rotateZ}deg)
         scale(${scale})`;

    /* RANDOM SUNLIGHT SHINE */
    if(Math.random() > 0.45){
        triggerLogoShine();
    }

    /* RETURN TO ORIGINAL POSITION */
    randomLogoResetTimer = setTimeout(function(){

        if(!skLogo.matches(":hover")){

            skLogo.style.transition =
                "transform 1.5s cubic-bezier(.22,.61,.36,1)";

            skLogo.style.transform = "";

            setTimeout(function(){

                if(!skLogo.matches(":hover")){
                    skLogo.style.transition = "";
                    skLogo.style.animation =
                        "logoFloat 4s ease-in-out infinite";
                }

            },1500);
        }

    },1800);

    /* REPEAT RANDOM MOVEMENT */
    randomLogoTimer = setTimeout(
        randomLogoMovement,
        Math.random() * 2000 + 2500
    );
}

/* INITIAL ANIMATION */
setTimeout(playLogoAnimation,3000);

/* START RANDOM MOVEMENTS */
setTimeout(randomLogoMovement,6000);

/* MOUSE INTERACTION */
if(skLogo){

    skLogo.addEventListener("mousemove",function(e){

        if(window.innerWidth < 900) return;

        const rect = skLogo.getBoundingClientRect();

        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        const rotateY =
            ((x - centerX) / centerX) * 12;

        const rotateX =
            ((centerY - y) / centerY) * 12;

        skLogo.style.animation = "none";

        skLogo.style.transition =
            "transform .15s ease-out";

        skLogo.style.transform =
            `rotateX(${rotateX}deg)
             rotateY(${rotateY}deg)
             scale(1.04)`;

        if(!skLogo.classList.contains("is-shining")){
            triggerLogoShine();
        }

    });

    /* MOUSE LEAVE */
    skLogo.addEventListener("mouseleave",function(){

        skLogo.style.transform = "";
        skLogo.style.transition = "";

        skLogo.style.animation =
            "logoFloat 4s ease-in-out infinite";

        skLogo.classList.remove("is-shining");

        if(logoShineTimeout){
            clearTimeout(logoShineTimeout);
            logoShineTimeout = null;
        }

    });

    /* CLICK ANIMATION */
    skLogo.addEventListener("click",function(){
        playLogoAnimation();
    });

}


/* =====================================================
   ASTRONAUT MESSAGES
===================================================== */
const astronautBuddy = document.getElementById("astronautBuddy");
const astronautQuote = document.getElementById("astronautQuote");
const flightEffects = document.getElementById("flightEffects");

const youthMessages = [
    "SK KONEXT",
    "Empowering the Youth",
    "Your Voice Matters!",
    "Dream Big, Young Leader.",
    "Small Actions. Big Changes.",
    "The Future Starts With You.",
    "Kabataan, Kilos Na!",
    "Be the Change.",
    "Lead With Purpose.",
    "Your Community Needs You!",
    "Kabataan ang Pag-asa.",
    "Your Ideas Can Make a Difference.",
    "Start Small. Inspire Big.",
    "Together, We Build Our Future.",
    "Youth Today, Leaders Tomorrow."
];

let messageIndex = 0;

function changeAstronautMessage() {
    if (!astronautQuote || !astronautBuddy) return;

    if (astronautBuddy.classList.contains("is-tour")) return;

    messageIndex = (messageIndex + 1) % youthMessages.length;

    astronautQuote.style.opacity = "0";

    astronautBuddy.classList.remove("message-change");
    void astronautBuddy.offsetWidth;
    astronautBuddy.classList.add("message-change");

    setTimeout(function () {
        astronautQuote.textContent = youthMessages[messageIndex];
        astronautQuote.style.opacity = "1";
    }, 180);
}

setInterval(changeAstronautMessage, 6000);

/* =====================================================
   GUIDE ELEMENTS
===================================================== */
const guideWelcome = document.getElementById("guideWelcome");
const guideOverlay = document.getElementById("guideOverlay");
const guideCard = document.getElementById("guideCard");

const guideTitle = document.getElementById("guideTitle");
const guideDescription = document.getElementById("guideDescription");
const guideCounter = document.getElementById("guideCounter");
const guideProgress = document.getElementById("guideProgress");

const guideStart = document.getElementById("guideStart");
const guideLater = document.getElementById("guideLater");
const guideNext = document.getElementById("guideNext");
const guideBack = document.getElementById("guideBack");
const guideSkip = document.getElementById("guideSkip");

const assistantPanel = document.getElementById("assistantPanel");
const assistantClose = document.getElementById("assistantClose");
const assistantAnswer = document.getElementById("assistantAnswer");
const replayGuide = document.getElementById("replayGuide");

let currentGuideStep = 0;
let activeGuideElement = null;
let guideMoving = false;
let randomActionTimer = null;
let flightSparkTimer = null;
let flightEndTimer = null;

/* =====================================================
   TOUR STEPS
===================================================== */
const guideSteps = [
    {
        selector: '[data-guide="navbar"]',
        title: "Navigation Menu",
        description: "This is the main navigation menu. Use it to return to Home, view Activities, open the Community Map, check Events, or access your account."
    },
    {
        selector: '[data-guide="intro"]',
        title: "Welcome to SK KONEXT",
        description: "This section introduces SK KONEXT. It is a digital platform that connects the Sangguniang Kabataan and the community."
    },
    {
        selector: '[data-guide="scholarship-button"]',
        title: "Scholarship Application",
        description: "When scholarship applications are open, click here to access the scholarship application. You may need to log in first."
    },
    {
        selector: '[data-guide="map-button"]',
        title: "Community Map",
        description: "Open the Community Map to explore the community projects and their locations."
    },
    {
        selector: '[data-guide="events-button"]',
        title: "Events",
        description: "Visit the Events page to see community events and activities."
    },
    {
        selector: '[data-guide="statistics"]',
        title: "Community Statistics",
        description: "This section displays the current statistics, including barangays, scholars, SK members, and community projects."
    },
    {
        selector: '[data-guide="scholarship-section"]',
        title: "Scholarship Updates",
        description: "When a scholarship batch is active, its details and application link will appear here."
    },
    {
        selector: '[data-guide="activities"]',
        title: "Recent Activities",
        description: "See the latest community activities posted on SK KONEXT. You can also use View All Events to explore more."
    },
    {
        selector: '[data-guide="footer"]',
        title: "Website Footer",
        description: "This is the bottom section of the homepage, showing the SK KONEXT platform identity."
    },
    {
        selector: '[data-guide="astronaut"]',
        title: "Your Personal Guide",
        description: "You can click this astronaut anytime to open the SK KONEXT Assistant. Ask questions or replay this website tour whenever you need help."
    }
];

/* REMOVE STEPS THAT ARE NOT AVAILABLE */
const availableGuideSteps = guideSteps.filter(function(step){
    return document.querySelector(step.selector) !== null;
});

/* =====================================================
   FLIGHT SPARK EFFECTS
===================================================== */

const reducedMotion = window.matchMedia &&
    window.matchMedia("(prefers-reduced-motion: reduce)").matches;

function clearFlightEffects(){
    if(flightSparkTimer){
        clearInterval(flightSparkTimer);
        flightSparkTimer = null;
    }

    if(flightEndTimer){
        clearTimeout(flightEndTimer);
        flightEndTimer = null;
    }
}

function createSpark(x, y, options = {}){

    if(!flightEffects || reducedMotion) return;

    const spark = document.createElement("span");

    const colors = options.landing
        ? ["#fff4a3","#ffd166","#ff9f1c","#ff6b1a","#4ade80"]
        : ["#fff4a3","#ffd166","#ff9f1c","#ff6b1a","#ff4500","#4ade80"];

    const color = colors[Math.floor(Math.random() * colors.length)];

    const size = options.landing
        ? (Math.random() * 5 + 3)
        : (Math.random() * 6 + 3);

    spark.className = "flight-spark" +
        (options.landing ? " landing" : "") +
        (Math.random() > .45 ? " fire" : "");

    spark.style.left = x + "px";
    spark.style.top = y + "px";
    spark.style.setProperty("--spark-color", color);
    spark.style.setProperty("--spark-size", size + "px");

    flightEffects.appendChild(spark);

    const angle = options.angle !== undefined
        ? options.angle
        : Math.random() * Math.PI * 2;

    const distance = options.distance !== undefined
        ? options.distance
        : Math.random() * 38 + 18;

    const dx = Math.cos(angle) * distance;
    const dy = Math.sin(angle) * distance;

    const duration = options.landing
        ? Math.random() * 450 + 450
        : Math.random() * 450 + 350;

    const animation = spark.animate([
        {
            transform:"translate(0,0) scale(1) rotate(0deg)",
            opacity:1
        },
        {
            transform:`translate(${dx}px,${dy}px) scale(.15) rotate(${Math.random()*180}deg)`,
            opacity:0
        }
    ],{
        duration:duration,
        easing:"cubic-bezier(.15,.65,.35,1)",
        fill:"forwards"
    });

    animation.onfinish = function(){
        spark.remove();
    };
}

function spawnFlightSpark(){

    if(!astronautBuddy || !flightEffects) return;

    const rect = astronautBuddy.getBoundingClientRect();

    const directionX = astronautBuddy.dataset.flightX === "left" ? -1 : 1;

    const x = rect.left + rect.width / 2 - (directionX * 43);
    const y = rect.top + rect.height * .58;

    for(let i = 0; i < 2; i++){

        createSpark(
            x + (Math.random() * 18 - 9),
            y + (Math.random() * 24 - 12),
            {
                distance:Math.random() * 35 + 15,
                angle:Math.PI + (Math.random() * .9 - .45)
            }
        );
    }
}

function startFlightTrail(){

    clearFlightEffects();

    if(reducedMotion) return;

    spawnFlightSpark();

    flightSparkTimer = setInterval(function(){
        if(guideMoving){
            spawnFlightSpark();
        }
    },45);

    flightEndTimer = setTimeout(function(){
        if(flightSparkTimer){
            clearInterval(flightSparkTimer);
            flightSparkTimer = null;
        }
    },1450);
}

function createLandingBurst(){

    if(!astronautBuddy || reducedMotion) return;

    const rect = astronautBuddy.getBoundingClientRect();

    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top + rect.height * .58;

    const totalSparks = 15;

    for(let i = 0; i < totalSparks; i++){

        const angle = (Math.PI * 2 / totalSparks) * i;

        createSpark(centerX,centerY,{
            landing:true,
            angle:angle,
            distance:Math.random() * 48 + 25
        });
    }
}

/* =====================================================
   ASTRONAUT ACTIONS
===================================================== */

function clearAstronautActions(){
    astronautBuddy.classList.remove(
        "is-pointing",
        "is-waving",
        "is-spinning",
        "is-happy"
    );
}

/* POSITION ASTRONAUT NEAR THE HIGHLIGHTED TARGET */
function moveAstronautTo(target){

    if(!target) return;

    /*
       Get the actual current position before changing
       the astronaut into tour mode.
    */
    const currentLeft = astronautBuddy.getBoundingClientRect().left;

    guideMoving = true;

    const isFinalStep = target === astronautBuddy;

    const rect = target.getBoundingClientRect();

    const astronautWidth = window.innerWidth <= 700 ? 125 : 170;
    const astronautHeight = window.innerWidth <= 700 ? 165 : 210;

    let left;
    let top;

    /*
       FINAL STEP:
       Keep astronaut at bottom-right and guide card at top.
       Do not place the astronaut beside itself.
    */
    if(isFinalStep){

        left = window.innerWidth - astronautWidth - 25;
        top = window.innerHeight - astronautHeight - 25;

    }else{

        const centerX = rect.left + (rect.width / 2);
        const centerY = rect.top + (rect.height / 2);

        /*
           Position astronaut beside the target.
        */
        if(centerX > window.innerWidth / 2){
            left = rect.left - astronautWidth - 25;
        }else{
            left = rect.right + 25;
        }

        if(left < 10 || left + astronautWidth > window.innerWidth - 10){
            left = centerX - (astronautWidth / 2);
        }

        top = centerY - (astronautHeight / 2);

        left = Math.max(10, Math.min(left, window.innerWidth - astronautWidth - 10));
        top = Math.max(85, Math.min(top, window.innerHeight - astronautHeight - 20));
    }

    astronautBuddy.classList.add("is-tour");

    clearAstronautActions();

    /*
       Determine flight direction for the fire trail.
    */
    const direction = left >= currentLeft ? "right" : "left";

    astronautBuddy.dataset.flightX = direction;

    astronautBuddy.style.filter =
        "drop-shadow(0 15px 25px rgba(0,0,0,.5)) " +
        "drop-shadow(0 0 25px rgba(74,222,128,.5))";

    /*
       Start sparks before moving.
    */
    startFlightTrail();

    astronautBuddy.style.left = left + "px";
    astronautBuddy.style.top = top + "px";

    setTimeout(function(){

        guideMoving = false;

        clearFlightEffects();

        /*
           Landing spark effect.
        */
        createLandingBurst();

        if(!isFinalStep){

            astronautBuddy.classList.add("is-pointing");

            setTimeout(function(){

                astronautBuddy.classList.remove("is-pointing");

                if(Math.random() > .5){

                    astronautBuddy.classList.add("is-waving");

                    setTimeout(function(){
                        astronautBuddy.classList.remove("is-waving");
                    },1400);
                }

            },900);

        }else{

            astronautBuddy.classList.add("is-waving");

            setTimeout(function(){
                astronautBuddy.classList.remove("is-waving");
            },1400);

        }

    },1400);
}

/* RANDOM SPACE ACTIONS */
function startRandomAstronautActions(){

    stopRandomAstronautActions();

    randomActionTimer = setInterval(function(){

        if(!guideCard.classList.contains("active")) return;
        if(guideMoving) return;

        if(currentGuideStep === availableGuideSteps.length - 1) return;

        clearAstronautActions();

        const actions = [
            "is-waving",
            "is-spinning",
            "is-happy"
        ];

        const action = actions[Math.floor(Math.random() * actions.length)];

        astronautBuddy.classList.add(action);

        setTimeout(function(){
            astronautBuddy.classList.remove(action);
        },1600);

    },6500);
}

function stopRandomAstronautActions(){

    if(randomActionTimer){
        clearInterval(randomActionTimer);
        randomActionTimer = null;
    }
}

/* =====================================================
   TOUR HIGHLIGHT
===================================================== */
function clearGuideHighlight(){

    if(activeGuideElement){
        activeGuideElement.classList.remove("guide-highlight");
        activeGuideElement = null;
    }
}

/* =====================================================
   DISPLAY TOUR STEP
===================================================== */
function showGuideStep(index){

    if(index < 0 || index >= availableGuideSteps.length) return;

    currentGuideStep = index;

    const step = availableGuideSteps[index];
    const target = document.querySelector(step.selector);
    const isFinalStep = index === availableGuideSteps.length - 1;

    clearGuideHighlight();

    if(!target){
        finishGuide();
        return;
    }

    /*
       Final astronaut step:
       Do not apply the giant highlight shadow to the astronaut.
       It can cause visual overlap with the guide.
    */
    if(!isFinalStep){

        activeGuideElement = target;
        activeGuideElement.classList.add("guide-highlight");

    }else{

        activeGuideElement = null;

    }

    guideCard.classList.toggle("final-step",isFinalStep);

    guideTitle.textContent = step.title;
    guideDescription.textContent = step.description;

    guideCounter.textContent =
        "Step " + (index + 1) + " of " + availableGuideSteps.length;

    guideProgress.style.width =
        ((index + 1) / availableGuideSteps.length * 100) + "%";

    guideBack.style.visibility = index === 0 ? "hidden" : "visible";

    guideNext.innerHTML =
        index === availableGuideSteps.length - 1
        ? 'Finish <i class="fa-solid fa-check"></i>'
        : 'Next <i class="fa-solid fa-arrow-right"></i>';

    guideOverlay.classList.add("active");
    guideCard.classList.add("active");

    /*
       Final step does not need scrollIntoView.
       The astronaut is already a fixed element.
    */
    if(!isFinalStep){

        target.scrollIntoView({
            behavior:"smooth",
            block:"center"
        });

    }

    setTimeout(function(){
        moveAstronautTo(target);
    },isFinalStep ? 100 : 500);

    startRandomAstronautActions();
}

/* =====================================================
   START TOUR
===================================================== */
function startGuide(){

    guideWelcome.classList.remove("active");
    assistantPanel.classList.remove("active");

    if(availableGuideSteps.length === 0) return;

    astronautBuddy.classList.add("is-tour");

    showGuideStep(0);
}

/* =====================================================
   FINISH TOUR
===================================================== */
function finishGuide(){

    guideMoving = false;

    clearFlightEffects();
    clearGuideHighlight();
    stopRandomAstronautActions();

    guideOverlay.classList.remove("active");
    guideCard.classList.remove("active");
    guideCard.classList.remove("final-step");
    guideWelcome.classList.remove("active");

    clearAstronautActions();

    astronautBuddy.classList.remove("is-tour");

    astronautBuddy.style.left = "";
    astronautBuddy.style.top = "";
    astronautBuddy.style.filter = "";

    delete astronautBuddy.dataset.flightX;

    astronautBuddy.classList.add("is-happy");

    setTimeout(function(){
        astronautBuddy.classList.remove("is-happy");
    },1700);

    try{
        localStorage.setItem("skKonextGuideCompleted","true");
    }catch(error){
        console.warn("Guide preference could not be saved.");
    }

    if(astronautQuote){
        astronautQuote.textContent = "You're ready to explore!";
    }
}

/* MAYBE LATER */
function closeWelcome(){

    guideWelcome.classList.remove("active");

    try{
        localStorage.setItem("skKonextGuideCompleted","true");
    }catch(error){
        console.warn("Guide preference could not be saved.");
    }
}

/* =====================================================
   GUIDE BUTTON EVENTS
===================================================== */
guideStart.addEventListener("click",startGuide);
guideLater.addEventListener("click",closeWelcome);

guideNext.addEventListener("click",function(){

    if(currentGuideStep >= availableGuideSteps.length - 1){
        finishGuide();
    }else{
        showGuideStep(currentGuideStep + 1);
    }

});

guideBack.addEventListener("click",function(){

    if(currentGuideStep > 0){
        showGuideStep(currentGuideStep - 1);
    }

});

guideSkip.addEventListener("click",finishGuide);

/* =====================================================
   FIRST VISIT
===================================================== */
try{

    if(!localStorage.getItem("skKonextGuideCompleted")){

        setTimeout(function(){
            guideWelcome.classList.add("active");
        },1200);

    }

}catch(error){
    console.warn("Guide preference could not be checked.");
}

/* =====================================================
   ASTRONAUT ASSISTANT
===================================================== */

function toggleAssistant(){

    const isOpen = assistantPanel.classList.contains("active");

    assistantPanel.classList.toggle("active");

    if(!isOpen){

        guideWelcome.classList.remove("active");
        assistantAnswer.classList.remove("active");

        if(!astronautBuddy.classList.contains("is-tour")){
            astronautBuddy.classList.add("is-waving");

            setTimeout(function(){
                astronautBuddy.classList.remove("is-waving");
            },1400);
        }

    }
}

/* ASTRONAUT CLICK */
if(astronautBuddy){

    astronautBuddy.addEventListener("click",function(event){

        event.stopPropagation();

        /*
           During tour, astronaut is only a guide.
           Do not open assistant while moving through steps.
        */
        if(astronautBuddy.classList.contains("is-tour")) return;

        toggleAssistant();

    });

    astronautBuddy.addEventListener("keydown",function(event){

        if(event.key === "Enter" || event.key === " "){

            event.preventDefault();

            if(!astronautBuddy.classList.contains("is-tour")){
                toggleAssistant();
            }

        }

    });

}

/* CLOSE ASSISTANT */
assistantClose.addEventListener("click", function(){
    assistantPanel.classList.remove("active");
});

/* ASSISTANT ANSWERS */
const assistantAnswers = {

    scholarship:{
        text:"To apply for a scholarship, open the Scholarship Application button when applications are available. Log in to your account first if required, then follow the application instructions."
    },

    account:{
        text:"To access applicant features, go to the Login page. If you do not have an account yet, look for the registration option and complete the required information."
    },

    map:{
        text:"The Community Map allows visitors to explore community projects and their locations. Click the Community Map button on the homepage or in the navigation menu."
    },

    events:{
        text:"You can view community events by clicking Events in the navigation menu or the Events button on the homepage. Recent community activities are also displayed in the Activities section."
    },

    about:{
        text:"SK KONEXT is a digital platform designed to connect the Sangguniang Kabataan with the community through transparency, youth engagement, scholarships, projects, and activities."
    }

};

/* DISPLAY ANSWERS */
document.querySelectorAll(".assistant-question").forEach(function(button){

    button.addEventListener("click",function(){

        const answerKey = button.getAttribute("data-answer");
        const answer = assistantAnswers[answerKey];

        if(!answer) return;

        assistantAnswer.textContent = answer.text;
        assistantAnswer.classList.add("active");

    });

});

/* REPLAY TOUR */
replayGuide.addEventListener("click",function(){

    assistantPanel.classList.remove("active");
    guideWelcome.classList.remove("active");

    startGuide();

});

/* ESCAPE KEY */
document.addEventListener("keydown",function(event){

    if(event.key === "Escape"){

        if(guideCard.classList.contains("active")){
            finishGuide();
        }else{
            assistantPanel.classList.remove("active");
            guideWelcome.classList.remove("active");
        }

    }

});

/* UPDATE ASTRONAUT POSITION WHEN WINDOW RESIZES */
window.addEventListener("resize",function(){

    if(
        guideCard.classList.contains("active") &&
        activeGuideElement
    ){
        moveAstronautTo(activeGuideElement);
    }else if(
        guideCard.classList.contains("active") &&
        currentGuideStep === availableGuideSteps.length - 1
    ){
        moveAstronautTo(astronautBuddy);
    }

});

</script>

</body>
</html>