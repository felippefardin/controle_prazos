<?php if (!empty($alertasLogin)): ?>
<link rel="stylesheet" href="assets/css/alerta_login.css?v=<?= (int)filemtime(__DIR__ . '/../assets/css/alerta_login.css') ?>">
<dialog class="login-deadline-modal" id="login-deadline-modal" aria-labelledby="login-deadline-title" aria-describedby="login-deadline-description">
    <div class="login-deadline-heading">
        <span class="login-deadline-icon" aria-hidden="true">!</span>
        <div>
            <p class="login-deadline-eyebrow">ATENÇÃO AO PRAZO</p>
            <h2 id="login-deadline-title"><?= count($alertasLogin) === 1 ? 'Um processo vence amanhã' : count($alertasLogin) . ' processos vencem amanhã' ?></h2>
        </div>
        <button class="login-deadline-close" type="button" data-close-deadline aria-label="Fechar alerta">×</button>
    </div>
    <p class="login-deadline-description" id="login-deadline-description">Falta 1 dia para o vencimento. Confira os detalhes para acompanhar o prazo.</p>
    <ul class="login-deadline-list" aria-label="Processos com vencimento amanhã">
        <?php foreach ($alertasLogin as $prazoAlerta): ?>
            <li class="login-deadline-item">
                <div class="login-deadline-details">
                    <strong><?= e($prazoAlerta['numero_processo']) ?></strong>
                    <p><?= e($prazoAlerta['assunto']) ?></p>
                    <span>Vencimento: <time datetime="<?= e($prazoAlerta['data_vencimento']) ?>"><?= e(dataBr($prazoAlerta['data_vencimento'])) ?></time> · Amanhã</span>
                </div>
                <a class="btn btn-primary" href="processo_visualizar.php?id=<?= (int)$prazoAlerta['id'] ?>" data-view-deadline aria-label="Ver processo <?= e($prazoAlerta['numero_processo']) ?>">Ver processo <span aria-hidden="true">↗</span></a>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="login-deadline-footer">
        <small>Este aviso aparece apenas uma vez a cada login.</small>
        <button class="btn btn-ghost" type="button" data-close-deadline autofocus>Fechar</button>
    </div>
</dialog>
<script src="assets/js/alerta_login.js?v=<?= (int)filemtime(__DIR__ . '/../assets/js/alerta_login.js') ?>"></script>
<?php endif; ?>
