# 🛍️ Emporia Shop API (Back-end)

A API do **Emporia Shop** é um serviço robusto de e-commerce desenvolvido em PHP e estruturado no framework **Laravel 11/13**. Ele gerencia o catálogo de produtos, controle atômico de estoque, autenticação via tokens e processamento de checkout e webhooks.

---

## 🛠️ Tecnologias Utilizadas

*   **Core:** PHP 8.3+ / Laravel 11/13
*   **Banco de Dados:** SQLite (rápido e autônomo para desenvolvimento local)
*   **Autenticação:** Laravel Sanctum (Tokens de acesso Bearer baseados em cabeçalho HTTP)
*   **Testes Automatizados:** Pest PHP (Suite completa de testes de feature e unidade)

---

## 🚀 Funcionalidades Principais

1.  **Catálogo de Produtos:** Rotas públicas para listar, buscar por termos, ordenar (preço, avaliação, relevância) e filtrar produtos por categoria.
2.  **Autenticação Segura:** Cadastro e Login de clientes utilizando criptografia `Hash` para senhas e geração de tokens temporários/permanentes.
3.  **Checkout Atômico:** Criação de pedidos com validação concorrente de estoque utilizando `lockForUpdate` do banco de dados, evitando vendas duplicadas de um mesmo produto em acessos simultâneos.
4.  **Painel Administrativo (CRUD):** Endpoints protegidos para administradores criarem, atualizarem e excluírem categorias e produtos (incluindo upload físico de imagens e deleção automática de imagens antigas do disco).
5.  **Webhooks de Pagamento:** Rota preparada para receber confirmações do gateway de pagamento, alterando o status do pedido e retornando o estoque para as prateleiras em caso de transação recusada ou devolvida.

---

## 📦 Instalação e Execução

### Pré-requisitos
*   PHP 8.3 ou superior instalado
*   Composer instalado
*   SQLite ativo no PHP

### Passos para Configuração

1.  **Instalar Dependências:**
    ```bash
    composer install
    ```

2.  **Configurar Variáveis de Ambiente:**
    Copie o arquivo de exemplo `.env.example` e crie o `.env`:
    ```bash
    cp .env.example .env
    ```
    *Certifique-se de configurar a conexão SQLite no `.env`:*
    ```env
    DB_CONNECTION=sqlite
    DB_DATABASE=/absolute/path/to/database/database.sqlite
    ```

3.  **Gerar a Chave da Aplicação:**
    ```bash
    php artisan key:generate
    ```

4.  **Criar o Banco de Dados SQLite (caso não exista):**
    ```bash
    touch database/database.sqlite
    ```

5.  **Executar Migrações e Alimentar o Banco (Seeder):**
    Este comando criará todas as tabelas e semeará os produtos premium com imagens reais do Unsplash e contas de demonstração:
    ```bash
    php artisan migrate:fresh --seed
    ```

6.  **Iniciar o Servidor:**
    ```bash
    php artisan serve
    ```
    *A API estará acessível em `http://localhost:8000/api`.*

---

## 👥 Contas de Demonstração (Seed)

Após rodar o seed, o banco conterá as seguintes contas para testes rápidos:

| Tipo | E-mail | Senha | Nome |
| :--- | :--- | :--- | :--- |
| **Cliente Padrão** | `marcondes@emporia.com` | `password123` | Marcondes Júnior |
| **Administrador** | `admin@emporia.com` | `password123` | Admin Emporia |

---

## 🧪 Rodando os Testes Automatizados

A suite possui **39 testes** que cobrem todos os controllers, middlewares, concorrência de estoque e regras de validação.

Para rodar os testes:
```bash
composer test
```
*ou*
```bash
./vendor/bin/pest
```

---

## 📘 Documentação dos Endpoints

Veja o arquivo [api_docs.md](file:///home/dev/Documentos/CODE/DEV/emporia-shop/emporia-shop-api/api_docs.md) para a lista e payload de todos os endpoints disponíveis na API (Autenticação, Catálogo, Checkout, Webhooks e Administrativo).
