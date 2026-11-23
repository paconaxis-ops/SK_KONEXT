
<?php
include("../db.php");
include("../includes/auth.php");

allowRoles(['superadmin']);

$barangay_id = isset($_GET['barangay_id']) ? (int)$_GET['barangay_id'] : 0;

if ($barangay_id <= 0) {
    header("Location: residents.php");
    exit();
}

/* GET BARANGAY */
$stmt = mysqli_prepare($conn, "
    SELECT id, name
    FROM barangays
    WHERE id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $barangay_id);
mysqli_stmt_execute($stmt);
$barangay = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$barangay) {
    header("Location: residents.php");
    exit();
}

/* SEARCH AND SORT */
$search = trim($_GET['search'] ?? '');

$allowed_sorts = [
    'name' => 'a.fullname ASC',
    'name_desc' => 'a.fullname DESC',
    'age' => 'p.date_of_birth ASC',
    'age_desc' => 'p.date_of_birth DESC',
    'gender' => 'p.gender ASC',
    'date' => 'a.created_at DESC',
    'date_asc' => 'a.created_at ASC',
    'education' => 'p.education_level ASC'
];

$sort = $_GET['sort'] ?? 'name';
$order_by = $allowed_sorts[$sort] ?? $allowed_sorts['name'];

$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));

$where = "a.is_verified = 1 AND p.barangay_id = ?";

if ($search !== '') {
    $where .= "
        AND (
            a.fullname LIKE ?
            OR a.email LIKE ?
            OR a.phone LIKE ?
            OR p.school LIKE ?
            OR p.course LIKE ?
            OR p.education_level LIKE ?
            OR p.gender LIKE ?
        )
    ";
}

$search_value = "%" . $search . "%";

/* COUNT RESIDENTS */
$count_sql = "
    SELECT COUNT(*) AS total
    FROM applicant_accounts a
    INNER JOIN applicant_profiles p ON p.applicant_id = a.id
    WHERE $where
";

$count_stmt = mysqli_prepare($conn, $count_sql);

if ($search !== '') {
    mysqli_stmt_bind_param(
        $count_stmt,
        "isssssss",
        $barangay_id,
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value
    );
} else {
    mysqli_stmt_bind_param($count_stmt, "i", $barangay_id);
}

mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_residents = (int)(mysqli_fetch_assoc($count_result)['total'] ?? 0);
mysqli_stmt_close($count_stmt);

$total_pages = max(1, (int)ceil($total_residents / $limit));
$page = min($page, $total_pages);
$offset = ($page - 1) * $limit;

/* GET RESIDENTS */
$sql = "
    SELECT
        a.id,
        a.fullname,
        a.email,
        a.phone,
        a.created_at,
        p.date_of_birth,
        TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) AS age,
        p.gender,
        p.contact_number,
        p.school,
        p.course,
        p.education_level,
        p.year_level,
        b.name AS barangay
    FROM applicant_accounts a
    INNER JOIN applicant_profiles p ON p.applicant_id = a.id
    LEFT JOIN barangays b ON b.id = p.barangay_id
    WHERE $where
    ORDER BY $order_by
    LIMIT ? OFFSET ?
";

$stmt = mysqli_prepare($conn, $sql);

if ($search !== '') {
    mysqli_stmt_bind_param(
        $stmt,
        "issssssiii",
        $barangay_id,
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $limit,
        $offset
    );
} else {
    mysqli_stmt_bind_param($stmt, "iii", $barangay_id, $limit, $offset);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$residents = [];

while ($row = mysqli_fetch_assoc($result)) {
    $residents[] = $row;
}

mysqli_stmt_close($stmt);

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function queryLink($pageNumber) {
    $params = $_GET;
    $params['page'] = $pageNumber;
    return '?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= e($barangay['name']) ?> Residents | SK KONEXT</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --bg: #0b1711;
    --panel: #12251a;
    --panel-light: #182e21;
    --border: #263e2e;
    --green: #3b8055;
    --green-light: #78b68b;
    --text: #edf4ee;
    --muted: #91a398;
}

* {
    box-sizing: border-box;
}

body {
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
}

.main-content {
    max-width: 1550px;
    margin: auto;
    padding: 32px;
}

.top-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 18px;
    margin-bottom: 28px;
}

