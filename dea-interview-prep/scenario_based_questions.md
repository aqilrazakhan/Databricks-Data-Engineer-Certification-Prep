# Databricks Data Engineer - Scenario-Based Interview Questions

## Table of Contents
- [Real-Time Data Pipeline Scenarios](#real-time-data-pipeline-scenarios)
- [Performance & Optimization Scenarios](#performance--optimization-scenarios)
- [Data Quality & Recovery Scenarios](#data-quality--recovery-scenarios)
- [Migration & Modernization Scenarios](#migration--modernization-scenarios)
- [Cost Optimization Scenarios](#cost-optimization-scenarios)

---

## Real-Time Data Pipeline Scenarios

### Scenario 1: Building a Real-Time CDC Pipeline from MySQL to Databricks

**Question:** Your company wants to replicate MySQL database changes to Databricks in near real-time for analytics. The MySQL database has 50 tables with varying schemas and receives 10,000 transactions per second. How would you design this pipeline?

**Answer:**

**Architecture:**

```
MySQL → Debezium CDC → Kafka → Auto Loader → Bronze (Delta) → Silver → Gold
```

**Implementation:**

**Step 1: CDC Capture with Debezium**
```yaml
# Debezium connector configuration
name: mysql-source-connector
config:
  connector.class: io.debezium.connector.mysql.MySqlConnector
  database.hostname: mysql-prod.example.com
  database.port: 3306
  database.user: debezium
  database.password: ${MYSQL_PASSWORD}
  database.server.id: 184054
  database.server.name: production
  table.include.list: sales.orders,sales.customers,sales.products
  database.history.kafka.bootstrap.servers: kafka:9092
  database.history.kafka.topic: dbhistory.mysql

  # CDC output format
  key.converter: org.apache.kafka.connect.json.JsonConverter
  value.converter: org.apache.kafka.connect.json.JsonConverter
  transforms: unwrap
  transforms.unwrap.type: io.debezium.transforms.ExtractNewRecordState
```

**Step 2: Kafka Topic Organization**
```python
# Topics created by Debezium
# production.sales.orders
# production.sales.customers
# production.sales.products

# Each message contains:
# {
#   "op": "c|u|d",  # create, update, delete
#   "ts_ms": 1234567890,
#   "before": {...},  # old values
#   "after": {...},   # new values
#   "source": {"table": "orders", "lsn": "..."}
# }
```

**Step 3: Ingest with Auto Loader (Bronze Layer)**
```python
# Auto Loader reads from Kafka topic files or direct Kafka
from pyspark.sql.functions import *
from pyspark.sql.types import *

# Option 1: Kafka Direct
def create_kafka_stream(topic_name, table_name):
    df = spark.readStream \
        .format("kafka") \
        .option("kafka.bootstrap.servers", "kafka:9092") \
        .option("subscribe", topic_name) \
        .option("startingOffsets", "earliest") \
        .option("maxOffsetsPerTrigger", 10000) \
        .load()

    # Parse CDC message
    parsed_df = df.select(
        col("key").cast("string").alias("key"),
        from_json(col("value").cast("string"), get_schema(table_name)).alias("data"),
        col("topic"),
        col("partition"),
        col("offset"),
        col("timestamp").alias("kafka_timestamp")
    )

    # Extract CDC fields
    cdc_df = parsed_df.select(
        col("data.after.*"),
        col("data.op").alias("operation"),
        col("data.ts_ms").alias("source_timestamp"),
        col("kafka_timestamp"),
        current_timestamp().alias("ingestion_timestamp")
    )

    # Write to Bronze
    query = cdc_df.writeStream \
        .format("delta") \
        .option("checkpointLocation", f"/checkpoints/bronze/{table_name}") \
        .option("mergeSchema", "true") \
        .trigger(processingTime="30 seconds") \
        .table(f"bronze.{table_name}")

    return query

# Start streams for all tables
tables = ["orders", "customers", "products"]
queries = []
for table in tables:
    topic = f"production.sales.{table}"
    query = create_kafka_stream(topic, table)
    queries.append(query)
```

**Step 4: Apply CDC Logic (Silver Layer)**
```python
# Apply MERGE to handle inserts, updates, deletes
from delta.tables import DeltaTable

def apply_cdc_silver(source_table, target_table, primary_key):
    """
    Apply CDC changes from Bronze to Silver using MERGE
    """

    # Read from Bronze as stream
    bronze_df = spark.readStream \
        .format("delta") \
        .table(source_table) \
        .withWatermark("ingestion_timestamp", "10 minutes")

    # Deduplicate within micro-batch (keep latest)
    dedup_df = bronze_df.dropDuplicates([primary_key, "source_timestamp"]) \
        .select("*")

    def upsert_to_silver(micro_batch_df, batch_id):
        # Get the target Delta table
        if not spark.catalog.tableExists(target_table):
            # Create table if first run
            micro_batch_df.write.format("delta").saveAsTable(target_table)
            return

        target = DeltaTable.forName(spark, target_table)

        # MERGE logic
        target.alias("target").merge(
            micro_batch_df.alias("source"),
            f"target.{primary_key} = source.{primary_key}"
        ).whenMatchedDelete(
            condition="source.operation = 'd'"
        ).whenMatchedUpdateAll(
            condition="source.operation = 'u'"
        ).whenNotMatchedInsertAll(
            condition="source.operation IN ('c', 'u')"
        ).execute()

    # Write stream using foreachBatch
    query = dedup_df.writeStream \
        .foreachBatch(upsert_to_silver) \
        .option("checkpointLocation", f"/checkpoints/silver/{target_table}") \
        .trigger(processingTime="1 minute") \
        .start()

    return query

# Apply CDC for each table
silver_queries = [
    apply_cdc_silver("bronze.orders", "silver.orders", "order_id"),
    apply_cdc_silver("bronze.customers", "silver.customers", "customer_id"),
    apply_cdc_silver("bronze.products", "silver.products", "product_id")
]
```

**Step 5: Aggregate to Gold Layer**
```python
# Create business-ready aggregations
def create_gold_aggregation():
    # Join tables for analytics
    orders = spark.read.table("silver.orders")
    customers = spark.read.table("silver.customers")
    products = spark.read.table("silver.products")

    # Create daily sales summary
    daily_sales = orders.join(customers, "customer_id") \
        .join(products, "product_id") \
        .groupBy(
            to_date(col("order_date")).alias("date"),
            col("customer_segment"),
            col("product_category")
        ).agg(
            sum("order_amount").alias("total_revenue"),
            count("order_id").alias("order_count"),
            countDistinct("customer_id").alias("unique_customers")
        )

    # Write to Gold (overwrite daily)
    daily_sales.write \
        .format("delta") \
        .mode("overwrite") \
        .partitionBy("date") \
        .option("replaceWhere", f"date = '{today}'") \
        .saveAsTable("gold.daily_sales_summary")

# Schedule to run every hour
create_gold_aggregation()
```

**Monitoring & Error Handling:**

```python
# Monitor streaming queries
def monitor_streams(queries):
    while True:
        for query in queries:
            status = query.status
            progress = query.lastProgress

            if progress:
                print(f"Query: {query.name}")
                print(f"  Batch: {progress['batchId']}")
                print(f"  Input Rate: {progress['inputRowsPerSecond']}")
                print(f"  Process Rate: {progress['processedRowsPerSecond']}")
                print(f"  Latency: {progress['batchDuration']} ms")

            # Alert if falling behind
            if progress and progress['inputRowsPerSecond'] > progress['processedRowsPerSecond'] * 1.5:
                send_alert(f"Stream {query.name} falling behind!")

        time.sleep(60)

# Error handling with retry logic
def create_resilient_stream(table_name):
    max_retries = 3
    retry_count = 0

    while retry_count < max_retries:
        try:
            query = create_kafka_stream(topic_name, table_name)
            query.awaitTermination()
        except Exception as e:
            retry_count += 1
            print(f"Stream failed (attempt {retry_count}/{max_retries}): {e}")

            if retry_count >= max_retries:
                send_alert(f"Stream {table_name} failed after {max_retries} retries")
                raise

            time.sleep(60 * retry_count)  # Exponential backoff
```

**Performance Considerations:**

```python
# Optimize Kafka consumption
spark.conf.set("spark.sql.streaming.kafka.maxOffsetsPerTrigger", "10000")
spark.conf.set("spark.streaming.kafka.maxRatePerPartition", "1000")

# Optimize Delta writes
spark.conf.set("spark.databricks.delta.optimizeWrite.enabled", "true")
spark.conf.set("spark.databricks.delta.autoCompact.enabled", "true")

# Auto-scaling cluster configuration
cluster_config = {
    "cluster_name": "cdc-pipeline",
    "spark_version": "13.3.x-scala2.12",
    "node_type_id": "i3.xlarge",
    "autoscale": {
        "min_workers": 2,
        "max_workers": 20  # Scale based on Kafka lag
    },
    "spark_conf": {
        "spark.databricks.delta.preview.enabled": "true",
        "spark.sql.adaptive.enabled": "true"
    }
}
```

**Cost Optimization:**

```python
# Use Trigger.AvailableNow for batch-streaming hybrid
# Processes available data then stops (cheaper than continuous)

query = df.writeStream \
    .format("delta") \
    .option("checkpointLocation", checkpoint_path) \
    .trigger(availableNow=True) \
    .table(target_table)

query.awaitTermination()

# Schedule this to run every 5 minutes via Databricks Job
# More cost-effective than continuous streaming for this throughput
```

---

### Scenario 2: Handling Late-Arriving Events in Real-Time Analytics

**Question:** You're building a real-time analytics dashboard that shows hourly metrics. Events can arrive up to 2 hours late due to mobile devices being offline. How do you ensure accurate aggregations while maintaining low latency?

**Answer:**

**Solution: Watermarking + Multiple Time Windows**

```python
from pyspark.sql.functions import *
from pyspark.sql.types import *

# Read streaming events
events_df = spark.readStream \
    .format("cloudFiles") \
    .option("cloudFiles.format", "json") \
    .schema("""
        event_id STRING,
        user_id STRING,
        event_type STRING,
        event_timestamp TIMESTAMP,
        properties MAP<STRING, STRING>
    """) \
    .load("/mnt/events/")

# Apply watermark: tolerate up to 2 hours late data
watermarked_df = events_df \
    .withWatermark("event_timestamp", "2 hours")

# Create hourly aggregations
hourly_metrics = watermarked_df \
    .groupBy(
        window(col("event_timestamp"), "1 hour"),
        col("event_type")
    ).agg(
        count("*").alias("event_count"),
        countDistinct("user_id").alias("unique_users")
    )

# Write with Update mode to handle late updates
query = hourly_metrics.writeStream \
    .format("delta") \
    .outputMode("update") \
    .option("checkpointLocation", "/checkpoints/hourly_metrics") \
    .trigger(processingTime="1 minute") \
    .table("analytics.hourly_metrics")
```

**Handling Dashboard Queries:**

```python
# Dashboard reads from Delta table
# Shows "near-final" results with late data disclaimer

def get_dashboard_data(hours_back=24):
    """
    Get hourly metrics for dashboard
    Marks recent hours as 'preliminary' due to late data
    """

    current_time = spark.sql("SELECT current_timestamp()").collect()[0][0]
    cutoff_time = current_time - timedelta(hours=2)

    df = spark.table("analytics.hourly_metrics")

    # Add status based on watermark
    dashboard_df = df.withColumn(
        "status",
        when(col("window.end") < cutoff_time, "final")
        .otherwise("preliminary")
    ).filter(
        col("window.start") >= current_time - timedelta(hours=hours_back)
    ).select(
        col("window.start").alias("hour"),
        col("event_type"),
        col("event_count"),
        col("unique_users"),
        col("status")
    ).orderBy(desc("hour"))

    return dashboard_df

# Dashboard UI shows:
# Hour          | Events | Users | Status
# 2024-01-15 14:00 | 10,523 | 3,421 | preliminary ⚠️
# 2024-01-15 13:00 | 12,447 | 4,102 | preliminary ⚠️
# 2024-01-15 12:00 | 11,890 | 3,876 | final ✓
```

**Optimizing for Late Data:**

```python
# Strategy 1: Dual-speed processing
# - Fast path: 5-minute aggregations for dashboard
# - Slow path: Hourly corrections for late data

# Fast path
fast_metrics = watermarked_df \
    .withWatermark("event_timestamp", "10 minutes") \
    .groupBy(
        window(col("event_timestamp"), "5 minutes"),
        col("event_type")
    ).agg(
        count("*").alias("event_count")
    )

fast_query = fast_metrics.writeStream \
    .format("delta") \
    .outputMode("update") \
    .option("checkpointLocation", "/checkpoints/fast_metrics") \
    .trigger(processingTime="30 seconds") \
    .table("analytics.realtime_metrics")

# Slow path
slow_metrics = watermarked_df \
    .withWatermark("event_timestamp", "2 hours") \
    .groupBy(
        window(col("event_timestamp"), "1 hour"),
        col("event_type")
    ).agg(
        count("*").alias("event_count"),
        countDistinct("user_id").alias("unique_users")
    )

slow_query = slow_metrics.writeStream \
    .format("delta") \
    .outputMode("complete") \
    .option("checkpointLocation", "/checkpoints/corrected_metrics") \
    .trigger(processingTime="5 minutes") \
    .table("analytics.corrected_hourly_metrics")
```

**Reconciliation Process:**

```python
# Nightly reconciliation to fix any discrepancies
def reconcile_metrics(date):
    """
    Compare realtime vs corrected metrics
    Alert on significant differences
    """

    realtime = spark.table("analytics.realtime_metrics") \
        .filter(col("date") == date) \
        .groupBy("hour", "event_type") \
        .sum("event_count")

    corrected = spark.table("analytics.corrected_hourly_metrics") \
        .filter(col("date") == date)

    comparison = realtime.alias("rt").join(
        corrected.alias("corr"),
        (col("rt.hour") == col("corr.window.start")) &
        (col("rt.event_type") == col("corr.event_type"))
    ).select(
        col("rt.hour"),
        col("rt.event_type"),
        col("rt.sum(event_count)").alias("realtime_count"),
        col("corr.event_count").alias("corrected_count"),
        ((col("corr.event_count") - col("rt.sum(event_count)")) /
         col("rt.sum(event_count)") * 100).alias("percent_diff")
    )

    # Alert if difference > 5%
    significant_diffs = comparison.filter(abs(col("percent_diff")) > 5)

    if significant_diffs.count() > 0:
        send_alert("Significant metric discrepancies detected",
                   significant_diffs.toPandas().to_dict())

    return comparison

# Schedule daily
reconcile_metrics("2024-01-15")
```

---

## Performance & Optimization Scenarios

### Scenario 3: Optimizing Slow Aggregation Query on 10TB Table

**Question:** You have a 10TB Delta table with 5 billion rows tracking user events. A query computing daily active users is taking 45 minutes. How would you optimize it to run under 5 minutes?

**Answer:**

**Initial Slow Query:**
```sql
-- Takes 45 minutes ❌
SELECT
    date,
    COUNT(DISTINCT user_id) as daily_active_users,
    COUNT(*) as total_events
FROM events
WHERE date >= '2024-01-01'
GROUP BY date
ORDER BY date;
```

**Step 1: Analyze Execution Plan**
```python
# Check what's causing slowness
slow_query = """
    SELECT date, COUNT(DISTINCT user_id) as dau
    FROM events
    WHERE date >= '2024-01-01'
    GROUP BY date
"""

spark.sql(slow_query).explain("formatted")

# Output shows:
# - Full table scan (10TB)
# - No partition pruning
# - DISTINCT causing shuffle of all user_ids
# - 200 partitions too few for 10TB
```

**Step 2: Table Optimization**
```python
# Check current table structure
spark.sql("DESCRIBE EXTENDED events").show(100, False)

# Problem: Not partitioned by date
# Solution: Reorganize table

# Partition by date
spark.sql("""
    CREATE OR REPLACE TABLE events_optimized
    USING DELTA
    PARTITIONED BY (date)
    AS SELECT * FROM events
""")

# Further optimize with liquid clustering
spark.sql("""
    ALTER TABLE events_optimized
    CLUSTER BY (user_id, event_type)
""")

# Run OPTIMIZE
spark.sql("""
    OPTIMIZE events_optimized
    ZORDER BY (user_id)
""")
```

**Step 3: Query Optimization**
```python
# Optimized Query Version 1: Partition Pruning
optimized_query_v1 = """
    SELECT
        date,
        COUNT(DISTINCT user_id) as daily_active_users,
        COUNT(*) as total_events
    FROM events_optimized
    WHERE date >= '2024-01-01'  -- Prunes partitions
    GROUP BY date
    ORDER BY date
"""

# Execution time: 15 minutes (67% improvement)
# Still slow due to COUNT(DISTINCT)

# Optimized Query Version 2: Approximation
optimized_query_v2 = """
    SELECT
        date,
        APPROX_COUNT_DISTINCT(user_id) as daily_active_users,  -- Much faster
        COUNT(*) as total_events
    FROM events_optimized
    WHERE date >= '2024-01-01'
    GROUP BY date
    ORDER BY date
"""

# Execution time: 5 minutes (89% improvement)
# Accuracy: 99%+ with default settings

# Optimized Query Version 3: Pre-aggregation (Best!)
# Create materialized aggregation

# Daily aggregation pipeline
def create_daily_aggregations():
    """
    Incrementally maintain daily aggregations
    """

    from delta.tables import DeltaTable

    # Get yesterday's data
    yesterday = (datetime.now() - timedelta(days=1)).strftime('%Y-%m-%d')

    # Aggregate new data
    daily_agg = spark.sql(f"""
        SELECT
            date,
            COUNT(DISTINCT user_id) as daily_active_users,
            COUNT(*) as total_events,
            COUNT(DISTINCT session_id) as sessions,
            SUM(duration) as total_duration
        FROM events_optimized
        WHERE date = '{yesterday}'
        GROUP BY date
    """)

    # MERGE into aggregation table
    if spark.catalog.tableExists("analytics.daily_user_metrics"):
        target = DeltaTable.forName(spark, "analytics.daily_user_metrics")

        target.alias("target").merge(
            daily_agg.alias("source"),
            "target.date = source.date"
        ).whenMatchedUpdateAll() \
         .whenNotMatchedInsertAll() \
         .execute()
    else:
        daily_agg.write.format("delta").saveAsTable("analytics.daily_user_metrics")

# Schedule to run daily
create_daily_aggregations()

# Now query runs instantly!
optimized_query_v3 = """
    SELECT *
    FROM analytics.daily_user_metrics
    WHERE date >= '2024-01-01'
    ORDER BY date
"""
# Execution time: < 1 second (99.96% improvement!) ✓
```

**Step 4: Cluster Configuration**
```python
# Original cluster: Too small
original_cluster = {
    "workers": 4,
    "driver": "Standard_DS3_v2",
    "worker": "Standard_DS3_v2"
}

# Optimized cluster: Right-sized with Photon
optimized_cluster = {
    "workers": {
        "min": 8,
        "max": 32  # Auto-scale
    },
    "driver": "Standard_DS5_v2",
    "worker": "Standard_DS4_v2",
    "runtime_engine": "PHOTON",  # 2-3x speedup for aggregations
    "spark_conf": {
        "spark.sql.shuffle.partitions": "2048",  # Increase from default 200
        "spark.sql.adaptive.enabled": "true",
        "spark.databricks.delta.optimizeWrite.enabled": "true"
    }
}
```

**Step 5: Incremental Processing**
```python
# Instead of querying 90 days at once, incrementalize

# Process each day separately
def compute_dau_incremental(start_date, end_date):
    """
    Compute DAU incrementally to avoid overwhelming cluster
    """

    dates = pd.date_range(start_date, end_date)
    results = []

    for date in dates:
        daily_stats = spark.sql(f"""
            SELECT
                '{date}' as date,
                APPROX_COUNT_DISTINCT(user_id) as dau
            FROM events_optimized
            WHERE date = '{date}'
        """).collect()[0]

        results.append(daily_stats)

    return spark.createDataFrame(results)

# Much more memory-efficient
dau_results = compute_dau_incremental('2024-01-01', '2024-03-31')
```

**Final Performance Comparison:**

| Approach | Execution Time | Cost | Accuracy |
|----------|---------------|------|----------|
| Original | 45 min | $15 | 100% |
| Partitioned | 15 min | $5 | 100% |
| Approx Count | 5 min | $1.50 | 99% |
| Pre-aggregated | <1 sec | $0.01 | 100% |

**Best Practice:**
```python
# Maintain both exact and approximate
# - Exact: For official metrics, run overnight
# - Approximate: For dashboards, run on-demand

def get_dau_metrics(exact=False):
    if exact:
        # Use pre-aggregated exact counts
        return spark.table("analytics.daily_user_metrics")
    else:
        # Use approximate for faster dashboard queries
        return spark.sql("""
            SELECT
                date,
                APPROX_COUNT_DISTINCT(user_id) as dau
            FROM events_optimized
            GROUP BY date
        """)
```

---

## Data Quality & Recovery Scenarios

### Scenario 4: Recovering from Bad Data Load

**Question:** A data engineer accidentally loaded corrupt data into your production orders table at 2 PM, overwriting good data. You discovered the issue at 4 PM. How do you recover while minimizing downtime?

**Answer:**

**Step 1: Assess the Damage**
```python
from datetime import datetime, timedelta

# Check table history
history_df = spark.sql("DESCRIBE HISTORY orders")
history_df.show(20, False)

# Output:
# version | timestamp           | operation | operationMetrics
# 487     | 2024-01-15 14:23:15 | WRITE     | numOutputRows: 5000000  ← Bad data
# 486     | 2024-01-15 13:00:05 | MERGE     | numOutputRows: 1000000
# 485     | 2024-01-15 12:00:01 | MERGE     | numOutputRows: 950000  ← Last good version

# Verify the corruption
current_data = spark.read.table("orders")
print(f"Current row count: {current_data.count()}")
print(f"Null order_ids: {current_data.filter('order_id IS NULL').count()}")

# Check data quality
bad_data_check = spark.sql("""
    SELECT
        COUNT(*) as total_rows,
        COUNT(DISTINCT order_id) as unique_orders,
        SUM(CASE WHEN order_amount < 0 THEN 1 ELSE 0 END) as negative_amounts,
        SUM(CASE WHEN customer_id IS NULL THEN 1 ELSE 0 END) as null_customers
    FROM orders
""")
bad_data_check.show()

# Results confirm corruption:
# 5M rows with null customers and negative amounts
```

**Step 2: Immediate Mitigation - Restore from Time Travel**
```python
# Quick restore to last good version
spark.sql("""
    RESTORE TABLE orders TO VERSION AS OF 485
""")

# Verify restoration
restored_data = spark.table("orders")
print(f"Restored row count: {restored_data.count()}")

# Downtime: ~30 seconds ✓
```

**Step 3: Recover Any Good Data from Bad Load**
```python
# Some orders between 2-4 PM might be valid
# Read the bad version to extract good records

bad_version_df = spark.read.format("delta") \
    .option("versionAsOf", 487) \
    .table("orders")

# Filter for potentially good records
# (orders loaded after 2 PM with valid data)
potentially_good = bad_version_df.filter(
    (col("created_at") >= "2024-01-15 14:00:00") &
    (col("order_id").isNotNull()) &
    (col("customer_id").isNotNull()) &
    (col("order_amount") > 0)
)

print(f"Potentially good orders from bad load: {potentially_good.count()}")

# If any good records found, merge them back
if potentially_good.count() > 0:
    from delta.tables import DeltaTable

    delta_table = DeltaTable.forName(spark, "orders")

    delta_table.alias("target").merge(
        potentially_good.alias("source"),
        "target.order_id = source.order_id"
    ).whenNotMatchedInsertAll().execute()
```

**Step 4: Prevent Future Occurrences**
```python
# Add constraints to catch bad data
spark.sql("""
    ALTER TABLE orders
    ADD CONSTRAINT valid_customer
    CHECK (customer_id IS NOT NULL)
""")

spark.sql("""
    ALTER TABLE orders
    ADD CONSTRAINT positive_amount
    CHECK (order_amount >= 0)
""")

# Add validation layer with Delta Live Tables
import dlt

@dlt.table(
    name="orders_validated",
    comment="Orders with data quality checks"
)
@dlt.expect_or_drop("valid_order_id", "order_id IS NOT NULL")
@dlt.expect_or_drop("valid_customer", "customer_id IS NOT NULL")
@dlt.expect_or_drop("positive_amount", "order_amount >= 0")
@dlt.expect_or_drop("valid_date", "order_date >= '2020-01-01'")
@dlt.expect_or_fail("critical_check", "order_id IS NOT NULL AND customer_id IS NOT NULL")
def validated_orders():
    return spark.table("bronze.orders_raw")
```

**Step 5: Implement Change Control**
```python
# Require approval for production writes
def write_to_production(df, table_name, mode="append"):
    """
    Safe write with validation and approval
    """

    # Step 1: Write to staging
    staging_table = f"staging.{table_name}"
    df.write.format("delta").mode("overwrite").saveAsTable(staging_table)

    # Step 2: Run validation
    validation_results = validate_data(staging_table)

    if not validation_results["passed"]:
        raise ValueError(f"Validation failed: {validation_results['errors']}")

    # Step 3: Show preview
    print("Preview of data to be loaded:")
    spark.table(staging_table).show(10)

    print("\nData Statistics:")
    spark.table(staging_table).describe().show()

    # Step 4: Require manual approval for production
    if is_production_environment():
        approval = input("Approve load to production? (yes/no): ")
        if approval.lower() != "yes":
            raise ValueError("Load cancelled by user")

    # Step 5: Create backup before write
    backup_table = f"backups.{table_name}_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
    spark.table(table_name).write.format("delta").saveAsTable(backup_table)

    # Step 6: Safe write with transaction
    try:
        if mode == "append":
            df.write.format("delta").mode("append").saveAsTable(table_name)
        elif mode == "merge":
            # Implement MERGE logic
            pass

        print(f"✓ Successfully loaded {df.count()} rows to {table_name}")
    except Exception as e:
        print(f"❌ Load failed: {e}")
        print("Data remains in staging. Production table unchanged.")
        raise

def validate_data(table_name):
    """
    Run data quality checks
    """
    df = spark.table(table_name)

    checks = {
        "row_count": df.count() > 0,
        "no_null_ids": df.filter("order_id IS NULL").count() == 0,
        "valid_amounts": df.filter("order_amount < 0").count() == 0,
        "valid_dates": df.filter("order_date < '2020-01-01'").count() == 0
    }

    passed = all(checks.values())
    errors = [k for k, v in checks.items() if not v]

    return {"passed": passed, "errors": errors, "checks": checks}
```

**Step 6: Set Up Monitoring**
```python
# Monitor table changes
def monitor_table_changes():
    """
    Alert on suspicious table changes
    """

    history = spark.sql("DESCRIBE HISTORY orders LIMIT 10")
    latest = history.first()

    # Alert conditions
    alerts = []

    # Check for large deletes
    if latest["operation"] == "DELETE":
        rows_deleted = int(latest["operationMetrics"]["numDeletedRows"])
        if rows_deleted > 100000:
            alerts.append(f"Large delete: {rows_deleted} rows")

    # Check for overwrites
    if latest["operation"] == "WRITE" and "mode=Overwrite" in str(latest["operationParameters"]):
        alerts.append("Table overwritten!")

    # Check for unexpected source
    if "notebook" in latest["operationParameters"]:
        notebook = latest["operationParameters"]["notebook"]
        if "prod" not in notebook:
            alerts.append(f"Modified from non-prod notebook: {notebook}")

    # Send alerts
    if alerts:
        send_alert("Suspicious table modification detected", alerts)

# Schedule to run every 5 minutes
```

**Recovery Timeline:**
- **2:00 PM**: Bad data loaded
- **4:00 PM**: Issue discovered
- **4:01 PM**: RESTORE executed (30 seconds)
- **4:05 PM**: Good records from bad load recovered
- **4:10 PM**: Constraints added
- **Total Downtime**: < 1 minute

**Key Takeaways:**
1. Delta Lake time travel enables instant recovery
2. Always validate before loading to production
3. Implement constraints to prevent bad data
4. Monitor table changes for early detection
5. Maintain backup strategy (though time travel usually sufficient)

---

This continues with more scenarios. Let me create the remaining documents.
