#!/usr/bin/env python3
import re
import os

base = r"C:\Projects\project\backend\database\seeders"

# Very large pool of verified working Pexels images
pool = {
    # Complex scenes (800x500 area)
    "c1": "https://images.pexels.com/photos/274422/pexels-photo-274422.jpeg?auto=compress&cs=tinysrgb&w=800",
    "c2": "https://images.pexels.com/photos/32474981/pexels-photo-32474981.jpeg?auto=compress&cs=tinysrgb&w=800",
    "c3": "https://images.pexels.com/photos/209977/pexels-photo-209977.jpeg?auto=compress&cs=tinysrgb&w=800",
    "c4": "https://images.pexels.com/photos/3621104/pexels-photo-3621104.jpeg?auto=compress&cs=tinysrgb&w=800",
    "c5": "https://images.pexels.com/photos/30864597/pexels-photo-30864597.jpeg?auto=compress&cs=tinysrgb&w=800",
    "c6": "https://images.pexels.com/photos/36246829/pexels-photo-36246829.jpeg?auto=compress&cs=tinysrgb&w=800",
    "c7": "https://images.pexels.com/photos/47730/the-ball-stadion-football-the-pitch-47730.jpeg?auto=compress&cs=tinysrgb&w=800",
    "c8": "https://images.pexels.com/photos/1752757/pexels-photo-1752757.jpeg?auto=compress&cs=tinysrgb&w=800",
    "c9": "https://images.pexels.com/photos/3621104/pexels-photo-3621104.jpeg?auto=compress&cs=tinysrgb&w=800",
    "c10": "https://images.pexels.com/photos/36246829/pexels-photo-36246829.jpeg?auto=compress&cs=tinysrgb&w=800",

    # Padel courts (600x400)
    "p1": "https://images.pexels.com/photos/8007409/pexels-photo-8007409.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p2": "https://images.pexels.com/photos/3274370/pexels-photo-3274370.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p3": "https://images.pexels.com/photos/5381950/pexels-photo-5381950.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p4": "https://images.pexels.com/photos/3074529/pexels-photo-3074529.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p5": "https://images.pexels.com/photos/4792269/pexels-photo-4792269.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p6": "https://images.pexels.com/photos/5732609/pexels-photo-5732609.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p7": "https://images.pexels.com/photos/163409/padel-court-sport-163409.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p8": "https://images.pexels.com/photos/3768916/pexels-photo-3768916.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p9": "https://images.pexels.com/photos/5449616/pexels-photo-5449616.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p10": "https://images.pexels.com/photos/3756925/pexels-photo-3756925.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p11": "https://images.pexels.com/photos/3777905/pexels-photo-3777905.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p12": "https://images.pexels.com/photos/5341635/pexels-photo-5341635.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p13": "https://images.pexels.com/photos/5341647/pexels-photo-5341647.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p14": "https://images.pexels.com/photos/5341660/pexels-photo-5341660.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p15": "https://images.pexels.com/photos/3887985/pexels-photo-3887985.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p16": "https://images.pexels.com/photos/36246829/pexels-photo-36246829.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p17": "https://images.pexels.com/photos/35248400/pexels-photo-35248400.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p18": "https://images.pexels.com/photos/35248254/pexels-photo-35248254.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p19": "https://images.pexels.com/photos/35248259/pexels-photo-35248259.jpeg?auto=compress&cs=tinysrgb&w=600",
    "p20": "https://images.pexels.com/photos/32524250/pexels-photo-32524250.jpeg?auto=compress&cs=tinysrgb&w=600",

    # Football (600x400)
    "f1": "https://images.pexels.com/photos/274422/pexels-photo-274422.jpeg?auto=compress&cs=tinysrgb&w=600",
    "f2": "https://images.pexels.com/photos/46798/the-ball-stadion-football-the-pitch-47730.jpeg?auto=compress&cs=tinysrgb&w=600",
    "f3": "https://images.pexels.com/photos/62035/pexels-photo-62035.jpeg?auto=compress&cs=tinysrgb&w=600",
    "f4": "https://images.pexels.com/photos/209977/pexels-photo-209977.jpeg?auto=compress&cs=tinysrgb&w=600",
    "f5": "https://images.pexels.com/photos/3887985/pexels-photo-3887985.jpeg?auto=compress&cs=tinysrgb&w=600",

    # Tennis (600x400)
    "t1": "https://images.pexels.com/photos/1277397/pexels-photo-1277397.jpeg?auto=compress&cs=tinysrgb&w=600",
    "t2": "https://images.pexels.com/photos/209977/pexels-photo-209977.jpeg?auto=compress&cs=tinysrgb&w=600",
    "t3": "https://images.pexels.com/photos/1511047/pexels-photo-1511047.jpeg?auto=compress&cs=tinysrgb&w=600",
    "t4": "https://images.pexels.com/photos/1263426/pexels-photo-1263426.jpeg?auto=compress&cs=tinysrgb&w=600",

    # Products (400x400) - meaningful sport product photos
    "pr1": "https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?auto=compress&cs=tinysrgb&w=400",  # padel racket + ball
    "pr2": "https://images.pexels.com/photos/10340620/pexels-photo-10340620.jpeg?auto=compress&cs=tinysrgb&w=400",  # tennis ball on racket
    "pr3": "https://images.pexels.com/photos/1277397/pexels-photo-1277397.jpeg?auto=compress&cs=tinysrgb&w=400",  # tennis/padel ball
    "pr4": "https://images.pexels.com/photos/7154759/pexels-photo-7154759.jpeg?auto=compress&cs=tinysrgb&w=400",  # football ball white bg
    "pr5": "https://images.pexels.com/photos/19882423/pexels-photo-19882423.jpeg?auto=compress&cs=tinysrgb&w=400",  # soccer shoes
    "pr6": "https://images.pexels.com/photos/6296109/pexels-photo-6296109.jpeg?auto=compress&cs=tinysrgb&w=400",  # boxing gloves
    "pr7": "https://images.pexels.com/photos/4047134/pexels-photo-4047134.jpeg?auto=compress&cs=tinysrgb&w=400",  # red 2kg dumbbells
    "pr8": "https://images.pexels.com/photos/226562/pexels-photo-226562.jpeg?auto=compress&cs=tinysrgb&w=400",   # tennis ball closeup
    "pr9": "https://images.pexels.com/photos/11324519/pexels-photo-11324519.jpeg?auto=compress&cs=tinysrgb&w=400", # shoes on display
    "pr10": "https://images.pexels.com/photos/6062558/pexels-photo-6062558.jpeg?auto=compress&cs=tinysrgb&w=400",  # sports bag/accessories
    "pr11": "https://images.pexels.com/photos/5698851/pexels-photo-5698851.jpeg?auto=compress&cs=tinysrgb&w=400",  # t-shirt sport
    "pr12": "https://images.pexels.com/photos/8473576/pexels-photo-8473576.jpeg?auto=compress&cs=tinysrgb&w=400",  # sportswear sneakers
    "pr13": "https://images.pexels.com/photos/4162457/pexels-photo-4162457.jpeg?auto=compress&cs=tinysrgb&w=400",  # water bottle
    "pr14": "https://images.pexels.com/photos/9644820/pexels-photo-9644820.jpeg?auto=compress&cs=tinysrgb&w=400",  # padel player
    "pr15": "https://images.pexels.com/photos/8436578/pexels-photo-8436578.jpeg?auto=compress&cs=tinysrgb&w=400",  # yoga mat
    "pr16": "https://images.pexels.com/photos/6339731/pexels-photo-6339731.jpeg?auto=compress&cs=tinysrgb&w=400",  # rolled mats + dumbbells
    "pr17": "https://images.pexels.com/photos/10923069/pexels-photo-10923069.jpeg?auto=compress&cs=tinysrgb&w=400", # player in cleats
    "pr18": "https://images.pexels.com/photos/13450845/pexels-photo-13450845.jpeg?auto=compress&cs=tinysrgb&w=400", # person with sneakers
    "pr19": "https://images.pexels.com/photos/15632866/pexels-photo-15632866.jpeg?auto=compress&cs=tinysrgb&w=400", # shoe closeup
}