.page-title {
    font-size: 27px;
    font-weight: 800;
    color: #f1f7f2;
    margin-bottom: 6px;
}

.page-title i {
    color: var(--green-light);
}

.subtitle {
    color: var(--muted);
    font-size: 13px;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.back-btn,
.add-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 16px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none !important;
    transition: .2s;
}

.back-btn {
    color: #d4e4d8;
    background: #1a3022;
    border: 1px solid var(--border);
}

.back-btn:hover {
    color: white;
    background: #223d2b;
}

.add-btn {
    color: white;
    background: #34764b;
    border: 1px solid #43875a;
}

.add-btn:hover {
    color: white;
    background: #40885a;
}

.stat-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 13px;
    padding: 21px;
    margin-bottom: 22px;
    min-height: 112px;
}

.stat-label {
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .7px;
}

.stat-number {
    color: #f1f7f2;
    font-size: 28px;
    font-weight: 800;
    margin-top: 7px;
}

.stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #203d2b;
    color: var(--green-light);
    font-size: 18px;
}

.content-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 8px 25px rgba(0,0,0,.12);
}

.section-title {
    font-size: 17px;
    font-weight: 800;
    color: #edf5ef;
}

.form-control {
    height: 43px;
    background: #0d1d14;
    color: #edf4ee;
    border: 1px solid #304838;
    border-radius: 8px;
    font-size: 13px;
}

.form-control:focus {
    background: #0d1d14;
    color: white;
    border-color: #5b9a6d;
    box-shadow: 0 0 0 2px rgba(91,154,109,.15);
}

.form-control::placeholder {
    color: #778b7d;
}

select.form-control option {
    background: #12251a;
    color: white;
}

.search-btn {
    height: 43px;
    padding: 0 17px;
    border: 1px solid #43875a;
    border-radius: 8px;
    background: #34764b;
    color: white;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
}

.search-btn:hover {
    background: #40885a;
}

.reset-btn {
    display: inline-flex;
    align-items: center;
    height: 43px;
    padding: 0 13px;
    background: #1c3224;
    color: #c6d8cb;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 12px;
    text-decoration: none !important;
}

.reset-btn:hover {
    color: white;
    background: #294331;
}

.table-responsive {
    border: 1px solid var(--border);
    border-radius: 10px;
}

.table {
    margin-bottom: 0;
    color: #dce8df;
}

.table thead th {
    background: #1a3022;
    color: #a9c3b0;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .6px;
    font-weight: 800;
    border: none;
    border-bottom: 1px solid #304838;
    padding: 15px 12px;
    white-space: nowrap;
}

.table tbody td {
    background: var(--panel);
    color: #d5e1d8;
    font-size: 12px;
    padding: 15px 12px;
    vertical-align: middle;
    border-top: 1px solid #263e2e;
}

.table tbody tr:hover td {
    background: #192e20;
}

.resident-name {
    color: #f0f7f1;
    font-weight: 700;
}

.badge-gender {
    display: inline-block;
    background: #203d2b;
    color: #9ed0aa;
    border: 1px solid #31563b;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
}

.empty-state {
    text-align: center;
    padding: 55px 15px;
    color: var(--muted);
}

.empty-state i {
    display: block;
    color: #526b59;
    font-size: 42px;
    margin-bottom: 17px;
}

.empty-state h5 {
    color: #e4eee6;
    font-size: 17px;
    font-weight: 800;
}

.pagination {
    gap: 4px;
}

.pagination .page-link {
    color: #c6d8cb;
    background: #172c1e;
    border: 1px solid #304838;
    border-radius: 6px;
    font-size: 12px;
}

.pagination .page-item.active .page-link {
    color: white;
    background: #34764b;
    border-color: #43875a;
}

.pagination .page-item.disabled .page-link {
    color: #65796b;
    background: #12251a;
}

@media(max-width: 768px) {
    .main-content {
        padding: 18px;
    }

    .content-card {
        padding: 16px;
    }

    .page-title {
        font-size: 22px;
    }

    .header-actions {
        width: 100%;
    }
}
</style>
</head>

<body>

