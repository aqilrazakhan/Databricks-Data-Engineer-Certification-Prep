# Common DAX Patterns and Formulas

Standard DAX patterns used across all Power BI portfolio projects.

## Basic Aggregations

### Total/Sum
```dax
Total Revenue = SUM(Orders[Amount])
```

### Count
```dax
Total Orders = COUNTROWS(Orders)
Total Customers = DISTINCTCOUNT(Orders[CustomerID])
```

### Average
```dax
Average Order Value = AVERAGE(Orders[Amount])
```

### Min/Max
```dax
Max Order = MAX(Orders[Amount])
Min Order = MIN(Orders[Amount])
```

## Time Intelligence

### Year-to-Date (YTD)
```dax
Revenue YTD =
TOTALYTD(
    [Total Revenue],
    'Calendar'[Date]
)
```

### Previous Year
```dax
Revenue LY =
CALCULATE(
    [Total Revenue],
    SAMEPERIODLASTYEAR('Calendar'[Date])
)
```

### Year-over-Year Growth
```dax
Revenue YoY% =
VAR CurrentYear = [Total Revenue]
VAR PreviousYear = [Revenue LY]
RETURN
    DIVIDE(CurrentYear - PreviousYear, PreviousYear, 0)
```

### Month-to-Date (MTD)
```dax
Revenue MTD =
TOTALMTD(
    [Total Revenue],
    'Calendar'[Date]
)
```

### Rolling 3 Months
```dax
Revenue Rolling 3M =
CALCULATE(
    [Total Revenue],
    DATESINPERIOD(
        'Calendar'[Date],
        LASTDATE('Calendar'[Date]),
        -3,
        MONTH
    )
)
```

## Ratios and Percentages

### Division with Zero Handling
```dax
Conversion Rate =
VAR Orders = [Total Orders]
VAR Visitors = [Total Visitors]
RETURN
    DIVIDE(Orders, Visitors, 0)
```

### Percentage of Total
```dax
% of Total Revenue =
DIVIDE(
    [Total Revenue],
    CALCULATE([Total Revenue], ALL(Products)),
    0
)
```

### Margin Calculation
```dax
Profit Margin % =
VAR Revenue = [Total Revenue]
VAR Cost = [Total Cost]
VAR Profit = Revenue - Cost
RETURN
    DIVIDE(Profit, Revenue, 0)
```

## Conditional Logic

### SWITCH Statement
```dax
Performance Category =
SWITCH(
    TRUE(),
    [Revenue] >= 100000, "Excellent",
    [Revenue] >= 50000, "Good",
    [Revenue] >= 10000, "Fair",
    "Needs Improvement"
)
```

### IF Statement
```dax
Revenue vs Target =
IF(
    [Total Revenue] >= [Target Revenue],
    "Above Target",
    "Below Target"
)
```

## Context Modification

### Remove Filters
```dax
All Products Revenue =
CALCULATE(
    [Total Revenue],
    ALL(Products)
)
```

### Apply Filter
```dax
VIP Customer Revenue =
CALCULATE(
    [Total Revenue],
    Customers[Segment] = "VIP"
)
```

### Multiple Filters
```dax
High Value Orders =
CALCULATE(
    [Total Orders],
    Orders[Amount] > 1000,
    Orders[Status] = "Completed"
)
```

## Iterator Functions

### SUMX (Row-by-Row Calculation)
```dax
Total Profit =
SUMX(
    Orders,
    Orders[Quantity] * (Orders[UnitPrice] - Orders[UnitCost])
)
```

### AVERAGEX
```dax
Avg Items Per Order =
AVERAGEX(
    Orders,
    CALCULATE(COUNTROWS(Order_Items))
)
```

### RANKX
```dax
Product Rank by Revenue =
RANKX(
    ALL(Products[ProductName]),
    [Total Revenue],
    ,
    DESC,
    Dense
)
```

## Advanced Patterns

### Customer Lifetime Value
```dax
Customer Lifetime Value =
DIVIDE(
    [Total Revenue],
    [Total Customers],
    0
)
```

### Cart Abandonment Rate
```dax
Cart Abandonment Rate =
VAR AddedToCart =
    CALCULATE(
        COUNTROWS(Website_Traffic),
        Website_Traffic[AddedToCart] = "Yes"
    )
VAR CartNotPurchased =
    CALCULATE(
        COUNTROWS(Website_Traffic),
        Website_Traffic[AddedToCart] = "Yes",
        ISBLANK(Website_Traffic[OrderID])
    )
RETURN
    DIVIDE(CartNotPurchased, AddedToCart, 0)
```

### Retention Rate (Cohort-Based)
```dax
Customer Retention Rate =
VAR CustomersLastPeriod =
    CALCULATE(
        DISTINCTCOUNT(Orders[CustomerID]),
        DATEADD('Calendar'[Date], -1, MONTH)
    )
VAR ReturningCustomers =
    CALCULATE(
        DISTINCTCOUNT(Orders[CustomerID]),
        FILTER(
            ALL(Orders),
            CALCULATE(
                COUNTROWS(Orders),
                DATEADD('Calendar'[Date], -1, MONTH)
            ) > 0
        )
    )
RETURN
    DIVIDE(ReturningCustomers, CustomersLastPeriod, 0)
```

## Conditional Formatting Measures

### Traffic Light Indicator
```dax
Conversion Rate Traffic Light =
SWITCH(
    TRUE(),
    [Conversion Rate] >= 0.05, 1,  // Green
    [Conversion Rate] >= 0.03, 2,  // Yellow
    3                              // Red
)
```

### Dynamic Title
```dax
Revenue Card Title =
"Total Revenue: " &
FORMAT([Total Revenue], "$#,##0") &
" (" &
FORMAT([Revenue YoY%], "+0.0%;-0.0%") &
" YoY)"
```

## Format Strings

Common format string patterns:

- **Currency:** `"$#,##0"`
- **Currency with decimals:** `"$#,##0.00"`
- **Percentage:** `"0.0%"`
- **Percentage (2 decimals):** `"0.00%"`
- **Number with comma:** `"#,##0"`
- **Number with decimals:** `"#,##0.00"`
- **Compact (K, M, B):** `"$#,##0,.0K"`
