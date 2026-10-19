<?php
require __DIR__ . '/includes/config.php';
$pageTitle = 'Order';

$sizes  = ['Small', 'Medium', 'Large'];
$errors = [];
$placed = null;

// Safe: prepared statements throughout. Only menu.php is meant to be vulnerable.
$menu = $db->query("SELECT id, name, price FROM menu_items WHERE available = 1 ORDER BY name")
           ->fetch_all(MYSQLI_ASSOC);
$menuById = array_column($menu, null, 'id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['customer_name'] ?? '');
    $itemId = (int) ($_POST['item_id'] ?? 0);
    $size   = $_POST['size'] ?? 'Medium';
    $notes  = trim($_POST['notes'] ?? '');

    if ($name === '' || mb_strlen($name) > 60) $errors[] = 'Tell us a name for the cup (60 characters max).';
    if (!isset($menuById[$itemId]))           $errors[] = 'Pick a drink from the menu.';
    if (!in_array($size, $sizes, true))         $errors[] = 'Pick a real size.';
    if (mb_strlen($notes) > 140)                $errors[] = 'Notes must fit on the cup (140 characters).';

    if (!$errors) {
        $stmt = $db->prepare('INSERT INTO orders (customer_name, item_id, size, notes) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('siss', $name, $itemId, $size, $notes);
        $stmt->execute();
        $placed = [
            'id'    => $stmt->insert_id,
            'name'  => $name,
            'drink' => $menuById[$itemId]['name'],
            'size'  => $size,
        ];
    }
}

$selected = (int) ($_POST['item_id'] ?? $_GET['item'] ?? 0);

require __DIR__ . '/includes/header.php';
?>
<section class="section narrow">
  <?php if ($placed): ?>
    <div class="receipt">
      <p class="eyebrow">Order #<?= (int) $placed['id'] ?></p>
      <h1>Your <?= h($placed['size']) ?> <?= h($placed['drink']) ?> is brewing.</h1>
      <p>Thanks, <?= h($placed['name']) ?>. Listen for your name to be called over UDP.
         It may or may not arrive.</p>
      <div class="steam" aria-hidden="true"><span></span><span></span><span></span></div>
      <a class="btn" href="/">Back to the shop</a>
      <a class="btn ghost" href="/order.php">Order another</a>
    </div>
  <?php else: ?>
    <h1>Order a virtual coffee</h1>
    <p class="muted">Free, instant, and calorie-free. Payment is accepted in good vibes.</p>

    <?php foreach ($errors as $e): ?>
      <div class="alert"><?= h($e) ?></div>
    <?php endforeach; ?>

    <form class="form" method="post" action="/order.php">
      <label>Name for the cup
        <input type="text" name="customer_name" maxlength="60" value="<?= h($_POST['customer_name'] ?? '') ?>" required>
      </label>
      <label>Drink
        <select name="item_id" required>
          <option value="">Choose a drink</option>
          <?php foreach ($menu as $item): ?>
            <option value="<?= (int) $item['id'] ?>" <?= $selected === (int) $item['id'] ? 'selected' : '' ?>>
              <?= h($item['name']) ?> (<?= money($item['price']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <fieldset class="sizes">
        <legend>Size</legend>
        <?php foreach ($sizes as $s): ?>
          <label class="chip">
            <input type="radio" name="size" value="<?= $s ?>" <?= ($_POST['size'] ?? 'Medium') === $s ? 'checked' : '' ?>>
            <span><?= $s ?></span>
          </label>
        <?php endforeach; ?>
      </fieldset>
      <label>Notes for the barista
        <input type="text" name="notes" maxlength="140" value="<?= h($_POST['notes'] ?? '') ?>" placeholder="oat milk, extra shot, no packet loss">
      </label>
      <button class="btn" type="submit">Place order</button>
    </form>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
