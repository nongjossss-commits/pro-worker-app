from html.parser import HTMLParser
import re

class DivCounter(HTMLParser):
    def __init__(self):
        super().__init__()
        self.stack = []
        self.div_count = 0
        self.errors = []

    def handle_starttag(self, tag, attrs):
        if tag == 'div':
            self.div_count += 1
            self.stack.append((tag, self.getpos()))

    def handle_endtag(self, tag):
        if tag == 'div':
            if self.stack:
                self.stack.pop()
            else:
                self.errors.append(f"Extra closing div at {self.getpos()}")

    def validate(self, content):
        self.feed(content)
        if self.stack:
            for tag, pos in self.stack:
                self.errors.append(f"Unclosed div at {pos} (started)")
        return len(self.errors) == 0

def check_file(file_path):
    print(f"Checking {file_path}...")
    with open(file_path, 'r') as f:
        content = f.read()

    # Find loop start
    match_start = re.search(r'@foreach\(\$employers as \$employer\)', content)
    match_end = re.search(r'@endforeach', content)

    if not match_start or not match_end:
        print("Could not find loop markers")
        return

    start_pos = match_start.end()
    end_pos = match_end.start()

    loop_content = content[start_pos:end_pos]

    # We added a closing div, so it should be balanced now relative to the start of the loop
    # IF the loop content is supposed to be self-contained in terms of divs.

    parser = DivCounter()
    parser.validate(loop_content)

    print(f"Errors found: {len(parser.errors)}")
    for err in parser.errors:
        print(err)

check_file('resources/views/production/registration/index.blade.php')
check_file('resources/views/production/renewal/index.blade.php')
