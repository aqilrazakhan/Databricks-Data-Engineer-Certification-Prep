---
name: website-building
description: Complete WordPress website development for technology publications, blogs, and content platforms. Use when building WordPress sites, creating custom themes, developing plugins, or deploying publication websites. Triggers include requests to build websites, create WordPress themes, develop publication platforms, implement blog designs, or create content management systems for articles and blogs.
---

# Website Building

## Overview

This skill provides comprehensive guidance for building modern, performant WordPress websites optimized for technology publications and content platforms. It includes complete WordPress theme boilerplate, plugin development patterns, modern design systems, SEO optimization, and deployment strategies.

## Quick Start

When asked to build a website, follow these steps:

1. **Understand Requirements**: Determine the website type (publication, blog, corporate), key features needed, and design preferences
2. **Select Architecture**: Choose between custom theme, child theme, or theme + plugins approach
3. **Use Theme Boilerplate**: Copy and customize the complete WordPress theme from `assets/theme-boilerplate/`
4. **Implement Features**: Build custom functionality using plugin templates from references
5. **Optimize & Deploy**: Apply SEO and performance optimizations, then deploy

## Core Capabilities

### 1. WordPress Theme Development

**When to use:** Building custom WordPress themes for publications, blogs, or content sites

**Boilerplate location:** `assets/theme-boilerplate/` - Complete, production-ready WordPress theme

The theme boilerplate includes:
- Modern card-based layout optimized for articles
- Responsive design (mobile-first approach)
- SEO-optimized with schema markup, Open Graph, and Twitter Cards
- Performance optimized (lazy loading, deferred JS, minification)
- Accessibility compliant (WCAG 2.1 AA)
- Customizer integration for colors and branding
- Widget areas and custom navigation

**Key files in boilerplate:**
- `style.css` - Theme stylesheet with modern design system
- `functions.php` - Theme functionality and hooks
- `header.php`, `footer.php` - Header and footer templates
- `index.php` - Main template file (blog index)
- `single.php` - Single post template
- `template-parts/content-card.php` - Article card component
- `inc/template-tags.php` - Custom template functions
- `inc/customizer.php` - Theme customizer settings
- `assets/css/main.css` - Additional styles
- `assets/js/navigation.js`, `assets/js/main.js` - JavaScript functionality

**Customization workflow:**
1. Copy `assets/theme-boilerplate/` to your project
2. Update `style.css` header with theme name, description, author
3. Customize colors in `:root` CSS variables
4. Modify layout in template files as needed
5. Add custom functionality to `functions.php`
6. Test and deploy

**Reference documentation:** See `references/theme-architecture.md` for complete WordPress theme structure, template hierarchy, and best practices.

### 2. Modern Design Implementation

**When to use:** Creating visually appealing, modern publication layouts

The skill includes comprehensive modern design patterns:
- Card-based article layouts
- Sticky navigation with scroll behavior
- Reading progress indicators
- Responsive grid systems
- Typography scales and font pairings
- Color schemes optimized for tech publications
- Interactive elements (hover effects, lazy loading, smooth scroll)

**Reference documentation:** See `references/modern-design-patterns.md` for:
- Complete design philosophy
- Color schemes and palettes (3 pre-designed options)
- Typography systems and font pairings
- Layout patterns (grid systems, magazine layouts, featured sections)
- Article card designs with code examples
- Navigation patterns (sticky headers, mobile menus)
- Responsive breakpoints and mobile-first approach
- Interactive JavaScript components

**Implementation approach:**
1. Choose a color scheme from the reference or customize
2. Select typography pairing
3. Implement grid layout for article cards
4. Add interactive elements (scroll effects, lazy loading)
5. Ensure mobile responsiveness

### 3. Plugin Development

**When to use:** Adding custom functionality beyond theme capabilities (custom post types, meta boxes, Ajax features, settings pages)

