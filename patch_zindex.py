import re

def process_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # The z-index for Bootstrap modals is usually 1055
    # The backdrop is 1050.
    # We should make the bulk action bar 1060 as we did
    # Maybe display: none is not being removed?
    # Let's check `window.toggleGlobalSelection`
    pass

process_file('resources/views/production/registration/index.blade.php')
