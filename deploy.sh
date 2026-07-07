#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")"

# ── Pre-flight checks ─────────────────────────────────────────────────────
if [ ! -f infra/terraform.tfvars ]; then
  echo "ERROR: infra/terraform.tfvars not found."
  echo "  cp infra/terraform.tfvars.example infra/terraform.tfvars"
  echo "  Edit it with your project_id."
  exit 1
fi

if [ -f .env ]; then
  set -a
  source .env
  set +a
fi

# Detect gcloud command for Windows Git Bash compatibility
if command -v gcloud.cmd &>/dev/null; then
  GCLOUD="gcloud.cmd"
else
  GCLOUD="gcloud"
fi

if ! command -v ${GCLOUD} &>/dev/null; then
  echo "ERROR: gcloud CLI not found."; exit 1
fi

ACCOUNT=$(${GCLOUD} auth list --filter=status:ACTIVE --format="value(account)" 2>/dev/null || true)
if [ -z "$ACCOUNT" ]; then
  echo "ERROR: No active gcloud account. Run: ${GCLOUD} auth login"
  exit 1
fi

PROJECT_ID=$(grep -oP 'project_id\s*=\s*"\K[^"]+' infra/terraform.tfvars 2>/dev/null || true)
if [ -z "$PROJECT_ID" ]; then
  echo "ERROR: Could not read project_id from infra/terraform.tfvars"
  exit 1
fi
echo "  Using project: ${PROJECT_ID} (from terraform.tfvars)"

CURRENT_GCLOUD_PROJECT=$(${GCLOUD} config get-value project 2>/dev/null || true)
if [ "$CURRENT_GCLOUD_PROJECT" != "$PROJECT_ID" ]; then
  echo "  ⚠ gcloud active project is '${CURRENT_GCLOUD_PROJECT}' but tfvars says '${PROJECT_ID}'"
  echo "  → Setting gcloud project to match..."
  ${GCLOUD} config set project "$PROJECT_ID"
fi

REGION="us-central1"
ZONE="us-central1-a"
CLUSTER_NAME="what2cook-cluster"
IMAGE_REPO="${REGION}-docker.pkg.dev/${PROJECT_ID}/what2cook/app"
TAG=$(git rev-parse --short HEAD)
IMAGE="${IMAGE_REPO}:${TAG}"

echo "Deploying to project: ${PROJECT_ID}"
echo "Active account:       ${ACCOUNT}"
echo ""

# ── 1. Deploy GCP infrastructure ──────────────────────────────────────────
echo "=== 1. Deploy GCP infrastructure with Terraform ==="
cd infra
terraform init
terraform plan -out=tfplan
echo "✓ Plan generado correctamente"
echo ""
read -p "¿Desea aplicar este plan? (y/N) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
  echo "Despliegue cancelado por el usuario."
  exit 1
fi
terraform apply tfplan
cd ..

echo "=== 2. Get outputs ==="
STATIC_IP=$(terraform -chdir=infra output -raw ingress_static_ip)
IMAGE_REPO_FULL=$(terraform -chdir=infra output -raw artifact_registry_repo)
SSIP_DOMAIN=$(terraform -chdir=infra output -raw sslip_domain)
DB_PASSWORD=$(terraform -chdir=infra output -raw postgres_password)

echo "   Static IP:  ${STATIC_IP}"
echo "   Domain:     ${SSIP_DOMAIN}"
echo ""

# ── 3. Build and push ──────────────────────────────────────────────────────
echo "=== 3. Authenticate with Artifact Registry ==="
${GCLOUD} auth configure-docker ${REGION}-docker.pkg.dev --quiet

echo "=== 4. Build and push image ==="
docker build -f docker/Dockerfile.k8s -t ${IMAGE} .
docker push ${IMAGE}

echo "=== 5. Get GKE credentials ==="
${GCLOUD} container clusters get-credentials ${CLUSTER_NAME} --zone ${ZONE} --project ${PROJECT_ID}

# ── 6. Helm charts ─────────────────────────────────────────────────────────
echo "=== 6. Install nginx-ingress ==="
helm repo add ingress-nginx https://kubernetes.github.io/ingress-nginx --force-update
helm upgrade --install ingress-nginx ingress-nginx/ingress-nginx \
  --namespace ingress-nginx --create-namespace \
  --set controller.service.loadBalancerIP=${STATIC_IP} \
  --set controller.service.type=LoadBalancer \
  --set controller.publishService.enabled=true \
  --wait --timeout 5m

echo "=== 7. Install cert-manager ==="
helm repo add jetstack https://charts.jetstack.io --force-update
helm upgrade --install cert-manager jetstack/cert-manager \
  --namespace cert-manager --create-namespace \
  --set installCRDs=true \
  --wait --timeout 3m

# ── 8. Generate manifests ──────────────────────────────────────────────────
echo "=== 8. Generate manifests ==="
rm -rf k8s/_generated
mkdir -p k8s/_generated

# ingress
sed \
  -e "s|__DOMAIN__|${SSIP_DOMAIN}|g" \
  k8s/ingress.yaml.template > k8s/_generated/ingress.yaml

# cluster issuer
sed \
  -e "s|__DOMAIN__|${SSIP_DOMAIN}|g" \
  k8s/cert-manager/cluster-issuer.yaml > k8s/_generated/cluster-issuer.yaml