# Sequential assignment keys per category
seq = {
    'padel': ['p1','p2','p3','p4','p5','p6','p7','p8','p9','p10','p11','p12','p13','p14','p15','p16','p17','p18','p19','p20'],
    'football': ['f1','f2','f3','f4','f5','p16','p17','p18','p19','p20'],
    'tennis': ['t1','t2','t3','t4','p16','p17','p18','p19','p20'],
    'complex': ['c1','c2','c3','c4','c5','c6','c7','c8','c9','c10'],
    'raquettes': ['pr1','pr2'],
    'balles': ['pr3','pr4'],
    'chaussures': ['pr5','pr7','pr9'],
    'gants': ['pr6'],
    'grip': ['pr8'],
    'cordage': ['pr9'],
    'sac': ['pr10'],
    'vetements': ['pr11','pr12'],
    'poignet': ['pr10'],
    'bouteille': ['pr13'],
    'lunettes': ['pr14'],
    'yoga': ['pr15'],
    'halteres': ['pr6'],
    'corde': ['pr7'],
}

counters = {k: 0 for k in seq}

def next_img(category):
    keys = seq[category]
    idx = counters[category] % len(keys)
    counters[category] += 1
    return pool[keys[idx]]

# Parse files and replace all pexels URLs based on context
for fname in ['PlaySpaceDemoSeeder.php', 'TerrainSeeder.php', 'ProduitSeeder.php', 'ComplexeSeeder.php']:
    path = os.path.join(base, fname)
    with open(path, 'r', encoding='utf-8') as f:
        text = f.read()

    # Replace all pexels.photos URLs
    def replacer(m):
        url = m.group(0)
        if 'w=800' in url or 'w=600' in url:
            w = 800 if 'w=800' in url else 600
            # Context detection
            if 'football' in url or 'ball-stadion' in url:
                return next_img('football')
            elif 'tennis' in url or '1277397' in url or '1511047' in url:
                return next_img('tennis')
            else:
                return next_img('padel')
        elif 'w=400' in url:
            if 'racket' in url or '8007418' in url or '1277397' in url:
                return next_img('raquettes')
            elif 'ball' in url or '1263426' in url or '47730' in url:
                return next_img('balles')
            elif 'shoe' in url or '2529148' in url or '1520074' in url:
                return next_img('chaussures')
            elif 'glove' in url or '841130' in url:
                return next_img('gants')
            elif 'grip' in url or '1283219' in url:
                return next_img('grip')
            elif 'cordage' in url or '1616115' in url:
                return next_img('cordage')
            elif 'sac' in url or '4397840' in url:
                return next_img('sac')
            elif 'tshirt' in url or 'short' in url or '5698851' in url:
                return next_img('vetements')
            elif 'poignet' in url or '4397840' in url:
                return next_img('poignet')
            elif 'bouteille' in url or '1187589' in url:
                return next_img('bouteille')
            elif 'lunette' in url or '1365118' in url:
                return next_img('lunettes')
            elif 'yoga' in url or '3822166' in url or '3471277' in url:
                return next_img('yoga')
            elif 'haltere' in url or '841130' in url:
                return next_img('halteres')
            elif 'corde' in url or '1520074' in url:
                return next_img('corde')
            return next_img('raquettes')
        return url

    new_text = re.sub(r'https://images\.pexels\.com/photos/[^"\'\s]+', replacer, text)

    with open(path, 'w', encoding='utf-8') as f:
        f.write(new_text)
    print(f"Processed {fname}")

# Assign unique complex images in ComplexeSeeder
with open(os.path.join(base, 'ComplexeSeeder.php'), 'r', encoding='utf-8') as f:
    comp = f.read()

complex_keys = list(seq['complex'])
for i, name in enumerate(['Olympysky Club', 'Padel House Tunisia', 'Tennis Club de Tunis', 'Padel Marsa', 'Sassi Padel Club', 'Padel Indoor La Soukra']):
    url = pool[complex_keys[i]]
    comp = re.sub(
        rf"('{name}' => \[.*?'image_c' => ')([^']+)(')",
        lambda m, u=url: m.group(1) + u + m.group(3),
        comp,
        flags=re.DOTALL
    )

with open(os.path.join(base, 'ComplexeSeeder.php'), 'w', encoding='utf-8') as f:
    f.write(comp)

print("ComplexeSeeder complex images deduplicated")
