<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Shared\Providers\ModularServiceProvider;
use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class ModularBoundaryTest extends TestCase
{
    /**
     * Parse all imported namespaces from a PHP file using PhpParser AST.
     *
     * @return list<string>
     */
    private function extractImportsFromFile(string $filePath): array
    {
        $code = file_get_contents($filePath);
        if ($code === false) {
            return [];
        }

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $stmts = $parser->parse($code);
        if ($stmts === null) {
            return [];
        }

        $imports = [];

        $traverser = new NodeTraverser;
        $traverser->addVisitor(new class($imports) extends NodeVisitorAbstract
        {
            /** @var list<string> */
            public array $imports;

            /**
             * @param  list<string>  $imports
             */
            public function __construct(array &$imports)
            {
                $this->imports = &$imports;
            }

            public function enterNode(Node $node)
            {
                if ($node instanceof Use_) {
                    foreach ($node->uses as $use) {
                        $this->imports[] = $use->name->toString();
                    }
                }

                return null;
            }
        });

        $traverser->traverse($stmts);

        return $imports;
    }

    /**
     * Get all PHP files in a directory.
     *
     * @return list<string>
     */
    private function getPhpFiles(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Invariant: Modul dilarang mengimpor Infrastructure / Models internal milik modul lain secara langsung.
     * Komunikasi lintas modul wajib melalui Application layer (Services, Contracts, Events).
     */
    public function test_modules_cannot_import_other_modules_infrastructure_or_models(): void
    {
        $modules = ModularServiceProvider::getRegisteredModules();
        $violations = [];

        foreach ($modules as $currentModule) {
            $moduleDir = app_path("Modules/{$currentModule}");
            $phpFiles = $this->getPhpFiles($moduleDir);

            foreach ($phpFiles as $filePath) {
                $imports = $this->extractImportsFromFile($filePath);

                foreach ($imports as $importedClass) {
                    foreach ($modules as $targetModule) {
                        if ($targetModule === $currentModule) {
                            continue;
                        }

                        // Disallow direct import of another module's Infrastructure (Models, Repositories, Migrations)
                        $forbiddenPrefix = "App\\Modules\\{$targetModule}\\Infrastructure";

                        if (str_starts_with($importedClass, $forbiddenPrefix)) {
                            $violations[] = sprintf(
                                'File [%s] in module [%s] illegally imports [%s] from module [%s]. Cross-module interactions must go through Application layer.',
                                $filePath,
                                $currentModule,
                                $importedClass,
                                $targetModule
                            );
                        }
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Modular monolith boundary violations detected:\n".implode("\n", $violations)
        );
    }

    /**
     * Invariant: Domain layer harus murni (pure PHP).
     * Dilarang mengimpor controller atau HTTP Request di dalam Domain layer.
     */
    public function test_domain_layer_must_not_import_http_artifacts(): void
    {
        $modules = ModularServiceProvider::getRegisteredModules();
        $violations = [];

        foreach ($modules as $module) {
            $domainDir = app_path("Modules/{$module}/Domain");
            $phpFiles = $this->getPhpFiles($domainDir);

            foreach ($phpFiles as $filePath) {
                $imports = $this->extractImportsFromFile($filePath);

                foreach ($imports as $importedClass) {
                    if (
                        str_starts_with($importedClass, 'Illuminate\Http') ||
                        str_starts_with($importedClass, 'Inertia')
                    ) {
                        $violations[] = sprintf(
                            'Domain class [%s] in module [%s] illegally imports HTTP artifact [%s]. Domain must remain pure.',
                            $filePath,
                            $module,
                            $importedClass
                        );
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Domain purity violations detected:\n".implode("\n", $violations)
        );
    }

    /**
     * Invariant: Controller harus berada di Interface/Http/Controllers.
     */
    public function test_controllers_must_reside_in_interface_layer(): void
    {
        $modules = ModularServiceProvider::getRegisteredModules();
        $violations = [];

        foreach ($modules as $module) {
            $moduleDir = app_path("Modules/{$module}");
            $phpFiles = $this->getPhpFiles($moduleDir);

            foreach ($phpFiles as $filePath) {
                $fileName = basename($filePath);

                if (str_ends_with($fileName, 'Controller.php')) {
                    $expectedSubpath = "Modules/{$module}/Interface/Http/Controllers";
                    if (! str_contains(str_replace('\\', '/', $filePath), $expectedSubpath)) {
                        $violations[] = sprintf(
                            'Controller [%s] is not placed in the expected Interface path [%s].',
                            $filePath,
                            $expectedSubpath
                        );
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Controller placement violations detected:\n".implode("\n", $violations)
        );
    }

    /**
     * Verify that the AST boundary checker properly detects a simulated illegal cross-module import.
     */
    public function test_boundary_checker_detects_illegal_cross_module_import(): void
    {
        $dummyCode = <<<'PHP'
        <?php
        namespace App\Modules\Academic\Application;
        use App\Modules\Core\Infrastructure\Models\SchoolModel;
        class AcademicService {}
        PHP;

        $tempFile = tempnam(sys_get_temp_dir(), 'arch_test_');
        $this->assertIsString($tempFile);
        file_put_contents($tempFile, $dummyCode);

        try {
            $imports = $this->extractImportsFromFile($tempFile);
            $this->assertContains('App\Modules\Core\Infrastructure\Models\SchoolModel', $imports);

            $isViolation = false;
            foreach ($imports as $import) {
                if (str_starts_with($import, 'App\Modules\Core\Infrastructure')) {
                    $isViolation = true;
                    break;
                }
            }

            $this->assertTrue($isViolation, 'Scanner should detect illegal cross-module infrastructure import.');
        } finally {
            @unlink($tempFile);
        }
    }
}
