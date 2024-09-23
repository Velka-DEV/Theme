<?php

namespace YAAP\Theme\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\text;

/**
 * Class ThemeGeneratorCommand.
 */
class ThemeGeneratorV2Command extends BaseThemeCommand implements PromptsForMissingInput
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'theme:create-v2 {name} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate tailwind based theme structure';

    protected array $containerFolder;

    protected string $themeName;

    protected string $themeDescription;

    protected string $themeAuthor;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->themeName = $this->argument('name');

        if (!$this->themeName) {
            $this->error('Theme name is required.');
            return self::FAILURE;
        }
        $error = $this->validateValue($this->themeName);

        if ($error) {
            $this->error($error);

            return self::FAILURE;
        }

        $this->themeDescription = $this->ask('Theme description', 'A new FeatherLMS theme');
        $this->themeAuthor = $this->ask('Theme author name');

        // Check if the theme is already exists.
        if (!$this->canGenerateTheme()) {
            return self::FAILURE;
        }

        // Directories.
        $dirMapping = $this->config->get('theme.containerDir');
        $this->containerFolder = [
            'assets' => $dirMapping['assets'] ?? 'assets',
            'lang' => $dirMapping['lang'] ?? 'lang',
            'layout' => $dirMapping['layout'] ?? 'views/layouts',
            'partial' => $dirMapping['partial'] ?? 'views/partials',
            'view' => $dirMapping['view'] ?? 'views',
        ];

        $this->generateThemeStructure();
        $this->generateViteAssets();
        $this->generateViteConfig();
        $this->updateNodePackagesDependencies();

        $this->info('Append next lines to your scripts section in package.json:');
        $this->info("\"dev:{$this->themeName}\": \"vite --config themes/{$this->themeName}/vite.config.ts --mode development\",");
        $this->info("\"build:{$this->themeName}\": \"vite --config themes/{$this->themeName}/vite.config.ts --mode production\",");

        $this->info('Please also run `npm install` to install the required dependencies.');

        $this->info('Theme created successfully.');

        return self::SUCCESS;
    }


    protected function generatePublicFolders(): void
    {
        // public assets
        $this->makeAssetsFile('styles/.gitkeep');
        $this->makeAssetsFile('scripts/.gitkeep');
        $this->makeAssetsFile('fonts/.gitkeep');
    }

    protected function generateThemeStructure(): void
    {
        // Generate inside config.
        $this->makeFile('config.php', $this->fromTemplate('common/config/config.php'));
        $this->makeFile('theme.json', $this->getThemeInfoJson());

        $this->makeFile(
            $this->containerFolder['lang'] . '/en/labels.php',
            $this->fromTemplate('common/lang/labels.php')
        );

        $this->makeFile(
            $this->containerFolder['partial'] . '/header.blade.php',
            $this->fromTemplate('common/views/partials/header.blade.php')
        );
        $this->makeFile(
            $this->containerFolder['partial'] . '/footer.blade.php',
            $this->fromTemplate('common/views/partials/footer.blade.php')
        );

        $this->makeFile(
            $this->containerFolder['layout'] . '/master.blade.php',
            $this->fromTemplate('tailwind/views/layouts/master.blade.php')
        );

        $this->writeContent(
            app_path('View/Components/AppLayout.php'),
            $this->fromTemplate('tailwind/app/AppLayout.stub')
        );
        $this->makeFile(
            $this->containerFolder['view'] . '/hello.blade.php',
            $this->fromTemplate('tailwind/views/hello.blade.php')
        );
    }

    protected function updateNodePackagesDependencies(): void
    {
        $dependencies = [
            'tailwindcss' => '^3.4',
            'vite' => '^5.4',
            '@tailwindcss/forms' => '^0.5',
            '@tailwindcss/typography' => '^0.4',
            'typescript' => '^5.5',
            'autoprefixer' => '^10.4',
            'postcss-nesting' => '^13.0',
        ];

        $packageJsonPath = base_path('package.json');
        $packageJson = json_decode(file_get_contents($packageJsonPath), true);

        // If package is present replace it, otherwise add it. (should handle dev and devDependencies without duplication)
        $packageJson['devDependencies'] = array_merge($packageJson['devDependencies'], $dependencies);

        foreach ($dependencies as $dependency => $version) {
            if (isset($packageJson['dependencies'][$dependency])) {
                unset($packageJson['dependencies'][$dependency]);
            }
        }

        ksort($packageJson['devDependencies']);
        ksort($packageJson['dependencies']);

        file_put_contents($packageJsonPath, json_encode($packageJson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL);

        $this->info('Dependencies updated successfully.');
    }

    protected function generateViteAssets(): void
    {
        // frontend sources
        $assets = $this->containerFolder['assets'];

        $this->makeFile("$assets/images/.gitkeep");
        $this->makeFile("$assets/images/favicon.png", $this->fromTemplate('common/favicon.png'));

        $this->makeFile("$assets/styles/_variables.scss", $this->fromTemplate('tailwind/styles/_variables.scss'));
        $this->makeFile("$assets/styles/app.scss", $this->fromTemplate('tailwind/styles/app.scss'));

        $this->makeFile("$assets/fonts/.gitkeep");

        $this->makeFile("$assets/scripts/app.ts", $this->fromTemplate('tailwind/scripts/app.ts'));
        $this->makeFile("$assets/scripts/bootstrap.ts", $this->fromTemplate('tailwind/scripts/bootstrap.ts'));
    }

    protected function getThemeInfoJson(): string {
        return json_encode([
            'name' => $this->themeName,
            'description' => $this->themeDescription,
            'author' => $this->themeAuthor,
            'version' => '1.0.0',
            'thumbnail' => 'assets/images/thumbnail.png',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    protected function canGenerateTheme(): bool
    {
        $directoryExists = $this->directoryExists();
        if (!$directoryExists) {
            return true;
        }

        $name = $this->getTheme()->getName();

        $this->error("Theme \"{$name}\" already exists.");

        $forceOverride = $this->option('force')
            || $this->confirm("Are you sure want to override \"{$name}\" theme folder?");

        if ($forceOverride) {
            $this->warn("Overriding Theme \"{$name}\".");
        } else {
            $this->error("Generation of Theme \"{$name}\" has been canceled.");
        }

        return $forceOverride;
    }

    /**
     * Make directory.
     */
    protected function makeDir(string $path): void
    {
        if (!$this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }
    }

    /**
     * Make file.
     */
    protected function makeFile(string $file, string $content = ''): void
    {
        $this->writeContent($this->getTheme()->pathForItem($file), $content);
    }

    /**
     * Make file.
     */
    protected function makeAssetsFile(string $file, string $template = ''): void
    {
        $this->writeContent($this->getAssetsPath($file), $template);
    }

    protected function writeContent(string $filePath, string $content): void
    {
        if (!$this->files->exists($filePath) || $this->option('force')) {
            $this->makeDir(pathinfo($filePath, PATHINFO_DIRNAME));

            $this->files->put($filePath, $content);
        }
    }

    /**
     * Get template content.
     */
    protected function fromTemplate(string $templateName, array $replacements = []): string
    {
        $templatePath = $this->getTemplatePath($templateName);

        $replacements = array_merge($replacements, [
            '%theme_name%' => $this->getTheme()->getName(),
        ]);

        $content = $this->files->get($templatePath);

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $content
        );
    }

    protected function getTemplatePath(string $templateName): string
    {
        $templatesPath = realpath(__DIR__ . '/../../stubs');

        return "{$templatesPath}/{$templateName}";
    }

    private function validateValue($value): ?string
    {
        return match (true) {
            empty($value) => 'Name is required.',

            !empty(
                preg_match(
                    '/[^a-zA-Z0-9\-_\s]/',
                    $value,
                )
            ) => 'Name must be alphanumeric, dash, space or underscore.',

            $this->files->isDirectory(
                $this->makeTheme($value)->getRootDirectoryPath()
            ) => "Theme \"{$value}\" already exists.",

            default => null,
        };
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            new InputArgument('name', InputArgument::REQUIRED, 'A name of the new theme'),
            new InputArgument('assets', InputArgument::OPTIONAL, 'A type of assets to install', 'vite', ['vite', 'mix']
            ),
        ];
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => fn () => text(
                label: 'What is a name of the new theme?',
                default: 'default',
                validate: fn ($value) => $this->validateValue($value)
            ),
        ];
    }

    protected function getOptions(): array
    {
        return [
            new InputOption('force', null, InputOption::VALUE_NONE, 'Force create theme with same name'),
        ];
    }

    protected function generateViteConfig(): void
    {
        $this->makeFile('vite.config.ts', $this->fromTemplate('tailwind/vite.config.ts', [
            '%theme_name%' => $this->getTheme()->getName(),
        ]));
        $this->makeFile('tailwind.config.js', $this->fromTemplate('tailwind/tailwind.config.js', [
            '%theme_name%' => $this->getTheme()->getName(),
        ]));
    }
}
