# **Custom Global Search API for SugarCRM**

## **Overview**
This repository provides a **Custom Global Search API** for **SugarCRM**, allowing users to perform **Elasticsearch-based** global searches across multiple modules.

The API is implemented as a **custom SugarCRM REST endpoint**, supporting authentication via **Bearer Token** and allowing search across specified modules.

---

## **Features**
✅ **Custom Global Search API** integrated with **Elasticsearch**  
✅ **Secure Authorization** via **Bearer Token**  
✅ **Pagination Support** (Limit, Offset)  
✅ **Sorting & Highlighting** in Search Results  
✅ **Optimized Query Handling**  

---

## **Installation & Setup**
### **Prerequisites**
1. **SugarCRM** (On-Premise or Cloud-based instance)
2. **Elasticsearch** configured as the search engine in SugarCRM
3. **GitHub Desktop (for version control)**

### **Step 1: Clone the Repository**
```sh
git clone https://github.com/YOUR_USERNAME/sugarcrm-global-search.git
```
Alternatively, use **GitHub Desktop**:
1. Click **Clone a repository**.
2. Select your repository and **clone it locally**.

### **Step 2: Add the API File**
Copy the file **CustomGlobalSearchApi.php** to:
```sh
custom/clients/base/api/
```
Ensure your **SugarCRM instance** is configured to recognize custom APIs.

### **Step 3: Repair & Rebuild SugarCRM**
1. Navigate to **Admin Panel** in SugarCRM.
2. Click **Quick Repair and Rebuild**.
3. Clear the cache and refresh your instance.

---

## **API Authentication**
This API uses a **Bearer Token** for authentication.  
The token must be **hashed** and stored securely in SugarCRM environment variables.

### **Generating & Storing Token**
1. Generate a secure token:
   ```sh
   php -r "echo password_hash('your-secure-token', PASSWORD_BCRYPT);"
   ```
2. Add the token as an **environment variable** in SugarCRM:
   ```sh
   export SUGARCRM_BEARER_HASH='your_hashed_token'
   ```
3. Set the **admin username** for API execution:
   ```sh
   export SUGARCRM_ADMIN_USER='admin'
   ```

---

## **Usage**
### **API Endpoint**
```sh
GET /rest/v10/customGlobalSearch
```
or
```sh
POST /rest/v10/customGlobalSearch
```

### **Headers**
```json
{
  "Authorization": "Bearer your-secure-token",
  "Content-Type": "application/json"
}
```

### **Request Parameters**
| Parameter       | Type   | Required | Description |
|----------------|--------|----------|-------------|
| `module_list`  | Array/String | ✅ Yes | Comma-separated list of modules to search in |
| `q`            | String | ❌ No  | Search term |
| `max_num`      | Integer | ❌ No  | Limit results (default: `20`) |
| `offset`       | Integer | ❌ No  | Pagination offset (default: `0`) |
| `highlights`   | Boolean | ❌ No  | Enable/Disable search result highlights |
| `sort`         | Array | ❌ No  | Sorting parameters |

---

## **Example Requests**
### **1. Search for Leads and Contacts**
#### **cURL Command**
```sh
curl -X GET "https://your-sugarcrm-instance/rest/v10/customGlobalSearch" \
     -H "Authorization: Bearer your-secure-token" \
     -H "Content-Type: application/json" \
     -d '{
           "module_list": ["Leads", "Contacts"],
           "q": "John Doe",
           "max_num": 10,
           "offset": 0
         }'
```

### **2. Search With Sorting & Highlights**
```sh
curl -X GET "https://your-sugarcrm-instance/rest/v10/customGlobalSearch" \
     -H "Authorization: Bearer your-secure-token" \
     -H "Content-Type: application/json" \
     -d '{
           "module_list": ["Accounts"],
           "q": "Acme Inc",
           "max_num": 5,
           "highlights": true,
           "sort": [{"date_entered": "desc"}]
         }'
```

---

## **Response Format**
A successful API request returns a JSON response like:
```json
{
  "next_offset": -1,
  "total": 2,
  "query_time": 45,
  "records": [
    {
      "id": "12345",
      "name": "John Doe",
      "module": "Leads",
      "_score": 1.0,
      "_highlights": {
        "name": "<strong>John</strong> Doe"
      }
    },
    {
      "id": "67890",
      "name": "Acme Inc",
      "module": "Accounts",
      "_score": 0.9
    }
  ]
}
```

---

## **Error Handling**
| Error Code | Message                        | Description |
|------------|--------------------------------|-------------|
| `401`      | `"Unauthorized"`               | Invalid or missing **Bearer Token** |
| `400`      | `"Missing required parameter: module_list"` | Required field missing |
| `500`      | `"Search Runtime Error"`       | Elasticsearch or internal API failure |

---

## **Development & Contribution**
### **1. Create a Feature Branch**
```sh
git checkout -b feature/custom-search
```

### **2. Commit Changes**
```sh
git add .
git commit -m "Added Custom Global Search API"
```

### **3. Push Changes**
```sh
git push origin feature/custom-search
```

### **4. Create a Pull Request**
1. Go to **GitHub** → Open repository.
2. Click **Pull Requests** → **New Pull Request**.
3. Select your branch and submit the request.

---

## **Troubleshooting**
🔹 If the API does not appear in SugarCRM:
   - Ensure the file is correctly placed in `custom/clients/base/api/`
   - Run **Quick Repair & Rebuild** in SugarCRM Admin

🔹 If **Unauthorized** errors occur:
   - Ensure the **Bearer Token** is correctly hashed and stored
   - Confirm the **environment variables** are set properly

🔹 If **search is slow or unresponsive**:
   - Check Elasticsearch service logs
   - Increase **max memory limit** for Elasticsearch in `php.ini`

---

## **License**
This project is licensed under the **MIT License**.