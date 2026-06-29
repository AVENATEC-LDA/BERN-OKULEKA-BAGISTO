<?php

use Illuminate\Support\Facades\Route;
use Webkul\Suggestion\Http\Controllers\Shop\SuggestionController;

Route::get('ajax-search', [SuggestionController::class, 'search'])
    ->name('search_suggestion.search.index')
    ->middleware('shop');
