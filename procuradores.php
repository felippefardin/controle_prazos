<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
exigirLogin();

$procuradores = db()->query("SELECT p.*, COUNT(pp.prazo_id) AS total_processos FROM procuradores p LEFT JOIN prazo_procuradores pp ON pp.procurador_id = p.id GROUP BY p.id ORDER BY p.ativo DESC, p.nome")->fetch_all(MYSQLI_ASSOC);
$titulo = 'Procuradores';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head">
    <div><h1>Procuradores</h1><p>Cadastre e gerencie os procuradores que podem ser vinculados aos processos.</p></div>
    <a class="btn btn-primary" href="procurador_form.php">+ Cadastrar procurador</a>
</section>
<?php if (isset($_GET['salvo'])): ?><div class="alert alert-success alert-page">Procurador salvo com sucesso.</div><?php endif; ?>
<section class="panel"><div class="table-wrap"><table>
    <thead><tr><th>Nome</th><th>OAB</th><th>E-mail</th><th>Telefone</th><th>Processos</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$procuradores): ?><tr><td colspan="7" class="empty">Nenhum procurador cadastrado.</td></tr><?php endif; ?>
    <?php foreach ($procuradores as $p): ?><tr>
        <td><strong><?= e($p['nome']) ?></strong></td><td><?= e($p['oab'] ?: '-') ?></td><td><?= e($p['email'] ?: '-') ?></td><td><?= e($p['telefone'] ?: '-') ?></td>
        <td><?= (int)$p['total_processos'] ?></td><td><span class="status <?= $p['ativo'] ? 'status-em-dia' : 'status-concluido' ?>"><?= $p['ativo'] ? 'ATIVO' : 'INATIVO' ?></span></td>
        <td class="actions"><a class="link" href="procurador_form.php?id=<?= (int)$p['id'] ?>">Editar</a></td>
    </tr><?php endforeach; ?>
    </tbody>
</table></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
