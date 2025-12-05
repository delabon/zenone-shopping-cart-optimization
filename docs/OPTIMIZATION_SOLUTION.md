## Development Notes

After researching optimization approaches, I chose a hybrid solution using a Rules and Weights engine. It's explainable, fast, and tunable - perfect for the MVP while collecting data for future ML approaches.

For devs, I recommend checking the feature tests and the action classes to understand more.

### Implementation Status

**Completed:**
- Algorithm: Weighted scoring with 4 optimization criteria (price, speed, availability, consolidation)
- Backend service: `OptimizeCartAction` with normalization and scoring logic
- Caching: 15-minute TTL with cache tags and observer-based invalidation
- Traceability: `OptimizationSession` and `OptimizationChange` models track all suggestions
- API endpoints: Add-to cart, optimize
- Database: Indexes for query performance

**Not Yet Implemented:**
- API endpoints: Apply, revert, and preset management
- Frontend UI for optimization review and control
- User preference persistence
- Background job processing for large carts (>50 items)
- Tier 2 advanced optimizer
- A/B testing framework for weight adjustments
- Analytics dashboard for acceptance rates

## 1. Algorithm Design

**Approach:** Weighted scoring with rules engine (Implemented)

**Reasoning:** Chose weighted scoring over alternatives because:
- **Explainability:** Users understand "Save $50 + 2 days faster" vs. black-box ML
- **Performance:** O(n×m) complexity achieves <100ms for typical carts
- **Tunability:** Business team can adjust weights without code deployment
- **Data foundation:** Tracks acceptance rates for future ML training

