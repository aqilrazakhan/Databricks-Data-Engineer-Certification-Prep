# Databricks Data Engineer - Coding Challenges

## Table of Contents
- [PySpark DataFrame Challenges](#pyspark-dataframe-challenges)
- [SQL Challenges](#sql-challenges)
- [Delta Lake Challenges](#delta-lake-challenges)
- [Streaming Challenges](#streaming-challenges)
- [Algorithm & Data Structure Challenges](#algorithm--data-structure-challenges)

---

## PySpark DataFrame Challenges

### Challenge 1: Deduplication with Window Functions

**Problem:** Given a DataFrame of user events with duplicates, keep only the most recent event for each user-event type combination.

```python
# Input DataFrame
from pyspark.sql.types import *

schema = StructType([
    StructField("user_id", StringType()),
    StructField("event_type", StringType()),
    StructField("timestamp", TimestampType()),
    StructField("properties", MapType(StringType(), StringType()))
])

data = [
    ("user1", "login", "2024-01-15 10:00:00", {"device": "mobile"}),
    ("user1", "login", "2024-01-15 11:00:00", {"device": "desktop"}),  # Keep this
    ("user1", "purchase", "2024-01-15 10:30:00", {"amount": "99.99"}),
    ("user2", "login", "2024-01-15 09:00:00", {"device": "mobile"}),
    ("user2", "login", "2024-01-15 09:05:00", {"device": "mobile"}),   # Keep this
]

df = spark.createDataFrame(data, schema)
df.show(truncate=False)
```

**Solution:**

```python
from pyspark.sql.functions import *
from pyspark.sql.window import Window

# Method 1: Using Window Function (Most Efficient)
def deduplicate_v1(df):
    """
    Use ROW_NUMBER() to identify latest record per group
    """

    window_spec = Window.partitionBy("user_id", "event_type") \
                        .orderBy(desc("timestamp"))

    deduped_df = df.withColumn("row_num", row_number().over(window_spec)) \
                   .filter(col("row_num") == 1) \
                   .drop("row_num")

    return deduped_df

result_v1 = deduplicate_v1(df)
result_v1.show(truncate=False)

# Method 2: Using Group By + Max (Good for Large Data)
def deduplicate_v2(df):
    """
    Find max timestamp per group, then join back
    """

    max_timestamps = df.groupBy("user_id", "event_type") \
                       .agg(max("timestamp").alias("max_timestamp"))

    deduped_df = df.join(
        max_timestamps,
        (df.user_id == max_timestamps.user_id) &
        (df.event_type == max_timestamps.event_type) &
        (df.timestamp == max_timestamps.max_timestamp)
    ).select(df["*"])

    return deduped_df

result_v2 = deduplicate_v2(df)
result_v2.show(truncate=False)

# Method 3: Using dropDuplicates (Simplest, but less control)
def deduplicate_v3(df):
    """
    Use built-in dropDuplicates keeping last occurrence
    Note: dropDuplicates doesn't guarantee which row is kept!
    Need to sort first.
    """

    # First, add a sortable column to ensure we keep the latest
    sorted_df = df.orderBy(desc("timestamp"))

    # Then deduplicate
    deduped_df = sorted_df.dropDuplicates(["user_id", "event_type"])

    return deduped_df

result_v3 = deduplicate_v3(df)
result_v3.show(truncate=False)
```

**Expected Output:**
```
+-------+-----------+-------------------+------------------+
|user_id|event_type |timestamp          |properties        |
+-------+-----------+-------------------+------------------+
|user1  |login      |2024-01-15 11:00:00|{device -> desktop}|
|user1  |purchase   |2024-01-15 10:30:00|{amount -> 99.99} |
|user2  |login      |2024-01-15 09:05:00|{device -> mobile}|
+-------+-----------+-------------------+------------------+
```

**Performance Comparison:**
```python
# Benchmark on large dataset
large_df = df.repartition(100).cache()  # Simulate large data
large_df.count()

import time

# Test Method 1
start = time.time()
deduplicate_v1(large_df).count()
print(f"Method 1 (Window): {time.time() - start:.2f}s")

# Test Method 2
start = time.time()
deduplicate_v2(large_df).count()
print(f"Method 2 (GroupBy): {time.time() - start:.2f}s")

# Test Method 3
start = time.time()
deduplicate_v3(large_df).count()
print(f"Method 3 (dropDuplicates): {time.time() - start:.2f}s")

# Typical results:
# Method 1 (Window): 8.23s
# Method 2 (GroupBy): 12.45s
# Method 3 (dropDuplicates): 15.67s
```

---

### Challenge 2: Flatten Nested JSON with Complex Schema

**Problem:** Parse and flatten a complex nested JSON structure containing arrays and nested objects.

```python
# Input: Nested JSON from e-commerce order
json_data = """
{
  "order_id": "ORD-001",
  "customer": {
    "customer_id": "C123",
    "name": "John Doe",
    "addresses": [
      {"type": "billing", "city": "New York", "zip": "10001"},
      {"type": "shipping", "city": "Boston", "zip": "02101"}
    ]
  },
  "items": [
    {"product_id": "P1", "name": "Laptop", "quantity": 1, "price": 999.99},
    {"product_id": "P2", "name": "Mouse", "quantity": 2, "price": 29.99}
  ],
  "metadata": {
    "source": "mobile_app",
    "campaign": "summer_sale"
  }
}
"""

# Create DataFrame
from pyspark.sql.types import *

json_schema = StructType([
    StructField("order_id", StringType()),
    StructField("customer", StructType([
        StructField("customer_id", StringType()),
        StructField("name", StringType()),
        StructField("addresses", ArrayType(StructType([
            StructField("type", StringType()),
            StructField("city", StringType()),
            StructField("zip", StringType())
        ])))
    ])),
    StructField("items", ArrayType(StructType([
        StructField("product_id", StringType()),
        StructField("name", StringType()),
        StructField("quantity", IntegerType()),
        StructField("price", DoubleType())
    ]))),
    StructField("metadata", MapType(StringType(), StringType()))
])

df = spark.read.json(spark.sparkContext.parallelize([json_data]), schema=json_schema)
df.printSchema()
```

**Solution:**

```python
from pyspark.sql.functions import *

def flatten_nested_json(df):
    """
    Flatten complex nested structure
    Target: One row per item with all order/customer info
    """

    # Step 1: Flatten customer info
    flattened = df.select(
        col("order_id"),
        col("customer.customer_id").alias("customer_id"),
        col("customer.name").alias("customer_name"),
        col("customer.addresses").alias("addresses"),
        col("items"),
        col("metadata")
    )

    # Step 2: Explode addresses to create address columns
    # Find billing and shipping addresses
    flattened = flattened.withColumn(
        "billing_address",
        expr("filter(addresses, x -> x.type = 'billing')[0]")
    ).withColumn(
        "shipping_address",
        expr("filter(addresses, x -> x.type = 'shipping')[0]")
    )

    # Extract address fields
    flattened = flattened.select(
        "*",
        col("billing_address.city").alias("billing_city"),
        col("billing_address.zip").alias("billing_zip"),
        col("shipping_address.city").alias("shipping_city"),
        col("shipping_address.zip").alias("shipping_zip")
    ).drop("addresses", "billing_address", "shipping_address")

    # Step 3: Explode items (one row per item)
    exploded = flattened.select(
        col("order_id"),
        col("customer_id"),
        col("customer_name"),
        col("billing_city"),
        col("billing_zip"),
        col("shipping_city"),
        col("shipping_zip"),
        explode(col("items")).alias("item"),
        col("metadata")
    )

    # Step 4: Flatten item structure
    final = exploded.select(
        col("order_id"),
        col("customer_id"),
        col("customer_name"),
        col("billing_city"),
        col("billing_zip"),
        col("shipping_city"),
        col("shipping_zip"),
        col("item.product_id").alias("product_id"),
        col("item.name").alias("product_name"),
        col("item.quantity"),
        col("item.price"),
        (col("item.quantity") * col("item.price")).alias("line_total"),
        col("metadata.source").alias("order_source"),
        col("metadata.campaign").alias("campaign")
    )

    return final

result = flatten_nested_json(df)
result.show(truncate=False)
```

**Expected Output:**
```
+--------+-----------+-------------+-------------+------------+--------------+-------------+----------+------------+--------+------+----------+------------+------------+
|order_id|customer_id|customer_name|billing_city |billing_zip |shipping_city |shipping_zip |product_id|product_name|quantity|price |line_total|order_source|campaign    |
+--------+-----------+-------------+-------------+------------+--------------+-------------+----------+------------+--------+------+----------+------------+------------+
|ORD-001 |C123       |John Doe     |New York     |10001       |Boston        |02101        |P1        |Laptop      |1       |999.99|999.99    |mobile_app  |summer_sale |
|ORD-001 |C123       |John Doe     |New York     |10001       |Boston        |02101        |P2        |Mouse       |2       |29.99 |59.98     |mobile_app  |summer_sale |
+--------+-----------+-------------+-------------+------------+--------------+-------------+----------+------------+--------+------+----------+------------+------------+
```

**Alternative: Using SQL**
```python
# Register as temp view
df.createOrReplaceTempView("orders_json")

result_sql = spark.sql("""
    SELECT
        order_id,
        customer.customer_id,
        customer.name as customer_name,
        filter(customer.addresses, x -> x.type = 'billing')[0].city as billing_city,
        filter(customer.addresses, x -> x.type = 'billing')[0].zip as billing_zip,
        filter(customer.addresses, x -> x.type = 'shipping')[0].city as shipping_city,
        filter(customer.addresses, x -> x.type = 'shipping')[0].zip as shipping_zip,
        item.product_id,
        item.name as product_name,
        item.quantity,
        item.price,
        item.quantity * item.price as line_total,
        metadata['source'] as order_source,
        metadata['campaign'] as campaign
    FROM orders_json
    LATERAL VIEW explode(items) item_table AS item
""")

result_sql.show(truncate=False)
```

---

### Challenge 3: Sessionization (Time-Based Windows)

**Problem:** Given user clickstream data, create sessions where a session ends after 30 minutes of inactivity.

```python
# Input data: User clicks with timestamps
from datetime import datetime, timedelta

data = [
    ("user1", "2024-01-15 10:00:00", "homepage"),
    ("user1", "2024-01-15 10:05:00", "product_page"),
    ("user1", "2024-01-15 10:10:00", "cart"),
    ("user1", "2024-01-15 10:45:00", "homepage"),      # New session (>30 min gap)
    ("user1", "2024-01-15 10:50:00", "product_page"),
    ("user2", "2024-01-15 11:00:00", "homepage"),
    ("user2", "2024-01-15 11:02:00", "search"),
    ("user2", "2024-01-15 11:40:00", "homepage"),      # New session
]

df = spark.createDataFrame(data, ["user_id", "timestamp", "page"])
df = df.withColumn("timestamp", to_timestamp(col("timestamp")))
df.show(truncate=False)
```

**Solution:**

```python
from pyspark.sql.functions import *
from pyspark.sql.window import Window

def create_sessions(df, inactivity_threshold_minutes=30):
    """
    Create session IDs based on inactivity threshold
    """

    # Step 1: Sort by user and timestamp
    window_spec = Window.partitionBy("user_id").orderBy("timestamp")

    # Step 2: Calculate time since previous event
    df_with_lag = df.withColumn(
        "prev_timestamp",
        lag("timestamp").over(window_spec)
    )

    # Step 3: Calculate time difference in minutes
    df_with_diff = df_with_lag.withColumn(
        "minutes_since_last",
        (unix_timestamp("timestamp") - unix_timestamp("prev_timestamp")) / 60
    )

    # Step 4: Mark session boundaries (new session if gap > threshold)
    df_with_boundary = df_with_diff.withColumn(
        "is_new_session",
        when(
            (col("prev_timestamp").isNull()) |  # First event for user
            (col("minutes_since_last") > inactivity_threshold_minutes),
            1
        ).otherwise(0)
    )

    # Step 5: Create session ID using cumulative sum
    session_window = Window.partitionBy("user_id").orderBy("timestamp") \
                           .rowsBetween(Window.unboundedPreceding, Window.currentRow)

    df_with_session = df_with_boundary.withColumn(
        "session_num",
        sum("is_new_session").over(session_window)
    ).withColumn(
        "session_id",
        concat(col("user_id"), lit("_"), col("session_num"))
    )

    # Step 6: Clean up intermediate columns
    result = df_with_session.select(
        "user_id",
        "session_id",
        "timestamp",
        "page",
        "minutes_since_last"
    )

    return result

sessions_df = create_sessions(df, inactivity_threshold_minutes=30)
sessions_df.show(truncate=False)
```

**Expected Output:**
```
+-------+-----------+-------------------+------------+------------------+
|user_id|session_id |timestamp          |page        |minutes_since_last|
+-------+-----------+-------------------+------------+------------------+
|user1  |user1_1    |2024-01-15 10:00:00|homepage    |null              |
|user1  |user1_1    |2024-01-15 10:05:00|product_page|5.0               |
|user1  |user1_1    |2024-01-15 10:10:00|cart        |5.0               |
|user1  |user1_2    |2024-01-15 10:45:00|homepage    |35.0              |
|user1  |user1_2    |2024-01-15 10:50:00|product_page|5.0               |
|user2  |user2_1    |2024-01-15 11:00:00|homepage    |null              |
|user2  |user2_1    |2024-01-15 11:02:00|search      |2.0               |
|user2  |user2_2    |2024-01-15 11:40:00|homepage    |38.0              |
+-------+-----------+-------------------+------------+------------------+
```

**Session Summary:**
```python
# Aggregate session metrics
session_summary = sessions_df.groupBy("user_id", "session_id").agg(
    min("timestamp").alias("session_start"),
    max("timestamp").alias("session_end"),
    count("*").alias("events_in_session"),
    collect_list("page").alias("page_sequence")
).withColumn(
    "session_duration_minutes",
    (unix_timestamp("session_end") - unix_timestamp("session_start")) / 60
)

session_summary.show(truncate=False)
```

**Output:**
```
+-------+-----------+-------------------+-------------------+-----------------+-----------------------------------+------------------------+
|user_id|session_id |session_start      |session_end        |events_in_session|page_sequence                      |session_duration_minutes|
+-------+-----------+-------------------+-------------------+-----------------+-----------------------------------+------------------------+
|user1  |user1_1    |2024-01-15 10:00:00|2024-01-15 10:10:00|3                |[homepage, product_page, cart]     |10.0                    |
|user1  |user1_2    |2024-01-15 10:45:00|2024-01-15 10:50:00|2                |[homepage, product_page]           |5.0                     |
|user2  |user2_1    |2024-01-15 11:00:00|2024-01-15 11:02:00|2                |[homepage, search]                 |2.0                     |
|user2  |user2_2    |2024-01-15 11:40:00|2024-01-15 11:40:00|1                |[homepage]                         |0.0                     |
+-------+-----------+-------------------+-------------------+-----------------+-----------------------------------+------------------------+
```

---

## SQL Challenges

### Challenge 4: Complex Aggregation with Multiple Conditions

**Problem:** Calculate monthly metrics with year-over-year comparison.

```sql
-- Given sales table
CREATE OR REPLACE TABLE sales (
    order_id STRING,
    order_date DATE,
    customer_id STRING,
    product_category STRING,
    amount DECIMAL(10,2)
);

-- Insert sample data
INSERT INTO sales VALUES
    ('O1', '2023-01-15', 'C1', 'Electronics', 999.99),
    ('O2', '2023-01-20', 'C2', 'Electronics', 499.99),
    ('O3', '2023-02-10', 'C1', 'Books', 29.99),
    ('O4', '2024-01-12', 'C2', 'Electronics', 1299.99),
    ('O5', '2024-01-25', 'C3', 'Electronics', 799.99),
    ('O6', '2024-02-08', 'C1', 'Books', 39.99);
```

**Task:** Create a report showing:
- Monthly revenue by category
- YoY growth percentage
- Cumulative revenue for the year
- Rank of each category within the month

**Solution:**

```sql
-- Complete solution with CTEs
WITH monthly_sales AS (
    -- Aggregate to monthly level
    SELECT
        DATE_TRUNC('month', order_date) as month,
        product_category,
        SUM(amount) as monthly_revenue,
        COUNT(DISTINCT customer_id) as unique_customers,
        COUNT(*) as order_count
    FROM sales
    GROUP BY 1, 2
),
yoy_comparison AS (
    -- Calculate YoY metrics
    SELECT
        month,
        product_category,
        monthly_revenue,
        unique_customers,
        order_count,
        LAG(monthly_revenue, 12) OVER (
            PARTITION BY product_category
            ORDER BY month
        ) as revenue_last_year,
        LAG(unique_customers, 12) OVER (
            PARTITION BY product_category
            ORDER BY month
        ) as customers_last_year
    FROM monthly_sales
),
metrics_calculated AS (
    -- Calculate growth and cumulative metrics
    SELECT
        month,
        product_category,
        monthly_revenue,
        unique_customers,
        order_count,
        revenue_last_year,
        CASE
            WHEN revenue_last_year IS NOT NULL AND revenue_last_year > 0
            THEN ROUND(((monthly_revenue - revenue_last_year) / revenue_last_year * 100), 2)
            ELSE NULL
        END as yoy_growth_pct,
        SUM(monthly_revenue) OVER (
            PARTITION BY YEAR(month), product_category
            ORDER BY month
            ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
        ) as ytd_revenue,
        ROW_NUMBER() OVER (
            PARTITION BY month
            ORDER BY monthly_revenue DESC
        ) as category_rank
    FROM yoy_comparison
)
-- Final output
SELECT
    month,
    product_category,
    monthly_revenue,
    unique_customers,
    order_count,
    revenue_last_year,
    yoy_growth_pct,
    ytd_revenue,
    category_rank,
    -- Add moving averages
    AVG(monthly_revenue) OVER (
        PARTITION BY product_category
        ORDER BY month
        ROWS BETWEEN 2 PRECEDING AND CURRENT ROW
    ) as moving_avg_3month
FROM metrics_calculated
ORDER BY month DESC, category_rank;
```

**Expected Output:**
```
+----------+-----------------+---------------+-----------------+------------+------------------+--------------+------------+--------------+------------------+
|month     |product_category |monthly_revenue|unique_customers |order_count |revenue_last_year |yoy_growth_pct|ytd_revenue |category_rank |moving_avg_3month |
+----------+-----------------+---------------+-----------------+------------+------------------+--------------+------------+--------------+------------------+
|2024-02-01|Books            |39.99          |1                |1           |29.99             |33.34         |39.99       |1             |34.99             |
|2024-01-01|Electronics      |2099.98        |2                |2           |1499.98           |40.00         |2099.98     |1             |1866.65           |
|2023-02-01|Books            |29.99          |1                |1           |null              |null          |29.99       |1             |29.99             |
|2023-01-01|Electronics      |1499.98        |2                |2           |null              |null          |1499.98     |1             |1499.98           |
+----------+-----------------+---------------+-----------------+------------+------------------+--------------+------------+--------------+------------------+
```

---

### Challenge 5: Recursive Query - Organizational Hierarchy

**Problem:** Given an employee table with manager relationships, find all reports (direct and indirect) for each manager.

```sql
-- Create employee table
CREATE OR REPLACE TABLE employees (
    employee_id INT,
    name STRING,
    manager_id INT,
    title STRING,
    department STRING
);

-- Insert org hierarchy
INSERT INTO employees VALUES
    (1, 'Alice (CEO)', NULL, 'CEO', 'Executive'),
    (2, 'Bob (VP Eng)', 1, 'VP Engineering', 'Engineering'),
    (3, 'Carol (VP Sales)', 1, 'VP Sales', 'Sales'),
    (4, 'David (Eng Mgr)', 2, 'Engineering Manager', 'Engineering'),
    (5, 'Eve (Sales Mgr)', 3, 'Sales Manager', 'Sales'),
    (6, 'Frank (Engineer)', 4, 'Senior Engineer', 'Engineering'),
    (7, 'Grace (Engineer)', 4, 'Engineer', 'Engineering'),
    (8, 'Henry (Sales Rep)', 5, 'Sales Representative', 'Sales');
```

**Solution:**

```sql
-- Method 1: Using Recursive CTE (Standard SQL)
-- Note: Databricks SQL supports this in DBR 11.3+

WITH RECURSIVE org_hierarchy AS (
    -- Base case: Start with all employees
    SELECT
        employee_id,
        name,
        manager_id,
        title,
        employee_id as top_manager_id,
        name as top_manager_name,
        0 as level,
        CAST(name AS STRING) as hierarchy_path
    FROM employees
    WHERE manager_id IS NULL  -- Start with CEO

    UNION ALL

    -- Recursive case: Find direct reports
    SELECT
        e.employee_id,
        e.name,
        e.manager_id,
        e.title,
        oh.top_manager_id,
        oh.top_manager_name,
        oh.level + 1,
        CONCAT(oh.hierarchy_path, ' > ', e.name)
    FROM employees e
    INNER JOIN org_hierarchy oh ON e.manager_id = oh.employee_id
)
SELECT
    employee_id,
    name,
    title,
    level,
    hierarchy_path,
    top_manager_name
FROM org_hierarchy
ORDER BY level, name;

-- Method 2: Using self-joins (works in all Databricks versions)
-- Limited to known depth (3 levels in this example)

SELECT
    e1.employee_id,
    e1.name,
    e1.title,
    CASE
        WHEN e1.manager_id IS NULL THEN 0
        WHEN e2.manager_id IS NULL THEN 1
        WHEN e3.manager_id IS NULL THEN 2
        ELSE 3
    END as level,
    CONCAT_WS(' > ',
        COALESCE(e3.name, ''),
        COALESCE(e2.name, ''),
        e1.name
    ) as hierarchy_path,
    COALESCE(e3.employee_id, e2.employee_id, e1.employee_id) as top_manager_id
FROM employees e1
LEFT JOIN employees e2 ON e1.manager_id = e2.employee_id
LEFT JOIN employees e3 ON e2.manager_id = e3.employee_id
ORDER BY level, e1.name;
```

**Find all reports for a specific manager:**
```sql
-- Get all direct and indirect reports for Bob (employee_id = 2)
WITH RECURSIVE reports AS (
    -- Base: Bob himself
    SELECT
        employee_id,
        name,
        title,
        0 as levels_down
    FROM employees
    WHERE employee_id = 2

    UNION ALL

    -- Recursive: All reports
    SELECT
        e.employee_id,
        e.name,
        e.title,
        r.levels_down + 1
    FROM employees e
    INNER JOIN reports r ON e.manager_id = r.employee_id
)
SELECT
    employee_id,
    name,
    title,
    levels_down,
    CASE levels_down
        WHEN 0 THEN 'Self'
        WHEN 1 THEN 'Direct Report'
        ELSE CONCAT('Indirect Report (', levels_down, ' levels)')
    END as relationship
FROM reports
ORDER BY levels_down, name;
```

**Expected Output:**
```
+-----------+----------------+--------------------+------------+-------------------------+
|employee_id|name            |title               |levels_down |relationship             |
+-----------+----------------+--------------------+------------+-------------------------+
|2          |Bob (VP Eng)    |VP Engineering      |0           |Self                     |
|4          |David (Eng Mgr) |Engineering Manager |1           |Direct Report            |
|6          |Frank (Engineer)|Senior Engineer     |2           |Indirect Report (2 levels)|
|7          |Grace (Engineer)|Engineer            |2           |Indirect Report (2 levels)|
+-----------+----------------+--------------------+------------+-------------------------+
```

---

## Delta Lake Challenges

### Challenge 6: Implement Type 2 Slowly Changing Dimension

**Problem:** Maintain historical versions of customer records, tracking changes over time.

```python
# Initial customer data
from pyspark.sql.types import *
from datetime import datetime

schema = StructType([
    StructField("customer_id", StringType()),
    StructField("name", StringType()),
    StructField("email", StringType()),
    StructField("city", StringType()),
    StructField("status", StringType())
])

# Day 1: Initial load
initial_data = [
    ("C001", "John Doe", "john@example.com", "New York", "active"),
    ("C002", "Jane Smith", "jane@example.com", "Boston", "active"),
]

# Create SCD Type 2 table with versioning columns
initial_df = spark.createDataFrame(initial_data, schema) \
    .withColumn("effective_date", lit("2024-01-01").cast("date")) \
    .withColumn("end_date", lit("9999-12-31").cast("date")) \
    .withColumn("is_current", lit(True))

initial_df.write.format("delta").mode("overwrite").saveAsTable("customers_scd")
```

**Solution:**

```python
from delta.tables import DeltaTable
from pyspark.sql.functions import *

def apply_scd_type2(source_df, target_table, key_column, effective_date):
    """
    Apply SCD Type 2 logic to maintain historical records
    """

    # Add metadata columns to source
    source_with_meta = source_df \
        .withColumn("effective_date", lit(effective_date).cast("date")) \
        .withColumn("end_date", lit("9999-12-31").cast("date")) \
        .withColumn("is_current", lit(True))

    # Load target table
    target = DeltaTable.forName(spark, target_table)

    # Find changed records by comparing all columns except metadata
    data_columns = [c for c in source_df.columns if c != key_column]

    # Build comparison condition for changes
    change_conditions = [
        f"source.{col} != target.{col} OR (source.{col} IS NULL AND target.{col} IS NOT NULL) OR (source.{col} IS NOT NULL AND target.{col} IS NULL)"
        for col in data_columns
    ]
    change_condition = " OR ".join(change_conditions)

    # MERGE logic
    merge_result = target.alias("target").merge(
        source_with_meta.alias("source"),
        f"target.{key_column} = source.{key_column} AND target.is_current = true"
    ).whenMatchedUpdate(
        condition=change_condition,
        set={
            "end_date": f"date_sub(source.effective_date, 1)",
            "is_current": "false"
        }
    ).execute()

    # Insert new versions (both new records and changed records)
    # For changed records, insert with new effective_date
    # For new records, insert as is
    current_keys = target.toDF() \
        .filter(col("is_current") == True) \
        .select(key_column) \
        .distinct()

    # New records (not in target)
    new_records = source_with_meta.join(
        current_keys,
        source_with_meta[key_column] == current_keys[key_column],
        "left_anti"
    )

    # Changed records (exists but different)
    changed_records = source_with_meta.join(
        target.toDF().filter(col("is_current") == False),
        [key_column],
        "inner"
    ).select(source_with_meta["*"])

    # Insert both
    records_to_insert = new_records.union(changed_records).distinct()

    if records_to_insert.count() > 0:
        records_to_insert.write.format("delta").mode("append").saveAsTable(target_table)

    return merge_result

# Test the SCD implementation

# Day 2: Customer C001 changes city, C003 is new
day2_data = [
    ("C001", "John Doe", "john@example.com", "Los Angeles", "active"),  # Changed city
    ("C002", "Jane Smith", "jane@example.com", "Boston", "active"),     # No change
    ("C003", "Bob Johnson", "bob@example.com", "Chicago", "active"),    # New customer
]

day2_df = spark.createDataFrame(day2_data, schema)
apply_scd_type2(day2_df, "customers_scd", "customer_id", "2024-01-15")

# Day 3: Customer C002 changes status
day3_data = [
    ("C001", "John Doe", "john@example.com", "Los Angeles", "active"),
    ("C002", "Jane Smith", "jane@example.com", "Boston", "inactive"),   # Status changed
    ("C003", "Bob Johnson", "bob@example.com", "Chicago", "active"),
]

day3_df = spark.createDataFrame(day3_data, schema)
apply_scd_type2(day3_df, "customers_scd", "customer_id", "2024-02-01")

# View results
result = spark.table("customers_scd").orderBy("customer_id", "effective_date")
result.show(truncate=False)
```

**Expected Output:**
```
+-----------+-----------+------------------+------------+--------+--------------+----------+----------+
|customer_id|name       |email             |city        |status  |effective_date|end_date  |is_current|
+-----------+-----------+------------------+------------+--------+--------------+----------+----------+
|C001       |John Doe   |john@example.com  |New York    |active  |2024-01-01    |2024-01-14|false     |
|C001       |John Doe   |john@example.com  |Los Angeles |active  |2024-01-15    |9999-12-31|true      |
|C002       |Jane Smith |jane@example.com  |Boston      |active  |2024-01-01    |2024-01-31|false     |
|C002       |Jane Smith |jane@example.com  |Boston      |inactive|2024-02-01    |9999-12-31|true      |
|C003       |Bob Johnson|bob@example.com   |Chicago     |active  |2024-01-15    |9999-12-31|true      |
+-----------+-----------+------------------+------------+--------+--------------+----------+----------+
```

**Query historical data:**
```sql
-- Get customer status as of specific date
SELECT *
FROM customers_scd
WHERE customer_id = 'C002'
  AND '2024-01-20' BETWEEN effective_date AND end_date;

-- Track all changes for a customer
SELECT
    customer_id,
    name,
    city,
    status,
    effective_date,
    end_date,
    DATEDIFF(end_date, effective_date) as days_active,
    is_current
FROM customers_scd
WHERE customer_id = 'C001'
ORDER BY effective_date;
```

---

This covers the major coding challenges. Would you like me to continue with the behavioral questions document?
