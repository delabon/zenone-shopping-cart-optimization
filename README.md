# Zenone - Shopping Cart Optimization

A smart cart optimization system that helps dental practices make better purchasing decisions by balancing price, delivery speed, availability, and distributor consolidation.

## Solution Overview

This implementation uses a **weighted scoring engine** that evaluates cart items across four optimization criteria:

- **Price** (50%) - Find cost-effective alternatives
- **Speed** (30%) - Prioritize faster delivery options  
- **Availability** (15%) - Ensure products are in stock
- **Consolidation** (5%) - Minimize number of distributors

The system provides full traceability, explainable recommendations, and is designed to scale to 100k+ SKUs with intelligent caching and database optimization.

**For detailed solution design, architecture, and reasoning, see [SOLUTION_DESIGN.md](docs/SOLUTION_DESIGN.md)**

## Tech Stack
- PHP 8.4 & Laravel 12
- Web Components: Tailwind 4, Vue 3, Inertia 2, Vite
- DBs: PostgreSQL 16, Redis (Pub/Sub, Cache, & Queues)
- Testing: Pest 4 (+ browser testing)
- CI: GitHub Actions
- Code Quality: Pint
- Dev tools: Sail (Docker)

### Installation

1. Clone the repo

```shell
git clone git@github.com:delabon/zenone-shopping-cart-optimization.git
cd zenone-shopping-cart-optimization
```

2. Setup

```shell
composer install
vendor/bin/sail up --build -d
cp .env.example .env
vendor/bin/sail artisan key:generate
```

3. Run the migration scripts

```shell
vendor/bin/sail artisan migrate
vendor/bin/sail artisan db:seed
```

### Run all tests

```shell
vendor/bin/sail test
```
