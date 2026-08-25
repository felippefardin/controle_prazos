<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php'; require_once __DIR__ . '/includes/db.php'; require_once __DIR__ . '/includes/helpers.php';
exigirLogin(); validarCsrf();
$id=(int)($_POST['id']??0); $nome=trim($_POST['nome']??''); $oab=trim($_POST['oab']??''); $email=trim($_POST['email']??''); $telefone=trim($_POST['telefone']??''); $ativo=isset($_POST['ativo'])?1:0;
if ($nome==='' || ($email!=='' && !filter_var($email, FILTER_VALIDATE_EMAIL))) exit('Dados inválidos.');
$conn=db();
if ($id) { $stmt=$conn->prepare('UPDATE procuradores SET nome=?, oab=?, email=?, telefone=?, ativo=? WHERE id=?'); $stmt->bind_param('ssssii',$nome,$oab,$email,$telefone,$ativo,$id); }
else { $stmt=$conn->prepare('INSERT INTO procuradores (nome,oab,email,telefone,ativo) VALUES (?,?,?,?,?)'); $stmt->bind_param('ssssi',$nome,$oab,$email,$telefone,$ativo); }
$stmt->execute(); if(!$id)$id=$conn->insert_id; registrarAuditoria('procurador_salvo','procurador',$id,['nome'=>$nome,'ativo'=>$ativo]); header('Location: procuradores.php?salvo=1'); exit;
