# Databricks Data Engineer - Technical Interview Questions

## Table of Contents
- [Delta Lake & Data Storage](#delta-lake--data-storage)
- [Apache Spark & PySpark](#apache-spark--pyspark)
- [Streaming & Real-Time Processing](#streaming--real-time-processing)
- [Unity Catalog & Governance](#unity-catalog--governance)
- [Performance Optimization](#performance-optimization)
- [Delta Live Tables](#delta-live-tables)
- [Databricks Platform](#databricks-platform)

---

## Delta Lake & Data Storage

### Question 1: Explain how Delta Lake ensures ACID transactions on cloud object storage.

**Answer:**

Delta Lake achieves ACID transactions through its transaction log mechanism:

**Key Components:**
1. **Transaction Log (_delta_log)**
   - JSON files recording every transaction
   - Sequentially numbered (00000.json, 00001.json, etc.)
   - Contains metadata about files added/removed, schema changes, operations

2. **Atomicity via Atomic Put**
   - Leverages cloud storage atomic put operations
   - Transaction commits only when log entry is successfully written
   - If log write fails, transaction is not visible

3. **Optimistic Concurrency Control**
   - Multiple writers can work concurrently
   - At commit time, checks for conflicts
   - Retries with latest state if conflict detected

**Example:**
```python
# Transaction 1: Add new data
df1.write.format("delta").mode("append").save("/data/sales")
# This writes:
# - Parquet files with new data
# - Transaction log entry: {"add": {"path": "part-00000.parquet", "size": 1024, ...}}

# Transaction 2: Concurrent update
spark.sql("UPDATE delta.`/data/sales` SET status = 'processed' WHERE id = 123")
# This writes:
# - New Parquet file with updated record
# - Transaction log entry: {"remove": "old-file.parquet", "add": "new-file.parquet"}

# Delta ensures:
# - Readers see consistent snapshot (read from specific version)
# - Writers don't corrupt each other's work (optimistic concurrency)
# - Failed writes are invisible (atomicity)
```

**Why It Works:**
- Transaction log provides single source of truth
- Cloud storage atomic operations ensure log integrity
- Readers use snapshot isolation (read from specific log version)
- Concurrent writes detected via log version checking

---

### Question 2: What's the difference between OPTIMIZE and VACUUM, and when would you use each?

**Answer:**

**OPTIMIZE:**
- **Purpose**: Compacts small files into larger ones (bin-packing)
- **Target**: ~1GB files for optimal performance
- **When to Use**:
  - After many small writes/appends
  - Before critical production queries
  - Regularly on frequently updated tables

**VACUUM:**
- **Purpose**: Deletes old data files no longer referenced
- **Default Retention**: 7 days
- **When to Use**:
  - To reduce storage costs
  - After major data updates
  - As part of regular maintenance

**Example:**
```python
# Scenario: High-frequency streaming writes create many small files
# Stream has been running for weeks, creating thousands of files

from delta.tables import DeltaTable

# Step 1: Check current file count
file_count = len(dbutils.fs.ls("/data/events/_delta_log"))
print(f"Current files: {file_count}")  # Output: 5847 files

# Step 2: Run OPTIMIZE to compact files
spark.sql("OPTIMIZE delta.`/data/events` ZORDER BY (timestamp, user_id)")
# Creates larger files (~1GB each)
# Old small files still exist (for time travel)

# Step 3: Check files again
new_file_count = len(dbutils.fs.ls("/data/events/_delta_log"))
print(f"After OPTIMIZE: {new_file_count}")  # Still ~5847 (old files remain)

# Step 4: Run VACUUM to remove old files
spark.sql("VACUUM delta.`/data/events` RETAIN 168 HOURS")  # 7 days
# Deletes files older than retention period

# Step 5: Final file count
final_count = len(dbutils.fs.ls("/data/events/_delta_log"))
print(f"After VACUUM: {final_count}")  # Output: 47 files (much cleaner!)

# Cost Impact:
# - Before: 5847 files × 50MB avg = 292GB storage
# - After: 47 files × 1GB avg = 47GB storage (but some data removed)
```

**Best Practices:**
```python
# Schedule OPTIMIZE regularly
spark.sql("""
    OPTIMIZE catalog.schema.large_table
    WHERE date >= current_date() - INTERVAL 7 DAYS
    ZORDER BY (customer_id, order_date)
""")

# VACUUM with caution - ensure retention covers:
# - Long-running queries (they read from old files)
# - Time travel requirements
# - Backup/recovery needs
spark.sql("VACUUM catalog.schema.large_table RETAIN 336 HOURS")  # 14 days for safety
```

**Common Pitfall:**
```python
# DON'T: Vacuum too aggressively
spark.sql("VACUUM delta.`/data/events` RETAIN 0 HOURS")
# This breaks:
# - Concurrent readers (file not found errors)
# - Time travel (versions become unavailable)
# - Recovery capabilities
```

---

### Question 3: Explain Z-Ordering and when it's more effective than partitioning.

**Answer:**

**Z-Ordering:**
- Multi-dimensional clustering technique
- Co-locates related data for multiple columns
- Uses space-filling curves to map multi-dimensional data to one dimension

**When Z-Ordering > Partitioning:**

1. **Multiple Filter Columns with Varying Combinations**
```python
# Scenario: Query patterns vary widely
queries = [
    "SELECT * FROM orders WHERE customer_id = 123",
    "SELECT * FROM orders WHERE order_date = '2024-01-01'",
    "SELECT * FROM orders WHERE status = 'pending' AND region = 'US'",
    "SELECT * FROM orders WHERE customer_id = 456 AND order_date > '2024-01-01'"
]

# Bad Approach: Partitioning
# Can only partition by 1-2 columns effectively
# PARTITIONED BY (order_date)  -- Doesn't help customer_id queries
# PARTITIONED BY (customer_id) -- Creates too many partitions

# Better Approach: Z-Ordering
spark.sql("""
    OPTIMIZE orders
    ZORDER BY (customer_id, order_date, status, region)
""")
# Now all query patterns benefit from data co-location
```

2. **High Cardinality Columns**
```python
# Partitioning by customer_id with 10M customers = 10M partitions ❌
# Z-Ordering on customer_id = Efficient clustering ✓

# Example:
# Without Z-Ordering: Query scans 1000 files
spark.sql("""
    SELECT * FROM orders WHERE customer_id = 'C123456'
""").explain()
# FileScan: 1000 files, 500GB scanned

# With Z-Ordering:
spark.sql("OPTIMIZE orders ZORDER BY (customer_id)")
spark.sql("""
    SELECT * FROM orders WHERE customer_id = 'C123456'
""").explain()
# FileScan: 2 files, 2GB scanned (250x improvement!)
```

3. **Combining Partitioning + Z-Ordering**
```python
# Best of both worlds for time-series data

# Create partitioned table
spark.sql("""
    CREATE TABLE events (
        event_id STRING,
        user_id STRING,
        event_type STRING,
        timestamp TIMESTAMP,
        date DATE
    )
    USING DELTA
    PARTITIONED BY (date)
""")

# Z-Order within each partition
spark.sql("""
    OPTIMIZE events
    WHERE date >= '2024-01-01'
    ZORDER BY (user_id, event_type)
""")

# Query benefits:
# - Partition pruning eliminates entire date ranges
# - Z-Ordering optimizes within selected partitions
query = """
    SELECT * FROM events
    WHERE date BETWEEN '2024-01-01' AND '2024-01-31'
      AND user_id = 'U12345'
      AND event_type = 'purchase'
"""
# Result: Only scans relevant partition + Z-Order skips irrelevant files
```

**Real-World Example:**
```python
# E-commerce orders table: 10TB, 100M orders/day
# Query patterns:
# - Customer lookup: WHERE customer_id = ?
# - Date range: WHERE order_date BETWEEN ? AND ?
# - Status tracking: WHERE status = ? AND updated_at > ?
# - Regional analysis: WHERE region = ? AND category = ?

# Solution:
spark.sql("""
    CREATE TABLE orders (
        order_id STRING,
        customer_id STRING,
        order_date DATE,
        status STRING,
        region STRING,
        category STRING,
        amount DECIMAL(10,2),
        updated_at TIMESTAMP
    )
    USING DELTA
    PARTITIONED BY (order_date)  -- Time-based partition (reasonable cardinality)
""")

# Within each date partition, Z-Order by query columns
spark.sql("""
    OPTIMIZE orders
    ZORDER BY (customer_id, status, region, category)
""")

# Performance Results:
# Before: Avg query time 45 seconds, 2TB scanned
# After: Avg query time 3 seconds, 50GB scanned (97% reduction!)
```

**When NOT to Use Z-Ordering:**
- Single column queries only → Use simple sorting
- Very small tables (<1GB) → Overhead not worth it
- Columns with very low cardinality (2-3 values) → Partitioning sufficient

---

## Apache Spark & PySpark

### Question 4: Explain the difference between transformations and actions in Spark. Why does this matter?

**Answer:**

**Transformations (Lazy):**
- Create new DataFrames/RDDs from existing ones
- Not executed immediately
- Build execution plan (DAG)
- Examples: select, filter, join, groupBy

**Actions (Eager):**
- Trigger execution of transformations
- Return results to driver or write to storage
- Examples: show, count, collect, write

**Why It Matters:**

1. **Query Optimization**
```python
# Without lazy evaluation
df = spark.read.parquet("large_data/")
df = df.filter(col("country") == "US")      # Would execute immediately
df = df.select("id", "name", "amount")      # Would execute again
df = df.filter(col("amount") > 1000)        # Would execute again
result = df.collect()                       # Would execute again
# Total: 4 full scans! 😱

# With lazy evaluation (actual Spark)
df = spark.read.parquet("large_data/")
df = df.filter(col("country") == "US")      # ✓ Recorded in plan
df = df.select("id", "name", "amount")      # ✓ Recorded in plan
df = df.filter(col("amount") > 1000)        # ✓ Recorded in plan
result = df.collect()                       # ✓ EXECUTES ONCE with optimized plan
# Total: 1 optimized scan! 🚀
```

2. **Predicate Pushdown**
```python
# Lazy evaluation enables automatic optimization
df = spark.read.parquet("sales/")  # 100 columns, 10TB
filtered_df = df.filter(col("year") == 2024) \
                .filter(col("region") == "US") \
                .select("order_id", "amount")

# Spark optimizes to:
# 1. Read only 'year', 'region', 'order_id', 'amount' columns (column pruning)
# 2. Apply filters during file read (predicate pushdown)
# 3. Skip partitions/files that don't match (partition pruning)

# Without lazy eval, would read all 100 columns, then filter 🐌
```

3. **Avoiding Common Mistakes**
```python
# MISTAKE 1: Using collect() too early
def process_data(df):
    data = df.collect()  # ❌ Brings ALL data to driver
    result = []
    for row in data:
        if row.amount > 1000:
            result.append(row)
    return result  # Driver OOM on large data!

# CORRECT: Keep using transformations
def process_data(df):
    return df.filter(col("amount") > 1000)  # ✓ Distributed processing

# MISTAKE 2: Multiple actions = multiple executions
df = spark.read.parquet("data/")
count = df.count()          # Action 1: Full scan
max_val = df.agg(max("val")).collect()  # Action 2: Full scan again!

# CORRECT: Cache when multiple actions needed
df = spark.read.parquet("data/").cache()
count = df.count()          # Action 1: Full scan + cache
max_val = df.agg(max("val")).collect()  # Action 2: Reads from cache ✓
```

4. **Debugging Tips**
```python
# See execution plan without triggering execution
df.explain(True)  # Shows optimized plan

# Example:
df = spark.read.parquet("sales/") \
    .filter(col("amount") > 1000) \
    .groupBy("category").agg(sum("amount"))

df.explain()
# == Physical Plan ==
# *(2) HashAggregate(keys=[category#123], functions=[sum(amount#124)])
# +- Exchange hashpartitioning(category#123, 200)
#    +- *(1) HashAggregate(keys=[category#123], functions=[partial_sum(amount#124)])
#       +- *(1) Filter (amount#124 > 1000.0)
#          +- *(1) FileScan parquet [category#123,amount#124]
#             PushedFilters: [IsNotNull(amount), GreaterThan(amount,1000.0)]
```

---

### Question 5: How would you handle data skew in a Spark join operation?

**Answer:**

**Data Skew Symptoms:**
- Few tasks run much longer than others
- Executor OOM errors on specific tasks
- 90% of tasks finish in 1 minute, 5 tasks take 30 minutes

**Solutions:**

**1. Salting (for skewed join keys)**
```python
# Problem: Customer 'C001' has 10M orders, others have ~100
orders = spark.read.table("orders")  # 100M rows
customers = spark.read.table("customers")  # 1M rows

# Skewed join (one executor handles 10M rows)
result = orders.join(customers, "customer_id")  # ❌ Slow!

# Solution: Salt the skewed key
from pyspark.sql.functions import concat, lit, rand, floor

# Add salt to large table (orders)
salt_count = 10
orders_salted = orders.withColumn(
    "salt", floor(rand() * salt_count).cast("int")
).withColumn(
    "salted_key", concat(col("customer_id"), lit("_"), col("salt"))
)

# Replicate small table (customers) with all salt values
from pyspark.sql.functions import explode, array, lit

customers_replicated = customers.withColumn(
    "salt", explode(array([lit(i) for i in range(salt_count)]))
).withColumn(
    "salted_key", concat(col("customer_id"), lit("_"), col("salt"))
)

# Join on salted key
result = orders_salted.join(customers_replicated, "salted_key") \
    .drop("salt", "salted_key")
# ✓ Distributes C001's 10M rows across 10 tasks
```

**2. Broadcast Join (for small skewed table)**
```python
# When one table is small (<10GB), broadcast it
from pyspark.sql.functions import broadcast

large_orders = spark.read.table("orders")  # 1TB
small_customers = spark.read.table("customers")  # 500MB

# Broadcast small table to all executors
result = large_orders.join(
    broadcast(small_customers),
    "customer_id"
)
# ✓ No shuffle, no skew issues
```

**3. Adaptive Query Execution (AQE) - Skew Join Optimization**
```python
# Enable AQE (Databricks enables by default)
spark.conf.set("spark.sql.adaptive.enabled", "true")
spark.conf.set("spark.sql.adaptive.skewJoin.enabled", "true")
spark.conf.set("spark.sql.adaptive.skewJoin.skewedPartitionThresholdInBytes", "256MB")

# Spark automatically detects and splits skewed partitions
result = orders.join(customers, "customer_id")
# Spark sees partition with C001 is huge, splits it automatically
```

**4. Isolate and Handle Skewed Keys Separately**
```python
# Identify skewed keys
skewed_keys = orders.groupBy("customer_id") \
    .count() \
    .filter(col("count") > 100000) \
    .select("customer_id")

# Process normal keys
normal_orders = orders.join(
    skewed_keys, "customer_id", "left_anti"
)
normal_result = normal_orders.join(customers, "customer_id")

# Process skewed keys differently (broadcast or special handling)
skewed_orders = orders.join(skewed_keys, "customer_id", "left_semi")
skewed_result = skewed_orders.join(
    broadcast(customers), "customer_id"
)

# Combine results
final_result = normal_result.union(skewed_result)
```

**5. Increase Partitions**
```python
# If skew is moderate, sometimes just need more partitions
spark.conf.set("spark.sql.shuffle.partitions", "1000")  # Default 200

result = orders.join(customers, "customer_id")
# More partitions = smaller chance of extreme skew
```

**Real-World Example:**
```python
# E-commerce: Joining 1B events with user profiles
# User 'bot_123' generated 100M events (DDoS), normal users: 10-100 events

events = spark.read.table("events")  # 1B rows
users = spark.read.table("users")    # 10M rows

# Detect skew
user_counts = events.groupBy("user_id").count()
user_counts.describe("count").show()
# count: mean=100, max=100000000 😱 Massive skew!

# Solution: Filter bots + salt + AQE
bots = user_counts.filter(col("count") > 10000).select("user_id")

# Process legitimate users normally
legit_events = events.join(bots, "user_id", "left_anti")
legit_result = legit_events.join(broadcast(users), "user_id")

# Handle bot events separately (maybe just count them)
bot_events = events.join(bots, "user_id", "left_semi")
bot_summary = bot_events.groupBy("user_id").agg(
    count("*").alias("event_count"),
    countDistinct("event_type").alias("unique_events")
)

# Final result
final = legit_result.union(
    bot_summary.join(users, "user_id")
)
```

---

## Streaming & Real-Time Processing

### Question 6: Explain the difference between Auto Loader and COPY INTO. When would you use each?

**Answer:**

**Auto Loader (cloudFiles):**
- Structured Streaming-based
- Continuous or triggered processing
- Uses file notification services (SQS, Event Grid) or directory listing
- Maintains state in checkpoints
- Schema inference and evolution

**COPY INTO:**
- SQL-based batch operation
- Idempotent (safe to re-run)
- Tracks loaded files in table metadata
- Simpler for scheduled batch loads

**Comparison:**

| Feature | Auto Loader | COPY INTO |
|---------|-------------|-----------|
| **Execution Model** | Streaming (continuous) | Batch (on-demand) |
| **Scalability** | Millions of files | Thousands of files |
| **Schema Evolution** | Automatic with rescue mode | Manual schema updates |
| **Infrastructure** | Checkpoints + (optional) notifications | Table metadata |
| **Use Case** | Continuous CDC, real-time | Scheduled ETL, ad-hoc loads |
| **Complexity** | Higher (streaming concepts) | Lower (simple SQL) |

**When to Use Auto Loader:**

1. **Continuous Data Ingestion**
```python
# Scenario: Files arrive continuously (CDC from source system)
# New files every few seconds/minutes

# Auto Loader: Perfect fit
df = spark.readStream \
    .format("cloudFiles") \
    .option("cloudFiles.format", "json") \
    .option("cloudFiles.schemaLocation", "/checkpoints/schema") \
    .option("cloudFiles.inferColumnTypes", "true") \
    .load("s3://bucket/incoming/")

df.writeStream \
    .format("delta") \
    .option("checkpointLocation", "/checkpoints/data") \
    .trigger(processingTime="1 minute") \
    .table("bronze.raw_events")

# Benefits:
# - Exactly-once processing guaranteed
# - Automatic handling of new files
# - Scales to millions of files
# - Schema evolution handled automatically
```

2. **Large-Scale File Tracking**
```python
# Scenario: S3 bucket with 10M+ files
# Adding 100K files per day

# Auto Loader with file notifications
df = spark.readStream \
    .format("cloudFiles") \
    .option("cloudFiles.format", "parquet") \
    .option("cloudFiles.useNotifications", "true") \
    .option("cloudFiles.queueUrl", "https://sqs.us-east-1.amazonaws.com/xxx") \
    .load("s3://massive-bucket/data/")

# Uses SQS notifications instead of listing directory
# Scales to billions of files efficiently
```

**When to Use COPY INTO:**

1. **Scheduled Batch Loads**
```sql
-- Scenario: Daily ETL job loads yesterday's data
-- Files arrive once per day in organized batches

-- COPY INTO: Simple and effective
COPY INTO catalog.schema.daily_sales
FROM 's3://bucket/sales/2024-01-15/'
FILEFORMAT = PARQUET
FORMAT_OPTIONS ('mergeSchema' = 'true')
COPY_OPTIONS ('mergeSchema' = 'true');

-- Benefits:
-- - Simple SQL (no streaming concepts)
-- - Idempotent (can rerun if job fails)
-- - Automatic file tracking
-- - Easy to schedule in workflows
```

2. **Backfill Historical Data**
```sql
-- Scenario: One-time load of historical files
-- 10,000 files need to be loaded

-- COPY INTO with pattern matching
COPY INTO catalog.schema.historical_orders
FROM (
  SELECT
    *,
    _metadata.file_path as source_file,
    current_timestamp() as loaded_at
  FROM 's3://bucket/historical/2023/*/*.parquet'
)
FILEFORMAT = PARQUET;

-- Simple, no streaming infrastructure needed
```

**Hybrid Approach:**
```python
# Use both in same pipeline!

# 1. Backfill historical with COPY INTO
spark.sql("""
    COPY INTO bronze.transactions
    FROM 's3://data/transactions/historical/'
    FILEFORMAT = JSON
""")

# 2. Stream new data with Auto Loader
df = spark.readStream \
    .format("cloudFiles") \
    .option("cloudFiles.format", "json") \
    .load("s3://data/transactions/streaming/")

query = df.writeStream \
    .format("delta") \
    .option("checkpointLocation", "/checkpoints/transactions") \
    .option("mergeSchema", "true") \
    .trigger(availableNow=True) \
    .table("bronze.transactions")

query.awaitTermination()
```

**Real Decision Tree:**
```python
# Choose Auto Loader if:
# - Files arrive continuously (< 1 hour intervals)
# - Need exactly-once guarantees
# - Handling millions of files
# - Schema changes frequently
# - Need complex transformations during load

# Choose COPY INTO if:
# - Batch loads (daily/hourly)
# - Small to medium file counts (< 100K)
# - Prefer SQL over streaming code
# - Simple schema
# - Team unfamiliar with streaming concepts
```

---

### Question 7: How do you implement exactly-once processing in Structured Streaming?

**Answer:**

**Exactly-Once Processing Requirements:**
1. Idempotent sources (can re-read without duplicates)
2. Checkpoint location for offset tracking
3. Idempotent sinks or transactional writes

**Implementation:**

**1. Checkpoint Configuration**
```python
# Checkpoint stores:
# - Source offsets (what's been processed)
# - State store (for aggregations)
# - Metadata (query configuration)

query = df.writeStream \
    .format("delta") \
    .option("checkpointLocation", "/mnt/checkpoints/my_stream") \
    .trigger(processingTime="1 minute") \
    .table("output_table")

# After failure/restart:
# - Reads checkpoint to determine last processed offset
# - Resumes from exact position
# - Reprocesses any incomplete micro-batch
```

**2. Source-Specific Exactly-Once**

**Kafka:**
```python
# Kafka offsets tracked in checkpoint
df = spark.readStream \
    .format("kafka") \
    .option("kafka.bootstrap.servers", "localhost:9092") \
    .option("subscribe", "orders") \
    .option("startingOffsets", "earliest") \
    .load()

parsed_df = df.select(
    col("key").cast("string"),
    from_json(col("value").cast("string"), schema).alias("data")
).select("data.*")

# Write with checkpoint
query = parsed_df.writeStream \
    .format("delta") \
    .option("checkpointLocation", "/checkpoints/kafka_orders") \
    .outputMode("append") \
    .table("bronze.orders")

# Exactly-once guarantee:
# - Kafka offset committed only after Delta write succeeds
# - If failure occurs, restarts from last committed offset
# - Delta's transactional write ensures atomicity
```

**Auto Loader:**
```python
# Tracks processed files in checkpoint
df = spark.readStream \
    .format("cloudFiles") \
    .option("cloudFiles.format", "json") \
    .option("cloudFiles.schemaLocation", "/checkpoints/schema") \
    .load("/mnt/data/")

query = df.writeStream \
    .format("delta") \
    .option("checkpointLocation", "/checkpoints/data") \
    .table("bronze.events")

# Exactly-once guarantee:
# - Files marked as processed only after write succeeds
# - Duplicate file arrival handled (same file not processed twice)
# - Checkpoint tracks: file list, schema, processing state
```

**3. Sink-Specific Guarantees**

**Delta Lake (Idempotent):**
```python
# Delta handles duplicates via transaction log
query = df.writeStream \
    .format("delta") \
    .option("checkpointLocation", "/checkpoints/output") \
    .outputMode("append") \
    .table("final_table")

# How Delta ensures exactly-once:
# 1. Micro-batch assigned unique ID
# 2. Delta checks if batch ID already processed
# 3. If yes, skips write (idempotent)
# 4. If no, writes atomically via transaction log
```

**foreachBatch for Custom Logic:**
```python
def write_to_multiple_sinks(batch_df, batch_id):
    # Use batch_id to ensure idempotency

    # Check if batch already processed
    processed = spark.sql(f"""
        SELECT 1 FROM metadata.processed_batches
        WHERE batch_id = {batch_id}
    """)

    if not processed.isEmpty():
        print(f"Batch {batch_id} already processed, skipping")
        return

    # Write to Delta (atomic)
    batch_df.write.format("delta").mode("append").save("/data/output")

    # Write to external system (make idempotent)
    send_to_api(batch_df, batch_id)  # Use batch_id as dedup key

    # Record batch as processed
    spark.sql(f"""
        INSERT INTO metadata.processed_batches VALUES ({batch_id}, current_timestamp())
    """)

query = df.writeStream \
    .foreachBatch(write_to_multiple_sinks) \
    .option("checkpointLocation", "/checkpoints/custom") \
    .start()
```

**4. Handling Failures and Recovery**
```python
# Scenario: Stream crashes mid-processing

# Initial run
query = spark.readStream \
    .format("cloudFiles") \
    .option("cloudFiles.format", "json") \
    .load("/data/input") \
    .writeStream \
    .format("delta") \
    .option("checkpointLocation", "/checkpoints/app") \
    .table("output")

# Checkpoint state at failure:
# {
#   "files_processed": ["file1.json", "file2.json"],
#   "current_batch": 3,
#   "offsets": {...}
# }

# After restart with SAME checkpoint location:
query = spark.readStream \
    .format("cloudFiles") \
    .option("cloudFiles.format", "json") \
    .load("/data/input") \
    .writeStream \
    .format("delta") \
    .option("checkpointLocation", "/checkpoints/app")  # Same location!
    .table("output")

# Recovery process:
# 1. Reads checkpoint state
# 2. Skips file1.json, file2.json (already processed)
# 3. Reprocesses batch 3 (was incomplete)
# 4. Continues with file3.json, file4.json...
# Result: Exactly-once, no duplicates or lost data
```

**5. Common Pitfalls**

**WRONG: No Checkpoint**
```python
# ❌ At-least-once (can have duplicates)
df.writeStream \
    .format("delta") \
    .table("output")  # No checkpoint!

# Problem: After failure, restarts from beginning or arbitrary point
```

**WRONG: Changing Checkpoint Location**
```python
# ❌ Loses processing state
# Initial
query = df.writeStream.option("checkpointLocation", "/checkpoint1").table("out")

# After failure (different location)
query = df.writeStream.option("checkpointLocation", "/checkpoint2").table("out")
# Starts from scratch, creates duplicates!
```

**CORRECT: Proper Checkpointing**
```python
# ✓ Exactly-once guaranteed
CHECKPOINT_LOCATION = "/mnt/checkpoints/production_stream"

query = spark.readStream \
    .format("cloudFiles") \
    .option("cloudFiles.format", "json") \
    .option("cloudFiles.schemaLocation", f"{CHECKPOINT_LOCATION}/schema") \
    .load("/data/input") \
    .writeStream \
    .format("delta") \
    .option("checkpointLocation", f"{CHECKPOINT_LOCATION}/data") \
    .trigger(processingTime="30 seconds") \
    .table("output_table")

# Best practices:
# - Use consistent checkpoint location
# - Include checkpoint in backups
# - Monitor checkpoint health
# - Never delete checkpoint while stream is running
```

**6. Testing Exactly-Once**
```python
# Simulate failure and verify no duplicates
import time

# Start stream
query = df.writeStream \
    .format("delta") \
    .option("checkpointLocation", "/test/checkpoint") \
    .table("test_output")

# Let it process some data
time.sleep(60)

# Stop (simulate crash)
query.stop()

# Count records
initial_count = spark.table("test_output").count()

# Restart with same checkpoint
query = df.writeStream \
    .format("delta") \
    .option("checkpointLocation", "/test/checkpoint") \
    .table("test_output")

time.sleep(60)
query.stop()

# Verify no duplicates
final_count = spark.table("test_output").count()
assert final_count >= initial_count, "Lost data!"

# Check for duplicates based on business key
duplicates = spark.sql("""
    SELECT order_id, COUNT(*) as cnt
    FROM test_output
    GROUP BY order_id
    HAVING cnt > 1
""")
assert duplicates.count() == 0, "Duplicates found!"
```

---

## Unity Catalog & Governance

### Question 8: Explain Unity Catalog's three-level namespace and how it differs from Hive metastore.

**Answer:**

**Unity Catalog Three-Level Namespace:**
```
metastore → catalog → schema → table/view/function
```

**Structure:**
```python
# Fully qualified name
catalog.schema.table

# Example
production.sales.orders
```

**1. Metastore (Top Container)**
```sql
-- One metastore per region/organization
-- Contains multiple catalogs
-- Centralized governance across workspaces

-- Example: Company has one Unity Catalog metastore
-- Multiple teams share it
```

**2. Catalog (Environment/Domain)**
```sql
-- Logical grouping of schemas
-- Often maps to: environments (dev/staging/prod) or domains (sales/marketing)

CREATE CATALOG IF NOT EXISTS production;
CREATE CATALOG IF NOT EXISTS development;
CREATE CATALOG IF NOT EXISTS analytics;

-- Team isolation
GRANT USE CATALOG production TO `data-engineers`;
GRANT USE CATALOG development TO `all-users`;
```

**3. Schema (Database)**
```sql
-- Logical grouping of tables
-- Similar to traditional database schema

CREATE SCHEMA IF NOT EXISTS production.sales;
CREATE SCHEMA IF NOT EXISTS production.inventory;

-- Access control
GRANT SELECT ON SCHEMA production.sales TO `sales-team`;
```

**Comparison with Hive Metastore:**

| Feature | Hive Metastore | Unity Catalog |
|---------|---------------|---------------|
| **Namespace** | database.table | catalog.schema.table |
| **Scope** | Single workspace | Cross-workspace |
| **Governance** | Table ACLs | Fine-grained (row/column) |
| **Lineage** | Manual | Automatic |
| **Data Sharing** | Complex | Delta Sharing built-in |
| **Isolation** | Workspace-level | Catalog-level |

**Real-World Example:**

**Before (Hive Metastore):**
```sql
-- Workspace 1 (Engineering)
CREATE DATABASE sales;
CREATE TABLE sales.orders (...);  -- Only visible in this workspace

-- Workspace 2 (Analytics)
CREATE DATABASE sales;  -- Separate, unrelated database
CREATE TABLE sales.orders (...);  -- Different table, same name

-- Problems:
-- ❌ No centralized governance
-- ❌ Duplicate effort
-- ❌ Data silos
-- ❌ No unified access control
```

**After (Unity Catalog):**
```sql
-- Single metastore shared across all workspaces

-- Workspace 1 (Engineering)
CREATE CATALOG production;
CREATE SCHEMA production.sales;
CREATE TABLE production.sales.orders (...);

-- Workspace 2 (Analytics)
SELECT * FROM production.sales.orders;  -- ✓ Same table!

-- Benefits:
-- ✓ Single source of truth
-- ✓ Centralized governance
-- ✓ Automatic lineage across workspaces
-- ✓ Unified access control
```

**Governance Example:**
```sql
-- Catalog-level isolation
CREATE CATALOG production;
CREATE CATALOG staging;

-- Different teams, different access
GRANT USE CATALOG production TO `analysts`;
GRANT SELECT ON production.sales.orders TO `analysts`;

GRANT ALL PRIVILEGES ON CATALOG staging TO `engineers`;

-- Row-level security
CREATE FUNCTION production.mask_pii(user_region STRING, data_region STRING)
RETURNS BOOLEAN
RETURN user_region = data_region OR is_member('admins');

ALTER TABLE production.sales.customers SET ROW FILTER
  mask_pii(current_user_region(), region) ON (region);

-- Column masking
CREATE FUNCTION production.mask_ssn(ssn STRING)
RETURNS STRING
RETURN CASE
  WHEN is_member('compliance') THEN ssn
  ELSE 'XXX-XX-' || right(ssn, 4)
END;

ALTER TABLE production.sales.customers
ALTER COLUMN ssn SET MASK production.mask_ssn;
```

**Migration Example:**
```python
# Migrate from Hive to Unity Catalog

# Old Hive table
spark.sql("CREATE DATABASE IF NOT EXISTS sales")
spark.sql("""
    CREATE TABLE sales.orders
    USING DELTA
    LOCATION '/mnt/data/sales/orders'
""")

# New Unity Catalog table
spark.sql("CREATE CATALOG IF NOT EXISTS production")
spark.sql("CREATE SCHEMA IF NOT EXISTS production.sales")

# Approach 1: Deep clone (copies data)
spark.sql("""
    CREATE TABLE production.sales.orders
    DEEP CLONE sales.orders
""")

# Approach 2: External table (links to existing data)
spark.sql("""
    CREATE TABLE production.sales.orders
    USING DELTA
    LOCATION '/mnt/data/sales/orders'
""")

# Approach 3: Sync for compatibility
spark.sql("""
    SYNC TABLE production.sales.orders
    FROM sales.orders
""")
```

---

This is the first comprehensive document covering core technical areas. Let me continue with the remaining interview materials.
