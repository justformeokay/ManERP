# ManERP — System Architecture & Design Document

## 1. Frontend Layout Design

### Stack
- **Laravel Blade** — Server-side rendering, fast page loads
- **TailwindCSS 4** — Utility-first CSS via Vite plugin
- **Alpine.js** — Lightweight JS for interactivity (modals, dropdowns, sidebar toggle)

### Layout Structure

```
┌────────────────────────────────────────────────────┐
│ [Sidebar]          │ [Top Navbar: Search | Notif | User]   │
│                    │───────────────────────────────────────│
│ Logo: ManERP       │ Breadcrumbs                           │
│                    │ Page Title + Actions                  │
│ ─── Main ───       │                                       │
│ Dashboard          │ ┌─────────┐ ┌─────────┐              │
│                    │ │ Stat    │ │ Stat    │ ...           │
│ ─── Operations ─── │ │ Card    │ │ Card    │              │
│ CRM / Clients      │ └─────────┘ └─────────┘              │
│ Projects           │                                       │
│ Inventory          │ ┌─────────────────────────┐           │
│ Manufacturing      │ │ Data Table              │           │
│                    │ │ (Recent Orders, etc.)   │           │
│ ─── Commerce ───   │ └─────────────────────────┘           │
│ Sales              │                                       │
│ Purchasing         │                                       │
│                    │                                       │
│ ─── Analytics ──── │                                       │
│ Reports            │                                       │
│ Settings           │                                       │
│                    │                                       │
│ [◀ Collapse]       │                                       │
└────────────────────────────────────────────────────┘
```

### Key UX Features
- Sidebar: Collapsible (desktop), slide-out (mobile)
- Breadcrumbs on every page
- Flash messages with auto-dismiss
- Modal container for AJAX dialogs
- Max 2-3 clicks to any core feature

---

## 2. Blade Code Structure

```
resources/views/
├── layouts/
│   └── app.blade.php           ← Master layout (html, head, body, sidebar + nav + main)
├── partials/
│   ├── sidebar.blade.php       ← Sidebar navigation (collapsible, data-driven menu)
│   └── navbar.blade.php        ← Top bar (search, notifications, user dropdown)
├── components/
│   ├── stat-card.blade.php     ← KPI stat card (title, value, trend, icon)
│   ├── data-table.blade.php    ← Reusable table (headers, rows, actions)
│   ├── button.blade.php        ← Button/link (primary, secondary, danger, ghost)
│   └── modal.blade.php         ← Alpine-powered modal (teleported to body)
└── dashboard.blade.php          ← Dashboard page
```

### Service Provider
- `ViewServiceProvider` — Injects `$sidebarMenu` array into sidebar via View Composer.
  Menu items are defined centrally; adding a new module = adding one array entry.

---

## 3. UI Components

| Component   | File                        | Props                                      | Usage                           |
|-------------|-----------------------------|--------------------------------------------|----------------------------------|
| Stat Card   | `components/stat-card`      | title, value, trend, trendUp, icon, iconBg | KPI metrics on dashboard         |
| Data Table  | `components/data-table`     | headers[], rows[], actions, tableTitle      | All list/index views             |
| Button      | `components/button`         | label, type, href, icon, buttonType        | All action triggers              |
| Modal       | `components/modal`          | title, maxWidth, slot                      | Create/Edit forms, confirmations |

---

## 4. Module Explanation

### CRM / Clients
Manages **customer and lead** information. Stores contact details, company info, and status tracking. Connected to Sales Orders and Projects.

### Projects
Tracks **client projects** with budget, timeline, and assigned manager. Links to Sales Orders, Purchase Orders, and Manufacturing Orders for cost allocation.

### Inventory (Warehouse)
Core module. Manages **products, warehouses, stock levels, and stock movements**. Every stock change (sale, purchase, production) creates a `stock_movement` record for full audit trail. Supports multiple warehouses with reserved quantity tracking.

### Manufacturing
Manages **production workflow**: Bill of Materials (BOM) defines recipes, Manufacturing Orders consume raw materials and produce finished goods. Tracks planned vs. actual production quantities and dates.

### Sales
Handles **outbound orders** to clients. Sales Orders reserve inventory on confirmation and deduct stock on shipment. Supports partial delivery tracking.

### Purchasing
Handles **inbound orders** from suppliers. Purchase Orders add stock on receipt. Supports partial receiving. Links to Inventory for automatic stock updates.

