# Open Source Technology Stack

Curated open-source tools for each architecture component. All tools are production-ready, actively maintained, and support both on-prem and cloud deployment.

## GenAI (Generative AI)

### LLM Inference

**Recommended: Ollama**
- **Why**: Easy local deployment, supports multiple models, production-ready
- **License**: MIT
- **Deployment**: Docker available, runs on-prem and cloud
- **Models**: Llama 3, Mistral, Gemma, many others

**Alternatives**:
- **vLLM**: High-performance inference server, best for scale
- **LocalAI**: OpenAI-compatible API, drop-in replacement
- **Text Generation Inference (TGI)**: Hugging Face's inference server

### Embedding Models

**Recommended: sentence-transformers**
- **Why**: Extensive model library, production-proven
- **License**: Apache 2.0
- **Deployment**: Python package, runs anywhere
- **Models**: all-MiniLM-L6-v2, all-mpnet-base-v2, many others

**Alternatives**:
- **Instructor**: State-of-the-art instruction-based embeddings
- **BGE Models**: BAAI general embeddings

## Agentic AI

### Workflow Orchestration

**Recommended: LangGraph**
- **Why**: State machine for agent workflows, composable
- **License**: MIT
- **Deployment**: Python library, framework-agnostic

**Alternatives**:
- **AutoGen**: Multi-agent conversation framework
- **CrewAI**: Role-based agent orchestration
- **Custom State Machine**: Build your own with plain code

### Decision Frameworks

**Recommended: Custom Logic + LLM**
- **Why**: Flexibility for 1-2 week MVPs, no framework overhead
- **Pattern**: Define decision trees, use LLM for classification/routing

**Alternatives**:
- **ReAct Pattern**: Reasoning + Acting framework
- **Chain-of-Thought**: Structured reasoning prompts

## RAG (Retrieval-Augmented Generation)

### Vector Stores

**Recommended: ChromaDB**
- **Why**: Simple setup, embedded or client-server, production-ready
- **License**: Apache 2.0
- **Deployment**: Python package (embedded) or Docker (server)

**Alternatives**:
- **Weaviate**: Feature-rich, strong hybrid search
- **Milvus**: Highly scalable, enterprise features
- **Qdrant**: Rust-based, high performance
- **FAISS**: Meta's library, fast but lower-level

### Retrieval Frameworks

**Recommended: LlamaIndex**
- **Why**: Purpose-built for RAG, excellent documentation
- **License**: MIT
- **Deployment**: Python library

**Alternatives**:
- **LangChain**: More general, includes RAG capabilities
- **Haystack**: Pipeline-based, modular

### Document Processing

**Recommended: Unstructured**
- **Why**: Handles many formats (PDF, DOCX, HTML, etc.), partitioning strategies
- **License**: Apache 2.0
- **Deployment**: Python package or API

**Alternatives**:
- **PyPDF**: PDF-specific, lightweight
- **python-docx**: DOCX-specific
- **Beautiful Soup**: HTML/XML parsing

## Data Layer

### Document Store Connectors

**Pattern**: Custom scripts using standard libraries
- **Confluence**: `atlassian-python-api`
- **SharePoint**: `Office365-REST-Python-Client`
- **Notion**: `notion-client`
- **Google Drive**: `google-api-python-client`

### File Store Connectors

**Recommended: fsspec**
- **Why**: Unified interface for local, S3, Azure Blob, GCS, etc.
- **License**: BSD
- **Deployment**: Python package

**Alternatives**:
- **boto3**: AWS S3 (if AWS-only)
- **azure-storage-blob**: Azure (if Azure-only)

### ETL Pipelines

**Recommended: Custom Python Scripts**
- **Why**: Simple, fast to develop, no framework overhead

**Alternatives**:
- **Apache Airflow**: If complex scheduling needed (may be overkill for MVP)
- **Prefect**: Modern workflow orchestration

## API Layer

### Web Frameworks

