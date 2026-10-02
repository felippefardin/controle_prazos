<?php
declare(strict_types=1);

// Compatibilidade com favoritos e links antigos. A tela de login atual fica em index.php.
header('Location: index.php', true, 302);
exit;
