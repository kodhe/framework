# Service, Modul, Routing Modern & Facade

Halaman ini melengkapi dokumentasi fitur-fitur ** bawaan Kodhe Framework**
(kernel `framework/` + paket `kodhe/http`) yang belum tercakup di halaman lain:
service container & service provider (addon), sistem modul, routing modern
(`Route::`), middleware, dan facade global. Semua API di bawah diverifikasi dari
kode sumber: `framework/src/Container`, `framework/src/Foundation/Service`,
`framework/src/Support/Modules.php`, `http/src/Routing`, `http/src/Middleware`,
`framework/src/Support/Facades`.

> Terkait pemindahan aplikasi lama, lihat [Migrasi dari CI3](migrating-from-ci3.md)
> dan [Struktur Folder & Router](folder-structure-and-routing.md).

## 1. Service Container (`Kodhe\Framework\Container\Container`)

Container adalah registry dependency-injection dengan **penamaan berprefix**.
Setiap service disimpan sebagai string `"prefix:Nama"`; bila prefix tidak
ditulis, container otomatis memakai prefix native `kodhe:`
(`Container::NATIVE_PREFIX`).

```php
use Kodhe\Framework\Container\Container;

$c = new Container();

// factory baru tiap panggilan
$c->register('mail:Mailer', function ($container, ...$args) {
    return new App\Services\Mailer(...$args);
});

// instance tunggal (singleton)
$c->registerSingleton('app:Settings', function ($container) {
    return new App\Services\Settings(config_item('settings'));
});

$mailer = $c->make('mail:Mailer');          // RuntimeException jika tak terdaftar
$c->has('mail:Mailer');                     // bool
$c->getBindings();                          // daftar binding non-singleton
$c->getSingletonBindings();                 // daftar singleton
$c->replace('mail:Mailer', $obj);           // override binding yang sudah ada
$c->setThrowOnDuplicate(false);             // izinkan register ulang tanpa error
```

Catatan implementasi (sesuai kode):
- `make()` dipanggil variadic: argumen ekstra diteruskan ke closure/factory.
- `make()` juga menerima bentuk `make($bindingObjek, $nama, ...$args)` untuk
  resolve lewat sub-binding tertentu.
- Interface kontrak: `BindingInterface` (`register`, `bind`,
  `registerSingleton`, `make`); dekorator `ConcreteBinding` meneruskan ke
  delegate bila nama tidak ditemukan lokal.
- Error tidak ditemukan dilambungkan via
  `Exceptions\ContainerException::notFound($id, $availableServices)` (memuat
  daftar service yang tersedia dalam pesannya).

## 2. ServiceProvider / ServiceLocator / ServiceManager (sistem Addon)

Satu set kelas di `Kodhe\Framework\Foundation\Service` untuk memaketkan fitur
sebagai **addon/modul mandiri** (pola warisan ExpressionEngine-style):

| Kelas | Peran |
|---|---|
| `ServiceLocator` | Registry provider: `register($prefix, $provider)`, `has`, `get`, `all` — duplikat prefix → `Exception("Addon of name {$prefix} already registered.")` |
| `ServiceProvider` | Base class per addon; membaca array dari file setup, mendaftarkan namespace autoload, services, singletons, models, aliases |
| `ServiceManager` | Orkestrasi: scan folder addon, `addProvider()`, kumpulkan data lintas provider (`forward()`) |
| `ServiceHelper` | Akses statis service dengan auto-detection prefix |

### 2.1 File setup addon

Sebuah addon adalah folder berisi `addon.setup.php` yang **mengembalikan array**:

```php
// addons/blog/addon.setup.php
return [
    'name'      => 'Blog',
    'version'   => '1.0.0',
    'author'    => 'Tim Kami',
    'namespace' => 'Addons\\Blog',            // didaftarkan ke Autoloader via addPrefix()
    'services'  => [                           // [nama => Closure|string]
        'PostService' => function ($provider) {
            return new \Addons Blog\PostService;
        },
        'TagService'  => 'Services\TagService', // string = FQCN relatif namespace addon
    ],
    'services.singletons' => [
        'StatsService' => fn ($provider) => new StatsService(),
    ],
    'models' => [
        'Post',                                 // jadi Addons\Blog\Post
        '\Shared\Model',                        // leading backslash = jangan dinamespace
    ],
    'models.dependencies' => ['Post' => ['kodhe:db']],
    'aliases' => [
        'CI_Post' => 'Addons\\Blog\\Post',      // key = alias, value = kelas asli
    ],
];
```

