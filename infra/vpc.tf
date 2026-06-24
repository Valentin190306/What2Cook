resource "google_compute_network" "vpc" {
  name                    = "what2cook-vpc"
  auto_create_subnetworks = false
  depends_on              = [google_project_service.services["compute.googleapis.com"]]
}

resource "google_compute_subnetwork" "subnet" {
  name          = "what2cook-subnet"
  network       = google_compute_network.vpc.id
  region        = var.region
  ip_cidr_range = "10.0.0.0/24"

  private_ip_google_access = true
}

resource "google_compute_address" "ingress" {
  name   = "what2cook-ip"
  region = var.region
}

resource "google_compute_firewall" "allow_health_checks" {
  name    = "what2cook-allow-health-checks"
  network = google_compute_network.vpc.name

  allow {
    protocol = "tcp"
    ports    = ["80", "443"]
  }

  source_ranges = ["35.191.0.0/16", "130.211.0.0/22"]
  target_tags   = ["gke-what2cook"]
}

resource "google_compute_firewall" "allow_internal" {
  name    = "what2cook-allow-internal"
  network = google_compute_network.vpc.name

  allow {
    protocol = "tcp"
    ports    = ["0-65535"]
  }

  source_ranges = ["10.0.0.0/24"]
}
