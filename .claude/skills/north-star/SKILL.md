---
name: north-star
description: "AI-powered architecture builder using Generative AI, Agentic AI, and RAG technologies for rapid MVP creation (1-2 weeks). Use when users request: (1) Building MVPs for AI-powered solutions, (2) Designing modular AI architectures with GenAI/Agentic AI/RAG, (3) Setting up RAG pipelines with document/file stores, (4) Implementing AI automation workflows, (5) Selecting open-source technology stacks for on-prem or cloud AI deployments, or (6) Creating API-driven, modular AI systems."
---

# North Star Architecture

## Overview

This skill guides rapid MVP development (1-2 weeks) for AI-powered solutions using open-source Generative AI, Agentic AI, and RAG technologies. It ensures modular, API-driven architectures that work on-premises or in-cloud.

## Core Architecture Requirements

Every implementation MUST adhere to these non-negotiable requirements:

1. **Open Source Only** - Use only open-source technologies
2. **Deployment Flexibility** - Support both on-premises and in-country cloud
3. **API-First** - All components expose APIs
4. **Modular Design** - Independently deployable components
5. **RAG Foundation** - Start with document stores, file stores, organizational tool exports
6. **AI Stack** - Incorporate GenAI, Agentic AI, and RAG
7. **Automation Ready** - Design for AI automation integration
8. **MVP Speed** - Target 1-2 week delivery (our differentiator)

## Workflow Decision Tree

```
User Request
    │
    ├─→ "Build MVP for [problem]"
    │   └─→ Go to: MVP Rapid Development Workflow
    │
    ├─→ "Design architecture for [use case]"
    │   └─→ Go to: Architecture Design Workflow
    │
    ├─→ "Set up RAG pipeline"
    │   └─→ Go to: RAG Implementation Workflow
    │
    └─→ "Select tech stack" or "What tools to use"
        └─→ Go to: Technology Selection Workflow
```

## MVP Rapid Development Workflow

For requests like "Build an MVP for X" or "Create a solution for Y problem":

### Step 1: Requirements Clarification

Ask these critical questions:

1. **Deployment**: On-premises or cloud? Any geographic/data residency requirements?
2. **Data Sources**: What document stores, file stores, or organizational tools exist?
3. **Automation**: Is AI automation available in the organization?
4. **Timeline**: Confirm 1-2 week MVP expectation and priority features

### Step 2: Architecture Design

Design using this modular structure:

```
MVP Architecture
├── Data Layer (RAG Foundation)
│   ├── Document Store Connector
│   ├── File Store Connector
│   └── Organizational Tool Exporters
├── AI Core
│   ├── GenAI Module (LLMs, generation)
│   ├── Agentic AI Module (decision-making, workflows)
│   └── RAG Module (retrieval, grounding)
├── API Layer
│   ├── REST/GraphQL endpoints
│   └── Authentication/Authorization
└── Automation Layer (if available)
    └── AI Automation integrations
```

### Step 3: Technology Selection

Refer to `references/tech-stack.md` for open-source tools. Apply these principles:

- **Prefer mature, well-documented projects** for 1-2 week timeline
- **Choose deployment-agnostic tools** (Docker-compatible)
- **Prioritize active communities** for troubleshooting support
- **Validate open-source licenses** for client requirements

### Step 4: Implementation

1. **Scaffold project structure** - Create modular directory layout
2. **Start with RAG** - Implement data ingestion first
3. **Add GenAI layer** - Integrate LLM for generation
4. **Build Agentic AI** - Add decision-making and workflow logic
5. **Expose APIs** - Create endpoints for all operations
6. **Integrate automation** - If available, connect to AI automation tools

### Step 5: Validation

Ensure MVP meets all Core Architecture Requirements:

- ✓ Uses only open-source technologies
- ✓ Can deploy on-prem AND cloud
- ✓ All components have API endpoints
- ✓ Modules are independently deployable
- ✓ RAG uses document/file stores
- ✓ Includes GenAI, Agentic AI, RAG
- ✓ Ready for automation integration
- ✓ Completed within 1-2 weeks

## Architecture Design Workflow

For requests focused on architecture planning without immediate implementation:

### Step 1: Understand Use Case

Gather:
- **Problem statement** - What client problem does this solve?
- **User personas** - Who will use this?
- **Scale requirements** - Expected data volume, users, requests
- **Integration points** - Existing systems to connect with

### Step 2: Design Modular Components

Use this template:

```markdown
## Architecture Overview

### Data Layer
- **Document Store**: [Technology choice]
- **File Store**: [Technology choice]
- **Org Tools**: [Integration approach]

### AI Core
- **GenAI**: [Model selection, inference approach]
- **Agentic AI**: [Decision framework, workflow engine]
- **RAG**: [Retrieval strategy, embedding approach]

### API Layer
- **Endpoints**: [List key APIs]
- **Auth**: [Authentication approach]

### Deployment
- **On-Prem**: [Containerization, orchestration]
- **Cloud**: [Cloud-agnostic approach]
```

### Step 3: Document Integration Points

For each external system, specify:
- **Data format** (JSON, CSV, PDFs, etc.)
- **Access method** (File export, API, database query)
- **Update frequency** (Real-time, batch, scheduled)
- **RAG strategy** (How data feeds into retrieval)

### Step 4: Plan for Extensibility

Design with future enhancements in mind:
- **New data sources** - How to add more connectors
- **Advanced integrations** - API hooks for future needs
- **Scaling** - Horizontal scaling approach
- **Monitoring** - Observability and logging

