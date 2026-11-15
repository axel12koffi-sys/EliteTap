  <?php if ($links): ?>
  <ul class="links">
    <?php foreach ($links as $l): ?>
      <li><a href="/l/<?= (int) $l['id'] ?>" rel="noopener" target="_blank"><?= e($l['label']) ?><span aria-hidden="true">→</span></a></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
