# TEAM7 TechAxis

TechAxis is a database-driven e-commerce platform built around a gaming and technology retail use case. The system includes a customer storefront, order and checkout flows, product comparison, reviews, support tickets, inventory workflows, and an admin operations area.

## Contributors

- `@RayyanW786` - Student ID: `240212160`
- `@PrinceRyu` - Student ID: `240365363`
- `@claymaneuan44` - Student ID: `240051695`
- `@Haaris06` - Student ID: `240118437`
- `@christopher005bot` - Student ID: `230405950`
- `@davidode7` - Student ID: `240128159`
- `@SynoMu` - Student ID: `240132891`
- `@DeepakSharma45` - Student ID: `250072620`

## Project Overview

This project was developed to deliver a realistic online store experience with:

- a customer-facing storefront
- secure account and authentication flows
- PostgreSQL-backed business logic and validation
- inventory, order, discount, review, and support workflows
- an administrator dashboard for operational tasks

## Core Features

### Storefront

- product listing with search, filters, and guided query building
- product detail pages with galleries, variants, stock messaging, reviews, and comparison
- cart and checkout with discount code support
- order history and order detail pages
- support tickets and refund-request flows linked to purchased items

### Admin

- product, customer, inventory, discount, review, report, and ticket management
- order processing and shipment management
- stock transaction logging and inventory alerts
- storefront access using the same account

### Database / Backend

- PostgreSQL schema with constraints, indexes, triggers, and helper functions
- Laravel controllers and models for role-based workflows
- stock-aware and discount-aware checkout behaviour

## Tech Stack

- Backend: Laravel / PHP
- Frontend: Blade, JavaScript, CSS
- Database: PostgreSQL
- Optional services: Redis

## Repository Layout

- `src/` - main Laravel application
- `schema.sql` - PostgreSQL schema, triggers, functions, and supporting SQL
- `dml.sql` - data manipulation helpers / inserts
- `setup-guide.md` - local setup and environment instructions

## Setup

For full setup and run instructions, see [setup-guide.md](setup-guide.md).

## Repository Standards

- [Code of Conduct](CODE_OF_CONDUCT.md)
- [Contributing Guide](CONTRIBUTING.md)
- [Security Policy](SECURITY.md)

