#!/usr/bin/env bash
# Sube la versión de index.php, cambia "## [Sin publicar]" por "## [X.Y.Z] - AAAA-MM-DD" en
# CHANGELOG.md, hace commit (solo de esta carpeta, en el repo de FRAP) y publica en el repo del
# plugin con el tag X.Y.Z (scripts/publish.sh).
# Uso: npm run push-v -- major|minor|patch
set -e

if [ $# -ne 1 ] || [[ ! "$1" =~ ^(major|minor|patch)$ ]]; then
    echo "Uso: npm run push-v -- major|minor|patch"
    exit 1
fi

DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$DIR"

VERSION=$(grep -m1 "^Version:" index.php | sed 's/.*Version:[[:space:]]*//' | tr -d '\r')
MAJOR=$(echo "$VERSION" | cut -d. -f1)
MINOR=$(echo "$VERSION" | cut -d. -f2)
PATCH=$(echo "$VERSION" | cut -d. -f3)

case "$1" in
    major)
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
    minor)
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    patch)
        PATCH=$((PATCH + 1))
        ;;
esac

NEW_VERSION="$MAJOR.$MINOR.$PATCH"

if [ -n "$(git status --porcelain -- .)" ]; then
    echo "✗ Hay cambios sin commit en el plugin: haz commit antes de publicar una versión."
    exit 1
fi

# Se comprueba antes de tocar nada: no se publica una versión sin notas en el changelog.
if ! grep -q '^## \[Sin publicar\]' CHANGELOG.md; then
    echo "✗ CHANGELOG.md no tiene una sección '## [Sin publicar]' con los cambios de esta versión"
    exit 1
fi

# Validaciones del proyecto (php -l, ES5…): no se publica si fallan.
bash bin/harness.sh

sed -i "s/^\(Version: *\)[0-9.]\+/\1$NEW_VERSION/" index.php
sed -i "s/^## \[Sin publicar\].*/## [$NEW_VERSION] - $(date +%F)/" CHANGELOG.md
npm run sync:version --silent > /dev/null

echo "✓ Versión: $VERSION → $NEW_VERSION"
echo "✓ CHANGELOG.md: [Sin publicar] → [$NEW_VERSION]"

# Commit solo de la carpeta del plugin (el resto del repo de FRAP no se toca).
git add -- .
git commit -m "frap-wordpress $NEW_VERSION" -- .

# Sin "|| true" ni "2>/dev/null": si el push falla, se ve el error.
bash scripts/publish.sh "$NEW_VERSION"
