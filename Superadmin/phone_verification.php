
<?php

include("../db.php");
include("../includes/auth.php");

allowRoles(['superadmin']);

/* =========================================
   ADMIN SESSION
========================================= */

$admin_id = (int)$_SESSION['user_id'];

/* =========================================
   CSRF TOKEN
========================================= */

if (empty($_SESSION['phone_verification_admin_csrf'])) {
    $_SESSION['phone_verification_admin_csrf'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['phone_verification_admin_csrf'];

$message = "";
$message_type = "";

/* =========================================
   APPROVE / REJECT REQUEST
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $request_id = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $remarks = trim($_POST['admin_remarks'] ?? '');
    $submitted_token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrf_token, $submitted_token)) {

        $message = "Invalid security token. Please refresh the page.";
        $message_type = "error";

    } elseif ($request_id <= 0 || !in_array($action, ['approve', 'reject'], true)) {

        $message = "Invalid request.";
        $message_type = "error";

    } else {

        mysqli_begin_transaction($conn);

        try {

            /* GET PENDING REQUEST */

            $stmt = mysqli_prepare($conn, "
                SELECT
                    id,
                    applicant_id,
                    phone_number,
                    status
                FROM phone_verification_requests
                WHERE id = ?
                LIMIT 1
                FOR UPDATE
            ");

            mysqli_stmt_bind_param($stmt, "i", $request_id);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $request = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if (!$request || $request['status'] !== 'Pending') {
                throw new Exception("This request is no longer pending.");
            }

            $applicant_id = (int)$request['applicant_id'];
            $phone_number = $request['phone_number'];

            /* GET APPLICANT */

            $stmt = mysqli_prepare($conn, "
                SELECT id, phone, phone_verified
                FROM applicant_accounts
                WHERE id = ?
                LIMIT 1
                FOR UPDATE
            ");

            mysqli_stmt_bind_param($stmt, "i", $applicant_id);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $applicant = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if (!$applicant) {
                throw new Exception("Applicant account not found.");
            }

            if ($action === 'approve') {

                /* CHECK IF NUMBER IS ALREADY VERIFIED
                   BY ANOTHER APPLICANT
                */

                $stmt = mysqli_prepare($conn, "
                    SELECT id
                    FROM applicant_accounts
                    WHERE phone = ?
                    AND phone_verified = 1
                    AND id != ?
                    LIMIT 1
                ");

                mysqli_stmt_bind_param(
                    $stmt,
                    "si",
                    $phone_number,
                    $applicant_id
                );

                mysqli_stmt_execute($stmt);

                $duplicate_result = mysqli_stmt_get_result($stmt);
                $duplicate = mysqli_fetch_assoc($duplicate_result);

                mysqli_stmt_close($stmt);

                if ($duplicate) {
                    throw new Exception(
                        "This mobile number is already verified under another applicant."
                    );
                }

                /* MAKE SURE REQUEST NUMBER MATCHES ACCOUNT */

                if ($applicant['phone'] !== $phone_number) {
                    throw new Exception(
                        "The requested phone number does not match the applicant's registered number."
                    );
                }

                /* VERIFY APPLICANT PHONE */

                $stmt = mysqli_prepare($conn, "
                    UPDATE applicant_accounts
                    SET
                        phone_verified = 1,
                        phone_verified_at = NOW()
                    WHERE id = ?
                ");

                mysqli_stmt_bind_param($stmt, "i", $applicant_id);

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Unable to verify applicant number.");
                }

                mysqli_stmt_close($stmt);

                $new_status = 'Approved';

            } else {

                $new_status = 'Rejected';

            }

            /* UPDATE REQUEST RECORD */

            $stmt = mysqli_prepare($conn, "
                UPDATE phone_verification_requests
                SET
                    status = ?,
                    reviewed_by = ?,
                    reviewed_at = NOW(),
                    admin_remarks = ?
                WHERE id = ?
                AND status = 'Pending'
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "sisi",
                $new_status,
                $admin_id,
                $remarks,
                $request_id
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Unable to update verification request.");
            }

            if (mysqli_stmt_affected_rows($stmt) !== 1) {
                throw new Exception("The request was already processed.");
            }

            mysqli_stmt_close($stmt);

            mysqli_commit($conn);

            if ($action === 'approve') {
                $message = "Mobile number successfully verified.";
            } else {
                $message = "Phone verification request rejected.";
            }

            $message_type = "success";

        } catch (Exception $e) {

            mysqli_rollback($conn);

            $message = $e->getMessage();
            $message_type = "error";

        }
    }
}

/* =========================================
   REQUEST COUNTS
========================================= */

$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

$count_query = mysqli_query($conn, "
    SELECT
        status,
        COUNT(*) AS total
    FROM phone_verification_requests
    GROUP BY status
");

if ($count_query) {

    while ($count = mysqli_fetch_assoc($count_query)) {

        if ($count['status'] === 'Pending') {
            $pending_count = (int)$count['total'];
        }

        if ($count['status'] === 'Approved') {
            $approved_count = (int)$count['total'];
        }

        if ($count['status'] === 'Rejected') {
            $rejected_count = (int)$count['total'];
        }
    }
}

/* =========================================
   GET ALL VERIFICATION REQUESTS
========================================= */

$requests = mysqli_query($conn, "
    SELECT
        pvr.*,
        aa.fullname,
        aa.email,
        aa.phone AS registered_phone
    FROM phone_verification_requests AS pvr
    LEFT JOIN applicant_accounts AS aa
        ON aa.id = pvr.applicant_id
    ORDER BY
        CASE
            WHEN pvr.status = 'Pending' THEN 0
            ELSE 1
        END,
        pvr.requested_at DESC
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Phone Verification | SK KONEXT</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap"
rel="stylesheet">

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 30px;
    background: #071a12;
    color: #e8f2ec;
    font-family: 'DM Sans', sans-serif;
}

.page-container {
    max-width: 1250px;
    margin: auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.page-title h2 {
    font-size: 25px;
    font-weight: 800;
    margin-bottom: 6px;
}

.page-title p {
    color: #91a99a;
    font-size: 13px;
    margin: 0;
}

.back-button {
    background: #183d2b;
    color: #d9eee0;
    padding: 11px 17px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
}

.back-button:hover {
    background: #24563c;
    color: white;
    text-decoration: none;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 22px;
}

.stat-card {
    background: #10291d;
    border: 1px solid #244a35;
    padding: 20px;
    border-radius: 14px;
}

.stat-card .stat-label {
    color: #9bb3a4;
    font-size: 12px;
    font-weight: 600;
}

.stat-card .stat-number {
    font-size: 28px;
    font-weight: 800;
    margin-top: 8px;
}

.stat-card i {
    float: right;
    font-size: 22px;
    color: #64d99a;
}

.panel {
    background: #10251b;
    border: 1px solid #244735;
    border-radius: 15px;
    overflow: hidden;
}

.panel-header {
    padding: 20px;
    border-bottom: 1px solid #244735;
}

.panel-header h4 {
    font-size: 16px;
    font-weight: 800;
    margin: 0;
}

.panel-header p {
    color: #8da698;
    font-size: 12px;
    margin: 6px 0 0;
}

.alert-message {
    padding: 13px 16px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: 13px;
    font-weight: 600;
}

.alert-success-custom {
    background: #123d29;
    border: 1px solid #27794c;
    color: #8cf0b5;
}

.alert-error-custom {
    background: #481e23;
    border: 1px solid #873d46;
    color: #ffadb5;
}

.table-responsive {
    padding: 15px;
}

table {
    width: 100%;
    min-width: 950px;
    border-collapse: collapse;
}

thead th {
    background: #183629;
    color: #a9c4b4;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .5px;
    padding: 14px 12px;
    border: none;
}

tbody td {
    padding: 15px 12px;
    border-bottom: 1px solid #244333;
    font-size: 12px;
    color: #dce9e0;
    vertical-align: middle;
}

tbody tr:hover {
    background: #153326;
}

.applicant-name {
    font-weight: 700;
    color: #f0f7f2;
}

.applicant-email {
    color: #8fa99a;
    font-size: 11px;
    margin-top: 4px;
}

.phone-number {
    font-weight: 700;
    color: #8ce5ae;
}

.status-badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 800;
}

.status-pending {
    background: #4a3918;
    color: #ffd77b;
}

.status-approved {
    background: #17472d;
    color: #8cf0b5;
}

.status-rejected {
    background: #51242a;
    color: #ffadb5;
}

.action-form {
    display: flex;
    flex-direction: column;
    gap: 7px;
    min-width: 145px;
}

.remarks-input {
    width: 100%;
    background: #091b12;
    border: 1px solid #31533e;
    color: white;
    padding: 8px;
    border-radius: 7px;
    font-size: 11px;
    outline: none;
}

.remarks-input:focus {
    border-color: #54d88d;
}

.btn-action {
    border: none;
    border-radius: 7px;
    padding: 8px 10px;
    font-size: 11px;
    font-weight: 800;
    cursor: pointer;
}

.btn-approve {
    background: #1c7544;
    color: white;
}

.btn-approve:hover {
    background: #269458;
}

.btn-reject {
    background: #87343d;
    color: white;
}

.btn-reject:hover {
    background: #a6424d;
}

.reviewed-text {
    color: #8ba596;
    font-size: 11px;
}

.empty-state {
    text-align: center;
    padding: 45px 15px;
    color: #8ba596;
}

.empty-state i {
    display: block;
    font-size: 30px;
    margin-bottom: 12px;
    color: #4d765d;
}

@media(max-width: 700px) {

    body {
        padding: 15px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .page-title h2 {
        font-size: 21px;
    }

}

</style>

</head>

<body>

<div class="page-container">

    <div class="page-header">

        <div class="page-title">

            <h2>
                <i class="fa-solid fa-mobile-screen-button"
                style="color:#64d99a;"></i>
                Phone Verification
            </h2>

            <p>
                Review and manage applicant mobile number verification requests.
            </p>

        </div>

        <a href="dashboard.php" class="back-button">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Dashboard
        </a>

    </div>


    <?php if($message !== ""){ ?>

        <div class="alert-message <?= $message_type === 'success' ? 'alert-success-custom' : 'alert-error-custom' ?>">

            <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>

            <?= htmlspecialchars($message) ?>

        </div>

    <?php } ?>


    <div class="stats-grid">

        <div class="stat-card">

            <i class="fa-solid fa-clock"></i>

            <div class="stat-label">
                Pending Requests
            </div>

            <div class="stat-number">
                <?= $pending_count ?>
            </div>

        </div>


        <div class="stat-card">

            <i class="fa-solid fa-circle-check"></i>

            <div class="stat-label">
                Approved Requests
            </div>

            <div class="stat-number">
                <?= $approved_count ?>
            </div>

        </div>


        <div class="stat-card">

            <i class="fa-solid fa-circle-xmark"></i>

            <div class="stat-label">
                Rejected Requests
            </div>

            <div class="stat-number">
                <?= $rejected_count ?>
            </div>

        </div>

    </div>


    <div class="panel">

        <div class="panel-header">

            <h4>
                <i class="fa-solid fa-list-check"
                style="color:#64d99a;"></i>
                Verification Requests
            </h4>

            <p>
                Pending requests appear first. Only pending requests can be processed.
            </p>

        </div>


        <div class="table-responsive">

            <table>

                <thead>

                    <tr>
                        <th>Applicant</th>
                        <th>Mobile Number</th>
                        <th>Request Date</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>

                </thead>


                <tbody>

                <?php if($requests && mysqli_num_rows($requests) > 0){ ?>

                    <?php while($row = mysqli_fetch_assoc($requests)){ ?>

                        <tr>

                            <td>

                                <div class="applicant-name">
                                    <?= htmlspecialchars($row['fullname'] ?? 'Unknown Applicant') ?>
                                </div>

                                <div class="applicant-email">
                                    <?= htmlspecialchars($row['email'] ?? '') ?>
                                </div>

                            </td>


                            <td>

                                <span class="phone-number">
                                    <?= htmlspecialchars($row['phone_number']) ?>
                                </span>

                            </td>


                            <td>
                                <?= date('M d, Y h:i A', strtotime($row['requested_at'])) ?>
                            </td>


                            <td>

                                <?php if($row['status'] === 'Pending'){ ?>

                                    <span class="status-badge status-pending">
                                        Pending
                                    </span>

                                <?php } elseif($row['status'] === 'Approved'){ ?>

                                    <span class="status-badge status-approved">
                                        Approved
                                    </span>

                                <?php } else { ?>

                                    <span class="status-badge status-rejected">
                                        Rejected
                                    </span>

                                <?php } ?>

                            </td>


                            <td>

                                <?= !empty($row['admin_remarks'])
                                    ? htmlspecialchars($row['admin_remarks'])
                                    : '<span style="color:#718b7b;">—</span>' ?>

                            </td>


                            <td>

                                <?php if($row['status'] === 'Pending'){ ?>

                                    <form method="POST" class="action-form">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= htmlspecialchars($csrf_token) ?>">

                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?= (int)$row['id'] ?>">

                                        <input
                                            type="text"
                                            name="admin_remarks"
                                            class="remarks-input"
                                            placeholder="Optional remarks">

                                        <button
                                            type="submit"
                                            name="action"
                                            value="approve"
                                            class="btn-action btn-approve"
                                            onclick="return confirm('Approve and verify this mobile number?');">

                                            <i class="fa-solid fa-check"></i>
                                            Approve

                                        </button>

                                        <button
                                            type="submit"
                                            name="action"
                                            value="reject"
                                            class="btn-action btn-reject"
                                            onclick="return confirm('Reject this phone verification request?');">

                                            <i class="fa-solid fa-xmark"></i>
                                            Reject

                                        </button>

                                    </form>

                                <?php } else { ?>

                                    <span class="reviewed-text">
                                        <i class="fa-solid fa-lock"></i>
                                        Already Reviewed
                                    </span>

                                <?php } ?>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>

                        <td colspan="6">

                            <div class="empty-state">

                                <i class="fa-regular fa-folder-open"></i>

                                No phone verification requests found.

                            </div>

                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>
