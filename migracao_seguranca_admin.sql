USE controle_prazos;
DROP INDEX uq_usuarios_cpf ON usuarios;
ALTER TABLE usuarios MODIFY cpf TEXT NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS cpf_hash CHAR(64) NULL AFTER cpf;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS perfil ENUM('usuario','admin') NOT NULL DEFAULT 'usuario' AFTER matricula;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS situacao ENUM('pendente','aprovado','bloqueado') NOT NULL DEFAULT 'aprovado' AFTER perfil;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS tentativas_login TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER situacao;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS bloqueado_em DATETIME NULL AFTER tentativas_login;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS ultimo_login_em DATETIME NULL AFTER bloqueado_em;
UPDATE usuarios SET situacao='aprovado' WHERE situacao IS NULL OR situacao='pendente';
CREATE UNIQUE INDEX IF NOT EXISTS uq_usuarios_cpf_hash ON usuarios(cpf_hash);

CREATE TABLE IF NOT EXISTS auditoria (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT UNSIGNED NULL,
 usuario_efetivo_id INT UNSIGNED NULL,
 acao VARCHAR(80) NOT NULL,
 entidade VARCHAR(80) NULL,
 entidade_id VARCHAR(80) NULL,
 detalhes TEXT NULL,
 ip VARCHAR(45) NULL,
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_auditoria_data(criado_em), INDEX idx_auditoria_usuario(usuario_id), INDEX idx_auditoria_acao(acao),
 CONSTRAINT fk_auditoria_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
 CONSTRAINT fk_auditoria_efetivo FOREIGN KEY(usuario_efetivo_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;
