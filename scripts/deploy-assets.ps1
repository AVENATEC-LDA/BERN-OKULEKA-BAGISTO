# Deploy script para Windows
# Garantir que os assets estão sempre atualizados
# Uso: .\scripts\deploy-assets.ps1 [-ServerPath "\\servidor\path"]

param(
    [string]$ServerPath
)

$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir

Write-Host "🔄 Starting Asset Deployment Process..." -ForegroundColor Yellow

# 1. Verificar branch
Write-Host "1️⃣  Checking git branch..." -ForegroundColor Yellow
$CurrentBranch = & git -C $ProjectRoot rev-parse --abbrev-ref HEAD
if ($CurrentBranch -ne "main") {
    Write-Host "❌ Not on main branch. Current: $CurrentBranch" -ForegroundColor Red
    exit 1
}
Write-Host "✅ On main branch" -ForegroundColor Green

# 2. Build Shop
Write-Host "2️⃣  Building Shop assets..." -ForegroundColor Yellow
Push-Location "$ProjectRoot\packages\Webkul\Shop"
npm install > $null 2>&1
npm run build
Pop-Location
Write-Host "✅ Shop assets built" -ForegroundColor Green

# 3. Build Suggestion
Write-Host "3️⃣  Building Suggestion assets..." -ForegroundColor Yellow
Push-Location "$ProjectRoot\packages\Webkul\Suggestion"
npm install > $null 2>&1
npm run build
Pop-Location
Write-Host "✅ Suggestion assets built" -ForegroundColor Green

# 4. Verificar integridade
Write-Host "4️⃣  Verifying asset integrity..." -ForegroundColor Yellow
$ShopManifest = "$ProjectRoot\public\themes\shop\default\build\manifest.json"
$SuggestionManifest = "$ProjectRoot\public\themes\suggestion\default\build\manifest.json"

if (-not (Test-Path $ShopManifest)) {
    Write-Host "❌ Shop manifest not found" -ForegroundColor Red
    exit 1
}
if (-not (Test-Path $SuggestionManifest)) {
    Write-Host "❌ Suggestion manifest not found" -ForegroundColor Red
    exit 1
}
Write-Host "✅ Manifests verified" -ForegroundColor Green

# 5. Commit
Write-Host "5️⃣  Checking for changes..." -ForegroundColor Yellow
Push-Location $ProjectRoot
$Status = & git diff --quiet public/themes/
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ No changes to commit" -ForegroundColor Green
}
else {
    Write-Host "   Committing asset changes..." -ForegroundColor Yellow
    & git add public/themes/shop/default/build/
    & git add public/themes/suggestion/default/build/
    & git commit -m "chore: rebuild shop and suggestion assets (deploy)" 2> $null
    & git push origin main
    Write-Host "✅ Assets committed and pushed" -ForegroundColor Green
}
Pop-Location

# 6. Sync para servidor
if ($ServerPath) {
    Write-Host "6️⃣  Syncing assets to server..." -ForegroundColor Yellow
    
    $ShopSrc = "$ProjectRoot\public\themes\shop\default\build\"
    $ShopDst = "$ServerPath\themes\shop\default\build\"
    $SuggestionSrc = "$ProjectRoot\public\themes\suggestion\default\build\"
    $SuggestionDst = "$ServerPath\themes\suggestion\default\build\"
    
    # Copy com remoção de arquivos antigos
    Remove-Item -Path $ShopDst -Recurse -Force -ErrorAction SilentlyContinue
    Remove-Item -Path $SuggestionDst -Recurse -Force -ErrorAction SilentlyContinue
    
    Copy-Item -Path $ShopSrc -Destination $ShopDst -Recurse -Force
    Copy-Item -Path $SuggestionSrc -Destination $SuggestionDst -Recurse -Force
    
    Write-Host "✅ Assets synced to server" -ForegroundColor Green
}

Write-Host "✅ Asset deployment completed successfully!" -ForegroundColor Green
