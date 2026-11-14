<?php

namespace App\Controllers;

/**
 * Bridges legacy PHP pages until each module is fully migrated to CI controllers/views.
 */
class Legacy extends BaseController
{
    private string $legacyPath = ROOTPATH . 'legacy' . DIRECTORY_SEPARATOR;

    public function page(string $file = '')
    {
        $allowed = [
            'accounts', 'inventory', 'orders', 'reports', 'settings',
            'productinventory', 'producthistory', 'ingredienthistory',
            'receipt', 'export_receipt',
        ];

        $file = preg_replace('/\.php$/', '', $file);
        if (!in_array($file, $allowed, true)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $path = $this->legacyPath . $file . '.php';
        if (!is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        chdir($this->legacyPath);

        helper('legacy');
        gabs_sync_legacy_session();

        ob_start();
        include $path;
        $html = ob_get_clean();

        return $this->response->setBody(gabs_fix_legacy_html($html));
    }
}
