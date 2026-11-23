
<?php

include("../db.php");
include("../includes/auth.php");

/* =========================================
   SUPERADMIN ONLY
========================================= */

allowRoles(['superadmin']);

/* =========================================
   SEARCH AND SORT
========================================= */

$search = trim($_GET['search'] ?? '');

$sort = $_GET['sort'] ?? 'name';

$allowed_sort = [
    'name' => 'b.name ASC',
    'name_desc' => 'b.name DESC',
    'residents' => 'resident_count DESC',
    'residents_asc' => 'resident_count ASC'
];

$order_by = $allowed_sort[$sort] ?? 'b.name ASC';

/* =========================================
   BARANGAY STATISTICS
========================================= */

$barangay_count = 0;
$total_residents = 0;
$barangays_with_residents = 0;

$barangay_stats = mysqli_query($conn,"
    SELECT COUNT(*) AS total
    FROM barangays
    WHERE name IS NOT NULL
    AND name <> ''
");

if($barangay_stats){
    $data = mysqli_fetch_assoc($barangay_stats);
    $barangay_count = (int)$data['total'];
}

$resident_stats = mysqli_query($conn,"
    SELECT
        COUNT(DISTINCT a.id) AS total_residents,
        COUNT(DISTINCT p.barangay_id) AS active_barangays
    FROM applicant_accounts a
    INNER JOIN applicant_profiles p
        ON p.applicant_id = a.id
    WHERE a.is_verified = 1
    AND p.barangay_id IS NOT NULL
");

if($resident_stats){
    $data = mysqli_fetch_assoc($resident_stats);

    $total_residents = (int)$data['total_residents'];
    $barangays_with_residents = (int)$data['active_barangays'];
}

/* =========================================
   GET BARANGAYS AND VERIFIED RESIDENT COUNT
========================================= */

$sql = "
    SELECT
        b.id,
        b.name,
        COUNT(DISTINCT a.id) AS resident_count

    FROM barangays b

    LEFT JOIN applicant_profiles p
        ON p.barangay_id = b.id

    LEFT JOIN applicant_accounts a
        ON a.id = p.applicant_id
        AND a.is_verified = 1

    WHERE b.name IS NOT NULL
    AND b.name <> ''
";

if($search !== ''){
    $sql .= " AND b.name LIKE ?";
}

$sql .= "
    GROUP BY b.id, b.name
    ORDER BY $order_by
";

$stmt = mysqli_prepare($conn, $sql);

if($stmt){

    if($search !== ''){
        $search_param = "%".$search."%";
        mysqli_stmt_bind_param($stmt, "s", $search_param);
    }

    mysqli_stmt_execute($stmt);

    $barangays = mysqli_stmt_get_result($stmt);

}else{
    $barangays = false;
}

$displayed_barangays = $barangays
    ? mysqli_num_rows($barangays)
    : 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Resident Profiling | SK KONEXT</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap"
rel="stylesheet">

<link rel="stylesheet" href="assets/style.css">

<style>

/* =========================================
   GLOBAL
========================================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'DM Sans',sans-serif;
    background:#07100d;
    color:#e8f1ed;
    min-height:100vh;
}

body::before{
    content:"";
    position:fixed;
    inset:0;
    background:
        radial-gradient(circle at 10% 10%,rgba(34,197,94,.10),transparent 30%),
        radial-gradient(circle at 90% 20%,rgba(16,185,129,.08),transparent 28%);
    pointer-events:none;
    z-index:-1;
}

/* =========================================
   SIDEBAR
========================================= */

.sidebar{
    position:fixed;
    left:18px;
    top:18px;
    width:255px;
    height:calc(100vh - 36px);
    background:linear-gradient(180deg,rgba(18,34,28,.96),rgba(9,22,17,.97));
    border:1px solid rgba(148,163,184,.12);
    border-radius:28px;
    padding:24px 16px;
    overflow-y:auto;
    box-shadow:0 25px 70px rgba(0,0,0,.35);
    backdrop-filter:blur(20px);
    z-index:1000;
}

.sidebar::-webkit-scrollbar{
    width:5px;
}

.sidebar::-webkit-scrollbar-thumb{
    background:rgba(148,163,184,.18);
    border-radius:20px;
}

.navbar-brand{
    display:flex !important;
    align-items:center;
    gap:11px;
    padding:10px 13px !important;
    margin-bottom:25px;
    color:#f4fff9 !important;
    font-family:'Manrope',sans-serif;
    font-size:20px;
    font-weight:800;
    text-decoration:none !important;
}

.navbar-brand i{
    width:38px;
    height:38px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:12px;
    background:linear-gradient(135deg,#22c55e,#059669);
    color:white;
}

.sidebar a:not(.navbar-brand){
    display:flex;
    align-items:center;
    gap:12px;
    padding:13px 14px;
    margin:5px 0;
    border-radius:14px;
    color:#a9bcb3;
    font-size:14px;
    font-weight:500;
    text-decoration:none;
    transition:.25s;
}

.sidebar a:not(.navbar-brand) i{
    width:21px;
    text-align:center;
    color:#71867b;
}

.sidebar a:not(.navbar-brand):hover{
    background:rgba(34,197,94,.08);
    color:#f1fff6;
    transform:translateX(3px);
}

.sidebar a.active{
    background:linear-gradient(135deg,rgba(34,197,94,.20),rgba(16,185,129,.08));
    color:#ecfff3;
    border:1px solid rgba(74,222,128,.13);
}

.sidebar a.active i{
    color:#4ade80;
}

/* =========================================
   MAIN
========================================= */

.main{
    margin-left:291px;
    padding:28px 32px 55px;
    min-height:100vh;
    max-width:1600px;
}

/* =========================================
   TOPBAR
========================================= */

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    margin-bottom:34px;
}

.topbar-eyebrow{
    display:flex;
    align-items:center;
    gap:8px;
    color:#4ade80;
    text-transform:uppercase;
    font-size:11px;
    font-weight:700;
    letter-spacing:2px;
    margin-bottom:8px;
}

.topbar-eyebrow span{
    width:7px;
    height:7px;
    border-radius:50%;
    background:#4ade80;
}

.topbar h2{
    margin:0;
    font-family:'Manrope',sans-serif;
    font-size:34px;
    font-weight:800;
    color:#f4fbf7;
}

.topbar p{
    margin:9px 0 0;
    color:#81978d;
    font-size:14px;
}

.admin-badge{
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px 15px;
    border-radius:16px;
    background:rgba(18,34,28,.75);
    border:1px solid rgba(148,163,184,.12);
    color:#dcebe3;
    font-size:13px;
    font-weight:600;
}

.admin-badge::before{
    content:"";
    width:8px;
    height:8px;
    border-radius:50%;
    background:#4ade80;
}

/* =========================================
   STAT CARDS
========================================= */

.stat-card{
    height:100%;
    min-height:145px;
    padding:22px;
    border-radius:22px;
    background:linear-gradient(145deg,rgba(18,34,28,.92),rgba(10,23,18,.86));
    border:1px solid rgba(148,163,184,.10);
    box-shadow:0 20px 45px rgba(0,0,0,.18);
    transition:.3s;
}

.stat-card:hover{
    transform:translateY(-4px);
    border-color:rgba(74,222,128,.18);
}

.stat-icon{
    width:43px;
    height:43px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:13px;
    margin-bottom:16px;
    background:rgba(34,197,94,.10);
    color:#4ade80;
    font-size:18px;
}

.stat-card h3{
    margin:0;
    font-family:'Manrope',sans-serif;
    color:#f4fbf7;
    font-size:29px;
    font-weight:800;
}

.stat-card p{
    margin:6px 0 0;
    color:#82968d;
    font-size:13px;
}

/* =========================================
   PANEL
========================================= */

.panel{
    background:linear-gradient(145deg,rgba(18,34,28,.94),rgba(9,22,17,.91));
    border:1px solid rgba(148,163,184,.10);
    border-radius:24px;
    margin-top:25px;
    padding:26px;
    box-shadow:0 20px 50px rgba(0,0,0,.18);
}

.panel-heading{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    margin-bottom:20px;
}

.panel-heading-left{
    display:flex;
    align-items:center;
    gap:11px;
}

.panel-heading-icon{
    width:38px;
    height:38px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:12px;
    background:rgba(34,197,94,.09);
    color:#4ade80;
}

.panel h4{
    margin:0;
    color:#effaf4;
    font-family:'Manrope',sans-serif;
    font-size:18px;
    font-weight:700;
}

/* =========================================
   SEARCH AND SORT
========================================= */

.filter-row{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:23px;
}

.search-box{
    position:relative;
    flex:1;
    min-width:220px;
}

.search-box i{
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    color:#71867b;
}

.search-box input,
.filter-row select{
    width:100%;
    background:#102019;
    color:#e8f1ed;
    border:1px solid rgba(148,163,184,.15);
    border-radius:11px;
    padding:11px 13px;
    outline:none;
    font-size:13px;
}

.search-box input{
    padding-left:40px;
}

.search-box input:focus,
.filter-row select:focus{
    border-color:rgba(74,222,128,.45);
}

.search-box input::placeholder{
    color:#71867b;
}

.sort-box{
    width:190px;
}

/* =========================================
   BARANGAY CARDS
========================================= */

.barangay-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:16px;
}

.barangay-card{
    display:block;
    position:relative;
    padding:20px;
    min-height:155px;
    border-radius:18px;
    background:rgba(255,255,255,.025);
    border:1px solid rgba(148,163,184,.11);
    text-decoration:none !important;
    transition:.25s;
    overflow:hidden;
}

.barangay-card:hover{
    transform:translateY(-4px);
    background:rgba(34,197,94,.06);
    border-color:rgba(74,222,128,.30);
}

.barangay-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:10px;
    margin-bottom:19px;
}

