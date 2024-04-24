<?php 
namespace Felixkpt\Nestedroutes\Console\Commands;

use Illuminate\Console\Command;

class PublishMigrationFilesCommand extends Command
{
    protected $signature = 'nestedroutes:publish-migrations';

    protected $description = 'Publish migration files for modifying permissions, roles, and users tables';

    public function handle()
    {
        $this->call('vendor:publish', [
            '--provider' => 'Felixkpt\Nestedroutes\NestedroutesServiceProvider',
            '--tag' => 'nestedroutes-migrations',
        ]);

        $this->info('Migration files published successfully.');
    }
}