# app deployment + cronjob
for f in k8s/app/deployment.yaml k8s/cronjob.yaml; do
  out="k8s/_generated/$(basename "$f")"
  sed \
    -e "s|__IMAGE__|${IMAGE_REPO}|g" \
    -e "s|__TAG__|${TAG}|g" \
    "$f" > "$out"
done

# secret: DB password from terraform + API keys from env vars
sed \
  -e "s|DB_PASSWORD: \"\"|DB_PASSWORD: \"${DB_PASSWORD}\"|g" \
  -e "s|SPOONACULAR_KEY: \"\"|SPOONACULAR_KEY: \"${SPOONACULAR_KEY:-}\"|g" \
  -e "s|SPOONACULAR_KEY_2: \"\"|SPOONACULAR_KEY_2: \"${SPOONACULAR_KEY_2:-}\"|g" \
  -e "s|SPOONACULAR_KEY_BACKGROUND: \"\"|SPOONACULAR_KEY_BACKGROUND: \"${SPOONACULAR_KEY_BACKGROUND:-}\"|g" \
  -e "s|OPENAI_API_KEY: \"\"|OPENAI_API_KEY: \"${OPENAI_API_KEY:-}\"|g" \
  -e "s|GEMINI_API_KEY: \"\"|GEMINI_API_KEY: \"${GEMINI_API_KEY:-}\"|g" \
  -e "s|GOOGLE_CLIENT_ID: \"\"|GOOGLE_CLIENT_ID: \"${GOOGLE_CLIENT_ID:-}\"|g" \
  -e "s|GOOGLE_CLIENT_SECRET: \"\"|GOOGLE_CLIENT_SECRET: \"${GOOGLE_CLIENT_SECRET:-}\"|g" \
  -e "s|GOOGLE_REDIRECT_URI: \"\"|GOOGLE_REDIRECT_URI: \"${GOOGLE_REDIRECT_URI:-}\"|g" \
  k8s/secret.yaml > k8s/_generated/secret.yaml

echo ""
echo "  Verify secrets before continuing:"
echo "    SPOONACULAR_KEY:        ${SPOONACULAR_KEY:+✅ set}${SPOONACULAR_KEY:-⚠️  EMPTY}"
echo "    SPOONACULAR_KEY_2:      ${SPOONACULAR_KEY_2:+✅ set}${SPOONACULAR_KEY_2:-⚠️  EMPTY}"
echo "    SPOONACULAR_KEY_BG:     ${SPOONACULAR_KEY_BACKGROUND:+✅ set}${SPOONACULAR_KEY_BACKGROUND:-⚠️  EMPTY}"
echo "    OPENAI_API_KEY:         ${OPENAI_API_KEY:+✅ set}${OPENAI_API_KEY:-⚪ optional}"
echo "    GEMINI_API_KEY:         ${GEMINI_API_KEY:+✅ set}${GEMINI_API_KEY:-⚪ optional}"
echo "    GOOGLE_CLIENT_ID:       ${GOOGLE_CLIENT_ID:+✅ set}${GOOGLE_CLIENT_ID:-⚠️  EMPTY}"
echo "    GOOGLE_CLIENT_SECRET:  ${GOOGLE_CLIENT_SECRET:+✅ set}${GOOGLE_CLIENT_SECRET:-⚠️  EMPTY}"
echo "    GOOGLE_REDIRECT_URI:    ${GOOGLE_REDIRECT_URI:+✅ set}${GOOGLE_REDIRECT_URI:-⚠️  EMPTY}"
echo ""

# ── 9. Apply manifests ────────────────────────────────────────────────────
echo "=== 9. Apply k8s manifests ==="
kubectl apply -f k8s/namespace.yaml
kubectl apply -f k8s/_generated/cluster-issuer.yaml
kubectl apply -f k8s/configmap.yaml
kubectl apply -f k8s/_generated/secret.yaml
kubectl apply -f k8s/postgres/
kubectl apply -f k8s/_generated/deployment.yaml
kubectl apply -f k8s/app/service.yaml
kubectl apply -f k8s/libretranslate/
kubectl apply -f k8s/_generated/cronjob.yaml

echo "=== 10. Wait for PostgreSQL and run migrations ==="
kubectl -n what2cook wait --for=condition=ready pod -l app=postgres --timeout=180s
sleep 10
kubectl rollout status deployment/app -n what2cook --timeout=300s
POD=$(kubectl get pod -n what2cook -l app=app -o jsonpath='{.items[0].metadata.name}')
kubectl exec -n what2cook "$POD" -- php vendor/bin/phinx migrate -e development

echo "=== 11. Apply ingress (last, after cert-manager is ready) ==="
kubectl -n cert-manager wait --for=condition=available deploy/cert-manager --timeout=120s
kubectl -n cert-manager wait --for=condition=available deploy/cert-manager-webhook --timeout=120s
sleep 15
kubectl apply -f k8s/_generated/ingress.yaml

echo ""
echo "============================================"
echo "  Deploy complete!"
echo ""
echo "  URL:  https://${SSIP_DOMAIN}"
echo ""
echo "  Note: SSL certificate provisioning takes"
echo "  2-5 minutes after deploy."
echo "============================================"
