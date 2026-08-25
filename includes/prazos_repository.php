<?php
declare(strict_types=1);

function dataFiltroValida(?string $data): ?string
{
    if (!$data) {
        return null;
    }
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
    return $dt && $dt->format('Y-m-d') === $data ? $data : null;
}

function normalizarPeriodo(?string $dataInicio, ?string $dataFim): array
{
    $dataInicio = dataFiltroValida($dataInicio);
    $dataFim = dataFiltroValida($dataFim);
    if ($dataInicio && $dataFim && $dataInicio > $dataFim) {
        [$dataInicio, $dataFim] = [$dataFim, $dataInicio];
    }
    return [$dataInicio, $dataFim];
}

function listarPrazos(string $busca = '', string $filtro = 'todos', ?string $dataInicio = null, ?string $dataFim = null): array
{
    $filtrosValidos = ['todos', 'novos', 'vencidos', 'hoje', 'cinco_dias', 'trinta_dias', 'proximos', 'em_dia', 'concluidos'];
    if (!in_array($filtro, $filtrosValidos, true)) {
        $filtro = 'todos';
    }

    $where = [];
    $params = [];
    $types = '';
    [$dataInicio, $dataFim] = normalizarPeriodo($dataInicio, $dataFim);

    if ($busca !== '') {
        $where[] = '(p.numero_processo LIKE ? OR p.assunto LIKE ? OR proc.procuradores_nomes LIKE ?)';
        $like = "%{$busca}%";
        $params = [$like, $like, $like];
        $types = 'sss';
    }

    switch ($filtro) {
        case 'novos':
            $where[] = "p.status = 'Novo'";
            break;
        case 'vencidos':
            $where[] = "p.status <> 'Concluído' AND p.data_vencimento < CURDATE()";
            break;
        case 'hoje':
            $where[] = "p.status <> 'Concluído' AND p.data_vencimento = CURDATE()";
            break;
        case 'cinco_dias':
            $where[] = "p.status <> 'Concluído' AND p.data_vencimento > CURDATE() AND p.data_vencimento <= DATE_ADD(CURDATE(), INTERVAL 5 DAY)";
            break;
        case 'trinta_dias':
            $where[] = "p.status <> 'Concluído' AND p.data_vencimento > DATE_ADD(CURDATE(), INTERVAL 5 DAY) AND p.data_vencimento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            break;
        case 'proximos':
            $where[] = "p.status <> 'Concluído' AND DATEDIFF(p.data_vencimento, CURDATE()) BETWEEN 1 AND 30";
            break;
        case 'em_dia':
            $where[] = "p.status <> 'Concluído' AND p.data_vencimento > DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            break;
        case 'concluidos':
            $where[] = "p.status = 'Concluído'";
            break;
    }

    if ($dataInicio) {
        $where[] = 'p.data_vencimento >= ?';
        $params[] = $dataInicio;
        $types .= 's';
    }

    if ($dataFim) {
        $where[] = 'p.data_vencimento <= ?';
        $params[] = $dataFim;
        $types .= 's';
    }

    $sql = 'SELECT p.*, proc.procuradores_nomes
            FROM prazos p
            LEFT JOIN (
                SELECT pp.prazo_id, GROUP_CONCAT(pr.nome ORDER BY pr.nome SEPARATOR ", ") AS procuradores_nomes
                FROM prazo_procuradores pp JOIN procuradores pr ON pr.id = pp.procurador_id
                GROUP BY pp.prazo_id
            ) proc ON proc.prazo_id = p.id';

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= " ORDER BY (p.status = 'Concluído') ASC, p.data_vencimento ASC, p.id DESC";

    $stmt = db()->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
