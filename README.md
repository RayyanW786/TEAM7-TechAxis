# TEAM7 TechAxis

TechAxis is an e-commerce web platform.  
The system focuses on a gaming and technology retail use case, covering browsing, comparison, purchase flow, and role-based management.

## Project Overview

The project was designed to deliver a realistic online store experience with:

- a customer-facing storefront
- secure account and authentication flows
- product, inventory, and order data management
- an administrator-capable backend workflow

The implementation prioritises practical user journeys (discover -> compare -> buy) and robust database-driven behaviour.

## What The System Offers

### Storefront Experience

- Product listing with search and filter support
- Product detail pages with image galleries and variant selection
- Product comparison workflow (select two products and compare key attributes side-by-side)
- Cart and checkout flow with order placement

### Account and Access

- Registration and login flows
- Role-based routing and behaviour (`customer` and `admin`)
- Strong password requirements enforced server-side

### Operational Features

- PostgreSQL-powered product search helpers
- Order and inventory-related database functions/triggers
- Address and order handling for checkout

## Technical Stack

- Backend: Laravel (PHP)
- Frontend: Blade templates, JavaScript, CSS
- Database: PostgreSQL
- Optional infrastructure support: Redis (documented in setup guide)

## Data and Architecture Notes

- Core domain entities include products, variants, categories, brands, carts, orders, inventory transactions, and reviews.
- Business logic is split across Laravel controllers/models and PostgreSQL functions for selected workflows.
- SQL schema includes constraints, indexes, triggers, and helper functions to enforce consistency and support performance.

## Repository Layout

- `src/` main Laravel application
- `schema.sql` PostgreSQL schema and DB functions
- `dml.sql` data manipulation helpers / sample inserts
- `setup-guide.md` environment setup and run instructions

## Setup Reference

This README is examiner-facing and intentionally project-focused.  
For full local setup and run instructions, see: `setup-guide.md`.
