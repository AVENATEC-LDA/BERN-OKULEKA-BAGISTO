<?php

namespace Webkul\Suggestion\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Suggestion\Providers\SuggestionServiceProvider;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search-suggestion:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installs and configures the Search Suggestion package.';

    /**
     * Install and configure Search Suggestion.
     */
    public function handle()
    {
        $this->call('vendor:publish', [
            '--provider' => SuggestionServiceProvider::class,
            '--force' => true,
        ]);

        $this->call('optimize:clear');

        $this->components->info('🎉 Search Suggestion package installed successfully!');
    }
}
