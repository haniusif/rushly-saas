#!/usr/bin/env bash
# verify-inertia-pages.sh — drive admin Inertia pages through the real nginx
# stack and assert each one returns 200 with the expected page component.
#
# Why this exists: route + controller + Inertia render + JSX bundle can each
# look fine in isolation while the end-to-end response is broken. This script
# walks every page as an authenticated admin and checks the Inertia component
# embedded in the SSR HTML matches what we expect.
#
# Usage:
#   scripts/verify-inertia-pages.sh <tenant-host> <email> <password>
#
# Example:
#   scripts/verify-inertia-pages.sh demo.rushly.tech demo@rushly-logistic.com 'xxx'
#
# Optional second pass (prop-shape inspection via X-Inertia JSON):
#   INSPECT_PROPS=1 scripts/verify-inertia-pages.sh ...
#
# Adds (no removes) — append URLs to PAGES below as you port more screens.

set -euo pipefail

HOST="${1:?tenant host, e.g. demo.rushly.tech}"
EMAIL="${2:?login email}"
PASSWORD="${3:?login password}"
BASE="https://${HOST}"

PAGES=(
  "/admin/push-notification|Admin/PushNotification/Index"
  "/admin/push-notification/create|Admin/PushNotification/Create"
  "/admin/todo/todo_list|Admin/Todo/Index"
  "/admin/support/index|Admin/Support/Index"
  "/admin/support/create|Admin/Support/Form"
  "/admin/news-offer|Admin/NewsOffer/Index"
  "/admin/news-offer/create|Admin/NewsOffer/Form"
  "/admin/logs|Admin/Log/Index"
  "/admin/fraud|Admin/Fraud/Index"
  "/admin/fraud/create|Admin/Fraud/Form"
  "/admin/subscribe|Admin/Subscribe/Index"
  "/subscription|Admin/Subscription/Index"
  "/admin/subscription/history|Admin/Subscription/History"
  "/admin/pickup-request/regular|Admin/PickupRequest/Regular"
  "/admin/pickup-request/express|Admin/PickupRequest/Express"
  "/admin/assets/index|Admin/Asset/Index"
  "/admin/assets/create|Admin/Asset/Form"
  "/admin/wallet-request|Admin/Wallet/Index"
  "/admin/general-settings/index|Admin/GeneralSettings/Index"
  "/admin/integrations|Admin/Integrations/Index"
  "/admin/integrations/salla/edit|Admin/Integrations/Edit"
  "/admin/delivery-category/index|Admin/DeliveryCategory/Index"
  "/admin/delivery-category/create|Admin/DeliveryCategory/Form"
  "/admin/delivery-charge/index|Admin/DeliveryCharge/Index"
  "/admin/delivery-charge/create|Admin/DeliveryCharge/Form"
  "/admin/paid/invoice|Admin/PaidInvoice/Index"
  "/admin/payout|Admin/Payout/Index"
  "/admin/accounts/index|Admin/Account/Index"
  "/admin/accounts/create|Admin/Account/Form"
  "/admin/users|Admin/User/Index"
  "/admin/users/create|Admin/User/Form"
  "/admin/salarys|Admin/Salary/Index"
  "/admin/salarys/create|Admin/Salary/Form"
  "/admin/delivery-type/index|Admin/DeliveryType/Index"
)

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
COOKIES="$WORK/cookies.txt"

# ── login ────────────────────────────────────────────────────────────────────
curl -sk -c "$COOKIES" -o "$WORK/login.html" "$BASE/login"
TOKEN="$(grep -oP 'name="_token" value="\K[^"]+' "$WORK/login.html" | head -1)"
[[ -z "$TOKEN" ]] && { echo "Could not extract CSRF token from $BASE/login" >&2; exit 1; }

curl -sk -b "$COOKIES" -c "$COOKIES" -o "$WORK/post.html" -L \
  -X POST "$BASE/login" \
  -d "_token=${TOKEN}&email=${EMAIL}&password=${PASSWORD}" \
  -H "Referer: $BASE/login" \
  -w "%{url_effective}\n" > "$WORK/redir.txt"

FINAL_URL="$(cat "$WORK/redir.txt")"
if [[ "$FINAL_URL" == *"/login"* ]]; then
  echo "❌ login failed — final URL was $FINAL_URL" >&2
  exit 1
fi
echo "🔑 logged in as $EMAIL → $FINAL_URL"
echo

# ── component check (real-surface SSR HTML) ──────────────────────────────────
fail=0
for entry in "${PAGES[@]}"; do
  path="${entry%|*}"
  expected="${entry##*|}"
  body="$(curl -sk -b "$COOKIES" -c "$COOKIES" -w "STATUS:%{http_code}" "$BASE${path}")"
  status="${body##*STATUS:}"
  body="${body%STATUS:*}"
  comp_name="$(echo "$body" | grep -oE '&quot;component&quot;:&quot;[^&]+&quot;' | head -1 \
    | sed 's/&quot;component&quot;:&quot;//;s/&quot;//g' | sed 's/\\\//\//g')"
  if [[ "$comp_name" == "$expected" ]]; then
    printf "✅ %-38s %s   %s\n" "$path" "$status" "$comp_name"
  else
    title="$(echo "$body" | grep -oE '<title>[^<]+</title>' | head -1 | sed 's/<title>//;s/<\/title>//')"
    printf "❌ %-38s %s   got=%s expected=%s title=%s\n" \
      "$path" "$status" "${comp_name:-NONE}" "$expected" "$title"
    fail=$((fail + 1))
  fi
done

# ── optional: prop-shape inspection via X-Inertia JSON ───────────────────────
if [[ "${INSPECT_PROPS:-0}" == "1" ]]; then
  echo
  INERTIA_VERSION="$(curl -sk -b "$COOKIES" "$BASE${PAGES[0]%|*}" \
    | grep -oE '&quot;version&quot;:&quot;[^&]+&quot;' | head -1 \
    | sed 's/&quot;version&quot;:&quot;//;s/&quot;//g')"
  echo "🔎 prop-shape pass (Inertia version $INERTIA_VERSION)"
  echo
  for entry in "${PAGES[@]}"; do
    path="${entry%|*}"
    json="$(curl -sk -b "$COOKIES" \
      -H 'X-Inertia: true' \
      -H "X-Inertia-Version: $INERTIA_VERSION" \
      -H 'Accept: application/json' \
      -w "STATUS:%{http_code}" "$BASE${path}")"
    status="${json##*STATUS:}"
    json="${json%STATUS:*}"
    if [[ "$status" == "200" ]]; then
      keys="$(echo "$json" | jq -r '.props | keys | join(",")' 2>/dev/null)"
      printf "   %-38s [%s]\n" "$path" "$keys"
    else
      printf "   %-38s status=%s\n" "$path" "$status"
    fi
  done
fi

echo
if [[ $fail -eq 0 ]]; then
  echo "✓ all ${#PAGES[@]} pages render the expected component"
  exit 0
else
  echo "✗ $fail / ${#PAGES[@]} page(s) failed"
  exit 1
fi