Aturan yang ditegakkan kode (`ServiceProvider::registerServices`):
- Nama service **tidak boleh mengandung `:`** (dipakai sebagai separator prefix).
- Service ter-register dengan kunci `"{addon}:{Nama}"` di container; method
  `register/registerSingleton/make` pada provider selalu memaksa prefix lewat
  `ensurePrefix()` (khusus nama `App` dipetakan ke `kodhe:App`).
- `string` pada `services` dibungkus refleksi:实例iasi kelas
  `namespace\Kelas` dengan argumen sisa.
- `aliases` numeric-key memicu pemetaan `Kodhe\Modules...` → alias `Modules...`;
  untuk alias `CI_*` versi lowercase (`CI_model`) ikut didaftarkan agar aman di
  Linux case-sensitive.

### 2.2 Registrasi runtime

```php
use Kodhe\Framework\Support\Autoloader;
use Kodhe\Framework\Container\Container;
use Kodhe\Framework\Foundation\Service\{ServiceLocator, ServiceManager};

$container = new Container();
$locator   = new ServiceLocator($container);
$manager   = new ServiceManager($container, $locator);
$manager->setAutoloader(new Autoloader()); // diperlukan utk registerNamespace

$manager->setupAddons(FCPATH . 'addons');  // scan folder yg punya addon.setup.php
// atau manual:
$provider = $manager->addProvider(FCPATH . 'addons/blog'); // prefix default = basename folder

$manager->getPrefixes();     // ['blog', ...]
$manager->getNamespaces();   // ['blog:blog' => 'Addons\Blog', ...] (hasil forward())
$manager->getModels();       // model terkumpul dgn kunci '{prefix}:{name}'
$manager->setClassAliases(); // aktifkan semua 'aliases'
```

Konflik prefix: provider yang sudah ada **tidak ditimpa**, kecuali path lama
berisi `Addons/pro/levelups` atau `ExpressionEngine/Addons` (prioritas
pro/first-party — logika internal `addProvider()`).

### 2.3 Mengakses service: `ServiceHelper` & helper `service()`

```php
use Kodhe\Framework\Foundation\Service\ServiceHelper;

ServiceHelper::get('email');                 // 'email' → EmailService (auto-suffix)
ServiceHelper::get('EmailService', 'blog');  // prefix eksplisit
ServiceHelper::email();                      // __callStatic: email() → EmailService
ServiceHelper::user_manager();               // snake_case → UserManagerService
ServiceHelper::getAvailableServices();       // daftar lintas semua provider
ServiceHelper::clearCache();                 // reset cache instances & providers

// fungsi global (framework/src/Support/Helpers.php):
$svc = service('email');            // delegasi ke ServiceHelper::get()
$svc = service('email', 'blog');
```

Konversi nama (`methodToServiceName`): underscore di-strip lalu `ucwords`,
suffix `Service` ditambahkan bila belum ada. Resolusi memakai
`kodhe('App')` (instance Application/provider root) — artinya **helper ini
aktif hanya bila kernel/bootstrap Kodhe sudah berjalan**; pada mode per-paket
murni, pakai `Container::make()` langsung.

## 3. Sistem Modul (`Kodhe\Framework\Support\Modules`)

Modul = folder aplikasi mandiri di bawah `APPPATH.'modules/'` (bisa lokasi lain
via config `modules_locations`) dengan struktur gaya CI3 HMVC:
`controllers/ models/ views/ config/ (routes.php)`.

### 3.1 Siklus hidup

- `Modules::init()` — panggil sekali saat bootstrap: menentukan locations,
  mencoba cache, lalu `scanAndCacheAllModules()`. Router modern
  (`Router`, `UnifiedRouter`) memanggilnya otomatis di konstruktor.
- Cache hasil scan disimpan di file cache (`cache()`, `loadFromCache()`,
  `isCacheFresh($maxAge=3600)`, `refreshCache()`, `clearCache()`) — penting di
  produksi karena scanning direktori mahal.
- Rute per modul dibaca dari `config/routes.php` milik modul
  (`parse_routes()`), plus grup rute modul via `Route::module()`.

