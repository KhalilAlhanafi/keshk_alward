# كشك الورد (Kashk Al-Ward) — Project Rules & Frontend Development Plan

This document serves as the project-scoped rules and development guidelines for **كشك الورد (Kashk Al-Ward)**. It is loaded automatically by the agent to ensure strict adherence to all conventions, styling tokens, and the implementation roadmap.

---

## 0. Top Priority - Fast, Smooth, Responsive (UX Checklist)

Every feature and phase must be validated against the following performance and user experience checklist:
- **No full-page reloads:** Always use `fetch()` + Alpine.js for cart, wishlist, and quantity modification actions.
- **Optimistic UI:** Update UI elements (e.g., quantity changes, wishlist toggles) instantly, reconcile with the server response, and roll back with a toast on failure.
- **No layout shift / No blank-white flash:** Implement skeleton loading states or reserved-space containers for any asynchronously fetched content.
- **Debounce inputs:** Debounce filter, search, and price-range inputs by approximately `300ms` before firing API requests.
- **Lazy-load images:** Set `loading="lazy"` on all below-the-fold images. Use `srcset` if the backend provides responsive image sizes.
- **Local Alpine scopes:** Keep `x-data` scopes small and local to avoid re-rendering large page structures on minor user inputs.
- **Smooth transitions:** Use standard `x-transition` with consistent durations (150–250ms) on modals, drawers, and toasts (avoid default browser snaps).
- **Mobile-first responsive CSS:** Verify zero horizontal scroll and touch targets of `≥44px` at breakpoints: `375px`, `768px`, `1024px`, and `1440px`.
- **Font optimization:** Ensure fonts are subsetted, preloaded, and configured with `font-display: swap`.
- **Production builds:** Use the real Vite production build (`npm run build`) as the final verification step.

---

## 1. Non-Negotiable Styling & Formatting Rules

1. **Currency Rendering:**
   - Always display currency in Syrian Pounds (SYP), formatted as `125,000 ل.س`.
   - Never use `$` or `ر.س`.
2. **Numeral System:**
   - Always use Western Arabic numerals (`0-9`). Never use Eastern Arabic-Indic numerals (`٠١٢٣٤٥٦٧٨٩`) for prices, counts, order IDs, phone numbers, dates, or pagination.
   - Do **NOT** use `toLocaleString('ar-SY')` or any Arabic locale formatter in JavaScript as it produces Eastern digits. Use `Intl.NumberFormat('en-US')` or custom comma-insertion instead.
   - For dates, build/use a custom Alpine-based date-picker to bypass native browser/locale rendering inconsistencies.
3. **Direction (RTL):**
   - The site uses `dir="rtl" lang="ar"` on the `<html>` element.
   - Use Tailwind **logical** layout utilities (`ps-*`, `pe-*`, `ms-*`, `me-*`, `start-*`, `end-*`) exclusively. Never use directional utilities like `pl-*`, `pr-*`, `ml-*`, `mr-*`, `left-*`, or `right-*`.
4. **Icon Mirroring:**
   - Mirror any icon indicating direction (back arrows, pagination chevrons, "عرض الكل" chevron) for RTL reading flow.
5. **Single Source of Truth (Money):**
   - Wrap all monetary formatting in the Blade component `<x-price>` and the JavaScript helper `formatMoney()`. No ad-hoc formatting allowed.

---

## 2. Design System Tokens (From `card.PNG`)

### Color Palette

| Token | Hex | Role |
|---|---|---|
| `primary` | `#4A2C4A` | Filled buttons, active nav/admin-sidebar state, headings on light bg (Dark Plum) |
| `secondary` | `#E8A2B6` | Secondary buttons, active size-selector pill, soft accents (Soft Pink) |
| `tertiary` | `#FFF9F0` | Page, section, and card backgrounds (Warm Cream / Background) |
| `neutral` | `#7B7678` | Body text, borders, muted UI elements, default icon state (Muted Grey) |
| `success` | `#16A34A` | "Delivered" badges (Standard green, pending client confirmation) |
| `warning` | `#F59E0B` | "Pending" badges (Standard amber, pending client confirmation) |

