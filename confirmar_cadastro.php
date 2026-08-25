<?php
declare(strict_types=1);
require_once __DIR__.'/includes/db.php'; require_once __DIR__.'/includes/auth.php'; require_once __DIR__.'/includes/helpers.php';
$email=strtolower(trim($_POST['email']??$_GET['email']??'')); $erro='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 validarCsrf(); $codigo=preg_replace('/\D/','',$_POST['codigo']??'');
 $stmt=db()->prepare("SELECT v.id,v.usuario_id,v.codigo_hash FROM verificacoes_email v JOIN usuarios u ON u.id=v.usuario_id WHERE u.email=? AND v.tipo='cadastro' AND v.usado_em IS NULL AND v.expira_em>=NOW() AND v.tentativas<5 ORDER BY v.id DESC LIMIT 1"); $stmt->bind_param('s',$email); $stmt->execute(); $v=$stmt->get_result()->fetch_assoc();
 if($v){$id=(int)$v['id'];$q=db()->prepare('UPDATE verificacoes_email SET tentativas=tentativas+1 WHERE id=?');$q->bind_param('i',$id);$q->execute();}
 if(!$v||strlen($codigo)!==6||!password_verify($codigo,$v['codigo_hash'])) $erro='Código inválido ou expirado.';
 else {$uid=(int)$v['usuario_id'];$c=db();$c->begin_transaction();try{$q=$c->prepare('UPDATE usuarios SET email_verificado_em=NOW() WHERE id=?');$q->bind_param('i',$uid);$q->execute();$q=$c->prepare('UPDATE verificacoes_email SET usado_em=NOW() WHERE id=?');$q->bind_param('i',$id);$q->execute();$c->commit();registrarAuditoria('email_confirmado','usuario',$uid);header('Location:index.php?cadastro_confirmado=1');exit;}catch(Throwable $e){$c->rollback();throw $e;}}
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Confirmar cadastro</title><link rel="stylesheet" href="assets/css/style.css"></head><body class="login-body"><div class="login-card"><div class="login-logo">CP</div><h1>Confirmar cadastro</h1><p>Digite o código de 6 dígitos enviado ao seu e-mail.</p><?php if($erro):?><div class="alert alert-danger"><?=e($erro)?></div><?php endif;?><form method="post" class="form"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><label>E-mail<input type="email" name="email" required value="<?=e($email)?>"></label><label>Código<input name="codigo" required pattern="[0-9]{6}" maxlength="6" inputmode="numeric"></label><button class="btn btn-primary">Confirmar cadastro</button></form><div class="auth-links auth-links-single"><a href="index.php">Voltar ao login</a></div></div></body></html>
