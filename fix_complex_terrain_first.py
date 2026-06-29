#!/usr/bin/env python3
"""
Replace ONLY ComplexeSeeder + TerrainSeeder photo IDs.
Best first-choice Pexels images per category.
Nothing else is touched.
"""
import re, os

base = r"C:\Projects\project\backend\database\seeders"

# FIRST-PICK (most popular) Pexels images per category
complex_first = {
    # 6 unique complex images for 6 clubs (2 photos each via counter)
    "c1": "https://images.pexels.com/photos/36293742/pexels-photo-36293742.jpeg?auto=compress&cs=tinysrgb&w=800",  # indoor hall
    "c2": "https://images.pexels.com/photos/12427044/pexels-photo-12427044.jpeg?auto=compress&cs=tinysrgb&w=800",  # multi-sport aerial
    "c3": "https://images.pexels.com/photos/36293741/pexels-photo-36293741.jpeg?auto=compress&cs=tinysrgb&w=800",  # spacious arena
    "c4": "https://images.pexels.com/photos/30747154/pexels-photo-30747154.jpeg?auto=compress&cs=tinysrgb&w=800",  # colorful complex
    "c5": "https://images.pexels.com/photos/917503/pexels-photo-917503.jpeg?auto=compress&cs=tinysrgb&w=800",      # night courts
    "c6": "https://images.pexels.com/photos/33977832/pexels-photo-33977832.jpeg?auto=compress&cs=tinysrgb&w=800",  # large stadium
    "c7": "https://images.pexels.com/photos/30722210/pexels-photo-30722210.jpeg?auto=compress&cs=tinysrgb&w=800",  # aerial soccer match
    "c8": "https://images.pexels.com/photos/30722208/pexels-photo-30722208.jpeg?auto=compress&cs=tinysrgb&w=800",  # tennis soccer basketball
    "c9": "https://images.pexels.com/photos/30575115/pexels-photo-30575115.jpeg?auto=compress&cs=tinysrgb&w=800",  # sports field aerial
    "c10": "https://images.pexels.com/photos/274422/pexels-photo-274422.jpeg?auto=compress&cs=tinysrgb&w=800",    # classic stadium
    "c11": "https://images.pexels.com/photos/3879495/pexels-photo-3879495.jpeg?auto=compress&cs=tinysrgb&w=800",  # sports complex aerial
    "c12": "https://images.pexels.com/photos/3887985/pexels-photo-3887985.jpeg?auto=compress&cs=tinysrgb&w=800",  # outdoor court
}

padel_first = {
    "p1": "https://images.pexels.com/photos/38155778/pexels-photo-38155778.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p2": "https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p3": "https://images.pexels.com/photos/32474981/pexels-photo-32474981.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p4": "https://images.pexels.com/photos/35248286/pexels-photo-35248286.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p5": "https://images.pexels.com/photos/35248374/pexels-photo-35248374.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p6": "https://images.pexels.com/photos/35248487/pexels-photo-35248487.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p7": "https://images.pexels.com/photos/35646550/pexels-photo-35646550.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p8": "https://images.pexels.com/photos/35248501/pexels-photo-35248501.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p9": "https://images.pexels.com/photos/35525977/pexels-photo-35525977.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p10": "https://images.pexels.com/photos/36014317/pexels-photo-36014317.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p11": "https://images.pexels.com/photos/35261961/pexels-photo-35261961.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p12": "https://images.pexels.com/photos/35248497/pexels-photo-35248497.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p13": "https://images.pexels.com/photos/35248400/pexels-photo-35248400.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p14": "https://images.pexels.com/photos/32897040/pexels-photo-32897040.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p15": "https://images.pexels.com/photos/32897038/pexels-photo-32897038.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p16": "https://images.pexels.com/photos/35214843/pexels-photo-35214843.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p17": "https://images.pexels.com/photos/30864601/pexels-photo-30864601.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p18": "https://images.pexels.com/photos/30864597/pexels-photo-30864597.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p19": "https://images.pexels.com/photos/33678503/pexels-photo-33678503.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p20": "https://images.pexels.com/photos/35248254/pexels-photo-35248254.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p21": "https://images.pexels.com/photos/35248279/pexels-photo-35248279.jpeg?auto=compress&cs=tinysrgb&w=800",
    "p22": "https://images.pexels.com/photos/35248269/pexels-photo-35248269.jpeg?auto=compress&cs=tinysrgb&w=800",
}

