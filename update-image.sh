#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")"

PROJECT_ID=$(grep -oP 'project_id\s*=\s*"\K[^"]+' infra/terraform.tfvars 2>/dev/null || true)
if [ -z "$PROJECT_ID" ]; then
  echo "ERROR: Could not read project_id from infra/terraform.tfvars"
  exit 1
fi

REGION="${REGION:-us-central1}"
ZONE="${ZONE:-us-central1-a}"
CLUSTER_NAME="${CLUSTER_NAME:-what2cook-cluster}"
IMAGE_REPO="${REGION}-docker.pkg.dev/${PROJECT_ID}/what2cook/app"
TAG="${TAG:-latest}"
IMAGE="${IMAGE_REPO}:${TAG}"
NAMESPACE="${NAMESPACE:-what2cook}"
DEPLOYMENT="${DEPLOYMENT:-app}"

echo "  Project:     ${PROJECT_ID}"
echo "  Region:      ${REGION}"
echo "  Zone:        ${ZONE}"
echo "  Cluster:     ${CLUSTER_NAME}"
echo "  Image:       ${IMAGE}"
echo "  Namespace:   ${NAMESPACE}"
echo "  Deployment:  ${DEPLOYMENT}"
echo ""

if ! command -v gcloud &>/dev/null; then
  echo "ERROR: gcloud CLI not found."; exit 1
fi

ACCOUNT=$(gcloud auth list --filter=status:ACTIVE --format="value(account)" 2>/dev/null || true)
if [ -z "$ACCOUNT" ]; then
  echo "ERROR: No active gcloud account. Run: gcloud auth login"
  exit 1
fi

echo "  Authenticated as: ${ACCOUNT}"
echo ""

CURRENT_GCLOUD_PROJECT=$(gcloud config get-value project 2>/dev/null || true)
if [ "$CURRENT_GCLOUD_PROJECT" != "$PROJECT_ID" ]; then
  echo "  Setting gcloud project to ${PROJECT_ID}..."
  gcloud config set project "$PROJECT_ID"
fi

echo "=== 1. Authenticate Docker with Artifact Registry ==="
gcloud auth configure-docker ${REGION}-docker.pkg.dev --quiet

echo "=== 2. Build image ==="
docker build -f docker/Dockerfile.k8s -t ${IMAGE} .

echo "=== 3. Push image ==="
docker push ${IMAGE}

echo "=== 4. Get GKE credentials ==="
gcloud container clusters get-credentials ${CLUSTER_NAME} --zone ${ZONE}

echo "=== 5. Rollout restart deployment ==="
kubectl -n ${NAMESPACE} rollout restart deploy/${DEPLOYMENT}

echo "=== 6. Wait for rollout to complete ==="
kubectl -n ${NAMESPACE} rollout status deploy/${DEPLOYMENT} --watch --timeout=180s

echo ""
echo "=== Done! ==="
echo "  Image: ${IMAGE}"
echo "  Deployment: ${NAMESPACE}/${DEPLOYMENT}"
