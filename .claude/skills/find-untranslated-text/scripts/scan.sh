#!/usr/bin/env bash
# scan.sh — encuentra texto hardcodeado en inglés sin $t()
# Uso: bash .claude/skills/find-untranslated-text/scripts/scan.sh
# Requiere: grep, python3 (macOS/Linux)

ROOT=$(git rev-parse --show-toplevel 2>/dev/null || pwd)
cd "$ROOT"

SCAN_DIRS="resources/assets/js/components resources/assets/js/composables"
TMPFILE=$(mktemp)
trap 'rm -f "$TMPFILE"' EXIT

# ── Recolectar hits en formato "file\tlineno\ttext" ──────────────────────────

for DIR in $SCAN_DIRS; do
  [ -d "$DIR" ] || continue

  # 1. Líneas que son SOLO texto plano visible (sin {{ ni < ni directivas)
  grep -rn --include="*.vue" -E "^[[:space:]]{4,}[A-Z][a-zA-Z][a-zA-Z0-9 .,!?()'-]+[a-zA-Z.!?]$" "$DIR" 2>/dev/null \
    | grep -v '\$t(' | grep -v '<!--' | grep -v '//' | grep -v 'xargs:' \
    | while IFS=: read -r file lineno text; do
        printf '%s\t%s\t%s\n' "$file" "$lineno" "$text"
      done >> "$TMPFILE"

  # 2. Texto inline: >Texto visible</tag>
  grep -rn --include="*.vue" -E ">[A-Z][a-z][^<{$]*[a-zA-Z.!]<\/[a-z]" "$DIR" 2>/dev/null \
    | grep -v '\$t(' | grep -v '{{' \
    | while IFS=: read -r file lineno text; do
        inner=$(echo "$text" | sed -E 's/.*>([^<{]+)<\/.*/\1/' | xargs)
        [ -n "$inner" ] && printf '%s\t%s\t%s\n' "$file" "$lineno" "$inner"
      done >> "$TMPFILE"

  # 3. Atributos visibles sin binding dinámico
  grep -rn --include="*.vue" -E '(placeholder|aria-label|title)="[A-Z][^"]{3,}"' "$DIR" 2>/dev/null \
    | grep -v ':\(placeholder\|aria-label\|title\)' | grep -v '\$t(' \
    | while IFS=: read -r file lineno text; do
        printf '%s\t%s\t%s\n' "$file" "$lineno" "$text"
      done >> "$TMPFILE"

  # 4. Toasts con string literal en inglés (composables .ts)
  grep -rn --include="*.ts" -E "(toastSuccess|toastWarning|toastError)\s*\('[A-Z]" "$DIR" 2>/dev/null \
    | grep -v "t('" \
    | while IFS=: read -r file lineno text; do
        printf '%s\t%s\t%s\n' "$file" "$lineno" "$text"
      done >> "$TMPFILE"
done

# Deduplicar
sort -u "$TMPFILE" -o "$TMPFILE"

# ── Árbol ASCII vía Python 3 ─────────────────────────────────────────────────
python3 - "$TMPFILE" <<'PYEOF'
import sys, os

hits_file = sys.argv[1]

# file -> [(lineno, text)]
from collections import defaultdict
hits = defaultdict(list)

with open(hits_file) as f:
    for line in f:
        line = line.rstrip('\n')
        if not line:
            continue
        parts = line.split('\t', 2)
        if len(parts) < 3:
            continue
        filepath, lineno, text = parts
        hits[filepath].append((lineno, text.strip()))

if not hits:
    print("✅  No se encontró texto hardcodeado sin traducir.")
    sys.exit(0)

# Agrupar por directorio
by_dir = defaultdict(list)
for f in sorted(hits):
    by_dir[os.path.dirname(f)].append(f)

total_hits = sum(len(v) for v in hits.values())
total_files = len(hits)

print()
print("\033[1;31mTextos sin traducir (i18n audit)\033[0m")
print("━" * 58)

dirs = sorted(by_dir)
for di, d in enumerate(dirs):
    is_last_dir = di == len(dirs) - 1
    d_prefix = "└──" if is_last_dir else "├──"
    print(f"\033[1;36m{d_prefix} {d}/\033[0m")

    files = by_dir[d]
    for fi, fpath in enumerate(sorted(files)):
        is_last_file = fi == len(files) - 1
        vbar  = " " if is_last_dir else "│"
        fmark = "└──" if is_last_file else "├──"
        bname = os.path.basename(fpath)
        fhits = hits[fpath]
        print(f"{vbar}   {fmark} \033[33m{bname}\033[0m  ({len(fhits)} hit{'s' if len(fhits)>1 else ''})")

        indent = " " if is_last_dir else "│"
        sub    = "    " if is_last_file else "│   "
        for lineno, text in fhits:
            short = text[:80] + ("…" if len(text) > 80 else "")
            print(f"{indent}   {sub}  \033[90mL{lineno}\033[0m  {short}")

print("━" * 58)
print(f"\033[1m{total_files} archivo(s)\033[0m · \033[1;31m{total_hits} texto(s)\033[0m sin traducir")
print()
PYEOF
