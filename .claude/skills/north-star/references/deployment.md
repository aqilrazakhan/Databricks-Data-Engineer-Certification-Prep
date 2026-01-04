# Deployment Guides

Comprehensive deployment strategies for on-premises and cloud environments.

## Table of Contents

1. On-Premises Deployment
2. Cloud Deployment
3. Hybrid Deployment
4. Configuration Management
5. Security Best Practices

## 1. On-Premises Deployment

### Docker Compose (Recommended for MVP)

**When to use**: Single-node MVP, quick deployment

#### Project Structure

```
mvp-project/
├── docker-compose.yml
├── .env
├── services/
│   ├── api/
│   │   ├── Dockerfile
│   │   ├── requirements.txt
│   │   └── app/
│   ├── rag/
│   │   ├── Dockerfile
│   │   └── ...
│   └── genai/
│       ├── Dockerfile
│       └── ...
└── data/
    └── vector_db/
```

#### docker-compose.yml Template

```yaml
version: '3.8'

services:
  # Vector Database (ChromaDB)
  vector-db:
    image: chromadb/chroma:latest
    ports:
      - "8000:8000"
    volumes:
      - ./data/vector_db:/chroma/chroma
    environment:
      - IS_PERSISTENT=TRUE

  # LLM Inference (Ollama)
  llm:
    image: ollama/ollama:latest
    ports:
      - "11434:11434"
    volumes:
      - ./data/ollama:/root/.ollama
    deploy:
      resources:
        reservations:
          devices:
            - driver: nvidia
              count: all
              capabilities: [gpu]  # If GPU available

  # API Service
  api:
    build: ./services/api
    ports:
      - "8080:8080"
    environment:
      - VECTOR_DB_URL=http://vector-db:8000
      - LLM_URL=http://llm:11434
      - LOG_LEVEL=${LOG_LEVEL:-INFO}
    env_file:
      - .env
    depends_on:
      - vector-db
      - llm
    volumes:
      - ./services/api/app:/app
    restart: unless-stopped

  # RAG Ingestion Service
  rag-ingestion:
    build: ./services/rag
    environment:
      - VECTOR_DB_URL=http://vector-db:8000
      - DATA_SOURCE_PATH=/data/documents
    volumes:
      - ./data/documents:/data/documents
    depends_on:
      - vector-db
```

#### .env Template

```bash
# Application
APP_NAME=north-star-mvp
LOG_LEVEL=INFO

# LLM Configuration
LLM_MODEL=llama3
LLM_TEMPERATURE=0.7

# RAG Configuration
EMBEDDING_MODEL=all-MiniLM-L6-v2
CHUNK_SIZE=512
CHUNK_OVERLAP=50
TOP_K=5

# API Security
API_KEY=your-secure-api-key-here
CORS_ORIGINS=http://localhost:3000,http://localhost:8080

# Database
VECTOR_DB_COLLECTION=documents
```

#### Deployment Steps

```bash
# 1. Clone/copy project to on-prem server
scp -r mvp-project/ user@server:/opt/

# 2. SSH into server
ssh user@server

# 3. Navigate to project
cd /opt/mvp-project

# 4. Configure environment
cp .env.example .env
nano .env  # Edit as needed

# 5. Pull Ollama model
docker compose run llm ollama pull llama3

# 6. Start services
docker compose up -d

# 7. Verify
docker compose ps
curl http://localhost:8080/health
```

### Kubernetes (For Production Scale)

**When to use**: Multi-node clusters, high availability

#### Deployment YAML Example

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: api-service
spec:
  replicas: 3
  selector:
    matchLabels:
      app: api
  template:
    metadata:
      labels:
        app: api
    spec:
      containers:
      - name: api
        image: your-registry/api:latest
        ports:
        - containerPort: 8080
        env:
        - name: VECTOR_DB_URL
          valueFrom:
            configMapKeyRef:
              name: app-config
              key: vector_db_url
        - name: LLM_URL
          valueFrom:
            configMapKeyRef:
              name: app-config
              key: llm_url
        resources:
          requests:
            memory: "512Mi"
            cpu: "500m"
          limits:
            memory: "1Gi"
            cpu: "1000m"
---
apiVersion: v1
kind: Service
metadata:
  name: api-service
spec:
  selector:
    app: api
  ports:
  - port: 80
    targetPort: 8080
  type: LoadBalancer
```

#### Deployment Steps

```bash
# 1. Build and push images
docker build -t your-registry/api:latest ./services/api
docker push your-registry/api:latest

# 2. Apply configurations
kubectl apply -f k8s/configmap.yaml
kubectl apply -f k8s/secrets.yaml

