CREATE DATABASE IF NOT EXISTS controle_prazos
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE controle_prazos;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS prazos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero_processo VARCHAR(100) NOT NULL,
    assunto VARCHAR(255) NOT NULL,
    responsavel_id INT UNSIGNED NULL,
    data_entrada DATE NOT NULL,
    data_vencimento DATE NOT NULL,
    status ENUM('Novo','Em andamento','Concluído') NOT NULL DEFAULT 'Novo',
    observacoes TEXT NULL,
    criado_por INT UNSIGNED NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_prazos_vencimento (data_vencimento),
    INDEX idx_prazos_status (status),
    INDEX idx_prazos_numero (numero_processo),
    CONSTRAINT fk_prazos_responsavel FOREIGN KEY (responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_prazos_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS procuradores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    oab VARCHAR(50) NULL,
    email VARCHAR(190) NULL,
    telefone VARCHAR(30) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_procuradores_nome (nome)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS prazo_procuradores (
    prazo_id INT UNSIGNED NOT NULL,
    procurador_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (prazo_id, procurador_id),
    CONSTRAINT fk_prazo_procuradores_prazo FOREIGN KEY (prazo_id) REFERENCES prazos(id) ON DELETE CASCADE,
    CONSTRAINT fk_prazo_procuradores_procurador FOREIGN KEY (procurador_id) REFERENCES procuradores(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS recuperacoes_senha (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    codigo_hash VARCHAR(255) NOT NULL,
    expira_em DATETIME NOT NULL,
    tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0,
    usado_em DATETIME NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recuperacoes_usuario (usuario_id, criado_em),
    INDEX idx_recuperacoes_expiracao (expira_em),
    CONSTRAINT fk_recuperacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Usuário inicial:
-- e-mail: admin@local
-- senha: admin123
INSERT INTO usuarios (nome, email, senha)
SELECT 'Administrador', 'admin@local', '$2y$12$AW51aIRHOGQkkfvsII5DvOhSLcL.2rZhTy8Hkxp6D/NGbi.3Ho/a2'
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE email = 'admin@local');

-- Exemplos opcionais para testar o painel
INSERT INTO prazos (numero_processo, assunto, responsavel_id, data_entrada, data_vencimento, status, observacoes, criado_por)
SELECT '0001234-25.2026.8.08.0000', 'Prazo para manifestação', 1, CURDATE(), CURDATE(), 'Novo', 'Exemplo de prazo que vence hoje.', 1
WHERE NOT EXISTS (SELECT 1 FROM prazos WHERE numero_processo = '0001234-25.2026.8.08.0000');

INSERT INTO prazos (numero_processo, assunto, responsavel_id, data_entrada, data_vencimento, status, observacoes, criado_por)
SELECT '0004567-13.2026.8.08.0000', 'Análise de documento', 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'Em andamento', 'Exemplo para testar alerta de até 5 dias.', 1
WHERE NOT EXISTS (SELECT 1 FROM prazos WHERE numero_processo = '0004567-13.2026.8.08.0000');