### Reports
Cross-module analytics. Pulls data from Sales, Purchasing, Inventory, and Manufacturing for business intelligence dashboards.

### Module Connections

```
              ┌──────────────┐
              │   Projects   │
              └──────┬───────┘
                     │ links to
        ┌────────────┼────────────┐
        ▼            ▼            ▼
┌──────────────┐ ┌──────────┐ ┌──────────────┐
│    Sales     │ │ Purchase │ │Manufacturing │
│   Orders     │ │  Orders  │ │   Orders     │
└──────┬───────┘ └────┬─────┘ └──────┬───────┘
       │              │              │
       │  stock out   │  stock in    │ consume in / produce out
       ▼              ▼              ▼
┌─────────────────────────────────────────┐
│            INVENTORY                     │
│  (inventory_stocks + stock_movements)    │
└─────────────────────────────────────────┘
       ▲                              ▲
       │                              │
┌──────────┐                   ┌──────────┐
│  Clients │                   │ Suppliers│
└──────────┘                   └──────────┘
```

---

## 5. Database Schema

### Table: `users` (extended)
| Column            | Type          | Notes                    |
|-------------------|---------------|--------------------------|
| id                | bigint PK     | Auto-increment           |
| name              | varchar       |                          |
| role              | varchar(50)   | Default: 'user'          |
| email             | varchar       | Unique                   |
| phone             | varchar(30)   | Nullable                 |
| is_active         | boolean       | Default: true            |
| email_verified_at | timestamp     | Nullable                 |
| password          | varchar       |                          |
| remember_token    | varchar       |                          |
| timestamps        |               |                          |

### Table: `clients`
| Column     | Type          | Notes                               |
|------------|---------------|--------------------------------------|
| id         | bigint PK     |                                      |
| code       | varchar(30)   | Unique (e.g., CLT-001)              |
| name       | varchar       |                                      |
| email      | varchar       | Nullable                             |
| phone      | varchar(30)   | Nullable                             |
| company    | varchar       | Nullable                             |
| tax_id     | varchar(50)   | Nullable                             |
| address    | text          | Nullable                             |
| city       | varchar(100)  | Nullable                             |
| country    | varchar(100)  | Nullable                             |
| type       | enum          | customer, lead, prospect             |
| status     | enum          | active, inactive                     |
| notes      | text          | Nullable                             |
| timestamps |               |                                      |
| deleted_at | timestamp     | Soft delete                          |

### Table: `categories`
| Column      | Type      | Notes                     |
|-------------|-----------|---------------------------|
| id          | bigint PK |                           |
| name        | varchar   |                           |
| slug        | varchar   | Unique                    |
| parent_id   | FK → categories | Nullable, self-ref    |
| description | text      | Nullable                  |
| timestamps  |           |                           |

### Table: `products`
| Column      | Type          | Notes                                           |
|-------------|---------------|-------------------------------------------------|
| id          | bigint PK     |                                                 |
| sku         | varchar(50)   | Unique                                          |
| name        | varchar       |                                                 |
| description | text          | Nullable                                        |
| category_id | FK → categories | Nullable                                     |
| type        | enum          | raw_material, semi_finished, finished_good, consumable |
| unit        | varchar(20)   | Default: 'pcs'                                  |
| cost_price  | decimal(12,2) |                                                 |
| sell_price  | decimal(12,2) |                                                 |
| min_stock   | unsigned int  | For low-stock alerts                            |
| is_active   | boolean       |                                                 |
| timestamps  |               |                                                 |
| deleted_at  | timestamp     | Soft delete                                     |

### Table: `suppliers`
| Column     | Type          | Notes                |
|------------|---------------|----------------------|
| id         | bigint PK     |                      |
| code       | varchar(30)   | Unique               |
| name       | varchar       |                      |
| email      | varchar       | Nullable             |
| phone      | varchar(30)   | Nullable             |
| company    | varchar       | Nullable             |
| tax_id     | varchar(50)   | Nullable             |
| address    | text          | Nullable             |
| city       | varchar(100)  | Nullable             |
| country    | varchar(100)  | Nullable             |
| status     | enum          | active, inactive     |
| notes      | text          | Nullable             |
| timestamps |               |                      |
| deleted_at | timestamp     | Soft delete          |