## RAG Implementation Workflow

For requests focused specifically on RAG pipeline setup:

### Step 1: Data Source Assessment

Identify available sources:
1. **Document stores** - Confluence, SharePoint, knowledge bases
2. **File stores** - S3, NAS, shared drives
3. **Organizational tools** - Jira exports, Slack archives, email dumps

### Step 2: Ingestion Pipeline

Design data flow:

```
Data Sources → Extraction → Chunking → Embedding → Vector Store → Retrieval
```

Key decisions:
- **Chunking strategy** - Token limits, overlap, semantic boundaries
- **Embedding model** - Open-source model selection (sentence-transformers, etc.)
- **Vector store** - Technology choice (Milvus, Weaviate, ChromaDB, etc.)

### Step 3: Retrieval Strategy

Choose approach:
- **Semantic search** - Vector similarity
- **Hybrid search** - Combine keyword + semantic
- **Metadata filtering** - Filter by date, source, tags
- **Reranking** - Post-retrieval scoring

See `references/rag-patterns.md` for detailed guidance.

### Step 4: Integration with GenAI

Connect retrieval to generation:
1. **Query processing** - User query → retrieval query
2. **Context assembly** - Retrieved docs → prompt context
3. **Generation** - LLM with grounded context
4. **Citation** - Reference source documents

## Technology Selection Workflow

For requests about which tools to use:

### Step 1: Identify Component Needs

Map user requirements to architecture components:
- **GenAI needs** → LLM inference, model hosting
- **Agentic AI needs** → Workflow orchestration, decision frameworks
- **RAG needs** → Embeddings, vector stores, retrieval
- **Data needs** → Connectors, ETL, storage
- **API needs** → Web frameworks, authentication
- **Deployment needs** → Containerization, orchestration

### Step 2: Apply Selection Criteria

For each component, evaluate:

1. **Open Source** ✓ Required - Must have permissive license
2. **Maturity** - Production-ready, stable releases
3. **Documentation** - Clear guides for 1-2 week timeline
4. **Deployment** - Works on-prem AND cloud
5. **Community** - Active support, maintained
6. **Modularity** - API-driven, composable

### Step 3: Provide Recommendations

See `references/tech-stack.md` for curated open-source tools.

Present options in this format:

```markdown
### [Component Name]

**Recommended**: [Tool name]
- **Why**: [Reason for recommendation]
- **License**: [Open source license]
- **Deployment**: [On-prem and cloud compatibility]

**Alternatives**:
- [Tool 2]: [When to use instead]
- [Tool 3]: [When to use instead]
```

## Best Practices

### Speed Without Sacrificing Quality

- **Use proven patterns** - Don't reinvent architecture
- **Start with core features** - MVP = Minimum Viable Product
- **Leverage templates** - Reuse project structures
- **Automate setup** - Scripts for scaffolding, deployment
- **Parallel development** - Independent modules enable parallel work

### Modular Design Principles

- **Clear interfaces** - API contracts between modules
- **Loose coupling** - Modules communicate via APIs only
- **Independent deployment** - Each module can be updated separately
- **Configuration-driven** - Behavior changes without code changes
- **Testable in isolation** - Unit tests per module

### Deployment Flexibility

- **Containerize everything** - Docker for consistency
- **Externalize configuration** - Environment variables, config files
- **Stateless services** - Data in stores, not application memory
- **Health checks** - Monitoring endpoints for all services
- **Documentation** - Deploy on-prem and cloud instructions

## Common Patterns

### Pattern 1: Document Q&A MVP

**Use case**: Answer questions from company documents

**Stack**:
- **Data**: Document store exports (PDFs, DOCX)
- **RAG**: Embeddings + vector store
- **GenAI**: Open-source LLM (Llama, Mistral)
- **API**: REST endpoints for queries

**Timeline**: 1 week

### Pattern 2: Workflow Automation MVP

**Use case**: Automate decision-making workflows

**Stack**:
- **Data**: Organizational tool exports (tickets, tasks)
- **Agentic AI**: Workflow engine + decision rules
- **GenAI**: LLM for recommendations
- **Automation**: AI automation tool integration

**Timeline**: 1-2 weeks

### Pattern 3: Knowledge Synthesis MVP

**Use case**: Synthesize insights from multiple sources

**Stack**:
- **Data**: Multiple document/file stores
- **RAG**: Multi-source retrieval
- **GenAI**: LLM for synthesis
- **Agentic AI**: Source prioritization logic

**Timeline**: 2 weeks

## Resources

### References

- **tech-stack.md** - Curated open-source tools for each component
- **rag-patterns.md** - Detailed RAG implementation patterns
- **deployment.md** - On-prem and cloud deployment guides
- **integration-patterns.md** - Document store, file store, org tool integration

Load these references when you need detailed guidance on specific topics.

## Validation Checklist

Before completing any MVP or architecture, verify:

- [ ] Uses only open-source technologies
- [ ] Documented deployment for on-prem
- [ ] Documented deployment for cloud
- [ ] All components expose APIs
- [ ] Modules are independently deployable
- [ ] RAG uses document/file stores as specified
- [ ] Includes GenAI component
- [ ] Includes Agentic AI component
- [ ] Includes RAG component
- [ ] AI automation integration points identified
- [ ] Can be delivered within 1-2 weeks
- [ ] Architecture is modular and extensible
