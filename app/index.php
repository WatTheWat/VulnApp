<?php
require __DIR__ . '/includes/config.php';
$pageTitle = 'Home';

$headline = setting($db, 'headline');
$tagline  = setting($db, 'tagline');

$featured = $db->query(
    "SELECT id, name, description, price FROM menu_items
     WHERE available = 1 AND id IN (3, 4, 9)"
)->fetch_all(MYSQLI_ASSOC);

$brewing = $db->query(
    "SELECT o.customer_name, o.size, m.name AS drink, o.created_at
     FROM orders o JOIN menu_items m ON m.id = o.item_id
     ORDER BY o.id DESC LIMIT 6"
)->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <p class="eyebrow">Now serving · Plano, the cloud, wherever</p>
  <h1><?= h($headline) ?></h1>
  <p class="lede"><?= h($tagline) ?></p>
  <div class="hero-actions">
    <a class="btn" href="/order.php">Order a coffee</a>
    <a class="btn ghost" href="/menu.php">Browse the menu</a>
  </div>
</section>

<section class="section">
  <h2>Barista picks</h2>
  <div class="cards">
    <?php foreach ($featured as $item): ?>
      <article class="card">
        <h3><?= h($item['name']) ?></h3>
        <p><?= h($item['description']) ?></p>
        <div class="card-foot">
          <span class="price"><?= money($item['price']) ?></span>
          <a class="btn small" href="/order.php?item=<?= (int) $item['id'] ?>">Order</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="section split">
  <div>
    <h2>Now brewing</h2>
    <ul class="board">
      <?php foreach ($brewing as $o): ?>
        <li>
          <strong><?= h($o['customer_name']) ?></strong>
          <span><?= h($o['size']) ?> <?= h($o['drink']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="about">
    <h2>About us</h2>
    <p>Neil and Avi started roasting packets in a dorm room with one rule: every cup is
       brewed to order and delivered over HTTP. Nothing to spill, nothing to pay, and
       the line is never longer than your bandwidth.</p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
