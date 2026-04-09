# Obiala Travel

A full-stack flight and hotel booking platform built with Laravel 13, Vue 3, and Inertia.js. Live flight data is powered by the Duffel API, and payments are handled by Stripe.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Frontend | Vue 3, Inertia.js, TypeScript |
| Styling | Tailwind CSS v4, shadcn/ui (Reka UI) |
| Routing | Laravel Wayfinder (type-safe route helpers) |
| Flights API | Duffel |
| Payments | Stripe Payment Elements |
| Auth | Laravel Fortify |
| Database | SQLite (local) |
| Build | Vite 8 |

---

## Features

- **Flight search** — live results from the Duffel API with filters (stops, airlines, price), sorting, and price alerts
- **Hotel search** — search with mock data (Duffel Stays integration planned)
- **Guest checkout** — passenger details form → Stripe card payment → booking confirmation, no account required
- **Bookings** — authenticated users can view booking history and saved flights
- **Auth** — registration, login, email verification, password reset via Laravel Fortify

---

## Requirements

- PHP 8.4+
- Node.js 20+
- [Laravel Herd](https://herd.laravel.com) (recommended for local dev) or any PHP server
- A [Duffel](https://duffel.com) account (test API key)
- A [Stripe](https://stripe.com) account (test API keys)

---

## Local Setup

### 1. Clone the repo

```bash
git clone https://github.com/Obiala-Travel/Obaila-Travel.git
cd Obaila-Travel
git checkout staging
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Then open `.env` and fill in the required values:

```env
APP_NAME="Obiala Travel"
APP_URL=https://obiala-travel.test   # or http://localhost:8000

# Duffel — https://app.duffel.com/
DUFFEL_API_KEY=duffel_test_...

# Stripe — https://dashboard.stripe.com/test/apikeys
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
```

### 4. Set up the database

```bash
php artisan migrate
```

### 5. Start the dev server

**With Laravel Herd** (recommended):
- Open Herd — the site is automatically served at `https://obiala-travel.test`
- In a terminal, run:

```bash
npm run dev
```

**Without Herd:**

```bash
php artisan serve &
npm run dev
```

Then visit `http://localhost:8000`.

---

## Project Structure

```
app/
  Http/
    Controllers/
      FlightController.php      # Flight search
      HotelController.php       # Hotel search
      CheckoutController.php    # Checkout flow (select → form → payment → confirm)
      BookingController.php     # Booking history, saved flights
  Services/
    DuffelService.php           # Duffel API client + mock fallbacks

resources/js/
  pages/
    Home.vue                    # Landing / search form
    flights/Search.vue          # Flight results
    hotels/Search.vue           # Hotel results
    checkout/
      Flight.vue                # Passenger form + Stripe Payment Element
      Confirmation.vue          # Booking confirmation
    bookings/
      Index.vue                 # Booking history
      Saved.vue                 # Saved flights
  layouts/
    GuestLayout.vue             # Unauthenticated pages (always light mode)

routes/
  web.php                       # All routes
```

---

## Payments (Stripe)

Checkout uses [Stripe Payment Elements](https://stripe.com/docs/payments/payment-element) with a server-side PaymentIntent flow:

1. Passenger form is submitted → Laravel creates a `Booking` (status: `pending`) and a Stripe `PaymentIntent`
2. The `client_secret` is returned to the frontend
3. Stripe's Payment Element renders using the `client_secret`
4. On payment confirmation Stripe redirects to `/checkout/flight/complete`
5. Laravel verifies the PaymentIntent status and updates the booking to `confirmed`

**Test cards:**

| Card | Result |
|---|---|
| `4242 4242 4242 4242` | Success |
| `4000 0000 0000 9995` | Declined |

Use any future expiry, any CVC, any postcode.

---

## Environment Variables Reference

| Variable | Description |
|---|---|
| `APP_KEY` | Laravel app key — generate with `php artisan key:generate` |
| `APP_URL` | Full URL of the app (e.g. `https://obiala-travel.test`) |
| `DUFFEL_API_KEY` | Duffel API key (starts with `duffel_test_` or `duffel_live_`) |
| `DUFFEL_BASE_URL` | Duffel base URL — default `https://api.duffel.com` |
| `DUFFEL_API_VERSION` | Duffel API version — default `v2` |
| `STRIPE_KEY` | Stripe publishable key (starts with `pk_`) |
| `STRIPE_SECRET` | Stripe secret key (starts with `sk_`) |

---

## Git Workflow

```
feature/* or fix/*  →  staging  →  main
```

- All work is done on a `feature/` or `fix/` branch
- Branches are merged into `staging` via PR after testing
- Once `staging` is stable, it is promoted to `main`

---

## Roadmap

- [ ] Docker + Azure deployment
- [ ] Duffel Stays — real hotel search
- [ ] Multi-passenger booking
- [ ] Email confirmation with e-ticket PDF
- [ ] Admin dashboard
