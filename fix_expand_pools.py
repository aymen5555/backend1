#!/usr/bin/env python3
"""
Expand ComplexeSeeder + TerrainSeeder image pools with MORE first-pick images
to eliminate redundancy. Products left completely untouched.
"""
import re, os

base = r"C:\Projects\project\backend\database\seeders"

# ============================================================
# COMPLEXE SEEDER - 6 clubs, each gets 2 photos (12 total)
# Larger pool of first-pick stadium/complex photos (800w)
# ============================================================
complex_pool = [
    'https://images.pexels.com/photos/36293742/pexels-photo-36293742.jpeg?auto=compress&cs=tinysrgb&w=800',  # indoor hall
    'https://images.pexels.com/photos/12427044/pexels-photo-12427044.jpeg?auto=compress&cs=tinysrgb&w=800',  # multi-sport aerial
    'https://images.pexels.com/photos/36293741/pexels-photo-36293741.jpeg?auto=compress&cs=tinysrgb&w=800',  # spacious arena
    'https://images.pexels.com/photos/30747154/pexels-photo-30747154.jpeg?auto=compress&cs=tinysrgb&w=800',  # colorful complex
    'https://images.pexels.com/photos/917503/pexels-photo-917503.jpeg?auto=compress&cs=tinysrgb&w=800',      # night courts
    'https://images.pexels.com/photos/33977832/pexels-photo-33977832.jpeg?auto=compress&cs=tinysrgb&w=800',  # large stadium
    'https://images.pexels.com/photos/15480801/pexels-photo-15480801.jpeg?auto=compress&cs=tinysrgb&w=800',  # drone football stadium
    'https://images.pexels.com/photos/16460735/pexels-photo-16460735.jpeg?auto=compress&cs=tinysrgb&w=800',  # estadio omnilife aerial
    'https://images.pexels.com/photos/34304359/pexels-photo-34304359.jpeg?auto=compress&cs=tinysrgb&w=800',  # estadio do dragao
    'https://images.pexels.com/photos/28948294/pexels-photo-28948294.jpeg?auto=compress&cs=tinysrgb&w=800',  # empty football stadium
    'https://images.pexels.com/photos/32803515/pexels-photo-32803515.jpeg?auto=compress&cs=tinysrgb&w=800',  # empty soccer stadium aerial
    'https://images.pexels.com/photos/8312918/pexels-photo-8312918.jpeg?auto=compress&cs=tinysrgb&w=800',    # high angle stadium
    'https://images.pexels.com/photos/35120075/pexels-photo-35120075.jpeg?auto=compress&cs=tinysrgb&w=800',  # aerial night stadium
    'https://images.pexels.com/photos/30722210/pexels-photo-30722210.jpeg?auto=compress&cs=tinysrgb&w=800',  # aerial soccer match
    'https://images.pexels.com/photos/30722208/pexels-photo-30722208.jpeg?auto=compress&cs=tinysrgb&w=800',  # tennis soccer basketball
    'https://images.pexels.com/photos/30575115/pexels-photo-30575115.jpeg?auto=compress&cs=tinysrgb&w=800',  # sports field aerial
    'https://images.pexels.com/photos/274422/pexels-photo-274422.jpeg?auto=compress&cs=tinysrgb&w=800',    # classic stadium
    'https://images.pexels.com/photos/3879495/pexels-photo-3879495.jpeg?auto=compress&cs=tinysrgb&w=800',  # sports complex aerial
    'https://images.pexels.com/photos/3887985/pexels-photo-3887985.jpeg?auto=compress&cs=tinysrgb&w=800',  # outdoor court
    'https://images.pexels.com/photos/36293760/pexels-photo-36293760.jpeg?auto=compress&cs=tinysrgb&w=800',  # modern complex delhi
]

# ============================================================
# TERRAIN SEEDER - 22 photos total
# Larger diverse pools per sport (800w)
# ============================================================
padel_pool = [
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
    'https://images.pexels.com/photos/35248393/pexels-photo-35248393.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248387/pexels-photo-35248387.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248475/pexels-photo-35248475.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248481/pexels-photo-35248481.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248470/pexels-photo-35248470.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248389/pexels-photo-35248389.jpeg?auto=compress&cs=tinysrgb&w=800',
]