API utama:

```php
Modules::moduleExists('blog');           // bool
Modules::getAllCachedModules();          // [nama => [path...]]
Modules::getFirstModulePath('blog');     // ?string
Modules::list_modules($excludeCore=false);
Modules::folders();                     // daftar lokasi modul
Modules::find($file, $module, 'config/'); // [path, file] search konvensi HMVC
Modules::run('blog/post/index', ...$args); // load controller + panggil method (ob capture)
Modules::load('blog/post');              // instansiasi controller modul (registry)
Modules::controller_exists('post','blog');
Modules::path($module, $folder);         // path folder modul
Modules::register_asset($asset); Modules::assets();
```

`Modules::run()` memvalidasi jumlah argumen terhadap parameter wajib method
(ReflectionMethod) dan mengembalikan output string/NULL; kegagalan dicatat
lewat `log_message('error', ...)`.

### 3.2 Helper modul global (`framework/src/Support/Helpers/module_helper.php`)

| Fungsi | Kegunaan |
|---|---|
| `current_module()` | nama modul request berjalan (deteksi berjenjang: CI instance → Modules → Facade → URI) |
| `is_module('blog')` | pembanding modul aktif |
| `module_path('blog','views')` | path absolut folder/subpath modul |
| `module_controller_exists('post','blog')` | cek controller |
| `module_view($view,$data,$return)` | render view `modules/{m}/views/{v}` |
| `module_config($key,$default,'blog')` | baca config modul |
| `module_info('blog')` / `module_exists()` | metadata |
| `module_url('post','blog')` / `module_asset()` | URL & aset modul |
| `active_module($m=null)` | getter/setter modul di router (`kodhe()->router->set_module()`) |

Loader juga menyediakan jembatan legacy: `$CI->load->run_module()`,
`load_module()`, `add_module()`, `remove_module()`,
`module_file_path()`, `module_config()`, `list_modules()` (FileLoader).

## 4. Routing Modern (`Route::` dari kodhe/http)

Di luar `$route['uri'] = 'class/method'` gaya CI3, paket `kodhe/http`
menyediakan fluent router (Laravel-style) di
`Kodhe\Framework\Http\Routing\Route` + `RouteCollection`:

```php
use Kodhe\Framework\Http\Routing\Route;

Route::get('posts', [PostController::class, 'index']);
Route::post('posts', [PostController::class, 'store'])->middleware('auth');
Route::match(['get','post'], 'login', LoginController::class);
Route::any('ping', fn () => response('pong'));

Route::group(['prefix' => 'admin', 'namespace' => 'App\\Controllers\\Admin',
              'middleware' => ['auth','role:admin']], function () {
    Route::resource('users', UserController::class);      // index/create/store/show/edit/update/destroy
    Route::apiResource('tags', TagController::class);     // varian JSON
    Route::module('blog', function () { /* rute dalam modul */ });
});

Route::apiVersion('v1', fn () => Route::get('feed', FeedController::class));
Route::domain('acme.example.test', fn () => Route::get('dash', DashController::class));
Route::view('about', 'pages/about', ['title' => 'About']);
Route::redirect('/old', '/new', 301);
Route::fallback(fn () => throw new NotFoundError());

Route::pattern('id', '[0-9]+');                  // constraint global
$name = Route::url('users.show', ['id' => 5]);   // reverse URL by name
Route::getRoutes(); Route::clear();
```

Fitur pendukung lain yang tersedia: `dynamic($prefix, $controller)`
(route otomatis ke controller), `permanentRedirect`, `api()` (alias prefix
`api/`), `wildcardDomain*`, `getCurrentApiVersion()/setApiVersion()`.
Koleksi rute mendukung **cache**: `RouteCollection::cache()`,
`loadFromCache()`, `isCacheFresh()` — aktifkan lewat config router
`cache_routes` (lihat [folder-structure-and-routing](folder-structure-and-routing.md)).

Resource fluency: `Route::resource(...)->only([...])->except([...])` via
`ResourceRegistrar` (`only/except/names/parameters/middleware`).

## 5. Middleware

Folder `http/src/Middleware`:
- `MiddlewareInterface` — kontrak `process(Request $request, callable $next)`
  (implement sesuai file tersebut).
- `MiddlewareRegistry` — daftar named-middleware yang bisa dirujuk string pada
  `->middleware('auth')` / group attributes.
