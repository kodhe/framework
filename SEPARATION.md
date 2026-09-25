# Pemisahan Legacy Routing ↔ Modern Routing

## Struktur akhir

```
CORE FRAMEWORK
src/Config/Setup.php
src/Foundation/Application.php
        │
        ▼
     kodhe/http  (WAJIB)
        │
        ├── Router              ← pure modern (implements RouterInterface)
        ├── RoutingManager      ← modern-only resolve()
        ├── ControllerExecutor  ← modern execution; legacy type throws clear error
        └── Kernel              ← tidak membuat legacy router
        │
        ▼ (optional)
kodhe/legacy-routing
        │
        ├── Routing/LegacyRouter.php
        ├── Routing/HybridRouter.php   (ex-Router yang extends LegacyRouter)
        ├── Routing/UnifiedRouter.php
        └── Adapter/LegacyRoutingProvider.php
```

## Phase 1 — kodhe/http (selesai)

| Item | Status |
|------|--------|
| Hapus LegacyRouter dari package | ✅ dihapus |
| Hapus UnifiedRouter dari package | ✅ dihapus |
| Hapus `Router extends LegacyRouter` | ✅ Router sekarang implements RouterInterface saja |
| RoutingManager hanya modern | ✅ resolve() hanya match modern |
| ControllerExecutor contract modern | ✅ type `legacy` melempar BadRequestException + pesan install package |
| Kernel tidak membuat legacy router | ✅ hanya `new Router()` modern |

## Phase 2 — kodhe/legacy-routing (selesai)

| Item | Status |
|------|--------|
| Pindahkan LegacyRouter | ✅ |
| Pindahkan hybrid Router → HybridRouter | ✅ |
| Pindahkan UnifiedRouter | ✅ |
| Adapter/provider | ✅ `LegacyRoutingProvider::register($container)` |

## Phase 3 — Composer

- **kodhe/http** `composer.json`: tidak require legacy; `suggest` kodhe/legacy-routing
- **kodhe/legacy-routing**: `require` kodhe/http; autoload namespace `Kodhe\Framework\Http\Routing\` untuk class legacy + `Kodhe\Framework\LegacyRouting\` untuk adapter

## Setup.php

- Alias **`Router`** → `Kodhe\Framework\Http\Routing\Router` (modern)
- Alias **`CI_Router`** → `HybridRouter` (hanya jika package legacy terpasang; tanpa package, ubah ke `Router::class`)
- Service `router` → modern Router
- Service `legacy.router` → HybridRouter / LegacyRouter bila ada

## Cara memakai legacy (opsional)

```bash
composer require kodhe/legacy-routing
```

```php
// bootstrap / Application
use Kodhe\Framework\LegacyRouting\Adapter\LegacyRoutingProvider;

LegacyRoutingProvider::register($container, [
    'enable_legacy_routing' => true,
    'prefer_modern' => true,
]);
```

Atau di Setup aliases pastikan:

```php
'CI_Router' => \Kodhe\Framework\Http\Routing\HybridRouter::class,
```

## Catatan implementasi

1. **ControllerExecutor** masih berisi method `executeLegacyController` (dead code path) agar diff lebih kecil; path tersebut tidak dipanggil karena `case 'legacy'` sudah di-throw. Pembersihan penuh bisa dilakukan di PR berikutnya.
2. **HybridRouter** masih di namespace `Kodhe\Framework\Http\Routing` agar BC untuk alias CI_Router dan kode lama yang type-hint class name tersebut.
3. **RoutingManager** modern tidak lagi memanggil `resolveFromLegacy`. Hybrid resolve (modern-first + legacy fallback) bisa ditambahkan di legacy package sebagai `HybridRoutingManager` yang extends / wraps modern manager jika dibutuhkan.
4. Pastikan app yang masih mengandalkan `$this->uri`, `set_class()`, `_set_routing()` memakai package legacy.

## File yang diubah / dihasilkan

```
packages/kodhe-http/
  composer.json                          (v1.2.0, suggest legacy)
  src/Routing/Router.php                 (rewrite modern-only)
  src/Routing/RoutingManager.php         (rewrite modern-only)
  src/Routing/ControllerExecutor.php     (patched: no legacy exec)
  src/Kernel/Kernel.php                  (no legacy router)

packages/kodhe-legacy-routing/
  composer.json
  src/Routing/LegacyRouter.php
  src/Routing/HybridRouter.php
  src/Routing/UnifiedRouter.php
  src/Adapter/LegacyRoutingProvider.php

framework_src/src/Config/Setup.php       (Router vs CI_Router dipisah)
```
