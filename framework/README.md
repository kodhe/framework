# Kodhe Framework

Kodhe Framework Core - Container, Config, Foundation, Exceptions & Support

## Changes in 1.1.1

- Extracted CodeIgniter 3 Legacy Loader (`LegacyLoader`) into a separate package: `kodhe/legacy-loader`
- `FileLoader` still extends `LegacyLoader` for full CI3 compatibility
- Framework now requires `kodhe/legacy-loader`

## Installation

```bash
composer require kodhe/framework
```

The legacy loader will be pulled in automatically.
