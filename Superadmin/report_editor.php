    <?php
    require_once "../db.php";
    require_once "../includes/auth.php";

    allowRoles(['superadmin']);

    /*
    |--------------------------------------------------------------------------
    | GET TEMPLATE
    |--------------------------------------------------------------------------
    */

    $template = $_GET['template'] ?? '';

    $templates = [

        // FINANCIAL
        'monthly_financial' => [
            'title' => 'Monthly Itemized List of Purchase Request',
            'category' => 'SK Official Form'
        ],

        'quarterly_financial' => [
            'title' => 'Register of Cash in Bank and Other Related Financial Transactions',
            'category' => 'Financial Report'
        ],

        'annual_financial' => [
            'title' => 'Purchase Request',
            'category' => 'SK Official Form'
        ],

        'budget_utilization' => [
            'title' => 'SK Full Public Disclosure Policy Board',
            'category' => 'Public Disclosure'
        ],

        'receipts_payments' => [
            'title' => 'Comprehensive Barangay Youth Development Plan (CBYDP)',
            'category' => 'Youth Development Plan'
        ],

        // ACCOMPLISHMENT
        'monthly_accomplishment' => [
            'title' => 'Monthly Accomplishment Report',
            'category' => 'Accomplishment Report'
        ],

        'quarterly_accomplishment' => [
            'title' => 'Quarterly Accomplishment Report',
            'category' => 'Accomplishment Report'
        ],

        'annual_accomplishment' => [
            'title' => 'Annual Accomplishment Report',
            'category' => 'Accomplishment Report'
        ],

        'ppa_accomplishment' => [
            'title' => 'PPA Accomplishment Report',
            'category' => 'Accomplishment Report'
        ],

        // FEDERATION
        'federation_consolidated' => [
            'title' => 'Consolidated Federation Accomplishment Report',
            'category' => 'Federation Report'
        ],

        'federation_activities' => [
            'title' => 'Federation Activities Report',
            'category' => 'Federation Report'
        ],

        'federation_meetings' => [
            'title' => 'Federation Meeting Report',
            'category' => 'Federation Report'
        ]
    ];


    /*
    |--------------------------------------------------------------------------
    | INVALID TEMPLATE
    |--------------------------------------------------------------------------
    */

    if (!isset($templates[$template])) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Report Editor</title>

            <style>
                body {
                    margin: 0;
                    font-family: Arial, sans-serif;
                    background: #f3f4f6;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                }

                .error-box {
                    background: white;
                    padding: 35px;
                    width: 420px;
                    text-align: center;
                    border-radius: 12px;
                    box-shadow: 0 5px 20px rgba(0,0,0,.08);
                }

                .error-box h2 {
                    margin-top: 0;
                }

                .btn {
                    display: inline-block;
                    margin-top: 15px;
                    padding: 11px 20px;
                    background: #1f2937;
                    color: white;
                    text-decoration: none;
                    border-radius: 7px;
                }
            </style>
        </head>

        <body>

            <div class="error-box">
                <h2>Report Template Not Found</h2>
                <p>Please select a valid report template.</p>

                <a href="reports.php" class="btn">
                    ← Back to Reports
                </a>
            </div>

        </body>
        </html>
        <?php
        exit;
    }


    $reportTitle = $templates[$template]['title'];
    $reportCategory = $templates[$template]['category'];

    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title><?= htmlspecialchars($reportTitle) ?> | SK KONEXT</title>

        <style>

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: Arial, Helvetica, sans-serif;
                background: #eef1f5;
                color: #222;
            }

            /* TOP BAR */

            .topbar {
                height: 65px;
                background: #ffffff;
                border-bottom: 1px solid #ddd;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 25px;
                position: sticky;
                top: 0;
                z-index: 20;
            }

            .topbar-left {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .logo-text {
                font-size: 19px;
                font-weight: bold;
                color: #1d3557;
            }

            .topbar-right {
                display: flex;
                gap: 10px;
            }

            .top-btn {
                text-decoration: none;
                padding: 9px 15px;
                border-radius: 7px;
                font-size: 14px;
                font-weight: bold;
            }

            .back-btn {
                background: #f1f3f5;
                color: #333;
            }

            .dashboard-btn {
                background: #1d3557;
                color: white;
            }


            /* MAIN */

            .page {
                width: 100%;
                padding: 30px;
            }

            .editor-container {
                max-width: 1250px;
                margin: auto;
            }


            /* EDITOR HEADER */

            .editor-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .editor-header h1 {
                margin: 0;
                font-size: 24px;
            }

            .editor-header p {
                margin: 5px 0 0;
                color: #777;
                font-size: 14px;
            }

            .action-buttons {
                display: flex;
                gap: 8px;
            }

            .action-btn {
                border: none;
                padding: 10px 16px;
                border-radius: 7px;
                cursor: pointer;
                font-weight: bold;
                font-size: 14px;
            }

            .print-btn {
                background: #1d3557;
                color: white;
            }

            .save-btn {
    background: #198754;
    color: white;
}

.save-btn:hover {
    background: #157347;
}

            .clear-btn {
                background: #e9ecef;
                color: #333;
            }


            /* REPORT PAPER */

            .report-paper {
                background: white;
                max-width: 1000px;
                min-height: 1000px;
                margin: auto;
                padding: 55px 65px;
                box-shadow: 0 4px 20px rgba(0,0,0,.08);
            }


            /* REPORT HEADER */

            .report-header {
                text-align: center;
                line-height: 1.4;
            }

            .report-header .republic {
                font-weight: bold;
                font-size: 14px;
            }

            .report-header .province {
                font-size: 14px;
            }

            .report-header .municipality {
                font-weight: bold;
                font-size: 15px;
            }

            .report-header .office {
                font-weight: bold;
                font-size: 15px;
            }

            .report-header .location {
                font-size: 14px;
            }

            /*
            Logo placeholder.
            Exact official Naic seal can be inserted here later.
            */

            .naic-seal {
        width: 85px;
        height: 85px;
        object-fit: contain;
        display: block;
        margin: 0 auto 10px;
    }


            .report-title {
                margin-top: 25px;
                text-align: center;
                font-size: 19px;
                font-weight: bold;
                text-transform: uppercase;
            }


            /* INFO SECTION */

            .info-grid {
                margin-top: 30px;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px 25px;
            }

            .field {
                display: flex;
                flex-direction: column;
            }

            .field.full {
                grid-column: 1 / -1;
            }

            .field label {
                font-size: 12px;
                font-weight: bold;
                margin-bottom: 5px;
            }

            .field input,
            .field textarea,
            .field select {
                border: 1px solid #aaa;
                padding: 8px 9px;
                font-size: 13px;
                font-family: Arial, sans-serif;
                border-radius: 3px;
                background: white;
            }

            .field textarea {
                min-height: 65px;
                resize: vertical;
            }


            /* SECTION TITLE */

            .section-title {
                margin-top: 30px;
                margin-bottom: 10px;
                font-size: 14px;
                font-weight: bold;
                text-transform: uppercase;
            }


            /* TABLE */

            .report-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                font-size: 12px;
            }

            .report-table th,
            .report-table td {
                border: 1px solid #555;
                padding: 7px;
                vertical-align: top;
            }

            .report-table th {
                text-align: center;
                background: #f1f1f1;
                font-weight: bold;
            }

            .report-table input,
            .report-table textarea {
                width: 100%;
                border: none;
                outline: none;
                padding: 3px;
                font-size: 12px;
                font-family: Arial, sans-serif;
                background: transparent;
            }

            .report-table textarea {
                min-height: 45px;
                resize: vertical;
            }

            .delete-row {
                border: none;
                background: #dc3545;
                color: white;
                padding: 5px 7px;
                border-radius: 4px;
                cursor: pointer;
            }


            .wide-table-wrap {
                width: 100%;
                overflow-x: auto;
                margin-top: 10px;
            }

            .wide-report-table {
                min-width: 1900px;
                font-size: 10px;
            }

            .wide-report-table th,
            .wide-report-table td {
                min-width: 95px;
                padding: 5px;
            }

            /* ADD ROW */

            .add-row-container {
                margin-top: 10px;
                display: flex;
                justify-content: flex-end;
            }

            .add-row-btn {
                border: none;
                background: #198754;
                color: white;
                padding: 8px 13px;
                border-radius: 5px;
                cursor: pointer;
                font-weight: bold;
            }


            /* SIGNATURE */

            .signature-section {
                margin-top: 70px;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 80px;
            }

            .signature-box {
                text-align: center;
            }

            .signature-line {
                border-bottom: 1px solid #333;
                height: 45px;
                margin-bottom: 7px;
            }

            .signature-name {
                font-weight: bold;
                font-size: 13px;
            }

            .signature-position {
                font-size: 12px;
            }


            /* NOTICE */

            .template-note {
                max-width: 1000px;
                margin: 15px auto 0;
                background: #fff8e1;
                border: 1px solid #f0d98c;
                padding: 10px 13px;
                border-radius: 6px;
                font-size: 12px;
                color: #6b5700;
            }


            /* PRINT */

            @media print {

                body {
                    background: white;
                }

                .topbar,
                .editor-header,
                .template-note,
                .add-row-container,
                .delete-column {
                    display: none !important;
                }

                .page {
                    padding: 0;
                }

                .report-paper {
                    box-shadow: none;
                    max-width: none;
                    min-height: auto;
                    padding: 25px 35px;
                }

                input,
                textarea,
                select {
                    border: none !important;
                }

                .delete-row {
                    display: none !important;
                }

                @page {
                    size: A4;
                    margin: 10mm;
                }
            }


            @media(max-width: 800px) {

                .page {
                    padding: 15px;
                }

                .report-paper {
                    padding: 30px 20px;
                }

                .info-grid {
                    grid-template-columns: 1fr;
                }

                .field.full {
                    grid-column: auto;
                }

                .signature-section {
                    grid-template-columns: 1fr;
                    gap: 40px;
                }

                .editor-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 15px;
                }

                .topbar {
                    padding: 0 12px;
                }

                .logo-text {
                    font-size: 15px;
                }
            }

        </style>

    </head>

    <body>


    <!-- TOP BAR -->

    <div class="topbar">

        <div class="topbar-left">
            <div class="logo-text">
                SK KONEXT
            </div>
        </div>

        <div class="topbar-right">

            <a href="reports.php" class="top-btn back-btn">
                ← Back to Reports
            </a>

            <a href="dashboard.php" class="top-btn dashboard-btn">
                Dashboard
            </a>

        </div>

    </div>


    <div class="page">

        <div class="editor-container">


            <!-- EDITOR HEADER -->

            <div class="editor-header">

                <div>

                    <h1>
                        <?= htmlspecialchars($reportTitle) ?>
                    </h1>

                    <p>
                        <?= htmlspecialchars($reportCategory) ?>
                    </p>

                </div>


                <div class="action-buttons">

                    <button
                        type="button"
                        class="action-btn clear-btn"
                        onclick="clearForm()"
                    >
                        Clear
                    </button> 
                    
                   <button
    type="button"
    class="action-btn save-btn"
    onclick="saveReport()"
