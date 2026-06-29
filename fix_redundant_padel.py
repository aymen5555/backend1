#!/usr/bin/env python3
"""
Replace REDUNDANT padel images in TerrainSeeder with fresh first-pick outdoor padel IDs.
Only TerrainSeeder.php touched.
"""
import re, os

base = r"C:\Projects\project\backend\database\seeders"
path = os.path.join(base, 'TerrainSeeder.php')

with open(path, 'r', encoding='utf-8') as f:
    text = f.read()

# New FIRST-PICK padel IDs from outdoor/blue search that are NOT currently in TerrainSeeder
new_padel_ids = [
    '35248332',  # woman playing padel tennis indoors
    '35248393',  # young woman playing padel tennis indoors  
    '35248387',  # woman playing padel tennis indoors
    '35248475',  # young woman playing indoor padel tennis
    '35248481',  # woman preparing for padel game on indoor court
    '35248470',  # woman in red attire preparing for padel match
    '35248389',  # woman playing indoor paddle tennis
    '34079414',  # padel racket on sunny court surface
]

# Current padel photo indices in TerrainSeeder (1-based)
padel_indices = [1, 2, 3, 6, 7, 8, 12, 13, 14, 15, 16, 17, 18, 19, 20]

# Find which CURRENT padel images are most likely redundant
# Sequential IDs from same photoshoot are most redundant
redundant_ids = {'35248400', '35248497', '32897040', '32897038', '35248487', '35646550', '35248254'}

# Build list of current padel URLs at those indices
lines = text.split('\n')
current_padel = []
for idx in padel_indices:
    for line in lines:
        if line.strip().startswith(f"{idx} =>"):
            m = re.search(r'photos/(\d+)', line)
            if m:
                current_padel.append((idx, m.group(1)))

print("Current padel IDs at indices:")
for idx, pid in current_padel:
    marker = " <-- REDUNDANT" if pid in redundant_ids else ""
    print(f"  photo {idx:2d}: {pid}{marker}")

# Replace redundant ones with new IDs
new_iter = iter(new_padel_ids)
for idx, pid in current_padel:
    if pid in redundant_ids:
        try:
            new_pid = next(new_iter)
        except StopIteration:
            break
        old_url = f"https://images.pexels.com/photos/{pid}/pexels-photo-{pid}.jpeg"
        new_url = f"https://images.pexels.com/photos/{new_pid}/pexels-photo-{new_pid}.jpeg"
        text = text.replace(old_url, new_url)
        print(f"Replaced photo {idx}: {pid} -> {new_pid}")

with open(path, 'w', encoding='utf-8') as f:
    f.write(text)
print(f"\nTerrainSeeder: replaced redundant padel images with fresh first-pick IDs")
