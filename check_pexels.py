import re
files = ['TerrainSeeder.php', 'PlaySpaceDemoSeeder.php']
all_urls = []
for f in files:
    with open(f) as fh: content = fh.read()
    urls = re.findall(r'https://images\.pexels\.com/photos/\d+', content)
    all_urls.extend(urls)
    print(f'{f}: {urls[:3]}...')
dupes = [u for u in set(all_urls) if all_urls.count(u) > 1]
print(f'Duplicates: {dupes if dupes else "None"}')
print(f'Total URLs: {len(all_urls)}')