football_pool = [
    'https://images.pexels.com/photos/14292206/pexels-photo-14292206.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/9188383/pexels-photo-9188383.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/17082186/pexels-photo-17082186.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/33977832/pexels-photo-33977832.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/3448246/pexels-photo-3448246.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/28599767/pexels-photo-28599767.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/4058691/pexels-photo-4058691.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/917503/pexels-photo-917503.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/12427044/pexels-photo-12427044.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/17896212/pexels-photo-17896212.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/33210166/pexels-photo-33210166.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/17779075/pexels-photo-17779075.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/9410605/pexels-photo-9410605.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/11300376/pexels-photo-11300376.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/37430612/pexels-photo-37430612.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/37158103/pexels-photo-37158103.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/37767349/pexels-photo-37767349.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/12541271/pexels-photo-12541271.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/25287801/pexels-photo-25287801.jpeg?auto=compress&cs=tinysrgb&w=800',
]

tennis_pool = [
    'https://images.pexels.com/photos/30894524/pexels-photo-30894524.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/9739468/pexels-photo-9739468.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35564300/pexels-photo-35564300.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/9739463/pexels-photo-9739463.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/12427044/pexels-photo-12427044.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/4170112/pexels-photo-4170112.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/30894524/pexels-photo-30894524.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/9739462/pexels-photo-9739462.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/27151849/pexels-photo-27151849.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/6931243/pexels-photo-6931243.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/3845084/pexels-photo-3845084.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/4582494/pexels-photo-4582494.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/13425628/pexels-photo-13425628.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/5666179/pexels-photo-5666179.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/3845095/pexels-photo-3845095.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/36034890/pexels-photo-36034890.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/17368514/pexels-photo-17368514.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/29893638/pexels-photo-29893638.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/27440719/pexels-photo-27440719.jpeg?auto=compress&cs=tinysrgb&w=800',
]

# Deduplicate each pool
def dedup(lst):
    seen = set()
    out = []
    for x in lst:
        if x not in seen:
            seen.add(x)
            out.append(x)
    return out

complex_pool = dedup(complex_pool)
padel_pool = dedup(padel_pool)
football_pool = dedup(football_pool)
tennis_pool = dedup(tennis_pool)

# ============================================================
# COMPLEXE SEEDER
# Photo order in file: 1=Olympysky, 2=Padel House, 3=TCT, 4=Padel Marsa, 5=Sassi, 6=Soukra
# Each has 2 photos via sequential counter
# ============================================================
comp_path = os.path.join(base, 'ComplexeSeeder.php')
with open(comp_path, 'r', encoding='utf-8') as f:
    comp = f.read()

complex_counts = {
    'Olympysky Club': 2,
    'Padel House Tunisia': 2,
    'Tennis Club de Tunis': 2,
    'Padel Marsa': 2,
    'Sassi Padel Club': 2,
    'Padel Indoor La Soukra': 2,
}

# Sequential counter for complexes
comp_counter = 0

# Replace photo URLs by club name pattern
for name, count in complex_counts.items():
    photos_for_club = []
    for _ in range(count):
        url = complex_pool[comp_counter % len(complex_pool)]
        comp_counter += 1
        photos_for_club.append(url)
    
    # Find the club block and replace its image_c lines
    # Pattern: 'image_c' => $photos['Club Name'],
    # We need to replace 2 occurrences with the two URLs
    pattern = rf"('{re.escape(name)}' => \[.*?'image_c' => )\$photos\['{re.escape(name)}'\]"
    
    # Simpler: find all occurrences of the club name in array context
    # and replace image_c values sequentially
    club_pattern = rf"(\['{re.escape(name)}'\][^]]*?'image_c' => )\$photos\['{re.escape(name)}'\]"
    
    def replace_club_photos(m, photos=photos_for_club):
        # We need to replace both occurrences in the club block
        return m.group(1) + f"'{photos.pop(0)}'"
    
    # Find and replace all image_c for this club
    idx = 0
    def replacer_for_club(m, photos=photos_for_club, idx_ref=[0]):
        url = photos[idx_ref[0] % len(photos)]
        idx_ref[0] += 1
        return f"'image_c' => '{url}'"
    
    comp = re.sub(rf"'image_c'\s*=>\s*\$photos\['{re.escape(name)}'\]", 
                  lambda m, name=name, pool=complex_pool, counter_ref=[0]: f"'image_c' => '{pool[counter_ref[0] % len(pool)]}'" if counter_ref[0] < 2 else m.group(0),
                  comp, count=2)

