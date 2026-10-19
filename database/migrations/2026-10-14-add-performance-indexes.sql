CREATE INDEX idx_perifericos_deleted_at ON perifericos (deleted_at);
CREATE INDEX idx_perifericos_deleted_at_quantidade ON perifericos (deleted_at, quantidade);
CREATE INDEX idx_perifericos_deleted_at_tipo ON perifericos (deleted_at, tipo);
CREATE INDEX idx_perifericos_deleted_at_nome_ativo ON perifericos (deleted_at, nome_ativo);
CREATE INDEX idx_usuarios_email ON usuarios (email);
CREATE INDEX idx_usuarios_perfil ON usuarios (perfil);
