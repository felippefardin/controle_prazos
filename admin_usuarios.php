<?php
declare(strict_types=1);
require_once __DIR__.'/includes/auth.php';require_once __DIR__.'/includes/db.php';require_once __DIR__.'/includes/helpers.php';exigirAdmin();
$usuarios=db()->query("SELECT id,nome,email,nome_usuario,matricula,cpf,perfil,situacao,tentativas_login,email_verificado_em,ultimo_login_em,criado_em FROM usuarios ORDER BY (situacao='pendente') DESC,(perfil='admin') DESC,nome")->fetch_all(MYSQLI_ASSOC);
$titulo='Administração de usuários';require __DIR__.'/includes/header.php';
?>
<section class="page-head"><div><h1>Administração de usuários</h1><p>Aprove, bloqueie, desbloqueie ou acesse temporariamente uma conta.</p></div></section>
<?php if(isset($_GET['ok'])):?><div class="alert alert-success alert-page">Ação realizada e registrada na auditoria.</div><?php endif;?>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Usuário</th><th>CPF</th><th>Matrícula</th><th>Perfil</th><th>Situação</th><th>Tentativas</th><th>Último login</th><th>Ações</th></tr></thead><tbody>
<?php foreach($usuarios as $u):?><tr><td><strong><?=e($u['nome'])?></strong><br><small><?=e($u['nome_usuario'])?> · <?=e($u['email'])?></small></td><td><?=e(mascararCpf($u['cpf']))?></td><td><?=e($u['matricula']?:'-')?></td><td><?=e(strtoupper($u['perfil']))?></td><td><span class="status <?= $u['situacao']==='aprovado'?'status-em-dia':($u['situacao']==='pendente'?'status-atencao':'status-vencido') ?>"><?=e(strtoupper($u['situacao']))?></span></td><td><?= (int)$u['tentativas_login'] ?>/5</td><td><?=e($u['ultimo_login_em']?:'-')?></td><td class="actions">
<?php if($u['perfil']!=='admin'):?>
<?php if($u['situacao']==='pendente'):?><form method="post" action="admin_usuario_acao.php" class="inline-form"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="id" value="<?=(int)$u['id']?>"><input type="hidden" name="acao" value="aprovar"><button class="link-button success">Aprovar</button></form><?php endif;?>
<form method="post" action="admin_usuario_acao.php" class="inline-form"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="id" value="<?=(int)$u['id']?>"><input type="hidden" name="acao" value="<?= $u['situacao']==='bloqueado'?'desbloquear':'bloquear' ?>"><button class="link-button <?= $u['situacao']==='bloqueado'?'success':'danger' ?>"><?= $u['situacao']==='bloqueado'?'Desbloquear':'Bloquear' ?></button></form>
<form method="post" action="admin_usuario_acao.php" class="inline-form"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="id" value="<?=(int)$u['id']?>"><input type="hidden" name="acao" value="representar"><button class="link-button note">Entrar como usuário</button></form>
<?php endif;?></td></tr><?php endforeach;?>
</tbody></table></div></section><?php require __DIR__.'/includes/footer.php';?>
