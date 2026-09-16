<?php

namespace App\Services\Email;

class EmailBrandingService
{
    public function wrap(string $html, ?string $sourceType = null, ?string $hint = null): string
    {
        if ($this->alreadyBranded($html)) {
            return $html;
        }

        $brand = $this->brand($sourceType, $hint);

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:'.$brand['background'].';margin:0;padding:32px 12px;color:#172033;font-family:Arial,Helvetica,sans-serif;">'
            .'<tr><td align="center">'
            .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:540px;">'
            .'<tr><td style="padding:4px 2px 26px;">'
            .'<img src="'.$brand['logo'].'" alt="'.$brand['name'].'" width="150" style="display:block;height:auto;max-width:150px;">'
            .'</td></tr>'
            .'<tr><td style="background:#ffffff;border-radius:24px;padding:34px 40px 30px;font-size:14px;line-height:1.55;">'
            .$html
            .'</td></tr>'
            .'<tr><td style="padding:28px 2px 0;color:#ffffff;font-size:13px;font-weight:bold;">'.$brand['name'].'</td></tr>'
            .'<tr><td style="padding:16px 0 12px;color:'.$brand['footer'].';font-size:12px;">This is an automated message from '.$brand['name'].'.</td></tr>'
            .'</table></td></tr></table>';
    }

    private function alreadyBranded(string $html): bool
    {
        return str_contains($html, 'gis-xl-logo.png')
            || str_contains($html, 'gms-stone.com/image/logo.png')
            || str_contains($html, 'logo-jeweal.png');
    }

    private function brand(?string $sourceType, ?string $hint): array
    {
        $type = strtolower((string) $sourceType.' '.(string) $hint);

        if (str_contains($type, 'gms')) {
            return [
                'name' => 'GMS Stone',
                'logo' => 'https://gms-stone.com/image/logo.png',
                'background' => '#00453F',
                'footer' => '#d9ebe6',
            ];
        }

        if (str_contains($type, 'gis')) {
            return [
                'name' => 'GIS247',
                'logo' => 'https://gis247.net/assets/v2/images/gis-xl-logo.png',
                'background' => '#8ed8d4',
                'footer' => '#24aeb0',
            ];
        }

        return [
            'name' => 'Jeweal',
            'logo' => 'https://jeweal.com/wp-content/themes/fourteen-blog/libarm/img/nav/logo-jeweal.png',
            'background' => '#1a1c4a',
            'footer' => '#d9dff7',
        ];
    }
}
