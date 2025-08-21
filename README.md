
---

Simple PHP backend for Mauiz Cafe demo

This project includes a minimal backend to support the new storefront pages.

- Endpoints under `api/`:
  - `api/products.php` — returns static product JSON
  - `api/orders.php` — accepts `{ customer, items, totals }` and saves to `data/orders.json`

Run locally with PHP 8+:

```bash
php -S 0.0.0.0:8080 -t /workspace
```

Open `http://localhost:8080/store.html` and checkout to create an order.