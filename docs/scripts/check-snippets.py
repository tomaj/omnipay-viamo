"""PHP examples must come from docs/snippets/, never be pasted into the page.

- Every `<<< @/...` snippet import must point at an existing file.
- Every file in docs/snippets/ must be imported by at least one page
  (tests/DocsSnippetsTest.php runs each of them against the current code).
- A fenced ```php block may only show a signature or a one-liner (at most 3 lines);
  anything longer belongs in a snippet file.
"""
import re
import sys
from pathlib import Path

docs = Path(__file__).resolve().parent.parent
errors = []
imported = set()
for page in sorted(docs.rglob("*.md")):
    if "node_modules" in page.parts or ".vitepress" in page.parts:
        continue
    text = page.read_text(encoding="utf-8")
    for target in re.findall(r"^<<< @/(\S+?)(?:\{[^}]*\})?(?:\s|$)", text, re.M):
        path = target.split("#", 1)[0]
        imported.add(path)
        if not (docs / path).is_file():
            errors.append(f"{page.relative_to(docs)}: snippet {path} does not exist")
    for block in re.findall(r"^```php[^\n]*\n(.*?)^```", text, re.M | re.S):
        lines = [line for line in block.splitlines() if line.strip()]
        if len(lines) > 3:
            errors.append(f"{page.relative_to(docs)}: a {len(lines)}-line PHP block, move it to docs/snippets/ and import it")

for snippet in sorted((docs / "snippets").glob("*.php")):
    if snippet.relative_to(docs).as_posix() not in imported:
        errors.append(f"snippets/{snippet.name} is not imported by any page")

for error in errors:
    print(error, file=sys.stderr)
if errors:
    sys.exit(1)
print(f"ok {len(imported)} snippets imported from docs/snippets/")