**Recommended: FastAPI**
- **Why**: Modern, async, auto-generated OpenAPI docs, fast development
- **License**: MIT
- **Deployment**: Python, runs with Uvicorn/Gunicorn

**Alternatives**:
- **Flask**: Simpler, more mature ecosystem
- **Django REST Framework**: If full Django features needed

### Authentication

**Recommended: OAuth2 + JWT**
- **Libraries**: `python-jose`, `passlib`, `python-multipart`
- **Why**: Standard, secure, stateless

**Alternatives**:
- **API Keys**: Simpler for internal MVPs
- **OAuth2 + Keycloak**: If SSO/IdP needed

## Deployment

### Containerization

**Recommended: Docker**
- **Why**: Industry standard, runs anywhere
- **License**: Apache 2.0
- **Deployment**: On-prem and all clouds

**Compose**: Use `docker-compose` for multi-container MVPs

### Orchestration

**Recommended: Docker Compose (MVP)**
- **Why**: Simple, sufficient for 1-2 week MVPs
- **Deployment**: Single-node or small clusters

**Alternatives** (for production scale):
- **Kubernetes**: Industry standard for container orchestration
- **Docker Swarm**: Simpler than K8s, Docker-native
- **Nomad**: HashiCorp's orchestrator

### CI/CD

**Recommended: GitHub Actions** (if using GitHub)
- **Why**: Integrated, free for public repos
- **Deployment**: Cloud-hosted

**Alternatives**:
- **GitLab CI**: Integrated with GitLab
- **Jenkins**: Self-hosted, very flexible

## Monitoring & Observability

### Logging

**Recommended: Python logging + JSON formatter**
- **Why**: Built-in, structured logs for aggregation
- **Deployment**: Application-level

**Alternatives**:
- **Loguru**: Better Python logging library
- **ELK Stack**: Elasticsearch + Logstash + Kibana (if centralized logging needed)

### Metrics

**Recommended: Prometheus + Grafana**
- **Why**: Industry standard, rich ecosystem
- **License**: Apache 2.0
- **Deployment**: Docker, on-prem and cloud

## AI Automation Integration

### Integration Patterns

**Pattern**: Expose automation hooks via APIs
- **Webhooks**: For event-driven automation
- **REST APIs**: For request-driven automation
- **Message Queues**: For async automation (RabbitMQ, Redis)

**Tools** (if organization uses):
- **n8n**: Open-source workflow automation
- **Zapier**: Commercial (check if open-source requirement flexible)
- **Make (Integromat)**: Commercial

## Selection Decision Matrix

| Component | Speed | Maturity | Community | On-Prem | Cloud | Modularity |
|-----------|-------|----------|-----------|---------|-------|------------|
| Ollama | ★★★★★ | ★★★★☆ | ★★★★★ | ✓ | ✓ | ★★★★★ |
| ChromaDB | ★★★★★ | ★★★★☆ | ★★★★☆ | ✓ | ✓ | ★★★★★ |
| LlamaIndex | ★★★★★ | ★★★★★ | ★★★★★ | ✓ | ✓ | ★★★★★ |
| FastAPI | ★★★★★ | ★★★★★ | ★★★★★ | ✓ | ✓ | ★★★★★ |
| Docker | ★★★★★ | ★★★★★ | ★★★★★ | ✓ | ✓ | ★★★★★ |

**Speed**: Setup time for 1-2 week MVP
**Maturity**: Production-readiness
**Community**: Support availability
**Modularity**: API-driven, composable

## Recommended MVP Stack (Quick Start)

For fastest 1-2 week MVP:

```
├── GenAI: Ollama (Llama 3 or Mistral)
├── RAG: LlamaIndex + ChromaDB + sentence-transformers
├── Agentic AI: LangGraph or custom logic
├── API: FastAPI
├── Data: Custom Python scripts + Unstructured
├── Deployment: Docker + docker-compose
└── Monitoring: Python logging + Prometheus (optional)
```

This stack balances speed, simplicity, and production-readiness for rapid MVP delivery.
