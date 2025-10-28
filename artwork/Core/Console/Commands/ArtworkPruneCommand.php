<?php

namespace Artwork\Core\Console\Commands;

use Illuminate\Database\Console\PruneCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Finder\Finder;

class ArtworkPruneCommand extends PruneCommand
{
    protected $description = 'Prune (artwork) models that are no longer needed';

    /**
     * Determine if the given class is a prunable Eloquent model.
     */
    protected function isPrunable(string $model): bool
    {
        if (! class_exists($model)) {
            return false;
        }

        if (! is_subclass_of($model, \Illuminate\Database\Eloquent\Model::class)) {
            return false;
        }

        // Check if the model uses the Prunable trait
        $uses = class_uses_recursive($model);
        if (! in_array(\Illuminate\Database\Eloquent\Prunable::class, $uses, true)) {
            return false;
        }

        // Ensure the model defines the prunable() query method
        return method_exists($model, 'prunable');
    }

    final protected function models(): Collection
    {
        if (! empty($models = $this->option('model'))) {
            return collect($models)->filter(function ($model) {
                return class_exists($model);
            })->values();
        }

        $except = $this->option('except');

        if (! empty($models) && ! empty($except)) {
            throw new InvalidArgumentException('The --models and --except options cannot be combined.');
        }

        return $this->getNamespaces()
            ->when(!empty($except), function ($models) use ($except) {
                return $models->reject(function ($model) use ($except) {
                    return in_array($model, $except);
                });
            })->filter(function ($model) {
                return class_exists($model);
            })->filter(function ($model) {
                return $this->isPrunable($model);
            })->values();
    }

    protected function getNamespaces(): Collection
    {
        //if additional logic is required just overwrite this function in derived class and implement logic in
        //another protected get(*)ModelNamespaces-function to concat
        return $this
            ->getAppModelNamespaces()
            ->concat($this->getArtworkModelNamespaces()->toArray());
    }

    final protected function getAppModelNamespaces(): Collection
    {
        if (!is_dir($this->getPath())) {
            return collect();
        }

        return collect(Finder::create()->in($this->getPath())->files()->name('*.php'))
            ->map(function ($model) {
                return $this->laravel->getNamespace() . str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($model->getRealPath(), realpath(app_path()) . DIRECTORY_SEPARATOR)
                );
            });
    }

    final protected function getArtworkModelNamespaces(): Collection
    {
        return collect(Finder::create()
            ->in(dirname($this->getPath(), 2) . '/artwork/Modules/*/Models')->files()->name('*.php'))
            ->map(function ($model) {
                return str_replace(
                    ['/', '.php', 'artwork\\'],
                    ['\\', '', 'Artwork\\'],
                    Str::after(
                        $model->getRealPath(),
                        str_replace('app/', '', realpath(app_path()) . DIRECTORY_SEPARATOR)
                    )
                );
            });
    }
}
