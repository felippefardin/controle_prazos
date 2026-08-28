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
        $dominio=substr(strrchr(SMTP_USER,'@')?:'@localhost',1);$messageId='<'.bin2hex(random_bytes(12)).'.'.time().'@'.$dominio.'>';
        $headers='From: '.APP_NAME.' <'.SMTP_USER.">\r\nReply-To: ".SMTP_USER."\r\nTo: <{$destinatario}>\r\nDate: ".date(DATE_RFC2822)."\r\nMessage-ID: {$messageId}\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n";
        $corpo=chunk_split(base64_encode($mensagem),76,"\r\n");
        smtpComando($s,$headers.'Subject: =?UTF-8?B?'.base64_encode($assunto)."?=\r\n\r\n".$corpo.".",[250]);
        smtpComando($s,'QUIT',[221]); fclose($s); return true;
    } catch(Throwable $e) { error_log($e->getMessage()); return false; }
}
function enviarCodigoVerificacao(string $email,string $nome,string $codigo,string $finalidade): bool {
    $nomeSeguro=htmlspecialchars($nome,ENT_QUOTES,'UTF-8');$codigoSeguro=htmlspecialchars($codigo,ENT_QUOTES,'UTF-8');$finalidadeSegura=htmlspecialchars($finalidade,ENT_QUOTES,'UTF-8');
    $html='<!doctype html><html lang="pt-BR"><body style="margin:0;background:#f3f5f7;font-family:Arial,sans-serif;color:#18212a"><div style="max-width:560px;margin:30px auto;background:#fff;border:1px solid #dce2e7;border-radius:14px;overflow:hidden"><div style="padding:22px 28px;background:#101820;color:#fff"><strong style="font-size:20px">Controle de Prazos</strong></div><div style="padding:28px"><h1 style="margin:0 0 14px;font-size:23px">'.$finalidadeSegura.'</h1><p>Olá, <strong>'.$nomeSeguro.'</strong>.</p><p>Use o código abaixo para continuar:</p><div style="margin:24px 0;padding:18px;text-align:center;border-radius:10px;background:#eaf8ff;color:#006b91;font-size:34px;font-weight:800;letter-spacing:8px">'.$codigoSeguro.'</div><p style="color:#52616d">O código expira em 15 minutos e só pode ser utilizado uma vez.</p><p style="color:#52616d">Se você não fez esta solicitação, ignore este e-mail.</p></div></div></body></html>';
    return enviarEmailSistema($email,$finalidade,$html);
}
function enviarCodigoRecuperacao(string $destinatario,string $nome,string $codigo): bool {
    return enviarCodigoVerificacao($destinatario,$nome,$codigo,'Código para redefinir sua senha');
}
