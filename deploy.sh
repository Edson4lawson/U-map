#!/bin/bash

# U-Map Deployment Script
# This script handles deployment for both Docker Compose and Kubernetes

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
DEPLOYMENT_TYPE=${1:-docker}
ENVIRONMENT=${2:-production}

echo -e "${GREEN}=== U-Map Deployment Script ===${NC}"
echo "Deployment Type: $DEPLOYMENT_TYPE"
echo "Environment: $ENVIRONMENT"
echo ""

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to print error and exit
error_exit() {
    echo -e "${RED}ERROR: $1${NC}" >&2
    exit 1
}

# Function to print success
success_msg() {
    echo -e "${GREEN}✓ $1${NC}"
}

# Function to print warning
warning_msg() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Check prerequisites
check_prerequisites() {
    echo "Checking prerequisites..."
    
    if [ "$DEPLOYMENT_TYPE" = "docker" ]; then
        command_exists docker || error_exit "Docker is not installed"
        command_exists docker-compose || error_exit "Docker Compose is not installed"
        success_msg "Docker and Docker Compose are installed"
    elif [ "$DEPLOYMENT_TYPE" = "kubernetes" ]; then
        command_exists kubectl || error_exit "kubectl is not installed"
        success_msg "kubectl is installed"
    fi
    
    echo ""
}

# Load environment variables
load_env() {
    echo "Loading environment variables..."
    
    if [ ! -f ".env.production" ]; then
        error_exit ".env.production file not found. Please create it first."
    fi
    
    # Load .env.production
    export $(cat .env.production | grep -v '^#' | xargs)
    success_msg "Environment variables loaded"
    echo ""
}

# Docker Compose deployment
deploy_docker() {
    echo "=== Docker Compose Deployment ==="
    
    # Check if .env file exists for docker-compose
    if [ ! -f ".env" ]; then
        warning_msg ".env file not found, copying from .env.production"
        cp .env.production .env
    fi
    
    # Stop existing containers
    echo "Stopping existing containers..."
    docker-compose -f docker-compose.production.yml down || true
    
    # Build and start containers
    echo "Building and starting containers..."
    docker-compose -f docker-compose.production.yml up -d --build
    
    # Run migrations
    echo "Running database migrations..."
    docker-compose -f docker-compose.production.yml exec -T backend php artisan migrate --force
    
    # Clear cache
    echo "Clearing application cache..."
    docker-compose -f docker-compose.production.yml exec -T backend php artisan cache:clear
    docker-compose -f docker-compose.production.yml exec -T backend php artisan config:clear
    docker-compose -f docker-compose.production.yml exec -T backend php artisan route:clear
    docker-compose -f docker-compose.production.yml exec -T backend php artisan view:clear
    
    # Check container status
    echo ""
    echo "Container status:"
    docker-compose -f docker-compose.production.yml ps
    
    success_msg "Docker Compose deployment completed"
    echo ""
}

# Kubernetes deployment
deploy_kubernetes() {
    echo "=== Kubernetes Deployment ==="
    
    # Check if kubeconfig is set
    if [ -z "$KUBECONFIG" ] && [ ! -f "$HOME/.kube/config" ]; then
        error_exit "Kubeconfig not found. Please set KUBECONFIG or ensure ~/.kube/config exists."
    fi
    
    # Create namespace if it doesn't exist
    echo "Creating namespace..."
    kubectl apply -f k8s/namespace.yaml || true
    
    # Create secrets
    echo "Creating secrets..."
    kubectl create secret generic umap-secrets \
        --from-literal=app-key="$APP_KEY" \
        --from-literal=db-database="$DB_DATABASE" \
        --from-literal=db-username="$DB_USERNAME" \
        --from-literal=db-password="$DB_PASSWORD" \
        --from-literal=groq-api-key="$GROQ_API_KEY" \
        --from-literal=gemini-api-key="$GEMINI_API_KEY" \
        --from-literal=redis-password="$REDIS_PASSWORD" \
        --namespace=umap --dry-run=client -o yaml | kubectl apply -f -
    
    # Apply ConfigMap
    echo "Applying ConfigMap..."
    kubectl apply -f k8s/configmap.yaml
    
    # Apply PVC
    echo "Applying Persistent Volume Claims..."
    kubectl apply -f k8s/pvc.yaml
    
    # Deploy backend
    echo "Deploying backend..."
    kubectl apply -f k8s/backend-deployment.yaml
    
    # Deploy frontend
    echo "Deploying frontend..."
    kubectl apply -f k8s/frontend-deployment.yaml
    
    # Deploy worker
    echo "Deploying worker..."
    kubectl apply -f k8s/worker-deployment.yaml
    
    # Deploy scheduler
    echo "Deploying scheduler..."
    kubectl apply -f k8s/scheduler-deployment.yaml
    
    # Apply services
    echo "Applying services..."
    kubectl apply -f k8s/services.yaml
    
    # Apply HPA
    echo "Applying Horizontal Pod Autoscaler..."
    kubectl apply -f k8s/hpa.yaml
    
    # Apply ingress
    echo "Applying ingress..."
    kubectl apply -f k8s/ingress.yaml
    
    # Apply network policies
    echo "Applying network policies..."
    kubectl apply -f k8s/network-policies.yaml
    
    # Wait for deployments
    echo "Waiting for deployments to be ready..."
    kubectl rollout status deployment/umap-backend -n umap --timeout=5m
    kubectl rollout status deployment/umap-frontend -n umap --timeout=5m
    kubectl rollout status deployment/umap-worker -n umap --timeout=5m
    kubectl rollout status deployment/umap-scheduler -n umap --timeout=5m
    
    # Show status
    echo ""
    echo "Pods status:"
    kubectl get pods -n umap
    
    echo ""
    echo "Services status:"
    kubectl get services -n umap
    
    echo ""
    echo "Ingress status:"
    kubectl get ingress -n umap
    
    success_msg "Kubernetes deployment completed"
    echo ""
}

# Health check
health_check() {
    echo "=== Health Check ==="
    
    if [ "$DEPLOYMENT_TYPE" = "docker" ]; then
        echo "Checking backend health..."
        docker-compose -f docker-compose.production.yml exec -T backend curl -f http://localhost:8000/api/health || error_exit "Backend health check failed"
        
        echo "Checking frontend health..."
        curl -f http://localhost/ || error_exit "Frontend health check failed"
        
    elif [ "$DEPLOYMENT_TYPE" = "kubernetes" ]; then
        echo "Checking backend health..."
        kubectl exec -n umap deployment/umap-backend -- curl -f http://localhost:8000/api/health || error_exit "Backend health check failed"
    fi
    
    success_msg "Health check passed"
    echo ""
}

# Main execution
main() {
    check_prerequisites
    load_env
    
    if [ "$DEPLOYMENT_TYPE" = "docker" ]; then
        deploy_docker
    elif [ "$DEPLOYMENT_TYPE" = "kubernetes" ]; then
        deploy_kubernetes
    else
        error_exit "Invalid deployment type. Use 'docker' or 'kubernetes'"
    fi
    
    health_check
    
    success_msg "Deployment completed successfully!"
    echo ""
    echo "Next steps:"
    echo "1. Verify the application is accessible"
    echo "2. Check logs: docker-compose -f docker-compose.production.yml logs -f (for Docker)"
    echo "3. Monitor performance and set up alerts"
}

# Run main function
main
