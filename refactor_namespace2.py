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

print(f"Found {len(files)} PHP files to process")

processed = 0
modified = 0

for filepath in files:
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original = content
        
        # Skip jika sudah ada Kodhe\Framework
        if 'Kodhe\\Framework' in content:
            continue
        
        # Pattern untuk namespace declaration - handle various whitespace
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
        # Handle single quotes - perlu escape backslash ganda dalam string
        content = re.sub(
            r"'Kodhe\\\\(?!Framework\\\\)",
            r"'Kodhe\\Framework\\",
            content
        )
        
        # Handle double quotes
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
        if processed % 100 == 0:
            print(f"Processed {processed} files...")
    
    except Exception as e:
        print(f"Error processing {filepath}: {e}")

print("\n=== Summary ===")
print(f"Total files processed: {processed}")
print(f"Files modified: {modified}")
print(f"Files unchanged: {processed - modified}")
