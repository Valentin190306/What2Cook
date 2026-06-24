resource "google_service_account" "gke_nodes" {
  account_id   = "what2cook-gke-nodes"
  display_name = "What2Cook GKE Node Service Account"
}

resource "google_project_iam_member" "gke_nodes_artifact_registry" {
  project = var.project_id
  role    = "roles/artifactregistry.reader"
  member  = "serviceAccount:${google_service_account.gke_nodes.email}"
}

resource "google_project_iam_member" "gke_nodes_logging" {
  project = var.project_id
  role    = "roles/logging.logWriter"
  member  = "serviceAccount:${google_service_account.gke_nodes.email}"
}

resource "google_project_iam_member" "gke_nodes_monitoring" {
  project = var.project_id
  role    = "roles/monitoring.metricWriter"
  member  = "serviceAccount:${google_service_account.gke_nodes.email}"
}

resource "google_container_cluster" "cluster" {
  name     = var.cluster_name
  location = var.zone

  initial_node_count = 1
  deletion_protection = false

  network    = google_compute_network.vpc.id
  subnetwork = google_compute_subnetwork.subnet.id

  ip_allocation_policy {
    cluster_secondary_range_name  = null
    services_secondary_range_name = null
  }

  networking_mode = "VPC_NATIVE"

  workload_identity_config {
    workload_pool = "${var.project_id}.svc.id.goog"
  }

  node_config {
    machine_type = var.node_machine_type
    disk_size_gb = var.node_disk_size_gb
    disk_type    = "pd-standard"

    service_account = google_service_account.gke_nodes.email
    oauth_scopes = [
      "https://www.googleapis.com/auth/cloud-platform",
    ]

    labels = {
      app = "what2cook"
    }
  }

  depends_on = [
    google_project_service.services["container.googleapis.com"],
    google_project_iam_member.gke_nodes_logging,
    google_project_iam_member.gke_nodes_monitoring,
  ]
}
