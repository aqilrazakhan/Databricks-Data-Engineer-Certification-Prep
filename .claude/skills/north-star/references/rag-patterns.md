# RAG Implementation Patterns

Detailed patterns for implementing Retrieval-Augmented Generation systems.

## Table of Contents

1. Data Ingestion Patterns
2. Chunking Strategies
3. Embedding Approaches
4. Retrieval Strategies
5. Context Assembly
6. Citation Patterns

## 1. Data Ingestion Patterns

### Pattern: Batch Processing

**When to use**: Initial data load, scheduled updates

```python
# Pseudocode
def batch_ingest(source_directory):
    documents = load_documents(source_directory)
    for doc in documents:
        chunks = chunk_document(doc)
        embeddings = embed_chunks(chunks)
        store_in_vector_db(embeddings, chunks, metadata)
```

**Pros**: Simple, complete control
**Cons**: Not real-time

### Pattern: Incremental Updates

**When to use**: Continuous data sources, real-time updates

```python
# Pseudocode
def incremental_ingest(changed_files):
    for file in changed_files:
        # Remove old version
        delete_from_vector_db(file_id)
        # Add new version
        chunks = chunk_document(file)
        embeddings = embed_chunks(chunks)
        store_in_vector_db(embeddings, chunks, metadata)
```

**Pros**: Always current
**Cons**: More complex, needs change detection

### Pattern: Streaming

**When to use**: High-volume, continuous data feeds

```python
# Pseudocode
def streaming_ingest(data_stream):
    for data_batch in stream_batches(data_stream, batch_size=100):
        process_and_store(data_batch)
```

**Pros**: Handles large volumes
**Cons**: Complex error handling

## 2. Chunking Strategies

### Strategy: Fixed Token Count

**When to use**: Simple MVP, uniform content

```python
chunk_size = 512  # tokens
chunk_overlap = 50  # tokens

def chunk_by_tokens(text):
    tokens = tokenize(text)
    for i in range(0, len(tokens), chunk_size - chunk_overlap):
        yield tokens[i:i + chunk_size]
```

**Pros**: Predictable chunk sizes, fits embedding limits
**Cons**: May split semantically-related content

### Strategy: Semantic Chunking

**When to use**: Narrative content, better quality needed

```python
def chunk_semantically(text):
    paragraphs = split_by_paragraphs(text)
    chunks = []
    current_chunk = []
    current_length = 0

    for para in paragraphs:
        para_length = count_tokens(para)
        if current_length + para_length > max_chunk_size:
            chunks.append(join(current_chunk))
            current_chunk = [para]
            current_length = para_length
        else:
            current_chunk.append(para)
            current_length += para_length

    return chunks
```

**Pros**: Preserves semantic boundaries
**Cons**: Variable chunk sizes

### Strategy: Hierarchical Chunking

**When to use**: Structured documents, context preservation

```python
def hierarchical_chunk(document):
    # Store both small chunks and their parent context
    sections = split_by_sections(document)
    for section in sections:
        # Store section-level chunk
        store_chunk(section, level="section")
        # Store paragraph-level chunks with section reference
        for paragraph in split_paragraphs(section):
            store_chunk(paragraph, level="paragraph", parent=section.id)
```

**Pros**: Multiple granularities, better context
**Cons**: More storage, complex retrieval

## 3. Embedding Approaches

### Approach: Single Embedding Model

**When to use**: MVP, uniform content types

```python
from sentence_transformers import SentenceTransformer

model = SentenceTransformer('all-MiniLM-L6-v2')

def embed_chunks(chunks):
    return model.encode(chunks)
```

**Pros**: Simple, fast
**Cons**: May not be optimal for all content types

### Approach: Domain-Specific Models

**When to use**: Specialized content (code, medical, legal, etc.)

```python
models = {
    'code': SentenceTransformer('sentence-transformers/all-mpnet-base-v2'),
    'general': SentenceTransformer('all-MiniLM-L6-v2')
}

def embed_chunks(chunks, content_type):
    model = models.get(content_type, models['general'])
    return model.encode(chunks)
```

**Pros**: Better quality for specific domains
**Cons**: More complex, multiple models to manage

### Approach: Query + Document Models

**When to use**: Asymmetric search (short query, long documents)

```python
query_model = SentenceTransformer('multi-qa-MiniLM-L6-cos-v1')
doc_model = SentenceTransformer('multi-qa-MiniLM-L6-cos-v1')

def embed_query(query):
    return query_model.encode(query)

def embed_documents(docs):
    return doc_model.encode(docs)
```

**Pros**: Optimized for query-document matching
**Cons**: Slightly more complex

## 4. Retrieval Strategies

### Strategy: Semantic Search Only

**When to use**: MVP, well-defined queries

```python
def retrieve(query, top_k=5):
    query_embedding = embed_query(query)
    results = vector_db.search(query_embedding, top_k=top_k)
    return results
```

**Pros**: Simple, works well for semantic matching
**Cons**: May miss exact keyword matches

### Strategy: Hybrid Search (Semantic + Keyword)

**When to use**: Better recall, mix of semantic and exact matches

```python
def hybrid_retrieve(query, top_k=5, semantic_weight=0.7):
    # Semantic search
    semantic_results = semantic_search(query, top_k=top_k*2)
    # Keyword search (BM25)
    keyword_results = keyword_search(query, top_k=top_k*2)
    # Combine and rerank
    combined = merge_and_rerank(
        semantic_results,
        keyword_results,
        semantic_weight=semantic_weight
    )
    return combined[:top_k]
```

