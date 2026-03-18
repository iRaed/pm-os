<div align="center">

# 🏢 PM-OS — Property Management Operating System

### منصة إدارة الأملاك والعقارات

**The most comprehensive open-source Property Management SaaS platform built for the Saudi Arabian market**

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white)](https://vuejs.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-316192?style=for-the-badge&logo=postgresql&logoColor=white)](https://postgresql.org)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](https://opensource.org/licenses/MIT)

[English](#overview) · [العربية](#نظرة-عامة) · [Quick Start](#-quick-start) · [Architecture](#-architecture) · [Roadmap](#-roadmap)

</div>

---

## Overview

PM-OS is a **Modular Monolith SaaS platform** designed to digitize and automate the entire lifecycle of property management — from onboarding a property to strategic portfolio optimization.

Built specifically for the **Saudi Arabian real estate market**, with full Arabic RTL support, Ejar integration readiness, ZATCA e-invoicing compliance, and Sadad payment gateway compatibility.

## نظرة عامة

نظام تشغيل سحابي متكامل لشركات إدارة الأملاك — مبني بالكامل على Laravel مع دعم كامل للعربية، نظام Multi-Tenant لعزل بيانات كل شركة، و 17+ وحدة تغطي دورة حياة العقار المُدار بالكامل.

---

## ✨ Key Features

**Foundation & Onboarding** — Multi-type property support (9 asset types), digital onboarding with checklists, owner management with IBAN tracking, document encryption (AES-256).

**Leasing & Occupancy** — Full lifecycle from vacancy to signed contract, smart pricing engine, Ejar integration-ready, electronic signatures.

**Collection & Finance** — Automated invoicing, multi-channel reminders (SMS/WhatsApp/Email), aging reports with auto-escalation, ZATCA e-invoicing, owner P&L statements, CapEx planning.

**Maintenance & Operations** — Work orders with SLA tracking (4h–168h by priority), preventive maintenance scheduler, contractor ratings, before/after photos, QA checklists.

**AI-Powered Intelligence** — Monthly executive summaries in Arabic, smart listing descriptions, portfolio risk analysis, tenant scoring, predictive maintenance.

**Governance & HOA** — Regulatory compliance checklists, insurance tracking, HOA meetings with voting, risk register, full audit trail.

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 11 (Modular Monolith) |
| Frontend | Vue 3 + Inertia.js + Tailwind CSS (RTL) |
| Database | PostgreSQL 16 + PostGIS |
| Cache/Queue | Redis + Laravel Horizon |
| WebSocket | Laravel Reverb |
| Multi-Tenancy | Stancl/Tenancy (DB-per-Tenant) |
| Auth & RBAC | Sanctum + Spatie Permission (7 roles, 70+ permissions) |
| AI | OpenAI API (pluggable interface) |
| Containers | Docker Compose |

---

## 🚀 Quick Start

```bash
git clone https://github.com/YOUR_USERNAME/pm-os.git
cd pm-os
cp .env.example .env
docker compose up -d
docker compose exec app composer install
docker compose exec app npm install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed --class=PlanSeeder
docker compose exec app php artisan tenant:create demo "شركة الواحة" demo.pmos.test
docker compose exec app npm run build
```

Access: `http://demo.pmos.test:8000` — Email: `admin@pmos.test` / Password: `password`

---

## 🏗️ Architecture

```
app/Modules/
├── Foundation/          # Properties, Units, Owners, Users
├── Leasing/             # Leases and contracts
├── TenantManagement/    # Resident lifecycle
├── Collection/          # Invoices, Payments, Aging
├── Finance/             # Ledger, Budgets, Owner statements
├── Maintenance/         # Work orders, PM plans, Contractors
├── FacilityManagement/  # Cleaning, Security, Utilities
├── Governance/          # Compliance, Insurance
├── HOA/                 # Associations, Meetings, Voting
├── Reporting/           # Dashboards, KPIs
├── Marketing/           # Listings, Lead tracking
├── AssetPerformance/    # NOI optimization
├── RiskManagement/      # Risk register
├── Experience/          # CSAT, NPS
├── Growth/              # Portfolio expansion
├── Strategy/            # Strategic KPIs
└── AIEngine/            # AI reports, Smart analysis
```

**Database:** 25+ tables per tenant — properties, units, leases, invoices, work_orders, pm_plans, inspections, ledger_entries, HOA, and more.

---

## 🗺️ Roadmap

| Phase | Scope | Status |
|-------|-------|--------|
| Phase 0 | Infrastructure, Multi-Tenancy, RBAC, DB Schema | ✅ |
| Phase 1 | Property Onboarding | 🔲 |
| Phase 2 | Leasing & Occupancy | 🔲 |
| Phase 3 | Tenant Management | 🔲 |
| Phase 4 | Collection & Invoicing | 🔲 |
| Phase 5–11 | Finance, Maintenance, Governance, Reports, AI, Growth | 🔲 |

**MVP (Phases 0–4):** ~22 weeks for a fully operational system.

---

## 🤝 Contributing

1. Fork the repo
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add feature'`)
4. Push & open a Pull Request

---

## 📄 License

MIT License — see [LICENSE](LICENSE).

<div align="center">

**Built with ❤️ for the Saudi PropTech ecosystem**

</div>
