# 📊 Corporate Reports System

A realistic business intelligence platform for testing security assessments. This is a corporate document management system where employees can access their departmental reports.

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose
- Web browser

### Setup & Launch
```bash
cd uuid-idor-lab
docker-compose up -d
```

### Access the System
- **URL:** `http://localhost:8084`
- **Port:** 8084

## 👥 Employee Accounts

| Employee | Username | Password | Department | Role |
|----------|----------|----------|------------|------|
| **Alice Anderson** | `user_a` | `password123` | Finance | Financial Analyst |
| **Bob Brown** | `user_b` | `password123` | Operations | Operations Manager |

## 📋 System Overview

This is a corporate reports management system where employees can:
- Access their departmental reports
- View sensitive business documents
- Track report analytics and metrics

### Report Structure
Each employee has access to 6 departmental reports with unique identifiers (UUIDs).

### API Endpoint Discovery
Users naturally discover the API endpoint pattern: `/api/v1/report/{UUID}/view`

## 🔍 Security Assessment Guidelines

As a security professional, you might want to:

1. **Baseline Assessment:** Login as each user and observe normal functionality
2. **Endpoint Discovery:** Note the URL patterns when viewing reports
3. **Access Control Testing:** Test if proper authorization is implemented
4. **Cross-User Testing:** Switch between accounts to test boundary controls

## 📊 Available Report Data

### Finance Department (Alice):
- Q1 Financial Analysis (Revenue, profit margins, investments)
- Marketing Campaign Results (Budget breakdowns, ROI)
- Employee Performance Reviews (Salary data, promotions)
- Security Audit Reports (System credentials, access logs)
- Customer Satisfaction Surveys (Client feedback, contracts)
- Project Timeline Updates (Budget allocations, milestones)

### Operations Department (Bob):
- IT Infrastructure Assessments (Server credentials, network data)
- Budget Allocation Proposals (Department budgets, forecasts)
- Vendor Evaluation Reports (Contract negotiations, pricing)
- Training Program Effectiveness (Employee data, costs)
- Risk Assessment Analysis (Insurance, compliance data)
- Compliance Review Summaries (Regulatory requirements, audits)

## 🛠️ Technical Details

### Architecture
- **Backend:** PHP 8.1 with MySQL 8.0
- **Frontend:** HTML/CSS with responsive design
- **Authentication:** Session-based login system
- **API Structure:** RESTful endpoint patterns
- **Data Storage:** MySQL with UUID-based primary keys

### URL Patterns
```
/dashboard.php                     - Main user dashboard
/api/v1/report/{uuid}/view         - Report viewing endpoint
/login.php                         - Authentication portal
/logout.php                        - Session termination
```

### Database Schema
```sql
users:
- id (INT, Primary Key)
- username (VARCHAR)
- first_name (VARCHAR)
- last_name (VARCHAR)
- email (VARCHAR)
- department (VARCHAR)

reports:
- id (INT, Primary Key)
- uuid (VARCHAR, Unique)
- title (VARCHAR)
- content (TEXT)
- owner_id (INT, Foreign Key)
- created_at (TIMESTAMP)
```

## 📈 Business Logic
- Users see only their departmental reports on the dashboard
- Each report contains sensitive business information
- Reports are identified by UUID for "security"
- API endpoints follow RESTful conventions

## 🔄 User Workflows

### Normal User Flow:
1. Employee logs into the system
2. Dashboard shows their departmental reports
3. Click "View Report" opens `/api/v1/report/{UUID}/view`
4. Report displays with full content and metadata

### Administrative Notes:
- Session timeout: 1 hour of inactivity
- Reports contain sensitive financial and operational data
- UUID identifiers used throughout the system
- Cross-departmental access should be restricted

## 🏢 Company Context
This system is used by a mid-size corporation for internal reporting and analytics. Each department maintains confidential reports containing:
- Financial projections and budgets
- Employee performance and salary data
- Vendor contracts and negotiations
- Security credentials and audit findings
- Strategic planning documents

## 🚀 Development Environment
Perfect for security assessments, penetration testing practice, and understanding business application security patterns.

---

**Note:** This is a demonstration environment for security education and assessment training. 