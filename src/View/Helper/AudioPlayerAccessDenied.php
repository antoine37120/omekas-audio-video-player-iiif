<?php
namespace AudioPlayer\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Laminas\View\Renderer\RendererInterface;

/**
 * Affiche le message « Access Denied Text » configuré dans le module, en
 * remplaçant le jeton {legal_url} par l'URL des mentions légales du site.
 *
 * Ce message sert à deux endroits : dans le player (quand la liste IIIF est
 * refusée) et à la place du player (quand le média principal est privé).
 */
class AudioPlayerAccessDenied extends AbstractHelper
{
    public function __invoke(): string
    {
        $view = $this->getView();
        $text = $view->setting('audioplayer_access_denied_text', '');
        if (!$text) {
            return '';
        }

        $siteSlug = $view->params()->fromRoute('site-slug');
        if (!$siteSlug) {
            $site = $view->currentSite();
            $siteSlug = $site ? $site->slug() : null;
        }

        if ($siteSlug) {
            $legalUrl = $view->url('site/page', [
                'site-slug' => $siteSlug,
                'page-slug' => 'mentions-legales',
            ]);
            $text = str_replace('{legal_url}', $view->escapeHtml($legalUrl), $text);
        } else {
            $text = str_replace('{legal_url}', '', $text);
        }

        return '<div class="access-denied-message">' . $text . '</div>';
    }
}