>
    Save HTML
</button>

                    

                    <button
                        type="button"
                        class="action-btn print-btn"
                        onclick="window.print()"
                    >
                        Print PDF
                    </button>

                </div>

            </div>


            <!-- REPORT PAPER -->

            <div class="report-paper" id="reportPaper">


                <!-- REPORT HEADER -->

                <div class="report-header">

                <img
        src="assets/naic-seal.png"
        alt="Municipality of Naic Seal"
        class="naic-seal"
    >


                    <div class="republic">
                        REPUBLIC OF THE PHILIPPINES
                    </div>

                    <div class="province">
                        PROVINCE OF CAVITE
                    </div>

                    <div class="municipality">
                        MUNICIPALITY OF NAIC
                    </div>

                    <div class="office">
                        SANGGUNIANG KABATAAN FEDERATION
                    </div>

                    <div class="location">
                        NAIC, CAVITE
                    </div>

                </div>


                <div class="report-title">
                    <?= htmlspecialchars($reportTitle) ?>
                </div>


                <!-- ===================================================== -->
                <!-- ACCOMPLISHMENT REPORTS -->
                <!-- ===================================================== -->

                <?php if (
                    in_array(
                        $template,
                        [
                            'monthly_accomplishment',
                            'quarterly_accomplishment',
                            'annual_accomplishment',
                            'ppa_accomplishment',
                            'federation_consolidated',
                            'federation_activities'
                        ]
                    )
                ): ?>


                    <div class="info-grid">


                        <div class="field">

                            <label>
                                Reporting Period
                            </label>

                            <input
                                type="text"
                                placeholder="e.g. January 2026"
                            >

                        </div>


                        <div class="field">

                            <label>
                                Date Prepared
                            </label>

                            <input
                                type="date"
                            >

                        </div>


                        <?php if ($template === 'federation_consolidated'): ?>

                            <div class="field full">

                                <label>
                                    Barangay / SK Unit
                                </label>

                                <input
                                    type="text"
                                    placeholder="Enter Barangay"
                                >

                            </div>

                        <?php elseif ($template === 'ppa_accomplishment'): ?>

                            <div class="field full">

                                <label>
                                    PPA Title
                                </label>

                                <input
                                    type="text"
                                    placeholder="Program / Project / Activity"
                                >

                            </div>

                        <?php endif; ?>


                    </div>


                    <?php if ($template === 'federation_activities'): ?>

                        <div class="section-title">
                            Federation Activities
                        </div>

                        <table class="report-table">

                            <thead>

                                <tr>

                                    <th style="width: 20%;">
                                        Activity
                                    </th>

                                    <th style="width: 12%;">
                                        Date
                                    </th>

                                    <th style="width: 15%;">
                                        Venue
                                    </th>

                                    <th style="width: 15%;">
                                        Participants
                                    </th>

                                    <th style="width: 12%;">
                                        Budget
                                    </th>

                                    <th>
                                        Accomplishment
                                    </th>

                                    <th class="delete-column">
                                        Action
                                    </th>

                                </tr>

                            </thead>

                            <tbody id="reportRows">

                                <tr>

                                    <td>
                                        <input type="text">
                                    </td>

                                    <td>
                                        <input type="date">
                                    </td>

                                    <td>
                                        <input type="text">
                                    </td>

                                    <td>
                                        <input type="text">
                                    </td>

                                    <td>
                                        <input
                                            type="number"
                                            step="0.01"
                                            placeholder="0.00"
                                        >
                                    </td>

                                    <td>
                                        <textarea></textarea>
                                    </td>

                                    <td class="delete-column">
                                        <button
                                            type="button"
                                            class="delete-row"
                                            onclick="deleteRow(this)"
                                        >
                                            X
                                        </button>
                                    </td>

                                </tr>

                            </tbody>

                        </table>


                    <?php elseif ($template === 'federation_consolidated'): ?>

                        <div class="section-title">
                            Consolidated Barangay Accomplishments
                        </div>

                        <table class="report-table">

                            <thead>

                                <tr>

                                    <th>Barangay</th>

                                    <th>PPA / Activity</th>

                                    <th>Date</th>

                                    <th>Status</th>

                                    <th>Budget</th>

                                    <th>Accomplishment / Output</th>

                                    <th>Remarks</th>

                                    <th class="delete-column">
                                        Action
                                    </th>

                                </tr>

                            </thead>

                            <tbody id="reportRows">

                                <tr>

                                    <td>
                                        <input type="text">
                                    </td>

                                    <td>
                                        <input type="text">
                                    </td>

                                    <td>
                                        <input type="date">
                                    </td>

                                    <td>
                                        <input type="text">
                                    </td>

                                    <td>
                                        <input
                                            type="number"
                                            step="0.01"
                                        >
                                    </td>

                                    <td>
                                        <textarea></textarea>
                                    </td>

                                    <td>
                                        <textarea></textarea>
                                    </td>

                                    <td class="delete-column">
                                        <button
                                            type="button"
                                            class="delete-row"
                                            onclick="deleteRow(this)"
                                        >
                                            X
                                        </button>
                                    </td>

                                </tr>

                            </tbody>

                        </table>


                    <?php else: ?>


                        <div class="section-title">
                            Accomplishment Details
                        </div>


                        <table class="report-table">

                            <thead>

                                <tr>

                                    <th style="width: 17%;">
                                        PPA / Activity
                                    </th>

                                    <th style="width: 11%;">
                                        Date
                                    </th>

                                    <th style="width: 11%;">
                                        Status
                                    </th>

                                    <th style="width: 12%;">
                                        Budget
                                    </th>

                                    <th style="width: 25%;">
                                        Accomplishment / Output
                                    </th>

                                    <th style="width: 15%;">
                                        Remarks
                                    </th>

                                    <th class="delete-column">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="reportRows">

                                <tr>

                                    <td>
                                        <input type="text">
                                    </td>

                                    <td>
                                        <input type="date">
                                    </td>

                                    <td>
                                        <input
                                            type="text"
                                            placeholder="Completed"
                                        >
                                    </td>

                                    <td>
                                        <input
                                            type="number"
                                            step="0.01"
                                            placeholder="0.00"
                                        >
                                    </td>

                                    <td>
                                        <textarea></textarea>
                                    </td>

                                    <td>
                                        <textarea></textarea>
                                    </td>

                                    <td class="delete-column">

                                        <button
                                            type="button"
                                            class="delete-row"
                                            onclick="deleteRow(this)"
                                        >
                                            X
                                        </button>

                                    </td>

                                </tr>

                            </tbody>

                        </table>


                    <?php endif; ?>


                    <div class="add-row-container">

                        <button
                            type="button"
                            class="add-row-btn"
                            onclick="addRow()"
                        >
                            + Add Row
                        </button>

                    </div>


                <!-- ===================================================== -->
                <!-- OFFICIAL SK FORMS -->
                <!-- ===================================================== -->

                <?php elseif ($template === 'quarterly_financial'): ?>

                    <div class="info-grid">
                        <div class="field">
                            <label>SK of Barangay</label>
                            <input type="text" placeholder="Enter Barangay">
                        </div>
                        <div class="field">
                            <label>SK Treasurer</label>
                            <input type="text" placeholder="Name of SK Treasurer">
                        </div>
                        <div class="field">
                            <label>City / Municipality</label>
                            <input type="text" value="NAIC">
                        </div>
                        <div class="field">
                            <label>Province</label>
                            <input type="text" value="CAVITE">
                        </div>
                        <div class="field">
                            <label>Fund</label>
                            <input type="text">
                        </div>
                        <div class="field">
                            <label>Quarter / Calendar Year</label>
                            <input type="text" placeholder="1st Quarter of CY 2026">
                        </div>
                        <div class="field">
                            <label>Sheet No.</label>
                            <input type="text">
                        </div>
                    </div>

                    <div class="section-title">Register of Cash in Bank and Other Related Financial Transactions</div>
                    <div class="wide-table-wrap">
                        <table class="report-table wide-report-table">
                            <thead>
                                <tr>
                                    <th rowspan="3">Date</th>
                                    <th rowspan="3">Reference</th>
                                    <th rowspan="3">Name of Payee</th>
                                    <th rowspan="3">Particulars</th>
                                    <th colspan="3">Cash in Bank</th>
                                    <th colspan="9">Breakdown of Withdrawals / Payments</th>
                                </tr>
                                <tr>
                                    <th rowspan="2">Deposit</th>
                                    <th rowspan="2">Withdrawal</th>
                                    <th rowspan="2">Balance</th>
                                    <th colspan="2">Maintenance and Other Operating Expenses</th>
                                    <th colspan="2">Capital Outlay</th>
                                    <th rowspan="2">Advances to SK Officials</th>
                                    <th rowspan="2">Other Advances</th>
                                    <th rowspan="2">Others</th>
                                    <th colspan="2">Withholding Tax</th>
                                </tr>
                                <tr>
                                    <th>Travelling Expenses</th>
                                    <th>Other MOOE</th>
                                    <th>Office Equipment</th>
                                    <th>Other Capital Outlay</th>
                                    <th>Tax Type 1</th>
                                    <th>Tax Type 2</th>
                                </tr>
                            </thead>
                            <tbody id="reportRows">
                                <?php for ($i = 0; $i < 8; $i++): ?>
                                <tr>
                                    <td><input type="date"></td>
                                    <td><input type="text" placeholder="Reference No."></td>
                                    <td><input type="text"></td>
                                    <td><textarea></textarea></td>
                                    <?php for ($j = 0; $j < 12; $j++): ?>
                                        <td><input type="number" step="0.01"></td>
                                    <?php endfor; ?>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="add-row-container">
                        <button type="button" class="add-row-btn" onclick="addRow()">+ Add Transaction</button>
                    </div>

                <?php elseif ($template === 'monthly_financial'): ?>

                    <div class="info-grid">
                        <div class="field">
                            <label>Barangay</label>
                            <input type="text" placeholder="Enter Barangay">
                        </div>
                        <div class="field">
                            <label>Month and Year</label>
                            <input type="text" placeholder="January 2026">
                        </div>
                        <div class="field">
                            <label>City / Municipality</label>
                            <input type="text" value="NAIC">
                        </div>
                        <div class="field">
                            <label>Province</label>
                            <input type="text" value="CAVITE">
                        </div>
                    </div>

                    <div class="section-title">Monthly Itemized List of Purchase Request (PR)</div>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>PR No.</th>
                                <th>Date of PR</th>
                                <th>Requested By (Name of SK Official and Position)</th>
                                <th>Total Estimated Amount (₱)</th>
                                <th class="delete-column">Action</th>
                            </tr>
                        </thead>
                        <tbody id="reportRows">
                            <?php for ($i = 1; $i <= 20; $i++): ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td><input type="text"></td>
                                <td><input type="date"></td>
                                <td><textarea></textarea></td>
                                <td><input type="number" step="0.01" class="pr-amount" oninput="calculatePRTotal()"></td>
                                <td class="delete-column"><button type="button" class="delete-row" onclick="deleteRow(this); calculatePRTotal();">X</button></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4">Total Amount of Purchase Requests for the Month</th>
                                <th><input type="number" id="monthlyPRTotal" readonly></th>
                                <th class="delete-column"></th>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="add-row-container">
                        <button type="button" class="add-row-btn" onclick="addRow();">+ Add Purchase Request</button>
                    </div>

                <?php elseif ($template === 'annual_financial'): ?>

                    <div class="section-title">PURCHASE REQUEST</div>
                    <div class="info-grid">
                        <div class="field">
                            <label>SK of Barangay</label>
                            <input type="text" placeholder="Enter Barangay">
                        </div>
                        <div class="field">
                            <label>PR No.</label>
                            <input type="text">
                        </div>
                        <div class="field">
                            <label>City / Municipality</label>
                            <input type="text" value="NAIC">
                        </div>
                        <div class="field">
                            <label>Date</label>
                            <input type="date">
                        </div>
                        <div class="field">
                            <label>Province</label>
                            <input type="text" value="CAVITE">
                        </div>
                    </div>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Item No.</th>
                                <th>Qty.</th>
                                <th>Unit of Measurement</th>
                                <th>Item Description</th>
                                <th>Estimated Unit Cost (₱)</th>
                                <th>Estimated Amount (₱)</th>
                                <th class="delete-column">Action</th>
                            </tr>
                        </thead>
                        <tbody id="reportRows">
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td><input type="number" step="0.01"></td>
                                <td><input type="text"></td>
                                <td><textarea></textarea></td>
                                <td><input type="number" step="0.01"></td>
                                <td><input type="number" step="0.01"></td>
                                <td class="delete-column"><button type="button" class="delete-row" onclick="deleteRow(this)">X</button></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                    <div class="add-row-container">
                        <button type="button" class="add-row-btn" onclick="addRow()">+ Add Item</button>
                    </div>
                    <div class="info-grid">
                        <div class="field full">
                            <label>Total Estimated Amount (₱)</label>
                            <input type="number" step="0.01">
                        </div>
                        <div class="field full">
                            <label>Purpose</label>
                            <textarea placeholder="Enter purpose of purchase"></textarea>
                        </div>
                    </div>

                <?php elseif ($template === 'budget_utilization'): ?>

                    <div class="section-title">SANGGUNIANG KABATAAN FULL PUBLIC DISCLOSURE POLICY BOARD</div>
                    <div class="info-grid">
                        <div class="field"><label>Barangay</label><input type="text" placeholder="Enter Barangay"></div>
                        <div class="field"><label>Calendar Year</label><input type="text" placeholder="2026"></div>
                        <div class="field"><label>CBYDP CY</label><input type="text" placeholder="2024 - 2026"></div>
                        <div class="field"><label>ABYIP CY</label><input type="text" placeholder="2026"></div>
                    </div>
                    <div class="section-title">Required Public Documents</div>
                    <table class="report-table">
                        <thead>
                            <tr><th>Document</th><th>Coverage / Period</th><th>Date Posted</th><th>Status / Remarks</th></tr>
                        </thead>
                        <tbody id="reportRows">
                            <?php
                            $disclosureDocuments = [
                                'Annual Barangay Youth Investment Program (ABYIP)',
                                'Annual Budget',
                                'Quarterly Register of Cash in Bank (RCB) and Other Related Financial Transactions',
                                'Monthly Itemized List of Purchase Request (PR)',
                                'Annual Accomplishment Report'
                            ];
                            foreach ($disclosureDocuments as $document):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($document) ?></td>
                                <td><input type="text" placeholder="Coverage / Period"></td>
                                <td><input type="date"></td>
                                <td><input type="text" placeholder="Posted / Pending"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php elseif ($template === 'receipts_payments'): ?>

                    <div class="info-grid">
                        <div class="field"><label>Barangay</label><input type="text" placeholder="Enter Barangay"></div>
                        <div class="field"><label>Region</label><input type="text" value="IV-A"></div>
                        <div class="field"><label>Province</label><input type="text" value="Cavite"></div>
                        <div class="field"><label>City / Municipality</label><input type="text" value="Naic"></div>
                        <div class="field"><label>CBYDP Period</label><input type="text" placeholder="2024 - 2026"></div>
                    </div>
                    <div class="section-title">Comprehensive Barangay Youth Development Plan (CBYDP)</div>
                    <div class="info-grid">
                        <div class="field full"><label>Center of Participation</label><input type="text" placeholder="Enter Center of Participation"></div>
                        <div class="field full"><label>Agenda Statement</label><textarea placeholder="Enter agenda statement"></textarea></div>
                    </div>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Youth Development Concern</th>
                                <th>Objective</th>
                                <th>Performance Indicator</th>
                                <th>Target Year 1</th>
                                <th>Target Year 2</th>
                                <th>Target Year 3</th>
                                <th>PPAs</th>
                                <th>Budget (₱)</th>
                                <th>Person Responsible</th>
                                <th class="delete-column">Action</th>
                            </tr>
                        </thead>
                        <tbody id="reportRows">
                            <tr>
                                <td><textarea></textarea></td>
                                <td><textarea></textarea></td>
                                <td><textarea></textarea></td>
                                <td><input type="text"></td>
                                <td><input type="text"></td>
                                <td><input type="text"></td>
                                <td><textarea></textarea></td>
                                <td><input type="number" step="0.01"></td>
                                <td><input type="text"></td>
                                <td class="delete-column"><button type="button" class="delete-row" onclick="deleteRow(this)">X</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="add-row-container">
                        <button type="button" class="add-row-btn" onclick="addRow()">+ Add Development Plan Item</button>
                    </div>

                <!-- ===================================================== -->
                <!-- FEDERATION MEETINGS -->
                <!-- ===================================================== -->

                <?php elseif ($template === 'federation_meetings'): ?>


                    <div class="info-grid">

                        <div class="field">

                            <label>
                                Reporting Period
                            </label>

                            <input
                                type="text"
                                placeholder="e.g. January 2026"
                            >

                        </div>


                        <div class="field">

                            <label>
                                Date Prepared
                            </label>

                            <input type="date">

                        </div>

                    </div>


                    <div class="section-title">
                        Federation Meeting Details
                    </div>


                    <table class="report-table">

                        <thead>

                            <tr>

                                <th>
                                    Meeting Date
                                </th>

                                <th>
                                    Venue
                                </th>

                                <th>
                                    Agenda
                                </th>

                                <th>
                                    Discussion / Action Taken
                                </th>

                                <th>
                                    Attendees
                                </th>

                                <th class="delete-column">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody id="reportRows">

                            <tr>

                                <td>
                                    <input type="date">
                                </td>

                                <td>
                                    <input type="text">
                                </td>

                                <td>
                                    <textarea></textarea>
                                </td>

                                <td>
                                    <textarea></textarea>
                                </td>

                                <td>
                                    <textarea></textarea>
                                </td>

                                <td class="delete-column">

                                    <button
                                        type="button"
                                        class="delete-row"
                                        onclick="deleteRow(this)"
                                    >
                                        X
                                    </button>

                                </td>

                            </tr>

                        </tbody>

                    </table>


                    <div class="add-row-container">

                        <button
                            type="button"
                            class="add-row-btn"
                            onclick="addMeetingRow()"
                        >
                            + Add Meeting
                        </button>

                    </div>


                <?php endif; ?>


                <!-- SIGNATURE -->

                <div class="signature-section">

                    <div class="signature-box">

                        <div class="signature-line"></div>

                        <div class="signature-name">
                            Prepared by:
                        </div>

                        <div class="signature-name">
                            Darlaine Mae A. Benesen
                        </div>

                        <div class="signature-position">
                            SK Federation President
                        </div>

                    </div>


                    <div class="signature-box">

                        <div class="signature-line"></div>

                        <div class="signature-name">
                            Noted by:
                        </div>

                        <div class="signature-name">
                            ______________________________
                        </div>

                        <div class="signature-position">
                            Authorized Official
                        </div>

                    </div>

                </div>


            </div>


            <div class="template-note">

                <strong>Note:</strong>
                This is the initial report template. You can fill out the fields,
                add rows, then use <strong>Print / Save PDF</strong> to create the report.

            </div>


        </div>

    </div>


    <script>

    /*
    |--------------------------------------------------------------------------
    | COMMON
    |--------------------------------------------------------------------------
    */

    function deleteRow(button) {

        const row = button.closest("tr");

        if (!row) {
            return;
        }

        const tbody = row.parentElement;

        if (tbody.rows.length > 1) {
            row.remove();
        } else {
            alert("At least one row is required.");
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ACCOMPLISHMENT / GENERAL ROW
    |--------------------------------------------------------------------------
    */

    function addRow() {

        const tbody = document.getElementById("reportRows");

        if (!tbody) {
            return;
        }

        const firstRow = tbody.rows[0];

        if (!firstRow) {
            return;
        }

        const newRow = firstRow.cloneNode(true);

        newRow.querySelectorAll("input, textarea").forEach(function(element) {

            element.value = "";

            if (element.type === "number") {
                element.value = "0";
            }

        });

        tbody.appendChild(newRow);
    }


    /*
    |--------------------------------------------------------------------------
    | FINANCIAL ROW
    |--------------------------------------------------------------------------
    */

    function addFinancialRow() {

        const tbody = document.getElementById("reportRows");

        if (!tbody) {
            return;
        }

        const row = document.createElement("tr");

        row.innerHTML = `

            <td>
                <input
                    type="date"
                    onchange="calculateBalance()"
                >
            </td>

            <td>
                <textarea></textarea>
            </td>

            <td>
                <input
                    type="number"
                    class="receipt"
                    step="0.01"
                    value="0"
                    oninput="calculateBalance()"
                >
            </td>

            <td>
                <input
                    type="number"
                    class="payment"
                    step="0.01"
                    value="0"
                    oninput="calculateBalance()"
                >
            </td>

            <td>
                <input
                    type="number"
                    class="balance"
                    step="0.01"
                    value="0"
                    readonly
                >
            </td>

            <td class="delete-column">

                <button
                    type="button"
                    class="delete-row"
                    onclick="deleteRow(this); calculateBalance();"
                >
                    X
                </button>

            </td>

        `;

        tbody.appendChild(row);

        calculateBalance();
    }


    /*
    |--------------------------------------------------------------------------
    | FINANCIAL BALANCE
    |--------------------------------------------------------------------------
    */

    function calculateBalance() {

        const beginning = parseFloat(
            document.getElementById("beginningCash")?.value
        ) || 0;

        let balance = beginning;

        const rows = document.querySelectorAll("#reportRows tr");

        rows.forEach(function(row) {

            const receipt =
                parseFloat(row.querySelector(".receipt")?.value) || 0;

            const payment =
                parseFloat(row.querySelector(".payment")?.value) || 0;

            balance = balance + receipt - payment;

            const balanceInput =
                row.querySelector(".balance");

            if (balanceInput) {
                balanceInput.value = balance.toFixed(2);
            }

        });

        const ending =
            document.getElementById("endingCash");

        if (ending) {
            ending.value = balance.toFixed(2);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | BUDGET UTILIZATION
    |--------------------------------------------------------------------------
    */

    function calculateBudget() {

        const approved =
            parseFloat(
                document.getElementById("approvedBudget")?.value
            ) || 0;

        const utilized =
            parseFloat(
                document.getElementById("utilizedBudget")?.value
            ) || 0;

        const remaining =
            document.getElementById("remainingBudget");

        if (remaining) {
            remaining.value =
                (approved - utilized).toFixed(2);
        }
    }


    function addBudgetRow() {

        const tbody =
            document.getElementById("reportRows");

        if (!tbody) {
            return;
        }

        const row = document.createElement("tr");

        row.innerHTML = `

            <td>
                <input type="text">
            </td>

            <td>
                <input
                    type="number"
                    class="budget-detail"
                    step="0.01"
                    value="0"
                    oninput="calculateBudgetDetails()"
                >
            </td>

            <td>
                <input
                    type="number"
                    class="utilized-detail"
                    step="0.01"
                    value="0"
                    oninput="calculateBudgetDetails()"
                >
            </td>

            <td>
                <input
                    type="number"
                    class="remaining-detail"
                    step="0.01"
                    value="0"
                    readonly
                >
            </td>

            <td>
                <textarea></textarea>
            </td>

            <td class="delete-column">

                <button
                    type="button"
                    class="delete-row"
                    onclick="deleteRow(this); calculateBudgetDetails();"
                >
                    X
                </button>

            </td>

        `;

        tbody.appendChild(row);

        calculateBudgetDetails();
    }


    function calculateBudgetDetails() {

        const rows =
            document.querySelectorAll(
                ".budget-detail"
            );

        rows.forEach(function(input) {

            const row = input.closest("tr");

            const budget =
                parseFloat(
                    row.querySelector(".budget-detail")?.value
                ) || 0;

            const utilized =
                parseFloat(
                    row.querySelector(".utilized-detail")?.value
                ) || 0;

            const remaining =
                row.querySelector(".remaining-detail");

            if (remaining) {
                remaining.value =
                    (budget - utilized).toFixed(2);
            }

        });

    }


    /*
    |--------------------------------------------------------------------------
    | RECEIPTS AND PAYMENTS
    |--------------------------------------------------------------------------
    */

    function addRPRow() {

        const tbody =
            document.getElementById("reportRows");

        if (!tbody) {
            return;
        }

        const row = document.createElement("tr");

        row.innerHTML = `

            <td>
                <input
                    type="date"
                    onchange="calculateRP()"
                >
            </td>

            <td>
                <textarea></textarea>
            </td>

            <td>
                <input
                    type="number"
                    class="rpReceipt"
                    value="0"
                    step="0.01"
                    oninput="calculateRP()"
                >
            </td>

            <td>
                <input
                    type="number"
                    class="rpPayment"
                    value="0"
                    step="0.01"
                    oninput="calculateRP()"
                >
            </td>

            <td>
                <input
                    type="number"
                    class="rpBalance"
                    value="0"
                    step="0.01"
                    readonly
                >
            </td>

            <td class="delete-column">

                <button
                    type="button"
                    class="delete-row"
                    onclick="deleteRow(this); calculateRP();"
                >
                    X
                </button>

            </td>

        `;

        tbody.appendChild(row);

        calculateRP();
    }


    function calculateRP() {

        const beginning =
            parseFloat(
                document.getElementById("rpBeginning")?.value
            ) || 0;

        let balance = beginning;

        const rows =
            document.querySelectorAll("#reportRows tr");

        rows.forEach(function(row) {

            const receipt =
                parseFloat(
                    row.querySelector(".rpReceipt")?.value
                ) || 0;

            const payment =
                parseFloat(
                    row.querySelector(".rpPayment")?.value
                ) || 0;

            balance =
                balance + receipt - payment;

            const balanceInput =
                row.querySelector(".rpBalance");

            if (balanceInput) {
                balanceInput.value =
                    balance.toFixed(2);
            }

        });

        const ending =
            document.getElementById("rpEnding");

        if (ending) {
            ending.value =
                balance.toFixed(2);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | MEETING ROW
    |--------------------------------------------------------------------------
    */

    function addMeetingRow() {

        const tbody =
            document.getElementById("reportRows");

        if (!tbody) {
            return;
        }

        const row = document.createElement("tr");

        row.innerHTML = `

            <td>
                <input type="date">
            </td>

            <td>
                <input type="text">
            </td>

            <td>
                <textarea></textarea>
            </td>

            <td>
                <textarea></textarea>
            </td>

            <td>
                <textarea></textarea>
            </td>

            <td class="delete-column">

                <button
                    type="button"
                    class="delete-row"
                    onclick="deleteRow(this)"
                >
                    X
                </button>

            </td>

        `;

        tbody.appendChild(row);
    }


    function calculatePRTotal() {
        let total = 0;
        document.querySelectorAll(".pr-amount").forEach(function(input) {
            total += parseFloat(input.value) || 0;
        });
        const output = document.getElementById("monthlyPRTotal");
        if (output) output.value = total.toFixed(2);
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAR FORM
    |--------------------------------------------------------------------------
    */

    function clearForm() {

        const confirmation =
            confirm(
                "Clear all entered information in this report?"
            );

        if (!confirmation) {
            return;
        }

        document
            .querySelectorAll(
                "#reportPaper input, #reportPaper textarea, #reportPaper select"
            )
            .forEach(function(element) {

                if (
                    element.type === "number"
                ) {
                    element.value = "0";
                } else {
                    element.value = "";
                }

            });

        calculateBalance();
        calculateBudget();
        calculateBudgetDetails();
        calculateRP();
    }

    /*
|--------------------------------------------------------------------------
| SAVE REPORT AS HTML FILE
|--------------------------------------------------------------------------
*/

function saveReport() {
    const reportPaper = document.getElementById("reportPaper");

    if (!reportPaper) {
        alert("Report not found.");
        return;
    }

    // Copy the report without changing the original page.
    const reportClone = reportPaper.cloneNode(true);

    // Preserve the current values of inputs, textareas, and selects.
    const originalFields = reportPaper.querySelectorAll(
        "input, textarea, select"
    );

    const clonedFields = reportClone.querySelectorAll(
        "input, textarea, select"
    );

    originalFields.forEach(function (field, index) {
        const copy = clonedFields[index];

        if (!copy) return;

        if (field.tagName === "TEXTAREA") {
            copy.textContent = field.value;
        } else if (field.tagName === "SELECT") {
            Array.from(copy.options).forEach(function (option) {
                option.selected = option.value === field.value;
            });
        } else {
            copy.setAttribute("value", field.value);
        }
    });

    // Remove editing controls from the downloaded report.
    reportClone.querySelectorAll(
        ".delete-column, .add-row-container, .delete-row"
    ).forEach(function (element) {
        element.remove();
    });

    // Display field values without editable input boxes.
    reportClone.querySelectorAll("input").forEach(function (input) {
        const value = input.value || "";

        const span = document.createElement("span");
        span.textContent = value;
        span.style.whiteSpace = "pre-wrap";

        input.replaceWith(span);
    });

    reportClone.querySelectorAll("textarea").forEach(function (textarea) {
        const value = textarea.value || "";

        const span = document.createElement("span");
        span.textContent = value;
        span.style.whiteSpace = "pre-wrap";
        span.style.display = "block";
        span.style.minHeight = "1em";

        textarea.replaceWith(span);
    });

    reportClone.querySelectorAll("select").forEach(function (select) {
        const span = document.createElement("span");
        span.textContent =
            select.options[select.selectedIndex]?.text || "";

        select.replaceWith(span);
    });

    // Get the existing CSS so the downloaded file keeps the report design.
    const styles = Array.from(document.querySelectorAll("style"))
        .map(function (style) {
            return style.textContent;
        })
        .join("\n");

    // Embed the Naic seal so it can appear in the saved HTML file.
    const seal = reportClone.querySelector(".naic-seal");

    function downloadReport(sealData = null) {
        if (seal && sealData) {
            seal.src = sealData;
        }

        const reportTitle = <?= json_encode($reportTitle) ?>;
        const templateKey = <?= json_encode($template) ?>;

        const standaloneCSS = `
            ${styles}

            body {
                background: white;
                margin: 0;
                padding: 20px;
            }

            .report-paper {
                box-shadow: none;
                max-width: 1000px;
                min-height: auto;
                margin: auto;
                padding: 35px;
            }

            input, textarea, select {
                border: none !important;
                background: transparent !important;
                color: #222 !important;
                font: inherit;
            }

            .signature-section {
                break-inside: avoid;
            }

            @media print {
                body {
                    padding: 0;
                }

                .report-paper {
                    padding: 25px 35px;
                }
            }
        `;

        const html = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${escapeHTML(reportTitle)}</title>
    <style>${standaloneCSS}</style>
</head>
<body>
    ${reportClone.outerHTML}
</body>
</html>`;

        const blob = new Blob(
            [html],
            { type: "text/html;charset=utf-8" }
        );

        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");

        const date = new Date().toISOString().slice(0, 10);
        link.href = url;
        link.download = `${templateKey}_${date}.html`;

        document.body.appendChild(link);
        link.click();
        link.remove();

        URL.revokeObjectURL(url);

        alert(
            "Report saved as an HTML file. Check your Downloads folder."
        );
    }

    if (seal && seal.getAttribute("src")) {
        const image = new Image();

        image.onload = function () {
            try {
                const canvas = document.createElement("canvas");
                canvas.width = image.naturalWidth;
                canvas.height = image.naturalHeight;

                const context = canvas.getContext("2d");
                context.drawImage(image, 0, 0);

                downloadReport(canvas.toDataURL("image/png"));
            } catch (error) {
                // Save the report even if the seal cannot be embedded.
                downloadReport();
            }
        };

        image.onerror = function () {
            downloadReport();
        };

        image.src = seal.src;
    } else {
        downloadReport();
    }
}

function escapeHTML(value) {
    return String(value).replace(/[&<>"']/g, function (character) {
        const entities = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        };

        return entities[character];
    });
}

    </script>


    </body>
    </html>