### Two-Tier Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     Cart Optimization Engine                    │
├─────────────────────────────────────────────────────────────────┤
│  ┌─────────────────────┐    ┌─────────────────────────────────┐ │
│  │   Route Decision    │───▶│  Items ≤ 50 AND                 │ │
│  │                     │    │  No complex constraints?        │ │
│  └─────────────────────┘    └─────────────────────────────────┘ │
│              │              ┌───────────┴───────────┐           │
│              │              ▼                       ▼           │
│              │    ┌─────────────────┐    ┌─────────────────────┐│
│              │    │   Tier 1        │    │   Tier 2            ││
│              │    │   QuickWins     │    │   AdvancedOptimizer ││
│              │    │   (80% carts)   │    │   (20% carts)       ││
│              │    │   DONE          │    │   NOT DONE          ││
│              │    └─────────────────┘    └─────────────────────┘│
└─────────────────────────────────────────────────────────────────┘
```

### QuickWins Engine (Implemented)

**Algorithm Flow:**
1. Find primary distributor (most frequent in cart)
2. Fetch alternatives for all products (cached, 15-min TTL)
3. Build scoring context (min/max price and delivery days for normalization)
4. Score each item and its alternatives using weighted formula
5. Suggest changes where score improvement exists
6. Save session and changes for analytics and traceability

**Scoring Formula:**
```php
score = (
    (price_score * price_weight) +
    (speed_score * speed_weight) +
    (availability_score * availability_weight) +
    (consolidation_score * consolidation_weight)
) / total_weight
```

## 2. Parameters & Normalization

**Input Parameters:**
- `price` - Product price from distributor
- `delivery_days` - Estimated delivery time
- `in_stock` - Boolean availability status
- `distributor_id` - For consolidation scoring

**Normalization Strategy (Implemented):**
- `price_score = 1 - ((price - min_price) / price_range)` (0-1 scale, inverted: lower price = higher score)
- `speed_score = 1 - ((delivery_days - min_delivery_days) / delivery_range)` (0-1 scale, inverted: faster = higher score)
- `availability_score = in_stock ? 1.0 : 0.0` (binary)
- `consolidation_score = matches_primary_distributor ? 1.0 : 0.0` (binary)

**Reasoning:** Min-max normalization ensures all factors contribute proportionally regardless of scale differences (e.g., $10 price vs. 3-day delivery).

**Default Weights:**
```json
{
    "price": 0.50,
    "speed": 0.30,
    "availability": 0.15,
    "consolidation": 0.05
}
```

**Weight Presets:**

| Preset      | Price | Speed | Availability | Consolidation | Use Case |
|-------------|-------|-------|--------------|---------------|----------|
| `balanced`  | 0.50 | 0.30 | 0.15         | 0.05 | Default for most practices |
| `budget`    | 0.70 | 0.15 | 0.10         | 0.05 | Cost-conscious practices |
| `urgent`    | 0.20 | 0.60 | 0.15         | 0.05 | Need supplies ASAP |
| `available` | 0.30 | 0.25 | 0.60         | 0.05 | Prefer guaranteed stock |

### Future Tiers

**Tier 2: Advanced Optimizer** - For complex carts (>50 items), cross-distributor bundles, quantity discounts

**Tier 3: ML Optimizer** - Personalized recommendations based on historical acceptance data

## 3. Implementation Architecture

**Approach:** Backend service integrated into cart logic (Implemented)

**Structure:**
- **Service Layer:** `OptimizeCartAction` - Core optimization logic
- **Data Layer:** Models with relationships and indexes
- **API Layer:** RESTful endpoints for frontend integration
- **Cache Layer:** Redis with tag-based invalidation

**Traceability & Explainability (Implemented):**

Every optimization creates:
1. `OptimizationSession` - Tracks algorithm version, weights used, execution time, total savings
2. `OptimizationChange` - Per-item record with original/suggested products, scores, price difference, reason codes

**Reason Codes:**
- `price_savings` - Suggested item is cheaper
- `faster_delivery` - Suggested item ships faster
- `in_stock` - Suggested item available when original isn't
- `consolidation` - Suggested item from primary distributor

**Process Flow:**
```
User clicks "Optimize" → Backend scores alternatives → Returns suggestions with reasons
→ User reviews changes → Accepts/rejects → Session updated with user_accepted flag
```

**UX Integration (Not Done):** Frontend UI pending, but API ready to support:
- Side-by-side comparison of original vs. suggested
- Clear explanation of savings/benefits per item
- Accept all / Accept individual / Reject controls


| Method | Endpoint                            | Description                                       |
|--------|-------------------------------------|---------------------------------------------------|
| `POST` | `/api/cart/items`                   | Add new item to the cart (Done)                   |
| `POST` | `/api/cart/optimize`                | Analyze cart and return optimization suggestions (Done) |
| `POST` | `/api/cart/optimize/apply`          | Apply optimization changes to cart                |
| `POST` | `/api/cart/optimize/revert`         | Revert to original cart state                     |
| `GET`  | `/api/optimization/presets`         | Get available weight presets                      |
| `PUT`  | `/api/user/optimization-preference` | Save user's preferred weight preset               |

## 4. Scalability

**Design for Scale (Implemented):**

**Database Optimization:**
- Composite index: `(product_id, in_stock, price)` - Optimizes alternative lookups
- Eager loading: Prevents N+1 queries when fetching cart items
- Batch queries: Single query fetches all alternatives for all cart products

**Caching Strategy:**
- Cache key: `cart_alternatives:{md5(product_ids)}` - Shared across users with same products
- TTL: 15 minutes - Balances freshness with performance
- Cache tags: `cart_alternatives`, `product_{id}` - Granular invalidation when supported
- Fallback: Works with file/database cache stores without tags
- Observer-based invalidation: Auto-clears cache when `DistributorProduct` updates

**Performance Targets:**

| Cart Size | Approach | Expected Performance |
|-----------|----------|---------------------|
| ≤ 50 items | Synchronous (Done) | <100ms (typical), <500ms (95th percentile) |
| > 50 items | Background job (Not Done) | Async with progress updates |

**Handling Large Catalogs:**
- **100k+ SKUs:** Indexed queries + caching keep lookups fast
- **Multiple distributors:** Batch processing prevents per-distributor queries
- **Concurrent users:** Shared cache keys reduce redundant computations


## Trade-offs & Decisions

**Why Rules Engine:**
- **Explainable** - Users understand "Save $50 + 2 days faster" vs. black-box ML
- **Fast** - O(n×m) complexity handles typical carts in <100ms
- **Tunable** - Business team can adjust weights without code deployment
- **Data collection** - Prepares for future ML improvements

**Alternatives Considered:**
- Multi-Objective Pareto: Too slow for real-time
- Machine Learning: Need training data (future phase)
- Genetic Algorithms: Non-deterministic, slow
- Constraint Satisfaction: Over-engineering for MVP, slow, hard to explain

### Processing Strategy

| Cart Size | Approach | Reason |
|-----------|----------|--------|
| ≤ 50 items | Synchronous | Response time <500ms acceptable |
| > 50 items | Background job | Prevent timeout, better UX with progress |

## 5. UX/UI Design

**Philosophy:** User control (weight presets) with smart automation

**Backend:**
- 4 weight presets: Balanced, Budget, Urgent, Available (Done)
- API returns detailed explanations per suggestion (Done)
- Accept/reject individual changes or all at once
- Revert capability to restore original cart

**Frontend Not Done:**

**Planned UX Flow:**
1. User selects weight preset and clicks "Optimize Cart" button
2. Modal shows optimization results:
   - Total savings summary at top
   - List of suggested changes with:
     - Side-by-side product comparison
     - Visual indicators (savings, faster, in stock)
     - Clear reason explanations
   - Preset selector (Balanced/Budget/Urgent/Available)
3. User can:
   - Accept all changes
   - Accept/reject individual items
   - Switch presets to see different recommendations
   - Revert if they change their mind
4. Preference memory: Save user's preferred preset for future sessions

**Control Mechanisms:**
- Default: "Balanced" preset (50% price, 30% speed, 15% availability, 5% consolidation)
- Override: Dropdown to select different optimization priorities
- Granular control: Checkboxes per suggestion
- Transparency: Always show original vs. suggested with clear metrics

## 6. Deployment & Feedback

**Deployment Strategy (Not Done):**

**Current State:**
- Database migrations: Schema supports versioning (`algorithm_version` field)

**Not Yet Implemented:**
- Feature flag: Can enable/disable via Laravel Pennant
- Backward compatible: Optimization is opt-in, doesn't affect existing cart flow

**Rollout Plan:**
1. **Phase 1:** Internal testing with staff accounts
2. **Phase 2:** Beta release to 5% of users (A/B test)
3. **Phase 3:** Gradual rollout to 25%, 50%, 100% based on metrics

**Feedback Collection:**
- Track acceptance rate per suggestion type (price vs. speed vs. availability)
- Monitor execution time per cart size
- Log user interactions: preset changes, individual accepts/rejects
- Survey prompt after first optimization use

**Adjustment Mechanisms:**
- **Weight tuning:** Update `OptimizationWeights` Sushi model
- **Algorithm versioning:** `algorithm_version` field allows A/B testing different approaches
- **Feature flags:** Quick disable if issues arise
- **Analytics dashboard:** Real-time monitoring of adoption and satisfaction

**Monitoring:**
- Execution time alerts (>500ms for <50 items)
- Cache hit rate tracking
- Acceptance rate by preset type
- Error rate and fallback usage

## 7. Testing

**Test Framework:** Pest (Laravel 12)

**Current Test Coverage:**

| Test Suite | Status | Description                                                                                                        |
|------------|--------|--------------------------------------------------------------------------------------------------------------------|
| `CartOptimizationTest.php` | Passing | Comprehensive tests covering all 4 presets (budget, urgent, available, balanced) with single and multiple products |
| `CartOptimizationCacheTest.php` | Passing | Cache tag validation and reuse verification                                                                        |
| `AddToCartTest.php` | Passing | Cart item creation and validation                                                                                  |

**Test Commands:**
```bash
# Run all tests
vendor/bin/sail test

