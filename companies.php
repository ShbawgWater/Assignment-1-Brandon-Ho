<?php
// companies.php 
require 'db_connect.php';
$pdo = get_db_connection();

// Get all companies for the sidebar
$stmt = $pdo->query('SELECT symbol, name FROM companies ORDER BY name');
$companies = $stmt->fetchAll();

// Check if a company has been selected 
$selected_company = null;
$history = null;
$stats = null;

if (isset($_GET['symbol'])) {
    $symbol = $_GET['symbol'];

    // get company details
    $stmt = $pdo->prepare('
        SELECT symbol, name, sector, subindustry, address, exchange, website, description
        FROM companies
        WHERE symbol = ?
    ');
    $stmt->execute([$symbol]);
    $selected_company = $stmt->fetch();

    if ($selected_company) {
    // Get the total volume numbers from the companies table,
    // and get the highest and lowest values from the history table.
        $stmt = $pdo->prepare('
            SELECT
                SUM(h.volume)                        AS totalVolume,
                AVG(h.volume)                        AS avgVolume,
                MAX(h.high)                          AS historyHigh,
                MIN(h.low)                           AS historyLow
            FROM history h
            WHERE h.symbol = ?
        ');
        $stmt->execute([$symbol]);
        $stats = $stmt->fetch();

        // Get full  history in ascending date order
        $stmt = $pdo->prepare('
            SELECT date, volume, open, close, high, low
            FROM history
            WHERE symbol = ?
            ORDER BY date ASC
        ');
        $stmt->execute([$symbol]);
        $history = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockViewer 10,000 Companies</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- HEADER -->
<header>
    <a href="index.php" class="site-title">Stock<span>Viewer 10,000</span></a>
    <nav>
        <a href="index.php">Home</a>
        <a href="companies.php" class="active">Companies</a>
        <a href="about.php">About</a>
    </nav>
</header>

<!--Layout -->
<div class="page-layout">

    <!-- Sidebar: company list -->
    <aside class="sidebar">
        <div class="sidebar-title">Companies</div>
        <ul class="sidebar-list">
            <?php foreach ($companies as $company): ?>
                <li>
                    <a href="companies.php?symbol=<?php echo urlencode($company['symbol']); ?>"
                       class="<?php echo (isset($selected_company) && $selected_company['symbol'] === $company['symbol']) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($company['name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <!--Main-->
    <main>

        <?php if (!$selected_company && isset($_GET['symbol'])): ?>
            <!-- Invalid -->
            <p class="error">Company not found.</p>

        <?php elseif (!$selected_company): ?>
            <!-- Nothing yet -->
            <div class="select-prompt">
                <p>Select a company from the list to view its details and price history.</p>
            </div>

        <?php else: ?>
            <!-- Company -->
            <div class="detail-header">
                <h2><?php echo htmlspecialchars($selected_company['name']); ?></h2>
                <div class="subtitle">
                    <span class="symbol-badge"><?php echo htmlspecialchars($selected_company['symbol']); ?></span>
                    <?php echo htmlspecialchars($selected_company['exchange']); ?>
                </div>
            </div>

            <!-- Company info -->
            <div class="info-block">
                <div class="info-row">
                    <span class="key">Sector:</span>
                    <span class="val"><?php echo htmlspecialchars($selected_company['sector']); ?></span>
                </div>
                <div class="info-row">
                    <span class="key">Sub-Industry:</span>
                    <span class="val"><?php echo htmlspecialchars($selected_company['subindustry']); ?></span>
                </div>
                <div class="info-row">
                    <span class="key">Address:</span>
                    <span class="val"><?php echo htmlspecialchars($selected_company['address']); ?></span>
                </div>
                <div class="info-row">
                    <span class="key">Website:</span>
                    <span class="val">
                        <a href="<?php echo htmlspecialchars($selected_company['website']); ?>" target="_blank">
                            <?php echo htmlspecialchars($selected_company['website']); ?>
                        </a>
                    </span>
                </div>
                <div class="info-row" style="grid-column: 1 / -1;">
                    <span class="key">Description</span>
                    <span class="val"><?php echo htmlspecialchars($selected_company['description']); ?></span>
                </div>
            </div>

            <!-- key stats. the big 4 -->
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="label">History High</div>
                    <div class="value">$<?php echo number_format($stats['historyHigh'], 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">History Low</div>
                    <div class="value">$<?php echo number_format($stats['historyLow'], 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Total Volume</div>
                    <div class="value">$<?php echo number_format($stats['totalVolume'], 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Average Volume</div>
                    <div class="value">$<?php echo number_format($stats['avgVolume'], 2); ?></div>
                </div>
            </div>

            <!-- History table -->
            <div class="section-title">History (3 Months)</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Volume</th>
                        <th>Open</th>
                        <th>Close</th>
                        <th>High</th>
                        <th>Low</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['date']); ?></td>
                            <td><?php echo number_format($row['volume']); ?></td>
                            <td>$<?php echo number_format($row['open'], 4); ?></td>
                            <td>$<?php echo number_format($row['close'], 4); ?></td>
                            <td>$<?php echo number_format($row['high'], 4); ?></td>
                            <td>$<?php echo number_format($row['low'], 4); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>

    </main>
</div>

</body>
</html>