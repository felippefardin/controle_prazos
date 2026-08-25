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

function textoPdf(string $texto): string
{
    $convertido = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $texto);
    return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $convertido ?: $texto);
}

function cortarTexto(string $texto, int $limite): array
{
    $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? $texto);
    if ($texto === '') {
        return ['-'];
    }

    $linhas = [];
    while (mb_strlen($texto, 'UTF-8') > $limite && count($linhas) < 2) {
        $trecho = mb_substr($texto, 0, $limite + 1, 'UTF-8');
        $posicao = mb_strrpos($trecho, ' ', 0, 'UTF-8');
        if ($posicao === false || $posicao < (int)($limite * .55)) {
            $posicao = $limite;
        }
        $linhas[] = trim(mb_substr($texto, 0, $posicao, 'UTF-8'));
        $texto = trim(mb_substr($texto, $posicao, null, 'UTF-8'));
    }
    if ($texto !== '') {
        if (count($linhas) >= 2 && mb_strlen($texto, 'UTF-8') > $limite) {
            $texto = mb_substr($texto, 0, $limite - 3, 'UTF-8') . '...';
        }
        $linhas[] = $texto;
    }
    return array_slice($linhas, 0, 3);
}

function comandoTexto(float $x, float $y, string $texto, float $tamanho = 7, bool $negrito = false): string
{
    $fonte = $negrito ? '/F2' : '/F1';
    return "BT {$fonte} {$tamanho} Tf 1 0 0 1 " . round($x, 2) . ' ' . round($y, 2) . ' Tm (' . textoPdf($texto) . ") Tj ET\n";
}

function montarPdf(array $paginas): string
{
    $objetos = [];
    $objetos[1] = '<< /Type /Catalog /Pages 2 0 R >>';
    $filhos = [];
    foreach ($paginas as $indice => $_) {
        $filhos[] = (4 + ($indice * 2)) . ' 0 R';
    }
    $objetos[2] = '<< /Type /Pages /Kids [' . implode(' ', $filhos) . '] /Count ' . count($paginas) . ' >>';
    $objetos[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';

    foreach ($paginas as $indice => $conteudo) {
        $paginaId = 4 + ($indice * 2);
        $conteudoId = $paginaId + 1;
        $objetos[$paginaId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R /F2 ' . (4 + count($paginas) * 2) . ' 0 R >> >> /Contents ' . $conteudoId . ' 0 R >>';
        $objetos[$conteudoId] = '<< /Length ' . strlen($conteudo) . ">>\nstream\n" . $conteudo . "endstream";
    }
    $objetos[4 + count($paginas) * 2] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
    ksort($objetos);

    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [0];
    foreach ($objetos as $id => $objeto) {
        $offsets[$id] = strlen($pdf);
        $pdf .= $id . " 0 obj\n" . $objeto . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= 'xref' . "\n0 " . (count($objetos) + 1) . "\n0000000000 65535 f \n";
    for ($id = 1; $id <= count($objetos); $id++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
    }
    $pdf .= 'trailer << /Size ' . (count($objetos) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    return $pdf;
}

$colunas = [
    ['titulo' => 'Processo', 'largura' => 108, 'limite' => 25],
    ['titulo' => 'Assunto', 'largura' => 140, 'limite' => 35],
    ['titulo' => 'Entrada', 'largura' => 61, 'limite' => 12],
    ['titulo' => 'Procuradores', 'largura' => 100, 'limite' => 23],
    ['titulo' => 'Vencimento', 'largura' => 66, 'limite' => 12],
    ['titulo' => 'Status', 'largura' => 70, 'limite' => 16],
    ['titulo' => 'Situação', 'largura' => 108, 'limite' => 25],
    ['titulo' => 'Observações', 'largura' => 129, 'limite' => 32],
];

$linhas = [];
foreach ($prazos as $prazo) {
    $situacao = statusPrazo($prazo['data_vencimento'], $prazo['status']);
    $linhas[] = [
        $prazo['numero_processo'], $prazo['assunto'], dataBr($prazo['data_entrada']),
        $prazo['procuradores_nomes'] ?: 'Não vinculado', dataBr($prazo['data_vencimento']),
        $prazo['status'], $situacao['texto'], $prazo['observacoes'] ?: '-',
    ];
}

$paginas = [];
$conteudo = '';
$y = 0.0;
$numeroPagina = 0;

$iniciarPagina = function () use (&$conteudo, &$y, &$numeroPagina, $colunas, $busca, $filtro, $dataInicio, $dataFim): void {
    $numeroPagina++;
    $conteudo = "0.07 0.07 0.07 rg 0 0 842 595 re f\n";
    $conteudo .= "0.95 0.95 0.95 rg\n" . comandoTexto(30, 558, 'Relatório de Prazos', 17, true);
    $descricao = 'Gerado em ' . date('d/m/Y H:i') . ' | Filtro: ' . $filtro;
    if ($busca !== '') $descricao .= ' | Busca: ' . $busca;
    if ($dataInicio) $descricao .= ' | De: ' . dataBr($dataInicio);
    if ($dataFim) $descricao .= ' | Até: ' . dataBr($dataFim);
    $conteudo .= "0.67 0.67 0.67 rg\n" . comandoTexto(30, 541, $descricao, 8);
    $conteudo .= comandoTexto(754, 558, 'Página ' . $numeroPagina, 8);
    $y = 514;
    $x = 30;
    $conteudo .= "0 0.55 0.75 rg {$x} " . ($y - 3) . " 782 22 re f\n0.02 0.08 0.1 rg\n";
    foreach ($colunas as $coluna) {
        $conteudo .= comandoTexto($x + 4, $y + 4, $coluna['titulo'], 7, true);
        $x += $coluna['largura'];
    }
    $y -= 3;
};

$iniciarPagina();
if (!$linhas) {
    $conteudo .= "0.75 0.75 0.75 rg\n" . comandoTexto(30, 475, 'Nenhum prazo encontrado para os filtros selecionados.', 10);
}

foreach ($linhas as $indice => $linha) {
    $celulas = [];
    $maxLinhas = 1;
    foreach ($linha as $i => $valor) {
        $celulas[$i] = cortarTexto((string)$valor, $colunas[$i]['limite']);
        $maxLinhas = max($maxLinhas, count($celulas[$i]));
    }
    $altura = 8 + ($maxLinhas * 9);

    if ($y - $altura < 34) {
        $paginas[] = $conteudo;
        $iniciarPagina();
    }

    $fundo = $indice % 2 === 0 ? '0.12 0.12 0.12' : '0.15 0.15 0.15';
    $conteudo .= "{$fundo} rg 30 " . ($y - $altura) . " 782 {$altura} re f\n";
    $conteudo .= "0.28 0.28 0.28 RG 0.35 w 30 " . ($y - $altura) . " m 812 " . ($y - $altura) . " l S\n";
    $x = 30;
    $conteudo .= "0.91 0.91 0.91 rg\n";
    foreach ($celulas as $i => $textos) {
        foreach ($textos as $linhaTexto => $texto) {
            $conteudo .= comandoTexto($x + 4, $y - 12 - ($linhaTexto * 9), $texto, 6.5, $i === 0);
        }
        $x += $colunas[$i]['largura'];
    }
    $y -= $altura;
}

$paginas[] = $conteudo;
$arquivo = montarPdf($paginas);
$nome = 'prazos_' . date('Y-m-d_H-i') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $nome . '"');
header('Content-Length: ' . strlen($arquivo));
header('Cache-Control: no-store, no-cache, must-revalidate');
echo $arquivo;
exit;
