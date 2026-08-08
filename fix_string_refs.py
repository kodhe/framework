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

print(f"Checking {len(files)} PHP files for string references")

processed = 0
modified = 0

for filepath in files:
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original = content
        
        # Skip jika tidak ada Kodhe sama sekali
        if 'Kodhe\\' not in content:
            continue
        
        # Pattern untuk string references 'Kodhe\...' atau "Kodhe\..."
        # Handle single quotes - perlu escape backslash ganda dalam string
        new_content = re.sub(
            r"'Kodhe\\\\(?!Framework\\\\)",
            r"'Kodhe\\Framework\\",
            content
        )
        
        # Handle double quotes
        new_content = re.sub(
            r'"Kodhe\\\\(?!Framework\\\\)',
            r'"Kodhe\\Framework\\',
            new_content
        )
        
        if new_content != original:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            modified += 1
            print(f"Modified: {filepath}")
        
        processed += 1
    
    except Exception as e:
        print(f"Error processing {filepath}: {e}")

print(f"\n=== Summary ===")
print(f"Files checked: {processed}")
print(f"Files modified: {modified}")
