# Bangladesh Dev Platform - API Documentation

Base URL: `https://api.banglade.sh/api/v1`

## Table of Contents
- [Portal Endpoints](#portal-endpoints)
  - [Weather](#weather)
  - [Currency](#currency)
  - [News](#news)
  - [Jobs](#jobs)
  - [Education](#education)
  - [Market](#market)
  - [Prayer Times](#prayer-times)
  - [Cricket](#cricket)
  - [Commodities](#commodities)
  - [Emergency Numbers](#emergency-numbers)
  - [Holidays](#holidays)
  - [Radio](#radio)
  - [Notices](#notices)
  - [Search](#search)
  - [AI Chat](#ai-chat)
  - [Combined Data](#combined-data)
- [Authentication Endpoints](#authentication-endpoints)
- [Video Endpoints](#video-endpoints)

---

## Portal Endpoints

All portal endpoints are public and require no authentication (except AI chat rate limits).

### Weather

#### Get Weather for Location
```
GET /portal/weather?lat={latitude}&lon={longitude}
```

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| lat | float | No | 23.8103 | Latitude (defaults to Dhaka) |
| lon | float | No | 90.4125 | Longitude (defaults to Dhaka) |

**Response:**
```json
{
  "success": true,
  "data": {
    "location": {
      "lat": 23.8103,
      "lon": 90.4125,
      "name": "Dhaka"
    },
    "current": {
      "temperature": 28.5,
      "humidity": 75,
      "windSpeed": 12.3,
      "weatherCode": 3,
      "description": "Partly Cloudy"
    },
    "hourly": [...],
    "daily": [...]
  }
}
```

#### Get Available Weather Locations
```
GET /portal/weather/locations
```

**Response:** List of all 64 districts with coordinates.

#### Get Weather by Divisions
```
GET /portal/weather/divisions
```

**Response:** Weather for all 8 division capitals.

#### Get Bulk Weather Data
```
GET /portal/weather/bulk?locations={location_ids}
```

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| locations | string | Yes | Comma-separated location IDs |

---

### Currency

#### Get Exchange Rates
```
GET /portal/currency
```

**Response:**
```json
{
  "success": true,
  "data": {
    "base": "BDT",
    "rates": {
      "USD": { "rate": 110.25, "change": 0.15 },
      "EUR": { "rate": 119.50, "change": -0.22 },
      "GBP": { "rate": 139.80, "change": 0.08 },
      "INR": { "rate": 1.32, "change": 0.01 },
      "SAR": { "rate": 29.40, "change": 0.05 }
    },
    "updated_at": "2024-01-15T10:00:00Z"
  }
}
```

---

### News

#### Get News Feed
```
GET /portal/news?source={source}&category={category}&limit={limit}
```

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| source | string | No | all | Filter by source: `prothom-alo`, `kaler-kantho`, `all` |
| category | string | No | all | Filter by category: `national`, `international`, `sports`, `entertainment`, `tech`, `all` |
| limit | int | No | 20 | Number of articles (max 50) |

**Response:**
```json
{
  "success": true,
  "data": {
    "articles": [
      {
        "id": "abc123",
        "title": "News headline",
        "title_bn": "বাংলা শিরোনাম",
        "summary": "Brief summary...",
        "source": "prothom-alo",
        "category": "national",
        "url": "https://...",
        "image": "https://...",
        "published_at": "2024-01-15T08:30:00Z"
      }
    ],
    "sources": ["prothom-alo", "kaler-kantho"],
    "total": 45
  }
}
```

---

### Jobs

#### Get Job Listings
```
GET /portal/jobs?type={type}&category={category}&limit={limit}
```

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| type | string | No | all | Job type: `government`, `private`, `ngo`, `all` |
| category | string | No | all | Job category |
| limit | int | No | 20 | Number of jobs (max 50) |

**Response:**
```json
{
  "success": true,
  "data": {
    "jobs": [
      {
        "id": "job123",
        "title": "Software Engineer",
        "title_bn": "সফটওয়্যার ইঞ্জিনিয়ার",
        "company": "Company Name",
        "type": "private",
        "location": "Dhaka",
        "salary": "50,000 - 80,000 BDT",
        "deadline": "2024-02-15",
        "url": "https://..."
      }
    ],
    "types": ["government", "private", "ngo"],
    "total": 120
  }
}
```

---

### Education

#### Get Education Resources
```
GET /portal/education?type={type}&level={level}&limit={limit}
```

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| type | string | No | all | Type: `admission`, `result`, `scholarship`, `notice`, `all` |
| level | string | No | all | Level: `ssc`, `hsc`, `university`, `all` |
| limit | int | No | 20 | Number of items (max 50) |

**Response:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": "edu123",
        "title": "HSC Result 2024",
        "title_bn": "এইচএসসি ফলাফল ২০২৪",
        "type": "result",
        "level": "hsc",
        "board": "Dhaka",
        "url": "https://...",
        "published_at": "2024-01-10"
      }
    ],
    "types": ["admission", "result", "scholarship", "notice"],
    "total": 35
  }
}
```

---

### Market

#### Get Stock Market Data
```
GET /portal/market
```

**Response:**
```json
{
  "success": true,
  "data": {
    "dse": {
      "index": 6543.21,
      "change": 45.67,
      "changePercent": 0.70,
      "volume": 125000000,
      "updated_at": "2024-01-15T14:30:00Z"
    },
    "cse": {
      "index": 1987.54,
      "change": -12.34,
      "changePercent": -0.62
    },
    "top_gainers": [...],
    "top_losers": [...]
  }
}
```

---

### Prayer Times

#### Get Prayer Times
```
GET /portal/prayer?lat={latitude}&lon={longitude}&date={date}
```

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| lat | float | No | 23.8103 | Latitude (defaults to Dhaka) |
| lon | float | No | 90.4125 | Longitude (defaults to Dhaka) |
| date | string | No | today | Date in YYYY-MM-DD format |

**Response:**
```json
{
  "success": true,
  "data": {
    "location": "Dhaka",
    "date": "2024-01-15",
    "times": {
      "fajr": "05:22",
      "sunrise": "06:38",
      "dhuhr": "12:05",
      "asr": "15:45",
      "maghrib": "17:32",
      "isha": "18:52"
    },
    "next_prayer": {
      "name": "Dhuhr",
      "time": "12:05",
      "remaining": "1h 23m"
    }
  }
}
```

---

### Cricket

#### Get Cricket Scores
```
GET /portal/cricket
```

**Response:**
```json
{
  "success": true,
  "data": {
    "live": [
      {
        "id": "match123",
        "teams": {
          "home": { "name": "Bangladesh", "score": "245/6", "overs": "42.3" },
          "away": { "name": "India", "score": "180", "overs": "45.2" }
        },
        "status": "Bangladesh lead by 65 runs",
        "format": "ODI",
        "venue": "Mirpur Stadium"
      }
    ],
    "upcoming": [...],
    "recent": [...]
  }
}
```

---

### Commodities

#### Get Commodity Prices
```
GET /portal/commodities
```

**Response:**
```json
{
  "success": true,
  "data": {
    "gold": {
      "price_per_gram": 9850,
      "price_per_vori": 114950,
      "change": 150,
      "unit": "BDT"
    },
    "silver": {
      "price_per_gram": 125,
      "price_per_vori": 1458,
      "change": -5
    },
    "fuel": {
      "petrol": 130,
      "diesel": 114,
      "octane": 135
    },
    "updated_at": "2024-01-15T09:00:00Z"
  }
}
```

---

### Emergency Numbers

#### Get Emergency Contact Numbers
```
GET /portal/emergency
```

**Response:**
```json
{
  "success": true,
  "data": {
    "categories": [
      {
        "name": "Emergency",
        "name_bn": "জরুরি",
        "contacts": [
          { "name": "National Emergency", "name_bn": "জাতীয় জরুরি", "number": "999" },
          { "name": "Fire Service", "name_bn": "ফায়ার সার্ভিস", "number": "199" },
          { "name": "Ambulance", "name_bn": "অ্যাম্বুলেন্স", "number": "199" }
        ]
      },
      {
        "name": "Police",
        "name_bn": "পুলিশ",
        "contacts": [
          { "name": "Police Control", "name_bn": "পুলিশ কন্ট্রোল", "number": "100" },
          { "name": "RAB", "name_bn": "র‍্যাব", "number": "01৫-২৭৪৩০০০০" }
        ]
      },
      {
        "name": "Utilities",
        "name_bn": "ইউটিলিটি",
        "contacts": [
          { "name": "DESCO", "name_bn": "ডেস্কো", "number": "16116" },
          { "name": "WASA", "name_bn": "ওয়াসা", "number": "16163" }
        ]
      }
    ]
  }
}
```

---

### Holidays

#### Get National Holidays
```
GET /portal/holidays?year={year}
```

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| year | int | No | current | Year for holidays |

**Response:**
```json
{
  "success": true,
  "data": {
    "year": 2024,
    "holidays": [
      {
        "name": "International Mother Language Day",
        "name_bn": "আন্তর্জাতিক মাতৃভাষা দিবস",
        "date": "2024-02-21",
        "type": "national",
        "is_public_holiday": true
      },
      {
        "name": "Independence Day",
        "name_bn": "স্বাধীনতা দিবস",
        "date": "2024-03-26",
        "type": "national",
        "is_public_holiday": true
      }
    ],
    "upcoming": {
      "name": "Independence Day",
      "date": "2024-03-26",
      "days_remaining": 70
    }
  }
}
```

---

### Radio

#### Get Radio Stations
```
GET /portal/radio
```

**Response:**
```json
{
  "success": true,
  "data": {
    "stations": [
      {
        "id": "radio-foorti",
        "name": "Radio Foorti",
        "frequency": "88.0 FM",
        "stream_url": "https://...",
        "logo": "https://...",
        "genre": "music"
      }
    ]
  }
}
```

---

### Notices

#### Get Government Notices
```
GET /portal/notices?type={type}&limit={limit}
```

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| type | string | No | all | Notice type |
| limit | int | No | 20 | Number of notices |

---

### Search

#### Unified Search
```
GET /portal/search?q={query}&type={type}
```

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| q | string | Yes | - | Search query |
| type | string | No | all | Search in: `news`, `jobs`, `education`, `all` |

**Response:**
```json
{
  "success": true,
  "data": {
    "query": "software engineer",
    "results": {
      "news": [...],
      "jobs": [...],
      "education": [...]
    },
    "total": 45
  }
}
```

---

### AI Chat

#### Send Message to AI Assistant
```
POST /portal/ai/chat
```

**Request Body:**
```json
{
  "message": "What is the weather in Dhaka?",
  "context": "optional context"
}
```

**Headers (optional):**
```
Authorization: Bearer {jwt_token}
```

**Rate Limits:**
- Guest users: 5 requests/day
- Authenticated users: 20 requests/day

**Response:**
```json
{
  "success": true,
  "data": {
    "response": "The current weather in Dhaka is...",
    "remaining": 4,
    "limit": 5
  }
}
```

#### Get AI Rate Limit Status
```
GET /portal/ai/limit
```

**Response:**
```json
{
  "success": true,
  "data": {
    "remaining": 4,
    "limit": 5,
    "resets_at": "2024-01-16T00:00:00Z"
  }
}
```

---

### Combined Data

#### Get All Portal Data
```
GET /portal/all
```

Returns combined response with weather, currency, news, jobs, education, prayer times, cricket, and more in a single request. Useful for initial page load.

---

## Authentication Endpoints

### Register
```
POST /auth/register
```
**Body:** `{ "email": "...", "password": "...", "name": "..." }`

### Login
```
POST /auth/login
```
**Body:** `{ "email": "...", "password": "..." }`

### Refresh Token
```
POST /auth/refresh
```
**Body:** `{ "refresh_token": "..." }`

### Logout
```
POST /auth/logout
```

### Forgot Password
```
POST /auth/forgot-password
```
**Body:** `{ "email": "..." }`

### Reset Password
```
POST /auth/reset-password
```
**Body:** `{ "token": "...", "password": "..." }`

### Verify Email
```
POST /auth/verify-email
```
**Body:** `{ "token": "..." }`

### Change Password (Auth Required)
```
POST /auth/change-password
```
**Headers:** `Authorization: Bearer {token}`
**Body:** `{ "current_password": "...", "new_password": "..." }`

---

## Video Endpoints

### Get Video Feed
```
GET /video/feed?page={page}&limit={limit}
```

### Search Videos
```
GET /video/search?q={query}
```

### Get Video Details
```
GET /video/{id}
```

### Get Video Comments
```
GET /video/{id}/comments
```

### Add Comment (Auth Required)
```
POST /video/{id}/comments
```

### Delete Comment (Auth Required)
```
DELETE /video/comments/{commentId}
```

### Get Bookmarks (Auth Required)
```
GET /video/bookmarks
```

### Add Bookmark (Auth Required)
```
POST /video/bookmarks
```

### Remove Bookmark (Auth Required)
```
DELETE /video/bookmarks/{videoId}
```

### Get Watch History (Auth Required)
```
GET /video/history
```

### Record Watch History (Auth Required)
```
POST /video/history
```

### Get Download Manifest (Auth Required)
```
GET /video/{id}/downloads
```

---

## Error Responses

All endpoints return errors in this format:

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable message",
    "details": {}
  }
}
```

**Common Error Codes:**
| Code | HTTP Status | Description |
|------|-------------|-------------|
| VALIDATION_ERROR | 400 | Invalid request parameters |
| UNAUTHORIZED | 401 | Missing or invalid auth token |
| FORBIDDEN | 403 | Insufficient permissions |
| NOT_FOUND | 404 | Resource not found |
| RATE_LIMITED | 429 | Too many requests |
| SERVER_ERROR | 500 | Internal server error |

---

## Rate Limiting

| Endpoint | Guest Limit | Auth Limit | Window |
|----------|-------------|------------|--------|
| /portal/ai/chat | 5/day | 20/day | 24 hours |
| All other endpoints | 100/min | 500/min | 1 minute |

---

## Notes

- All timestamps are in ISO 8601 format (UTC)
- Bengali text fields use `_bn` suffix
- Currency amounts are in BDT unless specified
- Weather temperatures are in Celsius
