# Kodhe Javascript

Package pembangkit kode JavaScript/jQuery dari sisi PHP — hasil refaktor library `Javascript` + `Jquery` CodeIgniter 3 (namespace `Kodhe\Framework\Javascript`). Kelas `Javascript` adalah *driver-agnostic wrapper* yang meneruskan pemanggilan ke driver JS aktif (default: jQuery), sementara `Jquery` berisi implementasi konkret untuk event, efek, dan penulisan tag `<script>`.

> ⚠️ **Legacy**: sejak CI 3.0 library ini berstatus deprecated di CodeIgniter. Untuk proyek baru, lebih baik menulis JS modern sendiri; gunakan package ini hanya untuk memelihara aplikasi lama.

## Instalasi

```bash
composer require kodhe/javascript
```

Persyaratan: PHP >= 8.1. Bekerja penuh dalam aplikasi CodeIgniter (membutuhkan instance `kodhe()` dan loader CI untuk `$this->CI->load->library(...)`).

## Quick Start

```php
<?php

declare(strict_types=1);

// Dalam konteks aplikasi CI/Kodhe framework:
$js = new Kodhe\Framework\Javascript\Javascript();

echo $js->ready(
    $js->click('#tombol', "alert('Halo!');")
);
// <script type="text/javascript">
// $(document).ready(function(){ ... });
// </script>
```

## Struktur Direktori

```
src/
├── Javascript.php   # Wrapper/facade; meneruskan method ke driver ($this->js)
└── Jquery.php       # Driver jQuery: event, efek, class ops, script(), dll.
```

## Penggunaan

### 1. Memilih driver & autoload script inti

```php
$js = new Javascript([
    'js_library_driver' => 'jquery', // driver yang di-load via CI loader
    'autoload'          => true,     // langsung cetak <script src="...jquery...">
]);
```

### 2. Event handler

Semua event menerima selector elemen (default `'this'`) dan string kode:

```php
echo $js->change('#kota', 'hitungOngkir();');
echo $js->click('#hapus', 'return confirm("Yakin?");', false);
echo $js->hover('#menu', "$('#submenu').show()", "$('#submenu').hide()");
echo $js->keyup('#cari', 'saran()');
```

Tersedia: `blur, change, click, dblclick, error, focus, hover, keydown, keyup, load, mousedown, mouseout, mouseover, mouseup, resize, scroll, unload`.

### 3. Efek & manipulasi class

```php
echo $js->addClass('.kartu', 'aktif');
echo $js->removeClass('.kartu', 'aktif');
echo $js->animate('#bola', ['left' => '200px'], 'slow');
echo $js->fadeIn('#panel', 'fast');
echo $js->fadeOut('#panel', 'fast', 'bersihkan()');
echo $js->slideUp('#drawer');
echo $js->slideDown('#drawer', 'medium');
echo $js->slideToggle('#drawer');
echo $js->hide('#elemen');
echo $js->toggle('#elemen');
```

### 4. Membungkus dengan document-ready

```php
echo $js->ready($js->click('#simpan', 'submitForm();'));
```

### 5. Menulis tag script / aset jQuery

```php
echo $js->script();                              // <script src=".../js/jquery.js">
echo $js->script('assets/plugins/mask.js', true);// path relatif terhadap base js
echo $js->plugin('nifty-corner');                // muat plugin jQuery
echo $js->ui('draggable');                       // muat komponen jQuery UI
echo $js->effect('blind');                       // muat efek jQuery UI
echo $js->modal('#dialog');                      // setup modal window
echo $js->sortable('#daftar', ['constraint' => 'vertical']);
echo $js->tablesorter('#tabel');                 // tabel bisa di-sort
echo $js->corner('#kartu', 'rounded-small');
```

### 6. Output mentah

```php
echo $js->output("console.log('jalan');"); // bungkus <script> sendiri
```

## Referensi API (ringkas)

| Group | Method |
|---|---|
| Event | `blur change click dblclick error focus hover keydown keyup load mousedown mouseout mouseover mouseup resize scroll unload` |
| Efek | `animate fadeIn fadeOut fadeTo slideUp slideDown slideToggle hide show toggle switchClass` |
| Class | `addClass removeClass toggleClass prependContent appendContent addDomEvent` |
| Aset | `script plugin ui effect modal corner sortable tablesorter zebraTables` |
| Util | `ready output alert confirm prompt window_open` |

Semua method pada `Javascript` didelegasikan ke driver (`Jquery`), jadi API-nya identik.

## Kompatibilitas CodeIgniter 3

Merupakan salinan fungsional `CI_Javascript`/`CI_Jquery`; nama kelas & namespace berubah (`Kodhe\Framework\Javascript\Javascript`). Kontruktor tetap memanggil `$this->CI->load->library('Javascript/'.$driver)` sehingga butuh loader CI. Di luar CI (tanpa `kodhe()`), instantiate `Jquery` secara langsung tidak disarankan karena bergantung helper framework.

## Catatan

- Deprecated secara historis; tidak ada fitur baru yang direncanakan.
- Elemen `'this'` hanya valid di dalam konteks event DOM library CI lama.

## Pengujian

```bash
vendor/bin/phpunit --filter Javascript
```
