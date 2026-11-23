<?php

include("../db.php");
include("../includes/auth.php");

allowRoles(['superadmin']);

/* =========================================
   REPORT TEMPLATES
========================================= */

$reportCategories = [

    [
        'title' => 'Financial Reports',
        'icon' => 'fa-chart-column',
        'description' => 'Financial reports for SK funds, budget, receipts, payments, and utilization.',

        'reports' => [

            [
                'title' => 'Register of Cash in Bank and Other Related Financial Transactions',
                'description' => 'Quarterly record of cash deposits, withdrawals, balances, and related transactions.',
                'template' => 'quarterly_financial',
                'icon' => 'fa-building-columns'
            ],

            [
                'title' => 'Monthly Itemized List of Purchase Request',
                'description' => 'Monthly itemized list of SK purchase requests and estimated amounts.',
                'template' => 'monthly_financial',
                'icon' => 'fa-list'
            ],

            [
                'title' => 'Purchase Request',
                'description' => 'Purchase request form with item descriptions, quantities, and estimated costs.',
                'template' => 'annual_financial',
                'icon' => 'fa-cart-shopping'
            ],

            [
                'title' => 'SK Full Public Disclosure Policy Board',
                'description' => 'Tracking board for required SK public disclosure documents.',
                'template' => 'budget_utilization',
                'icon' => 'fa-clipboard-list'
            ],

            [
                'title' => 'Comprehensive Barangay Youth Development Plan (CBYDP)',
                'description' => 'Barangay youth development concerns, objectives, targets, PPAs, and budgets.',
                'template' => 'receipts_payments',
                'icon' => 'fa-people-group'
            ]

        ]
    ],


    [
        'title' => 'Accomplishment Reports',
        'icon' => 'fa-clipboard-check',
        'description' => 'Reports for programs, projects, activities, outputs, and accomplishments.',

        'reports' => [

            [
                'title' => 'Monthly Accomplishment Report',
                'description' => 'Monthly record of programs, projects, activities, and accomplishments.',
                'template' => 'monthly_accomplishment',
                'icon' => 'fa-calendar-days'
            ],

            [
                'title' => 'Quarterly Accomplishment Report',
                'description' => 'Quarterly summary of SK programs, projects, and activities.',
                'template' => 'quarterly_accomplishment',
                'icon' => 'fa-calendar'
            ],

            [
                'title' => 'Annual Accomplishment Report',
                'description' => 'Annual accomplishment summary of the SK Federation.',
                'template' => 'annual_accomplishment',
                'icon' => 'fa-calendar-check'
            ],

            [
                'title' => 'PPA Accomplishment Report',
                'description' => 'Track Programs, Projects, and Activities and their implementation.',
                'template' => 'ppa_accomplishment',
                'icon' => 'fa-list-check'
            ]

        ]
    ],


    [
        'title' => 'Federation Reports',
        'icon' => 'fa-building-columns',
        'description' => 'Reports prepared for the Municipal SK Federation of Naic, Cavite.',

        'reports' => [

            [
                'title' => 'Consolidated Federation Accomplishment Report',
                'description' => 'Consolidated accomplishment report covering the SK barangays of Naic.',
                'template' => 'federation_consolidated',
                'icon' => 'fa-layer-group'
            ],

            [
                'title' => 'Federation Activities Report',
                'description' => 'Record of Federation-led programs, projects, and youth activities.',
                'template' => 'federation_activities',
                'icon' => 'fa-people-group'
            ],

            [
                'title' => 'Federation Meeting Report',
                'description' => 'Record of meetings, agenda, discussions, and actions taken.',
                'template' => 'federation_meetings',
                'icon' => 'fa-users-rectangle'
            ]

        ]
    ]

];

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Reports | SK KONEXT</title>


<!-- Bootstrap -->

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
>


<!-- Font Awesome -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
>


<!-- Google Fonts -->

<link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap"
    rel="stylesheet"
>


<style>

/* =========================================
   GLOBAL
========================================= */

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {

    margin: 0;

    min-height: 100vh;

    background: #07100d;

    color: #e8f1ed;

    font-family: 'DM Sans', sans-serif;

}


/* =========================================
   BACKGROUND
========================================= */

body::before {

    content: "";

    position: fixed;

    inset: 0;

    z-index: -1;

    pointer-events: none;

    background:

        radial-gradient(
            circle at 10% 10%,
            rgba(34,197,94,.10),
            transparent 30%
        ),

        radial-gradient(
            circle at 90% 20%,
            rgba(16,185,129,.08),
            transparent 28%
        ),

        radial-gradient(
            circle at 50% 100%,
            rgba(34,197,94,.06),
            transparent 35%
        );

}


/* =========================================
   TOPBAR
========================================= */

