<?php
// index.php 
require 'db_connect.php';
$pdo = get_db_connection();

// get all users for  sidebar
$stmt = $pdo->query('SELECT id, firstname, lastname FROM users ORDER BY lastname, firstname');
$users = $stmt->fetchAll();

// Check if a user has been selected through a query string
$selected_user = null;
$portfolio = null;
$summary = null;

if (isset($_GET['userId'])) {
    $user_id = $_GET['userId'];

    // get the user's details
    $stmt = $pdo->prepare('SELECT id, firstname, lastname FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $selected_user = $stmt->fetch();

    if ($selected_user) {
        // get the portfolio for this user
        // NOTE : DONT FORGET THIS: Value per stock = most recent closing price from history * shares owned
        $stmt = $pdo->prepare('
            SELECT
                p.symbol,
                c.name,
                p.amount,
                h.close,
                (p.amount * h.close) AS stockValue
            FROM portfolio p
            INNER JOIN companies c ON p.symbol = c.symbol
            INNER JOIN history h ON p.symbol = h.symbol
            WHERE p.userid = ?
              AND h.date = (
                  SELECT MAX(h2.date) FROM history h2 WHERE h2.symbol = p.symbol
              )
            ORDER BY c.name
        ');
        $stmt->execute([$user_id]);
        $portfolio = $stmt->fetchAll();

        // Calculate summary stats
        $total_value = 0;
        $total_companies = count($portfolio);
        $total_shares = 0;
        
        foreach ($portfolio as $row) {
            $total_value += $row['stockValue'];
            $total_shares += $row['amount'];
        }

        $summary = [
            'total_shares'    => $total_shares,
            'total_companies' => $total_companies,
            'total_value'     => $total_value,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockViewer 10,000 Customers</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- HEADER -->
<header>
    <a href="index.php" class="site-title">Stock<span>Viewer 10,000</span></a>
    <nav>
        <a href="index.php" class="active">Home</a>
        <a href="companies.php">Companies</a>
        <a href="about.php">About</a>
    </nav>
</header>

<!-- LAYOUT -->
<div class="page-layout">

    <!-- customer list -->
    <aside class="sidebar">
        <div class="sidebar-title">Customers</div>
        <ul class="sidebar-list">
            <?php foreach ($users as $user): ?>
                <li>
                <a href="index.php?userId=<?php echo $user['id']; ?>"
                class="<?php echo (isset($selected_user) && $selected_user['id'] == $user['id']) ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($user['lastname'] . ', ' . $user['firstname']); ?>
                </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <!-- Mains stuff -->
    <main>

        <?php if (!$selected_user): ?>
            <!-- Not selected yet -->
            <div class="select-prompt">
                <p>Select a customer from the list to view their portfolio.</p>
            </div>

        <?php else: ?>
            <!-- User details header -->
            <div class="detail-header">
                <h2><?php echo htmlspecialchars($selected_user['firstname'] . ' ' . $selected_user['lastname']); ?></h2>
                <div class="subtitle">Portfolio overview prices as of most recent trading day</div>
            </div>

            <!-- key stats -->
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="label">Number of Shares</div>
                    <div class="value"><?php echo number_format($summary['total_shares']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Number of Companies</div>
                    <div class="value blue"><?php echo $summary['total_companies']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Total Value</div>
                    <div class="value green">$<?php echo number_format($summary['total_value'], 2); ?></div>
                </div>
            </div>

            <!-- table -->
            <div class="section-title">Holdings</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Symbol</th>
                        <th>Company Name</th>
                        <th>Shares (amount)</th>
                        <th>Latest Close</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($portfolio as $row): ?>
                        <tr>
                            <td><span class="symbol-badge"><?php echo htmlspecialchars($row['symbol']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo number_format($row['amount']); ?></td>
                            <td>$<?php echo number_format($row['close'], 4); ?></td>
                            <td>$<?php echo number_format($row['stockValue'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>

    </main>
</div>

</body>
</html>