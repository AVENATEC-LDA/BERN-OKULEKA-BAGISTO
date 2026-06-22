# EMIS Payment — Gateway Multicaixa Express para Bagisto

**Versão:** 1.0.0  
**Compatível com:** Bagisto v2.x · Laravel 10/11 · PHP 8.1+  
**Autor:** AVENATEC  

Gateway de pagamento EMIS GPO / WebFrame para o mercado angolano. Permite pagamentos via MULTICAIXA Express, QR Code ou Pagar Online directamente no Bagisto.

---

## Instalação

### 1. Copiar o pacote

Copie a pasta `Avenatec/EmisPayment` para o directório `packages/` do Bagisto:

```
seu-bagisto/
└── packages/
    └── Avenatec/
        └── EmisPayment/
            ├── composer.json
            └── src/
```

### 2. Registar no composer.json do Bagisto

Abra o `composer.json` **raiz do Bagisto** e adicione em `autoload.psr-4`:

```json
"autoload": {
    "psr-4": {
        "Avenatec\\EmisPayment\\": "packages/Avenatec/EmisPayment/src"
    }
}
```

### 3. Registar o ServiceProvider

Abra `config/app.php` e adicione em `providers`:

```php
'providers' => [
    // ... outros providers
    Avenatec\EmisPayment\Providers\EmisPaymentServiceProvider::class,
],
```

### 4. Executar comandos

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 5. Excluir a rota do webhook do CSRF

Abra `app/Http/Middleware/VerifyCsrfToken.php` e adicione:

```php
protected $except = [
    'emis-payment/webhook',
];
```

---

## Configuração no Admin

Aceda a:  
**Admin → Configurações → Configuração → Vendas → Métodos de Pagamento → EMIS – Multicaixa Express**

| Campo | Descrição |
|---|---|
| **Título** | Nome exibido no checkout |
| **Prefixo da Referência** | Até 6 chars. Ex: `BERNO` → referência `BERNO1234` |
| **Frame Token** | Token do comerciante — Portal EMIS → ícone Quiosque |
| **ID do Terminal (POS)** | Fornecido pela EMIS |
| **MULTICAIXA Express** | PAYMENT / DISABLED / AUTHORIZATION |
| **QR Code** | PAYMENT / DISABLED / AUTHORIZATION |
| **Cartão Multicaixa** | DISABLED (requer certificação EGR) |
| **Endpoint EMIS** | `https://pagamentonline.emis.co.ao/online-payment-gateway/portal/frameToken` |
| **Host iframe EMIS** | `https://pagamentonline.emis.co.ao/online-payment-gateway/portal/frame?token=` |

---

## Diagnóstico

Verificar se o endpoint está público e acessível:

```
https://seusite.com/emis-payment/test
```

Resposta esperada:
```json
{
  "ok": true,
  "versao": "1.0.0",
  "mensagem": "Endpoint EMIS Payment acessível e a funcionar.",
  "webhook_url": "https://seusite.com/emis-payment/webhook"
}
```

---

## Fluxo de Pagamento

```
Checkout Bagisto
    ↓
/emis-payment/redirect
    ↓ (solicita token à EMIS)
/emis-payment/pay (iframe fullscreen)
    ↓ (cliente paga no MCX Express)
postMessage da EMIS → redirect para sucesso/falha
    ↓ (servidor EMIS)
POST /emis-payment/webhook → actualiza status do pedido
```

---

## Segurança

- O status do pedido é actualizado **exclusivamente pelo webhook** servidor-para-servidor.
- O browser do cliente **nunca** altera o status de um pedido.
- IP do servidor EMIS: `102.221.52.196` — adicionar à whitelist se usar Cloudflare.

---

## Suporte

AVENATEC — auxilionunesr7@gmail.com