<div class="main-content">

    <div class="top-header">
        <div>
            <div class="page-title">
                <i class="fa-solid fa-users-viewfinder mr-2"></i>
                Resident Profiling
            </div>

            <div class="subtitle">
                Manage and view registered residents of
                <strong style="color:#c9dfcf;"><?= e($barangay['name']) ?></strong>
            </div>
        </div>

        <div class="header-actions">
            <a href="residents.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Barangays
            </a>

           
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">VERIFIED RESIDENTS</div>
                        <div class="stat-number"><?= number_format($total_residents) ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">BARANGAY</div>
                        <div class="stat-number" style="font-size:21px;">
                            <?= e($barangay['name']) ?>
                        </div>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card">

        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <div class="section-title">
                <i class="fa-solid fa-list-ul mr-2" style="color:#78b68b;"></i>
                Resident List
            </div>

            <div class="subtitle">
                Verified registered residents only
            </div>
        </div>

        <form method="GET" class="mb-4">
            <input type="hidden" name="barangay_id" value="<?= $barangay_id ?>">

            <div class="row">
                <div class="col-md-5 mb-2">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Search name, email, phone, school..."
                        value="<?= e($search) ?>"
                    >
                </div>

                <div class="col-md-4 mb-2">
                    <select name="sort" class="form-control">
                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
                        <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                        <option value="age" <?= $sort === 'age' ? 'selected' : '' ?>>Age (Oldest First)</option>
                        <option value="age_desc" <?= $sort === 'age_desc' ? 'selected' : '' ?>>Age (Youngest First)</option>
                        <option value="gender" <?= $sort === 'gender' ? 'selected' : '' ?>>Gender</option>
                        <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Recently Registered</option>
                        <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Oldest Registration</option>
                        <option value="education" <?= $sort === 'education' ? 'selected' : '' ?>>Education Level</option>
                    </select>
                </div>

                <div class="col-md-3 mb-2">
                    <button type="submit" class="search-btn">
                        <i class="fa-solid fa-magnifying-glass mr-1"></i>
                        Search / Sort
                    </button>

                    <a href="barangay_residents.php?barangay_id=<?= $barangay_id ?>" class="reset-btn ml-1">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        <?php if (count($residents) > 0): ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Contact Number</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Education</th>
                        <th>Year Level</th>
                        <th>School</th>
                        <th>Course / Strand</th>
                        <th>Date Registered</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($residents as $index => $resident): ?>
                    <tr>
                        <td><?= $offset + $index + 1 ?></td>

                        <td>
                            <div class="resident-name"><?= e($resident['fullname']) ?></div>
                        </td>

                        <td><?= e($resident['email']) ?></td>

                        <td><?= e($resident['contact_number'] ?: $resident['phone']) ?></td>

                        <td>
                            <?= $resident['age'] !== null
                                ? (int)$resident['age'] . ' yrs'
                                : '—' ?>
                        </td>

                        <td>
                            <span class="badge-gender">
                                <?= e($resident['gender'] ?: 'Not specified') ?>
                            </span>
                        </td>

                        <td><?= e($resident['education_level'] ?: '—') ?></td>
                        <td><?= e($resident['year_level'] ?: '—') ?></td>
                        <td><?= e($resident['school'] ?: '—') ?></td>
                        <td><?= e($resident['course'] ?: '—') ?></td>

                        <td>
                            <?= !empty($resident['created_at'])
                                ? date('M d, Y', strtotime($resident['created_at']))
                                : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap mt-4">
            <div class="subtitle mb-2">
                Showing <?= $offset + 1 ?> to
                <?= min($offset + $limit, $total_residents) ?>
                of <?= number_format($total_residents) ?> residents
            </div>

            <?php if ($total_pages > 1): ?>
            <nav>
                <ul class="pagination pagination-sm mb-0">

                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e(queryLink(max(1, $page - 1))) ?>">
                            Previous
                        </a>
                    </li>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                        <a class="page-link" href="<?= e(queryLink($i)) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e(queryLink(min($total_pages, $page + 1))) ?>">
                            Next
                        </a>
                    </li>

                </ul>
            </nav>
            <?php endif; ?>
        </div>

        <?php else: ?>

        <div class="empty-state">
            <i class="fa-solid fa-users-slash"></i>
            <h5>No Residents Found</h5>
            <p class="mb-0">
                <?php if ($search !== ''): ?>
                    No verified residents matched your search.
                <?php else: ?>
                    There are currently no verified registered residents assigned to this barangay.
                <?php endif; ?>
            </p>
        </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
