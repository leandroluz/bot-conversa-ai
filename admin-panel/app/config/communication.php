<?php
return [
    // 'host'  =>  "",
    // 'port'  =>  "",
    // 'name'  =>  "app/database/communication.db",
    // 'user'  =>  "",
    // 'pass'  =>  "",
    // 'type'  =>  "sqlite",
    // 'prep'  =>  "1"
    'host' => "postgres",  # Nome do serviço no docker-compose
    'port' => "5432",  # Porta padrão do PostgreSQL
    'name' => "atendente",  # Nome do banco de dados
    'user' => "atendente",  # Usuário do banco
    'pass' => "atendente123",  # Senha do banco
    'type' => "pgsql",  # Tipo de banco de dados
    'prep' => "1"  # Utilizar consultas preparadas
    // 'slog' => "SystemSqlLog"  # Log SQL (opcional)
];
