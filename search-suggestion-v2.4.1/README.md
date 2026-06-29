# Bagisto Search Suggestion Extension

## 1. Introduction

The Bagisto Search Suggestion extension helps users complete their searches faster by providing real-time suggestions as they type. It also makes your website more engaging and user-friendly.

This extension comes packed with powerful features that help your business scale effortlessly:
1. Product category is visible in the auto search suggestions.
2. Product image is displayed in the auto search suggestion results.
3. Product price is shown in the search suggestion.
4. Total number of searched terms is displayed in the search suggestion.
5. Admin can disable the visibility of categories, products, or terms in the auto search suggestions.
6. Admin can set the total number of categories and products visible in the auto search suggestions.

## 2. Requirements

- **Bagisto Version**: v2.4.x

## 3. Installation

To install the Bagisto Search Suggestion Extension, follow these steps:

1. Unzip the extension package and merge the `packages` folder into your project root directory.

2. Open the `composer.json` file and add the following line inside  the `autoload > psr-4` section:

```json
"Webkul\\Suggestion\\": "packages/Webkul/Suggestion/src"
```

3. In the `bootstrap/providers.php` file, add the following line under the providers array:

```php
Webkul\Suggestion\Providers\SuggestionServiceProvider::class,
```

4. Run the following commands to complete the setup:

```bash
composer dump-autoload
```

```bash
php artisan search-suggestion:install
```

That's it! Now you can run the project on your specified domain.

## 4. Configuration

After successful installation, follow these steps to configure the extension:

1. Login to the Admin Panel.
2. Navigate to **Configuration → Search Suggestion**.
3. Configure the following settings:
   * **Status** — Enable or disable the Search Suggestion feature.
   * **Minimum Search Terms** — Set the minimum number of characters required to display search suggestions.
   * **Products Limit** — Define the maximum number of products displayed in search suggestions.
   * **Show Searched Terms** — Enable or disable previously searched terms in suggestions.
   * **Show Products** — Enable or disable product suggestions.
   * **Show Categories** — Enable or disable category suggestions.
4. Click on **Save Configuration** to apply the changes.
