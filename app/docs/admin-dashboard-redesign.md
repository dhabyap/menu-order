# Admin Dashboard Redesign Proposal

## Current state
- Single-page scroll layout: intro → stats → menu table → settings → orders table → activity log
- All panels inline, no navigation
- Wide tables cause horizontal scroll
- Stats at top, but everything below requires full scroll

## Goals
- Reduce scrolling for daily tasks
- Clear section separation
- Keep mobile usable
- Maintain existing functionality (sort, edit, toggle, export, detail dialog)

## Proposed layout

### 1. Top bar (sticky)
- Brand + store name
- Nav tabs: Ringkasan | Menu | Pesanan | Pengaturan
- Right: Lihat katalog | Keluar

### 2. Ringkasan tab (overview)
- Stat cards row (same 4 cards, compact)
- Quick actions: Tambah Menu | Export CSV | Refresh Status
- Recent orders mini-table (last 5, clickable → detail dialog)
- Activity log mini (last 10)

### 3. Menu tab
- Full menu table only
- Tambah Menu button sticky top of section
- Search/filter input (by name/category)
- Keep sort, edit, toggle, delete

### 4. Pesanan tab
- Orders table only
- Refresh + Export CSV sticky
- Filter by status (Menunggu/Diproses/Selesai/Dibatalkan)
- Detail dialog unchanged

### 5. Pengaturan tab
- Settings form only
- No distraction from menu/orders

## UX improvements
- Stat cards clickable shortcuts: total orders → Pesanan tab, menu tersedia → Menu tab
- Sticky sub-actions per tab (buttons float below top bar)
- Empty states with guidance per tab
- Better table density: smaller padding, sticky header
- Collapse long item lists in orders table (show first item + "dan N lainnya")

## Tech approach
- Keep single `admin.blade.php` + include partials
- Add tab switching via JS (no page reload)
- Use existing CSS variables (green, plum, gold, rose, line)
- Maintain existing features (sortable, dialogs, exports)

## Not changing
- Existing data endpoints/controllers
- Order detail dialog structure
- Sortable menu reordering
- Store branding/language

## Priority
P1: tab navigation + stat shortcuts
P2: search/filter menu + status filter orders
P3: sticky actions + density tuning
