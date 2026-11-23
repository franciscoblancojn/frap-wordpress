#!/usr/bin/env bash
# Publica el plugin en su propio repositorio. El código vive en FRAP (integrations/wordpress/frap-wordpress)
# y aquí se separa con `git subtree split`: el repo del plugin recibe solo esta carpeta (con su historial)
# como raíz, así su .github/workflows/release.yml funciona allá.
# Uso: npm run publish            → empuja la rama master del repo del plugin
#      npm run publish -- 1.2.3   → además crea el tag 1.2.3 allá (el workflow arma la release)
# Repo destino: FRAPWP_REMOTE (por defecto https://github.com/franciscoblancojn/frap-wordpress.git).
set -euo pipefail

REMOTE="${FRAPWP_REMOTE:-https://github.com/franciscoblancojn/frap-wordpress.git}"
BRANCH="${FRAPWP_BRANCH:-master}"
TAG="${1:-}"
DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$DIR"
ROOT="$(git rev-parse --show-toplevel)"
PREFIX="$(git rev-parse --show-prefix)"
PREFIX="${PREFIX%/}"

if [ -z "$PREFIX" ]; then
    echo "✗ Esto se corre desde la copia del plugin dentro de FRAP (el repo propio del plugin se publica con git push)."
    exit 1
fi
if [ -n "$(git status --porcelain -- .)" ]; then
    echo "✗ Hay cambios sin commit en $PREFIX: haz commit antes de publicar."
    git status --short -- .
    exit 1
fi
if [ -n "$TAG" ] && git ls-remote --exit-code --tags "$REMOTE" "refs/tags/$TAG" > /dev/null 2>&1; then
    echo "✗ El tag $TAG ya existe en $REMOTE"
    exit 1
fi

echo "→ Separando $PREFIX (git subtree split)…"
SPLIT="$(cd "$ROOT" && git subtree split --prefix="$PREFIX" HEAD)"
echo "✓ Commit del plugin: $SPLIT"

echo "→ Empujando a $REMOTE ($BRANCH)…"
git push "$REMOTE" "$SPLIT:refs/heads/$BRANCH"
if [ -n "$TAG" ]; then
    git push "$REMOTE" "$SPLIT:refs/tags/$TAG"
    echo "✓ Tag $TAG publicado: el workflow release crea la release que usa FWUUpdate."
fi
echo "✓ Publicado"
