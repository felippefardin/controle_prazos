USE controle_prazos;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS cpf VARCHAR(14) NULL AFTER email;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS nome_usuario VARCHAR(60) NULL AFTER cpf;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS matricula VARCHAR(50) NULL AFTER nome_usuario;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS email_verificado_em DATETIME NULL AFTER senha;
UPDATE usuarios SET email_verificado_em = NOW() WHERE email_verificado_em IS NULL;
UPDATE usuarios SET nome_usuario = CONCAT('usuario', id) WHERE nome_usuario IS NULL OR nome_usuario = '';
ALTER TABLE usuarios MODIFY nome_usuario VARCHAR(60) NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_usuarios_nome_usuario ON usuarios(nome_usuario);
CREATE UNIQUE INDEX IF NOT EXISTS uq_usuarios_cpf ON usuarios(cpf);
CREATE UNIQUE INDEX IF NOT EXISTS uq_usuarios_matricula ON usuarios(matricula);

CREATE TABLE IF NOT EXISTS verificacoes_email (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, usuario_id INT UNSIGNED NOT NULL,
 tipo ENUM('cadastro','vinculo_email') NOT NULL, codigo_hash VARCHAR(255) NOT NULL,
 expira_em DATETIME NOT NULL, tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0,
 usado_em DATETIME NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;