# 3. Deploy services
kubectl apply -f k8s/vector-db.yaml
kubectl apply -f k8s/llm.yaml
kubectl apply -f k8s/api.yaml

# 4. Verify
kubectl get pods
kubectl get services
```

## 2. Cloud Deployment

### Cloud-Agnostic Approach (Recommended)

Use Docker + cloud VMs for portability across AWS, GCP, Azure.

#### Generic Cloud Deployment

```bash
# 1. Provision VM (example with any cloud provider)
# - OS: Ubuntu 22.04 LTS
# - RAM: 8GB+ (16GB recommended)
# - Storage: 50GB+
# - GPU: Optional (for faster inference)

# 2. Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# 3. Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# 4. Clone project
git clone <your-repo>
cd mvp-project

# 5. Deploy
docker-compose up -d
```

### AWS-Specific

**Option 1: EC2 + Docker Compose**

```bash
# Launch EC2 instance
aws ec2 run-instances \
  --image-id ami-0c55b159cbfafe1f0 \
  --instance-type t3.xlarge \
  --key-name your-key \
  --security-group-ids sg-xxxxx \
  --subnet-id subnet-xxxxx

# Follow generic cloud deployment steps above
```

**Option 2: ECS (Elastic Container Service)**

```yaml
# task-definition.json
{
  "family": "north-star-mvp",
  "containerDefinitions": [
    {
      "name": "api",
      "image": "your-ecr-repo/api:latest",
      "memory": 1024,
      "cpu": 512,
      "portMappings": [
        {
          "containerPort": 8080,
          "protocol": "tcp"
        }
      ],
      "environment": [
        {
          "name": "VECTOR_DB_URL",
          "value": "http://vector-db:8000"
        }
      ]
    }
  ]
}
```

### GCP-Specific

**Option 1: Compute Engine + Docker Compose**

```bash
# Create instance
gcloud compute instances create north-star-mvp \
  --machine-type=n1-standard-4 \
  --image-family=ubuntu-2204-lts \
  --image-project=ubuntu-os-cloud \
  --boot-disk-size=50GB

# SSH and deploy
gcloud compute ssh north-star-mvp
# Follow generic cloud deployment steps
```

**Option 2: GKE (Google Kubernetes Engine)**

```bash
# Create cluster
gcloud container clusters create north-star-cluster \
  --num-nodes=3 \
  --machine-type=n1-standard-4

# Deploy with kubectl (see Kubernetes section above)
```

### Azure-Specific

**Option 1: VM + Docker Compose**

```bash
# Create VM
az vm create \
  --resource-group north-star-rg \
  --name north-star-vm \
  --image UbuntuLTS \
  --size Standard_D4s_v3 \
  --admin-username azureuser

# Follow generic cloud deployment steps
```

**Option 2: AKS (Azure Kubernetes Service)**

```bash
# Create cluster
az aks create \
  --resource-group north-star-rg \
  --name north-star-aks \
  --node-count 3 \
  --node-vm-size Standard_D4s_v3

# Deploy with kubectl
```

## 3. Hybrid Deployment

### Pattern: Data On-Prem, Compute in Cloud

**When to use**: Data residency requirements, cloud compute benefits

```
┌─────────────────┐         ┌─────────────────┐
│   On-Premises   │         │      Cloud      │
│                 │         │                 │
│  Document Store │◄───────►│   API Service   │
│  File Store     │  VPN/   │   GenAI/RAG     │
│  Vector DB      │  Direct │   LLM Inference │
└─────────────────┘  Link   └─────────────────┘
```

**Setup**:
1. Deploy vector DB and data stores on-prem
2. Deploy compute-intensive services (LLM, API) in cloud
3. Connect via VPN or direct link with encryption
4. Use API authentication for security

### Pattern: Multi-Region Deployment

**When to use**: Global users, data locality laws

```
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  Region US   │    │  Region EU   │    │  Region APAC │
│              │    │              │    │              │
│  Full Stack  │    │  Full Stack  │    │  Full Stack  │
│  Local Data  │    │  Local Data  │    │  Local Data  │
└──────────────┘    └──────────────┘    └──────────────┘
```

## 4. Configuration Management

### Environment-Specific Configs

```
config/
├── base.yaml           # Common settings
├── dev.yaml           # Development overrides
├── staging.yaml       # Staging overrides
└── production.yaml    # Production overrides
```

### Secrets Management

**On-Prem**: Docker secrets or HashiCorp Vault

```yaml
# docker-compose with secrets
services:
  api:
    secrets:
      - api_key
      - db_password

secrets:
  api_key:
    file: ./secrets/api_key.txt
  db_password:
    file: ./secrets/db_password.txt
