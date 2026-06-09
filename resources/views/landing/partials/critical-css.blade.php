<style>
    html { background: #020812; }
    body.landing-page { margin: 0; background: #020812; color: #f8fafc; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; overflow-x: hidden; }
    .landing-page *, .landing-page *::before, .landing-page *::after { box-sizing: border-box; }
    .landing-page a { color: inherit; text-decoration: none; }
    .landing-shell { width: calc(100% - 48px); max-width: 1180px; margin: 0 auto; }
    @supports (width: min(100%, 1px)) {
        .landing-shell { width: min(1180px, calc(100% - 48px)); max-width: none; }
    }
    .landing-nav { position: sticky; top: 0; z-index: 40; background: rgba(2, 8, 18, 0.94); border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
    .landing-nav-inner, .brand-link, .landing-menu, .landing-nav-actions { display: flex; align-items: center; }
    .landing-nav-inner { min-height: 64px; justify-content: space-between; gap: 24px; }
    .brand-link { gap: 9px; font-size: 20px; font-weight: 900; }
    .brand-link img { width: 36px; height: 36px; display: block; flex: 0 0 auto; border-radius: 50%; object-fit: cover; }
    .landing-menu { gap: 28px; font-size: 13px; font-weight: 700; }
    .landing-nav-actions { gap: 12px; }
    .btn { display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 0 18px; border-radius: 999px; font-weight: 800; }
    .btn--primary { background: #ff6a00; color: #111; }
    .landing-mobile-toggle, .landing-menu-open, .landing-mobile-backdrop, .landing-mobile-drawer { display: none; }
    .landing-page img { max-width: 100%; height: auto; }
    .landing-section { padding: 56px 0; }
    .proof-strip { display: grid; gap: 24px; padding: 22px; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 18px; background: rgba(15, 25, 44, 0.72); }
    .proof-logos { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; }
    .proof-logos a { width: 54px; height: 54px; display: inline-flex; align-items: center; justify-content: center; overflow: hidden; border-radius: 50%; background: rgba(255, 255, 255, 0.08); }
    .proof-logos img { width: 100%; height: 100%; display: block; object-fit: cover; }
    .proof-metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
    @supports (grid-template-columns: repeat(3, minmax(0, 1fr))) {
        .proof-metrics { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    .proof-metrics strong { display: grid; gap: 4px; color: #ff6a00; font-size: 24px; }
    .proof-metrics span { color: #9aa8bd; font-size: 12px; font-weight: 600; }
    .portfolio-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
    @supports (grid-template-columns: repeat(3, minmax(0, 1fr))) {
        .portfolio-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    .portfolio-card { display: grid; gap: 12px; min-width: 0; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 18px; background: rgba(15, 25, 44, 0.72); }
    .portfolio-media { height: 180px; overflow: hidden; background: rgba(255, 255, 255, 0.06); }
    @supports (aspect-ratio: 16 / 10) {
        .portfolio-media { height: auto; aspect-ratio: 16 / 10; }
    }
    .portfolio-media img { width: 100%; height: 100%; display: block; object-fit: cover; }
    .portfolio-copy { display: grid; gap: 4px; padding: 0 16px 16px; }
    .section-head { max-width: 720px; margin: 0 auto 28px; text-align: center; }
    .hero-content, .catalog-copy, .feature-card, .plan-card, .final-card { min-width: 0; }
    .hero-badge, .hero-trust, .action-row { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
    .hero-content h1 { max-width: 780px; margin: 18px 0 14px; font-size: 44px; line-height: 0.96; }
    @supports (font-size: clamp(36px, 10vw, 64px)) {
        .hero-content h1 { font-size: clamp(36px, 10vw, 64px); }
    }
    .hero-content p { max-width: 580px; color: #b8c4d6; line-height: 1.65; }
    .steps, .feature-grid, .plans-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
    @supports (grid-template-columns: repeat(3, minmax(0, 1fr))) {
        .steps, .feature-grid, .plans-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    .step-card, .feature-card, .plan-card { display: grid; gap: 12px; padding: 20px; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 18px; background: rgba(15, 25, 44, 0.72); }
    .step-card span, .feature-card span, .plan-badge { width: auto; max-width: 100%; border-radius: 999px; padding: 6px 10px; background: rgba(255, 106, 0, 0.14); color: #ff9a3d; font-size: 11px; font-weight: 900; }
    @supports (width: max-content) {
        .step-card span, .feature-card span, .plan-badge { width: max-content; }
    }
    .catalog-preview { margin-top: 28px; padding: 24px; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 18px; background: rgba(15, 25, 44, 0.72); }
    .catalog-copy { display: grid; gap: 18px; }
    .plan-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; }
    .plan-price { display: flex; align-items: baseline; gap: 6px; }
    .plan-price strong { font-size: 34px; }
    .plan-features { display: grid; gap: 8px; margin: 0; padding-left: 18px; color: #cbd5e1; }
    .final-cta { padding: 56px 0; }
    .final-card { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 22px; padding: 24px; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 22px; background: rgba(15, 25, 44, 0.82); }
    @supports (grid-template-columns: auto minmax(0, 1fr) auto) {
        .final-card { grid-template-columns: auto minmax(0, 1fr) auto; }
    }
    .final-icon { width: 72px; height: 72px; overflow: hidden; border-radius: 18px; }
    .final-icon img { width: 100%; height: 100%; object-fit: cover; }
    .whatsapp-float { position: fixed; right: 18px; bottom: 18px; z-index: 50; display: inline-flex; align-items: center; gap: 10px; max-width: calc(100vw - 36px); padding: 10px 14px; border-radius: 999px; background: #25d366; color: #03140a; font-weight: 900; box-shadow: 0 16px 34px rgba(37, 211, 102, 0.28); }
    .whatsapp-float img { width: 24px; height: 24px; flex: 0 0 auto; }
    @media (max-width: 820px) {
        .landing-shell { width: calc(100% - 28px); max-width: 1180px; }
        @supports (width: min(100%, 1px)) {
            .landing-shell { width: min(100% - 28px, 1180px); max-width: none; }
        }
        .landing-menu, .landing-nav-actions .btn, .login-link { display: none; }
        .landing-menu-open { width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 12px; }
        .landing-menu-open span, .landing-menu-open span::before, .landing-menu-open span::after { width: 18px; height: 2px; display: block; border-radius: 999px; background: #fff; }
        .landing-menu-open span { position: relative; }
        .landing-menu-open span::before, .landing-menu-open span::after { content: ""; position: absolute; left: 0; }
        .landing-menu-open span::before { top: -6px; }
        .landing-menu-open span::after { top: 6px; }
        .landing-mobile-toggle:checked ~ .landing-mobile-backdrop { position: fixed; inset: 0; z-index: 60; display: block; background: rgba(0, 0, 0, 0.72); }
        .landing-mobile-toggle:checked ~ .landing-mobile-drawer { position: fixed; inset: 0 auto 0 0; z-index: 70; width: 88vw; max-width: 360px; height: 100vh; display: flex; flex-direction: column; gap: 24px; overflow-y: auto; padding: 28px; background: #081120; }
        .mobile-drawer-menu, .mobile-drawer-actions { display: grid; gap: 10px; }
        .landing-section { padding: 42px 0; }
        .proof-metrics, .portfolio-grid, .steps, .feature-grid, .plans-grid, .final-card { grid-template-columns: 1fr; }
        .proof-logos a { width: 48px; height: 48px; }
        .hero-content h1 { font-size: 38px; }
        @supports (font-size: clamp(34px, 11vw, 48px)) {
            .hero-content h1 { font-size: clamp(34px, 11vw, 48px); }
        }
        .final-card { text-align: left; }
        .final-card-action { width: 100%; }
        .whatsapp-float { right: 14px; bottom: 14px; }
    }
</style>