football_first = {
    "f1": "https://images.pexels.com/photos/14292206/pexels-photo-14292206.jpeg?auto=compress&cs=tinysrgb&w=800",
    "f2": "https://images.pexels.com/photos/9188383/pexels-photo-9188383.jpeg?auto=compress&cs=tinysrgb&w=800",
    "f3": "https://images.pexels.com/photos/17082186/pexels-photo-17082186.jpeg?auto=compress&cs=tinysrgb&w=800",
    "f4": "https://images.pexels.com/photos/33977832/pexels-photo-33977832.jpeg?auto=compress&cs=tinysrgb&w=800",
    "f5": "https://images.pexels.com/photos/3448246/pexels-photo-3448246.jpeg?auto=compress&cs=tinysrgb&w=800",
    "f6": "https://images.pexels.com/photos/28599767/pexels-photo-28599767.jpeg?auto=compress&cs=tinysrgb&w=800",
    "f7": "https://images.pexels.com/photos/4058691/pexels-photo-4058691.jpeg?auto=compress&cs=tinysrgb&w=800",
    "f8": "https://images.pexels.com/photos/917503/pexels-photo-917503.jpeg?auto=compress&cs=tinysrgb&w=800",
    "f9": "https://images.pexels.com/photos/12427044/pexels-photo-12427044.jpeg?auto=compress&cs=tinysrgb&w=800",
    "f10": "https://images.pexels.com/photos/36293760/pexels-photo-36293760.jpeg?auto=compress&cs=tinysrgb&w=800",
}

tennis_first = {
    "t1": "https://images.pexels.com/photos/30894524/pexels-photo-30894524.jpeg?auto=compress&cs=tinysrgb&w=800",
    "t2": "https://images.pexels.com/photos/9739468/pexels-photo-9739468.jpeg?auto=compress&cs=tinysrgb&w=800",
    "t3": "https://images.pexels.com/photos/35564300/pexels-photo-35564300.jpeg?auto=compress&cs=tinysrgb&w=800",
    "t4": "https://images.pexels.com/photos/9739463/pexels-photo-9739463.jpeg?auto=compress&cs=tinysrgb&w=800",
    "t5": "https://images.pexels.com/photos/12427044/pexels-photo-12427044.jpeg?auto=compress&cs=tinysrgb&w=800",
    "t6": "https://images.pexels.com/photos/4170112/pexels-photo-4170112.jpeg?auto=compress&cs=tinysrgb&w=800",
}

# ============================================================
# COMPLEXE SEEDER - WRITE WITH NEW FIRST-PICK IMAGES
# ============================================================
comp_path = os.path.join(base, 'ComplexeSeeder.php')
with open(comp_path, 'r', encoding='utf-8') as f:
    comp = f.read()

# Extract just the photo IDs used and replace with new first-picks
# Keep only first 12 unique photos (avoid dupes)
comp_unique = list(dict.fromkeys(list(complex_first.values())))
# Take first 12 without duplicating
seen = set()
comp_final = []
for url in comp_unique[:12]:
    if url not in seen:
        seen.add(url)
        comp_final.append(url)

# Build new $photos array
new_comp_lines = ["        \$photos = ["]
for i, url in enumerate(comp_final):
    new_comp_lines.append(f"            {i+1} => '{url}',")
new_comp_lines.append("        ];")

# Replace the $photos = [...] block
comp = re.sub(r'\$photos\s*=\s*\[.*?\];', '\n'.join(new_comp_lines), comp, flags=re.DOTALL)

with open(comp_path, 'w', encoding='utf-8') as f:
    f.write(comp)
print(f"ComplexeSeeder: set {len(comp_final)} first-pick complex images")

# ============================================================
# TERRAIN SEEDER - WRITE WITH FIRST-PICK IMAGES BY SPORT
# ============================================================
terr_path = os.path.join(base, 'TerrainSeeder.php')
with open(terr_path, 'r', encoding='utf-8') as f:
    terr = f.read()

# Build first-pick terrain images in order
# Photo order in $this->photos matches $terrains order:
# 1-3 = Olympysky padel, 4-5 = Olympysky football
# 6-8 = Padel House padel, 9-11 = TCT tennis, 12 = Padel Marsa padel
# 13-15 = Padel Marsa padel, 16-18 = Sassi padel, 19-20 = Soukra indoor padel
terrain_photos = {}

# Padel courts: first 22 padel photos from padel_first
padel_keys = [f"p{i}" for i in range(1, 23)]
for i, key in enumerate(padel_keys):
    terrain_photos[i+1] = padel_first[key]

# Override tennis indices (9-11) with tennis_first
tennis_keys = [f"t{i}" for i in range(1, 7)]
terrain_photos[9] = tennis_first["t1"]
terrain_photos[10] = tennis_first["t2"]
terrain_photos[11] = tennis_first["t3"]

# Override football indices (4-5) with football_first
terrain_photos[4] = football_first["f1"]
terrain_photos[5] = football_first["f2"]

new_terr_lines = ["        \$this->photos = ["]
for i in range(1, 23):
    url = terrain_photos[i]
    new_terr_lines.append(f"             {i} => '{url}',")
new_terr_lines.append("        ];")

terr = re.sub(r'\$this->photos\s*=\s*\[.*?\];', '\n'.join(new_terr_lines), terr, flags=re.DOTALL)

with open(terr_path, 'w', encoding='utf-8') as f:
    f.write(terr)
print(f"TerrainSeeder: set 22 first-pick images by sport (padel/football/tennis)")

print("\nDone! Only ComplexeSeeder and TerrainSeeder were modified.")