- `MiddlewareGroup` — sekumpulan middleware (mis. `web`, `api`).
- `CallableMiddleware` — membungkus closure sebagai middleware.
- `RateLimiter` — pembatas laju (dipakai atribut throttle pada route/API).

Registrasi biasanya lewat Kernel/routing config aplikasi Anda; middleware jalan
dalam pipeline sebelum `ControllerExecutor` memanggil action. Detail request/
response lifecycle: [http.md →](../libraries/http.md).

## 6. Facade Global & Helper Aplikasi

`Kodhe\Framework\Support\Facades\Facade` adalah **singleton service locator**
yang sekaligus menjadi isi `$GLOBALS['kodhe']`:

```php
$f = Facade::getInstance();
$f->set('load', $loader);          // registrasi komponen (dev boleh override; prod diam)
$f->get('router');                 // InvalidArgumentException bila tak ada
$f->has('di');                     // bool
$f->register('myMacro', fn() => …);// custom method → $f->myMacro()
$f->remove('tmp'); $f->all();
Facade::reset();                   // bersihkan singleton (testing)
```

- `__call` meneruskan ke method loader bila ada, lalu ke callback terdaftar.
- Ada peta deprecated otomatis (`blacklist` → `blockedlist`) dengan
  `E_USER_DEPRECATED` di environment development.
- `Controller` dasar (`http/src/Controllers/Controller.php`) mengambil facade
  dari `$GLOBALS['CI_APP']->getKernel()` / `$GLOBALS['CI_KERNEL']` bila ada,
  fallback ke `Facade::getInstance()`; `BaseController` menambahkan helper
  `string`, library `user_agent`, dan `theme` (bila paket `kodhe/view` pasang).

Helper global lain di `framework/src/Support/Helpers.php`:
`session($key=null)`, `resolve_path()`, `csrf_token()/csrf_field()/csrf_meta()`,
`active_module()`, `service()`. Fungsi `kodhe()` / `app()` disediakan oleh
kernel/bootstrap aplikasi (lihat README `Foundation/Service` dan contoh stub
`.phpstan/bootstrap.php`); pastikan versi aplikasi Anda mendefinisikannya —
pada mode per-paket murni keduanya tidak otomatis ada.

## 7. Kapan Memakai Yang Mana?

| Kebutuhan | Pakai |
|---|---|
| DI internal aplikasi satu repo | `Container::register/make` dengan prefix sendiri |
| Paket fitur lepas-passang (addon marketplace) | `addon.setup.php` + `ServiceManager::setupAddons()` |
| Ambil service addon dari controller | `service('nama')` / `ServiceHelper::nama()` |
| Subset aplikasi besar jadi paket mandiri | `Modules` (HMVC) + `Route::module()` |
| API/endpoint modern | `Route::get/group/resource/apiVersion` + middleware |
| Kode CI3 lama tetap jalan | `$CI->load->library()` dual-mode (lihat loader-kernel.md) |

## Catatan Known Issues

- `ServiceHelper` bergantung pada `kodhe('App')`; bila facade/container `App`
  belum di-boot, resolusi melempar error — bukan graceful null.
- `ServiceManager::addProvider()` **diam (return void)** bila file setup tidak
  ada (throw dikomentari di kode) — sulit debug; cek log `debug`.
- `setupAddons()` hanya memproses folder yang memiliki `addon.setup.php`;
  addon tanpa file itu dilewati tanpa warning.
- Prioritas konflik prefix hardcoded ke string path
  `Addons/pro/levelups` / `ExpressionEngine/Addons` — artefak asal-usul EE;
  untuk proyek baru sebaiknya hindari duplikasi prefix.
- `getAvailableServices()` pada provider mengandalkan method
  `getAvailableServices()` milik provider itu sendiri; base `ServiceProvider`
  tidak mendefinisikannya, sehingga default mengembalikan `[]` kecuali addon
  mengimplementasikannya.

## Tautan Terkait

- [Arsitektur](architecture.md) · [Struktur Folder & Router](folder-structure-and-routing.md)
- [Menjalankan Aplikasi](../general/running.md) · [Referensi Loader Kernel](../libraries/loader-kernel.md)
- [HTTP package](../libraries/http.md) · [Migrasi dari CI3](migrating-from-ci3.md)
