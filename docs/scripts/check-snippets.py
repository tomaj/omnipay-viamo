"""PHP examples must come from docs/snippets/, never be pasted into the page.

- Every `<<< @/...` snippet import must point at an existing file.
- Every file in docs/snippets/ must be imported by at least one page
  (tests/DocsSnippetsTest.php runs each of them against the current code).
- Every example in examples/ has a page docs/examples/<name>.md that includes its
  expected-output.txt (the Examples workflow checks that output).
- A fenced ```php block may only show a signature or a one-liner (at most 3 lines);
  anything longer belongs in a snippet file. The exception is sk/upgrade.md, the Slovak translation of
  UPGRADE-5.0.md: its blocks must be copies of the blocks in UPGRADE-5.0.md (tests/InlineSnippetsTest.php
  runs both).
"""
import re
import sys
from pathlib import Path

docs = Path(__file__).resolve().parent.parent
errors = []
imported = set()
upgrade_blocks = set(re.findall(r"^```php[^\n]*\n(.*?)^```", (docs.parent / "UPGRADE-5.0.md").read_text(encoding="utf-8"), re.M | re.S))
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
        if page.relative_to(docs).as_posix() == "sk/upgrade.md":
            if block not in upgrade_blocks:
                errors.append(f"{page.relative_to(docs)}: a PHP block that is not in UPGRADE-5.0.md, keep the code identical to the English upgrade guide")
            continue
        lines = [line for line in block.splitlines() if line.strip()]
        if len(lines) > 3:
            errors.append(f"{page.relative_to(docs)}: a {len(lines)}-line PHP block, move it to docs/snippets/ and import it")

examples = docs.parent / "examples"
for example in sorted(p for p in examples.iterdir() if p.is_dir() and p.name != "ci"):
    page = docs / "examples" / f"{example.name}.md"
    if not page.is_file():
        errors.append(f"examples/{example.name}/ has no page docs/examples/{example.name}.md")
    elif f"../examples/{example.name}/expected-output.txt" not in imported:
        errors.append(f"docs/examples/{example.name}.md does not include its expected-output.txt")

for snippet in sorted((docs / "snippets").glob("*.php")):
    if snippet.relative_to(docs).as_posix() not in imported:
        errors.append(f"snippets/{snippet.name} is not imported by any page")

for error in errors:
    print(error, file=sys.stderr)
if errors:
    sys.exit(1)
print(f"ok {len(imported)} snippets imported from docs/snippets/")
