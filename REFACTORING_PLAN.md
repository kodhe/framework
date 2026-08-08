# Refactoring Plan for PSR Modern Compliance

## Issues Identified

### 1. PSR-12 Coding Style Violations
- Inconsistent spacing and indentation
- Mixed naming conventions (camelCase, snake_case)
- Missing type hints in many places
- Old-style PHP tags (`<?php namespace` instead of `<?php declare(strict_types=1);\n\nnamespace`)

### 2. Class Structure Issues
- Email.php: 2453 lines - needs decomposition
- Kernel.php: Already partially refactored
- Legacy code patterns throughout

### 3. PSR-4 Autoloading
- Namespace structure mostly correct but some inconsistencies
- Some files missing strict_types declaration

### 4. Modern PHP Features Not Used
- No constructor property promotion
- Limited use of union types
- Limited use of attributes
- No enums (PHP 8.1+)

## Refactoring Phases

### Phase 1: Foundation (Critical)
- Add strict_types to all files
- Fix namespace declarations
- Apply consistent coding style

### Phase 2: Class Decomposition
- Break down Email.php into smaller classes
- Extract strategies for email sending

### Phase 3: Modern PHP Features
- Add typed properties everywhere
- Use constructor promotion
- Add return types
- Consider enums for status/constants

### Phase 4: Documentation
- Add proper PHPDoc
- Convert Indonesian comments to English
