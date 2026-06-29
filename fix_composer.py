import json, pathlib, subprocess, os, sys
path = pathlib.Path('composer.json')
data = json.loads(path.read_text(encoding='utf-8'))
psr4 = data['autoload']['psr-4']
if 'Webkul\\Suggestion\\' not in psr4:
    new_psr4 = {}
    inserted = False
    for k, v in psr4.items():
        new_psr4[k] = v
        if k == 'Webkul\\Stripe\\' and not inserted:
            new_psr4['Webkul\\Suggestion\\'] = 'packages/Webkul/Suggestion/src'
            inserted = True
    if not inserted:
        new_psr4['Webkul\\Suggestion\\'] = 'packages/Webkul/Suggestion/src'
    data['autoload']['psr-4'] = new_psr4
path.write_text(json.dumps(data, indent=4, ensure_ascii=False) + '\n', encoding='utf-8')
print('composer.json fixed')
