-- Acrescenta Retorno ao final do ENUM, preservando as opções e os registros existentes.
USE controle_prazos;

ALTER TABLE prazos
    MODIFY COLUMN status ENUM('Novo','Em andamento','Concluído','Retorno') NOT NULL DEFAULT 'Novo',
    ALGORITHM=INPLACE,
    LOCK=NONE;
