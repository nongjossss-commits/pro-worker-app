<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

class PruneSoftDeletes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prune-soft-deletes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently deletes soft-deleted records older than a configured number of days.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to prune soft-deleted records...');

        $days = Config::get('app.prune_soft_deletes_after_days', 30);
        $totalPruned = 0;

        $models = $this->getModels();

        foreach ($models as $modelClass) {
            if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($modelClass))) {
                $this->info("Checking model: {$modelClass}");

                // Use the model's own deleted_at column name
                $modelInstance = new $modelClass();
                $deletedAtColumn = $modelInstance->getDeletedAtColumn();

                $prunedCount = $modelClass::onlyTrashed()
                    ->where($deletedAtColumn, '<=', now()->subDays($days))
                    ->forceDelete();

                if ($prunedCount > 0) {
                    $this->line(" - Pruned {$prunedCount} records from " . class_basename($modelClass));
                    $totalPruned += $prunedCount;
                }
            }
        }

        $this->info("Pruning complete. Total records pruned: {$totalPruned}.");
    }

    /**
     * Get all the model classes in the application.
     *
     * @return array
     */
    protected function getModels(): array
    {
        $models = [];
        $path = App::path('Models');
        $files = File::allFiles($path);

        foreach ($files as $file) {
            $namespace = App::getNamespace();
            $class = $namespace . 'Models\\' . str_replace(
                ['/', '.php'],
                ['\\', ''],
                $file->getRelativePathname()
            );

            if (class_exists($class) && is_subclass_of($class, Model::class)) {
                $models[] = $class;
            }
        }

        return $models;
    }
}