# Run specific test file
vendor/bin/sail test --filter=CartOptimizationTest
```

**CI/CD Status:**
- GitHub Actions workflow configured (`.github/workflows/tests.yml`)
- PostgreSQL service container for database tests
- Automated test runs on push and pull requests

**Test Coverage Highlights:**
- ✓ All 4 optimization presets tested
- ✓ Single and multi-product cart scenarios
- ✓ Cache hit/miss scenarios
- ✓ Observer-based cache invalidation
- ✓ API authentication and rate limiting
- ✓ Edge cases: out-of-stock items, no alternatives available

**Not Yet Tested:**
- Background job processing for large carts (>50 items)
- Apply/revert optimization endpoints

## 8. Security

**Authentication:**
- Laravel Sanctum token-based authentication
- All optimization endpoints require `auth:sanctum` middleware
- Personal access tokens stored in `personal_access_tokens` table

**Rate Limiting:**
- Cart optimization: 10 requests per minute per user (`throttle:10,1`)
- Add to cart: 40 requests per minute per user (`throttle:40,1`)
- Prevents abuse and ensures fair resource allocation

**Input Validation:**
- Request validation for all API endpoints
- Product ID and quantity validation
- Weight preset validation against allowed values
- SQL injection protection via Eloquent ORM

**Data Privacy:**
- User cart data isolated by `user_id` foreign key
- Cache keys include product IDs only (no user-specific data)
- Optimization sessions linked to user for analytics but not shared

**Cache Security:**
- No sensitive user information stored in cache

## 9. Technical Requirements

**Runtime:**
- PHP: ^8.2
- Laravel: ^12.0
- Node.js: 18+ (for frontend assets)

**Database:**
- PostgreSQL (recommended for production)

**Cache:**
- Redis (recommended) - Supports cache tags for granular invalidation

**Queue:**
- Redis driver (current)
- Redis (recommended for production)
- Required for future background job processing (>50 item carts)

**Dependencies:**
```json
{
  "calebporzio/sushi": "^2.5",
  "inertiajs/inertia-laravel": "^2.0",
  "laravel/sanctum": "^4.0"
}
```

**Development Tools:**
- Pest: ^4.1 (testing framework)
- Laravel Pint: ^1.24 (code formatting)
- Laravel Sail: ^1.49 (Docker development environment)

## 10. API Documentation

**Base URL:** `/api/v1`

**Authentication:** All endpoints require `Authorization: Bearer {token}` header

### Endpoints (Implemented)

#### Add Item to Cart
```http
POST /api/v1/cart/items
Content-Type: application/json
Authorization: Bearer {token}