.barangay-icon{
    width:43px;
    height:43px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:13px;
    background:rgba(34,197,94,.10);
    color:#4ade80;
    font-size:18px;
}

.barangay-arrow{
    color:#64796e;
    font-size:13px;
    transition:.25s;
}

.barangay-card:hover .barangay-arrow{
    color:#4ade80;
    transform:translateX(4px);
}

.barangay-card h5{
    color:#edf8f1;
    font-family:'Manrope',sans-serif;
    font-size:15px;
    font-weight:700;
    margin:0 0 7px;
}

.resident-count{
    color:#82968d;
    font-size:12px;
}

.resident-count strong{
    color:#4ade80;
    font-weight:700;
}

/* =========================================
   EMPTY STATE
========================================= */

.empty-state{
    text-align:center;
    padding:55px 15px;
    color:#81978d;
}

.empty-state i{
    display:block;
    font-size:35px;
    margin-bottom:14px;
    color:#40584b;
}

.empty-state h5{
    color:#dcebe3;
    font-size:16px;
    margin-bottom:7px;
}

.empty-state p{
    font-size:13px;
    margin:0;
}

/* =========================================
   RESPONSIVE
========================================= */

@media(max-width:1200px){
    .barangay-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
}

@media(max-width:991px){

    .sidebar{
        position:relative;
        left:auto;
        top:auto;
        width:calc(100% - 30px);
        height:auto;
        margin:15px;
        border-radius:22px;
    }

    .main{
        margin-left:0;
        padding:20px;
    }
}

