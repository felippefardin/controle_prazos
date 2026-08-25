<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function smtpLer($socket, array $codigos): void {
    $resposta=''; do { $linha=fgets($socket,515); if($linha===false) break; $resposta.=$linha; } while(isset($linha[3]) && $linha[3]==='-');
    if(!in_array((int)substr($resposta,0,3),$codigos,true)) throw new RuntimeException('Falha no servidor de e-mail.');
}
function smtpComando($socket,string $comando,array $codigos): void { fwrite($socket,$comando."\r\n"); smtpLer($socket,$codigos); }
function enviarEmailSistema(string $destinatario,string $assunto,string $mensagem): bool {
    if(SMTP_PASS==='') return false;
    try {
        $s=stream_socket_client('ssl://smtp.gmail.com:465',$errno,$errstr,15); if(!$s) return false; stream_set_timeout($s,15);
        smtpLer($s,[220]); smtpComando($s,'EHLO localhost',[250]); smtpComando($s,'AUTH LOGIN',[334]);
        smtpComando($s,base64_encode(SMTP_USER),[334]); smtpComando($s,base64_encode(SMTP_PASS),[235]);
        smtpComando($s,'MAIL FROM:<'.SMTP_USER.'>',[250]); smtpComando($s,'RCPT TO:<'.$destinatario.'>',[250,251]); smtpComando($s,'DATA',[354]);
        $headers='From: '.APP_NAME.' <'.SMTP_USER.">\r\nTo: <{$destinatario}>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        smtpComando($s,$headers.'Subject: =?UTF-8?B?'.base64_encode($assunto)."?=\r\n\r\n".str_replace("\n.","\n..",$mensagem)."\r\n.",[250]);
        smtpComando($s,'QUIT',[221]); fclose($s); return true;
    } catch(Throwable $e) { error_log($e->getMessage()); return false; }
}
function enviarCodigoVerificacao(string $email,string $nome,string $codigo,string $finalidade): bool {
    return enviarEmailSistema($email,$finalidade,"Olá, {$nome}!\n\nSeu código é: {$codigo}\n\nEle expira em 15 minutos e pode ser usado uma única vez.");
}
function enviarCodigoRecuperacao(string $destinatario,string $nome,string $codigo): bool {
    return enviarCodigoVerificacao($destinatario,$nome,$codigo,'Código para redefinir sua senha');
}
