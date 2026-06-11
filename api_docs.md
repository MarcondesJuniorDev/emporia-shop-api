# Documentação da API - Emporia Shop

Esta é a documentação completa dos endpoints da API do Emporia Shop. 

*   **URL Base Local:** `http://localhost:8000/api`
*   **Formato de Comunicação:** `application/json` (exceto uploads de arquivos)

---

## 🔐 1. Autenticação (Laravel Sanctum)

A API utiliza autenticação baseada em tokens (Bearer Token). 
Para acessar as rotas protegidas (Pedidos e Administração), envie o token obtido no login ou registro no cabeçalho HTTP:

```http
Authorization: Bearer <seu_token_aqui>
Accept: application/json
```

---

## 🛍️ 2. Catálogo Público (Sem Autenticação)

### 📌 Listar e Filtrar Produtos
Retorna uma lista paginada de produtos ativos.

*   **Rota:** `GET /products`
*   **Parâmetros de Query (Opcionais):**
    *   `search` (string): Busca termo no nome ou descrição.
    *   `category` (string): Slug de uma categoria específica (ex: `eletronicos`).
    *   `sort_by` (string): Campo de ordenação (`price`, `created_at`, `name`). Padrão: `created_at`.
    *   `sort_order` (string): Direção (`asc`, `desc`). Padrão: `desc`.
    *   `per_page` (int): Quantidade de itens por página. Padrão: `12`.
*   **Resposta (200 OK):**
    ```json
    {
      "data": [
        {
          "id": 1,
          "name": "Caneca Especial",
          "slug": "caneca-especial",
          "description": "Uma caneca muito bacana.",
          "price": 49.9,
          "price_formatted": "R$ 49,90",
          "stock": 100,
          "image": "http://localhost:8000/storage/products/imagem.jpg",
          "is_active": true,
          "category": {
            "id": 2,
            "name": "Casa e Decoração",
            "slug": "casa-e-decoracao"
          }
        }
      ],
      "links": { ... },
      "meta": { ... }
    }
    ```

### 📌 Detalhes de um Produto
*   **Rota:** `GET /products/{slug}`
*   **Resposta (200 OK):** *(Mesma estrutura do objeto individual acima)*
*   **Resposta (404 Not Found):** Se o produto não existir ou estiver desativado (`is_active = false`).

### 📌 Listar Categorias Públicas
Retorna todas as categorias cadastradas ordenadas alfabeticamente.
*(Nota: Rota com cacheamento ativo de 24 horas para melhor performance).*

*   **Rota:** `GET /categories`
*   **Resposta (200 OK):**
    ```json
    {
      "data": [
        {
          "id": 1,
          "name": "Casa e Decoração",
          "slug": "casa-e-decoracao"
        }
      ]
    }
    ```

---

## 👤 3. Autenticação e Perfil

### 📌 Registrar Cliente
*   **Rota:** `POST /register`
*   **Payload (JSON):**
    ```json
    {
      "name": "João Silva",
      "email": "joao@email.com",
      "password": "password123",
      "password_confirmation": "password123"
    }
    ```
*   **Resposta (201 Created):**
    ```json
    {
      "access_token": "3|la8dJ...",
      "token_type": "Bearer",
      "user": {
        "id": 5,
        "name": "João Silva",
        "email": "joao@email.com",
        "created_at": "2026-06-11T12:00:00.000000Z"
      }
    }
    ```

### 📌 Login de Cliente
*   **Rota:** `POST /login`
*   **Payload (JSON):**
    ```json
    {
      "email": "joao@email.com",
      "password": "password123"
    }
    ```
*   **Resposta (200 OK):** *(Mesma estrutura do Register)*
*   **Resposta (401 Unauthorized):** Se as credenciais estiverem incorretas.

### 📌 Dados do Usuário Logado
*   **Rota:** `GET /me` (Requer Token)
*   **Resposta (200 OK):**
    ```json
    {
      "data": {
        "id": 5,
        "name": "João Silva",
        "email": "joao@email.com",
        "created_at": "2026-06-11T12:00:00.000000Z"
      }
    }
    ```

### 📌 Logout (Revogar Token)
*   **Rota:** `POST /logout` (Requer Token)
*   **Resposta (200 OK):**
    ```json
    {
      "message": "Sessão encerrada com sucesso."
    }
    ```

---

## 🛒 4. Pedidos e Checkout (Requer Token)

### 📌 Criar Pedido (Checkout)
Coloca um pedido no sistema, verificando estoque de forma atômica e reduzindo as unidades vendidas.
*(Nota: Dispara o evento assíncrono `OrderPlaced` para envio de confirmação em segundo plano).*