```

**Cloud**: Use cloud-native secrets

- AWS: AWS Secrets Manager
- GCP: Secret Manager
- Azure: Key Vault

### Configuration Loading Pattern

```python
import os
import yaml

def load_config():
    env = os.getenv('ENVIRONMENT', 'dev')

    # Load base config
    with open('config/base.yaml') as f:
        config = yaml.safe_load(f)

    # Load environment-specific config
    with open(f'config/{env}.yaml') as f:
        env_config = yaml.safe_load(f)

    # Merge configs
    config.update(env_config)

    # Override with environment variables
    config['llm_url'] = os.getenv('LLM_URL', config['llm_url'])

    return config
```

## 5. Security Best Practices

### Network Security

```yaml
# docker-compose with network isolation
networks:
  frontend:
    driver: bridge
  backend:
    driver: bridge
    internal: true  # No external access

services:
  api:
    networks:
      - frontend
      - backend

  vector-db:
    networks:
      - backend  # Not exposed externally
```

### API Security

```python
# API key authentication
from fastapi import Security, HTTPException
from fastapi.security import APIKeyHeader

api_key_header = APIKeyHeader(name="X-API-Key")

def verify_api_key(api_key: str = Security(api_key_header)):
    if api_key != os.getenv("API_KEY"):
        raise HTTPException(status_code=403, detail="Invalid API Key")
    return api_key
```

### TLS/HTTPS

```nginx
# nginx reverse proxy with TLS
server {
    listen 443 ssl;
    server_name your-domain.com;

    ssl_certificate /etc/ssl/certs/cert.pem;
    ssl_certificate_key /etc/ssl/private/key.pem;

    location / {
        proxy_pass http://api:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### Resource Limits

```yaml
# docker-compose with resource limits
services:
  api:
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 2G
        reservations:
          cpus: '1'
          memory: 1G
```

## Health Checks

### Application Health Endpoint

```python
# FastAPI health check
@app.get("/health")
async def health():
    checks = {
        "api": "healthy",
        "vector_db": await check_vector_db(),
        "llm": await check_llm()
    }

    all_healthy = all(v == "healthy" for v in checks.values())

    return {
        "status": "healthy" if all_healthy else "degraded",
        "checks": checks
    }
```

### Docker Compose Health Checks

```yaml
services:
  api:
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8080/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s
```

## Monitoring

### Prometheus + Grafana

```yaml
# docker-compose monitoring
services:
  prometheus:
    image: prom/prometheus:latest
    ports:
      - "9090:9090"
    volumes:
      - ./prometheus.yml:/etc/prometheus/prometheus.yml

  grafana:
    image: grafana/grafana:latest
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin
```

### Application Metrics

```python
# Export metrics
from prometheus_client import Counter, Histogram, make_asgi_app

query_counter = Counter('queries_total', 'Total queries')
query_duration = Histogram('query_duration_seconds', 'Query duration')

@app.get("/query")
@query_duration.time()
async def query(q: str):
    query_counter.inc()
    # Process query
    return result

# Mount metrics endpoint
metrics_app = make_asgi_app()
app.mount("/metrics", metrics_app)
```

## Backup and Recovery

### Vector DB Backup

```bash
# Backup script
#!/bin/bash
BACKUP_DIR=/backups/vector_db
DATE=$(date +%Y%m%d_%H%M%S)

# Stop writes (optional, for consistency)
# docker-compose exec vector-db /pause-writes.sh

# Backup
docker-compose exec -T vector-db tar czf - /chroma/chroma > $BACKUP_DIR/backup_$DATE.tar.gz

# Resume writes
# docker-compose exec vector-db /resume-writes.sh

# Cleanup old backups (keep last 7 days)
find $BACKUP_DIR -name "backup_*.tar.gz" -mtime +7 -delete
```

### Automated Backups

```yaml
# docker-compose with backup container
services:
  backup:
    image: your-backup-image
    volumes:
      - ./data:/data:ro
      - /backups:/backups
    environment:
      - BACKUP_SCHEDULE=0 2 * * *  # Daily at 2 AM
```

## Deployment Checklist

Before going live:

- [ ] Environment variables configured
- [ ] Secrets properly managed (not in code)
- [ ] TLS/HTTPS enabled
- [ ] API authentication enabled
- [ ] Resource limits set
- [ ] Health checks configured
- [ ] Monitoring and logging set up
- [ ] Backup strategy implemented
- [ ] Firewall rules configured
- [ ] Documentation complete (runbooks, incident response)
- [ ] Tested on-prem deployment
- [ ] Tested cloud deployment
- [ ] Load testing completed
- [ ] Security scan completed
