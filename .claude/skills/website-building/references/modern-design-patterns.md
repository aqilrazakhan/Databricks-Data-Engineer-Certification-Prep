# Modern Design Patterns for Technology Publications

## Table of Contents
- Design Philosophy
- Color Schemes and Typography
- Layout Patterns
- Article Card Designs
- Navigation Patterns
- Responsive Design
- Interactive Elements

## Design Philosophy for Tech Publications

### Core Principles
1. **Content First**: Prioritize readability and content discovery
2. **Performance**: Fast loading, optimized images, lazy loading
3. **Accessibility**: WCAG 2.1 AA compliance minimum
4. **Mobile-First**: Design for mobile, enhance for desktop
5. **Progressive Enhancement**: Core functionality works everywhere

### Visual Hierarchy
- Clear distinction between featured and regular content
- Generous whitespace for breathing room
- Consistent spacing system (8px grid)
- Strong typography hierarchy

## Color Schemes

### Modern Tech Publication Palettes

**Palette 1: Deep Blue + Vibrant Accent**
```css
:root {
    --primary: #0B88EE;        /* Bright blue */
    --secondary: #38CB89;      /* Green accent */
    --dark: #1A202C;           /* Near black */
    --gray: #718096;           /* Medium gray */
    --light: #F7FAFC;          /* Off-white */
    --white: #FFFFFF;
}
```

**Palette 2: Modern Gradient**
```css
:root {
    --gradient-start: #667eea;  /* Purple */
    --gradient-end: #764ba2;    /* Deep purple */
    --accent: #f093fb;          /* Pink accent */
    --dark: #2D3748;
    --gray: #A0AEC0;
    --light: #EDF2F7;
}
```

**Palette 3: Clean Minimalist**
```css
:root {
    --primary: #2563EB;         /* Blue */
    --secondary: #10B981;       /* Green */
    --dark: #111827;
    --gray: #6B7280;
    --light: #F9FAFB;
    --border: #E5E7EB;
}
```

## Typography

### Font Pairings

**Option 1: Modern Sans-Serif**
```css
:root {
    --font-heading: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --font-body: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --font-mono: 'Fira Code', 'Courier New', monospace;
}
```

**Option 2: Serif Headings + Sans Body**
```css
:root {
    --font-heading: 'Playfair Display', Georgia, serif;
    --font-body: 'Inter', sans-serif;
    --font-mono: 'JetBrains Mono', monospace;
}
```

**Option 3: Tech Publication Standard**
```css
:root {
    --font-heading: 'Poppins', sans-serif;
    --font-body: 'Open Sans', sans-serif;
    --font-mono: 'Source Code Pro', monospace;
}
```

### Typography Scale
```css
:root {
    --text-xs: 0.75rem;    /* 12px */
    --text-sm: 0.875rem;   /* 14px */
    --text-base: 1rem;     /* 16px */
    --text-lg: 1.125rem;   /* 18px */
    --text-xl: 1.25rem;    /* 20px */
    --text-2xl: 1.5rem;    /* 24px */
    --text-3xl: 1.875rem;  /* 30px */
    --text-4xl: 2.25rem;   /* 36px */
    --text-5xl: 3rem;      /* 48px */
}
```

## Layout Patterns

### Homepage Grid Layout

**Pattern 1: Featured + Grid**
```html
<div class="homepage-layout">
    <!-- Hero/Featured Section -->
    <section class="featured-section">
        <article class="featured-article">
            <!-- Large featured article -->
        </article>
    </section>

    <!-- Latest Articles Grid -->
    <section class="articles-grid">
        <h2>Latest Articles</h2>
        <div class="grid grid-3-col">
            <!-- Article cards -->
        </div>
    </section>

    <!-- Editor's Picks -->
    <section class="editors-picks">
        <h2>Editor's Picks</h2>
        <div class="grid grid-4-col">
            <!-- Curated articles -->
        </div>
    </section>
</div>
```

**Pattern 2: Magazine Style**
```html
<div class="magazine-layout">
    <div class="main-content">
        <!-- Primary content area -->
        <div class="content-blocks">
            <div class="hero-block"></div>
            <div class="article-list"></div>
            <div class="featured-grid"></div>
        </div>
    </div>
    <aside class="sidebar">
        <!-- Trending, Newsletter, etc -->
    </aside>
</div>
```

### CSS Grid System
```css
.grid {
    display: grid;
    gap: 2rem;
}

.grid-2-col {
    grid-template-columns: repeat(2, 1fr);
}

.grid-3-col {
    grid-template-columns: repeat(3, 1fr);
}

.grid-4-col {
    grid-template-columns: repeat(4, 1fr);
}

/* Responsive */
@media (max-width: 1024px) {
    .grid-4-col,
    .grid-3-col {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .grid-2-col,
    .grid-3-col,
    .grid-4-col {
        grid-template-columns: 1fr;
    }
}
```

## Article Card Designs

### Modern Card Design
```html
<article class="article-card">
    <div class="card-image">
        <img src="featured-image.jpg" alt="Article title">
        <span class="category-badge">AI & Machine Learning</span>
    </div>
    <div class="card-content">
        <h3 class="card-title">
            <a href="#">Article Title Goes Here</a>
        </h3>
        <p class="card-excerpt">Brief description or excerpt of the article...</p>
        <div class="card-meta">
            <img src="author.jpg" alt="Author" class="author-avatar">
            <div class="meta-info">
                <span class="author-name">John Doe</span>
                <span class="meta-separator">·</span>
                <span class="publish-date">Dec 20, 2025</span>
                <span class="meta-separator">·</span>
                <span class="reading-time">5 min read</span>
            </div>
        </div>
    </div>
</article>
```

