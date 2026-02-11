# Laravel Mews Connector API

## Overview

This project is a Laravel-based REST API that integrates with the **Mews Connector API** to expose hotel data in a clean and consumer-friendly format.

The API provides:

- Token-based authentication (Laravel Sanctum)
- Hotel reservations retrieval
- Room availability and pricing (computed)
- Customer search
- Input validation, error handling, and rate limiting
- Conversational availability via Dialogflow chatbot

The focus of this challenge is backend architecture, API design, and real-world system integration.

---

# Architecture Overview

The application follows a layered architecture to ensure separation of concerns and maintainability.

## Layers

- **Controllers** – Handle HTTP requests and responses
- **Form Requests** – Input validation
- **Services** – Business logic and integration with Mews API
- **Mappers** – Transform raw Mews responses into API-friendly format
- **Middleware** – Authentication and rate limiting
- **Routes** – Endpoint definitions

This structure improves testability and scalability.

---

# Authentication

All protected endpoints use **Laravel Sanctum**.

## Flow

1. User registers or logs in  
2. API returns a Bearer token  
3. Protected endpoints require:

```
Authorization: Bearer YOUR_TOKEN
```

Unauthorized requests return **HTTP 401**.

---

# API Endpoints

## Authentication

```
POST /api/register
POST /api/login
GET  /api/user
POST /api/logout
```

---

## Reservations

```
GET /api/mews/reservations
```

### Query Parameters

| Parameter   | Required | Format       |
|------------|----------|--------------|
| property_id | Yes      | string       |
| check_in    | Yes      | YYYY-MM-DD   |
| check_out   | No       | YYYY-MM-DD   |
| status      | No       | string       |

### Behavior

- Reservations are fetched from the Mews Connector API  
- Results are filtered by date intersection  
- Missing optional fields return `null`

---

## Customers Search

```
GET /api/mews/customers/search
```

### Query Parameters

| Parameter    | Required |
|-------------|----------|
| name        | No       |
| resource_id | No       |

---

## Availability (Computed)

```
GET /api/mews/availability
```

### Query Parameters

| Parameter   | Required | Format       |
|------------|----------|--------------|
| property_id | Yes      | string       |
| check_in    | Yes      | YYYY-MM-DD   |
| check_out   | Yes      | YYYY-MM-DD   |
| adults      | Yes      | integer ≥ 1  |

---

# Availability Logic

The Mews Connector API does not provide a direct availability endpoint.

Availability is computed by:

1. Fetching reservations intersecting the date range  
2. Ignoring canceled reservations  
3. Counting occupied rooms per room type  
4. Subtracting occupied rooms from total inventory  
5. Filtering rooms by capacity  
6. Calculating total stay price  

A room type is available only if at least one unit is free for the entire stay.

---

# Pricing Strategy

```
price_per_night × number_of_nights
```

Rates are defined in the inventory configuration.

---

# Example Availability Response

```json
{
  "property_id": "hotel_123",
  "check_in": "2025-06-01",
  "check_out": "2025-06-03",
  "nights": 2,
  "adults": 2,
  "currency": "EUR",
  "rooms": [
    {
      "room_description": "Standard Double Room",
      "price": 180,
      "currency": "EUR"
    }
  ]
}
```

If no rooms are available:

```json
{
  "rooms": []
}
```

---

# Conversational Availability (Chatbot)

## Overview

The API includes a conversational interface powered by **Google Dialogflow ES**.

Users can check availability using natural language input.

No availability logic is duplicated — the chatbot layer reuses the existing availability service.

---

## Chatbot Endpoint

```
POST /api/chatbot/message
```

### Request Body

```json
{
  "text": "Do you have rooms from June 1 to June 3 for 2 adults?",
  "session_id": "optional-session-id"
}
```

- `text` – Natural language input  
- `session_id` – Optional conversation context  

This endpoint is public and rate-limited.

---

## Supported Intent

- `CheckAvailability`

Unsupported intents are handled gracefully.

---

## Example Chatbot Response

```json
{
  "error": false,
  "intent": "CheckAvailability",
  "reply": {
    "text": "Yes! We have 2 room(s) available from 2025-06-01 to 2025-06-03:",
    "carousel": {
      "items": [
        {
          "title": "Standard Double Room",
          "subtitle": "EUR 180.00 for your stay"
        }
      ]
    }
  }
}
```

---

# Validation and Error Handling

| Scenario              | HTTP Status |
|-----------------------|------------|
| Validation error      | 422        |
| Unauthorized          | 401        |
| Mews API error        | 502        |
| Rate limit exceeded   | 429        |

---

# How to Run Locally

## 1. Clone the Repository

```bash
git clone https://github.com/diogoferreira22006678/mews-api.git
cd mews-api
```

## 2. Install Dependencies

```bash
composer install
```

## 3. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

## 4. Configure Database

Edit `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mews_api
DB_USERNAME=root
DB_PASSWORD=your_password
```

Run migrations:

```bash
php artisan migrate
```

## 5. Configure Mews Credentials

```
MEWS_URL=
MEWS_CLIENT_TOKEN=
MEWS_ACCESS_TOKEN=
MEWS_CLIENT_NAME=
```

## 6. Configure Dialogflow

```
DIALOGFLOW_PROJECT_ID=
DIALOGFLOW_LOCATION=
DIALOGFLOW_AGENT_LANGUAGE=en
DIALOGFLOW_CREDENTIALS=/absolute/path/to/service-account.json
```

⚠️ Never commit service account JSON files.

## 7. Start the Server

```bash
php artisan serve
```

API runs at:

```
http://127.0.0.1:8000
```

---

# Assumptions and Limitations

- Single-property setup  
- Room inventory is predefined  
- Only reservations with states Started, Processed, or Confirmed are considered  
- Advanced Mews features (overbooking, channel restrictions, out-of-order rooms) are out of scope  
- Multi-turn chatbot conversations are not implemented  

---

# Production Notes

- Use Nginx or Apache  
- Use HTTPS  
- Store secrets securely  
- Do not commit `.env` or service account credentials  
- Configure proper logging and monitoring  

---

# Author

Diogo Ferreira  
Backend Developer