### Typography Stack
- **Headings (Arabic):** `Amiri`, `Aref Ruqaa`, `serif`
- **Headings (Latin/Brand):** `Bodoni Moda`, `serif`
- **Body & UI (Arabic):** `Cairo`, `Almarai`, `Tajawal`, `sans-serif`
- **Body & UI (Latin/Numbers):** `Be Vietnam Pro`, `sans-serif`

### UI Components Specs
- **Primary Button:** Filled `primary` bg, white text, large radius (`rounded-2xl` / `rounded-3xl`).
- **Secondary Button:** Light pink fill or outline, dark text, large radius.
- **Inverted Button:** Dark gray/near-black fill, white text (for images/dark overlays).
- **Outlined Button:** Transparent/light bg, visible border, dark text.
- **Icon Button:** Circular, `~40-44px` touch target. Supported colors: `primary`, `secondary`, `tertiary`, `danger-red`.
- **Search Input:** Pill-shaped (`rounded-full`), light gray/cream background, leading search icon at the start (RTL right) edge.
- **Badge/Label Pill:** Colored background with optional leading icon, `rounded-full` shape.
- **Cards:** Rounded corners (`rounded-2xl`/`rounded-3xl`) with soft low-opacity shadows.

---

## 3. Backend Integration Contract

We use **Blade templates + Alpine.js** with asynchronous `fetch()` API integration (no Livewire/Inertia).
- **API Endpoints:**
  - `POST /cart/items` — Add product to cart (payload: `product_id`, `size`, `addons[]`, `delivery_date`, `delivery_slot`, `personal_message`)
  - `PATCH /cart/items/{id}` — Update item quantity
  - `DELETE /cart/items/{id}` — Remove item
  - `POST /wishlist/toggle` — Optimistic toggle, reconcile with server response
- **Checkout Flow:** Standard POST form submission to the backend `OrderService`.
  - **Sham Cash:** Redirect to checkout gateway.
  - **Cash on Delivery (COD):** Redirect directly to order confirmation page.
- **Rules:**
  - Server is the final authority on pricing and totals. Client-side logic provides real-time preview totals, but values are reconciled against the backend JSON payload response upon mutation.
  - If companion routes or migrations do not exist yet, scaffold minimal Laravel controllers and routes, marking them with `// TODO: confirm with backend plan`.

---

## 4. 14-Phase Implementation Plan

We progress strictly in this order:

1. **Phase 1: Bootstrap & Layout Settings** -> Verify blank test page loads with fonts, color tokens, and RTL.
2. **Phase 2: Reusable Blade Components** -> Verify visual QA of all variants on `/dev/components`.
3. **Phase 3: Master Layout & Nav** -> Verify headers, footers, breadcrumbs, off-canvas nav.
4. **Phase 4: Homepage** -> Build pixel-close layout matching designs, load dynamic data.
5. **Phase 5: Product Catalog / Listing** -> Build filtering (price range, categories), sorting, pagination, reflecting state in URL.
6. **Phase 6: Product Detail** -> synched size selector, add-ons calculation, Alpine custom date-picker, personal message text.
7. **Phase 7: Cart** -> Inline updates, quantities, deletes, side-bar dynamic pricing fetched from backend.
8. **Phase 8: Checkout** -> recipient forms, delivery options, payment radio selects.
9. **Phase 9: Confirmation & Profile** -> Order success screen, history list, phone-auth forms, wishlist view.
10. **Phase 10: Admin Dashboard** -> Sidebar navigation, stat cards, orders/customers/products management tables.
11. **Phase 11: Alpine State Audit** -> Re-verify global stores (cart, wishlist), toast system, stepper, and date picker.
12. **Phase 12: RTL & Numeral Audit** -> Comprehensive check for currency strings, Western digits, and mirrored icons.
13. **Phase 13: Responsiveness & Accessibility** -> Viewport checks (`375px`, `768px`, `1024px`, `1440px`), contrast, and keyboard nav.
14. **Phase 14: Production Build & Final QA** -> Run `npm run build` and measure loading speeds.
