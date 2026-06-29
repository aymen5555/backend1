#!/usr/bin/env python3
import re
import os

base = r"C:\Projects\project\backend\database\seeders"

# Fresh image pool from live Pexels searches (June 2026)
# Phase 1: sports complexes, stadiums, indoor arenas (800w)
complex_pool = [
    'https://images.pexels.com/photos/30747154/pexels-photo-30747154.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/4058691/pexels-photo-4058691.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/9052923/pexels-photo-9052923.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/14292206/pexels-photo-14292206.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/20414085/pexels-photo-20414085.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/9188383/pexels-photo-9188383.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35564300/pexels-photo-35564300.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/33977832/pexels-photo-33977832.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/7513413/pexels-photo-7513413.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/36293760/pexels-photo-36293760.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/30970274/pexels-photo-30970274.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/12427044/pexels-photo-12427044.jpeg?auto=compress&cs=tinysrgb&w=800',
]

# Phase 2: padel courts (800w)
padel_pool = [
    'https://images.pexels.com/photos/38155778/pexels-photo-38155778.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/32474981/pexels-photo-32474981.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248286/pexels-photo-35248286.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248374/pexels-photo-35248374.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248487/pexels-photo-35248487.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35646550/pexels-photo-35646550.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248269/pexels-photo-35248269.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35525977/pexels-photo-35525977.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/30864597/pexels-photo-30864597.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248501/pexels-photo-35248501.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35214843/pexels-photo-35214843.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/30864601/pexels-photo-30864601.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/36014317/pexels-photo-36014317.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35261961/pexels-photo-35261961.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248497/pexels-photo-35248497.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248400/pexels-photo-35248400.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/32897040/pexels-photo-32897040.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/32897038/pexels-photo-32897038.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35248279/pexels-photo-35248279.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/33678503/pexels-photo-33678503.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/10926534/pexels-photo-10926534.jpeg?auto=compress&cs=tinysrgb&w=800',
]

# Phase 3: football fields (800w)
football_pool = [
    'https://images.pexels.com/photos/12427044/pexels-photo-12427044.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/14292206/pexels-photo-14292206.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/9188383/pexels-photo-9188383.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/33977832/pexels-photo-33977832.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/17082186/pexels-photo-17082186.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/30970274/pexels-photo-30970274.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/3448246/pexels-photo-3448246.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/28599767/pexels-photo-28599767.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/4058691/pexels-photo-4058691.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/917503/pexels-photo-917503.jpeg?auto=compress&cs=tinysrgb&w=800',
]

# Phase 4: tennis courts (800w)
tennis_pool = [
    'https://images.pexels.com/photos/30894524/pexels-photo-30894524.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/9739468/pexels-photo-9739468.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/35564300/pexels-photo-35564300.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/9739463/pexels-photo-9739463.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/12427044/pexels-photo-12427044.jpeg?auto=compress&cs=tinysrgb&w=800',
    'https://images.pexels.com/photos/4170112/pexels-photo-4170112.jpeg?auto=compress&cs=tinysrgb&w=800',
]

# Phase 5: product images (400w) - gym equipment, sportswear, accessories
product_pool = [
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

counters = {
    'complex': 0,
    'padel': 0,
    'football': 0,
    'tennis': 0,
    'products': 0,
}

def next_img(category):
    pools = {
        'complex': complex_pool,
        'padel': padel_pool,
        'football': football_pool,
        'tennis': tennis_pool,
        'products': product_pool,
    }
    p = pools[category]
    idx = counters[category] % len(p)
    counters[category] += 1
    return p[idx]

def detect_sport_context(text, match_pos):
    """Look backward from match position to find nearest sport_type keyword."""
    before = text[:match_pos]
    # Find nearest sport_type assignment
    # Look for patterns like 'sport_type' => 'padel' or 'football' or 'tennis'
    patterns = [
        (r"'sport_type'\s*=>\s*'padel'", 'padel'),
        (r"'sport_type'\s*=>\s*'football'", 'football'),
        (r"'sport_type'\s*=>\s*'tennis'", 'tennis'),
        (r"sport_type.*?padel", 'padel'),
        (r"sport_type.*?football", 'football'),
        (r"sport_type.*?tennis", 'tennis'),
    ]
    for pattern, sport in patterns:
        matches = list(re.finditer(pattern, before, re.IGNORECASE))
        if matches:
            return sport
    return 'complex'

for fname in ['PlaySpaceDemoSeeder.php', 'TerrainSeeder.php', 'ProduitSeeder.php', 'ComplexeSeeder.php']:
    path = os.path.join(base, fname)
    with open(path, 'r', encoding='utf-8') as f:
        text = f.read()

    if fname == 'ComplexeSeeder.php':
        # Replace all pexels URLs with complex images
        def replacer_complex(m):
            return next_img('complex')
        new_text = re.sub(r'https://images\.pexels\.com/photos/[^"\'\s]+', replacer_complex, text)
    elif fname in ('TerrainSeeder.php', 'PlaySpaceDemoSeeder.php'):
        # Context-aware replacement for terrain/demo
        def replacer_terrain(m):
            sport = detect_sport_context(text, m.start())
            return next_img(sport)
        new_text = re.sub(r'https://images\.pexels\.com/photos/[^"\'\s]+', replacer_terrain, text)
    elif fname == 'ProduitSeeder.php':
        # Replace all product image URLs (w=400)
        def replacer_product(m):
            return next_img('products')
        new_text = re.sub(r'https://images\.pexels\.com/photos/[^"\'\s]+', replacer_product, text)

    with open(path, 'w', encoding='utf-8') as f:
        f.write(new_text)
    print(f"Processed {fname}")

# Verify no duplicates across all seeders
all_urls = []
for fname in ['ComplexeSeeder.php', 'TerrainSeeder.php', 'ProduitSeeder.php', 'PlaySpaceDemoSeeder.php']:
    path = os.path.join(base, fname)
    with open(path, 'r', encoding='utf-8') as f:
        text = f.read()
    all_urls.extend(re.findall(r'https://images\.pexels\.com/photos/\d+', text))

dupes = [u for u in set(all_urls) if all_urls.count(u) > 1]
if dupes:
    print(f"WARNING: Duplicate URLs found: {dupes}")
else:
    print(f"OK: {len(all_urls)} unique URLs across all seeders")

print("Done!")
