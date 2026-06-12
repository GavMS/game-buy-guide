"""Quick check for server._parse_verdict — run: python test_parse_verdict.py"""
from server import _parse_verdict

tests = [
    ("BUY\n\nCurrent state: ...", "BUY"),
    ("WAIT\nbad state", "WAIT"),
    ("AVOID\nabandoned", "AVOID"),
    ("**AVOID**\nmarkdown bold verdict", "AVOID"),
    ("Don't buy this game, avoid it", "AVOID"),
    ("You should not buy yet, wait for patches", "WAIT"),
    ("I recommend you BUY now", "BUY"),
    ("garbage answer with no keyword", "WAIT"),
]

failed = 0
for text, expected in tests:
    got = _parse_verdict(text)
    status = "OK " if got == expected else "FAIL"
    if got != expected:
        failed += 1
    print(f"{status} {text[:40]!r:45} -> {got} (expected {expected})")

print(f"\n{len(tests) - failed}/{len(tests)} passed")
raise SystemExit(1 if failed else 0)