**Reference documentation:** See `references/plugin-development.md` for:
- Complete plugin structure and headers
- Custom post type creation (e.g., Articles with custom fields)
- Meta boxes for additional fields (reading time, featured status, editor's picks)
- Settings API implementation
- Ajax functionality with jQuery
- Database operations with wpdb
- Security best practices (nonces, sanitization, escaping)

**Common plugin examples included:**
1. **Articles Manager** - Custom post type with meta boxes for publication metadata
2. **Site Settings Manager** - Settings page using Settings API
3. **Ajax Handler** - Load more posts functionality with Ajax
4. **Custom Database** - Creating and managing custom database tables

**Development workflow:**
1. Identify functionality that belongs in a plugin (vs. theme)
2. Copy relevant plugin template from references
3. Customize plugin header and functionality
4. Implement hooks and filters
5. Add admin UI if needed
6. Test and activate

### 4. SEO & Performance Optimization

**When to use:** Optimizing websites for search engines and page speed

The skill provides battle-tested optimization techniques:

**SEO Features (auto-included in theme boilerplate):**
- Meta descriptions
- Open Graph tags for social sharing
- Twitter Card tags
- Schema.org structured data (Article markup)
- Semantic HTML5 markup
- Proper heading hierarchy

**Performance Optimizations (auto-included in theme boilerplate):**
- Lazy loading images
- Deferred JavaScript
- Minimized HTTP requests
- Optimized asset loading
- Browser caching headers
- GZIP compression

**Reference documentation:** See `references/seo-performance.md` for:
- Complete SEO implementation code
- XML sitemap generation
- Image optimization (responsive images, WebP support)
- Caching strategies (object caching, fragment caching)
- Core Web Vitals optimization (LCP, CLS, FID)
- CDN integration
- Security headers
- Performance monitoring

**Optimization checklist:**
1. Implement meta tags and schema markup
2. Enable lazy loading for images
3. Defer non-critical JavaScript
4. Optimize image sizes (use responsive images)
5. Enable browser caching
6. Add GZIP compression
7. Monitor Core Web Vitals

### 5. Publication-Specific Features

**When to use:** Building technology publications, news sites, or content platforms

The theme boilerplate is optimized for publications with:

**Built-in features:**
- Article card grid layouts (2, 3, or 4 columns)
- Category badges on articles
- Reading time calculation
- Author information and bios
- Featured images with automatic aspect ratios
- Excerpt handling
- Pagination
- Related posts capability

**Easily add with plugins:**
- Editor's Picks section
- Featured articles spotlight
- Newsletter signup integration
- Social sharing buttons
- Comment system
- Author profiles with avatars
- Multi-author support
- View counters

**Implementation for publication sites:**
1. Use theme boilerplate as foundation
2. Create "Featured" and "Editor's Pick" taxonomies
3. Build custom query for featured section on homepage
4. Add newsletter plugin/integration
5. Configure multi-author workflow
6. Implement social sharing

## Workflow Decision Tree

```
User Request → Determine Site Type
    │
    ├─ Publication/Blog/News Site
    │   └─ Use theme-boilerplate as starting point
    │       ├─ Customize colors and typography
    │       ├─ Add publication-specific features (featured, editor's picks)
    │       ├─ Implement newsletter integration
    │       └─ Deploy
    │
    ├─ Corporate/Business Site
    │   └─ Use theme-boilerplate, simplify
    │       ├─ Remove blog-specific features
    │       ├─ Add business sections (services, team, contact)
    │       ├─ Customize for brand
    │       └─ Deploy
    │
    └─ Custom Complex Site
        └─ Start with theme-boilerplate
            ├─ Add custom post types (via plugins)
            ├─ Implement custom features (via plugins)
            ├─ Advanced customization
            └─ Deploy
```

## Building a Technology Publication Website

**Example scenario:** "Build a website like TowardsDataScience.com or TowardsAI.net"

**Step-by-step process:**

1. **Setup WordPress Theme**
   - Copy `assets/theme-boilerplate/` to `wp-content/themes/yourtheme/`
   - Update theme header in `style.css`
   - Activate theme in WordPress admin

2. **Customize Branding**
   - Choose color scheme (see `references/modern-design-patterns.md`)
   - Update CSS variables in `style.css`
   - Add logo via Customizer
   - Configure site title and tagline

3. **Configure Publication Features**
   - Create categories (AI, Machine Learning, Data Science, etc.)
   - Set up author profiles
   - Configure permalink structure
   - Create menu structure

4. **Add Custom Functionality** (optional)
   - Create Articles Manager plugin for custom fields
   - Add "Featured" and "Editor's Pick" meta boxes
   - Implement newsletter signup widget
   - Add social sharing plugin

5. **Content Setup**
   - Import sample content for testing
   - Create initial articles
   - Add author bios
   - Configure sidebar widgets

6. **Optimization**
   - Verify SEO tags are working (view source)
   - Test mobile responsiveness
   - Check page speed with Lighthouse
   - Optimize images
   - Enable caching

7. **Deployment**
   - Choose hosting (WP Engine, SiteGround, or VPS)
   - Configure domain
   - Deploy theme and plugins
   - Set up SSL certificate
   - Configure backups

## Deployment Strategies

**Option 1: Managed WordPress Hosting** (Recommended for beginners)
- Services: WP Engine, Kinsta, SiteGround
- Pros: Automatic updates, backups, security, support
- Cons: Higher cost

**Option 2: VPS Deployment** (For advanced users)
- Services: DigitalOcean, AWS, Linode
- Pros: Full control, cost-effective, scalable
- Cons: Requires technical knowledge

**Option 3: Traditional Shared Hosting**
- Services: Bluehost, HostGator, DreamHost
- Pros: Affordable, easy setup
- Cons: Limited resources, slower performance

**Deployment checklist:**
1. Install WordPress on hosting
2. Upload theme via FTP or admin panel
3. Activate theme
4. Install required plugins
5. Configure permalinks (Settings → Permalinks)
6. Set up SSL certificate
7. Configure caching plugin (W3 Total Cache or WP Super Cache)
8. Import content or create initial pages
9. Configure backups (UpdraftPlus or BackupBuddy)
10. Test thoroughly

## Common Customizations

### Change Color Scheme
Edit CSS variables in `style.css`:
```css
:root {
    --primary: #0B88EE;    /* Your primary color */
    --secondary: #38CB89;  /* Your secondary color */
}
```

### Add Custom Post Type
Create plugin using template from `references/plugin-development.md`, Articles Manager section.

### Modify Article Card Layout
Edit `template-parts/content-card.php` to change card structure, add/remove elements.

### Change Grid Columns
In `index.php`, change `grid-3-col` to `grid-2-col` or `grid-4-col`.

### Add Newsletter Signup
1. Install plugin (Mailchimp, ConvertKit, Newsletter)
2. Add widget to sidebar or footer widget area
3. Style with theme CSS variables

## Best Practices

1. **Always use child themes** for heavy customizations to preserve updateability
2. **Keep plugins minimal** - only install what you need
3. **Optimize images before upload** - use WebP when possible
4. **Test on mobile devices** - mobile traffic often exceeds desktop
5. **Monitor performance** - use Google PageSpeed Insights regularly
6. **Keep WordPress updated** - for security and features
7. **Use version control** - Git for theme development
8. **Backup regularly** - automated daily backups
9. **Validate HTML** - ensure semantic markup
10. **Follow WordPress coding standards** - for maintainability

## Troubleshooting

**Issue:** Theme not displaying correctly
- Check if WordPress is using correct template file
- Verify CSS/JS files are enqueued properly
- Clear browser cache and WordPress cache

**Issue:** Slow page load times
- Enable caching plugin
- Optimize images (compress, use WebP)
- Defer JavaScript
- Use a CDN

**Issue:** Mobile menu not working
- Verify `navigation.js` is loaded
- Check for JavaScript errors in console
- Ensure jQuery is loaded

## Additional Resources

For detailed reference documentation on specific topics, consult:
- `references/theme-architecture.md` - Complete WordPress theme structure
- `references/modern-design-patterns.md` - Design systems and UI patterns
- `references/plugin-development.md` - Plugin creation and examples
- `references/seo-performance.md` - Optimization techniques
- `assets/theme-boilerplate/` - Production-ready starter theme
