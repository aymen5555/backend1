#!/usr/bin/env python3
"""
Replace only ComplexeSeeder + TerrainSeeder images with BETTER quality ones.
ProduitSeeder, PlaySpaceDemoSeeder are NOT touched.
Tennis courts get blue/Miami aerial shots (more modern than green ball).
"""
import re, os

base = r"C:\Projects\project\backend\database\seeders"

# ============================================================
# COMPLEXE SEEDER (12 photos total, 2 each)
# Better modern sports facility photos (800w)
# ============================================================
complex_path = os.path.join(base, 'ComplexeSeeder.php')
with open(complex_path, 'r', encoding='utf-8') as f:
    comp_text = f.read()

# Current good complex images we KEEP:
# c1: empty-sports-arena-5269770 (already good)
# c2: top-view-tennis-football-12427044 (already good)
# c3: aerial-sports-field-4170112 (already good)
# c4: aerial-photography-football-9188383 (already good)
# c5: sports-court-illustration-917503 (night courts - we replace)
# c6: tennis-court-blue-surface-9739468 (blue aerial - good)
# c7: outdoor-tennis-court-lights-35564300 (tennis with lights - good)
# c8: aerial-soccer-match-3448246 (soccer stadium aerial)
# c9: large-football-stadium-33977832 (big stadium)
# c10: stadium-28599767 (clean stadium)

new_complex_photos = {
    1:  'https://images.pexels.com/photos/5269770/pexels-photo-5269770.jpeg?auto=compress&cs=tinysrgb&w=800',
    2:  'https://images.pexels.com/photos/12427044/pexels-photo-12427044.jpeg?auto=compress&cs=tinysrgb&w=800',
    3:  'https://images.pexels.com/photos/4170112/pexels-photo-4170112.jpeg?auto=compress&cs=tinysrgb&w=800',
    4:  'https://images.pexels.com/photos/9188383/pexels-photo-9188383.jpeg?auto=compress&cs=tinysrgb&w=800',
    5:  'https://images.pexels.com/photos/29248906/pexels-photo-29248906.jpeg?auto=compress&cs=tinysrgb&w=800',
    6:  'https://images.pexels.com/photos/9739468/pexels-photo-9739468.jpeg?auto=compress&cs=tinysrgb&w=800',
    7:  'https://images.pexels.com/photos/35564300/pexels-photo-35564300.jpeg?auto=compress&cs=tinysrgb&w=800',
    8:  'https://images.pexels.com/photos/3448246/pexels-photo-3448246.jpeg?auto=compress&cs=tinysrgb&w=800',
    9:  'https://images.pexels.com/photos/33977832/pexels-photo-33977832.jpeg?auto=compress&cs=tinysrgb&w=800',
    10: 'https://images.pexels.com/photos/28599767/pexels-photo-28599767.jpeg?auto=compress&cs=tinysrgb&w=800',
    11: 'https://images.pexels.com/photos/2178172/pexels-photo-2178172.jpeg?auto=compress&cs=tinysrgb&w=800',
    12: 'https://images.pexels.com/photos/3621104/pexels-photo-3621104.jpeg?auto=compress&cs=tinysrgb&w=800',
}

for idx, new_url in new_complex_photos.items():
    pattern = rf'({idx}\s*=>\s*\')(https://images\.pexels\.com/photos/[^\'\s]+)(\')'
    replacement = f'{idx} => \'{new_url}\''
    comp_text = re.sub(pattern, replacement, comp_text)

with open(complex_path, 'w', encoding='utf-8') as f:
    f.write(comp_text)
print(f"ComplexeSeeder: updated {len(new_complex_photos)} images")

# ============================================================
# TERRAIN SEEDER (22 photos)
# Better high-quality photos for tennis courts (indices 9-11)
# Padel and football already good from fix_dedup.py
# ============================================================
terrain_path = os.path.join(base, 'TerrainSeeder.php')
with open(terrain_path, 'r', encoding='utf-8') as f:
    terr_text = f.read()

# Only replace tennis court photos [9-11] with better aerial shots
# Padel (1-3, 6-8, 12-20) and football (4-5) stay as-is from fix_dedup.py
terrain_tennis_replacements = {
    9:  'https://images.pexels.com/photos/30894524/pexels-photo-30894524.jpeg?auto=compress&cs=tinysrgb&w=600',  # red clay aerial
    10: 'https://images.pexels.com/photos/29893638/pexels-photo-29893638.jpeg?auto=compress&cs=tinysrgb&w=600',  # blue courts Miami
    11: 'https://images.pexels.com/photos/27440719/pexels-photo-27440719.jpeg?auto=compress&cs=tinysrgb&w=600',  # blue surface
}

for idx, new_url in terrain_tennis_replacements.items():
    pattern = rf'({idx}\s*=>\s*\')(https://images\.pexels\.com/photos/[^\'\s]+)(\')'
    replacement = f'{idx} => \'{new_url}\''
    terr_text = re.sub(pattern, replacement, terr_text)

with open(terrain_path, 'w', encoding='utf-8') as f:
    f.write(terr_text)
print(f"TerrainSeeder: updated {len(terrain_tennis_replacements)} tennis court images")
print("Done - ONLY ComplexeSeeder and TerrainSeeder touched")
