<?php
function admin_nav(array $u): void { ?>
<nav class="top">
  <a href="index.php">Liberatorie</a>
  <a href="templates.php">Modelli</a>
  <a href="password.php">Password</a>
  <span class="sp"></span>
  <span><?= h($u['username']) ?></span>
  <a href="logout.php">Esci</a>
</nav>
<?php }
