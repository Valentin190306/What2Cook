output "cluster_name" {
  value = google_container_cluster.cluster.name
}

output "cluster_zone" {
  value = var.zone
}

output "ingress_static_ip" {
  value = google_compute_address.ingress.address
}

output "artifact_registry_repo" {
  value = "${var.region}-docker.pkg.dev/${var.project_id}/${google_artifact_registry_repository.app.repository_id}/app"
}

output "sslip_domain" {
  value = "what2cook.${replace(google_compute_address.ingress.address, ".", "-")}.sslip.io"
}

output "postgres_password" {
  value     = random_password.postgres.result
  sensitive = true
}

resource "random_password" "postgres" {
  length  = 24
  special = false
}
