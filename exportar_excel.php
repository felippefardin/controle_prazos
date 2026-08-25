<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/prazos_repository.php';

exigirLogin();

$busca = trim($_GET['busca'] ?? '');
$filtro = $_GET['filtro'] ?? 'todos';
[$dataInicio, $dataFim] = normalizarPeriodo($_GET['data_inicio'] ?? null, $_GET['data_fim'] ?? null);
$prazos = listarPrazos($busca, $filtro, $dataInicio, $dataFim);

function xml(string $valor): string
{
    return htmlspecialchars($valor, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function colunaExcel(int $indice): string
{
    $nome = '';
    while ($indice > 0) {
        $indice--;
        $nome = chr(65 + ($indice % 26)) . $nome;
        $indice = intdiv($indice, 26);
    }
    return $nome;
}

function dataExcel(string $data): int
{
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
    $base = new DateTimeImmutable('1899-12-30');
    return $dt ? (int)$base->diff($dt)->format('%r%a') : 0;
}

function criarXlsx(array $arquivos): string
{
    $saida = '';
    $diretorio = '';
    $offset = 0;

    foreach ($arquivos as $nome => $conteudo) {
        $nome = str_replace('\\', '/', $nome);
        $crc = crc32($conteudo);
        $tamanho = strlen($conteudo);
        $cabecalho = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0, $crc, $tamanho, $tamanho, strlen($nome), 0);
        $saida .= $cabecalho . $nome . $conteudo;
        $diretorio .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, 0, 0, $crc, $tamanho, $tamanho, strlen($nome), 0, 0, 0, 0, 0, $offset) . $nome;
        $offset = strlen($saida);
    }

    $inicioDiretorio = strlen($saida);
    $saida .= $diretorio;
    $saida .= pack('VvvvvVVv', 0x06054b50, 0, 0, count($arquivos), count($arquivos), strlen($diretorio), $inicioDiretorio, 0);
    return $saida;
}

$cabecalhos = ['Processo', 'Assunto', 'Entrada', 'Procuradores vinculados', 'Vencimento', 'Status', 'Situação', 'Observações'];
$linhas = [$cabecalhos];

foreach ($prazos as $prazo) {
    $situacao = statusPrazo($prazo['data_vencimento'], $prazo['status']);
    $linhas[] = [
        $prazo['numero_processo'],
        $prazo['assunto'],
        dataExcel($prazo['data_entrada']),
        $prazo['procuradores_nomes'] ?: 'Não vinculado',
        dataExcel($prazo['data_vencimento']),
        $prazo['status'],
        $situacao['texto'],
        $prazo['observacoes'] ?? '',
    ];
}

$sheetRows = '';
foreach ($linhas as $linhaIndex => $linha) {
    $numeroLinha = $linhaIndex + 1;
    $cells = '';
    foreach ($linha as $colunaIndex => $valor) {
        $ref = colunaExcel($colunaIndex + 1) . $numeroLinha;
        $ehData = $linhaIndex > 0 && in_array($colunaIndex, [2, 4], true);
        if ($ehData) {
            $cells .= '<c r="' . $ref . '" s="3"><v>' . (int)$valor . '</v></c>';
        } else {
            $style = $linhaIndex === 0 ? ' s="1"' : ' s="2"';
            $cells .= '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t xml:space="preserve">' . xml((string)$valor) . '</t></is></c>';
        }
    }
    $sheetRows .= '<row r="' . $numeroLinha . '">' . $cells . '</row>';
}

$sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
    . '<cols><col min="1" max="1" width="24" customWidth="1"/><col min="2" max="2" width="35" customWidth="1"/>'
    . '<col min="3" max="3" width="13" customWidth="1"/><col min="4" max="4" width="24" customWidth="1"/>'
    . '<col min="5" max="5" width="13" customWidth="1"/><col min="6" max="7" width="22" customWidth="1"/>'
    . '<col min="8" max="8" width="45" customWidth="1"/></cols>'
    . '<sheetData>' . $sheetRows . '</sheetData><autoFilter ref="A1:H' . max(1, count($linhas)) . '"/>'
    . '</worksheet>';

$arquivos = [
    '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
    '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
    'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Prazos" sheetId="1" r:id="rId1"/></sheets></workbook>',
    'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
    'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="dd/mm/yyyy"/></numFmts><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF00789E"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment vertical="top"/></xf></cellXfs></styleSheet>',
    'xl/worksheets/sheet1.xml' => $sheet,
];

$arquivo = criarXlsx($arquivos);
$nome = 'prazos_' . date('Y-m-d_H-i') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nome . '"');
header('Content-Length: ' . strlen($arquivo));
header('Cache-Control: no-store, no-cache, must-revalidate');
echo $arquivo;
exit;
