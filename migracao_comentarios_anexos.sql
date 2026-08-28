USE controle_prazos;

CREATE TABLE IF NOT EXISTS processo_comentarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prazo_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NULL,
    comentario TEXT NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_comentarios_processo (prazo_id, criado_em),
    CONSTRAINT fk_comentarios_processo FOREIGN KEY (prazo_id) REFERENCES prazos(id) ON DELETE CASCADE,
    CONSTRAINT fk_comentarios_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS processo_anexos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prazo_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NULL,
    nome_original VARCHAR(255) NOT NULL,
    nome_armazenado CHAR(64) NOT NULL UNIQUE,
    mime_type VARCHAR(120) NOT NULL,
    tamanho BIGINT UNSIGNED NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_anexos_processo (prazo_id, criado_em),
    CONSTRAINT fk_anexos_processo FOREIGN KEY (prazo_id) REFERENCES prazos(id) ON DELETE CASCADE,
    CONSTRAINT fk_anexos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;
