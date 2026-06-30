# 📦 Asset Deployment Guide

## Problema Identificado

O servidor estava servindo um build antigo dos assets do tema Shop/Suggestion, causando erros 404 (`vue-CuzGsaQ5.js`, etc), enquanto o repositório local tinha hashes diferentes (`vue-C3R1tuGv.js`).

## Solução Implementada

### 1. Workflow Automático (CI/CD)

Criamos `.github/workflows/build_assets.yml` que:
- ✅ Executa automaticamente quando há mudanças no código do Shop/Suggestion
- ✅ Rebuild os assets localmente
- ✅ Commit dos assets atualizados de volta ao repositório
- ✅ Garante que os assets sempre estão em sync com o código

**Acionado por:**
- Push de mudanças em `packages/Webkul/Shop/src/Resources/assets/**`
- Push de mudanças em `packages/Webkul/Suggestion/src/Resources/assets/**`
- Mudanças em `package.json` desses pacotes

### 2. Scripts de Deploy Local

#### **Linux/macOS:**
```bash
./scripts/deploy-assets.sh [caminho-do-servidor]
```

**Exemplo com sincronização para servidor:**
```bash
./scripts/deploy-assets.sh "/mnt/servidor/public"
```

#### **Windows:**
```powershell
.\scripts\deploy-assets.ps1 -ServerPath "\\servidor\public"
```

**O que o script faz:**
1. Verifica que você está no branch `main`
2. Rebuild Shop assets (`npm run build`)
3. Rebuild Suggestion assets (`npm run build`)
4. Valida que os `manifest.json` foram gerados
5. Commit automático se houver mudanças
6. **Sincroniza para servidor e REMOVE arquivos antigos**

### 3. Processo Recomendado para Desenvolvedores

Depois de editar código de frontend ou mudanças no Suggestion:

**Opção A - Automático via GitHub (recomendado):**
```bash
git add .
git commit -m "Sua mensagem aqui"
git push origin main
# ✅ GitHub Actions vai rebuild automaticamente
# ✅ Assets vão ser commitados de volta
```

**Opção B - Local antes de fazer push:**
```bash
# Fazer suas mudanças
git add .
git commit -m "Sua mudança aqui"

# Rebuild e commit dos assets
./scripts/deploy-assets.sh

# Fazer push com assets já atualizados
git push origin main
```

### 4. Processo de Deploy para Produção

**No servidor de produção:**

```bash
# Clonar/atualizar repo
cd /app
git pull origin main

# Rebuild e sincronizar assets (se tiver acesso direto)
./scripts/deploy-assets.sh /var/www/html/bagisto/public

# OU apenas rebuild (se for via container)
cd packages/Webkul/Shop && npm install && npm run build
cd packages/Webkul/Suggestion && npm install && npm run build

# Se usar Docker:
docker-compose exec app ./scripts/deploy-assets.sh /var/www/html/public
```

### 5. Arquivos Trackados vs. Não Trackados

**✅ AGORA TRACKADOS (versionados no git):**
- `public/themes/shop/default/build/manifest.json`
- `public/themes/shop/default/build/assets/`
- `public/themes/suggestion/default/build/manifest.json`
- `public/themes/suggestion/default/build/assets/`

**✅ IGNORADOS (não precisam ser commitados):**
- `node_modules/`
- Build cache

## ⚠️ Importante

1. **Nunca deixe assets antigos no servidor** - o script de deploy usa `--delete` para remover arquivos antigos
2. **Se mudar código frontend, os assets vão ser rebuilded automaticamente** pelo GitHub Actions
3. **Em produção, sempre use o script de deploy** para garantir sincronização

## 🔍 Verificação

Para confirmar que os assets estão corretos no servidor:

```bash
# Listar assets atuais
ls -la /var/www/html/bagisto/public/themes/shop/default/build/assets/ | grep vue

# Deve mostrar algo como:
# vue-C3R1tuGv.js (hash atual correto)
# ❌ NÃO deve mostrar vue-CuzGsaQ5.js ou hashes antigos
```

## 📊 Monitoramento

Se erros 404 continuarem após deploy:

1. **Verificar manifest.json no servidor:**
   ```bash
   cat /var/www/html/bagisto/public/themes/shop/default/build/manifest.json | grep vue
   ```

2. **Verificar HTML da página:**
   ```bash
   curl https://seusite.com | grep "themes/shop/default/build/assets/vue"
   ```

3. **Limpar cache (se usar Cloudflare/CDN):**
   - Cloudflare: purge cache
   - Nginx: `nginx -s reload` ou `service nginx restart`
   - PHP: `php artisan cache:clear` + `php artisan view:clear`

## 🚀 Próximas Melhorias Recomendadas

- [ ] Adicionar cache buster (query params nos assets)
- [ ] Configurar Cloudflare/CDN com "Purge on Deploy"
- [ ] Monitorar 404s via erro tracking (Sentry, etc)
- [ ] Adicionar health check para verificar assets após deploy