*   **Rota:** `POST /orders`
*   **Payload (JSON):**
    ```json
    {
      "shipping_address": "Rua das Flores, 123",
      "items": [
        {
          "product_id": 1,
          "quantity": 2
        }
      ]
    }
    ```
*   **Resposta (201 Created):**
    ```json
    {
      "data": {
        "id": 12,
        "status": "pending",
        "total_amount": 99.8,
        "total_amount_formatted": "R$ 99,80",
        "shipping_address": "Rua das Flores, 123",
        "created_at": "2026-06-11T12:00:00.000000Z",
        "items": [
          {
            "id": 18,
            "product_id": 1,
            "product_name": "Caneca Especial",
            "quantity": 2,
            "price": 49.9,
            "price_formatted": "R$ 49,90",
            "subtotal": 99.8,
            "subtotal_formatted": "R$ 99,80"
          }
        ]
      }
    }
    ```
*   **Resposta (422 Unprocessable Entity):** Se o estoque for insuficiente para algum produto ou se o payload for inválido.

### 📌 Histórico de Pedidos do Cliente
Lista os pedidos do cliente logado, dos mais recentes para os mais antigos.

*   **Rota:** `GET /orders`
*   **Resposta (200 OK):**
    ```json
    {
      "data": [
        {
          "id": 12,
          "status": "pending",
          "total_amount": 99.8,
          ...
        }
      ],
      "links": { ... },
      "meta": { ... }
    }
    ```

### 📌 Detalhes de um Pedido
Retorna um pedido específico se ele pertencer ao cliente autenticado.

*   **Rota:** `GET /orders/{id}`
*   **Resposta (200 OK):** *(Mesma estrutura de retorno do Checkout)*
*   **Resposta (403 Forbidden):** Caso o pedido pertença a outro usuário.

---

## 🔌 5. Webhooks de Pagamento (Público com Validação)

Endpoint utilizado pelo gateway de pagamento (Stripe/Mercado Pago) para informar a aprovação ou falha do pagamento.
*(Nota: Devolve o estoque de volta à prateleira se o status for `declined` ou `refunded`).*

*   **Rota:** `POST /webhooks/payment`
*   **Cabeçalho Requerido:** `X-Webhook-Token: emporia-secret-token`
*   **Payload (JSON):**
    ```json
    {
      "order_id": 12,
      "status": "approved"
    }
    ```
    *(Status válidos: `approved` [pago], `declined` [cancelado/estornado], `refunded` [estornado]).*
*   **Resposta (200 OK):**
    ```json
    {
      "message": "Webhook processado com sucesso."
    }
    ```
*   **Resposta (401 Unauthorized):** Se o token enviado em `X-Webhook-Token` for incorreto.

---

## 🛡️ 6. Painel Administrativo (Requer Token e Flag `is_admin = true`)

Todos estes endpoints devem ser acessados com prefixo `/admin` e exigem autenticação administrativa.

### 📌 Categorias Administrativas (CRUD)
*   `POST /admin/categories` - Cria nova categoria. Payload: `{"name": "Nova Categoria"}` (201 Created).
*   `PUT /admin/categories/{id}` - Altera categoria. Payload: `{"name": "Categoria Alterada"}` (200 OK).
*   `DELETE /admin/categories/{id}` - Exclui categoria. Retorno: `{"message": "Categoria excluída com sucesso."}` (200 OK).

### 📌 Produtos Administrativos (CRUD e Upload)
*   **Criar Produto:** `POST /admin/products`
    *   **Content-Type:** `multipart/form-data`
    *   **Body (Form Data):**
        *   `category_id` (int, obrigatório)
        *   `name` (string, obrigatório)
        *   `description` (string, opcional)
        *   `price` (decimal, obrigatório)
        *   `stock` (int, obrigatório)
        *   `is_active` (boolean, opcional)
        *   `image` (file, opcional, máx 2MB, jpeg/png/etc.)
    *   **Resposta (201 Created):** Retorna o produto com seu link de imagem gerado.
*   **Atualizar Produto:** `PUT /admin/products/{id}`
    *   **Body (Form Data ou JSON):** *(Semelhante ao Criar)*
    *   **Nota:** Se enviado arquivo de imagem, o sistema apaga o arquivo físico antigo do disco antes de salvar o novo.
    *   **Resposta (200 OK):** Retorna o produto atualizado.
*   **Excluir Produto:** `DELETE /admin/products/{id}`
    *   **Nota:** Apaga o produto do banco e remove fisicamente o arquivo de imagem do storage.
    *   **Resposta (200 OK):** `{"message": "Produto excluído com sucesso."}`