### Card CSS
```css
.article-card {
    background: var(--white);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.article-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
}

.card-image {
    position: relative;
    aspect-ratio: 16/9;
    overflow: hidden;
}

.card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.article-card:hover .card-image img {
    transform: scale(1.05);
}

.category-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: var(--primary);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: var(--text-sm);
    font-weight: 600;
}

.card-content {
    padding: 1.5rem;
}

.card-title {
    font-size: var(--text-xl);
    font-weight: 700;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.card-title a {
    color: var(--dark);
    text-decoration: none;
}

.card-title a:hover {
    color: var(--primary);
}

.card-excerpt {
    color: var(--gray);
    font-size: var(--text-base);
    line-height: 1.6;
    margin-bottom: 1rem;
}

.card-meta {
    display: flex;
    align-items: center;
    font-size: var(--text-sm);
    color: var(--gray);
}

.author-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    margin-right: 0.5rem;
}

.meta-separator {
    margin: 0 0.5rem;
}
```

## Navigation Patterns

### Sticky Header with Search
```html
<header class="site-header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="/">
                    <img src="logo.svg" alt="Site Name">
                </a>
            </div>

            <nav class="main-nav">
                <ul>
                    <li><a href="/latest">Latest</a></li>
                    <li><a href="/categories">Categories</a></li>
                    <li><a href="/editors-picks">Editor's Picks</a></li>
                    <li><a href="/about">About</a></li>
                </ul>
            </nav>

            <div class="header-actions">
                <button class="search-toggle" aria-label="Search">
                    <svg><!-- Search icon --></svg>
                </button>
                <a href="/newsletter" class="btn-subscribe">Subscribe</a>
            </div>

            <button class="mobile-menu-toggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</header>
```

### Sticky Header CSS
```css
.site-header {
    position: sticky;
    top: 0;
    background: var(--white);
    border-bottom: 1px solid var(--border);
    z-index: 1000;
    transition: transform 0.3s, box-shadow 0.3s;
}

.site-header.scrolled {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 0;
}

.main-nav ul {
    display: flex;
    gap: 2rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.main-nav a {
    color: var(--dark);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.main-nav a:hover {
    color: var(--primary);
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.btn-subscribe {
    background: var(--primary);
    color: white;
    padding: 0.5rem 1.5rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: background 0.2s;
}

.btn-subscribe:hover {
    background: var(--primary-dark);
}
```

## Responsive Design

### Breakpoints
```css
/* Mobile First Approach */
:root {
    --breakpoint-sm: 640px;
    --breakpoint-md: 768px;
    --breakpoint-lg: 1024px;
    --breakpoint-xl: 1280px;
    --breakpoint-2xl: 1536px;
}

/* Container */
.container {
    width: 100%;
    padding-left: 1rem;
    padding-right: 1rem;
    margin-left: auto;
    margin-right: auto;
}

@media (min-width: 640px) {
    .container { max-width: 640px; }
}

@media (min-width: 768px) {
    .container { max-width: 768px; }
}

@media (min-width: 1024px) {
    .container { max-width: 1024px; }
}

@media (min-width: 1280px) {
    .container { max-width: 1200px; }
}
```

### Mobile Menu
```javascript
// Mobile navigation toggle
const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
const mainNav = document.querySelector('.main-nav');

mobileMenuToggle.addEventListener('click', () => {
    mainNav.classList.toggle('active');
    mobileMenuToggle.classList.toggle('active');
    document.body.classList.toggle('menu-open');
});
```

## Interactive Elements

### Smooth Scroll
```javascript
// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
```

### Lazy Loading Images
```html
<img
    data-src="image.jpg"
    alt="Description"
    class="lazy"
    src="placeholder.jpg"
>
```

```javascript
// Intersection Observer for lazy loading
const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.classList.remove('lazy');
            observer.unobserve(img);
        }
    });
});

document.querySelectorAll('img.lazy').forEach(img => {
    imageObserver.observe(img);
});
```

### Reading Progress Bar
```html
<div class="reading-progress">
    <div class="progress-bar"></div>
</div>
```

```javascript
// Reading progress indicator
window.addEventListener('scroll', () => {
    const winScroll = document.documentElement.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = (winScroll / height) * 100;
    document.querySelector('.progress-bar').style.width = scrolled + '%';
});
```

```css
.reading-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: rgba(0, 0, 0, 0.1);
    z-index: 9999;
}

.progress-bar {
    height: 100%;
    background: var(--primary);
    width: 0%;
    transition: width 0.1s ease;
}
```

## Performance Optimization

### Critical CSS
Inline critical CSS in `<head>` for above-the-fold content:
```html
<style>
    /* Critical CSS for initial render */
    body { margin: 0; font-family: var(--font-body); }
    .site-header { /* Header styles */ }
    /* Other critical styles */
</style>
```

### Deferred Loading
```html
<!-- Defer non-critical CSS -->
<link rel="preload" href="main.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="main.css"></noscript>

<!-- Defer JavaScript -->
<script src="main.js" defer></script>
```

### Image Optimization
- Use WebP with fallback
- Implement responsive images with srcset
- Lazy load below-the-fold images
- Optimize image dimensions (serve correct size)
