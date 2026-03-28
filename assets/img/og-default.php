<?php
/**
 * Generates a simple branded SVG to serve as the Open Graph default image
 * when no real image is available.
 * Access via: /assets/img/og-default.jpg (via RewriteRule or direct PHP)
 * For production, replace with a real 1200x630 PNG/JPEG.
 */
header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400');
?>
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%"   stop-color="#1a1a2e"/>
      <stop offset="50%"  stop-color="#16213e"/>
      <stop offset="100%" stop-color="#0f3460"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="630" fill="url(#bg)"/>
  <text x="600" y="280" font-family="Segoe UI, sans-serif" font-size="80" font-weight="bold"
        fill="#f5c518" text-anchor="middle">Just Designs</text>
  <text x="600" y="370" font-family="Segoe UI, sans-serif" font-size="32"
        fill="rgba(255,255,255,0.8)" text-anchor="middle">
    Discover stunning free &amp; premium design images
  </text>
</svg>
