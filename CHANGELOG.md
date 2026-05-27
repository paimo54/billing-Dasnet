# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned Features
- Payment Gateway Integration (Midtrans, Xendit)
- Mikrotik RouterOS API Integration for auto-suspend
- Customer Self-Service Portal
- Ticketing & Support System
- Advanced Reporting & Analytics
- Queue System for bulk operations
- Email/SMS/WhatsApp Notification System
- API Development with OAuth2
- Mobile Application for Technicians
- Inventory & Equipment Management

---

## [2.0.0] - 2026-05-27

### Added
- **Versioning System**: Implemented semantic versioning with CHANGELOG.md
- **Git Repository**: Initialized git repository for version control
- **Documentation**: Added comprehensive upgrade roadmap for ISP enterprise features

### Changed
- Project structure prepared for enterprise-scale ISP billing management
- Ready for Phase 1 implementation (Payment Gateway, Mikrotik Integration, Notifications)

### Notes
- Current version supports basic billing operations
- Designed to scale for thousands of customers
- Multi-role system (SuperAdmin, Admin, Technician)

---

## [1.0.0] - 2025-08-15

### Added
- **User Management**: Multi-role system (SuperAdmin, Admin, Technician)
- **Customer Management**: CRUD operations for customers with geolocation
- **Package Management**: Internet package management with automatic tax calculation (PPN 11%)
- **Invoice Management**: 
  - Automatic monthly invoice generation
  - Print status tracking per role
  - Payment status tracking
  - Technician fee calculation
- **Financial Reports**: 
  - Mitra reports for technician commissions
  - Payment status tracking
  - Print status management
- **Dashboard**: Role-based dashboards with statistics
- **Export/Import**: Customer data export to Excel
- **AdminLTE Integration**: Professional admin template
- **Database**: MySQL with proper relationships and foreign keys

### Features by Role

#### SuperAdmin
- Manage all admins and technicians
- Full package management (create, edit, delete)
- View all invoices and financial reports
- View all customers across all technicians
- System-wide statistics and analytics

#### Admin
- Manage technicians
- Edit package prices (limited package management)
- Create and manage invoices
- Financial reports and mitra reports
- Customer management
- Payment status updates

#### Technician
- Manage own customers
- View own financial reports
- Print invoices for own customers
- Update payment status for own customers
- Customer import functionality

### Technical Stack
- **Framework**: Laravel 10.x
- **PHP**: ^8.1
- **Database**: MySQL
- **Frontend**: AdminLTE 3.x, Bootstrap, jQuery
- **Libraries**:
  - Laravel Sanctum for API authentication
  - Laravel DataTables for data tables
  - Maatwebsite Excel for export/import
  - Guzzle HTTP for API calls

### Database Schema
- users (with role_id)
- roles (superadmin, admin, technician)
- customers (with package_id, created_by, geolocation)
- packages (with automatic tax calculation)
- invoices (with print status per role, payment tracking)
- mitra_reports (technician commission tracking)
- settings (system configuration)

---

## Version History Summary

- **v2.0.0** (2026-05-27): Enterprise preparation with versioning system
- **v1.0.0** (2025-08-15): Initial release with core billing features

---

## Upgrade Notes

### From v1.0.0 to v2.0.0
- No database changes required
- Added CHANGELOG.md and VERSION file
- Prepared for enterprise features implementation

---

## Contributing

When contributing to this project, please:
1. Update the CHANGELOG.md under [Unreleased] section
2. Follow semantic versioning guidelines
3. Document all breaking changes
4. Update VERSION file when releasing

---

## Semantic Versioning Guide

Given a version number MAJOR.MINOR.PATCH:
- **MAJOR**: Incompatible API changes or major feature overhaul
- **MINOR**: New features added in a backward-compatible manner
- **PATCH**: Backward-compatible bug fixes

---

**Project**: Billing Management System for ISP  
**Repository**: billing-Dasnet  
**Maintainer**: Development Team  
**License**: MIT