@media(max-width:600px){

    .main{
        padding:16px;
    }

    .topbar{
        align-items:flex-start;
        flex-direction:column;
    }

    .topbar h2{
        font-size:28px;
    }

    .barangay-grid{
        grid-template-columns:1fr;
    }

    .panel{
        padding:19px;
    }

    .filter-row{
        align-items:stretch;
        flex-direction:column;
    }

    .sort-box{
        width:100%;
    }
}

</style>

</head>

<body>

<!-- =========================================
     SIDEBAR
========================================= -->

<div class="sidebar">

    <a class="navbar-brand" href="dashboard.php">
        <i class="fa-solid fa-shield-halved"></i>
        <span>SK KONEXT</span>
    </a>

    <a href="dashboard.php">
        <i class="fa-solid fa-chart-line"></i>
        <span>Dashboard</span>
    </a>

    <a href="users.php">
        <i class="fa-solid fa-users"></i>
        <span>Manage Users</span>
    </a>

    <a href="scholars.php">
        <i class="fa-solid fa-user-graduate"></i>
        <span>Scholars</span>
    </a>

    <a href="map.php">
        <i class="fa-solid fa-map-location-dot"></i>
        <span>Map System</span>
    </a>

    <a href="reports.php">
        <i class="fa-solid fa-file-invoice"></i>
        <span>Reports</span>
    </a>

    <a href="residents.php" class="active">
        <i class="fa-solid fa-users-viewfinder"></i>
        <span>Resident Profiling</span>
    </a>

    <a href="phone_verification.php">
    <i class="fa-solid fa-mobile-screen-button"></i>
    <span>Phone Verification</span>
