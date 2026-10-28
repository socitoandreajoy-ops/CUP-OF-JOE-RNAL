<?php
// Shared menu (search + category tabs + product cards with stock).
// Expects $prods = mysqli result with: product_id, product_name, price, stock_quantity, category_name
if (!function_exists('coj_icon')) {
    function coj_icon($cat, $name) {
        $s = strtolower($cat . ' ' . $name);
        $map = [
            '/tea|matcha|chai/'                                         => '🍵',
            '/iced|cold|frapp|shake|smoothie|blended|float/'            => '🥤',
            '/coffee|latte|espresso|americano|cappuccino|mocha|brew/'   => '☕',
            '/cake|dessert|cookie|brownie|donut|sweet|waffle/'          => '🍰',
            '/croissant|pastry|bread|bake|muffin|bun/'                  => '🥐',
            '/sandwich|burger|panini|toast/'                            => '🥪',
            '/pasta|rice|meal|silog|noodle/'                            => '🍝',
            '/snack|fries|chips|nacho/'                                 => '🍟',
        ];
        foreach ($map as $rx => $ico) if (preg_match($rx, $s)) return $ico;
        return '🍽️';
    }
}
$esc = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);

$byCat = [];
while ($p = mysqli_fetch_assoc($prods)) {
    $byCat[$p['category_name'] ?: 'Other'][] = $p;
}
// alphabetical, but "Other"/"Others" always goes last
uksort($byCat, function ($a, $b) {
    $ao = preg_match('/^others?$/i', $a) ? 1 : 0;
    $bo = preg_match('/^others?$/i', $b) ? 1 : 0;
    return $ao <=> $bo ?: strcasecmp($a, $b);
});
$catIdx = []; $i = 0;
foreach ($byCat as $cn => $_) $catIdx[$cn] = 'c' . (++$i);
?>
<div class="menu-bar">
  <input type="text" id="search" placeholder="🔍 Search menu…" autocomplete="off">
</div>

<div class="cat-tabs">
  <button type="button" class="cat-tab active" data-cat="all">All <em><?= array_sum(array_map('count', $byCat)) ?></em></button>
  <?php foreach ($byCat as $cn => $list): ?>
    <button type="button" class="cat-tab" data-cat="<?= $catIdx[$cn] ?>"><?= $esc($cn) ?> <em><?= count($list) ?></em></button>
  <?php endforeach; ?>
</div>

<?php foreach ($byCat as $cn => $list): ?>
<div class="cat-sec" data-cat="<?= $catIdx[$cn] ?>">
  <h3><?= $esc($cn) ?></h3>
  <div class="items">
    <?php foreach ($list as $p):
        $pid = (int)$p['product_id']; $stock = (int)$p['stock_quantity'];
        $cls = $stock <= 0 ? 'out' : ($stock <= 10 ? 'low' : 'ok');
        $label = $stock <= 0 ? 'Sold out' : ($stock <= 10 ? "Only $stock left" : "$stock in stock");
    ?>
    <div class="item <?= $stock <= 0 ? 'soldout' : '' ?>" data-name="<?= $esc(strtolower($p['product_name'])) ?>">
      <div class="item-top">
        <span class="item-ico"><?= coj_icon($cn, $p['product_name']) ?></span>
        <span class="stock <?= $cls ?>"><?= $label ?></span>
      </div>
      <div class="item-body">
        <div class="item-name"><?= $esc($p['product_name']) ?></div>
        <div class="item-cat"><?= $esc($cn) ?></div>
        <div class="item-foot">
          <span class="price">₱<?= number_format($p['price'], 2) ?></span>
          <?php if ($stock > 0): ?>
          <div class="step">
            <button type="button" onclick="bump(<?= $pid ?>,-1)" aria-label="Less">−</button>
            <input type="number" id="q<?= $pid ?>" name="qty[<?= $pid ?>]" min="0" max="<?= $stock ?>" value="0"
                   data-name="<?= $esc($p['product_name']) ?>" data-price="<?= (float)$p['price'] ?>" oninput="render()">
            <button type="button" onclick="bump(<?= $pid ?>,1)" aria-label="More">+</button>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>

<div class="empty" id="noMatch" style="display:none">No menu items match your search.</div>

<script>
(function () {
  let cat = 'all', term = '';
  const secs = [...document.querySelectorAll('.cat-sec')];
  function apply() {
    let any = false;
    secs.forEach(s => {
      let vis = 0;
      s.querySelectorAll('.item').forEach(i => {
        const ok = i.dataset.name.includes(term);
        i.style.display = ok ? '' : 'none';
        if (ok) vis++;
      });
      const show = (cat === 'all' || s.dataset.cat === cat) && vis > 0;
      s.style.display = show ? '' : 'none';
      if (show) any = true;
    });
    document.getElementById('noMatch').style.display = any ? 'none' : '';
  }
  document.querySelectorAll('.cat-tab').forEach(b => b.addEventListener('click', () => {
    document.querySelectorAll('.cat-tab').forEach(x => x.classList.remove('active'));
    b.classList.add('active'); cat = b.dataset.cat; apply();
  }));
  document.getElementById('search').addEventListener('input', ev => {
    term = ev.target.value.trim().toLowerCase(); apply();
  });
  window.markSelected = function () {
    document.querySelectorAll('.item').forEach(it => {
      const inp = it.querySelector('.step input');
      it.classList.toggle('has-qty', !!inp && (+inp.value || 0) > 0);
    });
  };
})();
</script>