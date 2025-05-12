<?php
namespace App\Core;

abstract class Controller
{
    protected function render(string $view, array $params = []): void
    {
        extract($params);
        $viewFile = __DIR__ . '/../../views/' . $view . '.phtml';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: $viewFile");
        }
        include __DIR__ . '/../../views/layout/header.phtml';
        include $viewFile;
        include __DIR__ . '/../../views/layout/footer.phtml';
    }
}
