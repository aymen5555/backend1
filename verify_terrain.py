import re

with open('TerrainSeeder.php') as f: text = f.read()

# Check all padel IDs
padel_indices = [1,2,3,6,7,8,12,13,14,15,16,17,18,19,20]
print('=== TerrainSeeder PADEL photos (should have no duplicates) ===')
padel_ids = []
for i in padel_indices:
    m = re.search(rf'{i}\s*=>\s*\'[^\']*photos/(\d+)', text)
    if m:
        pid = m.group(1)
        padel_ids.append(pid)
        dupes = padel_ids.count(pid) > 1
        marker = " <-- DUPLICATE" if dupes else ""
        print(f'  photo {i:2d}: {pid}{marker}')

unique = set(padel_ids)
print(f'\nPadel: {len(padel_ids)} total, {len(unique)} unique')

# Check football and tennis too
print()
print('=== TerrainSeeder FOOTBALL photos ===')
for i in [4,5]:
    m = re.search(rf'{i}\s*=>\s*\'[^\']*photos/(\d+)', text)
    if m:
        print(f'  photo {i}: {m.group(1)}')

print()
print('=== TerrainSeeder TENNIS photos ===')
for i in [9,10,11]:
    m = re.search(rf'{i}\s*=>\s*\'[^\']*photos/(\d+)', text)
    if m:
        print(f'  photo {i}: {m.group(1)}')
