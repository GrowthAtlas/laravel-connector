<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GrowthAtlas API Key
    |--------------------------------------------------------------------------
    | This is the API key GrowthAtlas uses to authenticate requests to your
    | connector. Set this in your .env file as GROWTHATLAS_API_KEY.
    */
    'api_key' => env('GROWTHATLAS_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | HMAC Signing Secret (optional)
    |--------------------------------------------------------------------------
    | When set, GrowthAtlas will sign all requests with HMAC-SHA256 and your
    | connector will verify the signature. Must match the secret in GrowthAtlas.
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
        'fields' => [
            'title'            => 'title',
            'slug'             => 'slug',
            'excerpt'          => 'excerpt',
            'body'             => 'content',
            'body_html'        => 'content_html',
            'meta_title'       => 'meta_title',
            'meta_description' => 'meta_description',
        ],

        // Status value mapping: growthatlas_status => your_model_status
        'status_map' => [
            'draft'     => 'draft',
            'published' => 'published',
        ],

        // Column name for publish_status in your model
        'status_column' => 'status',

        // Column for the growthatlas_draft_id meta (used for idempotency)
        'growthatlas_id_column' => 'growthatlas_draft_id',
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
    */
    'log_inbound' => env('GROWTHATLAS_LOG_INBOUND', false),

    /*
    |--------------------------------------------------------------------------
    | Filament Integration (optional)
    |--------------------------------------------------------------------------
    | Set filament_page = true (or GROWTHATLAS_FILAMENT=true in .env) to register
    | the GrowthAtlas Connector Status page in your Filament admin panel.
    | Requires filament/filament to be installed.
    |
    | filament_panel_id — the ID of the Filament panel to register the page in.
    | Leave null to use the first discovered panel.
    */
    'filament_page'     => env('GROWTHATLAS_FILAMENT', false),
    'filament_panel_id' => null,
];
