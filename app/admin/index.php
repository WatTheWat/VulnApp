<?php
require __DIR__ . '/../includes/config.php';
require_admin();
$pageTitle = 'Back office';

$defaults = [
    'headline'     => 'Fresh virtual coffee, brewed in the cloud.',
    'tagline'      => 'Zero calories. Zero caffeine. One hundred percent packets.',
    'announcement' => 'New this week: the Honeypot Oat Latte. Irresistible, by design.',
    'theme'        => 'normal',
];
$themes  = ['normal' => 'Classic', 'alert' => 'Emergency notice (red)'];
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['restore'])) {
        $values  = $defaults;
        $message = 'Storefront restored to the defaults.';
    } else {
        $values = [
            'headline'     => mb_substr(trim($_POST['headline'] ?? ''), 0, 255),
            'tagline'      => mb_substr(trim($_POST['tagline'] ?? ''), 0, 255),
            'announcement' => mb_substr(trim($_POST['announcement'] ?? ''), 0, 255),
            'theme'        => array_key_exists($_POST['theme'] ?? '', $themes) ? $_POST['theme'] : 'normal',
        ];
        $message = 'Storefront updated.';
    }
    $stmt = $db->prepare('UPDATE settings SET value = ? WHERE name = ?');
    foreach ($values as $name => $value) {
        $stmt->bind_param('ss', $value, $name);
        $stmt->execute();
    }
}

$current = [];
foreach (array_keys($defaults) as $name) {
    $current[$name] = setting($db, $name);
}

$orderCount = (int) $db->query('SELECT COUNT(*) AS n FROM orders')->fetch_assoc()['n'];
$recent = $db->query(
    "SELECT o.id, o.customer_name, o.size, o.notes, m.name AS drink, o.created_at
     FROM orders o JOIN menu_items m ON m.id = o.item_id
     ORDER BY o.id DESC LIMIT 10"
)->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/../includes/header.php';
?>
<section class="section">
  <div class="admin-head">
    <div>
      <p class="eyebrow">Signed in as <?= h($_SESSION['admin_user']) ?></p>
      <h1>Back office</h1>
    </div>
    <a class="btn ghost small" href="/admin/logout.php">Sign out</a>
  </div>

  <?php if ($message): ?><div class="notice"><?= h($message) ?></div><?php endif; ?>

  <div class="split">
    <div>
      <h2>Storefront</h2>
      <form class="form" method="post" action="/admin/">
        <label>Homepage headline <input type="text" name="headline" maxlength="255" value="<?= h($current['headline']) ?>"></label>
        <label>Tagline <input type="text" name="tagline" maxlength="255" value="<?= h($current['tagline']) ?>"></label>
        <label>Announcement bar <input type="text" name="announcement" maxlength="255" value="<?= h($current['announcement']) ?>"></label>
        <label>Theme
          <select name="theme">
            <?php foreach ($themes as $key => $label): ?>
              <option value="<?= $key ?>" <?= $current['theme'] === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <div class="row">
          <button class="btn" type="submit">Save storefront</button>
          <button class="btn ghost" type="submit" name="restore" value="1">Restore defaults</button>
        </div>
      </form>
    </div>
    <div>
      <h2>Orders <span class="count"><?= $orderCount ?></span></h2>
      <table class="menu compact">
        <thead><tr><th>#</th><th>Name</th><th>Drink</th><th>Notes</th></tr></thead>
        <tbody>
          <?php foreach ($recent as $o): ?>
            <tr>
              <td><?= (int) $o['id'] ?></td>
              <td><?= h($o['customer_name']) ?></td>
              <td><?= h($o['size']) ?> <?= h($o['drink']) ?></td>
              <td class="muted"><?= h($o['notes']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
