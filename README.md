# Themekraft Pricing Page

Shared pricing-page submodule used as the Go Pro screen across Themekraft plugins. Each host plugin sells a single Freemius product (its bundle, or its premium plugin when no bundle exists) across three site-license tiers.

## Mounting in a host plugin

1. Add as a git submodule:
   ```sh
   git submodule add git@github.com:Themekraft/pricing-page.git includes/admin/pricing-page
   ```
2. Require the entrypoint from the host plugin bootstrap:
   ```php
   require_once __DIR__ . '/includes/admin/pricing-page/pricing-page.php';
   ```
3. Register a submenu page whose slug ends in `bundle_screen` (the asset enqueuer matches that suffix) and uses `tk_pricing_page_render` as its callback:
   ```php
   add_submenu_page(
       'edit.php?post_type=my_plugin_cpt',
       __( 'Bundle', 'my-plugin' ),
       __( 'Go Pro!', 'my-plugin' ),
       'manage_options',
       'my_plugin_bundle_screen',
       'tk_pricing_page_render',
       99
   );
   ```
4. Register the `tk_pricing_page_config` filter to provide bundle credentials and tier data (see schema below).

## Filter contract: `tk_pricing_page_config`

```php
add_filter( 'tk_pricing_page_config', function ( $config ) {
    $config['heading']    = 'Choose the Best Plan for You';
    $config['subheading'] = 'Upgrade to unlock all premium features.';

    $config['bundle'] = array(
        'product_id' => '7487',                          // Freemius product or bundle id
        'plan_id'    => '12239',                         // Optional Freemius plan id
        'public_key' => 'pk_xxx',                        // Freemius product public key
        'name'       => 'BuddyForms Bundle',             // Optional: shown in the FS.Checkout modal
    );

    $bullets = array(
        array( 'label' => 'All BuddyForms add-ons included', 'highlight' => true ),
        'BuddyForms', 'BuddyForms ACF', 'BuddyForms Members',
        'One year of support', 'One year of updates',
    );

    $config['tiers'] = array(
        array(
            'id'        => 'personal',
            'name'      => 'Personal Plan',
            'sites'     => 'One Site',
            'licenses'  => '1',                           // Passed to FS.Checkout.open({licenses})
            'price'     => '99.99',
            'currency'  => '$',
            'period'    => '/year',
            'highlight' => false,
            'cta_label' => 'Get Started',
            'bullets'   => $bullets,
        ),
        array(
            'id'        => 'professional',
            'name'      => 'Professional Plan',
            'sites'     => 'Five Sites',
            'licenses'  => '5',
            'price'     => '149.99',
            'highlight' => true,
            'bullets'   => $bullets,
        ),
        array(
            'id'        => 'agency',
            'name'      => 'Agency Plan',
            'sites'     => 'Unlimited Sites',
            'licenses'  => 'unlimited',
            'price'     => '249.99',
            'bullets'   => $bullets,
        ),
    );

    return $config;
} );
```

### Bundle schema

| Key | Type | Notes |
|---|---|---|
| `product_id` | string | Freemius product or bundle id. Required. |
| `plan_id` | string | Optional Freemius plan id. |
| `public_key` | string | Freemius public key. Required. |
| `name` | string | Optional name shown in the Freemius checkout modal. |

### Tier schema

| Key | Type | Notes |
|---|---|---|
| `id` | string | Unique within the page (`personal` / `professional` / `agency` etc.). |
| `name` | string | Plan label shown in the badge. |
| `sites` | string | Subtitle (e.g. "Five Sites"). |
| `licenses` | string | Site count passed to `FS.Checkout.open({ licenses })`. Use `'unlimited'` (or `'0'`, depending on the bundle's plan config) for unlimited. |
| `price` | string | Price for this tier. |
| `currency` | string | Defaults to `$`. |
| `period` | string | Defaults to `/year`. |
| `highlight` | bool | Renders a highlighted card outline. |
| `cta_label` | string | Defaults to "Get Started". |
| `bullets` | array | Each item is a string or `{ label, url?, highlight? }`. Typically the same set across all tiers. |

If the bundle is missing required credentials or no tiers are registered, the page renders a notice prompting the host plugin to register the filter.

## Build

```sh
npm install
npm run build       # esbuild -> build/js, sass -> build/css
npm start           # watch mode for both
```

## Asset enqueue

`tk_pricing_page_enqueue_assets` registers itself on `admin_enqueue_scripts` and runs only on screens whose id contains `bundle_screen`. It enqueues `freemius-checkout` from Freemius's CDN, the built `pricing-page.js`, and `pricing-page.css`, and uses `wp_localize_script` to expose the filtered config to the JS module as `window.tkPricingPageConfig`.

## Backward compatibility

`buddyforms_bundle_screen_content()` is kept as a thin alias of `tk_pricing_page_render()` so host plugins still passing the old callback name to `add_submenu_page()` keep working during the migration. Drop the alias once every host plugin has migrated.