### Table: `warehouses`
| Column     | Type        | Notes          |
|------------|-------------|----------------|
| id         | bigint PK   |                |
| code       | varchar(20) | Unique         |
| name       | varchar     |                |
| address    | text        | Nullable       |
| is_default | boolean     | Default: false |
| is_active  | boolean     | Default: true  |
| timestamps |             |                |

### Table: `inventory_stocks`
| Column            | Type          | Notes                                   |
|-------------------|---------------|-----------------------------------------|
| id                | bigint PK     |                                         |
| product_id        | FK → products | Cascade delete                          |
| warehouse_id      | FK → warehouses | Cascade delete                        |
| quantity          | decimal(12,2) | Available stock                         |
| reserved_quantity | decimal(12,2) | Reserved by confirmed sales orders      |
| timestamps        |               |                                         |
| **UNIQUE**        |               | (product_id, warehouse_id)              |

### Table: `stock_movements`
| Column         | Type          | Notes                                     |
|----------------|---------------|-------------------------------------------|
| id             | bigint PK     |                                           |
| product_id     | FK → products |                                           |
| warehouse_id   | FK → warehouses |                                         |
| type           | enum          | in, out, transfer, adjustment             |
| quantity       | decimal(12,2) | Signed quantity                           |
| balance_after  | decimal(12,2) | Running balance for audit                 |
| reference_type | varchar(50)   | Polymorphic: sales_order, purchase_order, etc. |
| reference_id   | bigint        | FK to the source document                 |
| notes          | text          | Nullable                                  |
| created_by     | FK → users    | Nullable                                  |
| timestamps     |               |                                           |

### Table: `purchase_orders`
| Column        | Type          | Notes                                    |
|---------------|---------------|------------------------------------------|
| id            | bigint PK     |                                          |
| number        | varchar(30)   | Unique (e.g., PO-2026-001)              |
| supplier_id   | FK → suppliers | Restrict delete                         |
| warehouse_id  | FK → warehouses | Restrict delete                        |
| project_id    | FK → projects | Nullable                                 |
| status        | enum          | draft, confirmed, partial, received, cancelled |
| order_date    | date          |                                          |
| expected_date | date          | Nullable                                 |
| subtotal      | decimal(14,2) |                                          |
| tax_amount    | decimal(14,2) |                                          |
| total         | decimal(14,2) |                                          |
| notes         | text          | Nullable                                 |
| created_by    | FK → users    | Nullable                                 |
| timestamps    |               |                                          |
| deleted_at    | timestamp     | Soft delete                              |

### Table: `purchase_order_items`
| Column            | Type          | Notes               |
|-------------------|---------------|----------------------|
| id                | bigint PK     |                      |
| purchase_order_id | FK → purchase_orders | Cascade delete |
| product_id        | FK → products | Restrict delete      |
| quantity          | decimal(12,2) | Ordered qty          |
| received_quantity | decimal(12,2) | Default: 0           |
| unit_price        | decimal(12,2) |                      |
| total             | decimal(14,2) |                      |
| timestamps        |               |                      |

### Table: `sales_orders`
| Column        | Type          | Notes                                              |
|---------------|---------------|----------------------------------------------------|
| id            | bigint PK     |                                                    |
| number        | varchar(30)   | Unique (e.g., SO-2026-001)                        |
| client_id     | FK → clients  | Restrict delete                                    |
| warehouse_id  | FK → warehouses | Restrict delete                                  |
| project_id    | FK → projects | Nullable                                           |
| status        | enum          | draft, confirmed, processing, partial, shipped, completed, cancelled |
| order_date    | date          |                                                    |
| delivery_date | date          | Nullable                                           |
| subtotal      | decimal(14,2) |                                                    |
| tax_amount    | decimal(14,2) |                                                    |
| discount      | decimal(14,2) |                                                    |
| total         | decimal(14,2) |                                                    |
| notes         | text          | Nullable                                           |
| created_by    | FK → users    | Nullable                                           |
| timestamps    |               |                                                    |
| deleted_at    | timestamp     | Soft delete                                        |

### Table: `sales_order_items`
| Column             | Type          | Notes               |
|--------------------|---------------|----------------------|
| id                 | bigint PK     |                      |
| sales_order_id     | FK → sales_orders | Cascade delete   |
| product_id         | FK → products | Restrict delete      |
| quantity           | decimal(12,2) |                      |
| delivered_quantity | decimal(12,2) | Default: 0           |
| unit_price         | decimal(12,2) |                      |
| discount           | decimal(12,2) |                      |
| total              | decimal(14,2) |                      |
| timestamps         |               |                      |

