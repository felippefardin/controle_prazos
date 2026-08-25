USE controle_prazos;

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
