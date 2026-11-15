    <?php if ($actions): ?>
    <nav class="actions" aria-label="<?= te('Me contacter') ?>" style="--n: <?= min(4, count($actions)) ?>">
      <?php foreach ($actions as [$href, $label, $icon]): ?>
        <a href="<?= e($href) ?>"<?= str_starts_with($href, 'http') ? ' rel="noopener" target="_blank"' : '' ?>>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><?= $icon ?></svg>
          <?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
