# Aiqfome Challenge API

API RESTful para gerenciamento de produtos favoritos dos clientes, desenvolvida como parte do desafio técnico para desenvolvedor backend na Aiqfome.

## Visão Geral

Esta API contém:
- **Gerenciamento de clientes**: CRUD completo de clientes
- **Autenticação JWT**: Sistema de autenticação com tokens seguros
- **Produtos externos**: Integração com FakeStore API para listagem de produtos
- **Favoritos**: Sistema de produtos favoritos por cliente com cache integrado
- **Documentação**: OpenAPI/Swagger integrado

## Arquitetura

O projeto segue os princípios de **Clean Architecture**, organizando o código em camadas bem definidas:

```
src/
├── Domain/           # Entidades de negócio
│   └── Entities/     # Client, FavoriteProduct
├── Application/      # Regras de negócio
│   ├── Dtos/         # Data Transfer Objects
│   ├── Ports/        # Interfaces (Repository)
│   └── Services/     # Serviços de aplicação
└── Infrastructure/   # Implementações técnicas
    ├── Cache/        # Implementação Redis
    ├── Drivers/      # Conexões BD/Redis
    ├── Http/         # Controllers, Middleware
    └── Repository/   # Implementações dos repositórios
```

Essa organização permite o desacoplamento das partes integrantes do sistema, ou seja, podemos a qualquer momento mudar a implementação do banco de dados, cache, consultas a apis externas sem impactar no domínio ou lógica da aplicação.

O sistema de cache também permite a não acoplação ao repositório, sendo possível apenas modificar no DI se queremos usar o Decorator que adiciona o cache ou a implementação pura com o banco.

## Tecnologias Utilizadas

- **PHP 8.2+**
- **Slim Framework** - Microframework para APIs
- **PostgreSQL** - Banco de dados
- **Redis** - Storage cache
- **JWT** - Autenticação via JSON Header
- **OpenAPI/Swagger** - Documentação da API
- **PHPStan** - Análise estática de código

## Pré-requisitos

### Para execução via Docker (Recomendado):
- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)

### Para execução manual:
- PHP 8.2 ou superior
- PostgreSQL 12+
- Redis 6+
- Composer

## Instalação e Execução

### Método 1: Docker (Recomendado)

1. **Configure as variáveis de ambiente** (opcional):
```
Se você modificou algo na configuração do ambiente local de desenvolvimento é necessário alterar também no arquivo .env na respectiva chave de configuração
```

2. **Suba os containers**:
```bash
docker-compose up -d
```

3. **Acesse a aplicação**:
- API: http://localhost:8080
- Documentação: http://localhost:8080/api/v1/docs

### Método 2: Execução Manual

1. **Configure o banco de dados**:
```bash
# PostgreSQL
createdb aiqfome
psql aiqfome < database/init.sql
```

2. **Configure Redis**:
```bash
redis-server --daemonize yes
```

3. **Configure variáveis de ambiente**:
```
Edite o arquivo .env com as configurações de acesso do seu banco e do seu servidor redis local.
```

4. **Execute o servidor**:
```bash
php -S localhost:8080 -t src
```

## Funcionalidades

### Autenticação
- **POST** `/api/v1/auth/login` - Login do cliente
- **POST** `/api/v1/auth/refresh` - Renovar token JWT (autenticação necessária)
- **GET** `/api/v1/auth/verify` - Verificar token JWT (autenticação necessária)
- **POST** `/api/v1/auth/logout` - Logout e invalidação do token (autenticação necessária)

### Clientes
- **GET** `/api/v1/clients` - Listar todos os clientes
- **GET** `/api/v1/clients/{id}` - Obter cliente específico
- **POST** `/api/v1/clients` - Criar novo cliente
- **PUT** `/api/v1/clients/{id}` - Atualizar cliente (autenticação necessária)
- **DELETE** `/api/v1/clients/{id}` - Deletar cliente (autenticação necessária)

### Produtos
- **GET** `/api/v1/products` - Listar produtos  (autenticação necessária)
- **GET** `/api/v1/products/{id}` - Obter produto específico (autenticação necessária)

### Favoritos
- **GET** `/api/v1/clients/{clientId}/favorites` - Listar favoritos do cliente (autenticação necessária)
- **POST** `/api/v1/clients/{clientId}/favorites` - Adicionar produto aos favoritos (autenticação necessária)
- **DELETE** `/api/v1/clients/{clientId}/favorites/{productId}` - Remover dos favoritos (autenticação necessária)

### Documentação
- **GET** `/api/v1/docs` - Interface Swagger UI
- **GET** `/api/v1/docs/openapi` - Especificação OpenAPI JSON

## Documentação da API

### Swagger UI
Acesse a documentação interativa em: http://localhost:8080/api/v1/docs

### OpenAPI Specification
Especificação JSON disponível em: http://localhost:8080/api/v1/docs/openapi

### Autenticação
A API utiliza JWT Bearer tokens em rotas protegidas. Por tanto para rotas que necessitam de autenticação inclua o header:
```
Authorization: Bearer {seu_token_jwt}
```

### Exemplo de Uso

1. **Criar um cliente**:
```bash
curl -X POST http://localhost:8080/api/v1/clients \
  -H "Content-Type: application/json" \
  -d '{
    "name": "igor Candido",
    "email": "igor.candido@aiqfome.com.br",
    "password": "123456"
  }'
```

2. **Fazer login**:
```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "igor.candido@aiqfome.com.br",
    "password": "123456"
  }'
```

3. **Adicionar produto aos favoritos**:
```bash
curl -X POST http://localhost:8080/api/v1/clients/1/favorites \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{"product_id": 1}'
```

### Postman Collection

Importe a collection localizada em `docs/postman_collection.json` no Postman para importar todos os endpoints no Postman.

## Testes

A aplicação possui testes de integração implementados com PHPUnit para os endpoints de auth e favorites na pasta `tests`:

### Executando os Testes

```bash
# Via Composer
composer run test

# Via Docker
docker-compose exec app vendor/bin/phpunit
```

### Cobertura

Os testes cobrem:
- **AuthController**: Login, refresh token, logout, verificação de token
- **FavoriteController**: Listagem, adição e remoção de favoritos
- **Validações**: Dados de entrada, casos de erro

## Análise Estática (PHPStan)
A aplicação tem um linter de análise estática que utilizei durante o desenvolvimento, se quiser validar é só rodar:
```bash
# Via Composer
composer run lint

# Via Docker
docker-compose exec app composer run lint
```
