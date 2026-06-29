#!/usr/bin/env python3
"""
Replace ONLY the 21 product images in ProduitSeeder.php.
Direct index 101→121 assignment with MEANINGFUL category-matched photos.
No other seeder files touched.
"""
import re, os

base = r"C:\Projects\project\backend\database\seeders"
path = os.path.join(base, 'ProduitSeeder.php')

with open(path, 'r', encoding='utf-8') as f:
    text = f.read()

# MEANINGFUL product photos - one per product index (sequential)
# 101 → racket, 102 → ball, 103 → shoe, 104 → glove, 105 → racket beginner
# 106 → padel balls, 107 → padel shoe, 108 → grip, 109 → tennis racket
# 110 → tennis balls, 111 → clay shoe, 112 → tennis string, 113 → padel bag
# 114 → t-shirt, 115 → shorts, 116 → wristband, 117 → water bottle
# 118 → padel glasses, 119 → yoga mat, 120 → dumbbells, 121 → jump rope
index_images = {
    101: "https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?auto=compress&cs=tinysrgb&w=400",
    102: "https://images.pexels.com/photos/7154759/pexels-photo-7154759.jpeg?auto=compress&cs=tinysrgb&w=400",
    103: "https://images.pexels.com/photos/19882423/pexels-photo-19882423.jpeg?auto=compress&cs=tinysrgb&w=400",
    104: "https://images.pexels.com/photos/6296109/pexels-photo-6296109.jpeg?auto=compress&cs=tinysrgb&w=400",
    105: "https://images.pexels.com/photos/35646550/pexels-photo-35646550.jpeg?auto=compress&cs=tinysrgb&w=400",
    106: "https://images.pexels.com/photos/1277397/pexels-photo-1277397.jpeg?auto=compress&cs=tinysrgb&w=400",
    107: "https://images.pexels.com/photos/2529148/pexels-photo-2529148.jpeg?auto=compress&cs=tinysrgb&w=400",
    108: "https://images.pexels.com/photos/226562/pexels-photo-226562.jpeg?auto=compress&cs=tinysrgb&w=400",
    109: "https://images.pexels.com/photos/10340620/pexels-photo-10340620.jpeg?auto=compress&cs=tinysrgb&w=400",
    110: "https://images.pexels.com/photos/226565/pexels-photo-226565.jpeg?auto=compress&cs=tinysrgb&w=400",
    111: "https://images.pexels.com/photos/1511047/pexels-photo-1511047.jpeg?auto=compress&cs=tinysrgb&w=400",
    112: "https://images.pexels.com/photos/11324519/pexels-photo-11324519.jpeg?auto=compress&cs=tinysrgb&w=400",
    113: "https://images.pexels.com/photos/5698851/pexels-photo-5698851.jpeg?auto=compress&cs=tinysrgb&w=400",
    114: "https://images.pexels.com/photos/8473576/pexels-photo-8473576.jpeg?auto=compress&cs=tinysrgb&w=400",
    115: "https://images.pexels.com/photos/13450845/pexels-photo-13450845.jpeg?auto=compress&cs=tinysrgb&w=400",
    116: "https://images.pexels.com/photos/9644820/pexels-photo-9644820.jpeg?auto=compress&cs=tinysrgb&w=400",
    117: "https://images.pexels.com/photos/4162457/pexels-photo-4162457.jpeg?auto=compress&cs=tinysrgb&w=400",
    118: "https://images.pexels.com/photos/8436578/pexels-photo-8436578.jpeg?auto=compress&cs=tinysrgb&w=400",
    119: "https://images.pexels.com/photos/6339731/pexels-photo-6339731.jpeg?auto=compress&cs=tinysrgb&w=400",
    120: "https://images.pexels.com/photos/4047134/pexels-photo-4047134.jpeg?auto=compress&cs=tinysrgb&w=400",
    121: "https://images.pexels.com/photos/3822166/pexels-photo-3822166.jpeg?auto=compress&cs=tinysrgb&w=400",
}

lines = text.split('\n')
new_lines = []
changed = 0

for line in lines:
    m = re.match(r'(\s*)(\d+)(\s*=>\s*\')([^\']+)(\'\s*,?\s*)', line)
    if m:
        idx = int(m.group(2))
        if 101 <= idx <= 121 and idx in index_images:
            new_url = index_images[idx]
            line = f"{m.group(1)}{idx}{m.group(3)}{new_url}{m.group(5)}"
            changed += 1
    new_lines.append(line)

with open(path, 'w', encoding='utf-8') as f:
    f.write('\n'.join(new_lines))

print(f"Changed {changed} product images in ProduitSeeder")

# Final verification
with open(path, 'r', encoding='utf-8') as f:
    final = f.read()

print("\nFinal product images:")
for idx, url in index_images.items():
    in_file = url in final
    uid = url.split('/photos/')[1][:10]
    status = "OK" if in_file else "MISSING"
    print(f"  {idx}: {uid} [{status}]")
