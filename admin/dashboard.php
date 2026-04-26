<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$stmt = $db->prepare('SELECT user_id, first_name, middle_name, last_name, email, user_type FROM users ORDER BY user_id DESC');
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalUsers = count($users);
$adminCount = 0;
$mayorCount = 0;
$studentCount = 0;
foreach ($users as $user) {
    switch (strtolower($user['user_type'])) {
        case 'admin':
            $adminCount++;
            break;
        case 'mayor':
            $mayorCount++;
            break;
        case 'student':
            $studentCount++;
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Scholar App</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #f7fafc;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 40px;
            width: 100%;
            transition: margin-left 0.3s ease;
        }
        .main-content.expanded {
            margin-left: 0;
        }

        /* Topbar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        .menu-btn {
            font-size: 26px;
            cursor: pointer;
            color: #667eea;
            margin-right: 15px;
            background: rgba(102, 126, 234, 0.1);
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .menu-btn:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }
        .topbar-left { display: flex; align-items: center; }
        .topbar h1 {
            color: #2d3748;
            font-size: 26px;
            font-weight: 600;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.15);
            border-color: #667eea;
        }
        .stat-card .number {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
        }
        .stat-card .label {
            color: #666;
            font-size: 14px;
        }

        /* Users Table */
        .users-section {
            background: white;
            padding: 32px;
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        .users-section h3 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 600;
        }
        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .search-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .search-input::placeholder {
            color: #a0aec0;
        }
        .clear-search {
            padding: 12px 20px;
            background: #e2e8f0;
            color: #4a5568;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        .clear-search:hover {
            background: #cbd5e0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th {
            background-color: #f8f9fa;
            color: #333;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e0e0e0;
            cursor: pointer;
            user-select: none;
            position: relative;
        }
        table th:hover {
            background-color: #e9ecef;
        }
        table th::after {
            content: '⇅';
            position: absolute;
            right: 10px;
            opacity: 0.3;
            font-size: 12px;
        }
        table th.sort-asc::after {
            content: '▲';
            opacity: 1;
            color: #667eea;
        }
        table th.sort-desc::after {
            content: '▼';
            opacity: 1;
            color: #667eea;
        }
        table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            color: #555;
        }
        table tr:hover { background-color: #f8f9fa; }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .pagination-info {
            color: #666;
            font-size: 14px;
        }
        .pagination-controls {
            display: flex;
            gap: 8px;
        }
        .pagination-controls button {
            padding: 8px 16px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #667eea;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        .pagination-controls button:hover:not(:disabled) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        .pagination-controls button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .pagination-controls button.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 20px; }
            .pagination { flex-direction: column; gap: 15px; }
        }
    </style>
