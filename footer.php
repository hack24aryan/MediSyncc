<?php
/**
 * footer.php — Shared site footer for MediSyncc
 * Include this file before </body> on every user-facing page.
 * Inherits CSS custom properties from the host page's :root theme.
 */
?>
<style>
    /* ===== MEDISYNCC SHARED FOOTER ===== */
    .medisync-footer {
        width: 100%;
        padding: 22px 5%;
        text-align: center;
        font-family: 'Poppins', sans-serif;
        font-size: 0.83rem;
        color: var(--text-muted, #64748b);
        border-top: 1px solid var(--border, rgba(0, 0, 0, 0.07));
        background: transparent;
        box-sizing: border-box;
        line-height: 1.7;
    }

    /* Dark mode support — works with both [data-theme="dark"] and .dark class */
    [data-theme="dark"] .medisync-footer,
    .dark .medisync-footer {
        color: var(--text-muted, #94a3b8);
        border-top-color: var(--border, rgba(255, 255, 255, 0.08));
    }

    .medisync-footer p {
        margin: 3px 0;
    }

    .medisync-footer .footer-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        opacity: 0.65;
        margin-bottom: 5px;
    }

    .medisync-footer .footer-authors {
        font-weight: 600;
        color: var(--text-main, var(--text, #1e293b));
        font-size: 0.88rem;
    }

    [data-theme="dark"] .medisync-footer .footer-authors,
    .dark .medisync-footer .footer-authors {
        color: var(--text-main, var(--text, #f1f5f9));
    }

    .medisync-footer .footer-authors a {
        color: inherit;
        text-decoration: none;
        border-bottom: 1px solid transparent;
        transition: color 0.25s ease, border-color 0.25s ease;
    }

    .medisync-footer .footer-authors a:hover {
        color: var(--accent, #0ea5e9);
        border-bottom-color: var(--accent, #0ea5e9);
    }

    .medisync-footer .footer-copy {
        font-size: 0.78rem;
        opacity: 0.6;
        margin-top: 4px;
    }

    /* Responsive — no layout changes needed, just tighten padding on mobile */
    @media (max-width: 600px) {
        .medisync-footer {
            padding: 18px 4%;
            font-size: 0.80rem;
        }
    }
</style>

<footer class="medisync-footer" role="contentinfo">
    <p class="footer-label">Collaboratively Developed by</p>
    <p class="footer-authors">
        <a href="https://github.com/hack24aryan" target="_blank" rel="noopener noreferrer">Tapash Kumar Das</a>
        &amp;
        <a href="https://github.com/TrishaTarwey" target="_blank" rel="noopener noreferrer">Trisha Tarwey</a>
    </p>
    <p class="footer-copy">&copy; 2026 MediSync. All Rights Reserved.</p>
</footer>
