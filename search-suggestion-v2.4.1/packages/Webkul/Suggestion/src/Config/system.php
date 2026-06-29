<?php

return [
    [
        'key' => 'suggestion',
        'name' => 'suggestion::app.admin.configuration.index.search-suggestion.title',
        'info' => 'suggestion::app.admin.configuration.index.search-suggestion.info',
        'sort' => 1,
    ], [
        'key' => 'suggestion.suggestion',
        'name' => 'suggestion::app.admin.configuration.index.search-suggestion.title',
        'info' => 'suggestion::app.admin.configuration.index.search-suggestion.info',
        'icon' => 'settings/settings.svg',
        'sort' => 1,
    ], [
        'key' => 'suggestion.suggestion.general',
        'name' => 'suggestion::app.admin.configuration.index.search-suggestion.general.title',
        'info' => 'suggestion::app.admin.configuration.index.search-suggestion.general.info',
        'sort' => 1,
        'fields' => [
            [
                'name' => 'status',
                'title' => 'suggestion::app.admin.configuration.index.search-suggestion.general.status',
                'type' => 'boolean',
                'channel_based' => true,
            ], [
                'name' => 'min_search_terms',
                'title' => 'suggestion::app.admin.configuration.index.search-suggestion.general.min-search-terms',
                'type' => 'text',
                'validation' => 'required|numeric|between:1,5',
                'channel_based' => true,
            ], [
                'name' => 'products_limit',
                'title' => 'suggestion::app.admin.configuration.index.search-suggestion.general.products-limit',
                'type' => 'text',
                'validation' => 'required|numeric|between:1,10',
                'channel_based' => true,
            ], [
                'name' => 'show_searched_terms',
                'title' => 'suggestion::app.admin.configuration.index.search-suggestion.general.show-searched-terms',
                'type' => 'boolean',
                'channel_based' => true,
            ], [
                'name' => 'show_categories',
                'title' => 'suggestion::app.admin.configuration.index.search-suggestion.general.show-categories',
                'type' => 'boolean',
                'channel_based' => true,
            ],
        ],
    ],
];
