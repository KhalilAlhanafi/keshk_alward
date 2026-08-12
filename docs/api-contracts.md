# Kashk Al-Ward - Frontend API Contracts (Phase 10)

This document defines the JSON contracts for the AJAX endpoints used by the Alpine.js frontend. All these endpoints are CSRF-protected and expect `Accept: application/json`.

---

## 1. Cart Management

### Add to Cart
**Endpoint:** `POST /cart`

**Request:**
```json
{
  "product_id": 1,
  "product_size_id": 2,
  "quantity": 1,
  "message": "Happy Birthday!" // Optional
}
```

**Response:** `200 OK`
```json
{
  "items": [
    {
      "id": 1,
      "product_id": 1,
      "product_name": "باقة جوري أحمر",
      "size_id": 2,
      "size_label": "متوسط",
      "unit_price": 50000,
      "formatted_unit_price": "50,000 ل.س.",
      "quantity": 1,
      "message": "Happy Birthday!",
      "subtotal": 50000,
      "formatted_subtotal": "50,000 ل.س."
    }
  ],
  "totals": {
    "subtotal": 50000,
    "addons_total": 0,
    "delivery_fee": null,
    "total": 50000,
    "formatted_subtotal": "50,000 ل.س.",
    "formatted_addons_total": "0 ل.س.",
    "formatted_delivery_fee": "0 ل.س.",
    "formatted_total": "50,000 ل.س."
  }
}
```
*(Validation errors return `422 Unprocessable Entity`)*

---

### Update Cart Item
**Endpoint:** `PUT /cart/{id}`

**Request:**
```json
{
  "quantity": 2,
  "message": "Updated message" // Optional
}
```

**Response:** `200 OK`
Returns the exact same shape as **Add to Cart**.

---

### Remove from Cart
**Endpoint:** `DELETE /cart/{id}`

**Request:** Empty body.

**Response:** `200 OK`
Returns the exact same shape as **Add to Cart** (with the item removed).

---

## 2. Wishlist

### Toggle Wishlist
**Endpoint:** `POST /wishlist/toggle`

**Request:**
```json
{
  "product_id": 1
}
```

**Response:** `200 OK`
```json
{
  "status": "added", // or "removed"
  "message": "تمت إضافة المنتج إلى المفضلة.",
  "count": 3
}
```

---

## 3. Delivery Areas (Dependent Dropdown)

### Get Delivery Areas (Cities or Areas)
**Endpoint:** `GET /api/delivery-areas?city={city_name}`

**Request:** 
- To get all active cities: `GET /api/delivery-areas`
- To get areas within a city: `GET /api/delivery-areas?city=دمشق`

**Response (No city provided):** `200 OK`
```json
[
  "دمشق",
  "حلب",
  "حمص"
]
```

**Response (City provided):** `200 OK`
```json
[
  {
    "id": 1,
    "area": "المزة",
    "fee": 15000,
    "formatted_fee": "15,000 ل.س."
  },
  {
    "id": 2,
    "area": "الشعلان",
    "fee": 10000,
    "formatted_fee": "10,000 ل.س."
  }
]
```

---

## 4. Product Live Price Preview

### Compute Price Preview
**Endpoint:** `GET /products/{id}/price-preview?size_id={size_id}&addons[]={addon_id}`

**Request:** 
Query Parameters:
- `size_id` (integer, optional)
- `addons[]` (array of integers, optional)

**Response:** `200 OK`
```json
{
  "price": 85000,
  "formatted_price": "85,000 ل.س."
}
```

---

## 5. Single Product Display

### Get Single Product
**Endpoint:** `GET /products/{slug}`

**Response:** `200 OK`
Returns the full Product model with its loaded `category`, `sizes`, and active `addons` relations.
