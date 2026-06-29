import re

with open('TerrainSeeder.php') as f: text = f.read()

print('=== TerrainSeeder PADEL photos ===')
for i in [1,2,3,6,7,8,12,13,14,15,16,17,18,19,20]:
    m = re.search(rf'{i}\s*=>\s*\'([^\']+)\'', text)
    if m:
        uid = m.group(1).split('/photos/')[1][:10]
        print(f'  photo {i:2d}: {uid}')

print()
print('=== TerrainSeeder NON-PADEL ===')
for i in [4,5,9,10,11]:
    m = re.search(rf'{i}\s*=>\s*\'([^\']+)\'', text)
    if m:
        uid = m.group(1).split('/photos/')[1][:10]
        print(f'  photo {i:2d}: {uid}')

with open('PlaySpaceDemoSeeder.php') as f: text = f.read()
print()
print('=== PlaySpaceDemoSeeder PADEL photos ===')
for i in [7,8,9,12,13,14,18,19,20,21,22,23,25,26]:
    m = re.search(rf'{i}\s*=>\s*\'([^\']+)\'', text)
    if m:
        uid = m.group(1).split('/photos/')[1][:10]
        print(f'  photo {i:2d}: {uid}')

padel_urls = []
for fname, indices in [('TerrainSeeder.php', [1,2,3,6,7,8,12,13,14,15,16,17,18,19,20]), ('PlaySpaceDemoSeeder.php', [7,8,9,12,13,14,18,19,20,21,22,23,25,26])]:
    with open(fname) as f: text = f.read()
    for i in indices:
        m = re.search(rf'{i}\s*=>\s*\'([^\']+)\'', text)
        if m:
            padel_urls.append(m.group(1))

dupes = [u for u in set(padel_urls) if padel_urls.count(u) > 1]
print()
if dupes:
    print('Duplicate padel URLs found:')
    for d in dupes:
        print(f'  {d}')
else:
    print('No duplicate padel URLs - all unique!')
