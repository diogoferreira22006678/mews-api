# Laravel Mews Connector API

## Overview

This project is a **Laravel-based REST API** that integrates with the **Mews Connector API** to expose hotel data in a clean and consumer-friendly format.

The API provides:

- Token-based authentication
- Hotel reservations retrieval
- Room availability and pricing (computed)
- Customer search
- Input validation, error handling, and rate limiting

The focus of this challenge is **backend architecture, API design, and real-world system integration**.

---

## Architecture Overview

The application follows a layered architecture:
- **Controllers**: Handle HTTP requests, input validation, and responses.
- **Services**: Encapsulate business logic and interactions with the Mews API.
- **Routes**: Define API endpoints and middleware.
- **Mappers**: Transform Mews API responses into our API's response format.
- **Middleware**: Handle authentication, rate limiting, and other cross-cutting concerns.

### Layers

- **Controllers**: HTTP orchestration only  
- **Requests**: Input validation  
- **Services**: Business logic and integration with Mews  
- **Mappers**: Transform raw Mews payloads into API responses  

This structure ensures clear separation of concerns and testability.

---

## Authentication

All protected endpoints use **Laravel Sanctum**.

### Flow

1. User registers or logs in  
2. API returns a Bearer token  
3. All protected endpoints require the header:
Authorization: Bearer <token>

Unauthorized requests return **HTTP 401**.

---

## API Endpoints

### Authentication

POST /api/register
POST /api/login
GET /api/user
POST /api/logout

### Reservations
GET /api/mews/reservations

#### Query Parameters

- `property_id` (required)
- `check_in` (required, YYYY-MM-DD)
- `check_out` (optional, YYYY-MM-DD)
- `status` (optional)

#### Behavior

- Reservations are fetched from the Mews Connector API
- Results are filtered by date intersection
- Missing optional fields are returned as `null`

---

### Customers Search
GET /api/mews/customers/search

#### Query Parameters

- `name` (optional)
- `resource_id` (optional)

---

## Availability (Computed)
GET /api/mews/availability


### Query Parameters

- `property_id` (required)
- `check_in` (required, YYYY-MM-DD)
- `check_out` (required, YYYY-MM-DD)
- `adults` (required, integer ≥ 1)

---

### Availability Logic

The Mews Connector API does **not** expose a direct availability endpoint.

Availability is computed by analyzing existing reservations and predefined room inventory.

#### Steps

1. Fetch reservations that intersect the requested date range  
2. Ignore reservations that do not occupy rooms (e.g. canceled)  
3. Count occupied rooms per room type  
4. Subtract occupied rooms from total inventory  
5. Filter rooms by capacity (number of adults)  
6. Calculate total price based on number of nights  

A room type is considered available only if **at least one unit is free for all nights** of the stay.

---

### Pricing Strategy

Pricing is calculated using:
price_per_night × number_of_nights


Rates are defined in the inventory configuration and multiplied by the stay length.

---

### Example Availability Response

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
If no rooms are available:
{
  "rooms": []
}
```

### Assumptions and Limitations

Room inventory is predefined for the purpose of this challenge

Reservations without explicit room type information are assumed to occupy a standard room

Only reservations with states Started, Processed, or Confirmed are considered

Advanced Mews features (overbooking, out-of-order rooms, channel restrictions) are out of scope

All assumptions are isolated within the availability service

Validation and Error Handling
HTTP Response Codes
Scenario	Status
Validation error	422
Unauthorized	401
Mews API error	502
Rate limit exceeded	429

## How to Run Locally
### Setup
git clone <repository>
cd project
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
Environment Variables

## Configure Mews credentials in .env:

MEWS_URL=
MEWS_CLIENT_TOKEN=
MEWS_ACCESS_TOKEN=
MEWS_CLIENT_NAME=

DIALOGFLOW_PROJECT_ID=
DIALOGFLOW_LOCATION=
DIALOGFLOW_AGENT_LANGUAGE=
DIALOGFLOW_CREDENTIALS=

### Conversational Availability (Chatbot Integration)
Overview

The API includes a natural-language chatbot interface powered by Google Dialogflow, allowing users to check hotel availability using conversational input instead of structured query parameters.

The chatbot is implemented as a thin conversational layer on top of the existing availability engine.

No availability logic is duplicated.

Example user inputs:

“Do you have rooms from June 1 to June 3 for 2 adults?”

“Any rooms available next weekend for 3 people?”

“Check availability tomorrow for one night”

### Chatbot Architecture
Frontend / Dialogflow
        ↓
POST /api/chatbot/message
        ↓
ChatbotController
        ↓
DialogflowService (intent detection)
        ↓
ChatbotAvailabilityService (parameter normalization)
        ↓
MewsAvailabilityService
        ↓
Conversational JSON response
### Chatbot Endpoint
POST /api/chatbot/message
Request Body
{
  "text": "Do you have rooms from June 1 to June 3 for 2 adults",
  "session_id": "optional-session-id"
}

text: User natural-language input

session_id: Optional, used to maintain conversational context

This endpoint is public (not protected by Sanctum) and rate-limited.

### Supported Intent

CheckAvailability

Unsupported intents are handled gracefully.

### Dialogflow Parameter Mapping
Parameter	Entity
date_period	@sys.date-period
adults	@sys.number

Example payload received from Dialogflow:

{
  "date_period": {
    "startDate": "2025-06-01",
    "endDate": "2025-06-03"
  },
  "adults": 2
}
Parameter Normalization

### Conversational parameters are normalized server-side into the existing availability format:

[
  "property_id" => "hotel_123",
  "check_in"    => "2025-06-01",
  "check_out"   => "2025-06-03",
  "adults"      => 2
]

property_id is resolved via configuration

Dates are normalized using Carbon

Missing required parameters trigger validation errors

### Chatbot Response Example
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
### Chatbot Assumptions and Limitations

Only availability queries are supported

Single-property setup

Multi-turn conversations are out of scope

Dialogflow ES is used (not CX)

Chatbot endpoints do not require authentication