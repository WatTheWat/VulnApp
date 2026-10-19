<?php
require __DIR__ . '/includes/config.php';
$pageTitle = 'Menu';

$q = $_GET['q'] ?? '';

// =====================================================================
// INTENTIONALLY VULNERABLE: user input concatenated straight into SQL.
// This is the SQL injection the demo attacks and the pipeline fixes.
// =====================================================================
$sql = "SELECT id, name, description, price, category FROM menu_items
        WHERE available = 1 AND name LIKE '%$q%'
        ORDER BY category, name";

$result = mysqli_query($db, $sql);
$error  = $result === false ? mysqli_error($db) : null;
$items  = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

require __DIR__ . '/includes/header.php';
?>
<section class="section">
  <h1>The menu</h1>
  <form class="search" method="get" action="/menu.php">
    <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search drinks, e.g. latte">
    <button class="btn" type="submit">Search</button>
  </form>

  <?php if ($error): ?>
    <div class="alert">The barista dropped your order: <code><?= h($error) ?></code></div>
  <?php elseif (!$items): ?>
    <p class="muted">No drinks match "<?= h($q) ?>". Try something less exotic.</p>
  <?php else: ?>
    <?php if ($q !== ''): ?>
      <p class="muted"><?= count($items) ?> result(s) for "<?= h($q) ?>"</p>
    <?php endif; ?>
    <table class="menu">
      <thead><tr><th>Drink</th><th>Category</th><th>Price</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <strong><?= h($item['name']) ?></strong>
              <span class="desc"><?= h($item['description']) ?></span>
            </td>
            <td><span class="tag"><?= h($item['category']) ?></span></td>
            <td class="price"><?= h(is_numeric($item['price']) ? money($item['price']) : $item['price']) ?></td>
            <td><a class="btn small" href="/order.php?item=<?= (int) $item['id'] ?>">Order</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