.topbar {

    height: 76px;

    margin: 18px 24px 0;

    padding: 0 18px 0 20px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    background:
        rgba(18,34,28,.86);

    border:
        1px solid
        rgba(148,163,184,.12);

    border-radius: 20px;

    backdrop-filter: blur(18px);

    box-shadow:
        0 20px 50px
        rgba(0,0,0,.22);

}


/* =========================================
   BRAND
========================================= */

.brand {

    display: flex;

    align-items: center;

    gap: 11px;

    color: #f4fff9 !important;

    text-decoration: none !important;

    font-family: 'Manrope', sans-serif;

    font-size: 20px;

    font-weight: 800;

}


.brand-icon {

    width: 40px;

    height: 40px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            #22c55e,
            #059669
        );

    color: white;

}


/* =========================================
   TOPBAR RIGHT
========================================= */

.topbar-actions {

    display: flex;

    align-items: center;

    gap: 9px;

}


.nav-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 10px 14px;

    border-radius: 11px;

    border:
        1px solid
        rgba(148,163,184,.12);

    background:
        rgba(255,255,255,.025);

    color: #b9cec4 !important;

    text-decoration: none !important;

    font-size: 12px;

    font-weight: 700;

    transition: .25s;

}


.nav-btn:hover {

    background:
        rgba(34,197,94,.09);

    color: #effff5 !important;

    border-color:
        rgba(74,222,128,.18);

}


/* =========================================
   MAIN
========================================= */

.main {

    max-width: 1400px;

    margin: auto;

    padding:
        48px
        30px
        65px;

}


/* =========================================
   PAGE HEADER
========================================= */

.eyebrow {

    display: flex;

    align-items: center;

    gap: 8px;

    color: #4ade80;

    font-size: 11px;

    font-weight: 700;

    letter-spacing: 2px;

    text-transform: uppercase;

    margin-bottom: 9px;

}


.eyebrow-dot {

    width: 7px;

    height: 7px;

    border-radius: 50%;

    background: #4ade80;

    box-shadow:
        0 0 12px
        rgba(74,222,128,.7);

}


h1 {

    margin: 0;

    color: #f4fbf7;

    font-family: 'Manrope', sans-serif;

    font-size: 38px;

    font-weight: 800;

    letter-spacing: -1.2px;

}


.subtitle {

    color: #81978d;

    font-size: 14px;

    margin:
        10px
        0
        40px;

    max-width: 700px;

}


/* =========================================
   CATEGORY
========================================= */

.category {

    margin-top: 30px;

}


.category-head {

    display: flex;

    align-items: center;

    gap: 13px;

    margin-bottom: 16px;

}


.category-icon {

    width: 44px;

    height: 44px;

    flex-shrink: 0;

    border-radius: 13px;

    background:
        rgba(34,197,94,.09);

    border:
        1px solid
        rgba(74,222,128,.10);

    display: flex;

    align-items: center;

    justify-content: center;

    color: #4ade80;

}


.category-title {

    font-family: 'Manrope', sans-serif;

    font-size: 19px;

    margin: 0;

    font-weight: 700;

    color: #effaf4;

}


.category-description {

    margin: 3px 0 0;

    color: #71867b;

    font-size: 12px;

}


/* =========================================
   REPORT GRID
========================================= */

.report-grid {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 14px;

}


/* =========================================
   REPORT CARD
========================================= */

.report-card {

    position: relative;

    padding: 21px;

    border-radius: 18px;

    background:

        linear-gradient(
            145deg,
            rgba(18,34,28,.94),
            rgba(9,22,17,.91)
        );

    border:
        1px solid
        rgba(148,163,184,.10);

    box-shadow:
        0 15px 35px
        rgba(0,0,0,.16);

    transition:
        transform .25s,
        border-color .25s,
        box-shadow .25s;

}


.report-card:hover {

    transform:
        translateY(-3px);

    border-color:
        rgba(74,222,128,.20);

    box-shadow:
        0 20px 40px
        rgba(0,0,0,.22);

}


/* =========================================
   REPORT ICON
========================================= */

.report-icon {

    width: 40px;

    height: 40px;

    border-radius: 11px;

    background:
        rgba(34,197,94,.08);

    border:
        1px solid
        rgba(74,222,128,.08);

    color: #4ade80;

    display: flex;

    align-items: center;

    justify-content: center;

    margin-bottom: 15px;

}


/* =========================================
   REPORT TITLE
========================================= */

.report-card h3 {

    font-family: 'Manrope', sans-serif;

    font-size: 16px;

    line-height: 1.35;

    margin:
        0
        0
        8px;

    color: #eff8f2;

}


/* =========================================
   DESCRIPTION
========================================= */

.report-card p {

    font-size: 12px;

    line-height: 1.6;

    color: #7f958a;

    min-height: 58px;

    margin:
        0
        0
        18px;

}


/* =========================================
   CREATE BUTTON
========================================= */

