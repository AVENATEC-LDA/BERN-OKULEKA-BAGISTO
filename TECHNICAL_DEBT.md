# Technical Debt & Warnings

## 🔴 High Priority

### 1. Deprecated Package: `vue-flatpickr@2.3.0`
- **Status**: Active
- **Location**: 
  - `packages/Webkul/Admin/package.json` (line 31)
  - `packages/Webkul/Shop/package.json` (line 28)
  - `packages/Webkul/Installer/package.json` (line 22)
- **Issue**: Package is no longer maintained
- **Impact**: npm build warnings during deployment
- **Recommended Fix**: 
  - Option A: Replace with `@chenfengyuan/vue-daterangepicker`
  - Option B: Use `flatpickr` directly with vanilla JS wrapper
  - Option C: Migrate to `vue3-date-picker` (Vue 3 native)
- **Priority**: Medium (works but not maintained)
- **Timeline**: Next minor release

## 🟡 Medium Priority

### 2. npm Version Outdated in Docker
- **Status**: Active
- **Current**: npm 10.9.8
- **Latest**: npm 11.18.0
- **Location**: Docker build output during Admin/Shop/Installer npm installs
- **Impact**: Minor performance improvements, security patches
- **Recommended Fix**: Update base Node.js image to latest LTS
- **Timeline**: Next Docker build refresh

## 🟡 Medium Priority

### 3. npm Cache Clean Using --force
- **Status**: Active
- **Location**: `Dockerfile.dokploy` line 20 (npm cache clean --force)
- **Issue**: Using --force disables recommended protections
- **Impact**: Non-blocking warning, but suboptimal cleanup
- **Recommended Fix**: Replace with `npm cache clean` (without --force)
- **Timeline**: Next Docker optimization pass

## 📋 Completed Fixes

### ✅ vue-flatpickr Deprecation Warning Suppression
- Date: 2026-06-29
- Method: Monitored and documented
- Status: Awaiting replacement strategy

---

## How to Address

### For vue-flatpickr Migration:
```bash
# Test replacement in Admin package:
cd packages/Webkul/Admin
npm install vue3-date-picker --save-dev
# Update components using vue-flatpickr
# Run: npm run build
# Test: Check date picker functionality
```

### For npm Version Update:
```bash
# In Dockerfile.dokploy, update Node.js base image:
# FROM node:22-bookworm-slim → FROM node:latest (or 23-alpine)
```

### For npm Cache Clean:
```bash
# In Dockerfile.dokploy line 20:
# FROM: npm cache clean --force
# TO: npm cache clean
```

---

**Last Updated**: 2026-06-29
**Next Review**: After Suggestion v2.4.1 stabilization