</head>
<body>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content" id="main-content">

        <div class="topbar">
            <div class="topbar-left">
                <div class="menu-btn" onclick="toggleSidebar()">☰</div>
                <h1>Admin Dashboard</h1>
            </div>

            <div class="user-info">
                <span>Welcome, <strong><?= h($_SESSION['first_name'] ?? 'Admin') ?></strong></span>
                <span class="badge">ADMIN</span>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?= h($totalUsers) ?></div>
                <div class="label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= h($adminCount) ?></div>
                <div class="label">Admins</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= h($mayorCount) ?></div>
                <div class="label">Mayors</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= h($studentCount) ?></div>
                <div class="label">Students</div>
            </div>
        </div>

        <!-- Users List -->
        <div class="users-section" id="users">
            <h3>Registered Users</h3>
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="🔍 Search by User ID, Name, Email, or Role..." onkeyup="searchTable()">
                <button class="clear-search" type="button" onclick="clearSearch()">Clear</button>
            </div>
            <table id="usersTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)" data-column="0">User ID</th>
                        <th onclick="sortTable(1)" data-column="1">First Name</th>
                        <th onclick="sortTable(2)" data-column="2">Middle Name</th>
                        <th onclick="sortTable(3)" data-column="3">Last Name</th>
                        <th onclick="sortTable(4)" data-column="4">Email</th>
                        <th onclick="sortTable(5)" data-column="5">Role</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= h($user['user_id']) ?></td>
                        <td><?= h($user['first_name']) ?></td>
                        <td><?= h($user['middle_name'] ?: '-') ?></td>
                        <td><?= h($user['last_name']) ?></td>
                        <td><?= h($user['email']) ?></td>
                        <td><?= h($user['user_type']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="pagination">
                <div class="pagination-info">
                    Showing <span id="startEntry">1</span> to <span id="endEntry">10</span> of <span id="totalEntries"><?= h($totalUsers) ?></span> entries
                </div>
                <div class="pagination-controls">
                    <button type="button" onclick="changePage('prev')" id="prevBtn">Previous</button>
                    <div id="pageNumbers"></div>
                    <button type="button" onclick="changePage('next')" id="nextBtn">Next</button>
                </div>
            </div>
        </div>

    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            sidebar.classList.toggle('hide');
            mainContent.classList.toggle('expanded');
        }

        // Sorting
        let sortDirection = {};
        const tableBody = document.getElementById('tableBody');

        function sortTable(columnIndex) {
            const table = document.getElementById('usersTable');
            const headers = table.querySelectorAll('th');
            const rows = Array.from(tableBody.getElementsByTagName('tr'));

            sortDirection[columnIndex] = sortDirection[columnIndex] === 'asc' ? 'desc' : 'asc';
            headers.forEach(header => header.classList.remove('sort-asc', 'sort-desc'));
            headers[columnIndex].classList.add(sortDirection[columnIndex] === 'asc' ? 'sort-asc' : 'sort-desc');

            rows.sort((a, b) => {
                const aText = a.cells[columnIndex].textContent.trim();
                const bText = b.cells[columnIndex].textContent.trim();
                const aNum = parseFloat(aText);
                const bNum = parseFloat(bText);

                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return sortDirection[columnIndex] === 'asc' ? aNum - bNum : bNum - aNum;
                }

                return sortDirection[columnIndex] === 'asc'
                    ? aText.localeCompare(bText)
                    : bText.localeCompare(aText);
            });

            rows.forEach(row => tableBody.appendChild(row));
            currentPage = 1;
            displayPage(currentPage);
        }

        function searchTable() {
            const filter = document.getElementById('searchInput').value.toLowerCase();
            const rows = Array.from(tableBody.getElementsByTagName('tr'));

            rows.forEach(row => {
                const cells = Array.from(row.getElementsByTagName('td'));
                const visible = cells.some(cell => cell.textContent.toLowerCase().includes(filter));
                row.style.display = visible ? '' : 'none';
            });

            currentPage = 1;
            updatePaginationAfterSearch();
        }

        function clearSearch() {
            document.getElementById('searchInput').value = '';
            searchTable();
        }

        let currentPage = 1;
        const rowsPerPage = 10;
        let allRows = Array.from(tableBody.getElementsByTagName('tr'));
        let totalPages = Math.ceil(allRows.length / rowsPerPage);

        function displayPage(page) {
            allRows = Array.from(tableBody.getElementsByTagName('tr')).filter(row => row.style.display !== 'none');
            totalPages = Math.ceil(allRows.length / rowsPerPage);

            if (page > totalPages && totalPages > 0) {
                currentPage = totalPages;
                page = currentPage;
            } else if (totalPages === 0) {
                currentPage = 1;
                page = 1;
            }

            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            allRows.forEach((row, index) => {
                row.style.display = index >= start && index < end ? '' : 'none';
            });

            document.getElementById('startEntry').textContent = allRows.length > 0 ? start + 1 : 0;
            document.getElementById('endEntry').textContent = Math.min(end, allRows.length);
            document.getElementById('totalEntries').textContent = allRows.length;
            document.getElementById('prevBtn').disabled = page === 1;
            document.getElementById('nextBtn').disabled = page === totalPages || totalPages === 0;
            renderPageNumbers();
        }

        function renderPageNumbers() {
            const pageNumbersDiv = document.getElementById('pageNumbers');
            pageNumbersDiv.innerHTML = '';

            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.type = 'button';
                btn.className = i === currentPage ? 'active' : '';
                btn.onclick = () => {
                    currentPage = i;
                    displayPage(currentPage);
                };
                pageNumbersDiv.appendChild(btn);
            }
        }

        function changePage(direction) {
            if (direction === 'prev' && currentPage > 1) {
                currentPage--;
            } else if (direction === 'next' && currentPage < totalPages) {
                currentPage++;
            }
            displayPage(currentPage);
        }

        function updatePaginationAfterSearch() {
            allRows = Array.from(tableBody.getElementsByTagName('tr')).filter(row => row.style.display !== 'none');
            totalPages = Math.ceil(allRows.length / rowsPerPage);
            if (currentPage > totalPages && totalPages > 0) {
                currentPage = totalPages;
            } else if (totalPages === 0) {
                currentPage = 1;
            }
            displayPage(currentPage);
        }

        displayPage(currentPage);
    </script>

</body>
</html>
