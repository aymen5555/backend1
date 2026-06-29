#!/usr/bin/env python3
import re
import os

base = r"C:\Projects\project\backend\database\seeders"

# BEST PADEL IMAGES - hand-picked from live Pexels search
best_padel = [
    'https://images.pexels.com/photos/38155778/pexels-photo-38155778.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/32474981/pexels-photo-32474981.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248286/pexels-photo-35248286.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248374/pexels-photo-35248374.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248487/pexels-photo-35248487.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248501/pexels-photo-35248501.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35646550/pexels-photo-35646550.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35525977/pexels-photo-35525977.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/36014317/pexels-photo-36014317.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35261961/pexels-photo-35261961.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248497/pexels-photo-35248497.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248400/pexels-photo-35248400.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/32897040/pexels-photo-32897040.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/32897038/pexels-photo-32897038.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35214843/pexels-photo-35214843.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/30864601/pexels-photo-30864601.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/30864597/pexels-photo-30864597.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/33678503/pexels-photo-33678503.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248254/pexels-photo-35248254.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248279/pexels-photo-35248279.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248269/pexels-photo-35248269.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248332/pexels-photo-35248332.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/34079998/pexels-photo-34079998.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248470/pexels-photo-35248470.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248393/pexels-photo-35248393.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248389/pexels-photo-35248389.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248475/pexels-photo-35248475.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/36227708/pexels-photo-36227708.jpeg?auto=compress&cs=tinysrgb&w=800',
]

# Photo indices that are padel courts
padel_indices = {
    'TerrainSeeder.php': [
        1, 2, 3,           # Olympysky padel A,B,C
        6, 7, 8,           # Padel House indoor 1,2,3
        12,                # TCT padel
        13, 14, 15,        # Padel Marsa 1,2,3
        16, 17, 18,        # Sassi 1,2,3
        19, 20,            # Soukra indoor 1,2
    ],
    'PlaySpaceDemoSeeder.php': [
        7, 8, 9,           # Olympysky padel A,B,C
        12, 13, 14,        # Padel House indoor 1,2,3
        18,                # TCT padel
        19, 20, 21,        # Padel Marsa vue mer 1,2 + couvert
        22, 23,            # Sassi padel A,B
        25, 26,            # Soukra premium 1,2
    ],
}

# Shared counter so no duplicates BETWEEN seeders either
shared_counter = 0

for fname, indices in padel_indices.items():
    path = os.path.join(base, fname)
    with open(path, 'r', encoding='utf-8') as f:
        text = f.read()

    for idx in indices:
        url = best_padel[shared_counter % len(best_padel)]
        shared_counter += 1
        pattern = rf"({idx}\s*=>\s*')https://images\.pexels\.com/photos/[^'\s]+(')"
        replacement = f"{idx} => '{url}'"
        text = re.sub(pattern, replacement, text)

    with open(path, 'w', encoding='utf-8') as f:
        f.write(text)
    print(f"{fname}: replaced {len(indices)} padel images (no duplicates)")

print(f"\nTotal padel images used: {shared_counter}")
print("Done!")