.create-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    padding:
        9px
        12px;

    border-radius: 10px;

    background:
        rgba(34,197,94,.10);

    border:
        1px solid
        rgba(74,222,128,.13);

    color: #72e99a !important;

    text-decoration: none !important;

    font-size: 12px;

    font-weight: 700;

    transition: .25s;

}


.create-btn:hover {

    background:
        rgba(34,197,94,.17);

    color: #baffcf !important;

    border-color:
        rgba(74,222,128,.25);

}


/* =========================================
   INFO BOX
========================================= */

.info-box {

    margin-top: 40px;

    padding:
        16px
        18px;

    border:
        1px solid
        rgba(148,163,184,.09);

    border-radius: 14px;

    background:
        rgba(255,255,255,.02);

    color: #71867b;

    font-size: 12px;

    line-height: 1.7;

}


.info-box i {

    color: #4ade80;

    margin-right: 7px;

}


/* =========================================
   RESPONSIVE
========================================= */

@media(max-width:1050px) {

    .report-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

    }

}


@media(max-width:650px) {

    .topbar {

        margin:
            12px;

        height: 64px;

    }


    .brand {

        font-size: 16px;

    }


    .brand-icon {

        width: 35px;

        height: 35px;

    }


    .topbar-actions {

        gap: 5px;

    }


    .nav-btn {

        padding:
            9px
            10px;

    }


    .nav-btn span {

        display: none;

    }


    .main {

        padding:
            35px
            16px
            50px;

    }


    h1 {

        font-size: 31px;

    }


    .report-grid {

        grid-template-columns: 1fr;

    }

}

</style>

</head>


<body>


<!-- =========================================
     TOPBAR
========================================= -->

<header class="topbar">


    <a
        href="dashboard.php"
        class="brand"
    >

        <span class="brand-icon">

            <i class="fa-solid fa-file-invoice"></i>

        </span>


        <span>
            SK KONEXT
        </span>

    </a>



    <div class="topbar-actions">


        <!-- BACK TO DASHBOARD -->

        <a
            href="dashboard.php"
            class="nav-btn"
        >

            <i class="fa-solid fa-house"></i>

            <span>
                Dashboard
            </span>

        </a>


        <!-- BACK TO REPORTS / REFRESH -->

        <a
            href="reports.php"
            class="nav-btn"
        >

            <i class="fa-solid fa-file-lines"></i>

            <span>
                Reports
            </span>

        </a>


    </div>

</header>



<!-- =========================================
     MAIN
========================================= -->

<main class="main">


    <div class="eyebrow">

        <span class="eyebrow-dot"></span>

        REPORT CENTER

    </div>


    <h1>
        Reports
    </h1>


    <p class="subtitle">

        Select a report template to prepare
        financial, accomplishment, and
        Federation reports for the
        Municipal SK Federation of Naic, Cavite.

    </p>



    <!-- =========================================
         REPORT CATEGORIES
    ========================================== -->

    <?php foreach(
        $reportCategories
        as $category
    ): ?>


        <section class="category">


            <!-- CATEGORY HEADER -->

            <div class="category-head">


                <div class="category-icon">

                    <i
                        class="fa-solid
                        <?= htmlspecialchars(
                            $category['icon']
                        ) ?>"
                    ></i>

                </div>


                <div>

                    <h2 class="category-title">

                        <?= htmlspecialchars(
                            $category['title']
                        ) ?>

                    </h2>


                    <p class="category-description">

                        <?= htmlspecialchars(
                            $category['description']
                        ) ?>

                    </p>

                </div>


            </div>



            <!-- REPORT CARDS -->

            <div class="report-grid">


                <?php foreach(
                    $category['reports']
                    as $report
                ): ?>


                    <div class="report-card">


                        <div class="report-icon">

                            <i
                                class="fa-solid
                                <?= htmlspecialchars(
                                    $report['icon']
                                ) ?>"
                            ></i>

                        </div>


                        <h3>

                            <?= htmlspecialchars(
                                $report['title']
                            ) ?>

                        </h3>


                        <p>

                            <?= htmlspecialchars(
                                $report['description']
                            ) ?>

                        </p>


                        <!--
                            REPORT EDITOR LINK

                            Hindi natin ina-assume
                            kung anong report ang pipiliin.

                            Ipinapasa mismo ang template
                            ng card na pinindot.
                        -->

                        <a
                            href="report_editor.php?template=<?= urlencode(
                                $report['template']
                            ) ?>"
                            class="create-btn"
                        >

                            <i
                                class="fa-solid
                                fa-pen-to-square"
                            ></i>

                            Create Report

                        </a>


                    </div>


                <?php endforeach; ?>


            </div>


        </section>


    <?php endforeach; ?>



    <!-- =========================================
         INFORMATION
    ========================================== -->

    <div class="info-box">

        <i
            class="fa-solid
            fa-circle-info"
        ></i>

        Choose the appropriate report template
        above. Each template will open its own
        report editor once the editor module is
        connected.

    </div>


</main>


</body>

</html>