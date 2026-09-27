#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

COMMIT_MESSAGE="${1:-}"
REMOTE_NAME="${IC_DEPLOY_GIT_REMOTE:-origin}"
BRANCH="$(git rev-parse --abbrev-ref HEAD)"
CURRENT_COMMIT="$(git rev-parse HEAD)"
LAST_DEPLOY="$(git tag --list 'deploy-*' --sort=-creatordate | head -n 1)"
PRODUCTION_URL="${IC_PRODUCTION_URL:-https://www.italiancosplay.it}"

echo "ItalianCosplay Deploy"
echo
echo "Branch: ${BRANCH}"
echo "Current commit: ${CURRENT_COMMIT}"
echo "Last deployed: ${LAST_DEPLOY:-none}"
echo

require_clean_secrets() {
  local blocked
  blocked="$(git status --porcelain -- .env ':(top)*.sql' 'public_assets/uploads' 'storage/logs' 2>/dev/null || true)"
  if [[ -n "$blocked" ]]; then
    echo "Refusing to continue: sensitive/runtime files are staged or modified:"
    echo "$blocked"
    exit 1
  fi
}

require_deploy_tool() {
  if [[ -n "${IC_DEPLOY_DRY_RUN:-}" ]]; then
    return
  fi

  if [[ "${IC_DEPLOY_METHOD:-}" == "sftp" ]] && command -v sftp >/dev/null 2>&1; then
    return
  fi

  echo "Configure IC_DEPLOY_DRY_RUN=1 for preview, or IC_DEPLOY_METHOD=sftp plus IC_DEPLOY_HOST, IC_DEPLOY_USER and IC_DEPLOY_PATH."
  exit 1
}

run_tests() {
  echo "Running tests..."
  composer test
  echo
}

commit_and_push_if_needed() {
  if [[ -z "$(git status --porcelain)" ]]; then
    echo "No local changes to commit."
    return
  fi

  require_clean_secrets

  echo "Git changes:"
  git status --short
  echo

  if [[ -z "$COMMIT_MESSAGE" ]]; then
    read -r -p "Commit message: " COMMIT_MESSAGE
  fi

  if [[ -z "$COMMIT_MESSAGE" ]]; then
    echo "Missing commit message. Stop."
    exit 1
  fi

  git add -A
  require_clean_secrets
  git commit -m "$COMMIT_MESSAGE"
  git push "$REMOTE_NAME" "$BRANCH"
}

show_changes_since_deploy() {
  if [[ -z "$LAST_DEPLOY" ]]; then
    echo "No deploy tag found. The first deploy would compare the initial commit to HEAD."
    LAST_DEPLOY="$(git rev-list --max-parents=0 HEAD | tail -n 1)"
  fi

  echo
  echo "Changes since last deploy (${LAST_DEPLOY}..HEAD):"
  git diff --name-status "$LAST_DEPLOY" HEAD -- . \
    ':(exclude).env' \
    ':(exclude).git' \
    ':(exclude)vendor' \
    ':(exclude)node_modules' \
    ':(exclude)public_assets/uploads' \
    ':(exclude)storage/logs' \
    ':(exclude)storage/cache' \
    ':(top,exclude)*.sql' \
    | tee /tmp/italiancosplay-deploy-files.txt

  if [[ ! -s /tmp/italiancosplay-deploy-files.txt ]]; then
    echo "No deployable file changes found."
    exit 0
  fi
}

warn_migrations() {
  if git diff --name-only "$LAST_DEPLOY" HEAD -- database/migrations | grep -q .; then
    echo
    echo "ATTENZIONE: questo deploy contiene migration DB non ancora applicate automaticamente."
    echo "Le migration vanno valutate e applicate con conferma separata."
  fi
}

confirm_production() {
  echo
  echo "Commit to deploy: $(git rev-parse HEAD)"
  echo "Last deploy: ${LAST_DEPLOY}"
  echo "Files changed: $(wc -l < /tmp/italiancosplay-deploy-files.txt | tr -d ' ')"
  echo
  read -r -p "Pubblicare queste modifiche su ItalianCosplay.it? [y/N] " answer
  if [[ "$answer" != "y" && "$answer" != "Y" ]]; then
    echo "Deploy cancelled."
    exit 0
  fi
}

upload_files() {
  if [[ -n "${IC_DEPLOY_DRY_RUN:-}" ]]; then
    echo "Dry run enabled: no production files were changed."
    return
  fi

  if [[ "${IC_DEPLOY_METHOD:-}" != "sftp" ]]; then
    echo "Only SFTP is implemented for the safe first version."
    exit 1
  fi

  : "${IC_DEPLOY_HOST:?Missing IC_DEPLOY_HOST}"
  : "${IC_DEPLOY_USER:?Missing IC_DEPLOY_USER}"
  : "${IC_DEPLOY_PATH:?Missing IC_DEPLOY_PATH}"

  local batch_file
  batch_file="$(mktemp)"

  while IFS=$'\t' read -r status file rest; do
    case "$status" in
      A|M)
        printf 'put "%s" "%s/%s"\n' "$file" "$IC_DEPLOY_PATH" "$file" >> "$batch_file"
        ;;
      D)
        printf 'rm "%s/%s"\n' "$IC_DEPLOY_PATH" "$file" >> "$batch_file"
        ;;
      R*)
        printf 'rm "%s/%s"\n' "$IC_DEPLOY_PATH" "$file" >> "$batch_file"
        printf 'put "%s" "%s/%s"\n' "$rest" "$IC_DEPLOY_PATH" "$rest" >> "$batch_file"
        ;;
    esac
  done < /tmp/italiancosplay-deploy-files.txt

  sftp -b "$batch_file" "${IC_DEPLOY_USER}@${IC_DEPLOY_HOST}"
}

tag_deploy() {
  local tag
  tag="deploy-$(date +%Y%m%d-%H%M%S)"
  git tag "$tag"
  git push "$REMOTE_NAME" "$tag"
  echo "Deploy successful. Tag: ${tag}"
}

run_tests
commit_and_push_if_needed
CURRENT_COMMIT="$(git rev-parse HEAD)"
require_deploy_tool
show_changes_since_deploy
warn_migrations
confirm_production
upload_files
IC_SMOKE_BASE_URL="$PRODUCTION_URL" php scripts/smoke-test.php
tag_deploy
