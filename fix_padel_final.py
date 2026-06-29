#!/usr/bin/env python3
import re
import os

base = r"C:\Projects\project\backend\database\seeders"

# BEST PADEL IMAGES - from live Pexels search (modern, vibrant, high quality)
best_padel_800 = [
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
    'https://images.pexels.com/photos/35248332/pexels-photo-35248332.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248475/pexels-photo-35248475.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248481/pexels-photo-35248481.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248256/pexels-photo-35248256.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/34079998/pexels-photo-34079998.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248470/pexels-photo-35248470.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248393/pexels-photo-35248393.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248389/pexels-photo-35248389.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/36227708/pexels-photo-36227708.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/34079414/pexels-photo-34079414.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248387/pexels-photo-35248387.jpeg?auto=compress&cs=tinysrgb&w=800',
]

# TerrainSeeder padel photo indices
terrain_padel = [1, 2, 3, 6, 7, 8, 12, 13, 14, 15, 16, 17, 18, 19, 20]

# PlaySpaceDemoSeeder padel photo indices
demo_padel = [7, 8, 9, 12, 13, 14, 18, 19, 20, 21, 22, 23, 25, 26]

shared_idx = 0

def replace_padel_indices(fname, padel_indices):
    global shared_idx
    path = os.path.join(base, fname)
    with open(path, 'r', encoding='utf-8') as f:
        text = f.read()
    
    lines = text.split('\n')
    new_lines = []
    for line in lines:
        m = re.match(r'(\s*)(\d+)(\s*=>\s*\')(https://images\.pexels\.com/photos/\d+[^\'\s]*)(\'\s*,?\s*)', line)
        if m:
            idx = int(m.group(2))
            if idx in padel_indices:
                new_url = best_padel_800[shared_idx % len(best_padel_800)]
                shared_idx += 1
                line = f"{m.group(1)}{idx}{m.group(3)}{new_url}{m.group(5)}"
        new_lines.append(line)
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write('\n'.join(new_lines))
    print(f"{fname}: replaced {len(padel_indices)} padel images")

replace_padel_indices('TerrainSeeder.php', terrain_padel)
replace_padel_indices('PlaySpaceDemoSeeder.php', demo_padel)

print(f"\nTotal padel images assigned: {shared_idx}")
print("Done - ONLY padel images changed, all other categories untouched")