{
  "distributor_product_id": 123,
  "quantity": 5
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Item added to cart",
  "data": {
    "cart_item_id": 456
  }
}
```

**Rate Limit:** 40 requests/minute

---

#### Optimize Cart
```http
POST /api/v1/cart/optimize
Content-Type: application/json
Authorization: Bearer {token}

{
  "weight_preset": "budget"
}
```

**Request Parameters:**
- `weight_preset` (string, required): One of `balanced`, `budget`, `urgent`, `available`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "session_id": 789,
    "changes": [
      {
        "original": {
          "id": 101,
          "product_name": "White Gloves",
          "distributor_name": "MedSupply Co",
          "price": 10995,
          "quantity": 3,
          "delivery_days": 5,
          "in_stock": true
        },
        "suggested": {
          "id": 102,
          "product_name": "White Gloves",
          "distributor_name": "FastMed Inc",
          "price": 8995,
          "quantity": 3,
          "delivery_days": 3,
          "in_stock": true
        },
        "price_difference": 20.00,
        "delivery_days_difference": 2,
        "reasons": [
          {
            "code": "price_savings",
            "message": "Save $20.00 on this item",
            "impact": "high"
          },
          {
            "code": "faster_delivery",
            "message": "Arrives 2 days sooner",
            "impact": "medium"
          }
        ]
      }
    ],
    "total_savings": 20.00,
    "items_optimized": 1,
    "items_analyzed": 1,
    "execution_time_ms": 87
  }
}
```

**Rate Limit:** 10 requests/minute

**Error Responses:**
- `401 Unauthorized` - Invalid or missing token
- `422 Unprocessable Entity` - Invalid weight preset
- `429 Too Many Requests` - Rate limit exceeded

### Endpoints (Not Yet Implemented)

- `POST /api/v1/cart/optimize/apply` - Apply optimization changes
- `POST /api/v1/cart/optimize/revert` - Revert to original cart
- `GET /api/v1/optimization/presets` - List available weight presets
- `PUT /api/v1/user/optimization-preference` - Save user preference

## 11. Database Schema

**Core Tables:**

```
users
├── id (bigint, PK)
├── name (varchar)
├── email (varchar, unique)
└── timestamps

products
├── id (bigint, PK)
├── name (varchar)
├── sku (varchar, unique)
├── description (text, nullable)
└── timestamps

distributors
├── id (bigint, PK)
├── name (varchar)
├── code (varchar, unique)
└── timestamps

distributor_products
├── id (bigint, PK)
├── product_id (FK → products)
├── distributor_id (FK → distributors)
├── price (integer, cents)
├── in_stock (boolean)
├── stock_quantity (integer)
├── delivery_days (integer)
└── timestamps
└── INDEX: (product_id, in_stock, price)

carts
├── id (bigint, PK)
├── user_id (FK → users)
└── timestamps

cart_items
├── id (bigint, PK)
├── cart_id (FK → carts)
├── distributor_product_id (FK → distributor_products)
├── quantity (integer)
└── timestamps

optimization_sessions
├── id (bigint, PK)
├── cart_id (FK → carts)
├── algorithm_version (varchar)
├── weight_preset (varchar)
├── total_savings (decimal)
├── execution_time_ms (integer)
├── user_accepted (boolean, nullable)
└── timestamps

optimization_changes
├── id (bigint, PK)
├── optimization_session_id (FK → optimization_sessions)
├── cart_item_id (FK → cart_items)
├── original_distributor_product_id (FK → distributor_products)
├── suggested_distributor_product_id (FK → distributor_products)
├── original_score (decimal)
├── suggested_score (decimal)
├── price_difference (decimal)
├── delivery_days_difference (integer)
├── reason_codes (json)
└── timestamps
```

