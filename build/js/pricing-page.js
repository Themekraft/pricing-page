(() => {
  // src/js/pricing-page.js
  (function() {
    const config = window.tkPricingPageConfig || { bundle: {}, tiers: [] };
    const bundle = config.bundle || {};
    if (!bundle.product_id || !bundle.public_key) {
      return;
    }
    if (!Array.isArray(config.tiers) || !config.tiers.length) {
      return;
    }
    if (typeof FS === "undefined" || typeof FS.Checkout !== "function") {
      return;
    }
    const checkoutOptions = {
      product_id: bundle.product_id,
      public_key: bundle.public_key
    };
    if (bundle.plan_id) {
      checkoutOptions.plan_id = bundle.plan_id;
    }
    if (bundle.name) {
      checkoutOptions.name = bundle.name;
    }
    document.addEventListener("DOMContentLoaded", function() {
      const checkout = new FS.Checkout(checkoutOptions);
      document.querySelectorAll(".tk-pricing__button[data-tier-id]").forEach((button) => {
        button.addEventListener("click", (e) => {
          e.preventDefault();
          const card = button.closest(".tk-pricing__box");
          const licenses = card ? card.dataset.licenses : "1";
          checkout.open({ licenses });
        });
      });
      window.tkPricingPage = checkout;
    });
  })();
})();
