<?php

namespace Imran\WebShell\Services\Artisan;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Imran\WebShell\Services\Process\ProcessManager;
use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\CommandResult;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ArtisanService
{
    public function __construct(
        protected ProcessManager $processes,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function catalog(): array
    {
        $groups = [];

        foreach (Artisan::all() as $name => $command) {
            if (! $command instanceof Command) {
                continue;
            }

            if (str_starts_with($name, 'webshell:')) {
                continue;
            }

            $namespace = Str::before($name, ':');
            $namespace = $namespace === $name ? 'Application' : Str::headline($namespace);

            $groups[$namespace][] = [
                'name' => $name,
                'description' => (string) $command->getDescription(),
                'hidden' => $command->isHidden(),
            ];
        }

        ksort($groups);

        foreach ($groups as &$commands) {
            usort($commands, fn ($a, $b) => $a['name'] <=> $b['name']);
        }

        return collect($groups)
            ->map(fn ($commands, $namespace) => [
                'namespace' => $namespace,
                'commands' => $commands,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(string $name): array
    {
        $command = Artisan::all()[$name] ?? null;

        if (! $command instanceof Command) {
            abort(404, "Unknown Artisan command [{$name}].");
        }

        $definition = $command->getDefinition();

        return [
            'name' => $name,
            'description' => (string) $command->getDescription(),
            'help' => (string) $command->getHelp(),
            'arguments' => collect($definition->getArguments())->map(fn (InputArgument $argument) => [
                'name' => $argument->getName(),
                'description' => $argument->getDescription(),
                'required' => $argument->isRequired(),
                'array' => $argument->isArray(),
                'default' => $argument->getDefault(),
            ])->values()->all(),
            'options' => collect($definition->getOptions())->map(fn (InputOption $option) => [
                'name' => $option->getName(),
                'shortcut' => $option->getShortcut(),
                'description' => $option->getDescription(),
                'value_required' => $option->isValueRequired(),
                'value_optional' => $option->isValueOptional(),
                'accepts_value' => $option->acceptValue(),
                'array' => $option->isArray(),
                'negatable' => method_exists($option, 'isNegatable') ? $option->isNegatable() : false,
                'default' => $option->getDefault(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function run(string $name, array $parameters = [], array $context = []): CommandResult
    {
        $tokens = [$name];

        foreach ($parameters as $key => $value) {
            if (is_int($key)) {
                $tokens[] = (string) $value;

                continue;
            }

            if ($value === false || $value === null) {
                continue;
            }

            $flag = str_starts_with((string) $key, '-') ? (string) $key : '--'.$key;

            if ($value === true) {
                $tokens[] = $flag;

                continue;
            }

            $tokens[] = $flag.'='.$this->quote((string) $value);
        }

        return $this->processes->run(CommandRequest::make(
            'php artisan '.implode(' ', $tokens),
            array_merge($context, ['source' => 'artisan']),
        ));
    }

    protected function quote(string $value): string
    {
        if ($value === '' || preg_match('/\s/', $value)) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }
}