### Table: `bill_of_materials`
| Column          | Type          | Notes                      |
|-----------------|---------------|----------------------------|
| id              | bigint PK     |                            |
| product_id      | FK → products | Output (finished) product  |
| name            | varchar       |                            |
| description     | text          | Nullable                   |
| output_quantity | decimal(12,2) | Default: 1                 |
| is_active       | boolean       |                            |
| timestamps      |               |                            |

### Table: `bom_items`
| Column     | Type           | Notes                        |
|------------|----------------|------------------------------|
| id         | bigint PK      |                              |
| bom_id     | FK → bill_of_materials | Cascade delete         |
| product_id | FK → products  | Input (raw material) product |
| quantity   | decimal(12,4)  | Required qty per BOM output  |
| notes      | text           | Nullable                     |
| timestamps |                |                              |

### Table: `manufacturing_orders`
| Column           | Type          | Notes                                    |
|------------------|---------------|------------------------------------------|
| id               | bigint PK     |                                          |
| number           | varchar(30)   | Unique (e.g., MO-2026-001)              |
| bom_id           | FK → bill_of_materials | Restrict delete                 |
| product_id       | FK → products | Output product                           |
| warehouse_id     | FK → warehouses | Restrict delete                        |
| project_id       | FK → projects | Nullable                                 |
| planned_quantity | decimal(12,2) |                                          |
| produced_quantity| decimal(12,2) | Default: 0                               |
| status           | enum          | draft, confirmed, in_progress, done, cancelled |
| planned_start    | date          | Nullable                                 |
| planned_end      | date          | Nullable                                 |
| actual_start     | date          | Nullable                                 |
| actual_end       | date          | Nullable                                 |
| priority         | enum          | low, normal, high, urgent                |
| notes            | text          | Nullable                                 |
| created_by       | FK → users    | Nullable                                 |
| timestamps       |               |                                          |
| deleted_at       | timestamp     | Soft delete                              |

---

## 6. Data Flow Explanation

### Purchasing → Inventory (Stock In)

```
1. User creates Purchase Order (PO) with line items → status: draft
2. PO is confirmed → status: confirmed
3. Goods arrive → User records receipt per item (partial or full)
4. For each received item:
   a. inventory_stocks.quantity += received_qty
   b. stock_movements record created (type: 'in', reference: purchase_order)
   c. purchase_order_items.received_quantity updated
5. When all items fully received → PO status: received
```

### Sales → Inventory (Stock Out)

```
1. User creates Sales Order (SO) with line items → status: draft
2. SO is confirmed → status: confirmed
   a. inventory_stocks.reserved_quantity += line item qty (soft reservation)
3. Goods shipped → User records delivery per item (partial or full)
4. For each delivered item:
   a. inventory_stocks.quantity -= delivered_qty
   b. inventory_stocks.reserved_quantity -= delivered_qty
   c. stock_movements record created (type: 'out', reference: sales_order)
   d. sales_order_items.delivered_quantity updated
5. When all items fully delivered → SO status: completed
```

### Inventory ↔ Manufacturing

```
1. User creates Manufacturing Order (MO) from a BOM → status: draft
2. MO confirmed → raw material requirements calculated from BOM × planned_quantity
3. Production starts → status: in_progress
   a. Raw materials consumed:
      - inventory_stocks.quantity -= (bom_item.qty × MO.planned_quantity) for each BOM item
      - stock_movements records created (type: 'out', reference: manufacturing_order)
4. Production completes → status: done
   a. Finished goods produced:
      - inventory_stocks.quantity += MO.produced_quantity
      - stock_movements record created (type: 'in', reference: manufacturing_order)
```

### Cross-Module Summary

| Event                    | Inventory Effect              | Movement Type |
|--------------------------|-------------------------------|---------------|
| Purchase received        | Stock increases               | `in`          |
| Sale shipped             | Stock decreases               | `out`         |
| Manufacturing consumes   | Raw material stock decreases  | `out`         |
| Manufacturing produces   | Finished good stock increases | `in`          |
| Stock adjustment         | Manual correction             | `adjustment`  |
| Warehouse transfer       | Move between warehouses       | `transfer`    |