</a>

    <a href="../logout.php">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span>Logout</span>
    </a>

</div>

<!-- =========================================
     MAIN
========================================= -->

<div class="main">

    <!-- TOPBAR -->

    <div class="topbar">

        <div>

            <div class="topbar-eyebrow">
                <span></span>
                Community Management
            </div>

            <h2>Resident Profiling</h2>

            <p>
                Browse barangays and manage their registered residents.
            </p>

        </div>

        <div class="admin-badge">
            Superadmin
        </div>

    </div>

    <!-- STATISTICS -->

    <div class="row">

        <div class="col-xl-4 col-md-6 mb-3">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-building"></i>
                </div>

                <h3>
                    <?= number_format($barangay_count) ?>
                </h3>

                <p>Total Barangays</p>

            </div>

        </div>

        <div class="col-xl-4 col-md-6 mb-3">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>

                <h3>
                    <?= number_format($total_residents) ?>
                </h3>

                <p>Verified Residents</p>

            </div>

        </div>

        <div class="col-xl-4 col-md-6 mb-3">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="fa-solid fa-location-dot"></i>
                </div>

                <h3>
                    <?= number_format($barangays_with_residents) ?>
                </h3>

                <p>Barangays With Registered Residents</p>

            </div>

        </div>

    </div>

    <!-- BARANGAY LIST -->

    <div class="panel">

        <div class="panel-heading">

            <div class="panel-heading-left">

                <div class="panel-heading-icon">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>

                <h4>Barangay List</h4>

            </div>

            <span style="color:#81978d;font-size:12px;">
                <?= number_format($displayed_barangays) ?> barangays shown
            </span>

        </div>

        <!-- SEARCH AND SORT -->

        <form method="GET" class="filter-row">

            <div class="search-box">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    name="search"
                    placeholder="Search barangay..."
                    value="<?= htmlspecialchars($search) ?>"
                >

            </div>

            <div class="sort-box">

                <select name="sort" onchange="this.form.submit()">

                    <option value="name"
                        <?= $sort === 'name' ? 'selected' : '' ?>>
                        Name: A to Z
                    </option>

                    <option value="name_desc"
                        <?= $sort === 'name_desc' ? 'selected' : '' ?>>
                        Name: Z to A
                    </option>

                    <option value="residents"
                        <?= $sort === 'residents' ? 'selected' : '' ?>>
                        Most Residents
                    </option>

                    <option value="residents_asc"
                        <?= $sort === 'residents_asc' ? 'selected' : '' ?>>
                        Fewest Residents
                    </option>

                </select>

            </div>

            <button
                type="submit"
                class="btn"
                style="
                    background:rgba(34,197,94,.12);
                    color:#4ade80;
                    border:1px solid rgba(74,222,128,.20);
                    border-radius:11px;
                    padding:10px 16px;
                    font-size:13px;
                "
            >
                Search
            </button>

        </form>

        <!-- BARANGAY CARDS -->

        <?php if($barangays && $displayed_barangays > 0){ ?>

            <div class="barangay-grid">

                <?php while($barangay = mysqli_fetch_assoc($barangays)){ ?>

                    <a
                        href="barangay_residents.php?barangay_id=<?= (int)$barangay['id'] ?>"
                        class="barangay-card"
                    >

                        <div class="barangay-top">

                            <div class="barangay-icon">
                                <i class="fa-solid fa-building"></i>
                            </div>

                            <i class="fa-solid fa-arrow-right barangay-arrow"></i>

                        </div>

                        <h5>
                            <?= htmlspecialchars($barangay['name']) ?>
                        </h5>

                        <div class="resident-count">

                            <strong>
                                <?= number_format((int)$barangay['resident_count']) ?>
                            </strong>

                            <?= (int)$barangay['resident_count'] === 1
                                ? 'Verified Resident'
                                : 'Verified Residents'
                            ?>

                        </div>

                    </a>

                <?php } ?>

            </div>

        <?php }else{ ?>

            <div class="empty-state">

                <i class="fa-solid fa-building-circle-exclamation"></i>

                <h5>No Barangays Found</h5>

                <p>
                    No barangay matches your search.
                </p>

            </div>

        <?php } ?>

    </div>

</div>

</body>
</html>
