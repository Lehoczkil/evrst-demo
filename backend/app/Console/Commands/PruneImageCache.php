<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Walk storage/app/public/cache/img/ and delete any cache file whose
 * mtime is older than the configured retention window. Empty prefix
 * directories are removed afterwards. Scheduled daily — see
 * routes/console.php.
 */
class PruneImageCache extends Command
{
    protected $signature = 'image-cache:prune {--days=30 : Retention window in days}';

    protected $description = 'Delete cached image variants older than the retention window';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days)->getTimestamp();

        $disk = Storage::disk('public');
        $root = $disk->path('cache/img');

        if (! is_dir($root)) {
            $this->info('No image cache directory present; nothing to prune.');
            return self::SUCCESS;
        }

        $deleted = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $entry) {
            /** @var \SplFileInfo $entry */
            if ($entry->isFile() && $entry->getMTime() < $cutoff) {
                @unlink($entry->getPathname());
                $deleted++;
            } elseif ($entry->isDir()) {
                @rmdir($entry->getPathname());
            }
        }

        $this->info("Pruned {$deleted} cached image variant(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