# Actually, the complex seeder has 1 image per complex (just image_c)
# Not 2. Let me re-read the structure...
# Actually ComplexeSeeder has 6 complexes, each with 1 image_c
# The fix_dedup.py logic assigned pr1 consistently because all URLs matched raquettes pattern
# But wait - looking at the file, each complex only has 1 image_c, not 2 photos

# For ComplexeSeeder: 6 clubs, each needs 1 unique image
comp_counter = 0
complex_pool_iter = iter(complex_pool)

for name in ['Olympysky Club', 'Padel House Tunisia', 'Tennis Club de Tunis', 
             'Padel Marsa', 'Sassi Padel Club', 'Padel Indoor La Soukra']:
    new_url = next(complex_pool_iter)
    comp = re.sub(
        rf"('{re.escape(name)}' => \[.*?'image_c' => ')([^']+)(')",
        lambda m, u=new_url: m.group(1) + u + m.group(3),
        comp,
        count=1,
        flags=re.DOTALL
    )

with open(comp_path, 'w', encoding='utf-8') as f:
    f.write(comp)
print(f"ComplexeSeeder: assigned 6 diverse complex images")

# ============================================================
# TERRAIN SEEDER
# 22 photos mapped to 20 terrains (some share same image_c)
# Sport mapping:
#   1-3: padel (Olympysky), 4-5: football (Olympysky)
#   6-8: padel (Padel House), 9-11: tennis (TCT)
#   12: padel (Padel Marsa), 13-15: padel (Padel Marsa), 16-18: padel (Sassi)
#   19-20: padel (Soukra)
# 21-22: unused (extra in array)
# ============================================================
terr_path = os.path.join(base, 'TerrainSeeder.php')
with open(terr_path, 'r', encoding='utf-8') as f:
    terr = f.read()

# Photo indices (1-22) -> sport type
photo_sport = {
    1: 'padel', 2: 'padel', 3: 'padel',
    4: 'football', 5: 'football',
    6: 'padel', 7: 'padel', 8: 'padel',
    9: 'tennis', 10: 'tennis', 11: 'tennis',
    12: 'padel', 13: 'padel', 14: 'padel',
    15: 'padel', 16: 'padel', 17: 'padel',
    18: 'padel', 19: 'padel', 20: 'padel',
    21: 'padel', 22: 'padel',
}

pools = {
    'padel': iter(padel_pool),
    'football': iter(football_pool),
    'tennis': iter(tennis_pool),
}

for idx in range(1, 23):
    sport = photo_sport[idx]
    new_url = next(pools[sport])
    pattern = rf"({idx}\s*=>\s*')(https://images\.pexels\.com/photos/[^'\s]+)(')"
    replacement = f"{idx} => '{new_url}'"
    terr = re.sub(pattern, replacement, terr, count=1)

with open(terr_path, 'w', encoding='utf-8') as f:
    f.write(terr)
print(f"TerrainSeeder: assigned 22 diverse images (15 padel, 2 football, 3 tennis + extras)")

print("\nDone - ComplexeSeeder + TerrainSeeder updated with larger varied pools")
print(f"Padel pool size: {len(padel_pool)}")
print(f"Football pool size: {len(football_pool)}")
print(f"Tennis pool size: {len(tennis_pool)}")
