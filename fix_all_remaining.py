#!/usr/bin/env python3
import os
import re

exclude_dir = '/framework/'
files = []

for root, dirs, filenames in os.walk('/workspace'):
    if exclude_dir in root:
        continue
    
    for filename in filenames:
        if filename.endswith('.php'):
            filepath = os.path.join(root, filename)
            files.append(filepath)

print(f"Checking {len(files)} PHP files for remaining non-Framework namespaces")

processed = 0
modified = 0

for filepath in files:
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original = content
        
        # Skip jika sudah semua pakai Framework atau tidak ada Kodhe sama sekali
        if 'Kodhe\\' not in content:
            continue
        
        # Cek apakah ada namespace Kodhe yang bukan Framework
        has_non_framework = False
        
        # Check namespace declarations
        if re.search(r'namespace\s+Kodhe\\(?!Framework\\)', content):
            has_non_framework = True
        
        # Check use statements
        if re.search(r'^use\s+Kodhe\\(?!Framework\\)', content, flags=re.MULTILINE):
            has_non_framework = True
        
        # Check fully qualified calls
        if re.search(r'\\Kodhe\\(?!Framework\\)', content):
            has_non_framework = True
        
        # Check string references
        if re.search(r"'Kodhe\\\\(?!Framework\\\\)", content):
            has_non_framework = True
        
        if re.search(r'"Kodhe\\\\(?!Framework\\\\)', content):
            has_non_framework = True
        
        if not has_non_framework:
            continue
        
        # Perform replacements
        # Pattern untuk namespace declaration
        content = re.sub(
            r'namespace\s+Kodhe\\(?!Framework\\)',
            r'namespace Kodhe\\Framework\\',
            content
        )
        
        # Pattern untuk use statements
        content = re.sub(
            r'^use\s+Kodhe\\(?!Framework\\)',
            r'use Kodhe\\Framework\\',
            content,
            flags=re.MULTILINE
        )
        
        # Pattern untuk fully qualified calls (\Kodhe\...)
        content = re.sub(
            r'\\Kodhe\\(?!Framework\\)',
            r'\\Kodhe\\Framework\\',
            content
        )
        
        # Pattern untuk string references 'Kodhe\...' atau "Kodhe\..."
        content = re.sub(
            r"'Kodhe\\\\(?!Framework\\\\)",
            r"'Kodhe\\Framework\\",
            content
        )
        
        content = re.sub(
            r'"Kodhe\\\\(?!Framework\\\\)',
            r'"Kodhe\\Framework\\',
            content
        )
        
        if content != original:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            modified += 1
            print(f"Modified: {filepath}")
        
        processed += 1
    
    except Exception as e:
        print(f"Error processing {filepath}: {e}")

print(f"\n=== Summary ===")
print(f"Files checked: {processed}")
print(f"Files modified: {modified}")