**Pros**: Better recall, combines benefits
**Cons**: More complex, requires keyword index

### Strategy: Filtered Retrieval

**When to use**: Multi-tenant, time-sensitive, categorized data

```python
def filtered_retrieve(query, filters, top_k=5):
    query_embedding = embed_query(query)
    results = vector_db.search(
        query_embedding,
        top_k=top_k,
        filters={
            'date': {'$gte': filters['start_date']},
            'source': {'$in': filters['allowed_sources']},
            'user_id': filters['user_id']
        }
    )
    return results
```

**Pros**: Access control, relevance
**Cons**: Requires metadata management

### Strategy: Two-Stage Retrieval

**When to use**: Large document sets, quality > speed

```python
def two_stage_retrieve(query, top_k=5):
    # Stage 1: Fast retrieval of many candidates
    candidates = vector_db.search(query_embedding, top_k=top_k*10)

    # Stage 2: Rerank with cross-encoder or LLM
    reranked = rerank_with_cross_encoder(query, candidates)

    return reranked[:top_k]
```

**Pros**: Higher quality results
**Cons**: Slower, more compute

## 5. Context Assembly

### Pattern: Simple Concatenation

**When to use**: MVP, simple queries

```python
def assemble_context(query, retrieved_chunks):
    context = "\n\n".join([chunk['text'] for chunk in retrieved_chunks])
    prompt = f"""Answer the question based on the context below.

Context:
{context}

Question: {query}

Answer:"""
    return prompt
```

**Pros**: Simple, works well
**Cons**: No deduplication, may exceed token limits

### Pattern: Token-Aware Assembly

**When to use**: Token limits, long documents

```python
def assemble_context_token_aware(query, retrieved_chunks, max_tokens=2000):
    context_parts = []
    total_tokens = 0

    for chunk in retrieved_chunks:
        chunk_tokens = count_tokens(chunk['text'])
        if total_tokens + chunk_tokens <= max_tokens:
            context_parts.append(chunk['text'])
            total_tokens += chunk_tokens
        else:
            break

    return build_prompt(query, context_parts)
```

**Pros**: Respects limits, predictable
**Cons**: May cut off relevant context

### Pattern: Hierarchical Context

**When to use**: Structured documents, better understanding

```python
def assemble_hierarchical_context(query, retrieved_chunks):
    # Group chunks by document/section
    grouped = group_by_source(retrieved_chunks)

    context = ""
    for source, chunks in grouped.items():
        context += f"\n\n=== {source} ===\n"
        context += "\n".join([c['text'] for c in chunks])

    return build_prompt(query, context)
```

**Pros**: Better structure, preserves document context
**Cons**: More complex formatting

## 6. Citation Patterns

### Pattern: Chunk-Level Citations

**When to use**: Transparency, verification

```python
def generate_with_citations(query, retrieved_chunks):
    # Build context with IDs
    context_parts = []
    for i, chunk in enumerate(retrieved_chunks):
        context_parts.append(f"[{i+1}] {chunk['text']}")

    prompt = f"""Answer using the numbered sources. Cite sources as [1], [2], etc.

Context:
{chr(10).join(context_parts)}

Question: {query}
Answer with citations:"""

    response = llm.generate(prompt)

    # Return response + source metadata
    return {
        'answer': response,
        'sources': [chunk['metadata'] for chunk in retrieved_chunks]
    }
```

**Pros**: Clear attribution
**Cons**: LLM must follow citation format

### Pattern: Document-Level Citations

**When to use**: Simpler, document-focused

```python
def generate_with_doc_citations(query, retrieved_chunks):
    # Group by document
    docs = group_by_document(retrieved_chunks)

    prompt = build_prompt_with_doc_references(query, docs)
    response = llm.generate(prompt)

    return {
        'answer': response,
        'documents_used': [doc['title'] for doc in docs]
    }
```

**Pros**: Simpler for users
**Cons**: Less precise attribution

## Recommended Pattern for MVP

For 1-2 week MVP, start with:

1. **Ingestion**: Batch processing
2. **Chunking**: Fixed token count (512 tokens, 50 overlap)
3. **Embedding**: Single model (all-MiniLM-L6-v2)
4. **Retrieval**: Semantic search only (top_k=5)
5. **Context**: Token-aware assembly
6. **Citation**: Chunk-level citations

This provides good quality with minimal complexity. Iterate based on real usage.

## Performance Optimization

### Caching

Cache embeddings for common queries:

```python
from functools import lru_cache

@lru_cache(maxsize=1000)
def embed_query_cached(query: str):
    return embed_query(query)
```

### Batch Processing

Process multiple chunks at once:

```python
# Batch embed instead of one-by-one
embeddings = model.encode(chunks, batch_size=32)
```

### Async Operations

Use async for I/O operations:

```python
async def retrieve_async(query):
    embedding = await embed_query_async(query)
    results = await vector_db.search_async(embedding)
    return results
```

## Quality Evaluation

Measure RAG quality:

1. **Retrieval Precision**: Are retrieved chunks relevant?
2. **Retrieval Recall**: Are all relevant chunks retrieved?
3. **Answer Quality**: Is generated answer accurate?
4. **Citation Accuracy**: Do citations match usage?

Simple evaluation:

```python
def evaluate_retrieval(query, expected_chunks, retrieved_chunks):
    relevant_retrieved = set(retrieved_chunks) & set(expected_chunks)
    precision = len(relevant_retrieved) / len(retrieved_chunks)
    recall = len(relevant_retrieved) / len(expected_chunks)
    return precision, recall
```
