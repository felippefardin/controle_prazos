$ErrorActionPreference = 'Stop'

$destinoRede = 'S:\ATUACAO MEIO\Divisao de Apoio Administrativo\Felippe.andreata\Backup controle prazos app'
$projeto = 'C:\xampp\htdocs\controle_prazos'
$segredo = 'C:\xampp\controle_prazos_email_secret.php'
$mysqldump = 'C:\xampp\mysql\bin\mysqldump.exe'
$carimbo = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'
$nomeBackup = "controle_prazos_$carimbo"
$baseTemporaria = 'C:\xampp\temp\controle_prazos_backup'
$pastaTemporaria = Join-Path $baseTemporaria $nomeBackup
$arquivoFinal = Join-Path $destinoRede ($nomeBackup + '.zip')
$arquivoHash = Join-Path $destinoRede 'ultimo_hash_backup.txt'

if (-not (Test-Path -LiteralPath $destinoRede)) { throw "Pasta de rede indisponível: $destinoRede" }
if (-not (Test-Path -LiteralPath $projeto)) { throw "Projeto não encontrado: $projeto" }
if (-not (Test-Path -LiteralPath $mysqldump)) { throw 'Ferramenta de backup do MySQL não encontrada.' }

New-Item -ItemType Directory -Path $pastaTemporaria -Force | Out-Null
try {
    $pastaBanco = Join-Path $pastaTemporaria 'banco'
    $pastaSistema = Join-Path $pastaTemporaria 'sistema'
    $pastaSegredo = Join-Path $pastaTemporaria 'segredo'
    New-Item -ItemType Directory -Path $pastaBanco, $pastaSistema, $pastaSegredo -Force | Out-Null

    $arquivoSql = Join-Path $pastaBanco 'controle_prazos.sql'
    & $mysqldump --host=localhost --user=root --single-transaction --routines --triggers --skip-comments --databases controle_prazos --result-file=$arquivoSql
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $arquivoSql)) { throw 'Falha ao gerar o backup do banco.' }

    # Cria uma assinatura do banco, dos arquivos do sistema e da chave local.
    # Assim, uma nova cópia só é gerada quando o estado realmente mudou.
    $componentesHash = [System.Collections.Generic.List[string]]::new()
    $componentesHash.Add('BANCO|' + (Get-FileHash -LiteralPath $arquivoSql -Algorithm SHA256).Hash)
    Get-ChildItem -LiteralPath $projeto -File -Recurse | Sort-Object FullName | ForEach-Object {
        $relativo = $_.FullName.Substring($projeto.Length).TrimStart('\')
        $componentesHash.Add('SISTEMA|' + $relativo + '|' + (Get-FileHash -LiteralPath $_.FullName -Algorithm SHA256).Hash)
    }
    if (Test-Path -LiteralPath $segredo) {
        $componentesHash.Add('SEGREDO|' + (Get-FileHash -LiteralPath $segredo -Algorithm SHA256).Hash)
    }
    $sha = [System.Security.Cryptography.SHA256]::Create()
    try {
        $bytes = [System.Text.Encoding]::UTF8.GetBytes(($componentesHash -join "`n"))
        $hashAtual = ([System.BitConverter]::ToString($sha.ComputeHash($bytes))).Replace('-', '').ToLowerInvariant()
    } finally { $sha.Dispose() }

    $hashAnterior = if (Test-Path -LiteralPath $arquivoHash) { (Get-Content -Raw -LiteralPath $arquivoHash).Trim() } else { '' }
    if ($hashAnterior -eq $hashAtual) {
        $log = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') SEM_ALTERACOES Nenhum novo backup necessário."
        Add-Content -LiteralPath (Join-Path $destinoRede 'historico_backup.log') -Value $log -Encoding utf8
        Write-Output 'BACKUP_IGNORADO=SEM_ALTERACOES'
        return
    }

    Copy-Item -LiteralPath $projeto -Destination $pastaSistema -Recurse -Force
    if (Test-Path -LiteralPath $segredo) { Copy-Item -LiteralPath $segredo -Destination $pastaSegredo -Force }

    $manifesto = @(
        'Backup completo do Controle de Prazos'
        "Criado em: $(Get-Date -Format 'dd/MM/yyyy HH:mm:ss')"
        "Computador: $env:COMPUTERNAME"
        "Usuário: $env:USERDOMAIN\$env:USERNAME"
        'Conteúdo: banco MySQL, arquivos do sistema e chave local de criptografia.'
    )
    Set-Content -LiteralPath (Join-Path $pastaTemporaria 'LEIA-ME.txt') -Value $manifesto -Encoding utf8

    Compress-Archive -Path (Join-Path $pastaTemporaria '*') -DestinationPath $arquivoFinal -CompressionLevel Optimal -Force
    if (-not (Test-Path -LiteralPath $arquivoFinal)) { throw 'O arquivo compactado não foi criado.' }
    Set-Content -LiteralPath $arquivoHash -Value $hashAtual -Encoding ascii

    $log = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') SUCESSO $arquivoFinal"
    Add-Content -LiteralPath (Join-Path $destinoRede 'historico_backup.log') -Value $log -Encoding utf8
    Write-Output "BACKUP_OK=$arquivoFinal"
}
catch {
    $mensagem = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') ERRO $($_.Exception.Message)"
    if (Test-Path -LiteralPath $destinoRede) { Add-Content -LiteralPath (Join-Path $destinoRede 'historico_backup.log') -Value $mensagem -Encoding utf8 }
    throw
}
finally {
    $resolvido = [System.IO.Path]::GetFullPath($pastaTemporaria)
    if ($resolvido.StartsWith('C:\xampp\temp\controle_prazos_backup\', [System.StringComparison]::OrdinalIgnoreCase) -and (Test-Path -LiteralPath $resolvido)) {
        Remove-Item -LiteralPath $resolvido -Recurse -Force
    }
}
