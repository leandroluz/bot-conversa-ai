# adianti8.3-docker
Ambiente Docker para o Adianti Framework 8.3 com PostgreSQL 17 e pgAdmin pré-configurados.

## Requisitos
- Docker e Docker Compose instalados.

## Como iniciar
1. Suba os contêineres (constrói as imagens na primeira execução):
```bash
docker compose up -d --build
```
2. Instale as dependências do projeto com o Composer (execute após os contêineres estarem rodando):
```bash
docker compose exec app composer install
```

## Serviços e acessos
- Aplicação Adianti: http://localhost:8080
- PostgreSQL: porta `5432`, banco `adianti`, usuário `admin`, senha `admin`
- pgAdmin: http://localhost:5050 (login `admin@admin.com` / senha `admin`)

Os scripts SQL em `app/database` são carregados automaticamente na criação do banco.

## Comandos úteis
- Ver logs: `docker compose logs -f`
- Parar e remover contêineres e volumes nomeados: `docker compose down -v`
