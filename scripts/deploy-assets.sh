#!/bin/bash

# Deploy script para garantir que os assets estão sempre atualizados
# Uso: ./scripts/deploy-assets.sh [destino-servidor]

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}🔄 Starting Asset Deployment Process...${NC}"

# 1. Verificar que estamos no branch correto
echo -e "${YELLOW}1️⃣  Checking git branch...${NC}"
CURRENT_BRANCH=$(git -C "$PROJECT_ROOT" rev-parse --abbrev-ref HEAD)
if [ "$CURRENT_BRANCH" != "main" ]; then
    echo -e "${RED}❌ Not on main branch. Current: $CURRENT_BRANCH${NC}"
    exit 1
fi
echo -e "${GREEN}✅ On main branch${NC}"

# 2. Rebuild Shop assets
echo -e "${YELLOW}2️⃣  Building Shop assets...${NC}"
cd "$PROJECT_ROOT/packages/Webkul/Shop"
npm install --legacy-peer-deps > /dev/null 2>&1 || npm install > /dev/null 2>&1
npm run build
echo -e "${GREEN}✅ Shop assets built${NC}"

# 3. Rebuild Suggestion assets
echo -e "${YELLOW}3️⃣  Building Suggestion assets...${NC}"
cd "$PROJECT_ROOT/packages/Webkul/Suggestion"
npm install --legacy-peer-deps > /dev/null 2>&1 || npm install > /dev/null 2>&1
npm run build
echo -e "${GREEN}✅ Suggestion assets built${NC}"

# 4. Verificar integridade dos assets
echo -e "${YELLOW}4️⃣  Verifying asset integrity...${NC}"
SHOP_MANIFEST="$PROJECT_ROOT/public/themes/shop/default/build/manifest.json"
SUGGESTION_MANIFEST="$PROJECT_ROOT/public/themes/suggestion/default/build/manifest.json"

if [ ! -f "$SHOP_MANIFEST" ]; then
    echo -e "${RED}❌ Shop manifest not found at $SHOP_MANIFEST${NC}"
    exit 1
fi

if [ ! -f "$SUGGESTION_MANIFEST" ]; then
    echo -e "${RED}❌ Suggestion manifest not found at $SUGGESTION_MANIFEST${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Manifests verified${NC}"

# 5. Commit assets se houver mudanças
echo -e "${YELLOW}5️⃣  Checking for changes...${NC}"
cd "$PROJECT_ROOT"
if git diff --quiet public/themes/; then
    echo -e "${GREEN}✅ No changes to commit${NC}"
else
    echo -e "${YELLOW}   Committing asset changes...${NC}"
    git add public/themes/shop/default/build/
    git add public/themes/suggestion/default/build/
    git commit -m "chore: rebuild shop and suggestion assets (deploy)" || true
    git push origin main
    echo -e "${GREEN}✅ Assets committed and pushed${NC}"
fi

# 6. Se destino foi fornecido, sincronizar para servidor
if [ -n "$1" ]; then
    echo -e "${YELLOW}6️⃣  Syncing assets to $1...${NC}"
    rsync -avz --delete "$PROJECT_ROOT/public/themes/shop/default/build/" "$1/themes/shop/default/build/"
    rsync -avz --delete "$PROJECT_ROOT/public/themes/suggestion/default/build/" "$1/themes/suggestion/default/build/"
    echo -e "${GREEN}✅ Assets synced to server${NC}"
    
    # 7. Limpeza de cache no servidor (se houver script)
    if [ -f "$1/../clear-cache.sh" ]; then
        echo -e "${YELLOW}7️⃣  Clearing server cache...${NC}"
        bash "$1/../clear-cache.sh"
        echo -e "${GREEN}✅ Server cache cleared${NC}"
    fi
fi

echo -e "${GREEN}✅ Asset deployment completed successfully!${NC}"
