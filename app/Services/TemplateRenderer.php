<?php

namespace App\Services;

use App\Models\Contact;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class TemplateRenderer
{
    public function render(string $html, Contact $contact): string
    {
        $fields = ['first_name' => $contact->first_name, 'last_name' => $contact->last_name, 'email' => $contact->email, 'company' => $contact->company, 'unsubscribe_url' => route('unsubscribe', ['token' => $contact->unsubscribe_token])];
        $html = preg_replace_callback('/\{\{\s*([a-z_]+)(?:\|([^}]*))?\s*\}\}/u', fn ($m) => e($fields[$m[1]] ?? ($m[2] ?? '')), $html);

        return $this->sanitize($html);
    }

    public function sanitize(string $html): string
    {
        return (new HtmlSanitizer((new HtmlSanitizerConfig)->allowSafeElements()->allowElement('table', ['width', 'cellpadding', 'cellspacing', 'role', 'style'])->allowElement('td', ['style', 'width'])->allowElement('tr', ['style'])->allowAttribute('style', '*')->allowLinkSchemes(['https', 'http', 'mailto'])->allowMediaSchemes(['https'])))->sanitize($html);
    }

    /**
     * @param  list<array{type?:string,data?:array<string,mixed>}>  $blocks
     */
    public function blocks(array $blocks): string
    {
        $html = '<table role="presentation" width="100%" style="font-family:Arial,sans-serif;background:#f5f5f7"><tr><td style="padding:32px"><table role="presentation" width="600" style="max-width:100%;margin:auto;background:white"><tr><td style="padding:36px">';
        foreach ($blocks as $block) {
            $d = $block['data'] ?? [];
            $html .= match ($block['type'] ?? 'text') {
                'heading' => '<h1 style="font-size:28px;color:#202135">'.e($d['text'] ?? '').'</h1>',
                'text' => '<div style="font-size:16px;line-height:1.7;color:#4b4d60">'.$this->sanitize($d['text'] ?? '').'</div>',
                'button' => '<p style="padding:16px 0"><a href="'.e($d['url'] ?? '').'" style="background:#635bff;color:white;padding:14px 24px;border-radius:6px;text-decoration:none">'.e($d['label'] ?? 'Learn more').'</a></p>',
                'image' => '<img src="'.e($d['url'] ?? '').'" alt="'.e($d['alt'] ?? '').'" width="528" style="max-width:100%">',
                'divider' => '<hr>',
                default => '',
            };
        }

        $html = $this->sanitize($html.'<p style="font-size:12px;color:#777"><a href="https://mailora.invalid/merge-unsubscribe">Unsubscribe</a></p></td></tr></table></td></tr></table>');

        return str_replace('href="https://mailora.invalid/merge-unsubscribe"', 'href="{{unsubscribe_url}}"', $html);
    }
}
