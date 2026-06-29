#!/usr/bin/env python3
"""
Fix ComplexeSeeder: replace ALL 6 inline image_c URLs with unique first-pick images.
"""
import re, os

base = r"C:\Projects\project\backend\database\seeders"
path = os.path.join(base, 'ComplexeSeeder.php')

with open(path, 'r', encoding='utf-8') as f:
    text = f.read()

# 6 UNIQUE first-pick images
complex_images = [
    'https://images.pexels.com/photos/36293742/pexels-photo-36293742.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/12427044/pexels-photo-12427044.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/36293741/pexels-photo-36293741.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/30747154/pexels-photo-30747154.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/917503/pexels-photo-917503.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/33977832/pexels-photo-33977832.jpeg?auto=compress&cs=tinysrgb&w=800',
]

# Find all image_c lines and replace sequentially
img_c_lines = []
lines = text.split('\n')
new_lines = []
img_idx = 0

for line in lines:
    if "'image_c'" in line and "pexels-photo" in line and img_idx < len(complex_images):
        # Replace the entire image_c line
        indent = line[:len(line) - len(line.lstrip())]
        new_line = f"{indent}'image_c' => '{complex_images[img_idx]}',"
        new_lines.append(new_line)
        img_idx += 1
        print(f"Replaced image_c #{img_idx} with photo {complex_images[img_idx-1].split('/photos/')[1][:10]}")
    else:
        new_lines.append(line)

with open(path, 'w', encoding='utf-8') as f:
    f.write('\n'.join(new_lines))

print(f"\nTotal image_c replaced: {img_idx}")

# Verify all different
with open(path, 'r', encoding='utf-8') as f:
    final = f.read()
ids = re.findall(r"'image_c'\s*=>\s*'[^']*photos/(\d+)", final)
print(f"Unique IDs: {len(set(ids))} / {len(ids)}")
if len(set(ids)) == len(ids):
    print("SUCCESS: All 6 complexes have unique images!")
else:
    print("WARNING: Some complexes still share images")
