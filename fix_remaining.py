#!/usr/bin/env python3
import os
import re

# File-file yang masih perlu di-refactor
files_to_fix = [
    '/workspace/cache/src/Drivers/Redis.php',
    '/workspace/cache/src/Drivers/Wincache.php',
    '/workspace/cache/src/Drivers/File.php',
    '/workspace/cache/src/Drivers/Apc.php',
    '/workspace/cache/src/Drivers/Dummy.php',
    '/workspace/cache/src/Drivers/Memcached.php',
]

for filepath in files_to_fix:
    if not os.path.exists(filepath):
        print(f"File not found: {filepath}")
        continue
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original = content
    
    # Fix namespace declaration
    content = re.sub(
        r'namespace\s+Kodhe\\Cache\\',
        r'namespace Kodhe\\Framework\\Cache\\',
        content
    )
    
    # Fix use statements for Kodhe\Cache
    content = re.sub(
        r'^use\s+Kodhe\\Cache\\',
        r'use Kodhe\\Framework\\Cache\\',
        content,
        flags=re.MULTILINE
    )
    
    # Fix fully qualified calls
    content = re.sub(
        r'\\Kodhe\\Cache\\',
        r'\\Kodhe\\Framework\\Cache\\',
        content
    )
    
    if content != original:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Fixed: {filepath}")
    else:
        print(f"No changes needed: {filepath}")

print("\nDone!")
