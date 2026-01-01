# UI Design - Online Examination System

## Design Inspiration

The UI has been updated with a professional design aesthetic.

## Color Palette

### Primary Colors
- **Primary Teal**: `#1a4d4d` - Main brand color (sidebar, buttons, navbar)
- **Primary Dark**: `#0d2626` - Darker variant for hover states
- **Accent Gold**: `#d4a574` - Accent color for highlights and borders
- **Light Background**: `#f5f5f5` - Page background

### Usage
```css
:root {
    --primary-teal: #1a4d4d;
    --primary-dark: #0d2626;
    --accent-gold: #d4a574;
    --light-bg: #f5f5f5;
}
```

## Typography

- **Font Family**: `-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif`
- **Headings**: Bold (700), slightly increased letter-spacing
- **Body**: Regular (400-500), clean and readable

## Components

### 1. Login/Register Pages
- Clean white cards on light background
- Teal header with gold accent border
- Heart pulse icon (nursing theme)
- Rounded corners (12px)
- Subtle shadows

### 2. Admin Sidebar
- Dark teal background (`#1a4d4d`)
- Gold accent for active items
- Left border highlight on hover/active
- Heart pulse icon in header
- User name display at bottom of header

### 3. Student Navbar
- Teal background
- Horizontal navigation
- Dropdown for user menu
- Consistent with admin color scheme

### 4. Cards
- White background
- 12px border radius
- Subtle shadows (0 2px 8px rgba(0,0,0,0.06))
- Hover effect: lift and stronger shadow
- Gold left border for exam cards

### 5. Buttons
- Primary: Teal background
- Hover: Darker teal with lift effect
- Rounded corners (8px)
- Font weight: 600

### 6. Welcome Banners
- Gradient background (teal to dark)
- White text
- Motivational quotes
- Icon decoration

### 7. Stat Cards
- White background
- Icon with colored background (opacity 10%)
- Hover: lift effect
- Clean typography

## Design Principles

1. **Professional**: Clean, modern, healthcare-appropriate
2. **Consistent**: Same color palette throughout
3. **Accessible**: Good contrast ratios
4. **Responsive**: Works on all devices
5. **Intuitive**: Clear navigation and hierarchy

## Icon Usage

- **Brand**: `bi-heart-pulse-fill` (nursing theme)
- **Dashboard**: `bi-speedometer2`
- **Exams**: `bi-file-earmark-text`
- **Questions**: `bi-question-circle`
- **Students**: `bi-people`
- **Results**: `bi-bar-chart`
- **Settings**: `bi-gear`

## Spacing

- Card padding: 1.5-2rem
- Button padding: 0.625rem 1.25rem
- Section margins: 1-2rem
- Border radius: 8-12px

## Shadows

- **Light**: `0 2px 8px rgba(0,0,0,0.06)` - Default cards
- **Medium**: `0 4px 16px rgba(0,0,0,0.1)` - Hover state
- **Strong**: `0 4px 12px rgba(26, 77, 77, 0.3)` - Button hover

## Transitions

- Duration: 0.2-0.3s
- Easing: ease / ease-in-out
- Properties: all, transform, background, box-shadow

## Responsive Breakpoints

- Mobile: < 768px
- Tablet: 768px - 991px
- Desktop: 992px+

## Branding

- **Name**: Online Exam System
- **Tagline**: "Prepare with confidence. Excel with knowledge."
- **Theme**: Healthcare/Nursing education
- **Icon**: Heart pulse (medical theme)

## Implementation Notes

All pages have been updated with:
- Consistent color scheme
- Matching typography
- Unified component styles
- Responsive design
- Professional appearance
