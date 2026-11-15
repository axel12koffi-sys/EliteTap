    <?php
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $isApple = (bool) preg_match('/iPhone|iPad|iPod|Macintosh/i', $ua);
    $isAndroid = stripos($ua, 'Android') !== false;
    $walletOk = profile_can($p, 'wallet');
    $showApple = $walletOk && wallet_apple_enabled() && !$isAndroid;
    $showGoogle = $walletOk && wallet_google_enabled() && !$isApple;
    ?>
    <?php if ($showApple || $showGoogle): ?>
    <div class="wallet">
      <?php if ($showApple): ?><a class="wallet-btn" href="/u/<?= e($p['slug']) ?>/wallet/apple"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 10h18M15 15h3"/></svg><?= te('Ajouter à Apple Wallet') ?></a><?php endif; ?>
      <?php if ($showGoogle): ?><a class="wallet-btn" href="/u/<?= e($p['slug']) ?>/wallet/google" rel="nofollow"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 10h18M15 15h3"/></svg><?= te('Ajouter à Google Wallet') ?></a><?php endif; ?>
    </div>
    <?php endif; ?>
