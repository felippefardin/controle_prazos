<?php
declare(strict_types=1);

/**
 * Consome a sinalização criada exclusivamente após um login bem-sucedido.
 * Usa os mesmos dias restantes calculados pela consulta existente do dashboard.
 * Não consulta nem altera o banco de dados.
 */
function consumirAlertaVencimentoLogin(array &$sessao, array $proximos): array
{
    if (empty($sessao['usuario_id']) || empty($sessao['alerta_vencimento_login_pendente'])) {
        return [];
    }

    // Consome mesmo sem vencimentos amanhã: o aviso só pertence à chegada do login.
    unset($sessao['alerta_vencimento_login_pendente']);

    return array_values(array_filter($proximos, static fn(array $prazo): bool =>
        (int)($prazo['dias_restantes'] ?? 0) === 1
        && ($prazo['status'] ?? '') !== 'Concluído'
    ));
}
