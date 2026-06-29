import re

with open('ComplexeSeeder.php') as f: text = f.read()
print('=== ComplexeSeeder photos ===')
matches = re.findall(r"'([^']+)'\s*=>\s*'([^']+photos/(\d+)[^']*)'", text)
for name, url, pid in matches[:6]:
    print(f'  {name}: photo {pid}')

with open('TerrainSeeder.php') as f: text = f.read()
print()
print('=== TerrainSeeder photos ===')
lines = text.split('\n')
for i in range(1, 23):
    for line in lines:
        if line.strip().startswith(f'{i} =>'):
            uid = re.search(r'photos/(\d+)', line)
            if uid:
                pid = uid.group(1)
                sport = 'padel' if i in [1,2,3,6,7,8,12,13,14,15,16,17,18,19,20] else 'football' if i in [4,5] else 'tennis' if i in [9,10,11] else 'other'
                print(f'  photo {i:2d}: {pid} ({sport})')
            break
