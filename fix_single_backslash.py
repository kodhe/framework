#!/usr/bin/env python3
import os
import re

# File-file yang memiliki string dengan single backslash
files_to_fix = [
    '/workspace/upload/src/compat.php',
    '/workspace/xmlrpcs/src/Xmlrpcs.php',
    '/workspace/email/src/compat.php',
    '/workspace/http/src/Routing/UnifiedRouter.php',
    '/workspace/http/src/Routing/LegacyRouter.php',
]

for filepath in files_to_fix:
    if not os.path.exists(filepath):
        print(f"File not found: {filepath}")
        continue
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original = content
    
    # Fix string references dengan single backslash
    # Pattern: 'Kodhe\Something' -> 'Kodhe\Framework\Something'
    content = re.sub(
        r"'Kodhe\\(?!Framework\\)",
        r"'Kodhe\\Framework\\",
        content
    )
    
    # Handle double quotes juga
    content = re.sub(
        r'"Kodhe\\(?!Framework\\)',
        r'"Kodhe\\Framework\\',
        content
    )
    
    if content != original:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Fixed: {filepath}")
    else:
        print(f"No changes: {filepath}")

print("\nDone!")
