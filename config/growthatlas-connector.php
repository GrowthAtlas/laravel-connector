<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GrowthAtlas API Key
    |--------------------------------------------------------------------------
    | The API key GrowthAtlas uses to authenticate requests to your connector.
    |
    | You can either set GROWTHATLAS_API_KEY in .env (used as the default) OR
    | manage it from the GrowthAtlas Connector admin page (recommended). A value
    | saved from the admin page is stored in the database and overrides .env.
    */
    'api_key' => env('GROWTHATLAS_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | HMAC Signing Secret (optional)
    |--------------------------------------------------------------------------
    | When set, GrowthAtlas signs requests with HMAC-SHA256 and your connector
    | verifies the signature. Must match the secret in GrowthAtlas.
    |
    | Like the API key, this can be set in .env OR managed (and rotated) from
    | the admin page, where a saved value overrides .env.
    */
    'signing_secret' => env('GROWTHATLAS_SIGNING_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Publishing Configuration
    |--------------------------------------------------------------------------
    | Configure which Eloquent model receives published content and how fields
    | are mapped from the GrowthAtlas payload to your model's columns.
    */
    'publishing' => [
        // The fully qualified class name of your blog post / article model.
        'model' => env('GROWTHATLAS_PUBLISH_MODEL', \App\Models\Post::class),

        // Column mapping: growthatlas_field => your_model_column
        // Available GA fields: title, slug, excerpt, body, body_html, meta_title,
        // meta_description, featured_image_url, featured_image_alt, target_keyword,
        // language, seo_score, word_count, growthatlas_draft_id, growthatlas_brief_id
        'fields' => [
            'title'              => 'title',
            'slug'               => 'slug',
            'excerpt'            => 'excerpt',
            'body'               => 'content',
            'body_html'          => 'content_html',
            'meta_title'         => 'meta_title',
            'meta_description'   => 'meta_description',
            // Featured image — value is an absolute CDN URL (e.g. https://images.unsplash.com/...).
            // Do NOT pass through Storage::url() as it will corrupt absolute URLs.
            // Map to whatever column stores the image path/URL on your model:
            // 'featured_image_url' => 'featured_image_path',
            // 'featured_image_alt' => 'featured_image_alt',
        ],

        // Status value mapping: growthatlas_status => your_model_status
        'status_map' => [
            'draft'     => 'draft',
            'published' => 'published',
        ],

        // Column name for publish_status in your model
        'status_column' => 'status',

        // Column for tracking published_at timestamp.
        // When set and publish_status is "published", the connector automatically
        // writes now() to this column so your model's visibility checks pass.
        // Set to null to disable (e.g. if your model sets it via an observer).
        'published_at_column' => 'published_at',

        // Column for the growthatlas_draft_id meta (used for idempotency)
        'growthatlas_id_column' => 'growthatlas_draft_id',

        // Optional public path prefix prepended to the slug when building the
        // URL returned to GrowthAtlas (e.g. "blog" → https://site.com/blog/{slug}).
        // When set, this takes precedence over Model::getUrl(). Leave empty/null
        // to use getUrl() when available, otherwise url($slug).
        'url_prefix' => env('GROWTHATLAS_URL_PREFIX'),

        // Default status applied when a payload arrives without publish_status.
        // Can be overridden from the admin page.
        'default_publish_status' => 'draft',
    ],

    /*
    |--------------------------------------------------------------------------
    | Entities
    |--------------------------------------------------------------------------
    | Map entity types to Eloquent models. GrowthAtlas will query /entities
    | to discover products, categories, etc. from your site.
    */
    'entities' => [
        // 'product' => \App\Models\Product::class,
        // 'category' => \App\Models\Category::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages Source
    |--------------------------------------------------------------------------
    | Configure how GrowthAtlas discovers your existing content for SEO analysis.
    | Options: 'sitemap' | 'eloquent' | 'callback'
    */
    'pages' => [
        'source' => 'eloquent', // sitemap | eloquent | callback

        // For 'eloquent' source: the model and URL column
        'model' => env('GROWTHATLAS_PUBLISH_MODEL', \App\Models\Post::class),
        'url_column' => 'slug', // column containing the full URL or slug

        // For 'sitemap' source: URL to your sitemap.xml
        // 'sitemap_url' => env('GROWTHATLAS_SITEMAP_URL'),

        // For 'callback' source: a callable that returns an array of pages
        // 'callback' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    'route_prefix' => 'api/growthatlas/v1',
    'route_middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Inbound Request Logging
    |--------------------------------------------------------------------------
    | When true, every request from GrowthAtlas is written to the
    | growthatlas_inbound_requests table (publish + run the migration first).
    | This powers the recent-requests table on the Filament admin page.
    | Disable in high-traffic environments if you don't need the audit trail.
    |
    | This is the default only — it can be toggled from the admin page, where the
    | saved value overrides .env.
    */
    'log_inbound' => env('GROWTHATLAS_LOG_INBOUND', false),

    /*
    |--------------------------------------------------------------------------
    | Filament Integration (optional)
    |--------------------------------------------------------------------------
    | Register the GrowthAtlas Connector Status page in your Filament panel by
    | adding the plugin to your panel provider:
    |
    |   use GrowthAtlas\Connector\Filament\GrowthAtlasConnectorPlugin;
    |
    |   public function panel(Panel $panel): Panel
    |   {
    |       return $panel
    |           ->plugin(GrowthAtlasConnectorPlugin::make())
    |           // ...
    |   }
    |
    | Requires filament/filament ^4.0 to be installed.
    */
];