**Key Indexes:**
- `distributor_products(product_id, in_stock, price)` - Optimizes alternative lookups
- `cart_items(cart_id)` - Fast cart retrieval
- `optimization_sessions(cart_id)` - Session history queries

**Relationships:**
- Cart → User (1:1)
- Cart → CartItems (1:N)
- CartItem → DistributorProduct (N:1)
- OptimizationSession → Cart (N:1)
- OptimizationChange → OptimizationSession (N:1)

## 12. Configuration

**Environment Variables Required:**

```bash
# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=zenone
DB_USERNAME=postgres
DB_PASSWORD=secret

# Cache (Redis recommended)
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# Queue
QUEUE_CONNECTION=redis

# API
SANCTUM_STATEFUL_DOMAINS=localhost:3000
```

**Cache Configuration:**
- Default TTL: 15 minutes (900 seconds)
- Cache prefix: `zenone_` (recommended for shared Redis)
- Tag support: Required for optimal performance

**Optimization Settings:**
- Synchronous threshold: 50 items
- Background job threshold: >50 items (not yet implemented)
- Default weight preset: `balanced`

## 13. Error Handling & Edge Cases

**Implemented Handling:**

| Scenario | Behavior |
|----------|----------|
| No alternatives available | Returns empty `changes` array, `items_optimized: 0` |
| All items out of stock | Suggests in-stock alternatives if available |
| Cache unavailable | Falls back to direct database queries |
| Cache tags unsupported | Uses cache without tags (file/database drivers) |
| Invalid weight preset | Returns 422 validation error |
| Empty cart | Returns 422 validation error |

**Observer-Based Cache Invalidation:**
- `DistributorProduct` model has observer
- Automatically clears cache on `updated`, `created`, `deleted` events
- Invalidates specific product cache tags when supported

**Fallback Strategies:**
- Cache miss → Direct database query
- No score improvement → No suggestion made
- Normalization edge case (min = max) → Score defaults to 0.5

**Not Yet Handled:**
- Concurrent cart modifications during optimization
- Product price changes mid-optimization
- Distributor availability changes during session
- Network timeout for large cart queries
- Database connection failures

## 14. Known Issues & Limitations

**Current Limitations:**

1. **Synchronous Processing Only**
   - Carts >50 items may experience slow response times
   - Background job processing not yet implemented
   - No progress indicator for long-running optimizations

2. **No Discount Support**

3. **Single-Distributor Consolidation**
   - Only considers primary distributor (most frequent in cart)
   - Doesn't optimize for multi-distributor shipping cost reduction

4. **Cache Invalidation Granularity**
   - Full cache clear on any product update
   - Could be more selective (only affected products)

5. **No A/B Testing Framework**
   - Can't test different weight configurations in production
   - Algorithm version tracking exists but not utilized

**Technical Debt:**

- Frontend UI completely missing
- Apply/revert endpoints not implemented
- User preference persistence not built
- Analytics dashboard not created
- Feature flag system not integrated

**Performance Concerns:**

- Large catalogs (>100k SKUs) not yet tested at scale
- Cache memory usage not monitored
- No query performance benchmarks for production data volumes

**Browser/Client Compatibility:**
- API-only, no frontend constraints yet
- Inertia.js + Vue.js planned for UI

## Future Roadmap

### Phase 2: Advanced Optimizer
- Tier 2 algorithm for complex carts (>50 items)
- Cross-distributor bundle optimization
- Shipping tier consolidation
- Quantity discount optimization

### Phase 3: ML Integration
- Train models on optimization acceptance data
- Personalized weight recommendations per user
- Predictive suggestions based on order history
- Anomaly detection for pricing errors
