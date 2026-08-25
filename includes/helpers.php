<?php
declare(strict_types=1);

function e(?string $valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function dataBr(?string $data): string
{
    if (!$data) return '-';
    $dt = DateTime::createFromFormat('Y-m-d', $data);
    return $dt ? $dt->format('d/m/Y') : $data;
}

function diasRestantes(string $dataVencimento): int
{
    $hoje = new DateTime('today');
    $fim = new DateTime($dataVencimento);
    return (int)$hoje->diff($fim)->format('%r%a');
}

function statusPrazo(string $dataVencimento, string $status): array
{
    if ($status === 'Concluído') {
        return ['classe' => 'status-concluido', 'texto' => 'CONCLUÍDO', 'icone' => '✓'];
    }

    $dias = diasRestantes($dataVencimento);

    if ($dias < 0) {
        $n = abs($dias);
        return ['classe' => 'status-vencido', 'texto' => "VENCIDO HÁ {$n} " . ($n === 1 ? 'DIA' : 'DIAS'), 'icone' => '!'];
    }

    if ($dias === 0) {
        return ['classe' => 'status-hoje', 'texto' => 'VENCE HOJE', 'icone' => '!'];
    }

    if ($dias === 1) {
        return ['classe' => 'status-amanha', 'texto' => 'VENCE AMANHÃ', 'icone' => '!'];
    }

    if ($dias <= 5) {
        return ['classe' => 'status-atencao', 'texto' => "{$dias} DIAS RESTANTES", 'icone' => '•'];
    }

    if ($dias <= 30) {
        return ['classe' => 'status-30-dias', 'texto' => "{$dias} DIAS RESTANTES", 'icone' => '•'];
    }

    return ['classe' => 'status-em-dia', 'texto' => "{$dias} DIAS RESTANTES", 'icone' => '✓'];
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validarCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Sessão expirada. Atualize a página e tente novamente.');
    }
}

function criptografarCpf(string $cpf): string { require_once __DIR__.'/../config.php'; $iv=random_bytes(12);$tag='';$c=openssl_encrypt($cpf,'aes-256-gcm',EMAIL_ENCRYPTION_KEY,OPENSSL_RAW_DATA,$iv,$tag);return base64_encode($iv.$tag.$c); }
function hashCpf(string $cpf): string { require_once __DIR__.'/../config.php'; return hash_hmac('sha256',$cpf,EMAIL_ENCRYPTION_KEY); }
function mascararCpf(?string $valor): string { if(!$valor)return '-'; require_once __DIR__.'/../config.php';$r=base64_decode($valor,true);if($r===false||strlen($r)<29)return '***.***.***-**';$cpf=openssl_decrypt(substr($r,28),'aes-256-gcm',EMAIL_ENCRYPTION_KEY,OPENSSL_RAW_DATA,substr($r,0,12),substr($r,12,16));return $cpf?substr($cpf,0,3).'.***.***-'.substr($cpf,-2):'***.***.***-**'; }
function registrarAuditoria(string $acao,?string $entidade=null,$entidadeId=null,array $detalhes=[]): void { if(!function_exists('db'))return;$ator=(int)($_SESSION['admin_original_id']??$_SESSION['usuario_id']??0)?:null;$efetivo=(int)($_SESSION['usuario_id']??0)?:null;$json=$detalhes?json_encode($detalhes,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):null;$ip=$_SERVER['REMOTE_ADDR']??null;$stmt=db()->prepare('INSERT INTO auditoria(usuario_id,usuario_efetivo_id,acao,entidade,entidade_id,detalhes,ip) VALUES(?,?,?,?,?,?,?)');$eid=$entidadeId===null?null:(string)$entidadeId;$stmt->bind_param('iisssss',$ator,$efetivo,$acao,$entidade,$eid,$json,$ip);$stmt->execute(); }
