#!/usr/bin/env python3
import re
import os

base = r"C:\Projects\project\backend\database\seeders"

# HIGH-QUALITY curated Pexels images
complex_urls = [
    'https://images.pexels.com/photos/36707477/pexels-photo-36707477.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/5269770/pexels-photo-5269770.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/12427044/pexels-photo-12427044.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/7513413/pexels-photo-7513413.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/29789098/pexels-photo-29789098.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/33977832/pexels-photo-33977832.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/9188383/pexels-photo-9188383.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/28599767/pexels-photo-28599767.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/30747154/pexels-photo-30747154.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/4058691/pexels-photo-4058691.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/36293760/pexels-photo-36293760.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/33481606/pexels-photo-33481606.jpeg?auto=compress&cs=tinysrgb&w=800',
]

padel_urls = [
    'https://images.pexels.com/photos/38155778/pexels-photo-38155778.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/32474981/pexels-photo-32474981.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248286/pexels-photo-35248286.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248374/pexels-photo-35248374.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248487/pexels-photo-35248487.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35646550/pexels-photo-35646550.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35525977/pexels-photo-35525977.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248501/pexels-photo-35248501.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35214843/pexels-photo-35214843.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/36014317/pexels-photo-36014317.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35261961/pexels-photo-35261961.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248497/pexels-photo-35248497.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248400/pexels-photo-35248400.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/32897040/pexels-photo-32897040.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/32897038/pexels-photo-32897038.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/33678503/pexels-photo-33678503.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/30864601/pexels-photo-30864601.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/30864597/pexels-photo-30864597.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248269/pexels-photo-35248269.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248279/pexels-photo-35248279.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/10926534/pexels-photo-10926534.jpeg?auto=compress&cs=tinysrgb&w=800',
]

football_urls = [
    'https://images.pexels.com/photos/14292206/pexels-photo-14292206.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/9188383/pexels-photo-9188383.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/17082186/pexels-photo-17082186.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/30970274/pexels-photo-30970274.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/33977832/pexels-photo-33977832.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/3448246/pexels-photo-3448246.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/28599767/pexels-photo-28599767.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/4058691/pexels-photo-4058691.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/917503/pexels-photo-917503.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/12427044/pexels-photo-12427044.jpeg?auto=compress&cs=tinysrgb&w=800',
]

tennis_urls = [
    'https://images.pexels.com/photos/30894524/pexels-photo-30894524.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/9739468/pexels-photo-9739468.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35564300/pexels-photo-35564300.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/9739463/pexels-photo-9739463.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/12427044/pexels-photo-12427044.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/4170112/pexels-photo-4170112.jpeg?auto=compress&cs=tinysrgb&w=800',
]

product_urls = [
    'https://images.pexels.com/photos/4716814/pexels-photo-4716814.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/35529263/pexels-photo-35529263.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/31012863/pexels-photo-31012863.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/6551441/pexels-photo-6551441.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/32610333/pexels-photo-32610333.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/29526372/pexels-photo-29526372.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/13767451/pexels-photo-13767451.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/36327498/pexels-photo-36327498.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/30672398/pexels-photo-30672398.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/37570727/pexels-photo-37570727.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/6628962/pexels-photo-6628962.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/37208480/pexels-photo-37208480.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/29667299/pexels-photo-29667299.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/13885345/pexels-photo-13885345.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/9207813/pexels-photo-9207813.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/8456074/pexels-photo-8456074.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/8473576/pexels-photo-8473576.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/2529147/pexels-photo-2529147.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/11324519/pexels-photo-11324519.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/13450845/pexels-photo-13450845.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/15632866/pexels-photo-15632866.jpeg?auto=compress&cs=tinysrgb&w=400',
    'https://images.pexels.com/photos/15475641/pexels-photo-15475641.jpeg?auto=compress&cs=tinysrgb&w=400',
]

# Build index -> sport mapping for TerrainSeeder
# Order in $this->photos array matches order in $terrains array
terrain_photo_sports = {
    1: 'padel', 2: 'padel', 3: 'padel', 4: 'football', 5: 'football',
    6: 'padel', 7: 'padel', 8: 'padel',
    9: 'tennis', 10: 'tennis', 11: 'tennis', 12: 'padel',
    13: 'padel', 14: 'padel', 15: 'padel',
    16: 'padel', 17: 'padel', 18: 'padel',
    19: 'padel', 20: 'padel',
}

# Build index -> type mapping for PlaySpaceDemoSeeder
# photos 1-6 are complex images, 7+ are terrain images
demo_photo_type = {}
for i in range(1, 27):
    if i <= 6:
        demo_photo_type[i] = 'complex'
    else:
        demo_photo_type[i] = 'terrain'

