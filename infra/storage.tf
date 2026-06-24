resource "google_artifact_registry_repository" "app" {
  repository_id = "what2cook"
  location      = var.region
  format        = "DOCKER"

  depends_on = [
    google_project_service.services["artifactregistry.googleapis.com"]
  ]
}