# Sport mapping for demo terrain photos (based on $terrains1-6 order)
demo_terrain_sports = {
    7: 'padel', 8: 'padel', 9: 'padel', 10: 'football', 11: 'football',
    12: 'padel', 13: 'padel', 14: 'padel',
    15: 'tennis', 16: 'tennis', 17: 'tennis', 18: 'padel',
    19: 'padel', 20: 'padel', 21: 'padel',
    22: 'padel', 23: 'padel', 24: 'football',
    25: 'padel', 26: 'padel',
}

counters = {
    'complex': 0,
    'padel': 0,
    'football': 0,
    'tennis': 0,
    'products': 0,
}

def next_img(category):
    pools = {
        'complex': complex_urls,
        'padel': padel_urls,
        'football': football_urls,
        'tennis': tennis_urls,
        'products': product_urls,
    }
    p = pools[category]
    idx = counters[category] % len(p)
    counters[category] += 1
    return p[idx]

# Process TerrainSeeder - replace by photo index
path = os.path.join(base, 'TerrainSeeder.php')
with open(path, 'r', encoding='utf-8') as f:
    text = f.read()

for idx, sport in terrain_photo_sports.items():
    old_url_pattern = rf"({idx}\s*=>\s*')https://images\.pexels\.com/photos/[^'\s]+(')"
    new_url = next_img(sport)
    text = re.sub(old_url_pattern, lambda m, u=new_url: f"{m.group(1)}{u}{m.group(2)}", text)

with open(path, 'w', encoding='utf-8') as f:
    f.write(text)
print("Processed TerrainSeeder with index-based sport mapping")

# Process PlaySpaceDemoSeeder - complex photos first, then terrain by sport
path = os.path.join(base, 'PlaySpaceDemoSeeder.php')
with open(path, 'r', encoding='utf-8') as f:
    text = f.read()

counters = {k: 0 for k in counters}  # reset counters

for i in range(1, 27):
    if i <= 6:
        category = 'complex'
    else:
        category = demo_terrain_sports.get(i, 'padel')
    
    old_url_pattern = rf"({i}\s*=>\s*')https://images\.pexels\.com/photos/[^'\s]+(')"
    new_url = next_img(category)
    text = re.sub(old_url_pattern, lambda m, u=new_url: f"{m.group(1)}{u}{m.group(2)}", text)

with open(path, 'w', encoding='utf-8') as f:
    f.write(text)
print("Processed PlaySpaceDemoSeeder with index-based mapping")

# Process ComplexeSeeder - replace complex images
path = os.path.join(base, 'ComplexeSeeder.php')
with open(path, 'r', encoding='utf-8') as f:
    text = f.read()

counters = {k: 0 for k in counters}  # reset counters

def replacer_complex(m):
    return next_img('complex')

new_text = re.sub(r'https://images\.pexels\.com/photos/[^"\'\s]+', replacer_complex, text)

with open(path, 'w', encoding='utf-8') as f:
    f.write(new_text)
print("Processed ComplexeSeeder")

# Process ProduitSeeder - replace product images
path = os.path.join(base, 'ProduitSeeder.php')
with open(path, 'r', encoding='utf-8') as f:
    text = f.read()

counters = {k: 0 for k in counters}  # reset counters

def replacer_product(m):
    return next_img('products')

new_text = re.sub(r'https://images\.pexels\.com/photos/[^"\'\s]+', replacer_product, text)

with open(path, 'w', encoding='utf-8') as f:
    f.write(new_text)
print("Processed ProduitSeeder")

# Verify
print("\n=== VERIFICATION ===")
for fname in ['TerrainSeeder.php', 'PlaySpaceDemoSeeder.php', 'ComplexeSeeder.php', 'ProduitSeeder.php']:
    path = os.path.join(base, fname)
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    urls = re.findall(r'https://images\.pexels\.com/photos/\d+', content)
    print(f"{fname}: {len(urls)} URLs")

# Sample TerrainSeeder by sport
path = os.path.join(base, 'TerrainSeeder.php')
with open(path, 'r', encoding='utf-8') as f:
    text = f.read()
lines = text.split('\n')
print("\nTerrainSeeder samples:")
for sport in ['padel', 'football', 'tennis']:
    samples = []
    for i, line in enumerate(lines):
        if 'pexels-photo' in line and 'image_t' in line:
            idx_match = re.search(r'\$this->photos\[(\d+)\]', line)
            if idx_match:
                idx = int(idx_match.group(1))
                if terrain_photo_sports.get(idx) == sport:
                    url = re.search(r'https://images\.pexels\.com/photos/\d+', line)
                    if url and len(samples) < 2:
                        samples.append(url.group(0))
    print(f"  {sadel if sport=='padel' else 'football' if sport=='football' else 'tennis'}: {samples}")

print("\nDone!")
